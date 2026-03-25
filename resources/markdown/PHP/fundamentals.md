# PHP Fundamentals

## Question 1: What are the differences between `include`, `require`, `include_once`, and `require_once`?

**Answer:**

All four are used to include files, but they differ in error handling and re-inclusion:

| Function | Error on Failure | Allows Re-inclusion |
|----------|------------------|---------------------|
| `include` | Warning (E_WARNING) | Yes |
| `require` | Fatal Error (E_COMPILE_ERROR) | Yes |
| `include_once` | Warning | No |
| `require_once` | Fatal Error | No |

```php
// Use require for critical dependencies
require 'config/database.php';  // Fatal error if missing

// Use include for optional components
include 'templates/header.php';  // Script continues if missing

// Use *_once to prevent redeclaration errors
require_once 'vendor/autoload.php';
```

**Follow-up:**
- When would you use `include` vs `require`?
- What's the performance impact of `*_once` variants?

**Key Points:**
- `require` for critical files (config, classes)
- `include` for optional files (templates)
- `*_once` to prevent redeclarations
- Modern PHP uses autoloading instead

---

## Question 2: Explain PHP's type system. What are scalar types and type declarations?

**Answer:**

PHP is dynamically typed but supports type declarations (since PHP 7.0):

**Scalar Types:**
- `int`, `float`, `string`, `bool`

**Compound Types:**
- `array`, `object`, `callable`, `iterable`

**Special Types:**
- `resource`, `null`, `mixed`

```php
// Type declarations (PHP 7.0+)
function calculateTotal(int $quantity, float $price): float {
    return $quantity * $price;
}

// Strict types (top of file)
declare(strict_types=1);

// Union types (PHP 8.0+)
function process(int|float $number): string {
    return (string) $number;
}

// Nullable types
function findUser(int $id): ?User {
    return $this->users[$id] ?? null;
}
```

**Follow-up:**
- What's the difference between strict and weak type checking?
- Explain type coercion in PHP

**Key Points:**
- Type hints improve code reliability
- `declare(strict_types=1)` enforces strict typing
- Union types (PHP 8.0+) allow multiple types
- Return type declarations document expected output

---

## Question 3: What are PHP's magic methods? Name the most important ones.

**Answer:**

Magic methods are special methods with double underscore prefix that are triggered automatically:

```php
class User {
    private array $data = [];

    // Constructor
    public function __construct(string $name, string $email) {
        $this->data['name'] = $name;
        $this->data['email'] = $email;
    }

    // Destructor
    public function __destruct() {
        // Cleanup resources
    }

    // Property overloading
    public function __get(string $name) {
        return $this->data[$name] ?? null;
    }

    public function __set(string $name, $value): void {
        $this->data[$name] = $value;
    }

    public function __isset(string $name): bool {
        return isset($this->data[$name]);
    }

    // Method overloading
    public function __call(string $method, array $args) {
        throw new BadMethodCallException("Method {$method} not found");
    }

    // Static method overloading
    public static function __callStatic(string $method, array $args) {
        // Handle static method calls
    }

    // String representation
    public function __toString(): string {
        return $this->data['name'];
    }

    // Serialization
    public function __sleep(): array {
        return ['data'];
    }

    public function __wakeup(): void {
        // Reinitialize after unserialization
    }

    // Cloning
    public function __clone() {
        $this->data = array_merge($this->data, []);
    }

    // Invocation
    public function __invoke(string $greeting) {
        return "{$greeting}, {$this->data['name']}!";
    }
}
```

**Follow-up:**
- When would you use `__call()` vs `__callStatic()`?
- What are the security implications of `__wakeup()`?

**Key Points:**
- `__construct/__destruct`: Object lifecycle
- `__get/__set/__isset`: Property access
- `__call/__callStatic`: Method overloading
- `__toString`: String conversion
- `__invoke`: Make object callable

---

## Question 4: Explain references in PHP. How do they work?

**Answer:**

References allow multiple variables to point to the same value:

```php
// Reference assignment
$a = 'hello';
$b = &$a;  // $b references $a
$b = 'world';
echo $a;  // Output: world

// Function parameters
function increment(&$value) {
    $value++;
}

$num = 5;
increment($num);
echo $num;  // Output: 6

// Return by reference (rare usage)
class Config {
    private array $data = ['debug' => false];

    public function &getOption(string $key) {
        return $this->data[$key];
    }
}

$config = new Config();
$debug = &$config->getOption('debug');
$debug = true;  // Modifies internal data
```

**Follow-up:**
- What's the difference between references and pointers in C?
- When should you avoid using references?

**Key Points:**
- References are aliases, not pointers
- Use `&` for pass-by-reference
- PHP uses copy-on-write optimization
- Avoid references unless necessary (code clarity)

---

## Question 5: What are generators and when would you use them?

**Answer:**

Generators are functions that use `yield` to return values lazily, one at a time:

### Iterators vs Generators

```php
<?php
// Iterator: Classic approach (more code)
class FileIterator implements Iterator
{
    private $file;
    private $line;
    private $position = 0;

    public function __construct(string $path)
    {
        $this->file = fopen($path, 'r');
    }

    public function rewind(): void
    {
        rewind($this->file);
        $this->line = fgets($this->file);
        $this->position = 0;
    }

    public function current(): mixed
    {
        return $this->line;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        $this->line = fgets($this->file);
        $this->position++;
    }

    public function valid(): bool
    {
        return $this->line !== false;
    }

    public function __destruct()
    {
        fclose($this->file);
    }
}

// Generator: Much simpler, same result
function fileGenerator(string $path): Generator
{
    $handle = fopen($path, 'r');

    while (!feof($handle)) {
        yield fgets($handle);
    }

    fclose($handle);
}

// Usage is identical
foreach (new FileIterator('data.txt') as $line) { /* ... */ }
foreach (fileGenerator('data.txt') as $line) { /* ... */ }
```

### When to Use Generators

```php
<?php
// ✅ Perfect for: Large datasets, file processing, API pagination
function getUsersFromApi(): Generator
{
    $page = 1;
    
    while (true) {
        $response = $this->client->get("/users?page={$page}");
        $users = json_decode($response->getBody());
        
        if (empty($users)) {
            break;  // No more pages
        }
        
        foreach ($users as $user) {
            yield $user;  // One at a time
        }
        
        $page++;
    }
}

// Process millions of records without loading all into memory
foreach (getUsersFromApi() as $user) {
    $this->process($user);  // Memory stays constant!
}

// ❌ Don't use when:
// - Need to iterate multiple times (rewind() doesn't work)
// - Need random access by key
// - Need to know total count upfront
```

### Generator Features

```php
<?php
// Memory-efficient iteration
function readLargeFile(string $path): Generator {
    $handle = fopen($path, 'r');

    while (!feof($handle)) {
        yield fgets($handle);
    }

    fclose($handle);
}

// Process millions of lines without loading all into memory
foreach (readLargeFile('huge.log') as $line) {
    if (str_contains($line, 'ERROR')) {
        echo $line;
    }
}

// Generate infinite sequences
function fibonacci(): Generator {
    $a = 0;
    $b = 1;

    while (true) {
        yield $a;
        [$a, $b] = [$b, $a + $b];
    }
}

// Take first 10 Fibonacci numbers
$count = 0;
foreach (fibonacci() as $num) {
    echo $num . ' ';
    if (++$count >= 10) break;
}

// Key-value pairs
function getUsers(): Generator {
    yield 'admin' => ['role' => 'admin', 'active' => true];
    yield 'user1' => ['role' => 'user', 'active' => true];
}

// Send values back to generator
function logger(): Generator {
    while (true) {
        $message = yield;
        file_put_contents('log.txt', $message . PHP_EOL, FILE_APPEND);
    }
}

$log = logger();
$log->send('First log entry');
$log->send('Second log entry');
```

**Follow-up:**
- What's the memory advantage of generators?
- Can you explain `yield from`?

**Key Points:**
- Lazy evaluation saves memory
- Perfect for large datasets or streams
- Can yield key-value pairs
- `yield from` delegates to another generator
- Maintains state between yields

---

## Question 6: What are traits and how do they differ from inheritance?

**Answer:**

Traits enable horizontal code reuse without inheritance:

```php
trait Timestampable {
    protected DateTime $createdAt;
    protected DateTime $updatedAt;

    public function setTimestamps(): void {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public function touch(): void {
        $this->updatedAt = new DateTime();
    }
}

trait SoftDeletes {
    protected ?DateTime $deletedAt = null;

    public function delete(): void {
        $this->deletedAt = new DateTime();
    }

    public function restore(): void {
        $this->deletedAt = null;
    }

    public function isDeleted(): bool {
        return $this->deletedAt !== null;
    }
}

class User {
    use Timestampable, SoftDeletes;

    public function __construct(
        private string $name,
        private string $email
    ) {
        $this->setTimestamps();
    }
}

// Conflict resolution
trait A {
    public function greet() { return 'Hello from A'; }
}

trait B {
    public function greet() { return 'Hello from B'; }
}

class MyClass {
    use A, B {
        A::greet insteadof B;  // Use A's version
        B::greet as greetB;    // Alias B's version
    }
}
```

**Follow-up:**
- What happens if two traits have the same method?
- Can traits have abstract methods?
- What are the downsides of using traits?

**Key Points:**
- Solve multiple inheritance problem
- Horizontal code reuse
- Can conflict (resolved with `insteadof` and `as`)
- Can have properties, methods, and abstract methods
- Don't abuse - composition often better

---

## Question 7: Explain namespaces and autoloading in PHP.

**Answer:**

Namespaces organize code and prevent name collisions:

```php
// File: src/Services/PaymentProcessor.php
namespace App\Services;

use App\Models\Order;
use App\Exceptions\PaymentException;
use Stripe\StripeClient;

class PaymentProcessor {
    public function __construct(
        private StripeClient $stripe
    ) {}

    public function processPayment(Order $order): void {
        // Implementation
    }
}

// File: src/Services/Email/MailService.php
namespace App\Services\Email;

class MailService {
    // Different class with same name, different namespace
}

// Usage
use App\Services\PaymentProcessor;
use App\Services\Email\MailService;

$processor = new PaymentProcessor($stripe);
$mailer = new MailService();

// PSR-4 Autoloading (composer.json)
{
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}

// Custom autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
```

**Follow-up:**
- What is PSR-4 and how does it differ from PSR-0?
- How does `spl_autoload_register()` work?

**Key Points:**
- Namespaces prevent name collisions
- Use `use` to import classes
- PSR-4 maps namespaces to directories
- Composer handles autoloading automatically
- Follow PSR-4 standard for consistency

---

## Question 8: What are closures and how do they differ from anonymous functions?

**Answer:**

Closures are anonymous functions that can capture variables from parent scope:

```php
// Anonymous function
$greet = function($name) {
    return "Hello, {$name}!";
};

echo $greet('John');  // Hello, John!

// Closure with variable binding
$message = 'Welcome';
$welcomeUser = function($name) use ($message) {
    return "{$message}, {$name}!";
};

echo $welcomeUser('Jane');  // Welcome, Jane!

// By-reference binding
$counter = 0;
$increment = function() use (&$counter) {
    $counter++;
};

$increment();
$increment();
echo $counter;  // 2

// As callbacks
$numbers = [1, 2, 3, 4, 5];
$multiplier = 2;

$doubled = array_map(
    fn($n) => $n * $multiplier,  // PHP 7.4 arrow function
    $numbers
);

// Closure as object method
class Calculator {
    private int $base = 10;

    public function getAdder(): Closure {
        return function($value) {
            return $this->base + $value;  // Access $this
        };
    }
}

$calc = new Calculator();
$adder = $calc->getAdder();
echo $adder(5);  // 15

// bindTo - change closure scope
class A {
    private $value = 'A';
}

class B {
    private $value = 'B';
}

$closure = function() {
    return $this->value;
};

$bindToA = $closure->bindTo(new A(), A::class);
$bindToB = $closure->bindTo(new B(), B::class);

echo $bindToA();  // A
echo $bindToB();  // B
```

**Follow-up:**
- What's the difference between `use` and `use (&$var)`?
- When would you use `bindTo()`?
- Explain arrow functions (PHP 7.4+)

**Key Points:**
- All closures are anonymous functions
- `use` captures parent scope variables
- `use (&$var)` captures by reference
- Arrow functions (`fn`) auto-capture variables
- `bindTo()` changes closure scope

---

---

## Question 9: Explain weak vs strict typing modes in PHP.

**Answer:**

PHP 7.0+ supports two type checking modes that control how type declarations are enforced.

### Strict Types Declaration

```php
<?php
// Must be at the very top of file (first statement)
declare(strict_types=1);

// Only affects current file
// Does not affect included/required files
```

### Weak Typing (Default)

