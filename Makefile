install_base:
	apt-get update && apt-get install -y php-cli composer

install_deps:
	composer install

build_docs:
	php bin/check_docs.php $(DOCS_DIR)

build:
	php scripts/build_phar.php $(BIN_DIR)

test:
	php bin/cdd-php test

run: build
	php bin/cdd-php.phar $(filter-out $@,$(MAKECMDGOALS))

help:
	@echo "Available targets:"
	@echo "  install_base   Install language runtime"
	@echo "  install_deps   Install dependencies"
	@echo "  build_docs     Build the API docs (override dir with DOCS_DIR)"
	@echo "  build          Build the CLI binary (override dir with BIN_DIR)"
	@echo "  build_wasm     Build the WASM binary"
	@echo "  test           Run tests"
	@echo "  run            Run the CLI (e.g., make run --version)"
	@echo "  help           Show this help message"

all: help

build_wasm:
	@echo "Building WASM..."
	@echo "WASM build is currently experimental for PHP."
	# Add WASM build steps when applicable

%:
	@:
