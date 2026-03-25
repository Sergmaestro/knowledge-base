# PHP Performance Optimization

## Question 1: What are opcode caching and how does it improve PHP performance?

**Answer:**

Opcode caching stores compiled PHP bytecode in memory, avoiding recompilation on every request.

**How PHP execution works:**
```
1. Parse PHP source code
2. Compile to opcode (bytecode)
3. Execute opcode
```

**Without opcode cache:** Steps 1-3 happen on every request
**With opcode cache:** Steps 1-2 happen once, only step 3 repeats

**OPcache Configuration:**
```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1

; Development
opcache.validate_timestamps=1  ; Check for file changes

; Production
opcache.validate_timestamps=0  ; Never check (max performance)
opcache.preload=/path/to/preload.php  ; PHP 7.4+ preloading
```

**Preloading (PHP 7.4+):**
```php
// preload.php - Load frequently used classes into memory
opcache_compile_file(__DIR__ . '/vendor/symfony/http-kernel/Kernel.php');
opcache_compile_file(__DIR__ . '/vendor/laravel/framework/src/Illuminate/Foundation/Application.php');

// Or use Composer
require_once __DIR__ . '/vendor/autoload.php';
$files = require __DIR__ . '/vendor/composer/autoload_classmap.php';
foreach ($files as $file) {
    opcache_compile_file($file);
}
```

**Monitoring:**
```php
// Check OPcache status
$status = opcache_get_status();
echo "Hits: " . $status['opcache_statistics']['hits'];
echo "Misses: " . $status['opcache_statistics']['misses'];
echo "Memory Usage: " . $status['memory_usage']['used_memory'];
```

**Follow-up:**
- What is the difference between OPcache and APCu?
- How do you invalidate OPcache in production?

**Key Points:**
- OPcache is essential in production
- Preloading (PHP 7.4+) loads classes at startup
- Disable timestamp validation in production
- Can improve performance by 2-3x

---

## Question 2: How do you optimize database queries in PHP applications?

**Answer:**

### 1. N+1 Query Problem

```php
// Bad: N+1 queries
$users = User::all();  // 1 query
foreach ($users as $user) {
    echo $user->profile->bio;  // N queries (one per user)
}

// Good: Eager loading
$users = User::with('profile')->get();  // 2 queries total
foreach ($users as $user) {
    echo $user->profile->bio;
}

// Better: Select only needed columns
$users = User::with('profile:id,user_id,bio')->get(['id', 'name']);
```

### 2. Proper Indexing

```php
// Add indexes for frequently queried columns
Schema::table('users', function (Blueprint $table) {
    $table->index('email');
    $table->index('status');
    $table->index(['status', 'created_at']);  // Composite index
});

// Check slow queries
DB::listen(function ($query) {
    if ($query->time > 1000) {  // > 1 second
        Log::warning('Slow query', [
            'sql' => $query->sql,
            'time' => $query->time
        ]);
    }
});
```

### 3. Query Optimization

```php
// Bad: Loading entire table
$users = DB::table('users')->get();

// Good: Pagination
$users = DB::table('users')->paginate(20);

// Good: Chunking for large datasets
DB::table('users')->orderBy('id')->chunk(100, function ($users) {
    foreach ($users as $user) {
        // Process user
    }
});

// Good: Cursor for memory efficiency
foreach (DB::table('users')->cursor() as $user) {
    // Process one user at a time
}
```

### 4. Caching

```php
// Query caching
$users = Cache::remember('users.active', 3600, function () {
    return User::where('active', true)->get();
});

// Cache tags (Redis, Memcached)
Cache::tags(['users'])->remember('users.all', 3600, function () {
    return User::all();
});

// Invalidate when data changes
Cache::tags(['users'])->flush();

// Model caching
class User extends Model {
    public function getPopularPostsAttribute() {
        return Cache::remember(
            "user.{$this->id}.popular_posts",
            3600,
            fn() => $this->posts()->where('views', '>', 1000)->get()
        );
    }
}
```

### 5. Raw Queries for Complex Operations

```php
// Use raw queries when Eloquent is inefficient
DB::statement('
    UPDATE posts p
    JOIN users u ON p.user_id = u.id
    SET p.author_name = u.name
    WHERE u.updated_at > ?
', [now()->subDay()]);

// Bulk inserts
DB::table('logs')->insert([
    ['message' => 'Log 1', 'created_at' => now()],
    ['message' => 'Log 2', 'created_at' => now()],
    // ... 1000s of rows
]);
```

