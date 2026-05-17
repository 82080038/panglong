# API SPECIFICATION

# PANGLONG ERP - PHASE 1 MVP

## Version: 1.0
## Base URL: `http://localhost:8000/api/v1`
## Authentication: Session-based (Web) / Token-based (Future Mobile)

---

# GENERAL INFORMATION

## Response Format

All API responses follow this structure:

### Success Response
```json
{
    "success": true,
    "message": "Operation successful",
    "data": { ... }
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error description",
    "errors": { ... }
}
```

### Validation Error
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "field_name": ["Error message 1", "Error message 2"]
    }
}
```

## HTTP Status Codes

- `200 OK` - Request successful
- `201 Created` - Resource created successfully
- `400 Bad Request` - Invalid request
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - Permission denied
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation error
- `500 Internal Server Error` - Server error

## Pagination

List endpoints support pagination:

```json
{
    "success": true,
    "data": [...],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 100,
        "last_page": 7
    }
}
```

Query parameters:
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 15, max: 100)

---

# AUTHENTICATION ENDPOINTS

## POST /auth/login

Login user and return authentication token.

**Request Body:**
```json
{
    "username": "kasir1",
    "password": "password123"
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
        "user": {
            "id": 1,
            "username": "kasir1",
            "full_name": "John Doe",
            "role": {
                "id": 3,
                "name": "Kasir",
                "slug": "kasir"
            },
            "permissions": ["create_sales", "view_reports"]
        }
    }
}
```

**Error (401 Unauthorized):**
```json
{
    "success": false,
    "message": "Invalid credentials"
}
```

## POST /auth/logout

Logout current user.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Logout successful"
}
```

## GET /auth/me

Get current user information.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "username": "kasir1",
        "full_name": "John Doe",
        "email": "john@example.com",
        "role": {
            "id": 3,
            "name": "Kasir",
            "slug": "kasir"
        },
        "permissions": ["create_sales", "view_reports"]
    }
}
```

---

# SALES ENDPOINTS

## GET /sales

Get list of sales with pagination and filtering.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` - Page number
- `per_page` - Items per page
- `customer_id` - Filter by customer
- `status` - Filter by status (draft, completed, voided, returned)
- `payment_status` - Filter by payment status (paid, partial, unpaid)
- `from_date` - Filter by sale date from (YYYY-MM-DD)
- `to_date` - Filter by sale date to (YYYY-MM-DD)
- `search` - Search by invoice number or customer name

**Response (200 OK):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "invoice_no": "INV20240101001",
            "customer": {
                "id": 5,
                "name": "PT. Konstruksi Jaya"
            },
            "sale_date": "2024-01-01",
            "subtotal": 15000000,
            "discount": 500000,
            "tax": 1450000,
            "total": 15950000,
            "payment_method": "credit",
            "payment_status": "partial",
            "status": "completed",
            "created_at": "2024-01-01T10:30:00Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 100,
        "last_page": 7
    }
}
```

## GET /sales/{id}

Get single sale details.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "invoice_no": "INV20240101001",
        "customer": {
            "id": 5,
            "name": "PT. Konstruksi Jaya",
            "address": "Jl. Sudirman No. 123",
            "phone": "08123456789"
        },
        "sale_date": "2024-01-01",
        "subtotal": 15000000,
        "discount": 500000,
        "tax": 1450000,
        "total": 15950000,
        "payment_method": "credit",
        "payment_status": "partial",
        "status": "completed",
        "notes": "Pembayaran termin 1",
        "items": [
            {
                "id": 1,
                "product": {
                    "id": 10,
                    "code": "SEM001",
                    "name": "Semen Gresik 50kg"
                },
                "quantity": 100,
                "unit": "sak",
                "unit_price": 75000,
                "discount": 0,
                "subtotal": 7500000
            },
            {
                "id": 2,
                "product": {
                    "id": 15,
                    "code": "BES001",
                    "name": "Besi Beton 10mm"
                },
                "quantity": 500,
                "unit": "batang",
                "unit_price": 15000,
                "discount": 0,
                "subtotal": 7500000
            }
        ],
        "payments": [
            {
                "id": 1,
                "amount": 5000000,
                "payment_method": "transfer",
                "payment_date": "2024-01-01",
                "notes": "Transfer BCA"
            }
        ],
        "created_by": {
            "id": 1,
            "username": "kasir1",
            "full_name": "John Doe"
        },
        "created_at": "2024-01-01T10:30:00Z",
        "updated_at": "2024-01-01T10:35:00Z"
    }
}
```

## POST /sales

