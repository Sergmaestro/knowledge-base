# DevOps & Production

## Question 1: How do you monitor PHP/Laravel applications in production?

**Answer:**

### Monitoring Stack

```
Application → Logs → Aggregation → Alerting
             ↓
          Metrics → Dashboards → Monitoring
             ↓
           Traces → APM → Performance
```

### Application Performance Monitoring (APM)

```php
// Laravel Telescope (Development/Staging)
composer require laravel/telescope
php artisan telescope:install
php artisan migrate

// Access at /telescope
// Shows:
// - Requests
// - Exceptions
// - Database queries
// - Jobs/Queues
// - Cache hits/misses
// - Redis commands

// Production APM Tools:
// - New Relic
// - Datadog
// - Scout APM
// - Blackfire
```

### Logging

```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'slack'],
    ],

    'single' => [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'debug',
    ],

    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'username' => 'Laravel Log',
        'level' => 'critical',  // Only critical errors to Slack
    ],
],

// Structured logging
Log::info('User logged in', [
    'user_id' => $user->id,
    'ip' => request()->ip(),
    'user_agent' => request()->userAgent(),
]);

Log::error('Payment failed', [
    'order_id' => $order->id,
    'amount' => $order->total,
    'error' => $exception->getMessage(),
    'trace' => $exception->getTraceAsString(),
]);

// Context for all logs
Log::withContext([
    'request_id' => request()->header('X-Request-ID'),
    'user_id' => auth()->id(),
]);
```

### Log Aggregation

```yaml
# ELK Stack (Elasticsearch, Logstash, Kibana)
# or Loki + Grafana
# or Papertrail
# or Logtail

# Filebeat config (ships logs to Elasticsearch)
filebeat.inputs:
- type: log
  enabled: true
  paths:
    - /var/www/storage/logs/*.log
  json.keys_under_root: true
  json.add_error_key: true

output.elasticsearch:
  hosts: ["localhost:9200"]
  index: "laravel-%{+yyyy.MM.dd}"
```

### Metrics & Monitoring

```php
// Custom metrics with Prometheus
// composer require promphp/prometheus_client_php

$registry = new CollectorRegistry(new APC());

// Counter
$counter = $registry->getOrRegisterCounter(
    'app',
    'orders_total',
    'Total orders processed',
    ['status']
);
$counter->inc(['completed']);

// Gauge
$gauge = $registry->getOrRegisterGauge(
    'app',
    'queue_size',
    'Current queue size'
);
$gauge->set(Redis::llen('queue:default'));

// Histogram (response times)
$histogram = $registry->getOrRegisterHistogram(
    'app',
    'request_duration_seconds',
    'Request duration',
    ['route', 'method']
);
$histogram->observe($duration, [$route, $method]);
```

### Health Checks

```php
// routes/web.php
Route::get('/health', function () {
    $checks = [
        'database' => checkDatabase(),
        'redis' => checkRedis(),
        'storage' => checkStorage(),
        'queue' => checkQueue(),
    ];

    $healthy = collect($checks)->every(fn($check) => $check === true);

    return response()->json([
        'status' => $healthy ? 'healthy' : 'unhealthy',
        'checks' => $checks,
        'timestamp' => now()->toIso8601String(),
    ], $healthy ? 200 : 503);
});

function checkDatabase(): bool {
    try {
        DB::connection()->getPdo();
        return true;
    } catch (\Exception $e) {
        Log::error('Database health check failed', ['error' => $e->getMessage()]);
        return false;
    }
}

function checkRedis(): bool {
    try {
        Redis::ping();
        return true;
    } catch (\Exception $e) {
        Log::error('Redis health check failed', ['error' => $e->getMessage()]);
        return false;
    }
}

function checkQueue(): bool {
    try {
        $size = Redis::llen('queues:default');
        return $size < 10000;  // Alert if queue too large
    } catch (\Exception $e) {
        Log::error('Queue health check failed', ['error' => $e->getMessage()]);
        return false;
    }
}
```

### Error Tracking

```php
// Sentry integration
composer require sentry/sentry-laravel

// config/sentry.php
'dsn' => env('SENTRY_LARAVEL_DSN'),
'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.1),

// Capture exceptions
try {
    processPayment($order);
} catch (\Exception $e) {
    Sentry\captureException($e);
    Log::error('Payment processing failed', [
        'order_id' => $order->id,
        'error' => $e->getMessage(),
    ]);
}

// Capture context
Sentry\configureScope(function (Sentry\State\Scope $scope) {
    $scope->setUser([
        'id' => auth()->id(),
        'email' => auth()->user()->email,
    ]);
    $scope->setTag('environment', app()->environment());
});
```

### Uptime Monitoring