### 6. Connection Pooling

```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST'),
    'port' => env('DB_PORT'),
    'database' => env('DB_DATABASE'),
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
    'options' => [
        PDO::ATTR_PERSISTENT => true,  // Connection pooling
    ],
],
```

**Follow-up:**
- How do you identify slow queries?
- What is query result caching vs query caching?
- Explain database connection pooling

**Key Points:**
- Eager load relationships (avoid N+1)
- Index frequently queried columns
- Use pagination and chunking
- Cache query results
- Select only needed columns

---

## Question 3: What are PHP best practices for memory optimization?

**Answer:**

### 1. Unset Variables

```php
// Free memory for large variables
$largeArray = range(1, 1000000);
// ... process data
unset($largeArray);  // Free memory immediately

// Especially important in loops
foreach ($data as $item) {
    $processed = heavyProcessing($item);
    // Use $processed
    unset($processed);  // Free before next iteration
}
```

### 2. Generators for Large Datasets

```php
// Bad: Loads everything into memory
function getAllUsers() {
    return User::all()->toArray();  // Loads 100k users
}

// Good: Generator - one at a time
function getAllUsers(): Generator {
    foreach (User::cursor() as $user) {
        yield $user;
    }
}

// Usage
foreach (getAllUsers() as $user) {
    processUser($user);
}
```

### 3. Streaming Large Files

```php
// Bad: Loads entire file
$contents = file_get_contents('large-file.csv');  // 2GB file = 2GB memory

// Good: Stream line by line
$handle = fopen('large-file.csv', 'r');
while (($line = fgets($handle)) !== false) {
    processLine($line);
}
fclose($handle);

// Better: Using SplFileObject
$file = new SplFileObject('large-file.csv');
foreach ($file as $line) {
    processLine($line);
}
```

### 4. Avoid Memory Leaks

```php
// Bad: Circular references cause leaks
class Parent {
    public $children = [];

    public function addChild(Child $child) {
        $this->children[] = $child;
        $child->parent = $this;  // Circular reference
    }
}

// Good: Break circular references
class Parent {
    public function __destruct() {
        foreach ($this->children as $child) {
            $child->parent = null;  // Break reference
        }
    }
}

// Or use WeakMap (PHP 8.0+)
$weakMap = new WeakMap();
$weakMap[$object] = $data;  // Auto-freed when $object destroyed
```

### 5. Optimize Arrays

```php
// Pre-allocate arrays when size is known
$items = array_fill(0, 1000, null);

// Use array functions instead of loops
// Bad
$result = [];
foreach ($array as $item) {
    if ($item > 10) {
        $result[] = $item * 2;
    }
}

// Good: More efficient
$result = array_map(
    fn($x) => $x * 2,
    array_filter($array, fn($x) => $x > 10)
);
```

### 6. Limit Query Results

```php
// Select only needed columns
User::select(['id', 'name', 'email'])->get();

// Instead of
User::all();  // Loads all columns

// Use pagination
User::paginate(50);  // Load 50 at a time

// Use lazy collections (Laravel 6+)
User::lazy()->each(function ($user) {
    // Loads chunks automatically
});
```

### 7. Monitor Memory Usage

```php
// Track memory usage
echo memory_get_usage() / 1024 / 1024 . " MB\n";
echo memory_get_peak_usage() / 1024 / 1024 . " MB (peak)\n";

// Set memory limit
ini_set('memory_limit', '256M');

// Wrapper for memory profiling
function profileMemory(callable $fn) {
    $start = memory_get_usage();
    $result = $fn();
    $end = memory_get_usage();
    echo "Memory used: " . ($end - $start) / 1024 / 1024 . " MB\n";
    return $result;
}
```

**Follow-up:**
- What causes memory leaks in PHP?
- How does PHP's garbage collector work?
- What is copy-on-write optimization?

**Key Points:**
- Use generators for large datasets
- Unset large variables after use
- Stream files, don't load entirely
- Avoid circular references
- Select only needed data

---

## Question 4: How do you implement effective caching strategies?

**Answer:**

### 1. Cache Layers

