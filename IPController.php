<?php

namespace Nirvana\IPConfig\Http\Controllers;

use App;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;


class IPController extends BaseController
{
	private $path = "";
	private $con_name = "";
	public function __construct(){
		$this->path = public_path()."/../../miq/bin/monitoriq";
	}

    public function index(Request $request)
    {
        return view('IPConfig::index');
    }

    public function getDefaultData(Request $request)
    {
    	if($request->get('macAddress')){
			$cmd_str = "python3 {$this->path}/ipconfig.py -s -v";
			$process = new Process($cmd_str);
			$process->run();
			$output_value = preg_split('/\n/', $process->getOutput(), -1, PREG_SPLIT_NO_EMPTY);

			foreach ($output_value as $data) {
				if(strpos($data, "not found") !== false){
					$output = [];
					return $output;
				}
				$data = split("-", $data);
				$mac_details[$data[0]] = $data[1];
			}
		    for($i = 1; $i <= sizeof($output_value); $i++){
		    	$key1 = "disable".$i;
		    	$key2 = "port".$i;
		    	$disable_ports[$key1] = false; 
		    	$connected_ports[$key2] = "Connected";
		    }
			$output['macDetails'] = $mac_details;
			$output['disablePorts'] = $disable_ports;
			$output['connectedPorts'] = $connected_ports;
			return json_encode($output);
	    }
	}

	public function activateConnection(Request $request){
		$con_name = $request->get('conname');
		$cmd_str = "python3 {$this->path}/ipconfig.py -a {$con_name} -v";
		$process = new Process($cmd_str);
		$process->run();
		$output_value = preg_split('/\n/', $process->getOutput(), -1, PREG_SPLIT_NO_EMPTY)[1];
		if(strpos($response, "successfully ") !== false){
			$output['status'] = true;
			$output['result'] = $response;
		}
		else{
			$output['status'] = false;
			$output['result'] = "Failed to activate";	
		}
		return json_encode($output);
	}

	public function createConnection(Request $request){

		$ip_address = $request->get('ip');
		$host_name = $request->get('name');
		$subnet_mask = $request->get('sb');
		$gateway = $request->get('gw');
		$dns1 = $request->get('dns1');
		$dns2 = $request->get('dns2');
		$interface = $request->get('interface');

		$cmd_str = "python3 {$this->path}/ipconfig.py ";

		if(!empty($ip_address)){
			$valid = ip2long($ip_address); //check weather the ip address is valid or not
			if(!$valid){
				$invalid['status'] = false;
				$invalid['message'] = "IP Address is not valid";
				return json_encode($invalid);
			}
			$ip_array = explode('.', $ip_address);
			$con_name = 'con-'.end($ip_array);
			$cmd_str .= "-i {$ip_address} -n '{$con_name}' ";
		}
		else{
			$invalid['status'] = false;
			$invalid['message'] = "IP address is empty";
			return json_encode($invalid);	
		}
	
		if(!empty($gateway)){
			$cmd_str .= "-g {$gateway} ";
		}

		if(!empty($dns1) || !empty($dns2)){
			$cmd_str .= "-d {$dns1} {$dns2} ";
		}

		if(!empty($subnet_mask)){
			$cidr = $this->mask2cidr($subnet_mask); //calculate cidr based on given subnet mask
			if (floor($cidr) != $cidr){
				$invalid['status'] = false;
				$invalid['address'] = "subnet";
				$invalid['message'] = "Subnet Mask is not valid";
				return json_encode($invalid);
	
			}
			$cmd_str .= "-m {$subnet_mask} ";
		}
		else{
			$invalid['status'] = false;
			$invalid['message'] = "Subnet address is empty";
			return json_encode($invalid);
		}

		if(!empty($cidr))
			$cmd_str .= "-c {$cidr} ";

		if(isset($interface)){
			$cmd_str .= "-y {$interface} ";
		}

		$cmd_str .= "-v";
		
		$process = new Process($cmd_str);
		$process->run();
		$response = preg_split('/\n/', $process->getOutput(), -1, PREG_SPLIT_NO_EMPTY)[0];

		$output['Executable command'] = $cmd_str;
		
		if(strpos($response, "successfully") !== false){
			$output['status'] = true;
			$output['con_name'] = $con_name;
			$output['result'] = $response;
		}
		else{
			$output['status'] = false;
			$output['result'] = "Failed to create connection";	
		}
		return json_encode($output);
	}

	public function dhcpConnection(Request $request){
		$cmd_str = "python3 {$this->path}/ipconfig.py ";

		$interface = $request->get('interface');
		$cmd_str .= "-e dhcp -y {$interface} -v";
		$process = new Process($cmd_str);
		$process->run();
		$response = preg_split('/\n/', $process->getOutput(), -1, PREG_SPLIT_NO_EMPTY);
		$output['Executable command'] = $cmd_str;
		if(count($response) == 0){
			$output['status'] = false;
			$output['result'] = "Failed to create connection, please check your interface configuration";
		}
		else{
			$ip = last(explode(" ", $response[1]));
			if(strpos($response, "Successfully") !== false){
				$output['status'] = true;
				$output['ip_address'] = $ip;
				$output['result'] = $response;
			}
		}
		return json_encode($output);
	}

	private function mask2cidr($mask){
	  $long = ip2long($mask);
	  $base = ip2long('255.255.255.255');
	  return 32-log(($long ^ $base)+1,2);
	  /* xor-ing will give you the inverse mask,
	      log base 2 of that +1 will return the number
	      of bits that are off in the mask and subtracting
	      from 32 gets you the cidr notation */
	}
}