Create new sale transaction.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "customer_id": 5,
    "sale_date": "2024-01-01",
    "items": [
        {
            "product_id": 10,
            "quantity": 100,
            "unit_id": 25,
            "unit_price": 75000,
            "discount": 0
        },
        {
            "product_id": 15,
            "quantity": 500,
            "unit_id": 30,
            "unit_price": 15000,
            "discount": 0
        }
    ],
    "discount": 500000,
    "tax": 1450000,
    "payment_method": "credit",
    "notes": "Pembayaran termin 1"
}
```

**Response (201 Created):**
```json
{
    "success": true,
    "message": "Sale created successfully",
    "data": {
        "id": 1,
        "invoice_no": "INV20240101001",
        "customer": {
            "id": 5,
            "name": "PT. Konstruksi Jaya"
        },
        "sale_date": "2024-01-01",
        "subtotal": 15000000,
        "discount": 500000,
        "tax": 1450000,
        "total": 15950000,
        "payment_method": "credit",
        "payment_status": "unpaid",
        "status": "completed",
        "items": [...],
        "created_at": "2024-01-01T10:30:00Z"
    }
}
```

## PUT /sales/{id}

Update sale (only draft status can be updated).

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "customer_id": 5,
    "sale_date": "2024-01-01",
    "items": [...],
    "discount": 500000,
    "payment_method": "credit"
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Sale updated successfully",
    "data": { ... }
}
```

## DELETE /sales/{id}

Void sale transaction (requires approval if amount > threshold).

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "reason": "Salah input customer",
    "approved_by": 2
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Sale voided successfully"
}
```

## POST /sales/{id}/payment

Record payment for sale.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "amount": 5000000,
    "payment_method": "transfer",
    "payment_date": "2024-01-01",
    "notes": "Transfer BCA"
}
```

**Response (201 Created):**
```json
{
    "success": true,
    "message": "Payment recorded successfully",
    "data": {
        "id": 1,
        "amount": 5000000,
        "payment_method": "transfer",
        "payment_date": "2024-01-01"
    }
}
```

---

# PRODUCTS ENDPOINTS

## GET /products

Get list of products with pagination and filtering.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` - Page number
- `per_page` - Items per page
- `category_id` - Filter by category
- `search` - Search by code, name, alias, or barcode
- `is_active` - Filter by active status (0/1)
- `low_stock` - Only show low stock products (1)

**Response (200 OK):**
```json
{
    "success": true,
    "data": [
        {
            "id": 10,
            "code": "SEM001",
            "name": "Semen Gresik 50kg",
            "alias": ["Semen Putih", "Semen Abu-abu"],
            "category": {
                "id": 3,
                "name": "Semen"
            },
            "brand": "Gresik",
            "min_stock": 50,
            "max_stock": 500,
            "location": "A-1-1",
            "buy_price": 65000,
            "sell_price": 75000,
            "current_stock": 150,
            "base_unit": "sak",
            "is_active": true
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 250,
        "last_page": 17
    }
}
```

## GET /products/{id}

Get single product details.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "id": 10,
        "code": "SEM001",
        "name": "Semen Gresik 50kg",
        "alias": ["Semen Putih", "Semen Abu-abu"],
        "category": {
            "id": 3,
            "name": "Semen",
            "parent": {
                "id": 2,
                "name": "Bahan Bangunan"
            }
        },
        "brand": "Gresik",
        "min_stock": 50,
        "max_stock": 500,
        "location": "A-1-1",
        "buy_price": 65000,
        "sell_price": 75000,
        "current_stock": 150,
        "units": [
            {
                "id": 25,
                "unit_name": "sak",
                "conversion_factor": 1,
                "is_base_unit": true,
                "price_per_unit": 75000
            },
            {
                "id": 26,
                "unit_name": "ton",
                "conversion_factor": 20,
                "is_base_unit": false,
                "price_per_unit": 1500000
            }
        ],
        "barcodes": [
            {
                "id": 45,
                "barcode": "8991234567890",
                "unit": "sak",
                "is_primary": true
            }
        ],
        "is_active": true,
        "created_at": "2024-01-01T08:00:00Z",
        "updated_at": "2024-01-15T10:30:00Z"
    }
}
```

## POST /products

Create new product.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "code": "SEM001",
    "name": "Semen Gresik 50kg",
    "alias": ["Semen Putih", "Semen Abu-abu"],
    "category_id": 3,
    "brand": "Gresik",
    "min_stock": 50,
    "max_stock": 500,
    "location": "A-1-1",
    "buy_price": 65000,
    "sell_price": 75000,
    "units": [
        {
            "unit_name": "sak",
            "conversion_factor": 1,
            "is_base_unit": true,
            "price_per_unit": 75000
        },
        {
            "unit_name": "ton",
            "conversion_factor": 20,
            "is_base_unit": false,
            "price_per_unit": 1500000
        }
    ],
    "barcodes": [
        {
            "barcode": "8991234567890",
            "unit_name": "sak",
            "is_primary": true
        }
    ]
}
```

**Response (201 Created):**
```json
{
    "success": true,
    "message": "Product created successfully",
    "data": {
        "id": 10,
        "code": "SEM001",
        "name": "Semen Gresik 50kg",
        ...
    }
}
```

## PUT /products/{id}

Update product.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "name": "Semen Gresik 50kg Updated",
    "sell_price": 80000,
    "min_stock": 100
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Product updated successfully",
    "data": { ... }
}
```

