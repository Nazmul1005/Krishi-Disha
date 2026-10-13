# Deployment, upgrade and operations

## New deployment

1. Generate `.env` with `python3 bin/configure.py`. Keep it private and backed up separately; losing/changing `APP_KEY` makes encrypted MFA secrets unreadable.
2. Set `APP_ENV=production`, a canonical HTTPS `APP_URL`, strong unique database credentials, SMTP credentials, and a real `SUPPORT_CONTACT`.
3. Put the localhost web binding behind a trusted HTTPS reverse proxy. Configure client-IP forwarding only from known proxies; the application does not trust arbitrary forwarded headers. Rate limiting uses the server-supplied peer IP.
4. Build and start the services. The migration job receives administrator DB access; the web app gets SELECT/INSERT/UPDATE/DELETE only.
5. Create the first administrator via the CLI, enroll TOTP and preserve recovery codes privately. Verify SMTP delivery with a real account. Do not publish default/test accounts.
6. Review provider identities/qualifications, farm safety and cancellation arrangements, food preparation/allergen information and delivery logistics. Publish only reviewed reference data.
7. Verify backup restoration, security headers, external reachability and least-privileged DB access in the actual deployment. Enable monitoring/alerts for health failures, error logs, failed mail delivery, repeated authentication failures, and unresolved refunds/support tickets.

The public router exposes only application endpoints and local static assets. `.git`, configuration, SQL, Composer packages, CLI tools, test fixtures and private media are outside the public document root. Do not bind-mount an entire source checkout into a production document root.

## Upgrade an existing installation

Use a maintenance window and a staging copy first.

1. Back up the old MySQL database, uploaded media, and private configuration. Test the restore before proceeding. Preserve the old volume.
2. Restore a **copy** into a supported MySQL environment, following MySQL's supported upgrade path and checks. Never replace the database image against your only existing volume.
3. For schemas predating the former proposal/auth-token tables, run the revised `database/update_schema.sql` against the staging copy first. It no longer grants verification to all accounts. Then execute `php bin/migrate.php` with the migration credential.
4. New CHECK constraints can reject corrupted legacy quantities, suitability scores or retention values. The migration fails closed. Review and repair these records in staging with a documented business decision; do not silently delete transaction history. A failed migration can safely be rerun after repair; column/index existence is checked.
5. The migration adds missing dealer inventory foreign keys. Resolve existing orphan references before applying constraints.
6. Review legacy payments: old `completed` records have no trusted `verified_at` or allocation/receipt evidence. They are excluded from collected-money reports. Do not invent receipts. Reconcile payer, seller, business reference, allocations, refunds and prior commissions using real records before importing into the new cash workflow. Duplicates stay auditable; new workflows use a unique reference registry.
7. Public demonstration password hashes are suspended once. Create a new administrator or deliberately reset/re-enable a trusted administrator via secure console administration. Verify all genuine account emails; schema changes never grant verification.
8. Export legacy uploaded assets before deploying. New uploads live in private storage; old `assets/images/uploads` are excluded from images. Decode/re-encode and register existing images under MEDIA, updating only validated referenced paths during a controlled import. Do not expose a legacy uploads directory or `.git` as a shortcut.
9. Run the PHP and browser suites; check stock totals, cancellations, receipt reconciliation and appointments on staging. Only then switch the web deployment.

## Cash ledger rules

An order reserves stock. Delivery/service completion and cash collection are separate events. Only an authorized seller or administrator can confirm an exact receipt after fulfillment; procurement has its own receivable. A seller collecting cash retains their own net proceeds and still owes the platform commission and any other payee share.

The payment screen records actual transfers with receipt references. This does not initiate a bank/mobile-money transaction. Refund review blocks payouts and settlement. Refunds require seller proceeds to be returned first and reverse commission. A previously received commission may need to fund the refund: keep the original receipt and reversal in reconciliation records. A returned physical product requires a separate verified stock adjustment; do not restock financial disputes automatically.

Online payments require a provider integration with verified server callbacks, idempotency, signed/validated payloads and reconciliation. No provider credentials were supplied, so no gateway is pretended to be active.

## Backup and restore

`bin/backup.sh` writes the database and media archive into a private directory. Keep an independent, encrypted copy off the application host using your organization's chosen storage. Preserve `APP_KEY` separately with access controls. Backups contain personal and financial information.

```sh
sh bin/backup.sh storage/backups
```

For a restore drill, restore the SQL into a separate staging database and media into separate storage, then run migrations/tests and verify representative orders, payments, users and images. Do not restore directly over a live database without an approved maintenance plan. The Docker volumes intentionally persist when containers stop.

## Dependencies and monitoring

PHP 8.4 and MySQL 8.4 are used. The database image removes the unused MySQL Shell Python bundle and rebuilds gosu 1.19 using patched Go 1.27.2; the MySQL server/client are retained. Scan both application and database images. rebuild for patched images regularly. `composer.lock`, `package-lock.json` and the frontend asset manifest pin application dependencies. Review image scanning results and base-image support dates. Container tags are controlled release lines, not immutable patch identifiers; record deployed digests.

Application errors go to container logs; sensitive tokens/passwords are not intentionally logged. SMTP delivery errors should trigger an operational alert. In-app notifications are available; background SMS/push or offline/voice workflows need separate integrations and farmer testing.
