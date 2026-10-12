@echo off
REM ============================================
REM  SISTEMA DE VENTAS PROVCAL - Detener
REM  Doble clic para apagar web, base de datos e internet
REM ============================================
chcp 65001 >nul
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0detener.ps1"
