import { test, expect } from '@playwright/test';
import { login } from './helpers';

test.describe('Pomodoro', () => {
  test('Página de Pomodoro carga', async ({ page }) => {
    await login(page);
    await page.goto('/Pomodoro');
    await page.waitForTimeout(2000);
    const onLogin = page.url().includes('Login');
    expect(onLogin).toBeFalsy();
  });

  test('Configuración de Pomodoro carga', async ({ page }) => {
    await login(page);
    await page.goto('/Pomodoro/Configuracion');
    await page.waitForTimeout(2000);
    const onLogin = page.url().includes('Login');
    expect(onLogin).toBeFalsy();
  });
});
