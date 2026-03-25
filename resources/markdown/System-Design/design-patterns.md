# OOP Design Patterns for Laravel Development

This guide covers the most useful design patterns with real-world Laravel implementations.

---

## Creational Patterns

### 1. Singleton Pattern

**Concept**: Ensures a class has only one instance and provides a global access point to it.

**When to Use**:
- Application configuration
- Database connections
- Logger instances
- Cache managers

**Laravel Example - Service Container**:

```php
// Laravel's Application instance is a Singleton
$app = app();
$sameApp = app();
// $app === $sameApp (true)

// Creating a Singleton binding
app()->singleton(PaymentGateway::class, function ($app) {
    return new PaymentGateway(
        config('services.stripe.key')
    );
});

// Every time you resolve, you get the same instance
$gateway1 = app(PaymentGateway::class);
$gateway2 = app(PaymentGateway::class);
// $gateway1 === $gateway2 (true)
```

**Real Use Case - Configuration Manager**:

```php
class ConfigurationManager
{
    private static ?self $instance = null;
    private array $config = [];

    private function __construct()
    {
        // Load configuration
        $this->config = config('app');
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    // Prevent cloning
    private function __clone() {}

    // Prevent unserialization
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}

// Usage
$config = ConfigurationManager::getInstance();
echo $config->get('app.name');
```

---

### 2. Factory Pattern

**Concept**: Creates objects without specifying the exact class to create.

**When to Use**:
- Creating different types of objects based on conditions
- Payment gateways (Stripe, PayPal, etc.)
- Notification channels (Email, SMS, Slack)
- Report generators (PDF, Excel, CSV)

**Laravel Example - Payment Gateway Factory**:

```php
interface PaymentGatewayInterface
{
    public function charge(float $amount, string $currency): PaymentResult;
    public function refund(string $transactionId): RefundResult;
}

class StripeGateway implements PaymentGatewayInterface
{
    public function __construct(private string $apiKey) {}

    public function charge(float $amount, string $currency): PaymentResult
    {
        // Stripe-specific implementation
        return new PaymentResult(
            success: true,
            transactionId: 'stripe_' . uniqid()
        );
    }

    public function refund(string $transactionId): RefundResult
    {
        // Stripe refund logic
        return new RefundResult(success: true);
    }
}

class PayPalGateway implements PaymentGatewayInterface
{
    public function __construct(private string $clientId, private string $secret) {}

    public function charge(float $amount, string $currency): PaymentResult
    {
        // PayPal-specific implementation
        return new PaymentResult(
            success: true,
            transactionId: 'paypal_' . uniqid()
        );
    }

    public function refund(string $transactionId): RefundResult
    {
        // PayPal refund logic
        return new RefundResult(success: true);
    }
}

class PaymentGatewayFactory
{
    public function create(string $gateway): PaymentGatewayInterface
    {
        return match($gateway) {
            'stripe' => new StripeGateway(config('services.stripe.key')),
            'paypal' => new PayPalGateway(
                config('services.paypal.client_id'),
                config('services.paypal.secret')
            ),
            default => throw new \InvalidArgumentException("Unknown gateway: $gateway")
        };
    }
}

// Usage in Controller
class PaymentController extends Controller
{
    public function __construct(private PaymentGatewayFactory $factory) {}

    public function charge(Request $request)
    {
        $gateway = $this->factory->create($request->input('gateway'));

        $result = $gateway->charge(
            amount: $request->input('amount'),
            currency: 'USD'
        );

        return response()->json($result);
    }
}
```

**Laravel Example - Notification Channel Factory**:

```php
// Service Provider
app()->bind(NotificationFactory::class, function ($app) {
    return new NotificationFactory();
});

class NotificationFactory
{
    public function create(string $channel): NotificationChannel
    {
        return match($channel) {
            'email' => new EmailChannel(config('mail')),
            'sms' => new SmsChannel(config('services.twilio')),
            'slack' => new SlackChannel(config('services.slack.webhook')),
            'push' => new PushNotificationChannel(config('services.fcm')),
            default => throw new \InvalidArgumentException("Unknown channel: $channel")
        };
    }
}
```

---

### 3. Builder Pattern

**Concept**: Constructs complex objects step by step. Allows you to produce different types and representations using the same construction code.

**When to Use**:
- Query builders
- Complex object creation with many optional parameters
- Email/notification builders
- Report builders

**Laravel Example - Query Builder**:

```php
// Laravel's Query Builder is a perfect example
$users = DB::table('users')
    ->select('name', 'email')
    ->where('active', true)
    ->where('age', '>', 18)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

// Eloquent Builder
$posts = Post::query()
    ->with(['author', 'comments'])
    ->whereHas('author', function ($query) {
        $query->where('verified', true);
    })
    ->where('published', true)
    ->latest()
    ->paginate(15);
```

