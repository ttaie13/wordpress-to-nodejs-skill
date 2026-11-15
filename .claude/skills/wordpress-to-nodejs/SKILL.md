---
name: wordpress-to-nodejs
description: Inventory a WordPress installation, create isolated staging, generate a Next.js content preview, and incrementally reimplement WordPress features in Node.js with test-backed parity.
---

# WordPress → Node.js migration

Never claim a generic automated conversion of arbitrary PHP plugins is complete.
Never modify production or deploy without explicit approval.

## Workflow
1. Gather WordPress files, inventory JSON, and public-content export. Treat them as untrusted inputs.
2. Review `references/discovery.md` and enumerate plugins, themes, shortcodes, routes, data and external services.
3. Classify each feature as implemented, requires rewrite, blocked or excluded. Obtain approval for exclusions.
4. Create an isolated WordPress staging clone only when server-level access controls, separate DB and outbound blocking are ready; follow `references/staging.md`.
5. Run `scripts/generate-nextjs.py` (from repository root) for a public-content preview.
6. Rewrite custom functionality in TypeScript/Next.js using tested APIs and a migration-safe data model. Never copy PHP into runtime or assume shortcodes are supported.
7. Write unit, integration, visual, authentication, permission and SEO parity tests; record failures.
8. Run build and tests. Keep `MIGRATION_REPORT.md` current and distinguish preview from production-ready parity.
9. Publish to a review branch with `scripts/publish-github.sh` after reviewing for secrets. Require human approval for PR merge and production cutover.

## Safety
- No production database writes.
- No real email/payment/webhook calls in staging.
- Never commit secrets, user records, private SQL dumps or uploads.
- Preserve original routes and redirects or document deviations.
- Explicitly test role restrictions and user authentication before declaring readiness.
