# Laravel Architecture

## Question 1: Explain Laravel's Service Container and Dependency Injection.

**Answer:**

The Service Container is Laravel's powerful tool for managing class dependencies and performing dependency injection.

### Basic Binding

```php
// app/Providers/AppServiceProvider.php
public function register() {
    // Simple binding
    $this->app->bind(TransactionInterface::class, StripeTransaction::class);

    // Singleton binding (single instance)
    $this->app->singleton(ApiClient::class, function ($app) {
        return new ApiClient(config('services.api.key'));
    });

    // Bind instance
    $this->app->instance('config', $configArray);

    // Contextual binding
    $this->app->when(PhotoController::class)
        ->needs(Filesystem::class)
        ->give(function () {
            return Storage::disk('local');
        });

    $this->app->when(VideoController::class)
        ->needs(Filesystem::class)
        ->give(function () {
            return Storage::disk('s3');
        });
}
```

### Automatic Resolution

```php
// Laravel automatically resolves dependencies
class UserController extends Controller {
    // Constructor injection - automatically resolved
    public function __construct(
        private UserRepository $users,
        private Mailer $mailer
    ) {}

    // Method injection
    public function store(Request $request, UserValidator $validator) {
        $validator->validate($request->all());
        // ...
    }
}

// Manual resolution
$userRepo = app(UserRepository::class);
// or
$userRepo = resolve(UserRepository::class);
```

### How Laravel's Container Works (Under the Hood)

Laravel's service container uses PHP's Reflection API to automatically resolve dependencies:

```
Request → Container → Reflection → Resolve Dependencies → Instantiate

1. Controller constructor type-hints UserRepository
2. Container receives request to resolve UserController
3. Reflection examines constructor parameters: [UserRepository $users]
4. Container checks if UserRepository is bound → resolves it
5. If not bound, Container builds UserRepository automatically
6. Creates instance with resolved dependencies
7. Returns fully constructed object
```

```php
<?php
// Simplified container implementation showing the reflection process
class Container
{
    public function make(string $abstract)
    {
        $reflector = new ReflectionClass($abstract);
        
        // Check if class is instantiable
        if (!$reflector->isInstantiable()) {
            throw new Exception("Target {$abstract} is not instantiable.");
        }
        
        // Get constructor
        $constructor = $reflector->getConstructor();
        
        // No constructor = simple instantiation
        if (is_null($constructor)) {
            return new $abstract;
        }
        
        // Get constructor parameters
        $parameters = $constructor->getParameters();
        
        // Resolve each dependency
        $dependencies = $this->resolveDependencies($parameters);
        
        // Create instance with resolved dependencies
        return $reflector->newInstanceArgs($dependencies);
    }
    
    private function resolveDependencies(array $parameters): array
    {
        $dependencies = [];
        
        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            
            // If parameter has a class type hint
            if ($type && !$type->isBuiltin()) {
                // Recursively resolve the dependency
                $dependencies[] = $this->make($type->getName());
            } 
            // If parameter has default value
            elseif ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            }
            // Cannot resolve - throw error
            else {
                throw new Exception(
                    "Cannot resolve parameter: {$parameter->getName()}"
                );
            }
        }
        
        return $dependencies;
    }
}
```

### Dependency Injection Types

```php
<?php
// 1. Constructor Injection (most common)
class UserController extends Controller {
    public function __construct(
        private UserService $userService  // Required dependency
    ) {}
}

// 2. Method Injection
class OrderController extends Controller {
    public function update(Request $request, Order $order) {
        // Request is automatically injected
        // Order model is automatically injected from route
    }
}

// 3. Property Injection (less common)
class ServiceContainer {
    #[Inject]
    protected LoggerInterface $logger;
}
```

### Contextual Binding

```php
<?php
// Different implementations for different contexts
$this->app->when(PhotoController::class)
    ->needs(Filesystem::class)
    ->give(function () {
        return Storage::disk('local');
    });

$this->app->when(VideoController::class)
    ->needs(Filesystem::class)
    ->give(function () {
        return Storage::disk('s3');
    });

// Bind primitive values
$this->app->when(UserController::class)
    ->needs('$maxResults')
    ->give(100);
```

### Service Container Best Practices

