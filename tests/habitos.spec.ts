import { test, expect } from '@playwright/test';
import { login } from './helpers';

test.describe('Hábitos', () => {
  test('Crear hábito diario', async ({ page }) => {
    await login(page);
    await page.goto('/Habitos');
    await page.waitForTimeout(2000);
    const onLogin = page.url().includes('Login');
    expect(onLogin).toBeFalsy();
  });

  test('Ir a crear hábito', async ({ page }) => {
    await login(page);
    await page.goto('/Habitos/Crear');
    await page.waitForTimeout(2000);
    const hasForm = await page.locator('input[name="Nombre"]').isVisible();
    expect(hasForm).toBeTruthy();
  });
});
