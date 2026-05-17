# LARAVEL LEARNING GUIDE

# PANGLONG ERP - Framework Fundamentals

## Version: 1.0
## Target Audience: Developers transitioning to Laravel
## Prerequisites: Basic PHP knowledge

---

# TABLE OF CONTENTS

1. [Introduction to Laravel](#introduction-to-laravel)
2. [Installation & Setup](#installation--setup)
3. [Project Structure](#project-structure)
4. [Routing](#routing)
5. [Controllers](#controllers)
6. [Models & Eloquent ORM](#models--eloquent-orm)
7. [Migrations](#migrations)
8. [Views & Blade](#views--blade)
9. [Requests & Validation](#requests--validation)
10. [Service Layer Pattern](#service-layer-pattern)
11. [Repository Pattern](#repository-pattern)
12. [Authentication](#authentication)
13. [Middleware](#middleware)
14. [API Resources](#api-resources)
15. [Testing](#testing)
16. [Best Practices](#best-practices)

---

# INTRODUCTION TO LARAVEL

## What is Laravel?

Laravel is a PHP web application framework with expressive, elegant syntax. It provides:

- **Elegant Syntax**: Clean, readable code
- **Powerful Tools**: Built-in features for common tasks
- **MVC Architecture**: Separation of concerns
- **Eloquent ORM**: Database abstraction
- **Artisan CLI**: Command-line tools
- **Ecosystem**: Rich package ecosystem

## Why Laravel for Panglong ERP?

1. **Rapid Development**: Built-in features save time
2. **Security**: CSRF protection, SQL injection prevention, authentication
3. **Scalability**: Designed for growth
4. **Community**: Large community, extensive documentation
5. **Testing**: Built-in testing support
6. **Maintenance**: Clean code, easy to maintain

## Laravel vs PHP Native

| Feature | PHP Native | Laravel |
|---------|-----------|---------|
| Routing | Manual | Built-in |
| Database | Manual SQL | Eloquent ORM |
| Security | Manual | Built-in |
| Validation | Manual | Built-in |
| Authentication | Manual | Built-in |
| Testing | Manual | PHPUnit integrated |
| CLI | None | Artisan |

---

# INSTALLATION & SETUP

## System Requirements

- PHP >= 8.1
- Composer (PHP package manager)
- MySQL/MariaDB >= 5.7
- Web Server (Apache/Nginx) or PHP built-in server

## Installation Steps

### 1. Install Composer
```bash
# Linux/Mac
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Windows
# Download from https://getcomposer.org/
```

### 2. Create New Laravel Project
```bash
# Using Composer
composer create-project laravel/laravel panglong

# Or using Laravel Installer (faster)
composer global require laravel/installer
laravel new panglong
```

### 3. Configure Environment
```bash
cd panglong
cp .env.example .env
php artisan key:generate
```

### 4. Configure Database
Edit `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=panglong
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 5. Run Development Server
```bash
php artisan serve
# Application available at http://localhost:8000
```

## Artisan CLI

Artisan is Laravel's command-line interface:

```bash
# List all commands
php artisan list

# Create controller
php artisan make:controller ProductController

# Create model
php artisan make:model Product

# Create migration
php artisan make:migration create_products_table

# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Clear cache
php artisan cache:clear

# View routes
php artisan route:list
```

---

# PROJECT STRUCTURE

## Directory Overview

```
panglong/
├── app/              # Application core code
├── bootstrap/        # Framework bootstrapping
├── config/           # Configuration files
├── database/         # Database files
├── public/           # Publicly accessible files
├── resources/        # Views, assets, language files
├── routes/           # Route definitions
├── storage/          # Logs, cache, uploads
├── tests/            # Test files
├── vendor/           # Composer dependencies
├── .env              # Environment configuration
├── artisan           # Artisan CLI
└── composer.json     # Composer dependencies
```

## Key Directories Explained

### app/
Contains application logic:
- `Controllers/` - Handle HTTP requests
- `Models/` - Eloquent models
- `Services/` - Business logic
- `Repositories/` - Data access layer

### config/
Configuration files for:
- Database
- Mail
- Filesystems
- Session
- Cache

### database/
- `migrations/` - Database schema changes
- `seeders/` - Sample data
- `factories/` - Test data generation

### routes/
- `web.php` - Web routes (with session)
- `api.php` - API routes (stateless)
- `console.php` - CLI routes

### resources/
- `views/` - Blade templates
- `assets/` - Frontend assets (CSS, JS)

---

# ROUTING

## Basic Routing

Routes are defined in `routes/web.php` or `routes/api.php`:

```php
// routes/web.php
use Illuminate\Support\Facades\Route;

// Basic route
Route::get('/', function () {
    return 'Hello World';
});

// Route with parameter
Route::get('/user/{id}', function ($id) {
    return 'User ' . $id;
});

// Route with optional parameter
Route::get('/user/{name?}', function ($name = null) {
    return $name;
});

// Route with constraint
Route::get('/user/{id}', function ($id) {
    return 'User ' . $id;
})->where('id', '[0-9]+');
```

## Controller Routes

```php
// Single action
Route::get('/products', [ProductController::class, 'index']);
Route::post('/products', [ProductController::class, 'store']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::put('/products/{id}', [ProductController::class, 'update']);
Route::delete('/products/{id}', [ProductController::class, 'destroy']);

// Resource route (creates all CRUD routes)
Route::apiResource('products', ProductController::class);

// This creates:
// GET    /products          -> index
// POST   /products          -> store
// GET    /products/{id}     -> show
// PUT    /products/{id}     -> update
// DELETE /products/{id}     -> destroy
```

## Route Groups

```php
// Group with middleware
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/profile', [ProfileController::class, 'index']);
});

// Group with prefix
Route::prefix('admin')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/products', [AdminProductController::class, 'index']);
});

// API versioning
Route::prefix('v1')->group(function () {
    Route::apiResource('products', ProductController::class);
    Route::apiResource('sales', SalesController::class);
});
```

## Named Routes

```php
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{id}', [ProductController::class, 'show'])->name('products.show');

// Usage in controller
return redirect()->route('products.index');

// Usage in Blade
<a href="{{ route('products.show', $product->id) }}">View Product</a>
```

---

# CONTROLLERS

## Creating Controllers

```bash
php artisan make:controller ProductController
php artisan make:controller Api/ProductController --api  # Creates CRUD methods
```

## Basic Controller

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::all();
        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Store a newly created resource.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0'
        ]);

        $product = Product::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $product = Product::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }

    /**
     * Update the specified resource.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0'
        ]);

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product
        ]);
    }

    /**
     * Remove the specified resource.
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }
}
```

## Controller Best Practices

1. **Keep controllers thin** - Move business logic to services
2. **Use dependency injection** - Inject dependencies in constructor
3. **Validate input** - Use Form Requests for complex validation
4. **Return consistent responses** - Use API Resources
5. **Handle exceptions** - Use try-catch or exception handlers

---

# MODELS & ELOQUENT ORM

## Creating Models

```bash
php artisan make:model Product
php artisan make:model Product -m  # With migration
php artisan make:model Product -a  # With migration, factory, seeder
```

## Basic Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    // Table name (optional, Laravel uses class name lowercase + s)
    protected $table = 'products';
    
    // Primary key (optional, default is 'id')
    protected $primaryKey = 'id';
    
    // Disable auto-increment (if using custom IDs)
    public $incrementing = true;
    
    // Primary key type (default is integer)
    protected $keyType = 'int';
    
    // Timestamps (created_at, updated_at)
    public $timestamps = true;
    
    // Custom timestamp column names
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    
    // Mass assignable attributes
    protected $fillable = [
        'code',
        'name',
        'price',
        'category_id'
    ];
    
    // Hidden attributes (not shown in JSON)
    protected $hidden = [
        'password',
        'remember_token'
    ];
    
    // Cast attributes to types
    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime'
    ];
    
    // Default values
    protected $attributes = [
        'is_active' => true
    ];
    
    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }
    
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_id', 'id');
    }
    
    // Scopes (query modifiers)
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function scopeLowStock($query)
    {
        return $query->whereColumn('current_stock', '<', 'min_stock');
    }
    
    // Accessors (getters)
    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }
    
    // Mutators (setters)
    public function setCodeAttribute($value)
    {
        $this->attributes['code'] = strtoupper($value);
    }
}
```

## Eloquent Queries

```php
use App\Models\Product;

// Get all records
$products = Product::all();

// Find by ID
$product = Product::find(1);
$product = Product::findOrFail(1); // Throws 404 if not found

// Where conditions
$products = Product::where('is_active', true)->get();
$products = Product::where('price', '>', 10000)->get();
$products = Product::where('name', 'like', '%semen%')->get();

// Multiple where
$products = Product::where('is_active', true)
                   ->where('price', '>', 10000)
                   ->get();

// Or where
$products = Product::where('category_id', 1)
                   ->orWhere('category_id', 2)
                   ->get();

// Where in
$products = Product::whereIn('category_id', [1, 2, 3])->get();

// Order by
$products = Product::orderBy('name', 'asc')->get();
$products = Product::latest()->get(); // Order by created_at desc

// Limit
$products = Product::take(10)->get();

// Pagination
$products = Product::paginate(15);
// Access with $products->links()

// With relationships (eager loading)
$products = Product::with('category')->get();

// Count
$count = Product::where('is_active', true)->count();

// Exists
$exists = Product::where('code', 'SEM001')->exists();

// First or create
$product = Product::firstOrCreate(
    ['code' => 'SEM001'],
    ['name' => 'Semen', 'price' => 10000]
);

// Update or create
$product = Product::updateOrCreate(
    ['code' => 'SEM001'],
    ['name' => 'Semen Updated', 'price' => 15000]
);
```

## Relationships

### One-to-One
```php
// User has one profile
class User extends Model
{
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }
}

// Profile belongs to user
class Profile extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### One-to-Many
```php
// Category has many products
class Category extends Model
{
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}

// Product belongs to category
class Product extends Model
{
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
```

### Many-to-Many
```php
// Product belongs to many orders
class Product extends Model
{
    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_items');
    }
}

// Order belongs to many products
class Order extends Model
{
    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_items');
    }
}
```

---

# MIGRATIONS

## Creating Migrations

```bash
php artisan make:migration create_products_table
php artisan make:migration add_price_to_products_table --table=products
```

## Migration Structure

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // String columns
            $table->string('code')->unique();
            $table->string('name');
            $table->string('brand')->nullable();
            
            // Text column
            $table->text('description')->nullable();
            
            // Integer columns
            $table->integer('min_stock')->default(0);
            $table->integer('max_stock')->default(0);
            
            // Decimal columns
            $table->decimal('buy_price', 15, 2)->default(0);
            $table->decimal('sell_price', 15, 2)->default(0);
            
            // Boolean column
            $table->boolean('is_active')->default(true);
            
            // Foreign key
            $table->foreignId('category_id')
                  ->constrained()
                  ->onDelete('cascade');
            
            // Timestamps
            $table->timestamps();
            
            // Soft deletes
            $table->softDeletes();
            
            // Indexes
            $table->index('code');
            $table->index('name');
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

## Running Migrations

```bash
# Run all pending migrations
php artisan migrate

# Run migrations for specific path
php artisan migrate --path=database/migrations/2024_01_01_000000_create_products_table.php

# Rollback last migration
php artisan migrate:rollback

# Rollback all migrations
php artisan migrate:reset

# Rollback and migrate again
php artisan migrate:refresh

# Drop all tables and migrate
php artisan migrate:fresh

# Show migration status
php artisan migrate:status
```

## Column Types

```php
$table->id();                    // Big integer auto-increment
$table->foreignId('user_id');    // Foreign key
$table->string('name');          // VARCHAR(255)
$table->string('email', 100);    // VARCHAR(100)
$table->text('description');     // TEXT
$table->integer('count');        // INTEGER
$table->bigInteger('count');    // BIGINT
$table->decimal('price', 10, 2); // DECIMAL(10,2)
$table->float('amount');         // FLOAT
$table->boolean('is_active');    // BOOLEAN
$table->date('birth_date');      // DATE
$table->dateTime('created_at');  // DATETIME
$table->timestamp('created_at'); // TIMESTAMP
$table->json('settings');        // JSON
$table->enum('status', ['active', 'inactive']); // ENUM
```

---

# VIEWS & BLADE

## Blade Templates

Blade is Laravel's templating engine:

```php
// resources/views/products/index.blade.php

@extends('layouts.app')

@section('title', 'Products')

@section('content')
<div class="container">
    <h1>Products</h1>
    
    @if($products->count() > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                <tr>
                    <td>{{ $product->code }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->formatted_price }}</td>
                    <td>
                        <a href="{{ route('products.show', $product->id) }}">View</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No products found.</p>
    @endif
    
    {{ $products->links() }}
</div>
@endsection
```

## Blade Directives

```php
// If/Else
@if($condition)
    <!-- content -->
@elseif($anotherCondition)
    <!-- content -->
@else
    <!-- content -->
@endif

// Loops
@foreach($items as $item)
    {{ $item->name }}
@endforeach

@forelse($items as $item)
    {{ $item->name }}
@empty
    No items
@endforelse

// While
@while($condition)
    <!-- content -->
@endwhile

// Variables
@php($variable = 'value')

// Include
@include('partials.header')

// Extending layouts
@extends('layouts.app')

// Sections
@section('content')
    <!-- content -->
@endsection

// Yielding sections
@yield('content')

// CSRF token
@csrf

// Method spoofing
@method('PUT')

// Auth
@auth
    <!-- authenticated user content -->
@endauth

@guest
    <!-- guest content -->
@endguest

// Loops with else
@foreach($users as $user)
    {{ $user->name }}
@empty
    No users
@endforeach
```

---

# REQUESTS & VALIDATION

## Form Request Validation

```bash
php artisan make:request StoreProductRequest
```

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Or implement authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50|unique:products',
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'buy_price' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0|gte:buy_price',
            'min_stock' => 'nullable|integer|min:0',
            'max_stock' => 'nullable|integer|min:0',
            'is_active' => 'boolean'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Product code is required',
            'code.unique' => 'This code already exists',
            'sell_price.gte' => 'Sell price must be greater than buy price'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'code' => 'product code',
            'buy_price' => 'purchase price'
        ];
    }
}
```

## Using Form Request in Controller

```php
public function store(StoreProductRequest $request)
{
    // Validation already done by Form Request
    $validated = $request->validated();
    
    $product = Product::create($validated);
    
    return response()->json([
        'success' => true,
        'data' => $product
    ], 201);
}
```

## Manual Validation

```php
use Illuminate\Support\Facades\Validator;

public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'price' => 'required|numeric|min:0'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }

    // Validation passed
    $validated = $validator->validated();
    // ...
}
```

---

# SERVICE LAYER PATTERN

## What is Service Layer?

Service layer contains business logic, keeping controllers thin and focused on HTTP concerns.

## Service Example

```php
<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Exception;

class ProductService
{
    /**
     * Calculate subtotal from items
     */
    public function calculateSubtotal(array $items): float
    {
        return collect($items)->sum(function ($item) {
            return ($item['quantity'] * $item['unit_price']) - $item['discount'];
        });
    }

    /**
     * Calculate total with tax
     */
    public function calculateTotal(float $subtotal, float $discount, float $taxRate = 0.11): float
    {
        $taxable = $subtotal - $discount;
        return $taxable + ($taxable * $taxRate);
    }

    /**
     * Check if product has sufficient stock
     */
    public function validateStockAvailability(int $productId, float $quantity, int $unitId): bool
    {
        $product = Product::findOrFail($productId);
        $currentStock = $this->getCurrentStock($productId);
        
        // Convert to base unit if needed
        $unit = $product->units()->findOrFail($unitId);
        $requiredQuantity = $quantity * $unit->conversion_factor;
        
        return $currentStock >= $requiredQuantity;
    }

    /**
     * Get current stock for product
     */
    public function getCurrentStock(int $productId): float
    {
        return StockMovement::where('product_id', $productId)
                           ->sum('quantity');
    }

    /**
     * Create product with stock
     */
    public function createProductWithStock(array $productData, float $initialStock): Product
    {
        return DB::transaction(function () use ($productData, $initialStock) {
            $product = Product::create($productData);
            
            // Create initial stock movement
            StockMovement::create([
                'product_id' => $product->id,
                'quantity' => $initialStock,
                'unit_id' => $product->base_unit_id,
                'movement_type' => 'purchase',
                'created_by' => auth()->id()
            ]);
            
            return $product;
        });
    }
}
```

## Using Service in Controller

```php
<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function store(Request $request)
    {
        $validated = $request->validated();
        
        // Use service for business logic
        $product = $this->productService->createProductWithStock(
            $validated,
            $request->initial_stock
        );
        
        return response()->json([
            'success' => true,
            'data' => $product
        ], 201);
    }
}
```

---

# REPOSITORY PATTERN

## What is Repository Pattern?

Repository pattern abstracts data access logic, making it easier to test and switch data sources.

## Repository Interface

```php
<?php

namespace App\Repositories\Contracts;

interface ProductRepositoryInterface
{
    public function all();
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function search($query);
    public function getByCategory($categoryId);
}
```

## Repository Implementation

```php
<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;

class ProductRepository implements ProductRepositoryInterface
{
    protected $model;

    public function __construct(Product $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->all();
    }

    public function find($id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $product = $this->find($id);
        $product->update($data);
        return $product;
    }

    public function delete($id)
    {
        $product = $this->find($id);
        return $product->delete();
    }

    public function search($query)
    {
        return $this->model->where('name', 'like', "%{$query}%")
                         ->orWhere('code', 'like', "%{$query}%")
                         ->get();
    }

    public function getByCategory($categoryId)
    {
        return $this->model->where('category_id', $categoryId)->get();
    }
}
```

## Service Provider Registration

```php
// app/Providers/AppServiceProvider.php

public function register(): void
{
    $this->app->bind(
        \App\Repositories\Contracts\ProductRepositoryInterface::class,
        \App\Repositories\Eloquent\ProductRepository::class
    );
}
```

---

# AUTHENTICATION

## Setup Authentication

```bash
# Install authentication scaffolding
composer require laravel/breeze --dev

# Install breeze
php artisan breeze:install blade

# Or for API
php artisan breeze:install api
```

## Manual Authentication

```php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

// Register
public function register(Request $request)
{
    $user = User::create([
        'username' => $request->username,
        'password' => Hash::make($request->password),
        'full_name' => $request->full_name
    ]);

    Auth::login($user);
    
    return redirect()->route('dashboard');
}

// Login
public function login(Request $request)
{
    $credentials = $request->only('username', 'password');
    
    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        return redirect()->intended('dashboard');
    }
    
    return back()->withErrors([
        'username' => 'Invalid credentials'
    ]);
}

