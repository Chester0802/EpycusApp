import { Page, expect } from '@playwright/test';

export const TEST_USER = {
  email: process.env.TEST_EMAIL || 'antonioazanero123castillo@gmail.com',
  password: process.env.TEST_PASSWORD || 'Marco12@',
  nombre: 'Antonio',
};

export const ADMIN_USER = {
  email: process.env.ADMIN_EMAIL || 'admin@epycus.es',
  password: process.env.ADMIN_PASSWORD || 'Admin123456!',
};

let authToken: string | null = null;

export async function login(
  page: Page,
  email = TEST_USER.email,
  password = TEST_USER.password
) {
  await page.goto('/Autenticacion/Login', { waitUntil: 'domcontentloaded' }).catch(() => {});
  const domain = new URL(page.url()).hostname;

  const resp = await page.request.post('/api/v1/auth/login', {
    data: { correo: email, contrasena: password },
  });
  const json = await resp.json();
  if (!json.exito) throw new Error(`API login failed: ${json.mensaje || 'unknown'}`);

  const token = json.datos?.token;
  if (!token) throw new Error('No token in login response');

  authToken = token;
  await page.context().addCookies([
    { name: 'jwt_token', value: token, domain, path: '/' },
    { name: 'refresh_token', value: json.datos?.refreshToken || '', domain, path: '/' },
  ]);

  await page.evaluate(() => {
    const banner = document.getElementById('ep-cookie-consent');
    if (banner) banner.remove();
  }).catch(() => {});
}

export async function loginApi(
  page: Page,
  email = TEST_USER.email,
  password = TEST_USER.password
) {
  const resp = await page.request.post('/api/v1/auth/login', {
    data: { correo: email, contrasena: password },
  });
  const json = await resp.json();
  authToken = json.datos?.token || null;
  return json;
}

export function getAuthToken(): string | null {
  return authToken;
}

export function uniqueEmail(prefix = 'test'): string {
  const ts = Date.now();
  return `${prefix}_${ts}@epycus.test`;
}

export async function waitForRateLimit(page: Page) {
  await page.waitForTimeout(1000);
}

export async function checkHealth(page: Page) {
  const resp = await page.request.get('/health');
  expect(resp.ok()).toBeTruthy();
  return resp;
}
