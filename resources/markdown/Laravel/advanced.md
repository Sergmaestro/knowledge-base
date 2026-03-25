# Laravel Advanced Features

## Question 1: Explain Laravel Queues and Job processing.

**Answer:**

Queues defer time-consuming tasks (emails, API calls, reports) to background processing.

### Creating Jobs

```php
php artisan make:job ProcessOrder

// app/Jobs/ProcessOrder.php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessOrder implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 3;
    public $backoff = [60, 120, 300];  // Wait before retries

    public function __construct(
        public Order $order
    ) {}

    public function handle() {
        // Process order
        $this->order->process();
        $this->order->sendConfirmation();
    }

    public function failed(Throwable $exception) {
        // Handle failure
        Log::error('Order processing failed', [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage()
        ]);
    }
}
```

### Dispatching Jobs

```php
// Dispatch immediately
ProcessOrder::dispatch($order);

// Delayed execution
ProcessOrder::dispatch($order)->delay(now()->addMinutes(5));

// Specific queue
ProcessOrder::dispatch($order)->onQueue('high-priority');

// Specific connection
ProcessOrder::dispatch($order)->onConnection('redis');

// Chain jobs (sequential)
ProcessOrder::withChain([
    new SendInvoice($order),
    new UpdateInventory($order)
])->dispatch($order);

// Dispatch after response
ProcessOrder::dispatchAfterResponse($order);
```

### Queue Configuration

```php
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
],

// .env
QUEUE_CONNECTION=redis
```

### Running Queue Workers

```bash
# Process jobs
php artisan queue:work

# Specific queue
php artisan queue:work --queue=high,default,low

# Timeout and memory limits
php artisan queue:work --timeout=60 --memory=512

# Process one job and stop
php artisan queue:work --once

# Stop after processing current job
php artisan queue:work --stop-when-empty
```

### Job Batching

```php
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

$batch = Bus::batch([
    new ProcessCSVRow($row1),
    new ProcessCSVRow($row2),
    // ... 1000 jobs
])->then(function (Batch $batch) {
    // All jobs completed successfully
})->catch(function (Batch $batch, Throwable $e) {
    // First failure detected
})->finally(function (Batch $batch) {
    // Batch finished executing
})->dispatch();

// Check batch status
$batch = Bus::findBatch($batchId);
$batch->progress();  // Percentage
$batch->finished();  // All jobs done
```

### Rate Limiting

```php
use Illuminate\Support\Facades\Redis;

class ProcessOrder implements ShouldQueue {
    public function handle() {
        Redis::throttle('process-orders')
            ->allow(10)  // 10 jobs
            ->every(60)  // Per 60 seconds
            ->then(function () {
                // Process order
            }, function () {
                // Cannot get lock, release back to queue
                return $this->release(10);
            });
    }
}
```

**Follow-up:**
- How do you monitor queue performance?
- What's the difference between sync and async queues?
- How do you handle job failures?

**Key Points:**
- Queue time-consuming tasks
- Dispatch with `::dispatch()` or `dispatch()`
- Configure retries, timeouts, backoff
- Use chains for sequential jobs
- Use batches for parallel processing
- Monitor with Horizon (Redis) or queue:work

---

## Question 2: Explain Laravel Events and Listeners.

**Answer:**

Events provide a simple observer pattern implementation for decoupling application components.

### Creating Events and Listeners

```php
php artisan make:event OrderShipped
php artisan make:listener SendShipmentNotification --event=OrderShipped

// app/Events/OrderShipped.php
class OrderShipped {
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order
    ) {}
}

// app/Listeners/SendShipmentNotification.php
class SendShipmentNotification {
    public function handle(OrderShipped $event) {
        Mail::to($event->order->user)->send(
            new OrderShippedMail($event->order)
        );
    }
}
```

### Registering Events

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    OrderShipped::class => [
        SendShipmentNotification::class,
        UpdateInventory::class,
        LogShipment::class,
    ],
];

// Or auto-discover
public function shouldDiscoverEvents() {
    return true;  // Auto-discover listeners in app/Listeners
}
```

### Dispatching Events

```php
// Dispatch event
OrderShipped::dispatch($order);

// Or
event(new OrderShipped($order));