**Real Use Case - Email Builder**:

```php
class EmailBuilder
{
    private string $to = '';
    private string $subject = '';
    private string $body = '';
    private array $attachments = [];
    private array $cc = [];
    private array $bcc = [];
    private string $template = '';
    private array $data = [];

    public function to(string $email): self
    {
        $this->to = $email;
        return $this;
    }

    public function subject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function body(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function template(string $template, array $data = []): self
    {
        $this->template = $template;
        $this->data = $data;
        return $this;
    }

    public function attach(string $file): self
    {
        $this->attachments[] = $file;
        return $this;
    }

    public function cc(string $email): self
    {
        $this->cc[] = $email;
        return $this;
    }

    public function bcc(string $email): self
    {
        $this->bcc[] = $email;
        return $this;
    }

    public function send(): bool
    {
        $mailable = new GenericMail([
            'to' => $this->to,
            'subject' => $this->subject,
            'body' => $this->body,
            'template' => $this->template,
            'data' => $this->data,
            'attachments' => $this->attachments,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
        ]);

        Mail::send($mailable);

        return true;
    }
}

// Usage
$emailBuilder = new EmailBuilder();

$emailBuilder
    ->to('user@example.com')
    ->subject('Welcome to Our Platform')
    ->template('emails.welcome', ['name' => 'John'])
    ->attach(storage_path('app/welcome.pdf'))
    ->cc('manager@example.com')
    ->send();
```

---

## Structural Patterns

### 4. Repository Pattern

**Concept**: Mediates between the domain and data mapping layers, acting like an in-memory collection of domain objects.

**When to Use**:
- Abstracting database operations
- Switching between different data sources
- Testing (easy to mock repositories)
- Complex query logic

**Laravel Example - User Repository**:

```php
interface UserRepositoryInterface
{
    public function find(int $id): ?User;
    public function all(): Collection;
    public function create(array $data): User;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function findByEmail(string $email): ?User;
    public function getActiveUsers(): Collection;
}

class EloquentUserRepository implements UserRepositoryInterface
{
    public function find(int $id): ?User
    {
        return User::find($id);
    }

    public function all(): Collection
    {
        return User::all();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $user = User::find($id);

        if (!$user) {
            return false;
        }

        return $user->update($data);
    }

    public function delete(int $id): bool
    {
        $user = User::find($id);

        if (!$user) {
            return false;
        }

        return $user->delete();
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function getActiveUsers(): Collection
    {
        return User::where('active', true)
            ->where('email_verified_at', '!=', null)
            ->get();
    }
}

// Service Provider registration
public function register()
{
    $this->app->bind(
        UserRepositoryInterface::class,
        EloquentUserRepository::class
    );
}

// Usage in Controller
class UserController extends Controller
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function show(int $id)
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            abort(404);
        }

        return view('users.show', compact('user'));
    }

    public function store(StoreUserRequest $request)
    {
        $user = $this->userRepository->create($request->validated());

        return redirect()->route('users.show', $user->id);
    }
}
```

**Advanced Repository with Criteria Pattern**:

```php
interface CriteriaInterface
{
    public function apply($query);
}

class ActiveUsersCriteria implements CriteriaInterface
{
    public function apply($query)
    {
        return $query->where('active', true);
    }
}

class VerifiedUsersCriteria implements CriteriaInterface
{
    public function apply($query)
    {
        return $query->whereNotNull('email_verified_at');
    }
}

class BaseRepository
{
    protected $model;
    protected array $criteria = [];

    public function pushCriteria(CriteriaInterface $criteria): self
    {
        $this->criteria[] = $criteria;
        return $this;
    }

    protected function applyCriteria($query)
    {
        foreach ($this->criteria as $criteria) {
            $query = $criteria->apply($query);
        }

        return $query;
    }

    public function get(): Collection
    {
        $query = $this->model->query();
        $query = $this->applyCriteria($query);
        $this->criteria = []; // Reset

        return $query->get();
    }
}

// Usage
$users = $userRepository
    ->pushCriteria(new ActiveUsersCriteria())
    ->pushCriteria(new VerifiedUsersCriteria())
    ->get();
```

---

### 5. Decorator Pattern

**Concept**: Attaches additional responsibilities to an object dynamically without modifying its structure.

**When to Use**:
- Adding functionality to existing classes
- Caching layers
- Logging layers
- Authorization checks

**Laravel Example - Caching Decorator**:

```php
interface PostRepositoryInterface
{
    public function find(int $id): ?Post;
    public function all(): Collection;
    public function getFeatured(): Collection;
}

class EloquentPostRepository implements PostRepositoryInterface
{
    public function find(int $id): ?Post
    {
        return Post::find($id);
    }

    public function all(): Collection
    {
        return Post::all();
    }

    public function getFeatured(): Collection
    {
        return Post::where('featured', true)
            ->latest()
            ->take(5)
            ->get();
    }
}

class CachedPostRepository implements PostRepositoryInterface
{
    public function __construct(
        private PostRepositoryInterface $repository,
        private CacheManager $cache
    ) {}

    public function find(int $id): ?Post
    {
        return $this->cache->remember(
            "posts.{$id}",
            3600,
            fn() => $this->repository->find($id)
        );
    }

    public function all(): Collection
    {
        return $this->cache->remember(
            'posts.all',
            3600,
            fn() => $this->repository->all()
        );
    }

    public function getFeatured(): Collection
    {
        return $this->cache->remember(
            'posts.featured',
            1800,
            fn() => $this->repository->getFeatured()
        );
    }
}

// Service Provider
public function register()
{
    $this->app->singleton(PostRepositoryInterface::class, function ($app) {
        $repository = new EloquentPostRepository();

        // Wrap with caching decorator in production
        if (config('app.env') === 'production') {
            return new CachedPostRepository(
                $repository,
                $app->make(CacheManager::class)
            );
        }

        return $repository;
    });
}
```

**Logging Decorator Example**:

```php
class LoggingPostRepository implements PostRepositoryInterface
{
    public function __construct(
        private PostRepositoryInterface $repository,
        private LoggerInterface $logger
    ) {}

    public function find(int $id): ?Post
    {
        $this->logger->info("Finding post", ['id' => $id]);

        $post = $this->repository->find($id);

        $this->logger->info(
            $post ? "Post found" : "Post not found",
            ['id' => $id]
        );

        return $post;
    }

    public function all(): Collection
    {
        $this->logger->info("Fetching all posts");
        $posts = $this->repository->all();
        $this->logger->info("Fetched posts", ['count' => $posts->count()]);

        return $posts;
    }

    public function getFeatured(): Collection
    {
        $this->logger->info("Fetching featured posts");
        $posts = $this->repository->getFeatured();
        $this->logger->info("Fetched featured posts", ['count' => $posts->count()]);

        return $posts;
    }
}

// Combining decorators
$repository = new EloquentPostRepository();
$repository = new LoggingPostRepository($repository, $logger);
$repository = new CachedPostRepository($repository, $cache);
```

---

### 6. Adapter Pattern

**Concept**: Converts the interface of a class into another interface clients expect. Allows classes to work together that couldn't otherwise because of incompatible interfaces.

**When to Use**:
- Integrating third-party libraries
- Legacy code integration
- Multiple payment gateways
- Different storage systems

**Laravel Example - Storage Adapter**:

```php
interface FileStorageInterface
{
    public function store(string $path, string $contents): bool;
    public function get(string $path): ?string;
    public function delete(string $path): bool;
    public function exists(string $path): bool;
}

// Laravel Storage Adapter
class LaravelStorageAdapter implements FileStorageInterface
{
    public function __construct(private string $disk = 'local') {}

    public function store(string $path, string $contents): bool
    {
        return Storage::disk($this->disk)->put($path, $contents);
    }

    public function get(string $path): ?string
    {
        if (!$this->exists($path)) {
            return null;
        }

        return Storage::disk($this->disk)->get($path);
    }

    public function delete(string $path): bool
    {
        return Storage::disk($this->disk)->delete($path);
    }

    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }
}

// AWS S3 Adapter
class S3StorageAdapter implements FileStorageInterface
{
    private S3Client $client;
    private string $bucket;

    public function __construct(array $config)
    {
        $this->client = new S3Client([
            'version' => 'latest',
            'region' => $config['region'],
            'credentials' => [
                'key' => $config['key'],
                'secret' => $config['secret'],
            ],
        ]);

        $this->bucket = $config['bucket'];
    }

    public function store(string $path, string $contents): bool
    {
        try {
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
                'Body' => $contents,
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function get(string $path): ?string
    {
        try {
            $result = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            return (string) $result['Body'];
        } catch (\Exception $e) {
            return null;
        }
    }

    public function delete(string $path): bool
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function exists(string $path): bool
    {
        return $this->client->doesObjectExist($this->bucket, $path);
    }
}

// Usage - Switching storage is transparent
class DocumentService
{
    public function __construct(private FileStorageInterface $storage) {}

    public function uploadDocument(string $name, string $content): bool
    {
        $path = "documents/{$name}";
        return $this->storage->store($path, $content);
    }

    public function getDocument(string $name): ?string
    {
        $path = "documents/{$name}";
        return $this->storage->get($path);
    }
}

// Service Provider - Easy to switch implementations
public function register()
{
    $this->app->bind(FileStorageInterface::class, function ($app) {
        if (config('filesystems.default') === 's3') {
            return new S3StorageAdapter(config('filesystems.disks.s3'));
        }

        return new LaravelStorageAdapter(config('filesystems.default'));
    });
}
```

---