```php
<?php
// No declare statement = weak/coercive typing

function add(int $a, int $b): int {
    return $a + $b;
}

// Type coercion happens automatically
echo add(5, 10);        // 15
echo add('5', '10');    // 15 (strings converted to int)
echo add(5.9, 10.2);    // 15 (floats truncated to int)
echo add('5.9', '10');  // 15 (string → float → int)
echo add(true, false);  // 1 (bool → int: true=1, false=0)

// Only fails on incompatible types
try {
    echo add('hello', 'world');  // TypeError
} catch (TypeError $e) {
    echo $e->getMessage();  // must be of type int, string given
}
```

### Strict Typing

```php
<?php
declare(strict_types=1);

function add(int $a, int $b): int {
    return $a + $b;
}

echo add(5, 10);        // ✅ 15

// All of these throw TypeError:
add('5', '10');         // ❌ TypeError
add(5.9, 10.2);         // ❌ TypeError
add(true, false);       // ❌ TypeError

// Must pass exact type
add((int)'5', (int)'10');  // ✅ Works after explicit cast
```

### Differences in Practice

```php
// file1.php - Weak typing
<?php
function processAge(int $age): string {
    return "Age: $age";
}

echo processAge('25');    // ✅ Works - string coerced to int
echo processAge(25.7);    // ✅ Works - float truncated to int

// file2.php - Strict typing
<?php
declare(strict_types=1);

function processAge(int $age): string {
    return "Age: $age";
}

echo processAge(25);      // ✅ Works
echo processAge('25');    // ❌ TypeError
echo processAge(25.7);    // ❌ TypeError

// Must be explicit
echo processAge((int)'25');   // ✅ Works
```

### Return Type Coercion

```php
// Weak typing
function divide(int $a, int $b): int {
    return $a / $b;  // Returns float, coerced to int
}

echo divide(10, 3);  // 3 (3.333... truncated to 3)
echo divide(10, 4);  // 2 (2.5 truncated to 2)

// Strict typing
declare(strict_types=1);

function divide(int $a, int $b): int {
    return $a / $b;  // ❌ TypeError - can't return float as int
}

// Must explicitly convert
function divide(int $a, int $b): int {
    return (int)($a / $b);  // ✅ Explicit cast
}
```

### Nullable Types

```php
<?php
declare(strict_types=1);

// Nullable parameter
function greet(?string $name): string {
    return $name ?? 'Guest';
}

greet('John');   // ✅ "John"
greet(null);     // ✅ "Guest"
greet();         // ❌ Too few arguments

// Nullable return
function findUser(int $id): ?User {
    return User::find($id);  // Can return User or null
}

$user = findUser(1);  // User or null
```

### Union Types (PHP 8.0+)

```php
<?php
declare(strict_types=1);

function process(int|float $number): int|float {
    return $number * 2;
}

process(5);      // ✅ int
process(5.5);    // ✅ float
process('5');    // ❌ TypeError (even though convertible)

// Multiple types
function display(string|int|bool $value): void {
    echo $value;
}

display('hello');  // ✅
display(42);       // ✅
display(true);     // ✅
display(1.5);      // ❌ TypeError
```

### Mixed Type (PHP 8.0+)

```php
<?php
declare(strict_types=1);

// Mixed accepts any type (equivalent to not having type hint)
function debug(mixed $value): void {
    var_dump($value);
}

debug('string');   // ✅
debug(123);        // ✅
debug(true);       // ✅
debug([1, 2, 3]);  // ✅
debug(null);       // ✅
```

### Practical Examples

```php
// file: UserController.php (strict mode)
<?php
declare(strict_types=1);

class UserController {
    public function __construct(
        private UserRepository $users,
        private Validator $validator
    ) {}

    public function store(Request $request): JsonResponse {
        // Must be explicit about types
        $age = (int) $request->input('age');
        $active = (bool) $request->input('active');

        $user = new User(
            name: $request->input('name'),
            age: $age,
            active: $active
        );

        $this->users->save($user);

        return response()->json($user, 201);
    }
}

// file: helpers.php (weak mode for convenience)
<?php
// No declare statement

function env(string $key, mixed $default = null): mixed {
    // Weak typing allows flexibility
    return $_ENV[$key] ?? $default;
}

// Returns int, string, bool, whatever .env has
$debug = env('APP_DEBUG', false);  // Works with any type
$port = env('APP_PORT', 8000);     // Works with int or string
```

