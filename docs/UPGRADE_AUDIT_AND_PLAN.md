# Marketplace upgrade audit and proposed plan

Audit date: 14 October 2026 (Asia/Dhaka). Source revision: `aa27a5b`.

The requested upgrade is **partially implemented**. The repository already has a substantial security and transaction foundation, but none of the six requested phases is complete. This document records Step 0 and proposes Phase 1 for approval; it does not mark planned features as delivered.

## Repository and architecture

The application is in `Krishi-Disha/`, inside the workspace. The workspace-level `PROJECT_AUDIT.md` describes an older revision; many of its security and transaction findings have since been addressed. The workspace patch contains earlier hardening work already represented in the application's Git history, so it should not be reapplied.

- `public/index.php` is a small allowlist router for `/KrishiDisha/` PHP endpoints. Apache exposes copied local assets from `public/assets/`; application source, configuration, tests and private storage are outside the document root. URLs are still predominantly PHP filenames.
- Role directories (`admin`, `farmer`, `dealer`, `tourist`, `cook`, `expert`, `guide`, `user`) contain server-rendered pages combining PDO queries, request handlers and HTML. The database role `general` maps to the `user/` directory.
- `includes/auth_check.php` and `helpers.php` initialize configuration/session handling, enforce CSRF for POST requests, recheck account status/role/verification/authentication version, and expose escaping, flash messages and token helpers.
- `includes/transactions.php` holds shared procedural services for produce ordering, procurement, booking, fulfillment, cash collection, payouts and refunds. Row locks, transactions, request keys and a unique payment-reference registry protect stock and accounting workflows. `notify()` is a shared database notification helper.
- `includes/content.php`, administrator review pages and review metadata gate agricultural reference publication. `uploads.php` decodes and re-encodes validated images into private storage; `media.php` checks publication/ownership before serving them.
- `includes/header.php`, `sidebar.php` and `footer.php` are shared fragments. Page headers and layout markup are repeated in templates; the shared footer currently contains scripts and closing HTML only.
- `assets/css/style.css` supplies initial tokens/components. Vanilla JavaScript handles interactions and the calculator. Bootstrap and Font Awesome are installed locally. Inter/Nunito are named in CSS but their UI font files are not provided; no local Bangla text font exists.
- `database/schema.sql` is the initial schema. Five numbered PHP migrations add security, moderation, accounting, media, indexes and reference vocabulary. The migration CLI records versions and takes a database lock. Fresh installations need both the schema and migrations.
- Docker builds PHP 8.4/Apache and MySQL 8.4 services. Migration credentials and runtime credentials are separated. PHPMailer handles configured SMTP delivery; SMS and online payment providers are absent.
- Tests include calculator checks, disposable-database service/concurrency checks, desktop/mobile Playwright journeys, deployment and restore checks. CI builds containers and runs security/dependency checks. Documentation covers deployment, previous implementation status and credited photography; an MIT license exists.

## Requirement coverage

“Present” below means found in source. Runtime behavior was not independently verified unless listed under this audit's verification results.

### Phase 1: design, landing page and mobile interface

| Requested item | Status | Evidence and remaining work |
| --- | --- | --- |
| One complete design system | Partial | `assets/css/style.css` has colors, radii, shadows, buttons, inputs, cards, badges, tables and alerts. Missing spacing tokens, local UI/Bangla fonts and reusable skeletons, toasts, breadcrumbs, pagination, steppers and accessible modal/tab behavior. `includes/header.php` changes the primary color for each role rather than keeping one green brand. Contrast needs measurement. |
| Local icons and optimized real images | Partial | Local Font Awesome and 34 credited JPG/PNG reference photos exist. Emoji remain in dashboards, listing placeholders and empty states. WebP derivatives and consistent intrinsic image dimensions are missing. |
| Complete marketplace landing page | Partial | `index.php` has a hero, real database counts, a latest asking price, module links and photos. Missing search, actionable role entry cards, featured produce/tour queries, how-it-works, a dedicated trust section, FAQ and complete informational/legal footer links. |
| One shared layout and mobile navigation | Partial | Shared fragments and a mobile sidebar exist; public nav/topbars repeat across templates. No bottom navigation or card conversion for tables; 44px targets are not consistently enforced. |
| Substantive dashboards | Partial | All eight dashboards have real metrics/recent records; several have quick actions and pending counts. Missing consistent attention queues, unread message tracking, cash action summaries and trends. Guide dashboard lacks an earnings summary. Lists are not consistently filtered/paginated. |
| Form and interaction polish | Partial | Flash alerts, basic required-field border coloring and native `confirm()` dialogs exist. Missing actionable empty states across screens, field-specific accessible feedback, durable error messages, skeleton components and toast/confirmation components. |

