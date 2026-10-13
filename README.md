# KrishiDisha — কৃষিদিশা

A PHP/MySQL agricultural marketplace with farmer/dealer trading, cash collection, consultations, farm tours, food orders, and reviewed agricultural references.

## Local setup

Prerequisites: Docker Desktop, Python 3, and an authenticator app for administrators.

```sh
python3 bin/configure.py
docker compose up -d --build
docker compose run --rm --no-deps migrate php bin/create-admin.php your-email@example.com
```

Open http://localhost:8080/KrishiDisha/. Save the generated administrator password privately. Enroll the authenticator on first login, save the recovery codes, and change the password from Profile. No public default administrator or demo password is installed.

The local configuration uses development mode, localhost bindings, and a **new MySQL 8.4 volume**. Existing old MySQL volumes are preserved. The web app receives only its restricted database credentials, not the MySQL root password. Database administration is available only when explicitly enabled:

```sh
docker compose --profile tools up -d phpmyadmin
```

phpMyAdmin is bound to localhost:8081 and requires manual login. MySQL has no published host port.

## Email configuration

Set `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASSWORD`, `SMTP_ENCRYPTION=tls` (or `ssl`), and `MAIL_FROM` in the private `.env`, then recreate the web container. PHPMailer is pinned in `composer.lock`; certificate verification is retained. Production requires encrypted SMTP.

Registration stays closed until email is configured. Recovery and resend requests always return a generic response, and usable auth tokens are never displayed in a browser or stored as plaintext in the token table. Email delivery can fail; users can retry through the verification resend page. Provider accounts also need manual administrator approval, with application details and recorded identity/qualification review notes.

Do not commit `.env`, credentials, screenshots/traces containing account information, database exports, or uploaded media.

## Current workflows

- **Produce:** farmers submit listings with variety, grade, harvest date, pickup location and minimum order. An administrator publishes the listing. Buyers reserve stock and provide contact/fulfillment details. The farmer or dealer confirms and delivers the order.
- **Procurement:** dealer purchases atomically deduct farmer stock and create resale inventory, a procurement record, and a pending cash receivable.
- **Cash collection:** after delivery/service completion, the seller or administrator records the exact cash received and a receipt reference. Merely ordering or completing a service does not mark payment received. Procurement cash can be recorded once the procurement is created.
- **Commission/payouts:** 5% is rounded per payee allocation; seller net proceeds are tracked separately. When a seller collects cash, their own share is marked retained. Administrators/collectors record other seller transfers and the platform commission using receipt references. These are auditable confirmations of real-world transfers, not automated bank transfers.
- **Cancellation/refund:** pending buyers can cancel; providers can cancel eligible nonterminal transactions. Produce reservations are released once. Collected money enters an administrator refund review. Returned seller proceeds must be recorded before the buyer refund; commission is reversed with the refund. Stock from a fulfilled order is not automatically returned on a financial dispute.
- **Bookings:** tour capacity is checked on every booked date; guides cannot overlap tours and consultations. Consultations use Asia/Dhaka appointment times, positive 15-minute duration steps, and provider availability. Food orders respect cook approval and daily capacity.
- **Support:** authenticated participants can report transaction/quality problems; administrators can reply and resolve tickets. In-app notifications carry status updates. No SMS, push, or online payment provider is connected.
- **Account security:** current status/email verification/authentication version are checked on requests. Suspension and password changes revoke sessions. Login regenerates session IDs. Administrators require TOTP with single-use recovery codes. POST mutations require CSRF, and old mutation GET URLs return 405.
- **Images:** decoded JPG/PNG/WebP images are re-encoded, bounded by size/dimensions and daily quotas, and stored outside the public directory. Pending suggestion images require owner/admin access. Public media is served with an explicit image content type.

## Agricultural information

Disease pages are **reference catalogs**, not automated diagnoses. Crop suitability uses reviewed reference scores, not machine-learning predictions. Crop, disease, nutrient and suitability data stays unpublished until reviewed with a reference source. Seed values from the original demonstration are unreviewed; no claim is made that their pesticides, units, or suitability scores are correct.

Administrators can manage crops/diseases in Content and raw nutrient amounts, cooking retention, and regional references in Review References. Verify vitamin A units, edible/raw/cooked basis, preparation conditions, local crop varieties, planting windows, water/soil/salinity, weather risks and current treatment labels with qualified experts. Do not publish recommendations merely because a source URL was entered.

The profit estimator includes operational costs, saleable yield after losses, commission, decimal/acre/hectare conversion, low/base/high price scenarios, and break-even estimates. Platform asking-price averages cover recent active listings and show pickup area, date and sample count. They are not official transaction prices or guaranteed returns.

Useful external sources, **linked rather than automatically imported**:

- [BAMIS district weather advisories](https://www.bamis.gov.bd/en/bulletin/district/)
- [DAM market reports](https://market.dam.gov.bd/?L=E)
- [DAE pesticide information](https://prism.dae.gov.bd/pesticide-list)

Bangla navigation and bilingual core forms are available; English remains in some editorial/service screens. Real farmer field testing, professional agronomic review, verified credentials, and partner logistics require people and operational arrangements beyond these code changes.

## Tests

```sh
npm ci
npm test
docker compose run --rm --no-deps migrate php tests/run.php
```

The PHP suite creates a uniquely named temporary database and removes it afterwards. It checks stock, authorization, idempotency, cash, refunds, bookings, token replay, concurrent requests, migrations, MFA and escaping. It needs the migration service's database administrator credentials; the normal app has no DDL rights.

Browser checks use **another isolated database**, not your administrator database:

```sh
docker compose run --rm --no-deps -v "$PWD:/app" migrate php tests/browser-fixtures.php
BROWSER_DB="$(node -p "require('./.browser-fixtures.json').database")" docker compose --profile tests up -d browser
PLAYWRIGHT_SKIP_BROWSER_GC=1 npx playwright install chromium
npm run test:browser
docker compose stop browser
docker compose run --rm --no-deps -v "$PWD:/app" migrate php tests/cleanup-browser.php
```

Browser tests cover desktop/mobile, all eight roles, hidden repository files, CSRF, invalid role injection, recovery response, farmer fulfillment, cash collection and calculator rendering. Fixtures and trace/screenshot artifacts are private and ignored by Git. Restarting a test against reused fixtures may hit TOTP replay protection or rate limits; create fresh fixtures after cleanup.

Pinned frontend assets are served locally. After updating the packages, run `npm run assets` to regenerate assets and their SHA-256 manifest. Run Composer/npm audits and an image scan regularly. GitHub Actions runs syntax/integration checks and builds/scans the image; releases must still be tested with the actual deployment configuration.

## Production and existing databases

Read [deployment and upgrade instructions](docs/DEPLOYMENT.md) before changing an existing database image. Production requires HTTPS, a unique `APP_KEY`, SMTP delivery, a trusted reverse proxy configuration, private backups, and reviewed content. Do not point MySQL 8.4 at the only copy of an old database volume.

Versions are tracked in `SCHEMA_MIGRATION`; migrations do not mass-verify users. Unchanged public demo-password hashes are suspended on upgrade. Legacy payments are not treated as verified cash; their allocations and receipts need operator reconciliation before collection/reporting. Existing user email verification must be completed through the configured delivery workflow.

Updating this repository does not deploy a production service. Follow the deployment guide and complete the external setup and field review above before a public launch.