### When to Use Each

| Use Weak Typing | Use Strict Typing |
|----------------|-------------------|
| Helper functions with flexible input | Business logic with strict contracts |
| Configuration parsers | API controllers |
| Framework/library code | Domain models |
| Backward compatibility | New greenfield projects |
| Legacy codebases | Type-safe applications |

### Performance

```php
// No significant performance difference
// Type checking happens at runtime regardless
// Strict mode just changes error vs coercion behavior

// Both are fast:
// - Weak: coercion has minimal overhead
// - Strict: type check has minimal overhead
```

**Follow-up:**
- Does strict_types affect function calls in other files?
- Can you mix strict and weak typing in one project?
- What happens with array type coercion?

**Key Points:**
- Default = weak typing (automatic type coercion)
- `declare(strict_types=1)` = strict typing
- Strict types only affect current file
- Strict mode: no automatic coercion, exact types required
- Nullable types: `?Type` allows null
- Union types (PHP 8): `Type1|Type2`
- Use strict for business logic, weak for flexible helpers

---

## Question 10: Explain the HTTP request lifecycle from DNS resolution to response.

**Answer:**

Understanding the complete HTTP lifecycle helps debug performance issues and security concerns.

### Complete Lifecycle Overview

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           HTTP REQUEST LIFECYCLE                            │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  1. DNS RESOLUTION                                                           │
│     Browser Cache → OS Cache → Resolver → Root NS → TLD NS → Authoritative  │
│                              ↓                                               │
│  2. TCP CONNECTION                                                           │
│     SYN → SYN-ACK → ACK (3-way handshake)                                    │
│     TLS Handshake (HTTPS)                                                   │
│                              ↓                                               │
│  3. HTTP REQUEST                                                             │
│     Method + Path + Headers + Body                                           │
│                              ↓                                               │
│  4. SERVER PROCESSING                                                        │
│     Web Server → PHP FPM → Application → Database                           │
│                              ↓                                               │
│  5. HTTP RESPONSE                                                            │
│     Status Code + Headers + Body                                             │
│                              ↓                                               │
│  6. TCP TERMINATION                                                          │
│     FIN → FIN-ACK → ACK                                                      │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Step 1: DNS Resolution

```bash
# DNS resolution sequence
1. Browser checks its own cache (Chrome: chrome://net-internals/#dns)
2. OS checks DNS cache and hosts file
3. OS queries configured DNS resolver (usually ISP or 8.8.8.8)
4. Resolver queries root nameserver (.)
5. Root returns TLD nameserver (.com, .org, etc.)
6. Resolver queries TLD nameserver
7. TLD returns authoritative nameserver for domain
8. Resolver queries authoritative nameserver
9. Authoritative returns IP address
10. Resolver caches result and returns IP to browser

# TTL (Time To Live) controls caching duration
# Typical: 300 seconds (5 min) to 86400 (24 hours)
```

```php
// PHP: DNS lookup
$ip = gethostbyname('example.com');           // Blocking
$ips = dns_get_record('example.com', DNS_A);  // Get all A records

// For async DNS in Laravel
use Illuminate\Support\Facades\Cache;

Cache::remember('dns:example.com', 300, function () {
    return gethostbyname('example.com');
});
```

### Step 2: TCP Connection (3-Way Handshake)

```
Client                                    Server
  │                                         │
  │──────────── SYN (seq=x) ─────────────────▶│
  │                                         │
  │◀────────── SYN-ACK (seq=y, ack=x+1) ─────│
  │                                         │
  │──────────── ACK (ack=y+1) ──────────────▶│
  │                                         │
  │◀═════════════ Connection Established ════│
```

```bash
# Connection types
TCP 80   → Unencrypted HTTP
TCP 443  → Encrypted HTTPS with TLS
UDP 443  → QUIC/HTTP3 (no handshake, faster)

# TLS Handshake (additional round trips)
Client Hello (TLS version, ciphers, random)
      ↓
Server Hello (chosen cipher, certificate)
      ↓
Certificate verification (CA chain)
      ↓
Key exchange (DH or RSA)
      ↓
Encrypted channel established
```

### Step 3: HTTP Request Structure

```http
GET /api/users?page=1 HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer eyJhbG...
Content-Type: application/json
User-Agent: Mozilla/5.0...
Cache-Control: no-cache

{
  "filter": "active"
}
```