## DELETE /products/{id}

Delete product (soft delete).

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Product deleted successfully"
}
```

## GET /products/search

Search products by barcode or keyword (instant search for POS).

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `q` - Search query (barcode, code, name, alias)
- `limit` - Max results (default: 10, max: 50)

**Response (200 OK):**
```json
{
    "success": true,
    "data": [
        {
            "id": 10,
            "code": "SEM001",
            "name": "Semen Gresik 50kg",
            "barcode": "8991234567890",
            "current_stock": 150,
            "sell_price": 75000,
            "base_unit": "sak"
        }
    ]
}
```

---

# CUSTOMERS ENDPOINTS

## GET /customers

Get list of customers with pagination and filtering.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` - Page number
- `per_page` - Items per page
- `group_id` - Filter by customer group
- `search` - Search by name or phone
- `is_active` - Filter by active status (0/1)

**Response (200 OK):**
```json
{
    "success": true,
    "data": [
        {
            "id": 5,
            "name": "PT. Konstruksi Jaya",
            "address": "Jl. Sudirman No. 123",
            "phone": "08123456789",
            "email": "info@konstruksijaya.com",
            "group": {
                "id": 3,
                "name": "Kontraktor",
                "discount_pct": 10
            },
            "credit_limit": 20000000,
            "payment_terms": 30,
            "credit_score": "A",
            "outstanding_balance": 10950000,
            "is_active": true
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 50,
        "last_page": 4
    }
}
```

## GET /customers/{id}

Get single customer details.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "id": 5,
        "name": "PT. Konstruksi Jaya",
        "address": "Jl. Sudirman No. 123",
        "phone": "08123456789",
        "email": "info@konstruksijaya.com",
        "group": {
            "id": 3,
            "name": "Kontraktor",
            "discount_pct": 10,
            "credit_limit": 20000000
        },
        "credit_limit": 20000000,
        "payment_terms": 30,
        "credit_score": "A",
        "outstanding_balance": 10950000,
        "purchase_history": [...],
        "is_active": true,
        "created_at": "2023-06-01T08:00:00Z",
        "updated_at": "2024-01-15T10:30:00Z"
    }
}
```

## POST /customers

Create new customer.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "name": "PT. Konstruksi Jaya",
    "address": "Jl. Sudirman No. 123",
    "phone": "08123456789",
    "email": "info@konstruksijaya.com",
    "group_id": 3,
    "credit_limit": 20000000,
    "payment_terms": 30
}
```

**Response (201 Created):**
```json
{
    "success": true,
    "message": "Customer created successfully",
    "data": {
        "id": 5,
        "name": "PT. Konstruksi Jaya",
        ...
    }
}
```

## PUT /customers/{id}

Update customer.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "name": "PT. Konstruksi Jaya Tbk",
    "credit_limit": 25000000
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Customer updated successfully",
    "data": { ... }
}
```

## DELETE /customers/{id}

Delete customer (soft delete).

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Customer deleted successfully"
}
```

---

# INVENTORY ENDPOINTS

## GET /stock

