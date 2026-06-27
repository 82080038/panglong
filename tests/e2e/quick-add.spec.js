const { test, expect } = require('@playwright/test');

const API_BASE = 'http://localhost/panglong/frontend/ajax.php';
const FRONTEND_BASE = 'http://localhost/panglong/frontend';

test.describe('Quick Add API Endpoints', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto(`${FRONTEND_BASE}/login.php`);
        await page.fill('input[name="username"]', 'ownertest');
        await page.fill('input[name="password"]', 'password123');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/index.php');
    });

    test('warehouse-locations POST endpoint creates location', async ({ page }) => {
        // First create a warehouse via authenticated AJAX
        const whRes = await page.evaluate(async () => {
            const res = await fetch('ajax.php?endpoint=warehouses&test_mode=true', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code: 'TEST-WH-' + Date.now(), name: 'Test Warehouse', address: 'Test', is_active: 1 }),
                credentials: 'same-origin'
            });
            return res.json();
        });
        const warehouseId = whRes.success ? whRes.data.id : 1;

        const testCode = 'TEST-' + Date.now();
        const data = await page.evaluate(async ({ warehouseId, testCode }) => {
            const res = await fetch('ajax.php?endpoint=warehouse-locations&test_mode=true', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ warehouse_id: warehouseId, code: testCode, name: testCode, zone_type: 'storage' }),
                credentials: 'same-origin'
            });
            return res.json();
        }, { warehouseId, testCode });

        expect(data.success).toBeTruthy();
        expect(data.data.code).toBe(testCode);
        expect(data.data.name).toBe(testCode);
    });

    test('unit-measurements POST endpoint creates unit', async ({ page }) => {
        const testCode = 'UNIT-' + Date.now();
        const data = await page.evaluate(async (testCode) => {
            const res = await fetch('ajax.php?endpoint=unit-measurements&test_mode=true', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code: testCode, name: testCode }),
                credentials: 'same-origin'
            });
            return res.json();
        }, testCode);

        expect(data.success).toBeTruthy();
        expect(data.data.code).toBe(testCode);
        expect(data.data.name).toBe(testCode);
    });

    test('payment-methods POST endpoint creates payment method', async ({ page }) => {
        const testCode = 'PAY-' + Date.now();
        const data = await page.evaluate(async (testCode) => {
            const res = await fetch('ajax.php?endpoint=payment-methods&test_mode=true', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code: testCode, name: 'Test Payment Method' }),
                credentials: 'same-origin'
            });
            return res.json();
        }, testCode);

        expect(data.success).toBeTruthy();
        expect(data.data.code).toBe(testCode);
        expect(data.data.name).toBe('Test Payment Method');
    });
});
