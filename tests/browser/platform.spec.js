const { test, expect } = require('@playwright/test');
const crypto = require('node:crypto');
const fixtures = require('../../.browser-fixtures.json');
function code(secret) {
  let bits = ''; const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  for (const char of secret) bits += alphabet.indexOf(char).toString(2).padStart(5, '0');
  const bytes = []; for (let i = 0; i + 8 <= bits.length; i += 8) bytes.push(parseInt(bits.slice(i, i + 8), 2));
  const count = Buffer.alloc(8); count.writeBigUInt64BE(BigInt(Math.floor(Date.now() / 30000)));
  const hash = crypto.createHmac('sha1', Buffer.from(bytes)).update(count).digest();
  return ((hash.readUInt32BE(hash[19] & 15) & 0x7fffffff) % 1000000).toString().padStart(6, '0');
}
async function login(page, account, role) {
  await page.goto('/KrishiDisha/auth/login.php?role=' + role);
  await page.locator('#email').fill(account.email); await page.locator('#password').fill(account.password);
  await page.locator('form[action^="login.php"] button[type="submit"]').click();
  if (role === 'admin') { await page.locator('#code').fill(code(account.totp)); await page.getByRole('button', { name: 'Verify', exact: true }).click(); }
  await expect(page).toHaveURL(new RegExp('/' + (role === 'general' ? 'user' : role) + '/dashboard.php'));
}
test('public endpoints, protected files, script injection and CSRF fail closed', async ({ page, request }) => {
  const errors = []; page.on('pageerror', error => errors.push(error.message));
  for (const path of ['/KrishiDisha/', '/KrishiDisha/modules/disease.php', '/KrishiDisha/modules/recommend.php', '/KrishiDisha/modules/nutrition.php']) { const response = await page.goto(path); expect(response.status()).toBe(200); }
  for (const path of ['.env', '.git/config', 'config/db.php', 'database/schema.sql', 'bin/create-admin.php']) expect((await request.get('/KrishiDisha/' + path)).status()).toBe(404);
  expect((await request.get('/KrishiDisha/auth/login.php?role=' + encodeURIComponent('<img src=x onerror=alert(1)>'))).status()).toBe(400);
  expect((await request.post('/KrishiDisha/auth/login.php', { form: { email: 'x@example.test', password: 'x', role: 'admin' } })).status()).toBe(403);
  expect((await request.get('/KrishiDisha/admin/users.php?delete=1')).status()).toBe(405);
  await page.goto('/KrishiDisha/auth/forgot_password.php'); await page.locator('#email').fill('someone@example.test'); await page.locator('form.form-kd button[type="submit"]').click();
  await expect(page.locator('body')).not.toContainText('Development mode'); await expect(page.locator('a[href*="reset_password.php?token="]')).toHaveCount(0);
  expect(errors).toEqual([]);
});
test('all roles can access their pages without browser or server errors', async ({ browser }, info) => {
  const routes = { admin: ['users', 'approvals', 'manage_content', 'proposals', 'commissions', 'knowledge'], farmer: ['produce', 'farmland', 'orders', 'bookings'], dealer: ['inventory', 'sales'], tourist: ['tours', 'food_orders'], cook: ['recipes', 'orders'], expert: ['sessions'], guide: ['bookings', 'sessions'], general: [] };
  for (const [role, paths] of Object.entries(routes)) {
    const context = await browser.newContext(info.project.use); const page = await context.newPage(); const errors = []; page.on('pageerror', error => errors.push(error.message));
    await login(page, fixtures.accounts[info.project.name][role], role);
    for (const path of paths) expect((await page.goto('/KrishiDisha/' + role + '/' + path + '.php')).status()).toBe(200);
    for (const path of ['profile', 'orders', 'payments', 'notifications', 'support']) expect((await page.goto('/KrishiDisha/modules/' + path + '.php')).status()).toBe(200);
    expect(errors).toEqual([]); await context.close();
  }
});
test('buyer reserves produce and farmer can fulfill it with separate cash collection', async ({ browser }, info) => {
  const accounts = fixtures.accounts[info.project.name];
  const buyer = await browser.newContext(info.project.use); const page = await buyer.newPage(); await login(page, accounts.general, 'general');
  await page.goto('/KrishiDisha/user/marketplace.php'); const form = page.locator('form').filter({ has: page.locator('input[name="product_id"][value="' + accounts.product + '"]') });
  await form.locator('[name="quantity_kg"]').fill('2'); await form.locator('[name="delivery_address"]').fill('Local test market pickup'); await form.locator('[name="contact_phone"]').fill('01700000000'); page.on('dialog', dialog => dialog.accept()); await form.locator('[name="order_farmer"]').click();
  await expect(page.locator('body')).toContainText('reserved'); await page.goto('/KrishiDisha/modules/orders.php'); await expect(page.locator('body')).toContainText('2.00 kg');
  const seller = await browser.newContext(info.project.use); const farm = await seller.newPage(); await login(farm, accounts.farmer, 'farmer'); await farm.goto('/KrishiDisha/farmer/orders.php');
  await farm.locator('button[value="confirmed"]').first().click(); await farm.locator('button[value="delivered"]').first().click();
  await farm.goto('/KrishiDisha/modules/payments.php'); const collect = farm.locator('form').filter({ has: farm.locator('button[value="collect"]') }).first();
  await collect.locator('[name="amount"]').fill('40'); await collect.locator('[name="reference"]').fill('browser-cash-receipt'); farm.on('dialog', dialog => dialog.accept()); await collect.locator('button[value="collect"]').click(); await expect(farm.locator('body')).toContainText('collection verified');
  await buyer.close(); await seller.close();
});
test('calculator works with local units, costs and scenarios', async ({ page }, info) => {
  await page.goto('/KrishiDisha/modules/calculator.php'); await page.locator('#area').fill('100'); await page.locator('#yieldKg').fill('1000'); await page.locator('#price').fill('20'); await page.locator('#cost0').fill('10000');
  await page.locator('#farmEstimate button').click(); await expect(page.locator('#estimateResults')).toBeVisible(); await expect(page.locator('#estimateError')).toBeEmpty();
  await page.screenshot({ path: 'test-results/calculator-' + info.project.name + '.png', fullPage: true });
});

