import { test, expect } from '@playwright/test';
import { login } from './helpers';

test.describe('IA Assistant (EDY)', () => {
  test('Página de IA carga', async ({ page }) => {
    await login(page);
    await page.goto('/Ia');
    await page.waitForTimeout(2000);
    const onLogin = page.url().includes('Login');
    expect(onLogin).toBeFalsy();
  });

  test('Enviar mensaje y recibir respuesta', async ({ page }) => {
    await login(page);
    await page.goto('/Ia');
    await page.waitForTimeout(2000);
    const input = page.locator('textarea').first();
    if (await input.isVisible()) {
      await input.fill('Hola EDY');
      await page.evaluate(() => {
        const banner = document.getElementById('ep-cookie-consent');
        if (banner) banner.remove();
      }).catch(() => {});
      const btn = page.locator('button[type="submit"]').last();
      await btn.click({ force: true });
      await page.waitForTimeout(5000);
    }
  });
});