// Conditional dispatch
OrderShipped::dispatchIf($condition, $order);
OrderShipped::dispatchUnless($condition, $order);
```

### Queued Listeners

```php
class SendShipmentNotification implements ShouldQueue {
    use InteractsWithQueue;

    public $queue = 'emails';
    public $delay = 60;

    public function handle(OrderShipped $event) {
        // Runs in queue
    }

    public function failed(OrderShipped $event, Throwable $exception) {
        // Handle failure
    }
}
```

### Event Subscribers

```php
// Subscribe to multiple events
class UserEventSubscriber {
    public function handleUserLogin($event) {}
    public function handleUserLogout($event) {}

    public function subscribe($events) {
        $events->listen(
            UserLogin::class,
            [UserEventSubscriber::class, 'handleUserLogin']
        );

        $events->listen(
            UserLogout::class,
            [UserEventSubscriber::class, 'handleUserLogout']
        );
    }
}

// Register
protected $subscribe = [
    UserEventSubscriber::class,
];
```

**Follow-up:**
- When should you use events vs jobs?
- How do you test events?
- What's the difference between synchronous and queued listeners?

**Key Points:**
- Events decouple application components
- Multiple listeners per event
- Queue listeners for slow operations
- Use for: notifications, logging, analytics
- Test with `Event::fake()`

---

## Question 3: What is Laravel Broadcasting and real-time features?

**Answer:**

Broadcasting allows server-side events to push to client-side JavaScript in real-time.

### Setup

```php
// Install Pusher or Laravel WebSockets
composer require pusher/pusher-php-server

// config/broadcasting.php
'connections' => [
    'pusher' => [
        'driver' => 'pusher',
        'key' => env('PUSHER_APP_KEY'),
        'secret' => env('PUSHER_APP_SECRET'),
        'app_id' => env('PUSHER_APP_ID'),
        'options' => [
            'cluster' => env('PUSHER_APP_CLUSTER'),
            'encrypted' => true,
        ],
    ],
],
```

### Broadcasting Events

```php
// Event must implement ShouldBroadcast
class OrderShipped implements ShouldBroadcast {
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    public function __construct(Order $order) {
        $this->order = $order;
    }

    // Channel to broadcast on
    public function broadcastOn() {
        return new PrivateChannel('orders.' . $this->order->user_id);
    }

    // Event name
    public function broadcastAs() {
        return 'order.shipped';
    }

    // Data to broadcast
    public function broadcastWith() {
        return [
            'id' => $this->order->id,
            'tracking_number' => $this->order->tracking_number
        ];
    }
}

// Dispatch
event(new OrderShipped($order));
```

### Channel Authorization

```php
// routes/channels.php
Broadcast::channel('orders.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Public channel
Broadcast::channel('public-updates', function () {
    return true;
});
```

### Client-Side (Laravel Echo)

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    encrypted: true
});

// Listen to private channel
Echo.private(`orders.${userId}`)
    .listen('.order.shipped', (e) => {
        console.log('Order shipped:', e);
        showNotification(`Order #${e.id} has been shipped!`);
    });

// Public channel
Echo.channel('public-updates')
    .listen('SystemUpdate', (e) => {
        console.log(e.message);
    });

// Presence channel (see who's online)
Echo.join(`chat.${roomId}`)
    .here((users) => {
        // Users currently in room
    })
    .joining((user) => {
        console.log(user.name + ' joined');
    })
    .leaving((user) => {
        console.log(user.name + ' left');
    });
```

### Notifications

```php
// Broadcasting via notifications
class OrderShipped extends Notification implements ShouldBroadcast {
    public function via($notifiable) {
        return ['broadcast', 'database'];
    }

    public function toBroadcast($notifiable) {
        return new BroadcastMessage([
            'order_id' => $this->order->id,
            'message' => 'Your order has shipped!'
        ]);
    }
}

// Listen
Echo.private(`App.Models.User.${userId}`)
    .notification((notification) => {
        console.log(notification);
    });
