# Manual Testing Guide - Quick Add Functionality

## Test Environment
- URL: http://localhost/panglong/frontend/
- Login: admin / password123

---

## 1. Products Page - Quick Add Warehouse Location

### Steps:
1. Login to the application
2. Navigate to Products page
3. Click "Tambah Produk" button
4. In the modal, find "Lokasi Penyimpanan" dropdown
5. Click the "+" button next to the dropdown
6. Quick-add modal should appear with title "Tambah Lokasi Baru"
7. Enter a test code (e.g., "TEST-LOC-001")
8. Click "Simpan"
9. Alert should show "Berhasil ditambahkan"
10. Modal should close
11. Focus should return to the location dropdown
12. The new location should be selected in the dropdown
13. Format should be: "TEST-LOC-001 - TEST-LOC-001"

### Expected Result:
- New location is added to the dropdown
- Location is automatically selected
- Form focus is maintained

---

## 2. Products Page - Quick Add Unit Measurement

### Steps:
1. Login to the application
2. Navigate to Products page
3. Click "Tambah Produk" button
4. In the modal, find "Satuan Produk" section
5. Click the "+" button next to the unit dropdown
6. Quick-add modal should appear with title "Tambah Satuan Baru"
7. Enter a test code (e.g., "UNIT-TEST")
8. Click "Simpan"
9. Alert should show "Berhasil ditambahkan"
10. Modal should close
11. Focus should return to the unit dropdown
12. The new unit should be selected in the dropdown

### Expected Result:
- New unit is added to the dropdown
- Unit is automatically selected
- Form focus is maintained
- All unit dropdowns in the form are updated (if multiple units exist)

---

## 3. Sales Page - Quick Add Payment Method

### Steps:
1. Login to the application
2. Navigate to Sales page
3. Click "Penjualan Baru" button
4. In the modal, find "Metode Bayar" dropdown
5. Click the "+" button next to the dropdown
6. Quick-add modal should appear with title "Tambah Metode Bayar Baru"
7. Enter a test code (e.g., "PAY-TEST")
8. Enter a test name (e.g., "Test Payment Method")
9. Click "Simpan"
10. Alert should show "Metode bayar berhasil ditambahkan"
11. Modal should close
12. Focus should return to the payment method dropdown
13. The new payment method should be selected in the dropdown

### Expected Result:
- New payment method is added to the dropdown
- Payment method is automatically selected
- Form focus is maintained

---

## 4. Existing Quick Add - Brand (Products)

### Steps:
1. Login to the application
2. Navigate to Products page
3. Click "Tambah Produk" button
4. Click "+" next to "Merek/Brand" dropdown
5. Enter brand name
6. Click "Simpan"
7. Verify brand is added and selected

### Expected Result:
- Brand is added to dropdown
- Brand is automatically selected
- Focus returns to dropdown

---

## 5. Existing Quick Add - Category (Products)

### Steps:
1. Login to the application
2. Navigate to Products page
3. Click "Tambah Produk" button
4. Click "+" next to "Kategori" dropdown
5. Enter category name
6. Click "Simpan"
7. Verify category is added and selected

### Expected Result:
- Category is added to dropdown
- Category is automatically selected
- Focus returns to dropdown

---

## 6. Existing Quick Add - Customer Group (Customers)

### Steps:
1. Login to the application
2. Navigate to Customers page
3. Click "Tambah Pelanggan" button
4. Click "+" next to "Grup" dropdown
5. Enter group name
6. Click "Simpan"
7. Verify group is added and selected

### Expected Result:
- Group is added to dropdown
- Group is automatically selected
- Focus returns to dropdown

---

## 7. Existing Quick Add - Customer (Sales)

### Steps:
1. Login to the application
2. Navigate to Sales page
3. Click "Penjualan Baru" button
4. Click "+" next to "Pelanggan" dropdown
5. Enter customer details (name, phone, email, address)
6. Click "Simpan"
7. Verify customer is added and selected

### Expected Result:
- Customer is added to dropdown
- Customer is automatically selected
- Focus returns to dropdown

---

## Common Issues to Check

### Issue: Modal doesn't appear
- Check browser console for JavaScript errors
- Verify modal HTML exists in the page

### Issue: Data not added to dropdown
- Check network tab for API request
- Verify API endpoint returns HTTP 201
- Verify response includes `success: true` and `data` object

### Issue: Focus not returned to dropdown
- Check if `select.focus()` is called after modal close
- Verify modal is properly hidden before focus is set

### Issue: Form data lost
- Verify form is not submitted or reloaded
- Check that only the modal is closed, not the main form

---

## API Endpoint Verification

### warehouse-locations POST
```bash
curl -X POST http://localhost/panglong/frontend/ajax.php?endpoint=warehouse-locations \
  -H "Content-Type: application/json" \
  -d '{"warehouse_id":1,"code":"TEST-001","name":"Test Location","zone_type":"storage"}'
```

Expected response:
```json
{
  "success": true,
  "data": {
    "id": 123,
    "code": "TEST-001",
    "name": "Test Location"
  }
}
```

### unit-measurements POST
```bash
curl -X POST http://localhost/panglong/frontend/ajax.php?endpoint=unit-measurements \
  -H "Content-Type: application/json" \
  -d '{"code":"UNIT-001","name":"Test Unit"}'
```

Expected response:
```json
{
  "success": true,
  "data": {
    "id": 456,
    "code": "UNIT-001",
    "name": "Test Unit"
  }
}
```

### payment-methods POST
```bash
curl -X POST http://localhost/panglong/frontend/ajax.php?endpoint=payment-methods \
  -H "Content-Type: application/json" \
  -d '{"code":"PAY-001","name":"Test Payment"}'
```

Expected response:
```json
{
  "success": true,
  "data": {
    "id": 789,
    "code": "PAY-001",
    "name": "Test Payment"
  }
}
```

---

## Test Completion Checklist

- [ ] Warehouse location quick-add works
- [ ] Unit measurement quick-add works
- [ ] Payment method quick-add works
- [ ] Brand quick-add still works
- [ ] Category quick-add still works
- [ ] Customer group quick-add still works
- [ ] Customer quick-add still works
- [ ] Focus returns to dropdown after adding
- [ ] New data is automatically selected
- [ ] Form data is not lost
- [ ] All API endpoints return HTTP 201
- [ ] All API responses include required fields
