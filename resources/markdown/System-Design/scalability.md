# Scalability & Performance

## Question 1: What are caching strategies and how do you implement them?

**Answer:**

### Caching Layers

```
┌─────────────────────────────────────┐
│  1. Browser Cache (HTTP headers)   │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│  2. CDN Cache (Static assets)       │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│  3. Application Cache (Redis/Memcached) │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│  4. Database Query Cache            │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│  5. Database                        │
└─────────────────────────────────────┘
```

### Laravel Caching

```php
// 1. Cache Forever
Cache::forever('settings', $settings);

// 2. Cache with TTL
Cache::put('user:1', $user, now()->addHours(1));

// 3. Cache::remember (cache miss = execute callback)
$users = Cache::remember('users.all', 3600, function () {
    return User::all();
});

// 4. Cache Tags (Redis/Memcached only)
Cache::tags(['users', 'admins'])->put('user:1', $user, 3600);
Cache::tags(['users'])->flush(); // Clear all user-tagged cache

// 5. Cache Lock (prevent stampede)
$users = Cache::lock('users-lock', 10)->get(function () {
    return Cache::remember('users', 3600, fn() => User::all());
});

// 6. Atomic operations
Cache::increment('page_views');
Cache::decrement('items_left');
```

### Cache Patterns

```php
// Cache-Aside Pattern
public function getUser(int $id): ?User {
    $cacheKey = "user:{$id}";

    // Try cache first
    if ($cached = Cache::get($cacheKey)) {
        return $cached;
    }

    // Cache miss - load from DB
    $user = User::find($id);

    if ($user) {
        Cache::put($cacheKey, $user, 3600);
    }

    return $user;
}

// Write-Through Pattern
public function updateUser(User $user): void {
    // Update DB
    $user->save();

    // Update cache immediately
    Cache::put("user:{$user->id}", $user, 3600);
}

// Write-Behind Pattern (async)
public function updateUser(User $user): void {
    // Update cache immediately
    Cache::put("user:{$user->id}", $user, 3600);

    // Queue DB update
    dispatch(new UpdateUserInDatabase($user));
}

// Cache Warming
Artisan::command('cache:warm', function () {
    // Pre-populate cache
    Cache::put('popular_posts', Post::popular()->get(), 3600);
    Cache::put('categories', Category::all(), 3600);
});
```

### Cache Invalidation

```php
// Model events
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

// Manual invalidation
Cache::forget('key');
Cache::flush(); // Clear all
Cache::tags(['users', 'posts'])->flush(); // Clear by tags
```

### HTTP Caching

```php
// ETags
return response($content)
    ->setEtag(md5($content))
    ->setPublic()
    ->setMaxAge(3600);

// Last-Modified
return response($content)
    ->setLastModified($post->updated_at)
    ->setPublic()
    ->setMaxAge(3600);

// Cache-Control headers
return response()
    ->header('Cache-Control', 'public, max-age=3600, s-maxage=7200');
```

### Redis for Caching

```php
// config/cache.php
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
    ],
],

// Redis-specific operations
Redis::set('key', 'value', 'EX', 3600);
Redis::get('key');
Redis::del('key');

// Redis data structures
Redis::hset('user:1', 'name', 'John');
Redis::hget('user:1', 'name');
Redis::sadd('online_users', 1, 2, 3);
Redis::smembers('online_users');
```

**Follow-up:**
- What is cache stampede and how to prevent it?
- When should you use Redis vs Memcached?
- What are cache invalidation strategies?

**Key Points:**
- Multi-layer caching (browser, CDN, application, DB)
- Cache::remember for cache-aside pattern
- Use cache tags for grouped invalidation
- Prevent stampede with locks
- HTTP caching with ETags and Last-Modified
- Invalidate cache on data changes

---

## Question 2: How do you scale a web application horizontally and vertically?

**Answer:**

### Vertical Scaling (Scale Up)

```
┌─────────────────────────────┐
│  Before:                    │
│  ┌──────────────────────┐   │
│  │  Server              │   │
│  │  4 CPU, 8GB RAM      │   │
│  └──────────────────────┘   │
└─────────────────────────────┘

┌─────────────────────────────┐
│  After:                     │
│  ┌──────────────────────┐   │
│  │  Server              │   │
│  │  16 CPU, 64GB RAM    │   │
│  └──────────────────────┘   │
└─────────────────────────────┘

Pros:
- Simple (no code changes)
- No distributed system complexity

Cons:
- Hardware limits
- Expensive at scale
- Single point of failure
```