```php
// Multi-layer caching
class CachedRepository {
    public function find(int $id): ?User {
        // Layer 1: Runtime cache (in-memory)
        static $runtimeCache = [];
        if (isset($runtimeCache[$id])) {
            return $runtimeCache[$id];
        }

        // Layer 2: Application cache (Redis/Memcached)
        $user = Cache::remember("user:{$id}", 3600, function () use ($id) {
            // Layer 3: Database query cache
            return User::find($id);
        });

        $runtimeCache[$id] = $user;
        return $user;
    }
}
```

### 2. Cache Invalidation

```php
// Event-based invalidation
class User extends Model {
    protected static function booted() {
        static::updated(function ($user) {
            Cache::forget("user:{$user->id}");
            Cache::tags(['users'])->flush();
        });

        static::deleted(function ($user) {
            Cache::forget("user:{$user->id}");
        });
    }
}

// Manual cache busting
Cache::flush();  // Clear all
Cache::forget('key');  // Clear specific
Cache::tags(['users', 'posts'])->flush();  // Clear by tags
```

### 3. Cache Stampede Prevention

```php
// Problem: Cache expires, multiple requests hit database
// Solution: Locking

use Illuminate\Support\Facades\Cache;

$users = Cache::lock('users-lock', 10)->get(function () {
    return Cache::remember('users', 3600, function () {
        return User::all();  // Only one process executes this
    });
});

// Or: Probabilistic early expiration
class Cache {
    public function get(string $key, int $ttl, callable $callback) {
        $data = Redis::get($key);

        if ($data !== null) {
            // Recompute probabilistically before expiration
            $expiry = Redis::ttl($key);
            $delta = 60; // seconds
            $probability = $delta / $ttl;

            if ($expiry < $delta && rand(0, 100) < $probability * 100) {
                // Refresh asynchronously
                dispatch(fn() => $this->refresh($key, $ttl, $callback));
            }

            return unserialize($data);
        }

        return $this->refresh($key, $ttl, $callback);
    }
}
```

### 4. Cache Warming

```php
// Pre-populate cache
Artisan::command('cache:warm', function () {
    $this->info('Warming cache...');

    Cache::forever('settings', Settings::all());
    Cache::forever('categories', Category::all());

    User::chunk(100, function ($users) {
        foreach ($users as $user) {
            Cache::put("user:{$user->id}", $user, 3600);
        }
    });

    $this->info('Cache warmed!');
});
```

### 5. Partial Caching

```php
// Cache expensive computations, not entire response
class ProductController {
    public function show(Product $product) {
        // Cache only the expensive part
        $relatedProducts = Cache::remember(
            "product:{$product->id}:related",
            3600,
            fn() => $this->getRelatedProducts($product)
        );

        return view('product.show', [
            'product' => $product,  // Fresh data
            'related' => $relatedProducts  // Cached data
        ]);
    }
}
```

### 6. Cache Aside Pattern

```php
class UserRepository {
    public function find(int $id): ?User {
        // Try cache first
        $user = Cache::get("user:{$id}");

        if ($user === null) {
            // Cache miss - load from database
            $user = User::find($id);

            if ($user) {
                // Populate cache
                Cache::put("user:{$id}", $user, 3600);
            }
        }

        return $user;
    }

    public function save(User $user): void {
        $user->save();

        // Update cache
        Cache::put("user:{$user->id}", $user, 3600);
    }
}
```

### 7. CDN and Static Asset Caching

```php
// Generate versioned URLs
<link href="{{ asset('css/app.css') }}?v={{ config('app.version') }}" />

// Or use Laravel Mix versioning
<link href="{{ mix('css/app.css') }}" />  // Generates hash

// HTTP caching headers
return response($content)
    ->header('Cache-Control', 'public, max-age=3600')
    ->header('ETag', md5($content));
```

**Follow-up:**
- What is cache stampede and how to prevent it?
- Explain write-through vs write-back caching
- When would you use Redis vs Memcached?

**Key Points:**
- Multi-layer caching (runtime, Redis, DB)
- Invalidate cache on data changes
- Prevent cache stampede with locks
- Warm cache for frequently accessed data
- Use tags for group invalidation

---

## Question 5: What are async processing techniques in PHP?

**Answer:**

### 1. Queue Jobs

