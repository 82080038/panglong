# TESTING FRAMEWORK

# PANGLONG ERP - PHASE 1 MVP

## Version: 1.0
## Framework: PHPUnit (built-in Laravel)

---

# PHPUNIT CONFIGURATION

## phpunit.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         stopOnFailure="false"
         failOnWarning="true"
         failOnRisky="true"
         beStrictAboutOutputDuringTests="true"
         beStrictAboutTodoAnnotatedTests="true">
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
    </testsuites>
    <coverage processUncoveredFiles="true">
        <include>
            <directory suffix=".php">./app</directory>
        </include>
        <exclude>
            <directory>./app/Console</directory>
            <directory>./app/Providers</directory>
            <file>./app/Helpers/helpers.php</file>
        </exclude>
        <report>
            <html outputDirectory="coverage/html"/>
            <clover outputFile="coverage/clover.xml"/>
        </report>
    </coverage>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
    </php>
</phpunit>
```

---

# TESTING STRATEGY

## Test Pyramid

```
        /\
       /E2E\       - 5% (End-to-End)
      /------\
     / Feature \   - 25% (Integration tests)
    /----------\
   /   Unit    \  - 70% (Unit tests)
  /--------------\
```

## Test Categories

### 1. Unit Tests
- Test individual components in isolation
- Test business logic in Services
- Test data access in Repositories
- Test helper functions
- Fast execution (< 1 second per test)

### 2. Feature Tests
- Test API endpoints
- Test database interactions
- Test user flows
- Test authentication/authorization
- Medium execution (1-5 seconds per test)

### 3. End-to-End Tests (Optional for MVP)
- Test complete user workflows
- Browser automation (Laravel Dusk)
- Slow execution (> 5 seconds per test)

---

# TEST STRUCTURE

## tests/Unit/

```
tests/Unit/
├── Services/
│   ├── SaleServiceTest.php
│   ├── StockServiceTest.php
│   ├── ProductServiceTest.php
│   └── PaymentServiceTest.php
├── Repositories/
│   ├── SaleRepositoryTest.php
│   └── ProductRepositoryTest.php
├── Helpers/
│   ├── NumberHelperTest.php
│   └── DateHelperTest.php
└── Models/
    ├── ProductTest.php
    └── CustomerTest.php
```

## tests/Feature/

```
tests/Feature/
├── Auth/
│   └── AuthTest.php
├── Sales/
│   ├── SalesApiTest.php
│   └── SalePaymentTest.php
├── Products/
│   └── ProductsApiTest.php
├── Customers/
│   └── CustomersApiTest.php
├── Inventory/
│   ├── StockMovementTest.php
│   └── StockAdjustmentTest.php
└── Reports/
    └── ReportsApiTest.php
```

---

# BASE TEST CLASSES

## TestCase.php (Feature Tests Base)

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup common test data
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    protected function actingAsUser($role = 'kasir')
    {
        $user = \App\Models\User::factory()->create([
            'role_id' => \App\Models\Role::where('slug', $role)->first()->id
        ]);

        return $this->actingAs($user, 'api');
    }
}
```

## UnitTestCase.php (Unit Tests Base)

```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }
}
```

---

# UNIT TEST EXAMPLES

## SaleServiceTest.php

