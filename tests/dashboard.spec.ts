import { test, expect } from '@playwright/test';
import { login, TEST_USER } from './helpers';

test.describe('Dashboard (Home)', () => {
  test('Inicio carga tras login', async ({ page }) => {
    await login(page);
    await page.goto('/');
    await page.waitForTimeout(3000);
    const onLogin = page.url().includes('Login');
    expect(onLogin).toBeFalsy();
  });

  test('Redirección a login si no autenticado', async ({ page }) => {
    await page.goto('/');
    await page.waitForTimeout(2000);
    expect(page.url().includes('Login')).toBeTruthy();
  });
});