```php
// PHP: Viewing raw request
// In CLI: 
php -S localhost:8000 -t public

// Access request data in PHP
$_SERVER['REQUEST_METHOD'];      // GET, POST, PUT, DELETE
$_SERVER['REQUEST_URI'];         // /api/users?page=1
$_SERVER['HTTP_HOST'];           // api.example.com
$_SERVER['HTTP_AUTHORIZATION'];  // Bearer token

// GET parameters
$_GET['page'];                   // 1

// POST parameters
$_POST['name'];                  // submitted form data

// Raw body (for JSON, file uploads)
$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);
```

### Step 4: Server Processing

```bash
# Typical Laravel request flow on server
┌──────────────────────────────────────────────────────────┐
│ 1. nginx/apache (web server)                             │
│    - Serves static files                                 │
│    - Terminates TLS                                       │
│    - Proxies PHP to FPM                                  │
│                                                          │
│ 2. PHP-FPM (FastCGI Process Manager)                     │
│    - Worker pool (static, dynamic, ondemand)             │
│    - Listens on socket or port                           │
│    - Spawns/reccycles PHP processes                     │
│                                                          │
│ 3. Laravel Application                                   │
│    - Bootstrappers                                       │
│    - Middleware                                          │
│    - Router                                              │
│    - Controller                                          │
│    - Database query                                      │
│    - Response                                            │
└──────────────────────────────────────────────────────────┘

# nginx config example
location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

```php
// Laravel request lifecycle (simplified)
public function index(Request $request) {
    // 1. Request object already created
    // 2. Global middleware already ran
    // 3. Route matched
    
    $users = User::where('active', true)
        ->paginate($request->input('per_page', 15));
    
    // 4. Business logic executed
    // 5. Response prepared
    return response()->json($users);
}
```

### Step 5: HTTP Response Structure

```http
HTTP/1.1 200 OK
Server: nginx/1.24.0
Date: Thu, 19 Mar 2026 10:30:00 GMT
Content-Type: application/json
Content-Length: 1234
Cache-Control: max-age=3600
X-Request-Id: abc123

{
  "data": [...],
  "meta": {...}
}
```

### Step 6: Connection Handling & Caching

```bash
# Connection types
Connection: keep-alive    # Reuse TCP connection (HTTP/1.1 default)
Connection: close         # Close after response
Transfer-Encoding: chunked  # Stream response

# HTTP/2 & HTTP/3 improvements
HTTP/1.1: 6-8 parallel connections
HTTP/2:    Multiplexed streams (1 connection)
HTTP/3:    QUIC protocol (no head-of-line blocking)
```

```php
// PHP/Laravel: Response caching headers
response()->headers->set('Cache-Control', 'max-age=3600, public');

// ETag for conditional requests
$etag = md5($response->getContent());
response()->headers->set('ETag', $etag);

if (request()->header('If-None-Match') === $etag) {
    return response('', 304);  // Not Modified
}

// Browser caching
response()->headers->set('Expires', 'Thu, 19 Mar 2026 12:00:00 GMT');
```

### Performance: Timing Breakdown

```php
// Measure request lifecycle in Laravel
// Add to bootstrap/app.php or middleware

class RequestTimingMiddleware {
    public function handle($request, $next) {
        $start = hrtime(true);
        
        $response = $next($request);
        
        $end = hrtime(true);
        $duration = ($end - $start) / 1e6; // ms
        
        $response->headers->set('X-Response-Time', $duration . 'ms');
        
        return $response;
    }
}

// Typical timing breakdown
DNS:       5-50ms      (can be cached)
TCP:       10-100ms    (TLS adds 20-200ms)
TTFB:      50-500ms    (server processing)
Transfer:  10-100ms    (based on size)
                             
# Total:   ~100-500ms for dynamic content
# CDN:     Can reduce to 20-50ms for cached content
```

### Security Considerations

```php
// Mitigate common attacks at each stage

// 1. DNS: Prevent DNS rebinding
// nginx: 
// server_name ~^([a-z0-9-]+\.)?mydomain\.com$;

// 2. TLS: Enforce secure cipher suites
// nginx:
// ssl_protocols TLSv1.2 TLSv1.3;
// ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256;

