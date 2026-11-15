# WordPress to Node.js Skill - Tariq Al-Taie
$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot
if (-not (Test-Path .git)) { git init; if ($LASTEXITCODE -ne 0) { throw "git init failed" } }
git branch -M main
git config user.name "Tariq Al-Taie"
if (-not (git config user.email)) { Write-Host "Configure your email with: git config user.email YOUR_EMAIL"; exit 1 }
git add -A
git diff --cached --quiet
if ($LASTEXITCODE -ne 0) { git commit -m "Publish WordPress to Node.js Claude Skill and staging plugin"; if ($LASTEXITCODE -ne 0) { throw "commit failed" } }
$remote = git remote get-url origin 2>$null
if ($LASTEXITCODE -ne 0) { git remote add origin https://github.com/ttaie13/wordpress-to-nodejs-skill.git }
git fetch origin main
if ($LASTEXITCODE -eq 0) { git merge origin/main --allow-unrelated-histories --no-edit; if ($LASTEXITCODE -ne 0) { throw "Resolve merge conflicts before pushing. Run git status." } }
git push -u origin main
if ($LASTEXITCODE -ne 0) { throw "Push failed; inspect output above" }