```php
<?php
// ✅ Good: Bind interfaces to implementations
$this->app->bind(PaymentGatewayInterface::class, StripeGateway::class);

// ✅ Good: Use singleton for expensive objects
$this->app->singleton(ReportGenerator::class);

// ✅ Good: Contextual binding for different needs
$this->app->when(ExportController::class)
    ->needs('$format')
    ->give('pdf');

// ❌ Bad: Avoid tight coupling to container
class BadController {
    public function __construct() {
        $this->service = app(MyService::class);  // Avoid this
    }
}

// ✅ Good: Type hint dependencies
class GoodController {
    public function __construct(
        private MyService $service  // Let container inject
    ) {}
}
```

### Binding Interfaces to Implementations

```php
// Define interface
interface PaymentGateway {
    public function charge(int $amount): bool;
}

// Implementation
class StripeGateway implements PaymentGateway {
    public function charge(int $amount): bool {
        // Stripe implementation
    }
}

// Bind in service provider
public function register() {
    $this->app->bind(PaymentGateway::class, StripeGateway::class);

    // Or use closure for complex initialization
    $this->app->bind(PaymentGateway::class, function ($app) {
        return new StripeGateway(
            config('services.stripe.key'),
            $app->make(Logger::class)
        );
    });
}

// Use in controller
class PaymentController extends Controller {
    public function __construct(
        private PaymentGateway $gateway  // StripeGateway injected
    ) {}
}
```

### Tagged Services

```php
// Register and tag services
public function register() {
    $this->app->bind(SpeedReport::class);
    $this->app->bind(MemoryReport::class);

    $this->app->tag([SpeedReport::class, MemoryReport::class], 'reports');
}

// Resolve all tagged services
$reports = app()->tagged('reports');

foreach ($reports as $report) {
    $report->generate();
}
```

### Make vs Resolve

```php
// app()->make() - Resolve from container
$userService = app()->make(UserService::class);

// app()->makeWith() - Resolve with parameters
$userService = app()->makeWith(UserService::class, ['userId' => 1]);

// resolve() - Helper function
$userService = resolve(UserService::class);
```

**Follow-up:**
- What's the difference between `bind()` and `singleton()`?
- When would you use contextual binding?
- How does autowiring work?

**Key Points:**
- Service Container manages dependencies
- Automatic dependency injection via type-hinting
- Bind interfaces to implementations
- Singleton vs bind: single instance vs new instance
- Contextual binding for different contexts

---

## Question 2: What are Service Providers and how do they work?

**Answer:**

Service Providers are the central place for bootstrapping Laravel applications - registering bindings, event listeners, middleware, routes, etc.

### Basic Structure

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider {
    /**
     * Register services - bind things into container
     */
    public function register() {
        $this->app->singleton(PaymentGateway::class, function ($app) {
            return new StripeGateway(
                config('services.stripe.key')
            );
        });

        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../config/payment.php', 'payment'
        );
    }

    /**
     * Bootstrap services - use resolved services
     */
    public function boot() {
        // Register routes
        $this->loadRoutesFrom(__DIR__.'/../routes/payment.php');

        // Register migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'payment');

        // Publish assets
        $this->publishes([
            __DIR__.'/../config/payment.php' => config_path('payment.php'),
        ], 'config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ProcessPaymentsCommand::class,
            ]);
        }
    }
}
```

### Register vs Boot

```php
class AppServiceProvider extends ServiceProvider {
    /**
     * Register: Bind services, don't use other services
     * Called before boot() of all providers
     */
    public function register() {
        // Only bind services
        $this->app->singleton(UserRepository::class);

        // DON'T DO THIS in register():
        // $users = app(UserRepository::class)->all();  // Service may not be ready
    }

    /**
     * Boot: Use other services, they're all registered now
     * Called after all providers' register() methods
     */
    public function boot() {
        // Safe to use other services
        $repository = app(UserRepository::class);

        // Register view composers
        View::composer('profile', function ($view) {
            $view->with('count', app(UserRepository::class)->count());
        });

        // Model observers
        User::observe(UserObserver::class);

        // Validation rules
        Validator::extend('custom_rule', function ($attribute, $value, $parameters) {
            return $value === 'valid';
        });
    }
}
```

### Deferred Providers

```php
// Only loaded when services are actually needed
class RiakServiceProvider extends ServiceProvider {
    /**
     * Defer loading until needed
     */
    protected $defer = true;

    public function register() {
        $this->app->singleton(Connection::class, function ($app) {
            return new Connection(config('riak'));
        });
    }

