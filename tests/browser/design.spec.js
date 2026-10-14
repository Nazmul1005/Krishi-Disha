const { test, expect } = require('@playwright/test');
const { AxeBuilder } = require('@axe-core/playwright');
const fs = require('node:fs');
const fixtures = require('../../.browser-fixtures.json');
const { execFileSync } = require('node:child_process');
test.beforeAll(() => {
  require('node:child_process').execFileSync('docker', ['compose','run','--rm','--no-deps','-v',process.cwd() + ':/app','migrate','php','tests/browser-state.php','reset_limits'], { stdio: 'pipe' });
});
async function signIn(page, role, project) {
  const account = fixtures.accounts[project][role];
  await page.goto('/KrishiDisha/auth/login.php?role=' + role);
  await page.locator('#email').fill(account.email); await page.locator('#password').fill(account.password);
  await page.locator('form[action^="login.php"] button[type="submit"]').click();
  await expect(page).toHaveURL(new RegExp('/' + (role === 'general' ? 'user' : role) + '/dashboard.php'));
}
async function audit(page) {
  const result = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
  expect(result.violations.map(v => ({ id: v.id, nodes: v.nodes.map(n => ({ target: n.target, summary: n.failureSummary })) }))).toEqual([]);
}
test('landing page is truthful, keyboard usable and accessible', async ({ page }, info) => {
  const errors = []; page.on('pageerror', error => errors.push(error.message));
  expect((await page.goto('/KrishiDisha/')).status()).toBe(200);
  await expect(page.locator('.entry-card')).toHaveCount(5);
  await expect(page.locator('.produce-grid .market-card')).toHaveCount(4);
  await expect(page.locator('.tour-grid .market-card')).toHaveCount(2);
  await expect(page.locator('.registration-note')).toContainText('registration will open');
  await expect(page.locator('.asking-label').first()).toContainText('Seller asking price');
  await expect(page.locator('.listing-no-photo').first()).toContainText('photo not supplied');
  await page.getByText('How do payments work?', { exact: true }).click();
  await expect(page.getByText('Cash collection is recorded after fulfillment', { exact: false })).toBeVisible();
  await audit(page);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  fs.mkdirSync('docs/screenshots', { recursive: true });
  await page.evaluate(() => scrollTo(0, 0));
  await page.screenshot({ path: 'docs/screenshots/landing-' + info.project.name + '.png', fullPage: true });
  expect(errors).toEqual([]);
});
test('general route, mobile navigation and role activity are accessible', async ({ page }, info) => {
  await signIn(page, 'general', info.project.name);
  await expect(page.locator('.attention-card')).toHaveCount(3);
  await expect(page.locator('.dashboard-trends .card-kd')).toHaveCount(2);
  await page.goto('/KrishiDisha/'); await page.locator('.header-dashboard').click();
  await expect(page).toHaveURL(/\/user\/dashboard.php/);
  if (info.project.name === 'mobile') {
    await expect(page.locator('.bottom-nav')).toBeVisible();
    await page.locator('[data-open-menu]').click(); await expect(page.locator('#sidebar')).toHaveClass(/open/);
    await page.keyboard.press('Escape'); await expect(page.locator('#sidebar')).not.toHaveClass(/open/);
  }
  await audit(page);
  await page.screenshot({ path: 'docs/screenshots/dashboard-' + info.project.name + '.png', fullPage: true });
});
test('seller lists are paginated, filtered and have usable form labels', async ({ page }, info) => {
  await signIn(page, 'farmer', info.project.name); await page.goto('/KrishiDisha/farmer/produce.php');
  await expect(page.locator('.table-kd tbody tr')).toHaveCount(25);
  await expect(page.locator('a[rel="next"]')).toHaveCount(1); await page.locator('a[rel="next"]').click();
  await expect(page.locator('.table-kd tbody tr')).toHaveCount(4); await expect(page.locator('a[rel="next"]')).toHaveCount(0);
  await page.goto('/KrishiDisha/farmer/produce.php?produce_q=Test%20listing%2001');
  await expect(page.locator('.table-kd tbody tr')).toHaveCount(1);
  await expect(page.locator('.pagination-summary')).toContainText('1 record');
  await expect(page.getByLabel('Quantity (kg)', { exact: false })).toHaveCount(1);
  await audit(page);
  await page.screenshot({ path: 'docs/screenshots/produce-' + info.project.name + '.png', fullPage: true });
});
test('confirmation can be cancelled and invalid fields explain the error', async ({ page }, info) => {
  await signIn(page, 'general', info.project.name); await page.goto('/KrishiDisha/user/marketplace.php');
  const product = fixtures.accounts[info.project.name].product;
  const form = page.locator('form').filter({ has: page.locator('input[name="product_id"][value="' + product + '"]') });
  await form.locator('[name="order_farmer"]').click();
  await expect(form.locator('[aria-invalid="true"]').first()).toBeVisible();
  await form.locator('[name="quantity_kg"]').fill('2'); await form.locator('[name="delivery_address"]').fill('Test pickup'); await form.locator('[name="contact_phone"]').fill('01700000000');
  await form.locator('[name="order_farmer"]').click(); await expect(page.locator('#confirmDialog')).toBeVisible();
  await page.locator('#confirmDialog button[value="cancel"]').click(); await expect(page).toHaveURL(/marketplace.php/); await expect(form.locator('[name="quantity_kg"]')).toHaveValue('2');
  await audit(page);
});
test('public information works without JavaScript and with reduced motion', async ({ browser }, info) => {
  const context = await browser.newContext({ ...info.project.use, javaScriptEnabled: false, reducedMotion: 'reduce' }); const page = await context.newPage();
  await page.goto('/KrishiDisha/'); await expect(page.locator('.hero-copy h1')).toBeVisible(); await expect(page.locator('.count-section strong').first()).not.toHaveText('0');
  await page.goto('/KrishiDisha/modules/privacy.php'); await expect(page.locator('main')).toContainText('Owner review required');
  await page.goto('/KrishiDisha/modules/encyclopedia.php?detail=1'); await expect(page.locator('#cropDetailModal')).toBeVisible();
  await context.close();
});
test('provider and buyer dashboards have accessible controls and charts', async ({ browser }, info) => {
  test.setTimeout(60000);
  for (const role of ['farmer', 'dealer', 'tourist', 'cook', 'expert', 'guide', 'general']) {
    const context = await browser.newContext(info.project.use); const page = await context.newPage();
    await signIn(page, role, info.project.name); await audit(page);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    await context.close();
  }
});
test('empty catalog has clear actions and real zero counts', async ({ page }, info) => {
  const command = ['compose', 'run', '--rm', '--no-deps', '-v', process.cwd() + ':/app', 'migrate', 'php', 'tests/browser-state.php'];
  const state = execFileSync('docker', [...command, 'hide_catalog'], { encoding: 'utf8', stdio: ['pipe', 'pipe', 'pipe'] });
  try {
    await page.goto('/KrishiDisha/'); await expect(page.getByRole('heading', { name: 'No produce available yet' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Register as a farmer', exact: true })).toBeVisible();
    await expect(page.locator('.count-section strong').first()).toHaveText('0');
    await expect(page.locator('.produce-grid')).toHaveCount(0); await expect(page.locator('.tour-grid')).toHaveCount(0);
    await audit(page); await page.evaluate(() => scrollTo(0, 0));
    await page.screenshot({ path: 'docs/screenshots/landing-empty-' + info.project.name + '.png', fullPage: true });
  } finally { execFileSync('docker', [...command, 'restore_catalog'], { input: state, stdio: ['pipe', 'pipe', 'pipe'] }); }
});
