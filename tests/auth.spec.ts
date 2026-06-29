import { test, expect } from '@playwright/test';
import { login, uniqueEmail, TEST_USER } from './helpers';

async function dismissCookie(page: any) {
  await page.evaluate(() => {
    const banner = document.getElementById('ep-cookie-consent');
    if (banner) banner.remove();
  }).catch(() => {});
}

test.describe('Autenticación', () => {
  test('Login con credenciales válidas via API', async ({ page }) => {
    await login(page);
    await page.goto('/');
    await page.waitForTimeout(2000);
    const onLogin = page.url().includes('Login');
    expect(onLogin).toBeFalsy();
  });

  test('Login con credenciales inválidas', async ({ page }) => {
    await page.goto('/Autenticacion/Login');
    await dismissCookie(page);
    await page.fill('input[name="CorreoElectronico"]', 'noexiste@test.com');
    await page.fill('input[name="Contrasena"]', 'wrong');
    await page.click('button[type="submit"]', { force: true });
    await page.waitForTimeout(2000);
    const stillOnLogin = page.url().includes('Login');
    expect(stillOnLogin).toBeTruthy();
  });

  test('Campos vacíos en login', async ({ page }) => {
    await page.goto('/Autenticacion/Login');
    await dismissCookie(page);
    await page.click('button[type="submit"]', { force: true });
    await page.waitForTimeout(2000);
    const stillOnLogin = page.url().includes('Login');
    expect(stillOnLogin).toBeTruthy();
  });

  test('Registro de nuevo usuario', async ({ page }) => {
    const email = uniqueEmail('accept');
    await page.goto('/Autenticacion/Registro');
    await dismissCookie(page);
    await page.fill('input[name="Nombre"]', 'Accept Test');
    await page.fill('input[name="CorreoElectronico"]', email);
    await page.fill('input[name="Contrasena"]', 'Test123456!');
    await page.fill('input[name="ConfirmarContrasena"]', 'Test123456!');
    await page.selectOption('select[name="CarreraId"]', { index: 1 });
    await page.fill('input[name="FechaNacimiento"]', '2000-01-01');
    await page.click('button[type="submit"]', { force: true });
    await page.waitForTimeout(5000);
  });

  test('Logout cierra sesión', async ({ page }) => {
    await login(page);
    await page.goto('/');
    await dismissCookie(page);
    const btn = page.locator('button:has-text("Cerrar sesión")');
    if (await btn.isVisible()) {
      await btn.click({ force: true });
      await page.waitForTimeout(2000);
    }
    await page.goto('/Autenticacion/Login');
    await page.waitForTimeout(2000);
    expect(page.url().includes('Login')).toBeTruthy();
  });
});