```php
<?php

namespace Tests\Unit\Services;

use App\Services\SaleService;
use App\Models\Product;
use App\Models\Customer;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SaleServiceTest extends TestCase
{
    use RefreshDatabase;

    private SaleService $saleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->saleService = new SaleService();
    }

    public function test_calculate_subtotal()
    {
        $items = [
            [
                'quantity' => 10,
                'unit_price' => 10000,
                'discount' => 0
            ],
            [
                'quantity' => 5,
                'unit_price' => 5000,
                'discount' => 500
            ]
        ];

        $subtotal = $this->saleService->calculateSubtotal($items);

        $this->assertEquals(122500, $subtotal);
    }

    public function test_calculate_total_with_tax()
    {
        $subtotal = 100000;
        $discount = 5000;
        $taxRate = 0.11;

        $total = $this->saleService->calculateTotal($subtotal, $discount, $taxRate);

        $this->assertEquals(105500, $total);
    }

    public function test_validate_stock_availability()
    {
        $product = Product::factory()->create();
        
        // Add stock
        $product->stockMovements()->create([
            'quantity' => 100,
            'unit_id' => $product->base_unit_id,
            'movement_type' => 'purchase',
            'created_by' => 1
        ]);

        $result = $this->saleService->validateStockAvailability(
            $product->id,
            50,
            $product->base_unit_id
        );

        $this->assertTrue($result);
    }

    public function test_validate_insufficient_stock()
    {
        $product = Product::factory()->create();
        
        // Add only 10 units
        $product->stockMovements()->create([
            'quantity' => 10,
            'unit_id' => $product->base_unit_id,
            'movement_type' => 'purchase',
            'created_by' => 1
        ]);

        $result = $this->saleService->validateStockAvailability(
            $product->id,
            50,
            $product->base_unit_id
        );

        $this->assertFalse($result);
    }

    public function test_generate_invoice_number()
    {
        $invoiceNo = $this->saleService->generateInvoiceNumber('2024-01-01');

        $this->assertMatchesRegularExpression('/^INV20240101\d{4}$/', $invoiceNo);
    }
}
```

## StockServiceTest.php

```php
<?php

namespace Tests\Unit\Services;

use App\Services\StockService;
use App\Models\Product;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stockService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stockService = new StockService();
    }

    public function test_get_current_stock()
    {
        $product = Product::factory()->create();

        // Add stock movements
        $product->stockMovements()->createMany([
            [
                'quantity' => 100,
                'unit_id' => $product->base_unit_id,
                'movement_type' => 'purchase',
                'created_by' => 1
            ],
            [
                'quantity' => -30,
                'unit_id' => $product->base_unit_id,
                'movement_type' => 'sale',
                'created_by' => 1
            ]
        ]);

        $currentStock = $this->stockService->getCurrentStock($product->id);

        $this->assertEquals(70, $currentStock);
    }

    public function test_convert_unit_to_base()
    {
        $product = Product::factory()->create();
        
        // Add unit with conversion factor
        $product->units()->create([
            'unit_name' => 'batang',
            'conversion_factor' => 6,
            'is_base_unit' => false,
            'price_per_unit' => 90000
        ]);

        $baseQuantity = $this->stockService->convertToBaseUnit(
            $product->id,
            10,
            'batang'
        );

        $this->assertEquals(60, $baseQuantity);
    }

    public function test_check_low_stock()
    {
        $product = Product::factory()->create([
            'min_stock' => 50
        ]);

        $product->stockMovements()->create([
            'quantity' => 30,
            'unit_id' => $product->base_unit_id,
            'movement_type' => 'purchase',
            'created_by' => 1
        ]);

        $isLowStock = $this->stockService->isLowStock($product->id);

        $this->assertTrue($isLowStock);
    }

    public function test_check_normal_stock()
    {
        $product = Product::factory()->create([
            'min_stock' => 50
        ]);

        $product->stockMovements()->create([
            'quantity' => 100,
            'unit_id' => $product->base_unit_id,
            'movement_type' => 'purchase',
            'created_by' => 1
        ]);

        $isLowStock = $this->stockService->isLowStock($product->id);

        $this->assertFalse($isLowStock);
    }
}
```

## NumberHelperTest.php

```php
<?php

namespace Tests\Unit\Helpers;

use App\Helpers\NumberHelper;
use Tests\TestCase;

class NumberHelperTest extends TestCase
{
    public function test_format_currency_idr()
    {
        $formatted = NumberHelper::formatCurrency(15000000);

        $this->assertEquals('Rp 15.000.000', $formatted);
    }

    public function test_format_currency_zero()
    {
        $formatted = NumberHelper::formatCurrency(0);

        $this->assertEquals('Rp 0', $formatted);
    }

    public function test_format_percentage()
    {
        $formatted = NumberHelper::formatPercentage(0.15);

        $this->assertEquals('15%', $formatted);
    }

    public function test_round_to_thousands()
    {
        $rounded = NumberHelper::roundToThousands(15750);

        $this->assertEquals(16000, $rounded);
    }
}
```

