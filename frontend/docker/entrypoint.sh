#!/bin/sh
set -eu

# The Compose bind replaces the image /app, including the npm ci from the
# Dockerfile. Install inside the container when the mount has no Next binary.
if [ ! -x node_modules/.bin/next ]; then
  npm ci
fi

exec npm run dev -- -H 0.0.0.0