// 3. HTTP: Security headers
response()->headers->set('X-Content-Type-Options', 'nosniff');
response()->headers->set('X-Frame-Options', 'DENY');
response()->headers->set('X-XSS-Protection', '1; mode=block');
response()->headers->set('Strict-Transport-Security', 'max-age=31536000');
response()->headers->set('Content-Security-Policy', "default-src 'self'");

// 4. Connection: Rate limiting
Route::middleware('throttle:60,1')->group(function () {
    // 60 requests per minute
});

// 5. Input validation (always sanitize)
$validated = $request->validate([
    'email' => 'required|email',
    'page' => 'integer|min:1',
]);
```

**Follow-up:**
- What's the difference between HTTP/1.1, HTTP/2, and HTTP/3?
- How does CDN affect the request lifecycle?
- What causes high TTFB (Time To First Byte)?

**Key Points:**
- DNS: Browser → OS → Resolver → Authoritative server
- TCP: 3-way handshake (SYN → SYN-ACK → ACK)
- TLS: Additional 2 round trips for HTTPS
- HTTP: Request line + headers + body
- Server: Web server → PHP → Application → Database
- Response: Status + headers + body
- Caching: Headers control browser/CDN caching

---

## Question 11: How to implement PHP autoload without composer (pure PHP, no Laravel)?

**Answer:**

Autoloading allows PHP to automatically load class files when they're first used, without manually including them. While Composer is the standard approach, you can implement custom autoloading.

### Using spl_autoload_register

```php
<?php
// index.php

// Basic autoloader using PSR-4-like convention
spl_autoload_register(function ($class) {
    // Convert namespace separators to directory separators
    $classPath = str_replace('\\', '/', $class) . '.php';
    
    // Define base directories for different namespaces
    $baseDirs = [
        'App/' => __DIR__ . '/src/',
        'Database/' => __DIR__ . '/database/',
        'Helpers/' => __DIR__ . '/helpers/',
    ];
    
    foreach ($baseDirs as $prefix => $baseDir) {
        // Check if class starts with this prefix
        if (strpos($class, $prefix) === 0) {
            $relativeClass = substr($class, strlen($prefix));
            $file = $baseDir . $relativeClass;
            
            if (file_exists($file)) {
                require $file;
                return true;
            }
        }
    }
    
    return false;
});

// Now you can use classes without explicit includes
$user = new App\Models\User();
$db = new Database\Connection();
```

### Manual PSR-4 Implementation

```php
<?php
// Autoloader.php

class Autoloader
{
    private array $prefixes = [];
    private array $paths = [];
    
    /**
     * Add a namespace prefix to lookup path
     */
    public function addNamespace(string $prefix, string $path): self
    {
        $this->prefixes[rtrim($prefix, '\\')] = rtrim($path, '/');
        return $this;
    }
    
    /**
     * Add multiple namespaces at once
     */
    public function addNamespaces(array $namespaces): self
    {
        foreach ($namespaces as $prefix => $path) {
            $this->addNamespace($prefix, $path);
        }
        return $this;
    }
    
    /**
     * Add a fallback directory (for non-namespaced classes)
     */
    public function addPath(string $path): self
    {
        $this->paths[] = rtrim($path, '/');
        return $this;
    }
    
    /**
     * Register the autoloader
     */
    public function register(): void
    {
        spl_autoload_register([$this, 'loadClass']);
    }
    
    /**
     * Load a class file
     */
    public function loadClass(string $class): bool
    {
        // Check namespace prefixes
        foreach ($this->prefixes as $prefix => $path) {
            if (strpos($class, $prefix . '\\') === 0) {
                $relativeClass = substr($class, strlen($prefix) + 1);
                $file = $path . '/' . str_replace('\\', '/', $relativeClass) . '.php';
                
                if (file_exists($file)) {
                    require $file;
                    return true;
                }
            }
        }
        
        // Check fallback paths
        foreach ($this->paths as $path) {
            $file = $path . '/' . $class . '.php';
            if (file_exists($file)) {
                require $file;
                return true;
            }
        }
        
        return false;
    }
}

// Usage
$autoloader = new Autoloader();
$autoloader
    ->addNamespace('App', __DIR__ . '/src')
    ->addNamespace('Database', __DIR__ . '/database')
    ->addNamespace('Framework', __DIR__ . '/framework')
    ->addPath(__DIR__ . '/classes')  // For non-namespaced classes
    ->register();
```

### Class Map Autoloading

```php
<?php
// Faster but requires manual maintenance