### Phase 2: connected journeys

| Requested item | Status | Evidence and remaining work |
| --- | --- | --- |
| Crop-specific action bar | Missing | Crop detail is an overlay in `modules/encyclopedia.php`; no crop-prefilled profit, sellers, nutrition and consultation action bar. |
| Disease-to-expert and affected-crop links | Partial | Reviewed sources/dates and affected crop names are displayed. The expert instruction is plain text; affected crops are badges rather than navigation links. |
| Calculator-to-marketplace handoff | Partial | Recent active asking-price averages include sample count, area and listing date; a button fills price. No crop context, automatic selected-crop price suggestion or prefilled produce listing handoff. |
| Guided tour bundle | Partial | Tours can include a guide at booking, and food/consultations exist separately. No guided flow carries booking context into food or expert scheduling. |
| Unified notifications | Partial | `NOTIFICATION`, `notify()` and a page exist. No header bell/dropdown/timeline; approval, proposal, chat and support reply handlers do not notify through this helper. |
| Global grouped search | Missing | Separate module `LIKE` searches exist. No grouped search service, filters/keyboard support or FULLTEXT indexes. |
| Logical neighbor links | Partial | Shared navigation and selected back links exist; breadcrumbs and contextual related links are inconsistent. |

### Phase 3: Bangladesh-specific features

| Requested item | Status | Evidence and remaining work |
| --- | --- | --- |
| Complete Bangla/English interface | Partial | Session-based language toggle, a small `t()` map and bilingual controls exist. Missing `lang/en.php`, `lang/bn.php`, stored account preference and full template translation. `taka()` formats English digits; calculator formatting always uses Bangla digits. |
| Phone/SMS authentication | Missing | Registration collects a phone number but authentication requires email. Registration is closed without SMTP. No SMS interface, adapter, feature flag or OTP workflow. |
| Local units | Partial | Calculator supports decimal, acre and hectare. Listings/orders store kg and farmer profiles acres. Missing shared land/mass conversions, maund/ton and explicit local bigha conventions. |
| Division/district/upazila selector | Missing | Locations are free text; no verified, attributed location hierarchy. |
| Online payment gateway | Missing | Receipt-backed cash, commission allocations and manual refunds exist. No provider interface, sandbox initiation, verified callback/IPN, reconciliation or gateway flags. |
| Official weather/market adapters | Missing | BAMIS/DAM links are provided. No import adapter, cache or official access arrangement; no live integration should be claimed. |
| PWA | Missing | No manifest, install icons, service worker, offline fallback or install prompt. |

### Phase 4: trust and marketplace quality