test('suspension and password change invalidate existing sessions', async ({ browser }, info) => {
  const { execFileSync } = require('node:child_process');
  const account = fixtures.accounts[info.project.name].general;
  const first = await browser.newContext(info.project.use); const a = await first.newPage(); await login(a, account, 'general');
  execFileSync('docker', ['compose', 'run', '--rm', '--no-deps', '-v', process.cwd() + ':/app', 'migrate', 'php', 'tests/account-action.php', String(account.id), 'suspend'], { stdio: 'pipe' });
  await a.goto('/KrishiDisha/user/dashboard.php'); await expect(a).toHaveURL(/auth\/login.php/);
  execFileSync('docker', ['compose', 'run', '--rm', '--no-deps', '-v', process.cwd() + ':/app', 'migrate', 'php', 'tests/account-action.php', String(account.id), 'approve'], { stdio: 'pipe' });
  await login(a, account, 'general');
  const second = await browser.newContext(info.project.use); const b = await second.newPage(); await login(b, account, 'general');
  await a.goto('/KrishiDisha/modules/profile.php'); await a.locator('#current_password').fill(account.password); await a.locator('#new_password').fill('Changed-test-password-2026'); await a.locator('#confirm_password').fill('Changed-test-password-2026'); await a.locator('[name="change_password"]').click();
  await expect(a).toHaveURL(/auth\/login.php/); await b.goto('/KrishiDisha/user/dashboard.php'); await expect(b).toHaveURL(/auth\/login.php/);
  await first.close(); await second.close();
});
