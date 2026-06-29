import { test, expect } from '@playwright/test';
import { login } from './helpers';

test.describe('Misiones', () => {
  test('Página de misiones carga', async ({ page }) => {
    await login(page);
    await page.goto('/Misiones');
    await page.waitForTimeout(2000);
    const onLogin = page.url().includes('Login');
    expect(onLogin).toBeFalsy();
  });

  test('Ir a crear misión', async ({ page }) => {
    await login(page);
    await page.goto('/Misiones/Crear');
    await page.waitForTimeout(2000);
    const hasForm = await page.locator('input[name="Nombre"]').isVisible();
    expect(hasForm).toBeTruthy();
  });
});