| Requested item | Status | Evidence and remaining work |
| --- | --- | --- |
| Transaction-backed reviews | Missing | No ratings/review tables, eligibility checks, moderation/reporting or average ratings. |
| Verified public provider profiles | Partial | Provider applications record administrator approval notes/reviewer/date. Private editable profiles and public expert cards exist. No public multi-role profiles or evidence-based verified badges. |
| Disputes and refund timeline | Partial | Private support requests and manual refund/recovery operations exist. No dedicated dispute state machine, seller response, evidence upload or dispute timeline. |
| Order status timeline | Partial | Pending/confirmed/delivered/cancelled states and cash timestamps exist. No per-transition timestamp storage or complete order timeline. |
| Pickup/delivery instructions | Partial | Produce has pickup location; orders have fulfillment method, address and contact phone. No map/contact links or consistent instruction presentation. |
| Advanced filters and price history | Partial | Marketplace has crop/category search. Missing hierarchical location, grade/price/quantity/harvest/rating filters, sorting and recorded price history. Current listings alone cannot reconstruct past prices. |
| Seller analytics | Partial | Basic stock, order and collected proceeds metrics exist. No views/conversion instrumentation or earnings trends. |

### Phase 5: technical polish

| Requested item | Status | Evidence and remaining work |
| --- | --- | --- |
| SEO | Partial | Page titles and one shared description exist; public references are reachable without login. Missing clean detail URLs, unique descriptions, canonical/OG/structured metadata, sitemap and robots configuration. |
| Performance | Partial | Local minified vendor assets, some indexes, bounded dashboards/chat and order/payment pagination exist. Many lists are unbounded; payment cards have an N+1 query. No custom asset versioning/minification, reference caching, WebP pipeline or explicit compression configuration. |
| Reliability | Partial | Private backup script, restore guidance/check and DB health endpoint exist. Backup scheduling, structured JSON application logs, optional monitoring hook and designed 403/404/500 pages are missing. |
| JSON API | Missing | No versioned authenticated/rate-limited API route or API documentation. |
| Accessibility | Partial | Focus styles, some labels, skip links and CSS reduced-motion rules exist. Labels, semantic landmarks, modal focus/escape behavior and accessible feedback are incomplete. No axe/Lighthouse audit in the test suite. |
| Administrator tools | Partial | User/provider review, content/proposal moderation and cash reconciliation exist. No audit-log viewer or CSV exports. |
| Legal pages | Missing | No Terms, Privacy, Refund or Cookie pages or owner-review status. |

### Phase 6: README and documentation

| Requested item | Status | Evidence and remaining work |
| --- | --- | --- |
| Professional implementation-accurate README | Partial | Description, workflows, architecture diagrams, setup, testing, attribution, responsible-use statement and license exist. Missing generated screenshots, complete feature-flag table and roadmap. Emoji/badge density and several UI/documentation claims need revision. |

## Concrete problems found