```php
// Job class
class ProcessOrder implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(
        private Order $order
    ) {}

    public function handle(): void {
        // Long-running task
        $this->order->process();
        $this->order->sendConfirmation();
    }

    public function failed(Throwable $exception): void {
        // Handle failure
        Log::error('Order processing failed', [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage()
        ]);
    }
}

// Dispatch job
ProcessOrder::dispatch($order);

// Delayed execution
ProcessOrder::dispatch($order)->delay(now()->addMinutes(5));

// Specific queue
ProcessOrder::dispatch($order)->onQueue('high-priority');

// Chain jobs
ProcessOrder::withChain([
    new SendInvoice($order),
    new UpdateInventory($order)
])->dispatch($order);
```

### 2. Event-Driven Architecture

```php
// Event
class OrderPlaced {
    public function __construct(
        public Order $order
    ) {}
}

// Listeners
class SendOrderConfirmation implements ShouldQueue {
    public function handle(OrderPlaced $event): void {
        Mail::to($event->order->user)->send(new OrderConfirmationMail($event->order));
    }
}

class UpdateInventory implements ShouldQueue {
    public function handle(OrderPlaced $event): void {
        // Update inventory
    }
}

// Dispatch event
event(new OrderPlaced($order));

// Multiple listeners run asynchronously
```

### 3. Parallel Processing with Amphp/ReactPHP

```php
use Amp\Promise;
use Amp\Loop;

// Run multiple async operations
$promises = [];
foreach ($users as $user) {
    $promises[] = async_http_request("https://api.example.com/user/{$user->id}");
}

// Wait for all to complete
$results = Promise\wait(Promise\all($promises));
```

### 4. Deferred Tasks

```php
// After response sent to user
app()->terminating(function () {
    // User already received response, now do cleanup
    Log::info('Processing after response');
    Cache::cleanup();
});

// Using fastcgi_finish_request()
public function store(Request $request) {
    $order = Order::create($request->all());

    // Send response immediately
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

    // Continue processing after response sent
    $this->processOrder($order);
    $this->sendNotifications($order);

    return response()->json($order);
}
```

### 5. Horizon for Queue Management

```php
// config/horizon.php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default', 'high', 'low'],
            'balance' => 'auto',
            'processes' => 10,
            'tries' => 3,
            'timeout' => 300,
        ],
    ],
],
```

**Follow-up:**
- How do you handle job failures and retries?
- What's the difference between queues and events?
- How do you monitor queue performance?

**Key Points:**
- Queues offload slow tasks (emails, reports)
- Events for loosely coupled actions
- Horizon for monitoring/scaling queues
- Always queue: emails, API calls, file processing
- Use job chaining for dependent tasks

---

---

## Question 6: How does PHP's garbage collector work, and when does it actually run?

**Answer:**

PHP uses a reference-counting garbage collector with cycle detection for managing memory.

### How Reference Counting Works

```php
$a = "Hello";     // refcount = 1
$b = $a;          // refcount = 2
$c = $a;          // refcount = 3
unset($b);        // refcount = 2
unset($c);        // refcount = 1
unset($a);        // refcount = 0 → memory freed immediately
```

### Circular Reference Problem

```php
// Circular references aren't freed by simple refcounting
class Node {
    public $data;
    public $next;
}

$a = new Node();
$b = new Node();

$a->next = $b;  // $b refcount = 2
$b->next = $a;  // $a refcount = 2

unset($a);  // refcount = 1 (still referenced by $b->next)
unset($b);  // refcount = 1 (still referenced by $a->next)

// Memory leak! Objects reference each other but are unreachable
// This is where cycle GC comes in
```

### Cycle Collector

```php
// PHP's cycle collector runs when:
// 1. Root buffer reaches threshold (default 10,000 items)
// 2. gc_collect_cycles() is called manually

$before = memory_get_usage();

// Create circular references
for ($i = 0; $i < 10000; $i++) {
    $a = new Node();
    $b = new Node();
    $a->next = $b;
    $b->next = $a;
}

$after = memory_get_usage();
echo "Memory used: " . ($after - $before) . "\n";

// Trigger garbage collection
gc_collect_cycles();

$afterGc = memory_get_usage();
echo "Memory after GC: " . ($afterGc - $before) . "\n";
```

### Garbage Collector Configuration

