# WP Node Migration & Staging

**Version 1.0 — migration toolkit, not a universal automatic PHP converter.**

## Components
- Installable WordPress admin plugin: inventory and public-content JSON export.
- WordPress staging clone script (WP-CLI, rsync, isolated empty MySQL DB).
- Next.js generator for published content with original URL paths.
- Claude Code skill for discovery, functional migration, verification and GitHub delivery.
- GitHub publication helper that pushes a review branch.

## Install
Upload `wp-node-migration-plugin.zip` in WordPress Plugins → Add New → Upload Plugin.
Open Tools → WP Node Migration. Export inventory and public content JSON.

## Generate Node.js staging preview
```bash
python3 scripts/generate-nextjs.py --content ./public-content.json --output ./node-preview
cd node-preview
npm install
npm run build
npm run dev
```
Only use exports from a trusted administrator. WordPress HTML can include scripts or embeds.

## Create isolated WordPress staging
Read `STAGING.md` first. Create a separate empty database and restricted hostname.
```bash
export STAGING_DB_PASSWORD='staging-only-password'
./scripts/create-wp-staging.sh /var/www/production /var/www/staging https://staging.example.com staging_db staging_user
```
**Do not expose the clone before enforcing authentication and outbound network restrictions.**
The clone contains production data and must be protected. No automatic anonymisation is provided.

## GitHub
Authenticate with `gh auth login`; run `./scripts/publish-github.sh ./node-preview owner/private-repo`.
The script pushes a branch. Open/review a PR separately. Never commit exported databases or credentials.

## Limitations
No generic tool can guarantee automatic parity for arbitrary WordPress plugins. Custom PHP,
shortcodes, membership, checkout, forms, users and third-party integrations require feature-specific
implementation and verification. The generator creates a public-content preview only.
No production deployment or traffic switching is performed.
