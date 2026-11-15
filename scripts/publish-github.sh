#!/usr/bin/env bash
set -euo pipefail
[[ $# -eq 2 ]] || { echo 'Usage: publish-github.sh DIRECTORY OWNER/REPO' >&2; exit 2; }
DIR="$1"; REPO="$2"
command -v gh >/dev/null; command -v git >/dev/null
cd "$DIR"
[[ -f .gitignore ]] || { echo 'Missing .gitignore; refusing to publish' >&2; exit 2; }
if grep -R -l -E '(BEGIN (RSA|OPENSSH) PRIVATE KEY|DB_PASSWORD[[:space:]]*=)' --exclude-dir=.git --exclude='publish-github.sh' . | head -1 | grep -q .; then echo 'Potential secrets found; aborting' >&2; exit 2; fi
[[ -d .git ]] || git init
git checkout -B migration/staging
 git add .
 git commit -m 'Prepare WordPress to Node.js staging migration' || true
if ! gh repo view "$REPO" >/dev/null 2>&1; then gh repo create "$REPO" --private; fi
git remote remove origin >/dev/null 2>&1 || true
git remote add origin "https://github.com/$REPO.git"
git push -u origin migration/staging
printf 'Branch pushed. Open a PR with: gh pr create --repo %s --base main --head migration/staging\n' "$REPO"