    /**
     * Specify which services trigger loading this provider
     */
    public function provides() {
        return [Connection::class];
    }
}
```

### Registering Providers

```php
// config/app.php
'providers' => [
    // Laravel Framework Service Providers
    Illuminate\Auth\AuthServiceProvider::class,
    Illuminate\Broadcasting\BroadcastServiceProvider::class,

    // Package Service Providers
    Laravel\Passport\PassportServiceProvider::class,

    // Application Service Providers
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\RouteServiceProvider::class,
    App\Providers\PaymentServiceProvider::class,
],
```

### Package Development

```php
class MyPackageServiceProvider extends ServiceProvider {
    public function boot() {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/mypackage.php' => config_path('mypackage.php'),
        ], 'config');

        // Publish views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/mypackage'),
        ], 'views');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'migrations');

        // Publish assets
        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/mypackage'),
        ], 'public');
    }
}

// Users can publish with:
// php artisan vendor:publish --provider="MyPackageServiceProvider"
// php artisan vendor:publish --tag="config"
```

**Follow-up:**
- What's the difference between `register()` and `boot()`?
- When should you use deferred providers?
- How do you create a package with a service provider?

**Key Points:**
- `register()`: Bind services into container
- `boot()`: Bootstrap application, use resolved services
- Deferred providers loaded only when needed
- Register providers in `config/app.php`
- Use for: bindings, routes, views, migrations, commands

---

## Question 3: Explain Facades and how they differ from dependency injection.

**Answer:**

Facades provide a static interface to classes in the Service Container.

### How Facades Work

```php
// Using Facade
use Illuminate\Support\Facades\Cache;

Cache::put('key', 'value', 600);

// Behind the scenes, resolves to:
app('cache')->put('key', 'value', 600);

// Facade class
class Cache extends Facade {
    protected static function getFacadeAccessor() {
        return 'cache';  // Container binding name
    }
}
```

### Creating Custom Facades

```php
// 1. Create service class
namespace App\Services;

class PaymentService {
    public function charge(int $amount): bool {
        // Implementation
    }
}

// 2. Create Facade
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Payment extends Facade {
    protected static function getFacadeAccessor() {
        return PaymentService::class;
    }
}

// 3. Register service in provider
public function register() {
    $this->app->singleton(PaymentService::class);
}

// 4. Add alias (optional, in config/app.php)
'aliases' => [
    'Payment' => App\Facades\Payment::class,
]

// 5. Usage
use App\Facades\Payment;

Payment::charge(100);
```

### Facades vs Dependency Injection

```php
// Dependency Injection (recommended for classes)
class OrderController extends Controller {
    public function __construct(
        private PaymentGateway $payment
    ) {}

    public function store() {
        $this->payment->charge(100);  // Testable, explicit
    }
}

// Facade (convenient for quick access)
class OrderController extends Controller {
    public function store() {
        Payment::charge(100);  // Quick, but harder to test
    }
}
```

### Facade Testing

```php
// Facades are testable with fake/mock
use Illuminate\Support\Facades\Cache;

public function test_user_data_is_cached() {
    Cache::shouldReceive('put')
        ->once()
        ->with('user:1', $userData, 3600);

    $this->userService->cacheUser(1, $userData);
}

// Real-time facades (any class as facade)
use Facades\App\Services\PaymentService;

PaymentService::charge(100);  // Creates facade dynamically
```

### Common Facades

```php
// Cache
Cache::put('key', 'value', 600);
Cache::get('key');

// DB
DB::table('users')->where('active', 1)->get();

// Route
Route::get('/users', [UserController::class, 'index']);

// Storage
Storage::disk('s3')->put('file.jpg', $contents);

// Log
Log::info('User logged in', ['user_id' => 1]);

// Event
Event::dispatch(new OrderShipped($order));

// Queue
Queue::push(new ProcessOrder($order));
```

### Facade Methods

```php
// Get underlying instance
$cache = Cache::getFacadeRoot();

// Spy on facade
Cache::spy();
Cache::shouldHaveReceived('get')->once();

// Fake facade
Storage::fake('s3');
Storage::disk('s3')->put('file.jpg', 'contents');
Storage::disk('s3')->assertExists('file.jpg');
```

**When to use Facades vs DI:**

| Use Facades | Use Dependency Injection |
|-------------|--------------------------|
| Quick prototyping | Complex classes with many dependencies |
| Route closures | Controller classes |
| Simple scripts | Testable business logic |
| Blade templates | Service classes |

**Follow-up:**
- How do facades impact testability?
- What are real-time facades?
- Can you explain the facade pattern vs Laravel facades?

**Key Points:**
- Facades = static interface to container bindings
- Convenient but can hide dependencies
- Testable with `shouldReceive()` mocking
- Use DI for complex business logic
- Facades good for: routes, closures, quick access

---

## Question 4: What is the Laravel Request Lifecycle?

**Answer:**

Understanding the request lifecycle helps with debugging and extending Laravel.

### Complete Lifecycle

```
1. public/index.php (Entry point)
   ↓