Get current stock for all products.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` - Page number
- `per_page` - Items per page
- `category_id` - Filter by category
- `low_stock` - Only show low stock (1)
- `overstock` - Only show overstock (1)
- `search` - Search by product name or code

**Response (200 OK):**
```json
{
    "success": true,
    "data": [
        {
            "product_id": 10,
            "product_code": "SEM001",
            "product_name": "Semen Gresik 50kg",
            "current_stock": 150,
            "base_unit": "sak",
            "min_stock": 50,
            "max_stock": 500,
            "status": "normal"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 250,
        "last_page": 17
    }
}
```

## GET /stock/{product_id}

Get stock history for specific product.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `from_date` - Filter from date
- `to_date` - Filter to date
- `movement_type` - Filter by movement type

**Response (200 OK):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1250,
            "quantity": 100,
            "unit": "sak",
            "movement_type": "purchase",
            "reference": "PO20240101001",
            "notes": "Pembelian dari supplier",
            "created_by": "gudang1",
            "created_at": "2024-01-01T08:00:00Z"
        },
        {
            "id": 1255,
            "quantity": -50,
            "unit": "sak",
            "movement_type": "sale",
            "reference": "INV20240101001",
            "notes": "Penjualan ke customer",
            "created_by": "kasir1",
            "created_at": "2024-01-01T10:30:00Z"
        }
    ]
}
```

## POST /stock/adjustments

Create stock adjustment.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "product_id": 10,
    "quantity": -5,
    "unit_id": 25,
    "adjustment_type": "damage",
    "reason": "5 sak semen pecah saat bongkar muat"
}
```

**Response (201 Created):**
```json
{
    "success": true,
    "message": "Stock adjustment created successfully",
    "data": {
        "id": 500,
        "product_id": 10,
        "quantity": -5,
        "unit": "sak",
        "adjustment_type": "damage",
        "reason": "5 sak semen pecah saat bongkar muat",
        "status": "pending_approval",
        "created_at": "2024-01-15T14:00:00Z"
    }
}
```

## POST /stock/adjustments/{id}/approve

Approve stock adjustment.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Stock adjustment approved",
    "data": {
        "id": 500,
        "status": "approved",
        "approved_by": "manager1",
        "approved_at": "2024-01-15T14:30:00Z"
    }
}
```

## POST /stock/opnames

Create stock opname.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "opname_date": "2024-01-15",
    "notes": "Stock opname bulanan",
    "items": [
        {
            "product_id": 10,
            "physical_qty": 145
        },
        {
            "product_id": 15,
            "physical_qty": 480
        }
    ]
}
```

**Response (201 Created):**
```json
{
    "success": true,
    "message": "Stock opname created successfully",
    "data": {
        "id": 10,
        "opname_date": "2024-01-15",
        "notes": "Stock opname bulanan",
        "status": "pending_approval",
        "items": [
            {
                "product_id": 10,
                "system_qty": 150,
                "physical_qty": 145,
                "difference": -5
            }
        ],
        "created_at": "2024-01-15T16:00:00Z"
    }
}
```

## POST /stock/opnames/{id}/approve

Approve stock opname and create adjustments automatically.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Stock opname approved and adjustments created",
    "data": {
        "id": 10,
        "status": "approved",
        "approved_by": "manager1",
        "approved_at": "2024-01-15T16:30:00Z",
        "adjustments_created": 2
    }
}
```

---

# REPORTS ENDPOINTS

## GET /reports/sales/daily

Get daily sales report.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `date` - Report date (default: today)

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "date": "2024-01-15",
        "total_sales": 15,
        "total_revenue": 125500000,
        "total_cash": 45000000,
        "total_credit": 80500000,
        "total_discount": 5000000,
        "items": [
            {
                "product_id": 10,
                "product_name": "Semen Gresik 50kg",
                "quantity_sold": 200,
                "revenue": 15000000
            }
        ]
    }
}
```

## GET /reports/sales/monthly

Get monthly sales report.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `year` - Year (default: current year)
- `month` - Month (default: current month)

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "year": 2024,
        "month": 1,
        "total_sales": 450,
        "total_revenue": 3450000000,
        "total_cash": 1250000000,
        "total_credit": 2200000000,
        "daily_breakdown": [...]
    }
}
```

## GET /reports/inventory/low-stock

Get low stock report.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": [
        {
            "product_id": 10,
            "product_code": "SEM001",
            "product_name": "Semen Gresik 50kg",
            "current_stock": 30,
            "min_stock": 50,
            "shortage": 20,
            "unit": "sak"
        }
    ]
}
```

## GET /reports/accounts/receivable/aging

Get accounts receivable aging report.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "total_outstanding": 109500000,
        "aging": {
            "0_30_days": 50000000,
            "31_60_days": 35000000,
            "61_90_days": 15000000,
            "over_90_days": 9500000
        },
        "details": [
            {
                "customer_id": 5,
                "customer_name": "PT. Konstruksi Jaya",
                "outstanding": 10950000,
                "days_overdue": 15
            }
        ]
    }
}
```

---

# SUPPLIERS ENDPOINTS

## GET /suppliers