1. **General-user dashboard link is wrong.** `index.php:31` builds `/{currentRole()}/dashboard.php`, producing `/general/dashboard.php`; the supported route is `/user/dashboard.php` as already mapped in `redirectToDashboard()`.
2. **Public discovery includes providers who cannot transact.** `modules/tourism.php` does not filter tour hosts or available guides by approved account status. `tourist/tours.php` similarly lists active tours without checking the host status. Recipe discovery in `modules/nutrition.php` and `tourist/food_orders.php` also lacks the approval filter. Transaction services do recheck status, so this is a discovery/workflow inconsistency rather than evidence that booking authorization is bypassed.
3. **Some database strings remain unescaped.** Category option values/text in `modules/encyclopedia.php` and `user/marketplace.php` interpolate `$cat` directly. `modules/encyclopedia.php` outputs a history excerpt after `strip_tags()` without the final `e()` helper. Controlled vocabulary validation reduces exposure for new submissions, but legacy/imported data still needs safe output handling.
4. **Keyboard/assistive UI is inconsistent.** Many form labels have no associated input ID, icon-only actions lack names, and crop/proposal overlays lack a shared modal focus lifecycle. The home skip link has no matching `main-content` target.
5. **Language control can conflict with the navbar.** The shared language form is inserted before a separately fixed navbar that starts at viewport top. Layout integration and mobile keyboard testing are needed.
6. **The named text fonts are unavailable locally.** CSS references Inter and Nunito, but shipped WOFF2 files are icon fonts only. Bengali rendering currently depends on device fonts.
7. **Contrast and tap sizes need correction.** Primary/danger/gold gradients use white button text with light endpoints; small inline button padding appears throughout the templates. No measured AA result or enforced 44px baseline exists.
8. **No-JavaScript and reduced-motion behavior is incomplete.** `.fade-up` starts invisible until JavaScript adds a class; counts initially render zero before animation. CSS disables transitions but JavaScript still animates counters/retention bars. Public information should remain readable without those effects.
9. **Notifications do not cover all events.** Chat insertion, support resolution, user approval and content approval do not call `notify()`. No per-user consultation read position exists, so an unread-message count cannot yet be truthfully displayed.
10. **Lists are often unbounded or merely truncated.** Marketplace, crops, diseases, inventory, sales, recipes, sessions and administrator lists commonly fetch all rows. A `LIMIT 100` on notifications/support/bookings hides older records without navigation. Orders/payments always show a Next link, even when there is no following page.
11. **Payment rendering creates N+1 queries.** `modules/payments.php` fetches `PAYMENT_SHARE` inside the payment-card loop. Shares can be batch-loaded for the authorized page of payments.
12. **Some claims exceed their evidence.** `modules/consultation.php:32` calls experts “certified”; account approval does not universally establish certification. The admin “System Online” badge is unconditional. Branding also uses unsupported “premier/most comprehensive” claims.
13. **Historical analytics are not recorded.** Current stock/price/status fields cannot support an accurate historical price chart, view conversion or full status timeline without new event/history storage. Backfilled guesses must not be presented as recorded events.
14. **Administrator page handlers remain coupled to presentation.** Review/moderation behavior is spread across templates, and some related writes are not wrapped together. Further feature work should introduce focused shared services rather than duplicate logic.
15. **Legacy upgrade comments are stale.** `database/update_schema.sql` still references the old fresh-install SQL and public upload directories. Preserve old migrations; clarify the supported path in documentation instead of editing historical migration behavior.

## Verification performed during this audit

| Check | Result |
| --- | --- |
| Initial Git status | Clean at `aa27a5b` before this document was added. |
| `npm test` | Passed calculator scenarios, conversions, losses, commissions and invalid input checks. |
| `node --check` for `assets/js/main.js`, `assets/js/calculator.js`, `bin/assets.js` | Passed. |
| `sh -n bin/backup.sh` | Passed. |
| Reference catalog files/checksums | All 34 photographs exist and match their SHA-256 records. |
| Local vendor manifest checksums | All nine entries match their SHA-256 records. |
| PHP runtime availability | No host PHP executable found. |
| Docker availability | CLI installed; daemon socket absent. `docker compose ps` cannot connect. |
| PHP/service/database/browser/restore checks | Not rerun: they need the Docker services. No fresh pass is claimed. |

The earlier `docs/IMPLEMENTATION_STATUS.md` reports previous successful integration/browser/security checks. Those are historical results, not substitutes for fresh regression checks before delivering changes.

## Proposed Phase 1 implementation

Keep the existing PHP/PDO architecture, transactional rules, deployment isolation and local Bootstrap/Font Awesome compatibility. Build the UI incrementally. No new framework or fake marketplace seed data is needed.

### Feature 1: shared visual foundation

Change `assets/css/style.css`, `assets/js/main.js`, `includes/helpers.php`, `includes/header.php`, `includes/footer.php`, `auth/login.php`; create `includes/ui.php`, `assets/fonts/` with licensed local UI/Bengali font assets and attribution, and `docs/DESIGN_SYSTEM.md`.

Introduce one deep green brand, one accent, semantic colors, spacing/type tokens and consistent components. Preserve existing class compatibility during template updates. Add accessible confirmation/modal/tab behavior, breadcrumb/pagination helpers, skeleton and empty-state components, field errors and toast notifications. Keep important error content readable until dismissed. Measure text contrast and enforce 44px interactive targets. Remove inline hover handlers blocked by the current CSP. Render essential information without relying on animation or JavaScript.

### Feature 2: database-backed public landing page

