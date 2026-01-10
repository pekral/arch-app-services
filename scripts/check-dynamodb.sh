#!/bin/bash

set -e

DYNAMODB_HOST="${DYNAMODB_HOST:-localhost}"
DYNAMODB_PORT="${DYNAMODB_PORT:-8021}"
MAX_ATTEMPTS="${MAX_ATTEMPTS:-30}"
WAIT_SECONDS="${WAIT_SECONDS:-2}"
ENDPOINT_URL="http://${DYNAMODB_HOST}:${DYNAMODB_PORT}"

get_compose_cmd() {
    if command -v docker &> /dev/null && docker compose version &> /dev/null; then
        echo "docker compose"
    elif command -v docker-compose &> /dev/null; then
        echo "docker-compose"
    else
        echo ""
    fi
}

check_dynamodb_ready() {
    RESPONSE=$(curl -s -X POST "${ENDPOINT_URL}/" \
        -H "Content-Type: application/x-amz-json-1.0" \
        -H "X-Amz-Target: DynamoDB_20120810.ListTables" \
        -d '{}' 2>&1)

    if echo "${RESPONSE}" | grep -q "MissingAuthenticationToken\|TableNames"; then
        return 0
    fi
    return 1
}

if [ -z "${CI}" ] && [ -z "${GITHUB_ACTIONS}" ]; then
    COMPOSE_CMD=$(get_compose_cmd)

    if [ -z "${COMPOSE_CMD}" ]; then
        echo "❌ Neither 'docker compose' nor 'docker-compose' is available"
        exit 1
    fi

    CONTAINER_STATUS=$(docker ps -a --filter "name=dynamodb-local" --format "{{.Status}}" 2>/dev/null || echo "")

    if [ -z "${CONTAINER_STATUS}" ]; then
        echo "🚀 Starting DynamoDB container..."
        ${COMPOSE_CMD} up -d dynamodb-local
    elif echo "${CONTAINER_STATUS}" | grep -q "^Up"; then
        echo "✅ DynamoDB container is already running"
    else
        echo "🔄 Starting stopped DynamoDB container..."
        ${COMPOSE_CMD} up -d dynamodb-local
    fi

    echo "⏳ Waiting for container to be ready..."
    sleep 2
else
    echo "ℹ️  Running in CI environment, skipping container management (using service container)"
fi

echo "🔍 Checking DynamoDB connection at ${ENDPOINT_URL}..."

if ! command -v curl &> /dev/null; then
    echo "❌ curl is not installed. Please install curl first."
    exit 1
fi

for i in $(seq 1 ${MAX_ATTEMPTS}); do
    if check_dynamodb_ready; then
        echo "✅ DynamoDB is running and accessible at ${ENDPOINT_URL}"
        exit 0
    fi

    if [ ${i} -lt ${MAX_ATTEMPTS} ]; then
        echo "Attempt ${i}/${MAX_ATTEMPTS}: DynamoDB not ready yet, waiting..."
        sleep ${WAIT_SECONDS}
    fi
done

echo "❌ Cannot connect to DynamoDB at ${ENDPOINT_URL} after ${MAX_ATTEMPTS} attempts"
if [ -z "${CI}" ] && [ -z "${GITHUB_ACTIONS}" ]; then
    echo "💡 Make sure Docker is running and try again"
fi
exit 1
