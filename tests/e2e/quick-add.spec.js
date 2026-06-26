const { test, expect } = require('@playwright/test');

const API_BASE = 'http://localhost/panglong/frontend/ajax.php';

test.describe('Quick Add API Endpoints', () => {
    test.skip('warehouse-locations POST endpoint creates location', async ({ request }) => {
        // Skipped: Requires valid warehouse_id in database
        const testCode = 'TEST-' + Date.now();
        const response = await request.post(`${API_BASE}?endpoint=warehouse-locations&test_mode=true`, {
            data: {
                warehouse_id: 1,
                code: testCode,
                name: testCode,
                zone_type: 'storage'
            }
        });

        expect(response.ok()).toBeTruthy();
        const data = await response.json();
        expect(data.success).toBeTruthy();
        expect(data.data.code).toBe(testCode);
        expect(data.data.name).toBe(testCode);
    });

    test('unit-measurements POST endpoint creates unit', async ({ request }) => {
        const testCode = 'UNIT-' + Date.now();
        const response = await request.post(`${API_BASE}?endpoint=unit-measurements&test_mode=true`, {
            data: {
                code: testCode,
                name: testCode
            }
        });

        expect(response.ok()).toBeTruthy();
        const data = await response.json();
        expect(data.success).toBeTruthy();
        expect(data.data.code).toBe(testCode);
        expect(data.data.name).toBe(testCode);
    });

    test('payment-methods POST endpoint creates payment method', async ({ request }) => {
        const testCode = 'PAY-' + Date.now();
        const response = await request.post(`${API_BASE}?endpoint=payment-methods&test_mode=true`, {
            data: {
                code: testCode,
                name: 'Test Payment Method'
            }
        });

        expect(response.ok()).toBeTruthy();
        const data = await response.json();
        expect(data.success).toBeTruthy();
        expect(data.data.code).toBe(testCode);
        expect(data.data.name).toBe('Test Payment Method');
    });
});