## Behavioral Patterns

### 7. Strategy Pattern

**Concept**: Defines a family of algorithms, encapsulates each one, and makes them interchangeable.

**When to Use**:
- Different calculation methods
- Multiple sorting algorithms
- Various export formats
- Different pricing strategies

**Laravel Example - Pricing Strategy**:

```php
interface PricingStrategyInterface
{
    public function calculate(float $basePrice, array $context): float;
}

class RegularPricingStrategy implements PricingStrategyInterface
{
    public function calculate(float $basePrice, array $context): float
    {
        return $basePrice;
    }
}

class MemberPricingStrategy implements PricingStrategyInterface
{
    public function calculate(float $basePrice, array $context): float
    {
        // 10% discount for members
        return $basePrice * 0.90;
    }
}

class VipPricingStrategy implements PricingStrategyInterface
{
    public function calculate(float $basePrice, array $context): float
    {
        // 20% discount for VIP members
        return $basePrice * 0.80;
    }
}

class BulkPricingStrategy implements PricingStrategyInterface
{
    public function calculate(float $basePrice, array $context): float
    {
        $quantity = $context['quantity'] ?? 1;

        if ($quantity >= 100) {
            return $basePrice * 0.70; // 30% off
        } elseif ($quantity >= 50) {
            return $basePrice * 0.80; // 20% off
        } elseif ($quantity >= 10) {
            return $basePrice * 0.90; // 10% off
        }

        return $basePrice;
    }
}

class SeasonalPricingStrategy implements PricingStrategyInterface
{
    public function calculate(float $basePrice, array $context): float
    {
        $month = now()->month;

        // Black Friday / Holiday season (Nov-Dec)
        if (in_array($month, [11, 12])) {
            return $basePrice * 0.75; // 25% off
        }

        // Summer sale (Jun-Aug)
        if (in_array($month, [6, 7, 8])) {
            return $basePrice * 0.85; // 15% off
        }

        return $basePrice;
    }
}

class PricingContext
{
    public function __construct(
        private PricingStrategyInterface $strategy
    ) {}

    public function setStrategy(PricingStrategyInterface $strategy): void
    {
        $this->strategy = $strategy;
    }

    public function calculatePrice(float $basePrice, array $context = []): float
    {
        return $this->strategy->calculate($basePrice, $context);
    }
}

// Usage in Controller
class ProductController extends Controller
{
    public function getPrice(Request $request, Product $product)
    {
        $user = $request->user();
        $quantity = $request->input('quantity', 1);

        // Determine strategy based on user type
        $strategy = match(true) {
            $user?->isVip() => new VipPricingStrategy(),
            $user?->isMember() => new MemberPricingStrategy(),
            $quantity >= 10 => new BulkPricingStrategy(),
            default => new RegularPricingStrategy(),
        };

        $context = new PricingContext($strategy);

        // Apply seasonal discount on top if applicable
        $basePrice = $context->calculatePrice(
            $product->base_price,
            ['quantity' => $quantity]
        );

        $seasonalContext = new PricingContext(new SeasonalPricingStrategy());
        $finalPrice = $seasonalContext->calculatePrice($basePrice);

        return response()->json([
            'base_price' => $product->base_price,
            'final_price' => $finalPrice,
            'savings' => $product->base_price - $finalPrice,
        ]);
    }
}
```

---

### 8. Observer Pattern

**Concept**: Defines a one-to-many dependency between objects so that when one object changes state, all its dependents are notified.

**When to Use**:
- Event-driven systems
- Real-time notifications
- Activity logging
- Audit trails

**Laravel Example - Events & Listeners**:

```php
// Event
class OrderPlaced
{
    public function __construct(
        public Order $order,
        public User $user
    ) {}
}

// Listeners (Observers)
class SendOrderConfirmationEmail
{
    public function handle(OrderPlaced $event): void
    {
        Mail::to($event->user->email)
            ->send(new OrderConfirmationMail($event->order));
    }
}

class UpdateInventory
{
    public function handle(OrderPlaced $event): void
    {
        foreach ($event->order->items as $item) {
            $product = Product::find($item->product_id);
            $product->decrement('stock', $item->quantity);
        }
    }
}

class NotifyAdminOfLargeOrder
{
    public function handle(OrderPlaced $event): void
    {
        if ($event->order->total > 1000) {
            Notification::send(
                User::admins()->get(),
                new LargeOrderPlaced($event->order)
            );
        }
    }
}

class CreateInvoice
{
    public function handle(OrderPlaced $event): void
    {
        Invoice::create([
            'order_id' => $event->order->id,
            'user_id' => $event->user->id,
            'amount' => $event->order->total,
            'status' => 'pending',
        ]);
    }
}

class LogOrderActivity
{
    public function handle(OrderPlaced $event): void
    {
        ActivityLog::create([
            'user_id' => $event->user->id,
            'action' => 'order_placed',
            'subject_type' => Order::class,
            'subject_id' => $event->order->id,
            'properties' => [
                'total' => $event->order->total,
                'items_count' => $event->order->items->count(),
            ],
        ]);
    }
}

// EventServiceProvider
protected $listen = [
    OrderPlaced::class => [
        SendOrderConfirmationEmail::class,
        UpdateInventory::class,
        NotifyAdminOfLargeOrder::class,
        CreateInvoice::class,
        LogOrderActivity::class,
    ],
];

// Usage in Controller
class OrderController extends Controller
{
    public function store(StoreOrderRequest $request)
    {
        $order = Order::create($request->validated());

        // Fire event - all listeners will be notified
        event(new OrderPlaced($order, $request->user()));

        return redirect()->route('orders.show', $order);
    }
}
```