---

# FEATURE TEST EXAMPLES

## AuthTest.php

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => $user->username,
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'token',
                         'user' => [
                             'id',
                             'username',
                             'role'
                         ]
                     ]
                 ]);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'testuser',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Invalid credentials'
                 ]);
    }

    public function test_authenticated_user_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
                         ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Logout successful'
                 ]);
    }
}
```

## SalesApiTest.php

```php
<?php

namespace Tests\Feature\Sales;

use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_sale()
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'sell_price' => 10000
        ]);
        
        // Add stock
        $product->stockMovements()->create([
            'quantity' => 100,
            'unit_id' => $product->base_unit_id,
            'movement_type' => 'purchase',
            'created_by' => 1
        ]);

        $response = $this->actingAs($user, 'api')
                         ->postJson('/api/v1/sales', [
                             'customer_id' => $customer->id,
                             'sale_date' => '2024-01-01',
                             'items' => [
                                 [
                                     'product_id' => $product->id,
                                     'quantity' => 5,
                                     'unit_id' => $product->base_unit_id,
                                     'unit_price' => 10000,
                                     'discount' => 0
                                 ]
                             ],
                             'discount' => 0,
                             'tax' => 5500,
                             'payment_method' => 'cash'
                         ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'invoice_no',
                         'customer',
                         'total',
                         'status'
                     ]
                 ]);

        $this->assertDatabaseHas('sales', [
            'customer_id' => $customer->id,
            'total' => 55500
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'quantity' => -5,
            'movement_type' => 'sale'
        ]);
    }

    public function test_sale_requires_authentication()
    {
        $response = $this->postJson('/api/v1/sales', [
            'customer_id' => 1,
            'items' => []
        ]);

        $response->assertStatus(401);
    }

    public function test_sale_validation_fails_with_invalid_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
                         ->postJson('/api/v1/sales', [
                             'customer_id' => 999, // Non-existent customer
                             'items' => []
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['customer_id', 'items']);
    }

    public function test_user_can_get_sales_list()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
                         ->getJson('/api/v1/sales');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data',
                     'meta'
                 ]);
    }

    public function test_user_can_get_single_sale()
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        
        $sale = \App\Models\Sale::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $user->id
        ]);

        $response = $this->actingAs($user, 'api')
                         ->getJson("/api/v1/sales/{$sale->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $sale->id,
                         'invoice_no' => $sale->invoice_no
                     ]
                 ]);
    }
}
```

## ProductsApiTest.php

```php
<?php

namespace Tests\Feature\Products;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_product()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user, 'api')
                         ->postJson('/api/v1/products', [
                             'code' => 'TEST001',
                             'name' => 'Test Product',
                             'category_id' => $category->id,
                             'buy_price' => 10000,
                             'sell_price' => 15000,
                             'min_stock' => 10,
                             'max_stock' => 100,
                             'units' => [
                                 [
                                     'unit_name' => 'pcs',
                                     'conversion_factor' => 1,
                                     'is_base_unit' => true,
                                     'price_per_unit' => 15000
                                 ]
                             ]
                         ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'code',
                         'name'
                     ]
                 ]);

        $this->assertDatabaseHas('products', [
            'code' => 'TEST001',
            'name' => 'Test Product'
        ]);
    }

    public function test_product_search_works()
    {
        $user = User::factory()->create();
        Product::factory()->create([
            'code' => 'SEM001',
            'name' => 'Semen Gresik'
        ]);

        $response = $this->actingAs($user, 'api')
                         ->getJson('/api/v1/products/search?q=Semen');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }

    public function test_low_stock_filter_works()
    {
        $user = User::factory()->create();
        
        $product = Product::factory()->create([
            'min_stock' => 50
        ]);
        
        $product->stockMovements()->create([
            'quantity' => 30,
            'unit_id' => $product->base_unit_id,
            'movement_type' => 'purchase',
            'created_by' => 1
        ]);

        $response = $this->actingAs($user, 'api')
                         ->getJson('/api/v1/products?low_stock=1');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }
}
```

## StockAdjustmentTest.php

```php
<?php

