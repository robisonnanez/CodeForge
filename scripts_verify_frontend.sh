#!/usr/bin/env bash
set -euo pipefail
cd /home/robison/projects/repobase
echo "[1/3] npm run lint:check"
npm run lint:check
echo "[2/3] npm run types:check"
npm run types:check
echo "[3/3] npm run build"
npm run build
echo "OK: all frontend checks passed"
