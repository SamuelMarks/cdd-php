@echo off
setlocal

set "DOCS_DIR=docs"
if not "%~2"=="" (
    if "%~1"=="build_docs" set "DOCS_DIR=%~2"
)
set "BIN_DIR=bin"
if not "%~2"=="" (
    if "%~1"=="build" set "BIN_DIR=%~2"
)

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

echo Unknown command: %~1
goto help

:help
echo Available tasks:
echo   install_base   Install language runtime and tools
echo   install_deps   Install local dependencies
echo   build_docs     Build the API docs (override with DOCS_DIR=...)
echo   build          Build the CLI binary (override with BIN_DIR=...)
echo   test           Run tests locally
echo   run            Run the CLI (builds first if needed)
echo   build_wasm     Build the WASM binary
echo   build_docker   Build the Docker images
echo   run_docker     Run the Docker container
echo   all            Show help text
goto :EOF

:install_base
echo Installing base dependencies... (Skipped on Windows, please install PHP manually)
goto :EOF

:install_deps
echo Installing project dependencies...
composer install
goto :EOF

:build_docs
echo Building API docs in %DOCS_DIR%...
if not exist "%DOCS_DIR%" mkdir "%DOCS_DIR%"
php bin\cdd-php to_docs_json -i .\openapi.json -o "%DOCS_DIR%\docs.json"
goto :EOF

:build
echo Building the CLI binary in %BIN_DIR%...
if not exist "%BIN_DIR%" mkdir "%BIN_DIR%"
php scripts\build_phar.php
copy build\cdd-php.phar "%BIN_DIR%\cdd-php"
goto :EOF

:test
echo Running tests...
composer test
goto :EOF

:run
call :build
echo Running CLI...
php "%BIN_DIR%\cdd-php" %*
goto :EOF

:build_wasm
call :build
echo Building WASM...
if not exist "build\wasm" mkdir "build\wasm"
echo Downloading pre-compiled PHP WebAssembly runtime...
curl -sL "https://github.com/vmware-labs/webassembly-language-runtimes/releases/download/php/8.2.6%%2B20230714-11be424/php-cgi-8.2.6-slim.wasm" -o build\wasm\cdd-php.wasm
echo WASM runtime downloaded.
echo Bundling the cdd-php.phar payload as a WebAssembly custom section...
php scripts\bundle_wasm_payload.php build\wasm\cdd-php.wasm build\cdd-php
echo Successfully bundled.
goto :EOF

:build_docker
echo Building Docker images...
docker build -t cdd-php:alpine -f alpine.Dockerfile .
docker build -t cdd-php:debian -f debian.Dockerfile .
goto :EOF

:run_docker
echo Running Docker container...
docker run -p 8082:8082 cdd-php:alpine
goto :EOF
