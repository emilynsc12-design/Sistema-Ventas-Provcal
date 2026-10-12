@echo off
REM ============================================
REM  SISTEMA DE VENTAS PROVCAL - Iniciar
REM  Doble clic para arrancar base de datos y web
REM ============================================
chcp 65001 >nul
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0iniciar.ps1"