namespace Tests\Feature\Inventory;

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_stock_adjustment()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user, 'api')
                         ->postJson('/api/v1/stock/adjustments', [
                             'product_id' => $product->id,
                             'quantity' => -5,
                             'unit_id' => $product->base_unit_id,
                             'adjustment_type' => 'damage',
                             'reason' => 'Barang rusak'
                         ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Stock adjustment created successfully'
                 ]);

        $this->assertDatabaseHas('stock_adjustments', [
            'product_id' => $product->id,
            'quantity' => -5,
            'adjustment_type' => 'damage'
        ]);
    }

    public function test_manager_can_approve_adjustment()
    {
        $manager = User::factory()->create();
        $manager->role_id = \App\Models\Role::where('slug', 'manager')->first()->id;
        $manager->save();

        $product = Product::factory()->create();
        
        $adjustment = \App\Models\StockAdjustment::factory()->create([
            'product_id' => $product->id,
            'quantity' => -5,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($manager, 'api')
                         ->postJson("/api/v1/stock/adjustments/{$adjustment->id}/approve");

        $response->assertStatus(200);

        $this->assertDatabaseHas('stock_adjustments', [
            'id' => $adjustment->id,
            'status' => 'approved'
        ]);
    }
}
```

---

# DATA FACTORIES

## ProductFactory.php

```php
<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'code' => 'PRD' . $this->faker->unique()->numerify('#####'),
            'name' => $this->faker->words(3, true),
            'category_id' => Category::factory(),
            'brand' => $this->faker->company(),
            'min_stock' => $this->faker->numberBetween(10, 50),
            'max_stock' => $this->faker->numberBetween(100, 500),
            'buy_price' => $this->faker->numberBetween(10000, 100000),
            'sell_price' => $this->faker->numberBetween(15000, 150000),
            'is_active' => true,
        ];
    }
}
```

## CustomerFactory.php

```php
<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerGroup;
use Illuminate\Database\Eloquent\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company(),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->companyEmail(),
            'group_id' => CustomerGroup::factory(),
            'credit_limit' => $this->faker->numberBetween(1000000, 50000000),
            'payment_terms' => $this->faker->numberBetween(7, 60),
            'credit_score' => $this->faker->randomElement(['A', 'B', 'C', 'D']),
            'is_active' => true,
        ];
    }
}
```

## SaleFactory.php

```php
<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition()
    {
        $subtotal = $this->faker->numberBetween(100000, 10000000);
        $discount = $this->faker->numberBetween(0, 500000);
        
        return [
            'invoice_no' => 'INV' . date('Ymd') . $this->faker->unique()->numerify('####'),
            'customer_id' => Customer::factory(),
            'sale_date' => $this->faker->date(),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => ($subtotal - $discount) * 0.11,
            'total' => ($subtotal - $discount) * 1.11,
            'payment_method' => $this->faker->randomElement(['cash', 'credit', 'transfer']),
            'payment_status' => $this->faker->randomElement(['paid', 'partial', 'unpaid']),
            'status' => 'completed',
            'created_by' => User::factory(),
        ];
    }
}
```

---

# RUNNING TESTS

## Run All Tests
```bash
./vendor/bin/phpunit
```

## Run Unit Tests Only
```bash
./vendor/bin/phpunit --testsuite=Unit
```

## Run Feature Tests Only
```bash
./vendor/bin/phpunit --testsuite=Feature
```

## Run Specific Test File
```bash
./vendor/bin/phpunit tests/Feature/Sales/SalesApiTest.php
```

## Run Specific Test Method
```bash
./vendor/bin/phpunit --filter test_user_can_create_sale
```

## Run with Coverage
```bash
./vendor/bin/phpunit --coverage-html coverage
```

## Run in Parallel (using paratest)
```bash
./vendor/bin/paratest
```

---

# CONTINUOUS INTEGRATION

## GitHub Actions Example (.github/workflows/tests.yml)

```yaml
name: Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: mbstring, xml, mysql, pdo_mysql
        coverage: xdebug
    
    - name: Copy .env
      run: cp .env.example .env
    
    - name: Install Dependencies
      run: composer install -q --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist
    
    - name: Generate Key
      run: php artisan key:generate
    
    - name: Run Migrations
      run: php artisan migrate --force
    
    - name: Run Tests
      run: ./vendor/bin/phpunit --coverage-clover=coverage.xml
    
    - name: Upload Coverage
      uses: codecov/codecov-action@v2
      with:
        files: ./coverage.xml