**Model Observers Example**:

```php
class UserObserver
{
    public function creating(User $user): void
    {
        // Automatically generate UUID
        if (empty($user->uuid)) {
            $user->uuid = (string) Str::uuid();
        }

        // Hash password if not already hashed
        if (!Hash::needsRehash($user->password)) {
            $user->password = Hash::make($user->password);
        }
    }

    public function created(User $user): void
    {
        // Send welcome email
        Mail::to($user->email)->send(new WelcomeEmail($user));

        // Create default settings
        UserSettings::create([
            'user_id' => $user->id,
            'notifications_enabled' => true,
            'theme' => 'light',
        ]);

        // Log user creation
        Log::info('New user registered', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }

    public function updating(User $user): void
    {
        // Rehash password if changed
        if ($user->isDirty('password') && !Hash::needsRehash($user->password)) {
            $user->password = Hash::make($user->password);
        }
    }

    public function updated(User $user): void
    {
        // Clear user cache
        Cache::forget("user.{$user->id}");

        // Log important changes
        if ($user->isDirty('email')) {
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'email_changed',
                'old_value' => $user->getOriginal('email'),
                'new_value' => $user->email,
            ]);
        }
    }

    public function deleting(User $user): void
    {
        // Delete related records
        $user->posts()->delete();
        $user->comments()->delete();
        $user->settings()->delete();
    }

    public function deleted(User $user): void
    {
        // Clear all user caches
        Cache::tags(["user.{$user->id}"])->flush();

        // Log deletion
        Log::warning('User deleted', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }
}

// Register in ServiceProvider
public function boot()
{
    User::observe(UserObserver::class);
}
```

---

### 9. Chain of Responsibility Pattern

**Concept**: Passes requests along a chain of handlers. Each handler decides either to process the request or pass it to the next handler.

**When to Use**:
- Middleware
- Validation pipelines
- Request processing
- Authorization checks

**Laravel Example - Middleware Chain**:

```php
// Laravel Middleware is a perfect example
class CheckAge
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->age < 18) {
            return redirect('home');
        }

        return $next($request);
    }
}

class CheckRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        if (!$request->user()->hasRole($role)) {
            abort(403);
        }

        return $next($request);
    }
}

class LogRequest
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('Request received', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }
}

// Route with middleware chain
Route::get('/admin/dashboard', function () {
    return view('admin.dashboard');
})->middleware(['auth', 'age:18', 'role:admin', 'log.request']);
```

**Custom Chain of Responsibility Example**:

```php
abstract class ValidationHandler
{
    protected ?ValidationHandler $nextHandler = null;

    public function setNext(ValidationHandler $handler): ValidationHandler
    {
        $this->nextHandler = $handler;
        return $handler;
    }

    abstract public function validate(array $data): array;

    protected function next(array $data): array
    {
        if ($this->nextHandler) {
            return $this->nextHandler->validate($data);
        }

        return $data;
    }
}

class EmailValidationHandler extends ValidationHandler
{
    public function validate(array $data): array
    {
        if (empty($data['email'])) {
            throw new ValidationException('Email is required');
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email format');
        }

        // Email is valid, pass to next handler
        return $this->next($data);
    }
}

class UniqueEmailValidationHandler extends ValidationHandler
{
    public function validate(array $data): array
    {
        $exists = User::where('email', $data['email'])->exists();

        if ($exists) {
            throw new ValidationException('Email already exists');
        }

        return $this->next($data);
    }
}

class PasswordStrengthValidationHandler extends ValidationHandler
{
    public function validate(array $data): array
    {
        if (empty($data['password'])) {
            throw new ValidationException('Password is required');
        }

        if (strlen($data['password']) < 8) {
            throw new ValidationException('Password must be at least 8 characters');
        }

        if (!preg_match('/[A-Z]/', $data['password'])) {
            throw new ValidationException('Password must contain uppercase letter');
        }

        if (!preg_match('/[0-9]/', $data['password'])) {
            throw new ValidationException('Password must contain number');
        }

        return $this->next($data);
    }
}

class AgeValidationHandler extends ValidationHandler
{
    public function validate(array $data): array
    {
        if (empty($data['birthdate'])) {
            throw new ValidationException('Birthdate is required');
        }

        $age = Carbon::parse($data['birthdate'])->age;

        if ($age < 18) {
            throw new ValidationException('You must be at least 18 years old');
        }

        return $this->next($data);
    }
}

// Building and using the chain
class UserRegistrationService
{
    private ValidationHandler $validationChain;

    public function __construct()
    {
        // Build the validation chain
        $email = new EmailValidationHandler();
        $uniqueEmail = new UniqueEmailValidationHandler();
        $password = new PasswordStrengthValidationHandler();
        $age = new AgeValidationHandler();

        $email->setNext($uniqueEmail)
              ->setNext($password)
              ->setNext($age);

        $this->validationChain = $email;
    }

    public function register(array $data): User
    {
        // Validate through the chain
        $validatedData = $this->validationChain->validate($data);

        // If we reach here, all validations passed
        return User::create($validatedData);
    }
}

// Usage
$service = new UserRegistrationService();

try {
    $user = $service->register([
        'email' => 'john@example.com',
        'password' => 'SecurePass123',
        'birthdate' => '1990-01-01',
    ]);

    return response()->json(['message' => 'User registered successfully']);
} catch (ValidationException $e) {
    return response()->json(['error' => $e->getMessage()], 422);
}
```

