# KrishiDisha interface system

Phase 1 keeps the PHP/PDO application and locally hosted Bootstrap/Font Awesome assets. Shared presentation lives in `includes/`, `assets/css/style.css` and `assets/js/main.js`. No frontend framework or hosted font dependency was added.

## Foundations

| Token | Purpose | Value |
| --- | --- | --- |
| `--primary` | Main actions and links | `#176044` |
| `--primary-dark` | Headings and dark surfaces | `#103e2e` |
| `--accent` | Warm highlight | `#b16a16` |
| `--text` | Body text | `#18352a` |
| `--text-muted` | Supporting text | `#52665d` |
| `--surface`, `--surface2`, `--surface3` | Content and page surfaces | White, `#f6f8f5`, `#edf3ee` |
| `--success`, `--warning`, `--danger`, `--info` | Semantic states | Green, amber, red, blue |
| `--space-1` … `--space-8` | Spacing | 4, 8, 12, 16, 24, 32, 48, 64px |
| `--radius-sm`, `--radius` | Controls and cards | 8px, 16px |
| `--shadow`, `--shadow-lg` | Elevation | Soft green-tinted shadows |

Manrope (`KD UI`) and Noto Sans Bengali (`KD Bengali`) are locally hosted variable WOFF2 fonts. `assets/fonts/fonts.css` declares script-specific ranges; the Bengali language selection prioritizes the Bengali family. Font licenses and upstream attribution are in `assets/fonts/`. Full interface translation remains Phase 3.

Text, focus and semantic colors must retain their measured contrast when adding components. Do not use the warm accent as small body text on white without checking contrast. Existing role-specific primary-color overrides were removed. Use visible text as well as color to describe a state.

## Components

| Component | Classes/helper | Behavior |
| --- | --- | --- |
| Buttons | `.btn-kd`, primary/outline/danger/gold variants | Minimum 44px height, visible keyboard focus; danger confirmation where applicable |
| Inputs/selects | `.form-kd`, `.form-control` | Associated labels; native constraints plus field errors linked with `aria-describedby` |
| Cards/badges | `.card-kd`, `.stat-card`, `.badge-kd` | Shared radius/border/typography; semantic status text |
| Tables | `.table-kd` | Semantic column headings; JavaScript adds labels for card-style rows below 650px |
| Confirmation | `#confirmDialog`, `[data-confirm]` | Native modal dialog, Escape/cancel and focus restoration; submitting action retains its POST name/value |
| Crop/proposal dialogs | `#cropDetailModal`, `#rejectModal` | Native focus containment and Escape; crop details also render without JavaScript |
| Tabs | `.tabs`, `role=tablist/tab/tabpanel` | Arrow/Home/End keyboard navigation and selected state |
| Alerts | `.alert-kd` | Error/success roles, explicit dismiss buttons; errors remain until dismissed |
| Confirmation messages | `[data-toast]` | Receipt/transaction success shown through the existing escaped flash-message service |
| Pagination | `pagedRows()`, `listControls()`, `listPagers()` | Bound SQL pages, server filtering, preserved query parameters and terminal-page detection |
| Breadcrumbs | `breadcrumbs()` | Home and current page, with a named navigation landmark |
| Loading | `.skeleton`, `#pageLoading` | Shown only when an actual form navigation begins; no fabricated record placeholders |
| Empty state | `uiEmpty()` / `emptyState()` | Description, icon and an actionable link; real zero counts remain zero |
| Timeline/stepper | `.timeline`, `.stepper` | Reusable ordered progress styles; transaction event timelines remain Phase 4 |
| Trends | `.trend-chart`, `.chart-data` | Server-rendered bars backed by a monthly values table; no chart dependency |

`assetUrl()` adds a content hash to shared asset URLs. Keep credited reference originals and their checksums; regenerate the WebP derivatives with `node bin/images.js`, and verify them with `--check`.

## Layout

`header.php` includes the shared public header/search/language control. `navigation.php` owns role routes and exact active-route matching. `sidebar.php` uses those routes, and `bottom_nav.php` exposes the mobile shortcuts. Every role uses the same header, footer and navigation components.

Desktop uses a 260px sidebar. Below 992px the sidebar is toggled and a bottom bar appears; narrow screens place crop search below the brand. Form controls and navigation targets have a 44px baseline. Menu state is announced through `aria-expanded`, and Escape closes it and restores focus.

Server-rendered information stays visible without JavaScript. JavaScript enhances tables, dialogs, validation and navigation loading. Reduced-motion settings disable animation; numeric information is never held back for a counting effect. Browser back/forward navigation clears the pending form state.

## Data and trust rules

- Featured produce/tours come from approved, verified providers and published, in-stock/active records. Missing seller/host images are labeled; reference photography is never passed off as a seller's photograph.
- Landing counts come from current database queries. Prices are seller asking prices, labeled with the listing date.
- Dashboard money comes from verified cash records. Refund-review/refunded records are excluded. Net proceeds are after platform commission; they do not represent profit after production/procurement costs.
- Consultation read positions change only through authenticated, CSRF-protected POST requests by a participant. Reading a page does not create an unprotected mutation.
- Agricultural reference review/source/date requirements and private media validation remain in force.

## Verification

The browser suite uses axe with WCAG 2 A/AA and WCAG 2.1 AA tags on representative forms, marketplace screens and every role dashboard in desktop/mobile viewports. It also checks keyboard/menu/confirmation behavior, bounded lists, empty/populated landing states and public content without JavaScript. An automated pass is evidence for the covered screens, not a substitute for assistive-technology and Bangla field testing.

Screenshots in `docs/screenshots/` are generated from explicitly labeled disposable test data. They are not evidence of real marketplace supply, prices or customer activity.