Change `index.php`, `includes/reference_photos.php`, `bin/assets.js`, `assets/images/reference/catalog.json`; create `includes/landing.php`, `includes/public_nav.php`, `includes/site_footer.php`, `modules/about.php`, `modules/contact.php`, `modules/terms.php`, `modules/privacy.php`, and optimized image derivatives alongside the retained credited originals.

Add header crop search, language control, five actionable role entry cards, approved in-stock produce/active tours, practical how-it-works/trust/FAQ sections and real database counts. Keep registration availability honest when SMTP is absent. Header search uses the existing crop search for Phase 1; grouped global search is Phase 2. Product/tour cards show actual uploads or a clearly labeled missing-photo state; reference photos must not imply they depict a seller's produce/farm. Terms and Privacy are visibly marked as owner-review drafts; full policies follow in Phase 5. Contact uses configured support information. Fix dashboard role mapping and unsupported claims.

### Feature 3: shared role layout and mobile navigation

Change `includes/header.php`, `includes/sidebar.php`, `includes/footer.php`, all eight `*/dashboard.php` files and the role/shared templates listed under Feature 5; create `includes/topbar.php`, `includes/bottom_nav.php`, `includes/navigation.php`.

Use shared navigation definitions, exact active-route matching, one responsive topbar and footer, mobile bottom navigation and an accessible menu. Keep profile/payment/support links reachable. Convert table rows into readable labeled cards on narrow screens while retaining desktop tables. Integrate the language control into the layout rather than overlaying navigation.

### Feature 4: attention queues and truthful dashboard trends

Change `admin/dashboard.php`, `farmer/dashboard.php`, `dealer/dashboard.php`, `tourist/dashboard.php`, `cook/dashboard.php`, `expert/dashboard.php`, `guide/dashboard.php`, `user/dashboard.php`, `modules/consultation_chat.php`; create `includes/dashboard.php`, `assets/js/dashboard.js`, `tests/dashboard.php` and `database/migrations/006_dashboard_queries_and_message_reads.php` with companion rollback notes in `docs/MIGRATIONS.md`.

Add role-scoped pending work, approval status, unread notifications/messages and collectible cash summaries with relevant next actions. Record per-participant consultation read positions to support actual unread-message counts. Add lightweight accessible order/activity and verified cash-proceeds charts, with tabular data available to assistive technology. Buyers see purchases/spending rather than invented earnings; administrator commission metrics retain ledger eligibility rules. Test ownership, suspended providers, unverified/refund exclusions and empty datasets. Only add query indexes justified by the new queries; do not modify migrations 001–005 or invent historical events.

### Feature 5: lists, forms and module polish

Change `farmer/produce.php`, `farmer/farmland.php`, `farmer/bookings.php`, `dealer/inventory.php`, `dealer/sales.php`, `tourist/tours.php`, `tourist/food_orders.php`, `cook/recipes.php`, `cook/orders.php`, `expert/sessions.php`, `guide/bookings.php`, `guide/sessions.php`, `user/marketplace.php`, `user/nutrition.php`, all `admin/*.php` list/form screens, `modules/encyclopedia.php`, `modules/disease.php`, `modules/nutrition.php`, `modules/recommend.php`, `modules/tourism.php`, `modules/book_consultation.php`, `modules/consultation.php`, `modules/consultation_chat.php`, `modules/orders.php`, `modules/payments.php`, `modules/notifications.php`, `modules/support.php`, `modules/profile.php`, `modules/suggest.php`, `modules/photo_library.php`, `modules/calculator.php`, and the `auth/*.php` form templates. Create `includes/pagination.php`.

Apply consistent responsive components, associated input labels, meaningful empty states/next actions, destructive-action confirmation and field feedback. Add bounded SQL pagination and status/search filters to operational lists; preserve filters in page links and hide unavailable navigation. Batch-load payment shares. Filter public discovery to approved providers and escape database output. Keep the existing reviewed-reference gate and source/date display; use reference wording instead of “Solution” or blanket certification claims. Full crop context handoffs, notification event coverage, translation files and advanced marketplace filtering remain in their requested later phases.

