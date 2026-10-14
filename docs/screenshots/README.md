# Phase 1 screenshots

Playwright generates these PNGs from an **isolated test database**. Names such as “Test rice”, test accounts, prices, counts and recorded transactions are fixtures, not real marketplace activity or official market prices. The credited reference photographs remain real, attributed illustrations.

- `landing-desktop.png` / `landing-mobile.png`: populated landing page.
- `landing-empty-desktop.png` / `landing-empty-mobile.png`: honest empty catalog and next actions.
- `dashboard-desktop.png` / `dashboard-mobile.png`: shared dashboard layout, attention cards and trends.
- `produce-desktop.png` / `produce-mobile.png`: filtered seller list and produce form.

Run the browser fixture setup and `npm run test:browser` to regenerate them. Full-page captures include sticky/fixed navigation at the browser viewport's capture position; inspect an interactive viewport when checking its scrolling behavior.

See [the design system](../DESIGN_SYSTEM.md) for component and accessibility behavior.