```

---

# TEST COVERAGE TARGETS

## MVP Coverage Goals
- **Overall Coverage**: 70% minimum
- **Services**: 90% minimum
- **Repositories**: 80% minimum
- **Controllers**: 60% minimum
- **Models**: 75% minimum

## Critical Paths
- Sale creation flow: 100%
- Stock movement flow: 100%
- Payment recording: 100%
- Authentication: 100%

---

# BEST PRACTICES

## 1. Test Naming
- Use descriptive names: `test_user_can_create_sale_with_valid_data`
- Follow pattern: `test_[feature]_[expected_result]`

## 2. Test Isolation
- Each test should be independent
- Use RefreshDatabase trait for feature tests
- Don't rely on test execution order

## 3. Arrange-Act-Assert Pattern
```php
public function test_example()
{
    // Arrange
    $user = User::factory()->create();
    
    // Act
    $response = $this->actingAs($user)->get('/api/v1/sales');
    
    // Assert
    $response->assertStatus(200);
}
```

## 4. Use Factories
- Use factories for test data
- Don't hardcode test data
- Make tests maintainable

## 5. Mock External Services
- Mock API calls
- Mock file uploads
- Mock email sending

## 6. Test Edge Cases
- Empty inputs
- Null values
- Boundary conditions
- Invalid data types

## 7. Keep Tests Fast
- Unit tests should be < 1 second
- Feature tests should be < 5 seconds
- Avoid sleep() in tests

## 8. Test Documentation
- Add comments for complex test logic
- Document why, not what
- Keep test names descriptive

---

# MOCKING EXAMPLES

## Mocking External API

```php
public function test_sync_with_external_system()
{
    Http::fake([
        'external-api.com/*' => Http::response(['success' => true], 200)
    ]);

    $response = $this->postJson('/api/v1/sync');

    $response->assertStatus(200);
}
```

## Mocking File Upload

```php
public function test_product_image_upload()
{
    Storage::fake('public');
    
    $file = UploadedFile::fake()->image('product.jpg');
    
    $response = $this->postJson('/api/v1/products', [
        'image' => $file,
        ...
    ]);

    Storage::disk('public')->assertExists('products/' . $file->hashName());
}
```

## Mocking Email

```php
public function test_invoice_email_sent()
{
    Mail::fake();
    
    $this->postJson('/api/v1/sales', [...]);
    
    Mail::assertSent(InvoiceMail::class);
}
```

---

# TROUBLESHOOTING

## Common Issues

### 1. Tests failing due to database state
- Use RefreshDatabase trait
- Ensure proper cleanup in tearDown()

### 2. Authentication issues in tests
- Use actingAs() helper
- Ensure user has proper permissions

### 3. Time-related test failures
- Use Carbon::setTestNow() for fixed time
- Avoid time() or date() in tests

### 4. Random test failures
- Check for race conditions
- Ensure proper test isolation
- Use proper seeding

---

# TEST REPORTING

## Generate HTML Coverage Report
```bash
./vendor/bin/phpunit --coverage-html coverage/html
```

View at: `coverage/html/index.html`

## Generate Clover XML Report
```bash
./vendor/bin/phpunit --coverage-clover coverage/clover.xml
```

## Generate Text Summary
```bash
./vendor/bin/phpunit --testdox
```