### Horizontal Scaling (Scale Out)

```
┌─────────────────────────────────────────┐
│           Load Balancer                 │
└─────────────────────────────────────────┘
        ↓           ↓           ↓
┌─────────────┐ ┌─────────────┐ ┌─────────────┐
│  Server 1   │ │  Server 2   │ │  Server 3   │
└─────────────┘ └─────────────┘ └─────────────┘
        ↓           ↓           ↓
┌─────────────────────────────────────────┐
│         Shared Database/Cache           │
└─────────────────────────────────────────┘

Pros:
- Unlimited scaling
- High availability
- Fault tolerance

Cons:
- Requires stateless architecture
- More complex deployment
- Need load balancer
```

### Making Apps Stateless

```php
// ❌ Bad: Session in memory (sticky sessions required)
$_SESSION['user_id'] = $userId;

// ✅ Good: Session in Redis/Database (any server can handle)
// config/session.php
'driver' => 'redis',

// ✅ Good: Token-based auth (stateless)
use Laravel\Sanctum\HasApiTokens;

$token = $user->createToken('api-token')->plainTextToken;
// Send token to client
// Client includes token in header: Authorization: Bearer {token}

// ✅ File uploads to shared storage
Storage::disk('s3')->put('uploads/file.jpg', $file);
// Not: Storage::disk('local') - only on one server
```

### Load Balancing

```nginx
# Nginx Load Balancer
upstream app_servers {
    least_conn; # or ip_hash, round_robin

    server app1.example.com:80 weight=3;
    server app2.example.com:80 weight=2;
    server app3.example.com:80 weight=1 backup;
}

server {
    listen 80;

    location / {
        proxy_pass http://app_servers;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

### Database Scaling

```
Read Replicas:
┌──────────────┐
│   Primary    │ (Writes)
│   Database   │
└──────────────┘
       ↓ Replication
    ┌──┴──┬──────┐
    ↓     ↓      ↓
┌────────┐┌────────┐┌────────┐
│Replica1││Replica2││Replica3│ (Reads)
└────────┘└────────┘└────────┘
```

```php
// Laravel read/write connections
// config/database.php
'mysql' => [
    'read' => [
        'host' => ['replica1.mysql.com', 'replica2.mysql.com'],
    ],
    'write' => [
        'host' => ['primary.mysql.com'],
    ],
    'driver' => 'mysql',
    // ...
];

// Usage (automatic)
User::create([...]); // Goes to write (primary)
User::all(); // Goes to read (replica)
```

### Sharding (Horizontal Partitioning)

```
Users 1-1M    → Shard 1
Users 1M-2M   → Shard 2
Users 2M-3M   → Shard 3

// Shard by user_id
function getShardForUser(int $userId): string {
    $shardCount = 3;
    $shardId = $userId % $shardCount;
    return "shard_{$shardId}";
}

// Connect to appropriate shard
$shard = getShardForUser($userId);
DB::connection($shard)->table('users')->find($userId);
```

### Auto-Scaling

```yaml
# AWS Auto Scaling example
MinSize: 2
MaxSize: 10
DesiredCapacity: 3

ScalingPolicies:
  - CPU > 70% → Scale up
  - CPU < 30% → Scale down
  - Request count > 1000/min → Scale up
```

**Follow-up:**
- What is the difference between stateful and stateless apps?
- How do you handle file uploads in horizontally scaled apps?
- What is sticky session and why avoid it?

**Key Points:**
- Vertical: Add more CPU/RAM (simple, limited)
- Horizontal: Add more servers (scalable, complex)
- Make app stateless (sessions in Redis, files in S3)
- Load balance across servers
- Database: Read replicas for reads, primary for writes
- Auto-scale based on metrics

---

## Question 3: Explain queueing systems and async processing.

**Answer:**

### Why Use Queues?

```
Without Queue:
User Request → [Process Order + Send Email + Update Inventory] → Response
                      (Takes 5 seconds)

With Queue:
User Request → [Queue Jobs] → Immediate Response (200ms)
                  ↓
         [Background Workers Process Jobs]