```

**Key Points:**
- Broadcasting pushes server events to clients
- Use Pusher or Laravel WebSockets
- Private channels require authorization
- Listen with Laravel Echo on frontend
- Good for: notifications, chat, live updates

---

## Question 4: Explain Laravel Task Scheduling.

**Answer:**

Laravel's scheduler allows expressive command scheduling within PHP instead of cron.

### Defining Schedule

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule) {
    // Run daily at midnight
    $schedule->command('emails:send')->daily();

    // Run hourly
    $schedule->command('reports:generate')->hourly();

    // Custom time
    $schedule->command('backup:run')->dailyAt('02:00');

    // Multiple times per day
    $schedule->command('cache:clear')
        ->dailyAt('00:00')
        ->dailyAt('12:00');

    // Cron expression
    $schedule->command('emails:send')->cron('0 */6 * * *');

    // Closures
    $schedule->call(function () {
        DB::table('recent_users')->delete();
    })->daily();

    // Jobs
    $schedule->job(new ProcessPendingOrders)->everyFiveMinutes();
}
```

### Schedule Frequencies

```php
->everyMinute();
->everyFiveMinutes();
->everyTenMinutes();
->everyFifteenMinutes();
->everyThirtyMinutes();
->hourly();
->hourlyAt(17);  // :17 past every hour
->daily();
->dailyAt('13:00');
->twiceDaily(1, 13);  // 1:00 and 13:00
->weekly();
->weeklyOn(1, '8:00');  // Monday at 8:00
->monthly();
->monthlyOn(4, '15:00');  // 4th of month at 15:00
->quarterly();
->yearly();
->timezone('America/New_York');
```

### Constraints

```php
// Only on weekdays
$schedule->command('emails:send')
    ->weekdays()
    ->at('09:00');

// Only on weekends
$schedule->command('reports:generate')
    ->weekends();

// Between times
$schedule->command('backup:run')
    ->daily()
    ->between('23:00', '05:00');

// Unless between times
$schedule->command('maintenance')
    ->daily()
    ->unlessBetween('08:00', '17:00');

// Custom conditions
$schedule->command('emails:send')
    ->daily()
    ->when(function () {
        return date('d') <= 15;  // First half of month
    });

// Skip if condition
$schedule->command('emails:send')
    ->daily()
    ->skip(function () {
        return Holiday::isToday();
    });
```

### Preventing Overlaps

```php
// Don't run if previous instance still running
$schedule->command('process:data')
    ->everyMinute()
    ->withoutOverlapping();

// Custom expiration
$schedule->command('process:data')
    ->everyMinute()
    ->withoutOverlapping(10);  // Lock expires after 10 minutes
```

### Task Output

```php
// Log output
$schedule->command('backup:run')
    ->daily()
    ->sendOutputTo(storage_path('logs/backup.log'));

// Append to file
$schedule->command('backup:run')
    ->daily()
    ->appendOutputTo(storage_path('logs/backup.log'));

// Email output
$schedule->command('reports:generate')
    ->daily()
    ->emailOutputTo('admin@example.com');
```

### Hooks

```php
$schedule->command('emails:send')
    ->daily()
    ->before(function () {
        // Before task runs
    })
    ->after(function () {
        // After task completes
    })
    ->onSuccess(function () {
        // If successful
    })
    ->onFailure(function () {
        // If failed
    })
    ->pingBefore('https://monitor.com/start')
    ->thenPing('https://monitor.com/end');
```

### Running Scheduler

```bash
# Add single cron entry
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1

# Test schedule
php artisan schedule:list
php artisan schedule:test --name="emails:send"
```

**Key Points:**
- Define schedules in `app/Console/Kernel.php`
- Expressive API for cron expressions
- Prevent overlaps with `withoutOverlapping()`
- Add hooks for before/after/success/failure
- One cron entry runs all scheduled tasks

---

---

## Question 6: How do you apply security practices to protect a Laravel application?

**Answer:**

See detailed answer in Laravel/advanced.md above (added inline). Key topics covered:
- Input validation with Form Requests
- XSS protection (Blade escaping)
- CSRF protection
- SQL injection prevention
- Authentication security (password hashing, rate limiting, 2FA)
- Authorization with policies
- Mass assignment protection
- File upload security
- API security and rate limiting
- Security headers middleware
- Data encryption

**Key Points:**
- Validate all input
- Use Eloquent/Query Builder (not raw SQL)
- Enable CSRF for forms
- Rate limit authentication endpoints
- Policies for authorization
- Encrypt sensitive data
- Security headers via middleware

