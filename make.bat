@echo off
setlocal enabledelayedexpansion

set DOCS_DIR=docs
set BIN_DIR=build
set BIN_PATH=%BIN_DIR%\cdd-php.phar

if "%~1"=="" goto help
if "%~1"=="help" goto help
if "%~1"=="all" goto help
if "%~1"=="install_base" goto install_base
if "%~1"=="install_deps" goto install_deps
if "%~1"=="build_docs" goto build_docs
if "%~1"=="build" goto build
if "%~1"=="test" goto test
if "%~1"=="run" goto run
if "%~1"=="build_wasm" goto build_wasm
if "%~1"=="build_docker" goto build_docker
if "%~1"=="run_docker" goto run_docker
goto eof

:help
echo Available tasks:
echo   install_base   Install PHP and Composer (Requires Chocolatey)
echo   install_deps   Install PHP dependencies via Composer
echo   build_docs     Build the API docs into DOCS_DIR (default: docs)
echo   build          Build the CLI binary into BIN_DIR (default: build)
echo   test           Run tests locally
echo   run            Run the CLI (builds if necessary)
echo   build_wasm     Build a WASM version
echo   build_docker   Build the Docker images
echo   run_docker     Run the Docker container
echo   help           Show this help text
echo   all            Show this help text
goto eof

:install_base
echo Attempting to install PHP and Composer...
choco install php composer -y
goto eof

:install_deps
composer install
goto eof

:build_docs
if not exist "%DOCS_DIR%" mkdir "%DOCS_DIR%"
echo Building docs to %DOCS_DIR%...
php bin\cdd-php to_docs_json --no-imports --no-wrapping -i openapi.json -o "%DOCS_DIR%\docs.json"
goto eof

:build
if not exist "%BIN_DIR%" mkdir "%BIN_DIR%"
echo Building CLI binary to %BIN_PATH%...
php -d phar.readonly=0 scripts\build_phar.php "%BIN_PATH%"
goto eof

:test
php bin\cdd-php test
goto eof

:run
call :build
php "%BIN_PATH%" %*
goto eof

:build_wasm
echo Building WASM...
if exist "..sdk" (
    call ..sdksdk_env.bat
    emcc --version
    echo WASM build not fully implemented for PHP natively yet. See WASM.md.
) else (
    echo emsdk not found at ..sdk
)
goto eof

:build_docker
echo Building Docker images...
docker build -t offscale/cdd-php:alpine -f alpine.Dockerfile .
docker build -t offscale/cdd-php:debian -f debian.Dockerfile .
goto eof

:run_docker
echo Running Docker container...
docker run --rm offscale/cdd-php:alpine serve_json_rpc --port 8082 --listen 0.0.0.0
goto eof

:eof
endlocal