// Logout
public function logout(Request $request)
{
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    
    return redirect('/');
}
```

## API Authentication (Sanctum)

```bash
composer require laravel/sanctum
php artisan sanctum:install
```

```php
// Create token
$user->createToken('app-name')->plainTextToken;

// Authenticate with token
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
```

---

# MIDDLEWARE

## Creating Middleware

```bash
php artisan make:middleware CheckPermission
```

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        if (!auth()->user()->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'Permission denied'
            ], 403);
        }

        return $next($request);
    }
}
```

## Register Middleware

```php
// app/Http/Kernel.php

protected $middlewareAliases = [
    'permission' => \App\Http\Middleware\CheckPermission::class,
];
```

## Using Middleware

```php
// In routes
Route::middleware(['auth', 'permission:create_products'])
     ->post('/products', [ProductController::class, 'store']);

// In controller constructor
public function __construct()
{
    $this->middleware('auth');
    $this->middleware('permission:view_products')->only('index', 'show');
    $this->middleware('permission:create_products')->only('store');
}
```

---

# API RESOURCES

## Creating Resources

```bash
php artisan make:resource ProductResource
```

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'buy_price' => $this->buy_price,
            'sell_price' => $this->sell_price,
            'formatted_price' => $this->formatted_price,
            'current_stock' => $this->current_stock,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