---

## Question 7: How do you implement logging in Laravel?

**Answer:**

Laravel uses Monolog for flexible logging with multiple channels.

### Basic Logging

```php
use Illuminate\Support\Facades\Log;

// Log levels (RFC 5424)
Log::emergency('System is down!');  // Most severe
Log::alert('Action required immediately');
Log::critical('Critical condition');
Log::error('Error occurred');
Log::warning('Warning message');
Log::notice('Normal but significant');
Log::info('Informational message');
Log::debug('Debug information');  // Least severe

// With context
Log::info('User logged in', [
    'user_id' => $user->id,
    'ip' => request()->ip(),
    'user_agent' => request()->userAgent(),
]);

// Exception logging
try {
    processPayment();
} catch (\Exception $e) {
    Log::error('Payment failed', [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'user_id' => auth()->id(),
    ]);
}
```

### Log Channels

```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'slack'],
        'ignore_exceptions' => false,
    ],

    'single' => [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
    ],

    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,  // Keep logs for 14 days
    ],

    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'username' => 'Laravel Log',
        'emoji' => ':boom:',
        'level' => 'critical',  // Only critical errors
    ],

    'papertrail' => [
        'driver' => 'monolog',
        'level' => env('LOG_LEVEL', 'debug'),
        'handler' => SyslogUdpHandler::class,
        'handler_with' => [
            'host' => env('PAPERTRAIL_URL'),
            'port' => env('PAPERTRAIL_PORT'),
        ],
    ],

    'stderr' => [
        'driver' => 'monolog',
        'level' => env('LOG_LEVEL', 'debug'),
        'handler' => StreamHandler::class,
        'formatter' => env('LOG_STDERR_FORMATTER'),
        'with' => [
            'stream' => 'php://stderr',
        ],
    ],

    'syslog' => [
        'driver' => 'syslog',
        'level' => env('LOG_LEVEL', 'debug'),
    ],

    'errorlog' => [
        'driver' => 'errorlog',
        'level' => env('LOG_LEVEL', 'debug'),
    ],
];

// Use specific channel
Log::channel('slack')->critical('System failure!');

// Multiple channels
Log::stack(['single', 'slack'])->info('Important event');
```

### Custom Log Channels

```php
// Custom database logger
'channels' => [
    'database' => [
        'driver' => 'custom',
        'via' => App\Logging\DatabaseLogger::class,
        'level' => 'debug',
    ],
],

// app/Logging/DatabaseLogger.php
class DatabaseLogger
{
    public function __invoke(array $config)
    {
        return new Logger(
            'database',
            [new DatabaseHandler($config['level'])]
        );
    }
}

class DatabaseHandler extends AbstractProcessingHandler
{
    protected function write(array $record): void
    {
        DB::table('logs')->insert([
            'level' => $record['level_name'],
            'message' => $record['message'],
            'context' => json_encode($record['context']),
            'created_at' => now(),
        ]);
    }
}
```

### Contextual Logging

```php
// Add context to all logs in request
Log::withContext([
    'request_id' => (string) Str::uuid(),
    'user_id' => auth()->id(),
]);

// Now all logs include this context
Log::info('Processing order');  // Includes request_id and user_id

// Middleware for request logging
class LogRequests
{
    public function handle($request, Closure $next)
    {
        $requestId = (string) Str::uuid();

        Log::withContext([
            'request_id' => $requestId,
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ]);

        Log::info('Request started');

        $response = $next($request);

        Log::info('Request completed', [
            'status' => $response->getStatusCode(),
        ]);

        return $response->header('X-Request-ID', $requestId);
    }
}
```

### Structured Logging

```php
// JSON formatting for log aggregators
'channels' => [
    'json' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'debug',
        'formatter' => JsonFormatter::class,
    ],
],

// Custom formatter
use Monolog\Formatter\JsonFormatter;

class CustomJsonFormatter extends JsonFormatter
{
    public function format(array $record): string
    {
        return json_encode([
            'timestamp' => $record['datetime']->format('Y-m-d H:i:s'),
            'level' => $record['level_name'],
            'message' => $record['message'],
            'context' => $record['context'],
            'extra' => $record['extra'],
            'environment' => app()->environment(),
        ]) . PHP_EOL;
    }
}
```