class ClassMapAutoloader
{
    private array $classMap = [];
    
    public function __construct(array $classMap = [])
    {
        $this->classMap = $classMap;
    }
    
    public function addClass(string $class, string $file): self
    {
        $this->classMap[$class] = $file;
        return $this;
    }
    
    public function addClassesFromArray(array $classes): self
    {
        $this->classMap = array_merge($this->classMap, $classes);
        return $this;
    }
    
    public function loadClass(string $class): bool
    {
        if (isset($this->classMap[$class])) {
            require $this->classMap[$class];
            return true;
        }
        return false;
    }
    
    public function register(): void
    {
        spl_autoload_register([$this, 'loadClass']);
    }
}

// Generate class map
$classMap = [
    'App\\Models\\User' => __DIR__ . '/src/Models/User.php',
    'App\\Models\\Post' => __DIR__ . '/src/Models/Post.php',
    'Database\\Connection' => __DIR__ . '/database/Connection.php',
];

$autoloader = new ClassMapAutoloader($classMap);
$autoloader->register();
```

### File-Based Autoloading (Simple)

```php
<?php
// For simpler projects without namespaces

spl_autoload_register(function ($class) {
    // Convert underscores to directory separators (PSR-0 style)
    $file = __DIR__ . '/classes/' . str_replace('_', '/', $class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// With directories
spl_autoload_register(function ($class) {
    $dirs = [
        __DIR__ . '/models/',
        __DIR__ . '/controllers/',
        __DIR__ . '/lib/',
    ];
    
    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});
```

### Autoloading with Include Paths

```php
<?php
// Use set_include_path for multiple directories

set_include_path(
    get_include_path() . 
    PATH_SEPARATOR . __DIR__ . '/src' . 
    PATH_SEPARATOR . __DIR__ . '/lib'
);

// Now PHP will look in these directories
spl_autoload_register();

// Example: will look in src/User.php, lib/User.php
$user = new User();
```

### Complete Example Without Composer

```php
<?php
// public/index.php

// src/Autoloader.php
require_once __DIR__ . '/../src/Autoloader.php';

use App\Core\Router;
use App\Core\Database;

// Initialize autoloader
$autoloader = new Autoloader();
$autoloader
    ->addNamespace('App', __DIR__ . '/../src')
    ->addNamespace('App\\Controllers', __DIR__ . '/../src/Controllers')
    ->addNamespace('App\\Models', __DIR__ . '/../src/Models')
    ->addNamespace('App\\Services', __DIR__ . '/../src/Services')
    ->register();

// Directory structure
// src/
//   Autoloader.php
//   Controllers/
//     HomeController.php
//   Models/
//     User.php
//   Services/
//     AuthService.php
// public/
//   index.php

// src/Controllers/HomeController.php
namespace App\Controllers;

class HomeController
{
    public function index(): void
    {
        echo "Welcome to the homepage!";
    }
}

// Usage
$controller = new App\Controllers\HomeController();
$controller->index();
```

### Comparison

| Method | Pros | Cons |
|--------|------|------|
| `spl_autoload_register` | Flexible, standard | Manual setup |
| Namespace-based | PSR-4 compatible | Requires namespace structure |
| Class map | Fast lookup | Manual maintenance |
| Simple directories | Easy to understand | No namespaces |

### Best Practices

```php
<?php
// Always check file exists before requiring
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Use __DIR__ for relative paths
// Makes autoloader portable

// Return boolean to allow other autoloaders to try
return false;  // Class not found, try next autoloader

// Register in correct order - more specific first
spl_autoload_register($specificLoader);  // Custom namespaces
spl_autoload_register($genericLoader);   // Fallback
```

**Follow-up:**
- What's the difference between PSR-0 and PSR-4?
- How does Composer autoloading work under the hood?
- Can you mix autoloading methods?

**Key Points:**
- `spl_autoload_register` is the standard way
- Convert namespace separators to path separators
- Return `false` to allow fallback autoloaders
- Use `__DIR__` for relative paths
- PSR-4 is the modern standard (class per file, namespace = directory)

---

## Notes

Add more questions covering:
- Error handling (exceptions, try-catch-finally)
- SPL (Standard PHP Library)
- Iterators and ArrayAccess
- Late static binding
- Variadic functions and argument unpacking
- PHP 8 features (named arguments, attributes, match expression)