```

### Laravel Queue System

```php
// Job
php artisan make:job ProcessOrder

class ProcessOrder implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(
        public Order $order
    ) {}

    public function handle() {
        // Process order
        $this->order->process();

        // Send confirmation
        Mail::to($this->order->user)->send(new OrderConfirmation());

        // Update inventory
        foreach ($this->order->items as $item) {
            $item->product->decrement('stock', $item->quantity);
        }
    }

    public function failed(Throwable $exception) {
        // Handle failure
        Log::error('Order processing failed', [
            'order' => $this->order->id,
            'error' => $exception->getMessage()
        ]);
    }
}

// Dispatch
ProcessOrder::dispatch($order);
ProcessOrder::dispatch($order)->delay(now()->addMinutes(5));
ProcessOrder::dispatch($order)->onQueue('high-priority');
```

### Queue Configurations

```php
// config/queue.php
'connections' => [
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
    ],

    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
];
```

### Queue Workers

```bash
# Start worker
php artisan queue:work

# Process specific queue
php artisan queue:work --queue=high,default,low

# Process one job then stop
php artisan queue:work --once

# Stop after current job
php artisan queue:work --stop-when-empty

# Timeout
php artisan queue:work --timeout=60

# Memory limit
php artisan queue:work --memory=512

# Supervisor config for production
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=8
user=forge
```

### Job Chaining

```php
// Sequential execution
ProcessOrder::withChain([
    new SendInvoice($order),
    new UpdateInventory($order),
    new NotifyWarehouse($order)
])->dispatch($order);

// If one fails, chain stops
```

### Job Batching

```php
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

// Process 1000s of jobs
$batch = Bus::batch([
    new ImportRow($row1),
    new ImportRow($row2),
    // ... 1000s more
])->then(function (Batch $batch) {
    // All jobs completed
    Log::info('Import complete');
})->catch(function (Batch $batch, Throwable $e) {
    // First job failure
})->finally(function (Batch $batch) {
    // Always executed
})->dispatch();

// Check progress
$batch->progress(); // Percentage
```

### Message Queues (RabbitMQ, SQS)

```
Producer → Exchange → Queue → Consumer

Benefits:
- Decoupling
- Load leveling
- Fault tolerance
- Async processing
```

### Rate Limiting

```php
use Illuminate\Support\Facades\Redis;

class ProcessApiRequest implements ShouldQueue {
    public function handle() {
        Redis::throttle('api-requests')
            ->allow(10) // 10 jobs
            ->every(60) // Per minute
            ->then(function () {
                // Process API request
            }, function () {
                // Couldn't get lock, release back to queue
                return $this->release(10);
            });
    }
}
```

**Follow-up:**
- How do you monitor queue health?
- What happens if a job fails?
- How do you prioritize jobs?

**Key Points:**
- Queues for async processing (emails, reports, API calls)
- Job chaining for sequential tasks
- Job batching for parallel processing
- Multiple queues for priorities
- Rate limiting to respect API limits
- Monitor with Horizon (Redis) or custom dashboard

---

## Question 4: How do you implement caching for high-traffic applications?

**Answer:**

### CDN for Static Assets

```
User → CDN (CloudFront, Cloudflare) → Origin Server

Benefits:
- Reduced latency (edge locations)
- Reduced server load
- DDoS protection
```

```php
// Asset URLs with CDN
// config/app.php
'asset_url' => env('ASSET_URL', 'https://cdn.example.com'),

// Usage
<link href="{{ asset('css/app.css') }}" />
// Outputs: https://cdn.example.com/css/app.css
```

### Full Page Caching

```php
// Middleware
class CacheResponse {
    public function handle($request, Closure $next) {
        $key = 'page:' . $request->url();

        if ($cached = Cache::get($key)) {
            return response($cached);
        }

        $response = $next($request);

        Cache::put($key, $response->getContent(), 300);

        return $response;
    }
}

// Or use Laravel Response Cache package
// spatie/laravel-responsecache
```

### Database Query Caching

```php
// Model-level caching
class Post extends Model {
    public static function popular() {
        return Cache::remember('posts.popular', 3600, function () {
            return static::where('views', '>', 1000)
                ->orderBy('views', 'desc')
                ->limit(10)
                ->get();
        });
    }
}