### Query Logging

```php
// Enable query log
DB::enableQueryLog();

// Run queries
User::where('active', true)->get();

// Get logged queries
$queries = DB::getQueryLog();

foreach ($queries as $query) {
    Log::debug('Query executed', [
        'sql' => $query['query'],
        'bindings' => $query['bindings'],
        'time' => $query['time'],
    ]);
}

// Log slow queries automatically
DB::listen(function ($query) {
    if ($query->time > 1000) {  // > 1 second
        Log::warning('Slow query detected', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time,
        ]);
    }
});
```

### Event Logging

```php
// Log model events
class User extends Model
{
    protected static function booted()
    {
        static::created(function ($user) {
            Log::info('User created', ['user_id' => $user->id]);
        });

        static::updated(function ($user) {
            Log::info('User updated', [
                'user_id' => $user->id,
                'changes' => $user->getChanges(),
            ]);
        });

        static::deleted(function ($user) {
            Log::warning('User deleted', ['user_id' => $user->id]);
        });
    }
}
```

### Log Rotation

```bash
# Laravel Automatic (Daily driver)
# Keeps last 14 days by default

# Manual with logrotate
# /etc/logrotate.d/laravel
/var/www/html/storage/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

**Follow-up:**
- How do you aggregate logs from multiple servers?
- What's the difference between Log::debug() and dd()?
- How do you search logs efficiently?

**Key Points:**
- Multiple channels: single, daily, slack, papertrail
- Log levels: emergency → debug
- Context for traceability
- JSON format for log aggregators (ELK, Datadog)
- Query logging for performance debugging
- Custom channels for specific needs
- Automatic rotation with daily driver

---

## Question 9: Explain various message brokers in Laravel and when to use each.

**Answer:**

Laravel supports multiple queue drivers, each suited for different use cases. Understanding their differences helps choose the right one.

### Queue Configuration

```php
// config/queue.php
'connections' => [
    'sync' => [
        'driver' => 'sync',  // Immediate execution
    ],
    
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
    ],
    
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => null,
    ],
    
    'rabbitmq' => [
        'driver' => 'rabbitmq',
        'host' => env('RABBITMQ_HOST', '127.0.0.1'),
        'port' => env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_USER', 'guest'),
        'password' => env('RABBITMQ_PASSWORD', 'guest'),
        'vhost' => env('RABBITMQ_VHOST', '/'),
        'queue' => 'default',
    ],
    
    'sqs' => [
        'driver' => 'sqs',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account'),
        'queue' => env('SQS_QUEUE', 'default'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
],
```

### 1. Sync (Development/Debugging)

```php
// For local development - executes immediately
// config/queue.php
'default' => 'sync',

// .env
QUEUE_CONNECTION=sync
```

**Use Case:** Local development, debugging, simple scripts
**Pros:** Simple, no setup required, immediate feedback
**Cons:** Not suitable for production, blocks request

### 2. Database (Simple Projects)

```php
// Creates jobs table automatically
php artisan queue:table
php artisan migrate

// Simple jobs table structure
// id, queue, payload, attempts, reserved_at, available_at, created_at
```

**Use Case:** Small projects, low volume, simple setup
**Pros:** No additional infrastructure, familiar MySQL/PostgreSQL
**Cons:** Polling overhead, not for high volume, no pub/sub

### 3. Redis (High Performance)

```php
// Most popular choice for Laravel
// Uses BRPOP for blocking pops - efficient

// config/queue.php
'redis' => [
    'driver' => 'redis',
    'connection' => 'queue',  // Separate connection recommended
    'queue' => 'default',
    'retry_after' => 90,
    'block_for' => 3,  // Block for 3 seconds waiting for job
],

// config/database.php
'redis' => [
    'client' => 'phpredis',
    
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_DB', 0),
    ],
    
    'queue' => [
        'host' => env('REDIS_QUEUE_HOST', '127.0.0.1'),
        'password' => env('REDIS_QUEUE_PASSWORD'),
        'port' => env('REDIS_QUEUE_PORT', 6379),
        'database' => env('REDIS_QUEUE_DB', 1),
    ],
],