---

### 10. Command Pattern

**Concept**: Encapsulates a request as an object, thereby letting you parameterize clients with different requests, queue or log requests, and support undoable operations.

**When to Use**:
- Queue jobs
- Undo/Redo functionality
- Transaction management
- Task scheduling

**Laravel Example - Jobs (Command Pattern)**:

```php
// Command
class ProcessPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $paymentMethod
    ) {}

    public function handle(PaymentGatewayFactory $factory): void
    {
        $gateway = $factory->create($this->paymentMethod);

        try {
            $result = $gateway->charge(
                $this->order->total,
                $this->order->currency
            );

            if ($result->success) {
                $this->order->update([
                    'status' => 'paid',
                    'transaction_id' => $result->transactionId,
                ]);

                event(new PaymentSuccessful($this->order));
            } else {
                $this->order->update(['status' => 'failed']);
                event(new PaymentFailed($this->order, $result->error));
            }
        } catch (\Exception $e) {
            $this->order->update(['status' => 'failed']);
            Log::error('Payment processing failed', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        // Handle job failure
        Notification::route('slack', config('logging.slack.webhook'))
            ->notify(new JobFailedNotification($exception));
    }
}

// Dispatch the command
ProcessPayment::dispatch($order, 'stripe');

// Dispatch with delay
ProcessPayment::dispatch($order, 'stripe')->delay(now()->addMinutes(5));

// Dispatch to specific queue
ProcessPayment::dispatch($order, 'stripe')->onQueue('payments');

// Chain commands
ProcessPayment::dispatch($order, 'stripe')
    ->chain([
        new SendInvoice($order),
        new UpdateInventory($order),
        new NotifyCustomer($order),
    ]);
```

**Advanced Command Pattern with Undo**:

```php
interface CommandInterface
{
    public function execute(): mixed;
    public function undo(): void;
}

class CreateUserCommand implements CommandInterface
{
    private ?User $createdUser = null;

    public function __construct(private array $data) {}

    public function execute(): User
    {
        $this->createdUser = User::create($this->data);
        return $this->createdUser;
    }

    public function undo(): void
    {
        if ($this->createdUser) {
            $this->createdUser->delete();
        }
    }
}

class UpdateUserCommand implements CommandInterface
{
    private array $oldData = [];

    public function __construct(
        private User $user,
        private array $newData
    ) {
        $this->oldData = $user->only(array_keys($newData));
    }

    public function execute(): User
    {
        $this->user->update($this->newData);
        return $this->user;
    }

    public function undo(): void
    {
        $this->user->update($this->oldData);
    }
}

class DeleteUserCommand implements CommandInterface
{
    private array $userData = [];

    public function __construct(private User $user)
    {
        $this->userData = $user->toArray();
    }

    public function execute(): bool
    {
        return $this->user->delete();
    }

    public function undo(): void
    {
        User::create($this->userData);
    }
}

class CommandInvoker
{
    private array $history = [];
    private int $currentPosition = -1;

    public function execute(CommandInterface $command): mixed
    {
        $result = $command->execute();

        // Remove any commands after current position (for redo)
        $this->history = array_slice($this->history, 0, $this->currentPosition + 1);

        // Add command to history
        $this->history[] = $command;
        $this->currentPosition++;

        return $result;
    }

    public function undo(): void
    {
        if ($this->currentPosition >= 0) {
            $command = $this->history[$this->currentPosition];
            $command->undo();
            $this->currentPosition--;
        }
    }

    public function redo(): void
    {
        if ($this->currentPosition < count($this->history) - 1) {
            $this->currentPosition++;
            $command = $this->history[$this->currentPosition];
            $command->execute();
        }
    }

    public function canUndo(): bool
    {
        return $this->currentPosition >= 0;
    }

    public function canRedo(): bool
    {
        return $this->currentPosition < count($this->history) - 1;
    }
}

// Usage
$invoker = new CommandInvoker();

// Execute commands
$user = $invoker->execute(new CreateUserCommand([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]));

$invoker->execute(new UpdateUserCommand($user, [
    'name' => 'Jane Doe',
]));

// Undo last action
$invoker->undo(); // Name reverts to 'John Doe'

// Redo
$invoker->redo(); // Name changes back to 'Jane Doe'

// Undo again
$invoker->undo();
$invoker->undo(); // User is deleted
```

