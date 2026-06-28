#!/usr/bin/env bash
set -e

PORT=8080

if curl -s http://127.0.0.1:${PORT} >/dev/null; then
    echo "Mock server is already running on port ${PORT}."
    exit 0
fi

echo "Starting petstore mock server on port ${PORT}..."

MOCK_FILE="cdd-openapi-test-harness/mock_nginx.py"
if [ ! -f "$MOCK_FILE" ]; then
  MOCK_FILE="../mock_nginx.py"
fi

if command -v python3 >/dev/null 2>&1 && [ -f "$MOCK_FILE" ]; then
    python3 -c "import socket; socket.getfqdn = lambda x: \"localhost\"; exec(open(\"$MOCK_FILE\").read())" >/dev/null 2>python3 "$MOCK_FILE" >/dev/null 2>&1 &
    sleep 1
    if curl -s http://127.0.0.1:${PORT} >/dev/null; then
        echo "Mock server (python) is up!"
        exit 0
    fi
fi

# Remove any existing container just in case
docker rm -f petstore_mock >/dev/null 2>&1 || true

if command -v docker >/dev/null 2>&1; then
    docker run -d -p ${PORT}:8080 -e SWAGGER_HOST="http://127.0.0.1:${PORT}" --name petstore_mock swaggerapi/petstore >/dev/null 2>&1 || docker run -d -p ${PORT}:8080 --name petstore_mock swaggerapi/swagger-petstore-node >/dev/null 2>&1

    echo "Waiting for mock server to be ready..."
    for i in {1..30}; do
        if curl -s http://127.0.0.1:${PORT} >/dev/null; then
            echo "Mock server is up!"
            exit 0
        fi
        sleep 1
    done

    echo "Failed to start mock server."
    exit 1
else
    echo "Docker is not installed or not running. Please start the mock server manually."
    exit 1
fi
