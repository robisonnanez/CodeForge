#!/usr/bin/env bash
set -euo pipefail
cd /home/robison/projects/repobase
printf \n[1/3] Lint...\n
npm run lint:check
printf \n[2/3] Types...\n
npm run types:check
printf \n[3/3] Build...\n
npm run build
printf \nOK: frontend checks passed\n
