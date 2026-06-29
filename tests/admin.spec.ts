import { test, expect } from '@playwright/test';
import { ADMIN_USER } from './helpers';

test.describe('Admin Panel', () => {
  test('Página de login admin visible', async ({ page }) => {
    await page.goto('/admin/login');
    await expect(page.locator('form')).toBeVisible({ timeout: 10000 });
    expect(page.url().includes('admin/login')).toBeTruthy();
  });
});
