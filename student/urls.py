from django.urls import path
from . import views

urlpatterns = [
    path('', views.student, name='students'),
    path('add', views.add, name='add')
]