---

## Additional Useful Patterns

### 11. Service Layer Pattern

**Concept**: Defines an application's boundary with a layer of services that establishes a set of available operations and coordinates the application's response in each operation.

**When to Use**:
- Complex business logic
- Separating controllers from business logic
- Reusable operations across multiple controllers
- Testing business logic

**Laravel Example**:

```php
class OrderService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private PaymentService $paymentService,
        private InventoryService $inventoryService,
        private NotificationService $notificationService
    ) {}

    public function createOrder(User $user, array $items): Order
    {
        DB::beginTransaction();

        try {
            // Validate inventory
            foreach ($items as $item) {
                if (!$this->inventoryService->hasStock($item['product_id'], $item['quantity'])) {
                    throw new InsufficientStockException();
                }
            }

            // Create order
            $order = $this->orderRepository->create([
                'user_id' => $user->id,
                'status' => 'pending',
                'total' => $this->calculateTotal($items),
            ]);

            // Add order items
            foreach ($items as $item) {
                $order->items()->create($item);
            }

            // Reserve inventory
            $this->inventoryService->reserveStock($order);

            DB::commit();

            // Send confirmation
            $this->notificationService->sendOrderConfirmation($order);

            return $order;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function processPayment(Order $order, string $paymentMethod): bool
    {
        $result = $this->paymentService->charge(
            $order->total,
            $paymentMethod,
            $order
        );

        if ($result->success) {
            $order->update([
                'status' => 'paid',
                'transaction_id' => $result->transactionId,
            ]);

            $this->inventoryService->fulfillOrder($order);
            $this->notificationService->sendPaymentConfirmation($order);

            return true;
        }

        $order->update(['status' => 'payment_failed']);
        return false;
    }

    public function cancelOrder(Order $order): void
    {
        if ($order->status === 'paid') {
            // Refund payment
            $this->paymentService->refund($order->transaction_id);
        }

        // Release inventory
        $this->inventoryService->releaseStock($order);

        // Update order
        $order->update(['status' => 'cancelled']);

        // Notify customer
        $this->notificationService->sendCancellationNotification($order);
    }

    private function calculateTotal(array $items): float
    {
        return collect($items)->sum(function ($item) {
            $product = Product::find($item['product_id']);
            return $product->price * $item['quantity'];
        });
    }
}

// Usage in Controller
class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function store(CreateOrderRequest $request)
    {
        try {
            $order = $this->orderService->createOrder(
                $request->user(),
                $request->input('items')
            );

            return response()->json($order, 201);
        } catch (InsufficientStockException $e) {
            return response()->json(['error' => 'Insufficient stock'], 422);
        }
    }
}
```

---

## Pattern Combinations

### Real-World Example: Complete Order Processing System