// Supervisor config
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/forge/app.com/artisan queue:work redis --queue=default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=forge
numprocs=4
redirect_stderr=true
stdout_logfile=/home/forge/app.com/worker.log
stopwaitsecs=3600
```

**Use Case:** High volume, production workloads, pub/sub patterns
**Pros:** Fast, pub/sub support, blocking pops, clustering
**Cons:** Requires Redis knowledge, memory management

### 4. RabbitMQ (Enterprise)

```php
// Install composer package
composer require vladimir-yuldashev/laravel-queue-rabbitmq

// Queue exchanges and bindings
// Exchange: topic, direct, fanout
// Queue: bound to exchange with routing keys
```

**Use Case:** Enterprise, complex routing, message durability
**Pros:** Reliable delivery, complex routing, message acknowledgment
**Cons:** Complex setup, heavier resource usage

```php
// Complex routing example
class OrderProcessor implements ShouldQueue
{
    public $queue = 'orders';
    
    public function handle(): void
    {
        // Message goes to orders exchange with routing key order.created
    }
}

// RabbitMQ topology
// Exchange: orders (topic)
// Queues: order-notifications, order-fulfillment, order-analytics
// Bindings: order.* -> order-notifications
//           order.created -> order-fulfillment
//           order.* -> order-analytics
```

### 5. Amazon SQS (Cloud/Native)

```php
// Serverless, managed by AWS
// Pay per request

'sqs' => [
    'driver' => 'sqs',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'prefix' => 'https://sqs.us-east-1.amazonaws.com/123456789',
    'queue' => 'production-queue',
    'region' => 'us-east-1',
    'suffix' => env('SQS_SUFFIX'),
],

// FIFO queue for ordered processing
// SQS FIFO: exactly-once processing, ordered
'sqs-fifo' => [
    'driver' => 'sqs',
    'queue' => 'orders.fifo',  // .fifo suffix
    'region' => 'us-east-1',
],
```

**Use Case:** AWS infrastructure, serverless, pay-per-use
**Pros:** Managed, scalable, no server maintenance, FIFO support
**Cons:** Latency, AWS costs, vendor lock-in

### 6. Apache Kafka (High Throughput/Real-time)

```php
// Laravel doesn't have native Kafka support
// Use package: mateusjunges/laravel-kafka

// Install
composer require mateusjunges/laravel-kafka

// config/kafka.php
'brokers' => env('KAFKA_BROKERS', 'localhost:9092'),
```

```php
use Junges\Kafka\Facades\Kafka;

// Producer
Kafka::publish()
    ->onTopic('user-events')
    ->withBody([
        'event' => 'user.created',
        'data' => $user->toArray(),
    ])
    ->send();

// Consumer
Kafka::consumer()
    ->subscribe('user-events')
    ->withConsumerGroupId('order-processing')
    ->withHandler(function ($message) {
        $data = json_decode($message->getBody());
        // Process message
    })
    ->build()
    ->consume();
```

**Use Case:** Real-time streaming, log aggregation, analytics
**Pros:** Extremely high throughput, persistent log, partitions
**Cons:** Complex setup, needs ZooKeeper/Kraft, learning curve

### Comparison Table

| Feature | Sync | Database | Redis | RabbitMQ | SQS | Kafka |
|---------|------|----------|-------|----------|-----|-------|
| Setup | None | Easy | Medium | Hard | Easy | Hard |
| Latency | Low | Medium | Low | Low | High | Low |
| Throughput | Low | Medium | High | High | Medium | Very High |
| Persistence | No | Yes | Yes | Yes | Yes | Yes |
| Ordering | - | Yes | Yes | Yes | FIFO | Yes |
| Message Size | - | Large | Large | Large | 256KB | Unlimited |
| Cost | Free | Your DB | Your Redis | Your Server | Pay/API | Your Server |
| Complexity | - | Low | Medium | High | Low | High |

### Choosing the Right Broker

```php
// Decision guide:

// 1. Small project, simple needs
// ✅ Database or Sync

// 2. Medium project, production
// ✅ Redis

// 3. Enterprise, complex routing
// ✅ RabbitMQ

// 4. AWS infrastructure
// ✅ SQS

