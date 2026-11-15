# Staging safety requirements

1. Create a dedicated HTTPS hostname, isolated filesystem and EMPTY database with separate credentials.
2. Restrict access with server-level authentication or private networking before cloning.
3. Block outgoing SMTP, payments, webhooks and scheduled jobs at network/hosting level.
4. Run the staging script only with a verified backup and a maintenance plan for large datasets.
5. Audit the cloned `wp-config.php` for custom secrets, hardcoded production paths and integration keys.
6. Disable indexing and confirm search engines cannot access staging.
7. Confirm staging cannot access or write to production services or databases.
8. Use a sanitised content export for external AI tooling; do not send private customer data.
9. Review Node.js output against original UI, accessibility, SEO, permissions and plugin features.
10. Production cutover and rollback must be separately planned and explicitly approved.

**Important:** The clone script is an operator-assisted utility, not a one-click safe hosting solution. Its database export may be large, and staging credentials and outbound restrictions must be configured before use.