2. Bootstrap Laravel (vendor/autoload.php)
   ↓
3. Create Application Instance (bootstrap/app.php)
   ↓
4. Kernel Instance (HTTP or Console)
   ↓
5. Service Providers Registration
   ↓
6. Service Providers Boot
   ↓
7. Middleware Stack
   ↓
8. Router Dispatch
   ↓
9. Controller/Route Handler
   ↓
10. Response
   ↓
11. Middleware (response)
   ↓
12. Send Response to Browser
   ↓
13. Terminate Middleware
```

### Detailed Flow

```php
// 1. public/index.php - Entry Point
<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);

// 2. bootstrap/app.php - Application Instance
$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

// Bind important interfaces
$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

return $app;

// 3. app/Http/Kernel.php - HTTP Kernel
class Kernel extends HttpKernel {
    // Global middleware (run on every request)
    protected $middleware = [
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
    ];

    // Middleware groups
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
        ],
        'api' => [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    // Route middleware
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
    ];
}
```

### Service Provider Lifecycle

```php
// 1. Register all providers
foreach ($providers as $provider) {
    $provider->register();
}

// 2. Boot all providers
foreach ($providers as $provider) {
    $provider->boot();
}

// Example timeline:
AppServiceProvider::register()
RouteServiceProvider::register()
AuthServiceProvider::register()
EventServiceProvider::register()
// ... all providers registered

AppServiceProvider::boot()
RouteServiceProvider::boot()  // Routes loaded here
AuthServiceProvider::boot()
EventServiceProvider::boot()
// ... all providers booted
```

### Middleware Execution

```php
// Request goes through middleware stack (onion layers)

// 1. Global middleware (all requests)
TrustProxies → ValidatePostSize → TrimStrings

// 2. Middleware groups (web/api)
web: EncryptCookies → StartSession → VerifyCsrfToken

// 3. Route-specific middleware
auth → verified

// 4. Controller action
UserController@show

// 5. Return through middleware (in reverse)
verified → auth → ... → TrustProxies

// 6. Terminate middleware (after response sent)
StartSession::terminate()  // Close session
```

### Request Processing

```php
class Kernel {
    public function handle($request) {
        // 1. Capture request
        $request = Request::capture();

        // 2. Send through middleware
        $response = $this->sendRequestThroughRouter($request);

        // 3. Return response
        return $response;
    }

    protected function sendRequestThroughRouter($request) {
        // Bootstrap application
        $this->bootstrap();

        // Send through middleware pipeline
        return (new Pipeline($this->app))
            ->send($request)
            ->through($this->middleware)
            ->then($this->dispatchToRouter());
    }

    public function terminate($request, $response) {
        // Run terminating middleware
        $this->terminateMiddleware($request, $response);

        // Terminate application
        $this->app->terminate();
    }
}
```

### Bootstrappers

```php
// Kernel bootstraps the application with:
protected $bootstrappers = [
    \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
    \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
    \Illuminate\Foundation\Bootstrap\HandleExceptions::class,
    \Illuminate\Foundation\Bootstrap\RegisterFacades::class,
    \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
    \Illuminate\Foundation\Bootstrap\BootProviders::class,
];
```

**Follow-up:**
- What happens in terminate middleware?
- When are service providers booted?
- How does the middleware pipeline work?

**Key Points:**
- Entry: `public/index.php`
- Bootstrap: Load config, register providers
- Middleware: Request → Global → Group → Route → Controller
- Response: Return through middleware in reverse
- Terminate: Cleanup after response sent

---

## Question 5: Explain Laravel's Pipeline pattern and how it's used.

**Answer:**

The Pipeline pattern passes an object through a series of stages, with each stage processing it.

### Basic Pipeline Usage

```php
use Illuminate\Pipeline\Pipeline;

$result = app(Pipeline::class)
    ->send($data)
    ->through([
        StageOne::class,
        StageTwo::class,
        StageThree::class,
    ])
    ->then(function ($data) {
        return $data;  // Final destination
    });
