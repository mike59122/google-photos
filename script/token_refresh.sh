#!/bin/bash
CONFIG_FILE="$(dirname "$0")/../config.json"
CLIENT_ID=$(jq -r '.client_id' "$CONFIG_FILE")
CLIENT_SECRET=$(jq -r '.client_secret' "$CONFIG_FILE")
REFRESH_TOKEN=$(jq -r '.refresh_token' "$CONFIG_FILE")
TOKEN_FILE=$(jq -r '.token_file' "$CONFIG_FILE")

RESPONSE=$(curl -s \
  -d client_id="$CLIENT_ID" \
  -d client_secret="$CLIENT_SECRET" \
  -d refresh_token="$REFRESH_TOKEN" \
  -d grant_type=refresh_token \
  https://oauth2.googleapis.com/token)

echo "$RESPONSE" > "$TOKEN_FILE"
