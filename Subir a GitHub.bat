@echo off
REM ============================================
REM  SISTEMA DE VENTAS PROVCAL - Subir a GitHub
REM  Doble clic para subir todos tus cambios
REM ============================================
chcp 65001 >nul
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0publicar.ps1"
