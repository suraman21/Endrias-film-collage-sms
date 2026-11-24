<?php
/**
 * ============================================================
 * DEPLOYMENT SECRETS  —  env_endriasfc.php
 * Endrias Film College School Management System
 * ============================================================
 *
 * This is the deployment env file for:
 *   https://endriasfilmcollage.arkeonethiopia.com
 *
 * The loader in /config.php looks for this exact filename in,
 * in order:
 *   1. your account home folder (ONE LEVEL ABOVE public_html)
 *   2. two levels up (some hosting layouts)
 *   3. the site root (development fallback — this copy)
 *
 * DEPLOYMENT INSTRUCTIONS (do this once, on the server):
 *
 *   1. Copy this file to your account home folder:
 *          /home/YOUR_ACCOUNT/env_endriasfc.php
 *      (one level above public_html)
 *
 *   2. Fill in the real DB_USER / DB_PASS below (see cPanel
 *      → MySQL Databases).
 *
 *   3. Set its permission to 0600 (owner read/write only):
 *          chmod 600 /home/YOUR_ACCOUNT/env_endriasfc.php
 *
 *   4. Reload the website. The "Setup required" message disappears.
 *
 * NOTE: This committed copy ships with the security keys already
 * generated (development deployment) so the site starts working
 * immediately. Before any production go-live, regenerate all five
 * keys below (php -r "echo bin2hex(random_bytes(32)), PHP_EOL;")
 * and re-import any data that depends on them. NEVER commit real
 * DB credentials to git — fill those in on the server only.
 * ============================================================
 */

// ---- Database (get these from cPanel → MySQL Databases) ----
define('DB_HOST', 'localhost');
// Production dump name (arkeonet_felegekidusan). Change only if you
// create a separate database for this deployment.
define('DB_NAME', 'arkeonet_felegekidusan');
define('DB_USER', 'REPLACE_WITH_YOUR_DB_USER');
define('DB_PASS', 'REPLACE_WITH_YOUR_DB_PASSWORD');

// ---- Site identity ----
// The deployment's public domain. SITE_URL, ADMIN_URL, the API's CORS
// origins, QR-card payloads and the uptime check all derive from this
// single value. No scheme, no www, no trailing slash:
define('SITE_DOMAIN', 'endriasfilmcollage.arkeonethiopia.com');

// ---- Security keys ----
// Generated for this development deployment (2026-11-24). Rotate all
// five before production go-live; keep them stable afterwards or
// existing logins/tokens/backups break.
define('JWT_SECRET',         '6a32931de042d00ee5305e8cdbb28d4e8dd65ce2391688745b461a8e1411895b'); // mobile app login tokens
define('BACKUP_KEY',         'b25f6edfb8e1f6e753140d9748c9c0c1336b3a4dbcd9e62b9b73ee3c86925f40'); // encrypts backups; required for restore
define('HEALTH_KEY',         'c5496ae8a21e6d1cbcba1654dce122fda2c84d3c3993fb028c3b731ede9ccc40'); // HTTP Basic/X-Health-Key health credential
define('MONITOR_SECRET_KEY', 'b4512036b83d8184bf1496e923393b8ecb24207e2747fd085a45026e4737dd17'); // password for the error-monitor dashboard (/monitor/)
define('TELEMETRY_HASH_SECRET', 'f46fd733732b82f33b5dee668ed22418ccff91c7f78031d19147ad06e0e2e3fc'); // HMAC salt for rotating telemetry IP hashes

// ---- Mobile authorization-scope rollout (TEMPORARY / OPTIONAL) ----
// Build 24+ always receives live role/status/assignment revalidation.
// Leave omitted until build 24 is the enforced minimum, then set this
// to a past timestamp (for example 1).
// define('API_AUTHZ_LEGACY_COMPAT_UNTIL', 1);

// ---- Encrypted database backup storage (OPTIONAL) ----
// Defaults to `ssms_secure_backups` beside (not inside) public_html.
// define('BACKUP_STORAGE_PATH', '/home/YOUR_ACCOUNT/ssms_secure_backups');
// define('BACKUP_ENCRYPTION_KEY', 'REPLACE_WITH_A_LONG_RANDOM_STRING');

// ---- Private member document storage (OPTIONAL) ----
// Defaults to a `ssms_private` directory beside (not inside) public_html.
// define('MEMBER_PRIVATE_STORAGE_PATH', '/home/YOUR_ACCOUNT/ssms_private');

// ---- AI provider key encryption (OPTIONAL but recommended) ----
// If set, used to encrypt stored AI API keys; otherwise derived from
// DB_PASS + JWT_SECRET. Set a stable value before saving AI keys.
// define('AI_ENC_KEY', 'REPLACE_WITH_A_LONG_RANDOM_STRING');

// ---- Deployment feature capabilities (OPTIONAL) ----
// Omit these to keep every current module enabled. Use real booleans only.
// define('FEATURE_AI_CHATBOT', false);
// define('FEATURE_GROUPS', false);
// define('FEATURE_FINANCE', false);
// define('FEATURE_MATERIAL', false);
// define('FEATURE_ID_CARDS', false);
// define('FEATURE_ATTENDANCE', false);
// define('FEATURE_GRADES', false);
// define('FEATURE_REPORTS', false);
// define('FEATURE_EXPORT_PDF', false);
// define('FEATURE_MONITOR', false);
// Attendance alerting v2 is OFF by default; enable only after sql/071+ are applied.
// define('FEATURE_ATTENDANCE_V2', true);

// Review-only Telegram registration (OFF unless explicitly enabled).
// define('FEATURE_TELEGRAM_REGISTRATION', false);
// define('TELEGRAM_BOT_TOKEN', '123456789:replace-with-deployment-local-token');
// define('TELEGRAM_WEBHOOK_SECRET', 'replace-with-32-to-256-url-safe-characters');
// define('TELEGRAM_WEBHOOK_MAX_BYTES', 262144);
// define('TELEGRAM_PROCESSED_UPDATE_RETENTION_DAYS', 30);
// define('TELEGRAM_CANCELLED_SESSION_RETENTION_DAYS', 90);
// define('TELEGRAM_AUDIT_RETENTION_DAYS', 730);

// Test-data loading/deletion is disabled by default.
// define('ENABLE_TEST_DATA_TOOLS', true);
