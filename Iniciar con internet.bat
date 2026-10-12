@echo off
REM ============================================
REM  SISTEMA DE VENTAS PROVCAL - Con internet
REM  Arranca todo y da un enlace publico https
REM ============================================
chcp 65001 >nul
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0iniciar_con_internet.ps1"