// Repository pattern with caching
class PostRepository {
    public function find(int $id): ?Post {
        return Cache::remember("post:{$id}", 3600, fn() =>
            Post::with('author', 'tags')->find($id)
        );
    }

    public function update(Post $post): void {
        $post->save();
        Cache::forget("post:{$post->id}");
        Cache::tags(['posts'])->flush();
    }
}
```

### Cache Stampede Prevention

```php
// Problem: Cache expires, 1000 concurrent requests hit DB

// Solution 1: Locks
$users = Cache::lock('users-lock', 10)->get(function () {
    return Cache::remember('users', 3600, fn() => User::all());
});

// Solution 2: Probabilistic early expiration
function remember(string $key, int $ttl, callable $callback) {
    $value = Cache::get($key);

    if ($value !== null) {
        $expiry = Cache::get($key . ':expiry');
        $delta = 60;

        // Refresh probabilistically before expiration
        if ($expiry - time() < $delta) {
            $probability = $delta / $ttl;
            if (mt_rand() / mt_getrandmax() < $probability) {
                dispatch(fn() => Cache::put($key, $callback(), $ttl));
            }
        }

        return $value;
    }

    return Cache::remember($key, $ttl, $callback);
}
```

### Multi-Level Caching

```php
class CachedUserRepository {
    // Level 1: Runtime (in-memory)
    private static $runtime = [];

    public function find(int $id): ?User {
        // Check runtime cache
        if (isset(self::$runtime[$id])) {
            return self::$runtime[$id];
        }

        // Check Redis
        $user = Cache::remember("user:{$id}", 3600, function () use ($id) {
            // Database query
            return User::find($id);
        });

        // Store in runtime
        self::$runtime[$id] = $user;

        return $user;
    }
}
```

### Cache Warming Strategy

```php
Artisan::command('cache:warm', function () {
    // Warm frequently accessed data
    $this->info('Warming cache...');

    // Settings
    Cache::forever('settings', Setting::pluck('value', 'key'));

    // Popular content
    Cache::put('popular_posts', Post::popular()->get(), 3600);

    // User-specific (for active users)
    User::where('last_login', '>', now()->subDays(7))
        ->chunk(100, function ($users) {
            foreach ($users as $user) {
                Cache::put("user:{$user->id}:feed",
                    $this->generateFeed($user),
                    1800
                );
            }
        });

    $this->info('Cache warmed!');
});

// Schedule
// app/Console/Kernel.php
$schedule->command('cache:warm')->daily();
```

**Follow-up:**
- How do you handle cache invalidation at scale?
- What is the difference between cache-aside and write-through?
- When should you use Redis vs Memcached?

**Key Points:**
- CDN for static assets
- Redis for application cache
- Full-page caching for public pages
- Prevent cache stampede with locks
- Multi-level caching (runtime → Redis → DB)
- Cache warming for predictable load
- Tag-based invalidation for grouped data

---

## Question 9: How does Apache Kafka handle messages at scale? Explain partitions and message ordering.

**Answer:**

Apache Kafka is a distributed event streaming platform capable of handling trillions of messages per day. Understanding its architecture is crucial for building scalable systems.

### Kafka Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        Kafka Cluster                             │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐             │
│  │   Broker 1  │  │   Broker 2  │  │   Broker 3  │             │
│  │  (Leader)   │  │  (Follower) │  │  (Follower) │             │
│  └─────────────┘  └─────────────┘  └─────────────┘             │
│         │                │                │                     │
│         └────────────────┼────────────────┘                     │
│                          │                                      │
│              ┌───────────┴───────────┐                          │
│              │       Topic: orders    │                          │
│              │   Partitions: 3        │                          │
│              │  ┌─────┬─────┬─────┐   │                          │
│              │  │ P0  │ P1  │ P2  │   │                          │
│              │  └─────┴─────┴─────┘   │                          │
│              │    Replication: 3      │                          │
│              └────────────────────────┘                          │
└─────────────────────────────────────────────────────────────────┘
```

### Kafka Concepts

