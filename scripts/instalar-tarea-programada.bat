@echo off
rem Doble clic para crear la tarea de Windows de los respaldos automaticos.
rem Windows pedira permiso de Administrador; acepte el aviso.
powershell -NoProfile -Command "Start-Process powershell -Verb RunAs -ArgumentList '-NoProfile -ExecutionPolicy Bypass -File \"%~dp0instalar-tarea-programada.ps1\"'"