Get list of suppliers.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` - Page number
- `per_page` - Items per page
- `search` - Search by name

**Response (200 OK):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "PT. Semen Indonesia",
            "address": "Jl. Industri No. 45",
            "phone": "02112345678",
            "email": "sales@semenindonesia.com",
            "payment_terms": 30,
            "credit_limit": 100000000,
            "outstanding_balance": 25000000,
            "is_active": true
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 20,
        "last_page": 2
    }
}
```

## POST /suppliers

Create new supplier.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "name": "PT. Semen Indonesia",
    "address": "Jl. Industri No. 45",
    "phone": "02112345678",
    "email": "sales@semenindonesia.com",
    "payment_terms": 30,
    "credit_limit": 100000000
}
```

**Response (201 Created):**
```json
{
    "success": true,
    "message": "Supplier created successfully",
    "data": { ... }
}
```

---

# PURCHASE ORDERS ENDPOINTS

## GET /purchase-orders

Get list of purchase orders.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` - Page number
- `per_page` - Items per page
- `supplier_id` - Filter by supplier
- `status` - Filter by status (draft, ordered, received, cancelled)

**Response (200 OK):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "po_number": "PO20240101001",
            "supplier": {
                "id": 1,
                "name": "PT. Semen Indonesia"
            },
            "po_date": "2024-01-01",
            "total": 15000000,
            "payment_status": "unpaid",
            "status": "received",
            "created_at": "2024-01-01T08:00:00Z"
        }
    ],
    "meta": { ... }
}
```

## POST /purchase-orders

Create new purchase order.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "supplier_id": 1,
    "po_date": "2024-01-01",
    "items": [
        {
            "product_id": 10,
            "quantity": 200,
            "unit_id": 25,
            "unit_price": 65000
        }
    ]
}
```

**Response (201 Created):**
```json
{
    "success": true,
    "message": "Purchase order created successfully",
    "data": {
        "id": 1,
        "po_number": "PO20240101001",
        ...
    }
}
```

## POST /purchase-orders/{id}/receive

Receive purchase order and update stock.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "notes": "Barang diterima dalam kondisi baik"
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Purchase order received successfully",
    "data": {
        "id": 1,
        "status": "received",
        "stock_updated": true
    }
}
```

---

# CATEGORIES ENDPOINTS

## GET /categories

Get list of categories (tree structure).

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Bahan Bangunan",
            "level": 1,
            "children": [
                {
                    "id": 2,
                    "name": "Semen",
                    "level": 2,
                    "children": []
                },
                {
                    "id": 3,
                    "name": "Besi",
                    "level": 2,
                    "children": []
                }
            ]
        }
    ]
}
```

## POST /categories

Create new category.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "name": "Cat",
    "parent_id": 1
}
```

**Response (201 Created):**
```json
{
    "success": true,
    "message": "Category created successfully",
    "data": { ... }
}
```

---

# CUSTOMER GROUPS ENDPOINTS

## GET /customer-groups

Get list of customer groups.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Retail",
            "discount_pct": 0,
            "credit_limit": 1000000,
            "is_active": true
        },
        {
            "id": 3,
            "name": "Kontraktor",
            "discount_pct": 10,
            "credit_limit": 20000000,
            "is_active": true
        }
    ]
}
```

---

# ERROR RESPONSE EXAMPLES

## 401 Unauthorized
```json
{
    "success": false,
    "message": "Unauthenticated. Please login."
}
```

## 403 Forbidden
```json
{
    "success": false,
    "message": "You do not have permission to perform this action."
}
```

## 404 Not Found
```json
{
    "success": false,
    "message": "Resource not found."
}
```

## 422 Validation Error
```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "customer_id": ["The customer field is required."],
        "items": ["The items field is required."],
        "items.0.product_id": ["The product is required."]
    }
}
```

## 500 Internal Server Error
```json
{
    "success": false,
    "message": "Internal server error. Please try again later."
}
```

---

# RATE LIMITING

- **Authenticated users**: 1000 requests per hour
- **Unauthenticated**: 100 requests per hour

Rate limit headers included in response:
```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 950
X-RateLimit-Reset: 1642234567
```

---

# VERSIONING

API versioning via URL path:
- Current version: `/api/v1`
- Future versions: `/api/v2`, `/api/v3`

Previous versions will be maintained for backward compatibility for at least 6 months.

---

# TESTING

Use Postman collection or cURL for testing:

```bash
# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"kasir1","password":"password123"}'

# Get products
curl -X GET http://localhost:8000/api/v1/products \
  -H "Authorization: Bearer {token}"

# Create sale
curl -X POST http://localhost:8000/api/v1/sales \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{...}'
```
