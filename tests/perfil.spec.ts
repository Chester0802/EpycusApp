import { test, expect } from '@playwright/test';
import { login } from './helpers';

test.describe('Perfil', () => {
  test('Página de perfil carga', async ({ page }) => {
    await login(page);
    await page.goto('/Perfil');
    await page.waitForTimeout(2000);
    const onLogin = page.url().includes('Login');
    expect(onLogin).toBeFalsy();
  });
});