```php
// 1. Repository Pattern (Data Access)
interface OrderRepositoryInterface
{
    public function find(int $id): ?Order;
    public function create(array $data): Order;
    public function update(Order $order, array $data): bool;
}

class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function find(int $id): ?Order
    {
        return Cache::remember(
            "orders.{$id}",
            3600,
            fn() => Order::with(['items', 'user'])->find($id)
        );
    }

    public function create(array $data): Order
    {
        $order = Order::create($data);
        Cache::forget('orders.all');
        return $order;
    }

    public function update(Order $order, array $data): bool
    {
        $result = $order->update($data);
        Cache::forget("orders.{$order->id}");
        return $result;
    }
}

// 2. Factory Pattern (Payment Gateway Creation)
class PaymentGatewayFactory
{
    public function create(string $type): PaymentGatewayInterface
    {
        return match($type) {
            'stripe' => new StripeGateway(),
            'paypal' => new PayPalGateway(),
            default => throw new \InvalidArgumentException(),
        };
    }
}

// 3. Strategy Pattern (Pricing)
interface PricingStrategyInterface
{
    public function calculate(Order $order): float;
}

class StandardPricing implements PricingStrategyInterface
{
    public function calculate(Order $order): float
    {
        return $order->items->sum(fn($item) => $item->price * $item->quantity);
    }
}

class DiscountedPricing implements PricingStrategyInterface
{
    public function __construct(private float $discountPercent) {}

    public function calculate(Order $order): float
    {
        $total = $order->items->sum(fn($item) => $item->price * $item->quantity);
        return $total * (1 - $this->discountPercent / 100);
    }
}

// 4. Service Layer Pattern (Business Logic)
class OrderService
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private PaymentGatewayFactory $paymentFactory,
        private PricingStrategyInterface $pricingStrategy
    ) {}

    public function createAndProcessOrder(
        User $user,
        array $items,
        string $paymentMethod
    ): Order {
        DB::beginTransaction();

        try {
            // Create order
            $order = $this->orderRepository->create([
                'user_id' => $user->id,
                'status' => 'pending',
            ]);

            // Add items
            foreach ($items as $itemData) {
                $order->items()->create($itemData);
            }

            // Calculate total with pricing strategy
            $total = $this->pricingStrategy->calculate($order);
            $this->orderRepository->update($order, ['total' => $total]);

            // Process payment with factory
            $gateway = $this->paymentFactory->create($paymentMethod);
            $result = $gateway->charge($total, 'USD');

            if ($result->success) {
                $this->orderRepository->update($order, [
                    'status' => 'paid',
                    'transaction_id' => $result->transactionId,
                ]);

                // Fire event (Observer pattern)
                event(new OrderPlaced($order));
            }

            DB::commit();
            return $order->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

// 5. Observer Pattern (Events)
class OrderPlaced
{
    public function __construct(public Order $order) {}
}

class SendOrderConfirmation
{
    public function handle(OrderPlaced $event): void
    {
        Mail::to($event->order->user->email)
            ->send(new OrderConfirmationMail($event->order));
    }
}

class UpdateInventory
{
    public function handle(OrderPlaced $event): void
    {
        foreach ($event->order->items as $item) {
            $item->product->decrement('stock', $item->quantity);
        }
    }
}

// 6. Command Pattern (Queue Jobs)
class ProcessOrderPayment implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(public Order $order) {}

    public function handle(OrderService $orderService): void
    {
        $orderService->processPayment($this->order);
    }
}

// Usage
Route::post('/orders', function (Request $request) {
    $pricingStrategy = $request->user()->isVip()
        ? new DiscountedPricing(20)
        : new StandardPricing();

    $service = new OrderService(
        app(OrderRepositoryInterface::class),
        app(PaymentGatewayFactory::class),
        $pricingStrategy
    );

    $order = $service->createAndProcessOrder(
        $request->user(),
        $request->input('items'),
        $request->input('payment_method')
    );

    return response()->json($order);
});
```

---

## Best Practices

1. **Don't Overuse Patterns**: Use patterns only when they solve a real problem. Not every piece of code needs a pattern.

2. **Start Simple**: Begin with simple solutions and refactor to patterns when complexity increases.

3. **Laravel Already Implements Many Patterns**:
   - Service Container (Dependency Injection)
   - Facades (Proxy Pattern)
   - Eloquent (Active Record Pattern)
   - Events/Listeners (Observer Pattern)
   - Middleware (Chain of Responsibility)
   - Jobs (Command Pattern)

4. **Keep It SOLID**:
   - Single Responsibility Principle
   - Open/Closed Principle
   - Liskov Substitution Principle
   - Interface Segregation Principle
   - Dependency Inversion Principle

5. **Test Your Patterns**: Design patterns should make testing easier, not harder.

6. **Document Your Patterns**: Make sure your team understands which patterns you're using and why.

---

## Quick Reference

| Pattern | Use Case | Laravel Example |
|---------|----------|-----------------|
| Singleton | Single instance needed | Service Container |
| Factory | Create objects dynamically | Payment Gateway Factory |
| Builder | Complex object construction | Query Builder |
| Repository | Data access abstraction | Repository Pattern |
| Decorator | Add functionality dynamically | Cached Repository |
| Adapter | Interface compatibility | Storage Adapters |
| Strategy | Interchangeable algorithms | Pricing Strategies |
| Observer | Event-driven notifications | Events/Listeners |
| Chain of Responsibility | Request pipeline | Middleware |
| Command | Encapsulate requests | Queue Jobs |
| Service Layer | Business logic separation | Service Classes |

---

## Further Reading

- **Gang of Four Design Patterns**: The original book on design patterns
- **Laravel Design Patterns**: [Laravel Docs](https://laravel.com/docs)
- **PHP The Right Way**: [phptherightway.com](https://phptherightway.com)
- **Refactoring Guru**: [refactoring.guru/design-patterns](https://refactoring.guru/design-patterns)
