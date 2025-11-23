#!/bin/bash

set -e

DYNAMODB_HOST="${DYNAMODB_HOST:-localhost}"
DYNAMODB_PORT="${DYNAMODB_PORT:-8021}"
MAX_ATTEMPTS="${MAX_ATTEMPTS:-30}"
WAIT_SECONDS="${WAIT_SECONDS:-2}"
ENDPOINT_URL="http://${DYNAMODB_HOST}:${DYNAMODB_PORT}"

echo "🔍 Checking DynamoDB connection at ${ENDPOINT_URL}..."

if ! command -v curl &> /dev/null; then
    echo "❌ curl is not installed. Please install curl first."
    exit 1
fi

for i in $(seq 1 ${MAX_ATTEMPTS}); do
    RESPONSE=$(curl -s -X POST "${ENDPOINT_URL}/" \
        -H "Content-Type: application/x-amz-json-1.0" \
        -H "X-Amz-Target: DynamoDB_20120810.ListTables" \
        -d '{}' 2>&1)

    if echo "${RESPONSE}" | grep -q "MissingAuthenticationToken\|TableNames"; then
        echo "✅ DynamoDB is running and accessible at ${ENDPOINT_URL}"
        exit 0
    fi

    if [ ${i} -lt ${MAX_ATTEMPTS} ]; then
        echo "Attempt ${i}/${MAX_ATTEMPTS}: DynamoDB not ready yet, waiting..."
        sleep ${WAIT_SECONDS}
    fi
done

echo "❌ Cannot connect to DynamoDB at ${ENDPOINT_URL} after ${MAX_ATTEMPTS} attempts"
echo "Response: ${RESPONSE}"
echo "💡 Make sure DynamoDB container is running:"
echo "   docker-compose up -d dynamodb-local"
exit 1