```

## Using Resources in Controller

```php
use App\Http\Resources\ProductResource;
use App\Models\Product;

public function index()
{
    $products = Product::with('category')->paginate(15);
    
    return ProductResource::collection($products);
}

public function show($id)
{
    $product = Product::with('category')->findOrFail($id);
    
    return new ProductResource($product);
}
```

## Resource Collections

```bash
php artisan make:resource ProductCollection
```

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'current_page' => $this->resource->currentPage(),
                'per_page' => $this->resource->perPage(),
                'total' => $this->resource->total(),
                'last_page' => $this->resource->lastPage()
            ]
        ];
    }
}
```

---

# TESTING

## Feature Test Example

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_products()
    {
        $user = User::factory()->create();
        Product::factory()->count(5)->create();

        $response = $this->actingAs($user, 'api')
                         ->getJson('/api/v1/products');

        $response->assertStatus(200)
                 ->assertJsonCount(5, 'data');
    }

    public function test_user_can_create_product()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
                         ->postJson('/api/v1/products', [
                             'code' => 'TEST001',
                             'name' => 'Test Product',
                             'price' => 10000
                         ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true
                 ]);

        $this->assertDatabaseHas('products', [
            'code' => 'TEST001'
        ]);
    }
}
```

## Running Tests

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test
./vendor/bin/phpunit tests/Feature/ProductApiTest.php

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage
```