```yaml
# Use services like:
# - Pingdom
# - UptimeRobot
# - StatusCake
# - Oh Dear

# Check every 1-5 minutes:
GET https://api.example.com/health
Expected: 200 OK
Alert if: Status != 200 or response time > 2s
```

### Performance Monitoring

```php
// Custom middleware to track request timing
class PerformanceMonitoring {
    public function handle($request, Closure $next) {
        $start = microtime(true);

        $response = $next($request);

        $duration = microtime(true) - $start;

        Log::info('Request completed', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status' => $response->getStatusCode(),
            'duration' => $duration,
            'memory' => memory_get_peak_usage(true) / 1024 / 1024, // MB
        ]);

        // Send to metrics system
        if ($duration > 1) {  // Slow request
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'duration' => $duration,
            ]);
        }

        return $response;
    }
}
```

**Follow-up:**
- How do you alert on-call engineers?
- What metrics are most important?
- How do you reduce alert fatigue?

**Key Points:**
- APM: Telescope (dev), New Relic/Datadog (prod)
- Logging: Structured logs with context
- Metrics: Prometheus, custom counters/gauges
- Health checks: Database, Redis, Queue
- Error tracking: Sentry, Bugsnag
- Uptime monitoring: Pingdom, UptimeRobot

---

## Question 2: What logs and metrics do you consider critical?

**Answer:**

### Critical Logs

```php
// 1. Application Errors
Log::error('Critical error', [
    'exception' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
    'user_id' => auth()->id(),
    'url' => request()->fullUrl(),
]);

// 2. Authentication Events
Log::info('User login', ['user_id' => $user->id, 'ip' => request()->ip()]);
Log::warning('Failed login attempt', ['email' => $email, 'ip' => request()->ip()]);
Log::alert('Multiple failed logins', ['email' => $email, 'attempts' => 5]);

// 3. Business Critical Operations
Log::info('Order placed', ['order_id' => $order->id, 'total' => $order->total]);
Log::error('Payment failed', ['order_id' => $order->id, 'error' => $error]);
Log::info('Refund processed', ['order_id' => $order->id, 'amount' => $amount]);

// 4. Security Events
Log::warning('Suspicious activity', ['user_id' => $user->id, 'action' => $action]);
Log::alert('Rate limit exceeded', ['ip' => request()->ip(), 'endpoint' => $endpoint]);

// 5. Performance Issues
Log::warning('Slow query', ['query' => $sql, 'duration' => $duration]);
Log::warning('High memory usage', ['memory' => memory_get_peak_usage()]);

// 6. External API Failures
Log::error('External API failed', [
    'service' => 'stripe',
    'endpoint' => $endpoint,
    'status' => $response->status(),
    'error' => $response->body(),
]);
```

### Critical Metrics

```php
// Application Metrics
'app.requests.total'           // Total requests
'app.requests.duration'        // Response time (p50, p95, p99)
'app.requests.errors'          // Error rate
'app.requests.5xx'            // Server errors
'app.requests.4xx'            // Client errors

// Business Metrics
'orders.created'              // Orders per minute
'orders.completed'            // Successful orders
'orders.failed'               // Failed orders
'revenue.total'               // Revenue tracking
'users.active'                // Active users
'users.registered'            // New signups

// Infrastructure Metrics
'system.cpu.usage'            // CPU %
'system.memory.usage'         // Memory %
'system.disk.usage'           // Disk %
'php.fpm.processes'           // PHP-FPM workers

// Database Metrics
'db.queries.count'            // Query count
'db.queries.duration'         // Query time
'db.connections.active'       // Active connections
'db.slow_queries'             // Slow queries (> 1s)

// Queue Metrics
'queue.jobs.pending'          // Jobs waiting
'queue.jobs.failed'           // Failed jobs
'queue.jobs.duration'         // Job processing time

// Cache Metrics
'cache.hits'                  // Cache hit rate
'cache.misses'                // Cache miss rate
'redis.memory.usage'          // Redis memory
'redis.connections'           // Redis connections
```

### Alerting Thresholds

```yaml
# Application
- Error rate > 1% over 5 minutes → Page on-call
- Response time p95 > 2s → Warning
- Response time p99 > 5s → Critical

# Queue
- Failed jobs > 10 in 5 minutes → Warning
- Queue size > 10,000 → Critical
- Job duration > 5 minutes → Warning

# Infrastructure
- CPU usage > 80% for 5 minutes → Warning
- CPU usage > 90% for 5 minutes → Critical
- Memory usage > 85% → Warning
- Disk usage > 90% → Critical

# Database
- Connection pool > 90% → Critical
- Slow queries > 100/minute → Warning
- Replication lag > 60s → Critical

# Business
- Order failure rate > 5% → Critical
- Revenue drop > 20% vs yesterday → Critical
- No orders in 15 minutes (during business hours) → Warning
```

