.PHONY: help install_base install_deps build_docs build test run all build_wasm build_docker run_docker

DOCS_DIR ?= docs
BIN_DIR ?= bin

help:
	@echo "Available tasks:"
	@echo "  install_base   Install language runtime and tools"
	@echo "  install_deps   Install local dependencies"
	@echo "  build_docs     Build the API docs (override with DOCS_DIR=...)"
	@echo "  build	  Build the CLI binary (override with BIN_DIR=...)"
	@echo "  test	   Run tests locally"
	@echo "  run	    Run the CLI (builds first if needed). Pass args like: make run ARGS=\"--version\""
	@echo "  build_wasm     Build the WASM binary"
	@echo "  build_docker   Build the Docker images"
	@echo "  run_docker     Run the Docker container"
	@echo "  all	    Show help text"

all: help

install_base:
	@echo "Installing base dependencies..."
	@sudo apt-get update && sudo apt-get install -y php php-cli php-xml php-mbstring composer

install_deps:
	@echo "Installing project dependencies..."
	@composer install

build_docs:
	@echo "Building API docs in $(DOCS_DIR)..."
	@mkdir -p $(DOCS_DIR)
	@php bin/cdd-php to_docs_json -i ./openapi.json -o $(DOCS_DIR)/docs.json || true

build: install_deps
	@echo "Building the CLI binary in build/..."
	@mkdir -p build
	@php -d phar.readonly=0 scripts/build_phar.php
	@chmod +x build/cdd-php

test:
	@echo "Running tests..."
	@composer test

run: install_deps
	@echo "Running CLI..."
	@php bin/cdd-php $(ARGS)
build_wasm: build
	@echo "Building WASM..."
	@mkdir -p build/wasm
	@echo "Downloading pre-compiled PHP WebAssembly runtime..."
	@curl -sfL "https://github.com/vmware-labs/webassembly-language-runtimes/releases/download/php/8.2.6+20230714-11be424/php-cgi-8.2.6-slim.wasm" -o build/wasm/cdd-php.wasm
	@echo "WASM runtime downloaded."
	@echo "Bundling the cdd-php.phar payload..."
	@cat build/cdd-php >> build/wasm/cdd-php.wasm
	@echo "Successfully bundled."

build_docker:
	@echo "Building Docker images..."
	@docker build -t cdd-php:alpine -f alpine.Dockerfile .
	@docker build -t cdd-php:debian -f debian.Dockerfile .

run_docker:
	@echo "Running Docker container..."
	@docker run -p 8082:8082 cdd-php:alpine
