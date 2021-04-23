from django.shortcuts import render
from django.http import HttpResponse

# Create your views here.
def student(request):
    return render(request, 'student.html', {'name' : 'pradeep'})

def add(request):
    val1 = int(request.POST['value1'])
    val2 = int(request.POST['value2'])
    res = val1+val2
    
    return render(request, 'result.html', {'result' : res})

def sub(request):
    val1 = int(request.POST['value1'])
    val2 = int(request.POST['value2'])
    res = val1-val2
    
    return render(request, 'result.html', {'result' : res})