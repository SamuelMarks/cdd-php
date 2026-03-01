.PHONY: all help install_base install_deps build_docs build test run build_wasm build_docker run_docker

DOCS_DIR ?= docs
BIN_DIR ?= build
BIN_PATH ?= $(BIN_DIR)/cdd-php

all: help

help:
	@echo "Available tasks:"
	@echo "  install_base   Install PHP and Composer (Debian/Ubuntu/macOS/rpm)"
	@echo "  install_deps   Install PHP dependencies via Composer"
	@echo "  build_docs     Build the API docs into DOCS_DIR (default: docs)"
	@echo "  build          Build the CLI binary into BIN_DIR (default: build)"
	@echo "  test           Run tests locally"
	@echo "  run            Run the CLI (builds if necessary). Use ARGS=\"--version\" to pass args"
	@echo "  build_wasm     Build a WASM version"
	@echo "  build_docker   Build the Docker images"
	@echo "  run_docker     Run the Docker container"
	@echo "  help           Show this help text"
	@echo "  all            Show this help text"

install_base:
	@echo "Attempting to install PHP and Composer..."
	@if [ -x "$$(command -v apt-get)" ]; then \
		sudo apt-get update && sudo apt-get install -y php-cli php-xml php-mbstring curl; \
	elif [ -x "$$(command -v yum)" ]; then \
		sudo yum install -y php-cli php-xml php-mbstring curl; \
	elif [ -x "$$(command -v brew)" ]; then \
		brew install php composer; \
	fi

install_deps:
	composer install

build_docs:
	@mkdir -p $(DOCS_DIR)
	@echo "Building docs to $(DOCS_DIR)..."
	php bin/cdd-php to_docs_json --no-imports --no-wrapping -i openapi.json -o $(DOCS_DIR)/docs.json || true

build:
	@mkdir -p $(BIN_DIR)
	@echo "Building CLI binary to $(BIN_PATH)..."
	php -d phar.readonly=0 scripts/build_phar.php $(BIN_PATH)
	@chmod +x $(BIN_PATH)

test:
	php bin/cdd-php test

run: build
	@$(BIN_PATH) $(filter-out $@,$(MAKECMDGOALS))

build_wasm:
	@echo "Building WASM via emsdk..."
	@if [ -d "../emsdk" ]; then \
		. ../emsdk/emsdk_env.sh && emcc --version; \
		echo "WASM build not fully implemented for PHP natively yet. See WASM.md."; \
	else \
		echo "emsdk not found at ../emsdk"; \
	fi

build_docker:
	docker build -t offscale/cdd-php:alpine -f alpine.Dockerfile .
	docker build -t offscale/cdd-php:debian -f debian.Dockerfile .

run_docker:
	docker run --rm offscale/cdd-php:alpine serve_json_rpc --port 8082 --listen 0.0.0.0

%:
	@: