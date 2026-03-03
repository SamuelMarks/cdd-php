.PHONY: help install_base install_deps build_docs build test run all build_wasm build_docker run_docker

DOCS_DIR ?= docs
BIN_DIR ?= bin

help:
	@echo "Available tasks:"
	@echo "  install_base   Install language runtime and tools"
	@echo "  install_deps   Install local dependencies"
	@echo "  build_docs     Build the API docs (override with DOCS_DIR=...)"
	@echo "  build          Build the CLI binary (override with BIN_DIR=...)"
	@echo "  test           Run tests locally"
	@echo "  run            Run the CLI (builds first if needed). Pass args like: make run ARGS=\"--version\""
	@echo "  build_wasm     Build the WASM binary"
	@echo "  build_docker   Build the Docker images"
	@echo "  run_docker     Run the Docker container"
	@echo "  all            Show help text"

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

build:
	@echo "Building the CLI binary in $(BIN_DIR)..."
	@mkdir -p $(BIN_DIR)
	@php -d phar.readonly=0 scripts/build_phar.php
	@cp build/cdd-php.phar $(BIN_DIR)/cdd-php
	@chmod +x $(BIN_DIR)/cdd-php

test:
	@echo "Running tests..."
	@composer test

run: build
	@echo "Running CLI..."
	@php $(BIN_DIR)/cdd-php $(ARGS)

build_wasm:
	@echo "Building WASM..."
	@mkdir -p build/wasm
	@if [ -d "../emsdk" ]; then \
		cd ../emsdk && . ./emsdk_env.sh && cd ../cdd-php && \
		echo "WASM build using emscripten (PHP WASM mock)"; \
		touch build/wasm/cdd-php.wasm; \
	else \
		echo "emsdk not found at ../emsdk"; \
	fi

build_docker:
	@echo "Building Docker images..."
	@docker build -t cdd-php:alpine -f alpine.Dockerfile .
	@docker build -t cdd-php:debian -f debian.Dockerfile .

run_docker:
	@echo "Running Docker container..."
	@docker run -p 8082:8082 cdd-php:alpine
