#!/bin/bash
# Builds rooted-cpanel-update.zip for in-browser upgrade.
# Run from the project root: bash build-update-zip.sh

set -e

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
OUTPUT="$PROJECT_DIR/rooted-cpanel-update.zip"

echo "Building update ZIP..."

# Remove existing
rm -f "$OUTPUT"

# Temp staging directory
TMP=$(mktemp -d)
cleanup() { rm -rf "$TMP"; }
trap cleanup EXIT

mkdir -p "$TMP/rooted"
mkdir -p "$TMP/rooted-files"

# public/ → rooted/  (the web root)
rsync -a --exclude='.DS_Store' --exclude='*.zip' "$PROJECT_DIR/public/" "$TMP/rooted/"

# App code → rooted-files/  (protected root)
for dir in app bootstrap config docs resources; do
    if [ -d "$PROJECT_DIR/$dir" ]; then
        rsync -a --exclude='.DS_Store' "$PROJECT_DIR/$dir/" "$TMP/rooted-files/$dir/"
    fi
done

# Build ZIP preserving structure
cd "$TMP"
zip -r "$OUTPUT" rooted rooted-files -x "*.DS_Store"

echo ""
echo "✅ Done: $OUTPUT"
ls -lh "$OUTPUT"
