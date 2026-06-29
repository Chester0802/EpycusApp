import { test, expect } from '@playwright/test';

test.describe('General Web', () => {
  test('Health endpoint responde', async ({ page }) => {
    const resp = await page.request.get('/health');
    expect(resp.ok()).toBeTruthy();
  });

  test('Sitemap.xml existe', async ({ page }) => {
    const resp = await page.request.get('/sitemap.xml');
    expect(resp.ok()).toBeTruthy();
  });

  test('Robots.txt existe', async ({ page }) => {
    const resp = await page.request.get('/robots.txt');
    expect(resp.ok()).toBeTruthy();
  });

  test('Error 404 se muestra', async ({ page }) => {
    const resp = await page.goto('/ruta-inexistente-xyz');
    expect(resp?.status()).toBe(404);
  });

  test('Contenido de privacidad accesible', async ({ page }) => {
    const resp = await page.goto('/Home/Privacy');
    expect(resp?.ok()).toBeTruthy();
  });
});
