@echo off
if "%1"=="run" goto run
goto :eof

:run
shift
set "ARGS="
:run_loop
if "%~1"=="" goto run_execute
set "ARGS=%ARGS% %1"
shift
goto run_loop

:run_execute
echo php bin\cdd-php %ARGS%
goto :eof