```

### Creating Pipeline Stages

```php
// Stage class
class ValidateData {
    public function handle($data, Closure $next) {
        // Process before
        if (!isset($data['email'])) {
            throw new ValidationException('Email required');
        }

        // Pass to next stage
        $result = $next($data);

        // Process after (optional)
        return $result;
    }
}

class TransformData {
    public function handle($data, Closure $next) {
        $data['email'] = strtolower($data['email']);
        return $next($data);
    }
}

class EnrichData {
    public function handle($data, Closure $next) {
        $data['timestamp'] = now();
        return $next($data);
    }
}

// Usage
$processed = app(Pipeline::class)
    ->send(['email' => 'USER@EXAMPLE.COM'])
    ->through([
        ValidateData::class,
        TransformData::class,
        EnrichData::class,
    ])
    ->then(fn($data) => $data);

// Result: ['email' => 'user@example.com', 'timestamp' => Carbon]
```

### Middleware Pipeline

```php
// Laravel's middleware IS the pipeline pattern
class Kernel {
    protected function sendRequestThroughRouter($request) {
        return (new Pipeline($this->app))
            ->send($request)
            ->through($this->middleware)
            ->then(function ($request) {
                return $this->router->dispatch($request);
            });
    }
}

// Each middleware is a pipeline stage
class Authenticate {
    public function handle($request, Closure $next) {
        if (!Auth::check()) {
            return redirect('/login');
        }

        return $next($request);  // Continue pipeline
    }
}
```

### Custom Pipeline for Business Logic

```php
// Order processing pipeline
class ProcessOrder {
    public function __construct(
        private Order $order
    ) {}

    public function process() {
        return app(Pipeline::class)
            ->send($this->order)
            ->through([
                ValidateInventory::class,
                ApplyDiscounts::class,
                CalculateTax::class,
                ProcessPayment::class,
                SendConfirmation::class,
                UpdateInventory::class,
            ])
            ->then(fn($order) => $order);
    }
}

// Each stage
class ValidateInventory {
    public function handle(Order $order, Closure $next) {
        foreach ($order->items as $item) {
            if ($item->product->stock < $item->quantity) {
                throw new OutOfStockException();
            }
        }

        return $next($order);
    }
}

class ApplyDiscounts {
    public function handle(Order $order, Closure $next) {
        $discount = DiscountService::calculate($order);
        $order->discount = $discount;
        $order->total -= $discount;

        return $next($order);
    }
}
```

### Pipeline with Parameters

```php
// Pass additional parameters
app(Pipeline::class)
    ->send($data)
    ->through([
        "App\\Pipes\\ValidateData:create",  // Pass 'create' as parameter
        "App\\Pipes\\TransformData:strict",
    ])
    ->then(fn($data) => $data);

// Stage receives parameter
class ValidateData {
    public function handle($data, Closure $next, $mode = 'default') {
        if ($mode === 'create') {
            // Validation for create
        }

        return $next($data);
    }
}
```

### Conditional Pipeline

```php
class DataProcessor {
    public function process($data, array $stages) {
        $pipeline = app(Pipeline::class)->send($data);

        // Dynamically add stages based on conditions
        if ($data['type'] === 'premium') {
            array_unshift($stages, PremiumValidation::class);
        }

        return $pipeline
            ->through($stages)
            ->then(fn($data) => $data);
    }
}
```

### Via Method (Custom Handler)

```php
// Use different method name instead of 'handle'
app(Pipeline::class)
    ->send($data)
    ->through([
        ProcessStageOne::class,
        ProcessStageTwo::class,
    ])
    ->via('process')  // Call 'process' instead of 'handle'
    ->then(fn($data) => $data);

class ProcessStageOne {
    public function process($data, Closure $next) {
        // Custom method name
        return $next($data);
    }
}
```

**Follow-up:**
- How does the pipeline pattern relate to middleware?
- What are the benefits of pipeline over procedural code?
- Can you short-circuit a pipeline?

**Key Points:**
- Pipeline passes object through multiple stages
- Each stage: process → `$next()` → process after
- Middleware is Laravel's pipeline implementation
- Useful for: validation, transformation, enrichment chains
- Clean, testable, composable logic

---

## Notes

Add more questions covering:
- Contracts (interfaces) in Laravel
- Middleware types and creation
- Macros and extending Laravel
- Package development
- Events and listeners architecture
- Collections and lazy collections
- Query builder architecture