---

# BEST PRACTICES

## 1. Follow PSR Standards
- PSR-4: Autoloading
- PSR-12: Coding style

## 2. Use Type Hints
```php
public function store(Request $request): JsonResponse
{
    // ...
}
```

## 3. Use Dependency Injection
```php
public function __construct(ProductService $service)
{
    $this->service = $service;
}
```

## 4. Keep Controllers Thin
Move business logic to services

## 5. Use Form Requests
For validation logic

## 6. Use API Resources
For consistent API responses

## 7. Use Migrations
Never modify database manually

## 8. Use Environment Variables
Never hardcode credentials

## 9. Write Tests
Test critical paths

## 10. Use Queues for Heavy Tasks
```bash
php artisan make:job SendInvoiceEmail
```

## 11. Use Events for Decoupling
```bash
php artisan make:event SaleCompleted
php artisan make:listener SendInvoiceNotification
```

## 12. Use Caching
```php
use Illuminate\Support\Facades\Cache;

$products = Cache::remember('products.all', 3600, function () {
    return Product::all();
});
```

## 13. Use Logging
```php
use Illuminate\Support\Facades\Log;

Log::info('Product created', ['product_id' => $product->id]);
Log::error('Failed to create product', ['error' => $e->getMessage()]);
```

## 14. Use Config Files
```php
// config/app.php
'invoice_prefix' => env('INVOICE_PREFIX', 'INV'),

// Usage
config('app.invoice_prefix')
```