### Dashboard Layout

```
┌─────────────────────────────────────────┐
│  Application Health                      │
│  - Uptime: 99.95%                       │
│  - Error rate: 0.12%                    │
│  - Avg response time: 250ms             │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│  Business Metrics (Today)                │
│  - Orders: 1,234 (+12%)                 │
│  - Revenue: $45,678 (+8%)               │
│  - Active users: 567                    │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│  Infrastructure                          │
│  - CPU: 45%                             │
│  - Memory: 62%                          │
│  - Queue: 23 jobs                       │
└─────────────────────────────────────────┘
```

**Key Points:**
- Log errors, auth events, business operations
- Track error rate, response time, throughput
- Monitor queue size, failed jobs
- Infrastructure: CPU, memory, disk
- Alert on anomalies and thresholds
- Dashboard for at-a-glance health

---

## Question 3: How do you handle configuration and secrets?

**Answer:**

### Environment Variables

```php
// .env (NOT committed to git)
APP_KEY=base64:...
DB_PASSWORD=secret
STRIPE_SECRET=sk_live_...
AWS_SECRET_ACCESS_KEY=...

// Access in code
$key = env('STRIPE_SECRET');

// config/services.php (committed to git)
'stripe' => [
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
],

// Access config
$secret = config('services.stripe.secret');
```

### Secrets Management

```bash
# AWS Secrets Manager
aws secretsmanager create-secret \
    --name prod/db/password \
    --secret-string "super-secret-password"

# Retrieve in application
$client = new SecretsManagerClient(['region' => 'us-east-1']);
$result = $client->getSecretValue(['SecretId' => 'prod/db/password']);
$secret = $result['SecretString'];

# HashiCorp Vault
vault kv put secret/db password=supersecret

# Laravel integration
DB_PASSWORD=vault://secret/db/password
```

### Environment-Specific Configuration

```php
// config/app.php
'env' => env('APP_ENV', 'production'),
'debug' => env('APP_DEBUG', false),

// Different configs per environment
if (app()->environment('local')) {
    // Development settings
    config(['logging.default' => 'single']);
}

if (app()->environment('production')) {
    // Production settings
    config(['logging.default' => 'stack']);
    config(['cache.default' => 'redis']);
}
```

### Deployment

```bash
# .env files per environment
.env.local
.env.staging
.env.production

# Deploy with Envoy/Deployer
php artisan config:cache  # Cache config for performance
php artisan route:cache
php artisan view:cache
```

**Key Points:**
- Use .env for secrets (never commit)
- Use AWS Secrets Manager/Vault for production
- Cache config in production
- Different .env per environment

---

## Question 4: How do you debug issues that only happen in production?

**Answer:**

### Enable Production-Safe Debugging

```php
// DON'T enable APP_DEBUG=true in production!

// Instead, use:
// 1. Detailed logging
Log::error('Production error', [
    'exception' => $e,
    'user' => auth()->user(),
    'request' => request()->all(),
    'headers' => request()->headers->all(),
    'server' => request()->server(),
]);

// 2. Error tracking (Sentry, Bugsnag)
Sentry\captureException($e);

// 3. APM tools (New Relic, Datadog)
// Automatic tracing and performance monitoring
```

### Reproduce Locally

```bash
# 1. Get production data (anonymized)
php artisan db:seed --class=ProductionSeeder

# 2. Match environment
APP_ENV=production
APP_DEBUG=false
QUEUE_CONNECTION=redis

# 3. Use production-like data volume
# Create 1M records to test performance

# 4. Enable query log
DB::enableQueryLog();
dd(DB::getQueryLog());
```

### Remote Debugging

```php
// Laravel Telescope in production (secure it!)
// Only accessible by admins
Telescope::auth(function ($request) {
    return auth()->check() && auth()->user()->isAdmin();
});

// Or use Tinker for quick checks
php artisan tinker
>>> User::find(123)
>>> Cache::get('key')
>>> Queue::size('default')
```

### Analysis Tools

```bash
# Check logs
tail -f storage/logs/laravel.log
grep "ERROR" storage/logs/laravel.log

# Database queries
php artisan telescope:prune  # Clean old data
# View slow queries in Telescope

# Queue jobs
php artisan queue:failed  # Failed jobs
php artisan queue:retry all

# Check system resources
top
htop
df -h
```

**Key Points:**
- Never enable APP_DEBUG in production
- Use Sentry/Bugsnag for errors
- Detailed logging with context
- Reproduce locally with production data
- Use Telescope (secured) or Tinker

---

## Notes

Add more questions covering:
- CI/CD pipelines
- Zero-downtime deployments
- Database migrations in production
- Load testing and stress testing
- Disaster recovery and backups
- Container orchestration (Docker, Kubernetes)
- Infrastructure as Code (Terraform)