```php
<?php
// Producer - sending messages
use Junges\Kafka\Facades\Kafka;

// Publish message
Kafka::publish()
    ->onTopic('orders')
    ->withBody([
        'event' => 'order.created',
        'order_id' => 12345,
        'customer_id' => 678,
        'total' => 99.99,
        'timestamp' => now()->toIso8601String(),
    ])
    ->send();

// Publish with key (determines partition)
Kafka::publish()
    ->onTopic('orders')
    ->withMessageKey('order-12345')  // Same key -> same partition
    ->withBody([...])
    ->send();

// Consumer - receiving messages
Kafka::consumer()
    ->subscribe('orders')
    ->withConsumerGroupId('order-processing')
    ->withHandler(function ($message) {
        $body = json_decode($message->getBody(), true);
        
        $orderId = $body['order_id'];
        $event = $body['event'];
        
        match ($event) {
            'order.created' => $this->handleCreated($body),
            'order.updated' => $this->handleUpdated($body),
            'order.cancelled' => $this->handleCancelled($body),
        };
    })
    ->build()
    ->consume();
```

### Partitions - How Kafka Handles High Throughput

```php
<?php
// Topic with multiple partitions
// orders topic with 3 partitions

// Producer chooses partition based on:
// 1. Specified partition
// 2. Key (hashed to partition) - ensures ordering per key
// 3. Round-robin (no key)

class OrderProducer
{
    // Key-based partitioning - orders from same user go to same partition
    public function publishOrderCreated(Order $order): void
    {
        Kafka::publish()
            ->onTopic('orders')
            ->withMessageKey((string) $order->user_id)  // User ID as key
            ->withBody([
                'event' => 'order.created',
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'total' => $order->total,
            ])
            ->send();
    }
    
    // Custom partitioner
    public function publishWithRegion(string $region): void
    {
        Kafka::publish()
            ->onTopic('orders')
            ->withMessageKey($region)  // Partition by region
            ->withBody(['region' => $region])
            ->send();
    }
}
```

### Partition Assignment and Rebalancing

```php
<?php
// Consumer group - partitions are divided among consumers
// Group: order-processing (3 consumers, 3 partitions)

// Partition assignment
// Consumer 1 -> Partition 0
// Consumer 2 -> Partition 1  
// Consumer 3 -> Partition 2

// If consumer fails:
// Rebalance occurs - partitions redistributed

// Handling rebalance
Kafka::consumer()
    ->subscribe('orders')
    ->withConsumerGroupId('order-processing')
    ->withHandler(function ($message) {
        // Process message
    })
    ->withOnPartitionsAssigned(function (array $partitions) {
        // Called when partitions are assigned
        Log::info("Assigned partitions: ", $partitions);
    })
    ->withOnPartitionsRevoked(function (array $partitions) {
        // Called before rebalance - commit offsets!
        $this->commitOffsets();
    })
    ->build()
    ->consume();
```

### Message Ordering Guarantees

```php
<?php
// Kafka guarantees:
// 1. Order within a partition
// 2. Per-key ordering with key-based partitioning
// 3. Not across partitions

// Example: Order processing
// Messages in partition maintain order:
// P0: order-123 created -> order-123 updated -> order-123 cancelled

// Without key, order not guaranteed:
// P0: order-456 created -> order-123 created (interleaved)

// Best practice: Use meaningful keys
class OrderEventPublisher
{
    public function publish(Order $order, string $event): void
    {
        // Key by order_id ensures all events for same order are ordered
        Kafka::publish()
            ->onTopic('order-events')
            ->withMessageKey("order-{$order->id}")  // Same order = same partition
            ->withBody([
                'event' => $event,
                'order_id' => $order->id,
                'data' => $order->toArray(),
                'timestamp' => now()->toIso8601String(),
            ])
            ->send();
    }
}

// In consumer - process in order
class OrderEventConsumer
{
    public function handle($message): void
    {
        $event = json_decode($message->getBody(), true);
        
        // Events for same order arrive in order
        // Process sequentially
        $this->processEvent($event);
    }
}
```

### Handling High Message Volume

