#!/bin/bash
# Backup workspace files to GitHub

WORKSPACE_DIR="/root/.openclaw/workspace"
REPO_DIR="/root/.openclaw/workspace/cleatus"
GIT_TOKEN="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"
REPO_URL="https://squigglyweb:$GIT_TOKEN@github.com/squigglyweb/cleatus.git"

# Files to backup (exclude configs with secrets)
FILES_TO_BACKUP=(
    "Bryan_Strategy.md"
    "lead-capture/index.html"
    "lead-capture/divi-landing-page.html"
)

cd "$REPO_DIR" || exit 1

# Copy updated files from workspace to repo
cp "$WORKSPACE_DIR/Bryan_Strategy.md" . 2>/dev/null
cp "$WORKSPACE_DIR/lead-capture/index.html" . 2>/dev/null
cp "$WORKSPACE_DIR/lead-capture/divi-landing-page.html" . 2>/dev/null

# Add and commit
git add .
git commit -m "Backup $(date '+%Y-%m-%d %H:%M')" 2>/dev/null || exit 0

# Push
git push "$REPO_URL" main

echo "Backup complete: $(date)"