## 15. Use Constants for Magic Values
```php
class MovementType
{
    const PURCHASE = 'purchase';
    const SALE = 'sale';
    const ADJUSTMENT = 'adjustment';
}
```

---

# LARAVEL ECOSYSTEM

## Popular Packages

- **Laravel Sanctum** - API authentication
- **Laravel Passport** - OAuth2 server
- **Laravel Telescope** - Debug assistant
- **Laravel Horizon** - Queue dashboard
- **Laravel Cashier** - Stripe integration
- **Spatie Permission** - Role/permission management
- **Laravel Excel** - Excel import/export
- **Laravel Debugbar** - Debug toolbar

## Learning Resources

- Official Documentation: https://laravel.com/docs
- Laracasts: https://laracasts.com
- Laravel News: https://laravel-news.com

---

# NEXT STEPS FOR PANGLONG ERP

1. **Study this guide** thoroughly
2. **Practice** with small examples
3. **Review** existing Laravel projects
4. **Start** with simple features
5. **Gradually** build complexity
6. **Reference** documentation often
7. **Ask** questions when stuck
8. **Contribute** to community

---

# SUMMARY

Laravel provides:
- ✅ Elegant syntax
- ✅ Built-in features
- ✅ Security
- ✅ Scalability
- ✅ Testing support
- ✅ Rich ecosystem

Key concepts to master:
- Routing & Controllers
- Models & Eloquent
- Migrations
- Service Layer
- Repository Pattern
- API Resources
- Testing

Happy coding! 🚀