// 5. Real-time streaming, analytics
// ✅ Kafka
```

### Laravel Horizon (Redis Queue Monitoring)

```php
// Install
composer require laravel/horizon

// config/horizon.php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default', 'emails'],
            'balance' => 'simple',  // simple, auto, false
            'maxProcesses' => 10,
            'minProcesses' => 1,
            'maxJobs' => 1000,
            'nice' => 0,
        ],
    ],
],

// Access dashboard at /horizon
// Shows: jobs per minute, failed jobs, throughput, wait time
```

### Supervisor Configuration

```bash
# /etc/supervisor/conf.d/laravel-worker.conf

[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work redis --queue=default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/laravel-worker.log
stopwaitsecs=3600
```

**Key Points:**
- Sync: Development only
- Database: Small projects
- Redis: High performance, most Laravel apps
- RabbitMQ: Enterprise, complex routing
- SQS: AWS infrastructure, serverless
- Kafka: Real-time streaming, high throughput
- Use Horizon for Redis monitoring
- Configure Supervisor for worker management

---

## Question 9: How does Laravel caching work without Service, database driven?

**Answer:**

Laravel's database cache driver stores cached items in your database, useful when Redis/Memcached aren't available.

### Database Cache Configuration

```php
// config/cache.php
'stores' => [
    'database' => [
        'driver' => 'database',
        'table' => 'cache',
        'connection' => null,
        'lock_connection' => null,
    ],
],

// config/database.php - Ensure cache table exists
'migrations' => 'cache',
```

### Create Cache Table

```bash
php artisan make:cache-table  # Laravel 11+
php artisan cache:table      # Older versions

# Run migration
php artisan migrate
```

### Manual Database Cache Implementation

```php
<?php
// Custom database cache store implementation
class DatabaseCacheStore implements CacheStore
{
    private $connection;
    private $table;
    private $lockConnection;

    public function __construct(
        private PDO $pdo,
        string $table = 'cache',
        string $connectionName = null
    ) {
        $this->table = $table;
        $this->connection = $connectionName;
    }

    public function get(string $key): mixed
    {
        $record = $this->table()
            ->where('key', $this->prefix . $key)
            ->where('expiration', '>', time())
            ->first();

        if (!$record) {
            return null;
        }

        return unserialize($record->value);
    }

    public function put(string $key, mixed $value, int $ttl = null): bool
    {
        $expiration = $ttl ? time() + $ttl : null;

        return $this->table()->upsert([
            'key' => $this->prefix . $key,
            'value' => serialize($value),
            'expiration' => $expiration,
        ], ['key'], ['value', 'expiration']);
    }

    public function forever(string $key, mixed $value): bool
    {
        return $this->put($key, $value, null);
    }

    public function forget(string $key): bool
    {
        return $this->table()
            ->where('key', $this->prefix . $key)
            ->delete() > 0;
    }

    public function flush(): bool
    {
        return $this->table()->delete() > 0;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    private function table()
    {
        return DB::connection($this->connection)->table($this->table);
    }
}
```

### Database Cache with Tags

```php
<?php
// Database cache doesn't support tags out of the box
// But you can implement it manually

class DatabaseTaggedCache
{
    private PDO $pdo;
    private string $table;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->table = 'cache_tags';
    }

    public function tags(array $tagNames): self
    {
        $this->currentTags = $tagNames;
        return $this;
    }

    public function get(string $key): mixed
    {
        $tagKey = md5(serialize($this->currentTags));

        $stmt = $this->pdo->prepare(
            "SELECT value FROM {$this->table} 
             WHERE tag_key = ? AND key = ? AND expiration > ?"
        );

        $stmt->execute([$tagKey, $key, time()]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? unserialize($result['value']) : null;
    }

    public function put(string $key, mixed $value, int $ttl): void
    {
        $tagKey = md5(serialize($this->currentTags));
        $expiration = time() + $ttl;

        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} (tag_key, key, value, expiration)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE value = VALUES(value), 
                                    expiration = VALUES(expiration)"
        );

        $stmt->execute([$tagKey, $key, serialize($value), $expiration]);
    }

    public function flush(): void
    {
        $tagKey = md5(serialize($this->currentTags));

        $stmt = $this->pdo->prepare(
            "DELETE FROM {$this->table} WHERE tag_key = ?"
        );

        $stmt->execute([$tagKey]);
    }
}
```

### Custom Cache Repository

```php
<?php
// Use custom cache driver in Laravel

