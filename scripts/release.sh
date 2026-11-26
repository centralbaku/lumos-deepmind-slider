#!/usr/bin/env bash
# Publish a new version:  scripts/release.sh 1.2.0 "What changed"
# Bumps the version, commits and pushes to main. The "Release on push" workflow then
# creates tag v<version>, builds the zip and publishes the release, and
# WordPress sites show "Update available" (every 6 hours, or Dashboard → Updates → Check again).
set -euo pipefail
cd "$(dirname "$0")/.."
F=lumos-deepmind-slider/lumos-deepmind-slider.php
V="${1:?usage: scripts/release.sh X.Y.Z \"notes\"}"; NOTES="${2:-Release $V}"
[[ "$V" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || { echo "Version must look like 1.2.0"; exit 1; }
git diff --quiet HEAD || { echo "Commit your changes first."; exit 1; }
find lumos-deepmind-slider -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
sed -i.bak -E "s/^( \* Version:[[:space:]]+).*/\1$V/" "$F"
sed -i.bak -E "s/define\( 'LUMOS_SLIDER_VERSION', '[^']+' \)/define( 'LUMOS_SLIDER_VERSION', '$V' )/" "$F"
rm -f "$F.bak"
git add "$F"
git commit -m "Release v$V: $NOTES"
git push origin main
echo "Pushed v$V. Build: https://github.com/centralbaku/lumos-deepmind-slider/actions"
