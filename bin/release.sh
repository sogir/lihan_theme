#!/usr/bin/env bash
# Release a new theme version to every site running it.
#
#   bin/release.sh patch            # 1.0.0 -> 1.0.1  (also: minor, major)
#   bin/release.sh 1.2.0            # exact version
#   bin/release.sh --current        # (re)publish the version already in style.css
#
# Bumps "Version:" in style.css (and "Stable tag:" in readme.txt), commits
# everything, tags v<version>, pushes, builds lihan.zip and publishes it as a
# GitHub Release (needs the GitHub CLI, logged in with `gh auth login`).
# Sites running the theme then auto-update within ~12 hours, or right away
# via Dashboard → Updates → Check again.
set -euo pipefail
cd "$(dirname "$0")/.."

SLUG=lihan
command -v gh >/dev/null || { echo "GitHub CLI missing: install gh, then run gh auth login"; exit 1; }
gh auth status >/dev/null 2>&1 || { echo "Not logged in to GitHub: gh auth login"; exit 1; }
git remote get-url origin >/dev/null 2>&1 || { echo "No 'origin' remote."; exit 1; }

current=$(grep -m1 -E '^[[:space:]]*Version:' style.css | sed -E 's/.*Version:[[:space:]]*//; s/[[:space:]]*$//')
arg="${1:-patch}"
IFS=. read -r MA MI PA <<<"$current"
case "$arg" in
  --current) new="$current" ;;
  major) new="$((MA+1)).0.0" ;;
  minor) new="$MA.$((MI+1)).0" ;;
  patch) new="$MA.$MI.$((PA+1))" ;;
  *)     new="$arg" ;;
esac
[[ "$new" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || { echo "Bad version: $new"; exit 1; }

if [[ "$new" != "$current" ]]; then
  echo "Releasing $current -> $new"
  sed -i.bak -E "s/^([[:space:]]*Version:[[:space:]]*).*/\1$new/" style.css && rm -f style.css.bak
  sed -i.bak -E "s/^(Stable tag:[[:space:]]*).*/\1$new/" readme.txt && rm -f readme.txt.bak
fi

# Commit anything pending (including the version bump).
if [[ -n "$(git status --porcelain)" ]]; then
  git add -A
  git commit -m "Release v$new"
fi

git rev-parse -q --verify "refs/tags/v$new" >/dev/null || git tag -a "v$new" -m "v$new"
git push origin HEAD
git push origin "v$new"

# Build the update zip from the tagged commit (only tracked files; bin/,
# .github/ and .gitignore are left out).
build=$(mktemp -d)
trap 'rm -rf "$build"' EXIT
git archive --format=tar --prefix="$SLUG/" "v$new" | tar -x -C "$build"
rm -rf "$build/$SLUG/bin" "$build/$SLUG/.github" "$build/$SLUG/.gitignore"
(cd "$build" && zip -qr "$SLUG.zip" "$SLUG")

if gh release view "v$new" >/dev/null 2>&1; then
  gh release upload "v$new" "$build/$SLUG.zip" --clobber
else
  gh release create "v$new" "$build/$SLUG.zip" --title "v$new" --generate-notes
fi
echo "Published v$new. Sites will update automatically."