```php
// Check GC status
var_dump(gc_enabled());  // true/false

// Enable/disable
gc_enable();
gc_disable();

// Manually trigger collection
$collected = gc_collect_cycles();
echo "Collected {$collected} cycles\n";

// GC statistics
$stats = gc_status();
print_r($stats);
/*
Array (
    [runs] => 12
    [collected] => 245
    [threshold] => 10001
    [roots] => 0
)
*/

// Configuration in php.ini
zend.enable_gc = 1
```

### When GC Runs

```php
// GC triggers automatically when:
// - Root buffer reaches ~10,000 potential garbage items
// - During script shutdown
// - When gc_collect_cycles() is called

// Long-running script example
class Task {
    private array $cache = [];

    public function processLargeDataset() {
        for ($i = 0; $i < 1000000; $i++) {
            $item = $this->createComplexObject();
            $this->process($item);

            // Without GC, memory grows continuously
            // Force GC every 10,000 iterations
            if ($i % 10000 === 0) {
                gc_collect_cycles();
                echo "Memory: " . memory_get_usage() . "\n";
            }
        }
    }
}
```

### Common Causes of Memory Leaks

```php
// 1. Circular references in closures
class EventManager {
    private array $listeners = [];

    public function addListener(callable $callback) {
        // Circular reference if callback references $this
        $this->listeners[] = $callback;
    }
}

$manager = new EventManager();
$manager->addListener(function() use ($manager) {
    // Circular reference: closure → $manager → listeners → closure
    $manager->doSomething();
});

// 2. Static properties
class Cache {
    private static array $data = [];  // Never freed until script ends

    public static function store($key, $value) {
        self::$data[$key] = $value;
    }
}

// 3. Global variables
$GLOBALS['cache'] = [];  // Never freed

// 4. Resource leaks
$fp = fopen('file.txt', 'r');
// ... use file
// fclose($fp);  // Forgot to close!

// 5. Event listeners not removed
class Subscriber {
    public function __construct(EventDispatcher $dispatcher) {
        $dispatcher->on('event', [$this, 'handle']);
        // Never removed, keeps $this alive
    }
}
```

### Best Practices

```php
// Break circular references manually
class Node {
    public $next;

    public function __destruct() {
        $this->next = null;  // Break cycle
    }
}

// Use WeakMap for cache (PHP 8.0+)
$cache = new WeakMap();
$object = new stdClass();
$cache[$object] = $data;  // Automatically removed when $object is destroyed

// Unset large variables after use
function processLargeFile($path) {
    $data = file_get_contents($path);  // Load 100MB file
    $result = process($data);
    unset($data);  // Free memory immediately

    gc_collect_cycles();  // Force GC if needed

    return $result;
}

// Close resources explicitly
function readFile($path) {
    $handle = fopen($path, 'r');
    try {
        return fgets($handle);
    } finally {
        fclose($handle);  // Always closed
    }
}

// Monitor memory in long-running processes
while (true) {
    processJob();

    // Check memory and GC periodically
    if (memory_get_usage() > 100 * 1024 * 1024) {  // > 100MB
        gc_collect_cycles();

        if (memory_get_usage() > 200 * 1024 * 1024) {  // Still > 200MB
            Log::warning('High memory usage');
        }
    }
}
```

### Debugging Memory Leaks

```php
// Track memory allocation
function trackMemory(callable $fn, string $label) {
    $before = memory_get_usage();
    $fn();
    $after = memory_get_usage();

    echo "{$label}: " . ($after - $before) / 1024 / 1024 . " MB\n";
}

trackMemory(function() {
    $users = User::all();
}, 'Load all users');

// Use Xdebug profiler
// php.ini:
// xdebug.profiler_enable = 1
// xdebug.profiler_output_dir = /tmp

// Analyze with tools like:
// - Xdebug profiler
// - Blackfire
// - Tideways
// - php-meminfo extension
```

**Follow-up:**
- What is copy-on-write optimization in PHP?
- How does WeakMap work?
- When should you manually call gc_collect_cycles()?

**Key Points:**
- PHP uses reference counting + cycle detection
- GC runs automatically when root buffer reaches ~10k
- Circular references cause leaks without cycle GC
- WeakMap (PHP 8.0+) prevents reference leaks
- Unset large variables in long-running scripts
- Monitor memory in workers/daemons

---

## Notes

Add more questions covering:
- HTTP/2 and HTTP/3 optimization
- PHP-FPM tuning
- Profiling with Xdebug and Blackfire
- Load balancing strategies
- Session handling at scale
- Asset optimization (minification, bundling)
