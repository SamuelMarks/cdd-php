@echo off
setlocal

set COMMAND=%1

if "%COMMAND%"=="" (
    goto help
)

if "%COMMAND%"=="install_base" (
    echo "Please install PHP manually on Windows."
    goto end
)

if "%COMMAND%"=="install_deps" (
    composer install
    goto end
)

if "%COMMAND%"=="build_docs" (
    set DOCS_DIR=%2
    if "%DOCS_DIR%"=="" set DOCS_DIR=docs
    php bin/check_docs.php %DOCS_DIR%
    goto end
)

if "%COMMAND%"=="build" (
    set BIN_DIR=%2
    if "%BIN_DIR%"=="" set BIN_DIR=bin
    php scripts/build_phar.php %BIN_DIR%
    goto end
)

if "%COMMAND%"=="build_wasm" (
    echo Building WASM...
    echo WASM build is currently experimental for PHP.
    goto end
)

if "%COMMAND%"=="test" (
    php bin/cdd-php test
    goto end
)

if "%COMMAND%"=="run" (
    call make.bat build
    shift
    set args=
    :loop
    if "%~1"=="" goto after_loop
    set args=%args% %1
    shift
    goto loop
    :after_loop
    php bin/cdd-php.phar %args%
    goto end
)

if "%COMMAND%"=="help" (
    goto help
)

if "%COMMAND%"=="all" (
    goto help
)

:help
echo Available targets:
echo   install_base   Install language runtime
echo   install_deps   Install dependencies
echo   build_docs     Build the API docs (pass dir as 2nd arg)
echo   build          Build the CLI binary (pass dir as 2nd arg)
echo   build_wasm     Build the WASM binary
echo   test           Run tests
echo   run            Run the CLI (e.g., make.bat run --version)
echo   help           Show this help message

:end