### Feature 6: regression checks and delivery evidence

Change `tests/run.php`, `tests/browser-fixtures.php`, `tests/browser/platform.spec.js`, `package.json`, `package-lock.json` if an accessibility test dependency is needed, and `docs/IMPLEMENTATION_STATUS.md`; create `tests/browser/design.spec.js`, `tests/landing.php`, `tests/pagination.php` and `docs/screenshots/`.

Test new services and query boundaries in the existing disposable-database approach. Extend browser checks for empty and populated landing pages, the general-user dashboard link, all eight roles, mobile navigation, filtered pagination, keyboard interaction, inline errors and confirmation flows. Include automated accessibility checks for the shared shell and representative screens. Preserve the CSRF/authorization/concurrency/cash/refund regression coverage. Capture desktop/mobile screenshots only from the isolated test environment and identify test content as fixtures in documentation.

Use one small commit per feature (split a broad template rollout into smaller role/module commits as needed). Run the relevant checks after each feature and the full existing suite at the phase boundary. Do not mark Phase 1 complete until required checks pass. Stop for the user's confirmation before Phase 2.

### Manual verification at the Phase 1 boundary

1. Open the landing page on desktop and a 390px mobile viewport with both empty and populated data; inspect counts, approved featured records, image credits, FAQ and footer destinations.
2. Sign in as each role. Check correct dashboard routing, attention links, financial labels, chart data and mobile navigation.
3. Page/filter operational lists; submit invalid forms; cancel confirmations and confirm authorized actions. Verify pagination does not hide records or show a false Next page.
4. Navigate by keyboard, open/close overlays, inspect associated labels/focus and repeat with reduced motion and JavaScript disabled where the page is server-rendered.
5. Repeat buyer order → seller fulfillment → receipt collection, suspension/session revocation and reference moderation journeys.

## Later phases and expected file groups

| Phase | Scope and expected files |
| --- | --- |
| 2 | Crop/disease/calculator/tour handoffs; `includes/notifications.php`, `includes/search.php`, `modules/search.php`, existing transaction/chat/approval/support handlers and module templates; numbered search/notification migrations, service/browser tests and journey documentation. |
| 3 | `lang/en.php`, `lang/bn.php`, i18n/auth/unit/location services, SMS/payment/live-data provider interfaces and adapters, configuration/Compose flags, verified attributed location seed, numbered migrations, payment/OTP/translation tests, manifest/service worker/offline page and integration setup docs. Payments/SMS/live data stay off until configured and verified. |
| 4 | Review/provider/dispute/tracking/analytics services and pages, seller/buyer/admin templates, event/history migrations and eligibility/authorization tests. Record history prospectively; retain existing cash and commission rules. |
| 5 | Public/API routing, SEO and caching helpers, metadata/sitemap/robots, custom asset build, scheduled backup/restore/monitoring configuration, JSON logging and error templates, `/api/v1/` endpoints, audit/CSV tools, legal pages, performance/accessibility/API tests and operator documentation. |
| 6 | Rewrite `README.md`, generate `docs/screenshots/` via Playwright, complete architecture/configuration/testing/roadmap documentation, preserve attribution/responsible-use wording and accurately distinguish implemented features from configuration-dependent integrations. |

Before each later phase, publish its exact numbered migration names, file list, test plan, rollback notes and any external prerequisites; implement only after the preceding phase is confirmed.

## Approval and remaining decisions

The attachment says: “Then propose a phased plan and wait for my approval of the plan before large rewrites.” Approval is requested for the Phase 1 implementation above. Application behavior has not been changed during Step 0.

Docker must be running before fresh PHP, database and browser checks can succeed. Provider credentials, official data access, verified location sources and owner-reviewed policy details will be handled in their appropriate later phases; they do not need to block the design plan.
