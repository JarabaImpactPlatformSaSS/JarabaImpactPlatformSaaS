#!/bin/bash
# Fix git object permissions on production server.
# Run when deploy fails with "insufficient permission for adding an object"
# Usage: ssh -p 2222 jaraba@82.223.204.169 "bash /var/www/jaraba/scripts/maintenance/fix-deploy-git-permissions.sh"

set -euo pipefail

REPO_DIR="/var/www/jaraba"
GIT_DIR="$REPO_DIR/.git"

echo "=== Fixing git object permissions ==="

# Fix pack files (most common issue)
find "$GIT_DIR/objects/pack" -type f ! -writable -exec chmod u+w {} \; 2>/dev/null || true

# Fix loose objects
find "$GIT_DIR/objects" -type d ! -writable -exec chmod u+wx {} \; 2>/dev/null || true
find "$GIT_DIR/objects" -type f ! -writable -exec chmod u+w {} \; 2>/dev/null || true

# Fix refs
find "$GIT_DIR/refs" -type f ! -writable -exec chmod u+w {} \; 2>/dev/null || true

# Verify
echo "Testing git fetch..."
cd "$REPO_DIR"
git fetch origin main 2>&1 | tail -3

echo "=== Done ==="
