import { expect, test } from '@playwright/test';

test('public smoke route responds when an E2E base URL is provided', async ({ request }) => {
    test.skip(!process.env.E2E_BASE_URL, 'Set E2E_BASE_URL to run browser/server E2E smoke tests.');

    const response = await request.get('/');

    expect(response.ok()).toBe(true);
});