```php
<?php
// 1. Batching - accumulate and send in batches
Kafka::publish()
    ->onTopic('orders')
    ->withMessages(function ($builder) {
        foreach ($orders as $order) {
            $builder->withMessageKey((string) $order->id)
                ->withBody(['order_id' => $order->id]);
        }
    })
    ->send();

// 2. Compression - reduce network overhead
Kafka::publish()
    ->onTopic('orders')
    ->withCompressionType(COMPRESSION_TYPE_GZIP)  // gzip, snappy, lz4, zstd
    ->withBody($largePayload)
    ->send();

// 3. Async publishing - don't wait for acknowledgment
Kafka::publish()
    ->onTopic('orders')
    ->withBody([...])
    ->withAsync()  // Fire and forget
    ->send();

// 4. Exactly-once semantics (EOS)
Kafka::publish()
    ->onTopic('orders')
    ->withTransactionalId('order-producer-1')  // Idempotent producer
    ->withBody([...])
    ->send();

// Consumer - read with offset management
Kafka::consumer()
    ->subscribe('orders')
    ->withAutoCommit()  // Or manual
    ->withHandler(function ($message) {
        // Process
        $this->process($message);
        
        // Or commit manually
        // $message->getCommit();
    })
    ->build()
    ->consume();
```

### Partition Strategy

```php
<?php
// Choosing partition count
// Rule of thumb: 3-10x number of brokers

// Factors:
// - Throughput: More partitions = more parallelism
// - Latency: More partitions = lower latency
// - Replication: More partitions = more overhead

// Partition assignment strategies:
// 1. By key (user_id, order_id)
// 2. By time (hourly partitions for time-series)
// 3. By category (product categories)

// Time-based partitioning for analytics
class AnalyticsPublisher
{
    public function publishEvent(array $event): void
    {
        $hour = now()->format('Y-m-d-H');
        
        Kafka::publish()
            ->onTopic("events-{$hour}")  // Hourly topics
            ->withMessageKey($event['id'])
            ->withBody($event)
            ->send();
    }
}
```

### Replication and Fault Tolerance

```php
<?php
// Replication factor: 3 (default recommended)
// acks: acknowledgment level

// acks=0: Fire and forget (fastest, least durable)
// acks=1: Leader acknowledges (default)
// acks=all: Wait for all replicas (most durable)

Kafka::publish()
    ->onTopic('orders')
    ->withAcks(ACKS_ALL)  // Wait for all replicas
    ->withBody([...])
    ->send();

// Consumer offset management
// earliest: Read from beginning
// latest: Read only new messages
// specific: Start from specific offset

Kafka::consumer()
    ->subscribe('orders')
    ->withAutoOffsetReset(AUTO_OFFSET_RESET_EARLIEST)  // Start from beginning
    // or AUTO_OFFSET_RESET_LATEST
    ->build()
    ->consume();
```

### Performance Tuning

```php
<?php
// Producer tuning
$config = [
    'bootstrap.servers' => 'kafka1:9092,kafka2:9093',
    'acks' => 'all',
    'retries' => 3,
    'batch.size' => 16384,  // Batch size in bytes
    'linger.ms' => 5,  // Wait up to 5ms for batching
    'buffer.memory' => 33554432,  // 32MB buffer
    'compression.type' => 'lz4',
];

// Consumer tuning
$consumerConfig = [
    'group.id' => 'order-processing',
    'fetch.min.bytes' => 1,
    'fetch.max.wait.ms' => 500,
    'max.poll.records' => 500,
    'enable.auto.commit' => false,
];
```

### When to Use Kafka

```php
<?php
// ✅ Good use cases:
// - Event-driven architecture
// - Activity tracking / analytics
// - Log aggregation
// - Real-time streaming
// - Change Data Capture (CDC)
// - Event sourcing

// ✅ In Laravel:
// - Process orders asynchronously
// - Sync data between microservices
// - Build audit logs
// - Analytics pipeline

// ❌ Don't use Kafka for:
// - Simple job queues (use Redis/Bull)
// - Request/response patterns
// - Low latency requirements (< 10ms)
// - Small message volumes
```

**Key Points:**
- Topic: Logical channel for messages
- Partition: Unit of parallelism, ordered within partition
- Producer: Publishes to partitions (key-based or round-robin)
- Consumer group: Coordinates partition assignment
- Ordering guaranteed within partition
- Use meaningful keys for per-entity ordering
- Replication factor: 3 for production
- acks=all for durability
- Partitions: 3-10x brokers for throughput
- Compress messages for large volumes

---

## Notes

Add more questions covering:
- Database connection pooling
- Database indexing strategies
- Content Delivery Networks (CDN)
- WebSocket scaling
- Monitoring and observability
- Circuit breakers and retries
- Service mesh (Istio, Linkerd)
- Container orchestration (Kubernetes)