// config/cache.php
'stores' => [
    'custom_db' => [
        'driver' => 'custom',
        'repository' => App\Cache\DatabaseCacheRepository::class,
    ],
],

// app/Cache/DatabaseCacheRepository.php
namespace App\Cache;

use Illuminate\Cache\CacheRepository;
use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Cache\Store;

class DatabaseCacheRepository extends CacheRepository implements TaggableStore
{
    public function __construct(Store $store)
    {
        parent::__construct($store);
    }

    public function getPrefix(): string
    {
        return $this->store->getPrefix();
    }
}
```

### Eloquent-Based Caching

```php
<?php
// Simple cache using Eloquent model

class CacheEntry extends Model
{
    protected $table = 'cache';
    public $timestamps = false;
    protected $fillable = ['key', 'value', 'expiration'];

    public static function remember(
        string $key, 
        int $ttl, 
        callable $callback
    ): mixed {
        $entry = static::where('key', $key)
            ->where('expiration', '>', time())
            ->first();

        if ($entry) {
            return unserialize($entry->value);
        }

        $value = $callback();
        
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => serialize($value),
                'expiration' => time() + $ttl,
            ]
        );

        return $value;
    }
}

// Usage
$data = CacheEntry::remember('users.all', 3600, function () {
    return User::all();
});
```

### Performance Considerations

```php
<?php
// Add indexes to cache table for better performance

Schema::create('cache', function (Blueprint $table) {
    $table->string('key')->primary();
    $table->text('value');
    $table->integer('expiration');
    
    // Add index for key + expiration queries
    $table->index(['key', 'expiration']);
});

// Query optimization
// Instead of:
$value = Cache::get('key');

// Use direct query when possible
$entry = DB::table('cache')
    ->where('key', 'key')
    ->where('expiration', '>', time())
    ->first();

if ($entry) {
    $value = unserialize($entry->value);
}
```

### When to Use Database Cache

```php
<?php
// Good for:
// - Development environments
// - Simple deployments without Redis
// - Small to medium traffic
// - When infrastructure options are limited

// config/cache.php
'stores' => [
    'database' => [
        'driver' => 'database',
        'table' => 'cache',
        'connection' => 'cache',  // Separate connection
    ],
],

// Not recommended for:
// - High traffic applications
// - Frequent cache operations
// - Distributed systems
// - Session storage
```

### Cache Key Strategies

```php
<?php
// Organize cache keys for database
class CacheKeyBuilder
{
    private string $prefix = 'app';
    private string $version = 'v1';

    public function make(string $resource, mixed $id = null): string
    {
        $parts = [$this->prefix, $this->version, $resource];
        
        if ($id !== null) {
            $parts[] = $id;
        }

        return implode(':', $parts);
    }

    public function user(int $userId): string
    {
        return $this->make('user', $userId);
    }

    public function posts(int $userId): string
    {
        return $this->make('posts', $userId);
    }

    public function invalidateUser(int $userId): void
    {
        // Clean up related caches
        Cache::forget($this->user($userId));
        Cache::forget($this->posts($userId));
    }
}

// Usage
$cacheKey = app(CacheKeyBuilder::class)->user($userId);
$user = Cache::remember($cacheKey, 3600, fn() => User::find($userId));
```

**Follow-up:**
- How does database cache compare to Redis?
- Can you use tags with database cache?
- How do you handle cache invalidation with database?

**Key Points:**
- Database cache uses `cache` table in database
- Run `php artisan cache:table` to create migration
- Good for development, simple deployments
- Add indexes on key + expiration for performance
- Database cache doesn't support tags natively
- Not recommended for high traffic applications
- Use separate DB connection for cache to isolate

---

## Notes

Add more questions covering:
- Laravel Notifications (mail, SMS, Slack, database)
- File Storage and S3 integration
- API Resources and transformers
- Rate limiting
- Telescope for debugging
- Horizon for queue monitoring
- Laravel Sanctum/Passport for API auth
