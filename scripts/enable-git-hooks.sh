#!/bin/sh
set -eu

root=$(git rev-parse --show-toplevel)
git -C "$root" config core.hooksPath .githooks
chmod 0755 "$root/.githooks/pre-commit"
echo "CodeForge security hooks enabled."
