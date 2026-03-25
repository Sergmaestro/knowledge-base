# PHP Advanced Topics

## Question 1: How do you create and use custom exceptions in PHP?

**Answer:**

Custom exceptions allow you to create specific error types for your application logic.

### Basic Custom Exception

```php
<?php
namespace App\Exceptions;

class PaymentException extends \Exception
{
    public function __construct(
        string $message = "Payment processing failed",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getContext(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}

// Usage
if (!$paymentGateway->charge($amount)) {
    throw new PaymentException("Failed to charge customer", 500);
}
```

### Exception with Additional Context

```php
<?php
class OrderNotFoundException extends \Exception
{
    private int $orderId;
    private string $userId;

    public function __construct(int $orderId, string $userId)
    {
        $this->orderId = $orderId;
        $this->userId = $userId;

        parent::__construct(
            "Order #{$orderId} not found for user {$userId}",
            404
        );
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function toArray(): array
    {
        return [
            'error' => 'order_not_found',
            'order_id' => $this->orderId,
            'user_id' => $this->userId,
            'message' => $this->getMessage(),
        ];
    }
}

// Usage
try {
    $order = Order::findOrFail($orderId);
} catch (ModelNotFoundException $e) {
    throw new OrderNotFoundException($orderId, auth()->id());
}
```

### Exception Hierarchy

```php
<?php
// Base application exception
abstract class ApplicationException extends \Exception
{
    protected array $context = [];

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }
}

// Domain-specific exceptions
class ValidationException extends ApplicationException
{
    private array $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct("Validation failed", 422);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

class AuthenticationException extends ApplicationException
{
    public function __construct(string $message = "Authentication required")
    {
        parent::__construct($message, 401);
    }
}

class AuthorizationException extends ApplicationException
{
    private string $ability;
    private $resource;

    public function __construct(string $ability, $resource = null)
    {
        $this->ability = $ability;
        $this->resource = $resource;

        parent::__construct(
            "User not authorized to {$ability}",
            403
        );
    }
}

// Business logic exceptions
class InsufficientFundsException extends ApplicationException
{
    private float $required;
    private float $available;

    public function __construct(float $required, float $available)
    {
        $this->required = $required;
        $this->available = $available;

        parent::__construct(
            "Insufficient funds: required {$required}, available {$available}",
            400
        );
    }

    public function getShortfall(): float
    {
        return $this->required - $this->available;
    }
}
```

### Laravel Exception Handler Integration

```php
<?php
// app/Exceptions/Handler.php
class Handler extends ExceptionHandler
{
    public function register()
    {
        $this->reportable(function (PaymentException $e) {
            // Custom reporting logic
            Log::critical('Payment failed', [
                'exception' => $e->getMessage(),
                'context' => $e->getContext(),
            ]);

            // Alert payment team
            Notification::route('slack', env('SLACK_PAYMENT_WEBHOOK'))
                ->notify(new PaymentFailedNotification($e));
        });

        $this->renderable(function (OrderNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json($e->toArray(), $e->getCode());
            }

            return redirect()->route('orders.index')
                ->with('error', $e->getMessage());
        });

        $this->renderable(function (InsufficientFundsException $e, $request) {
            return response()->json([
                'error' => 'insufficient_funds',
                'message' => $e->getMessage(),
                'required' => $e->getShortfall(),
            ], 400);
        });
    }
}
```

### Exception Best Practices

```php
<?php
// ✅ Good: Specific exceptions
class EmailAlreadyTakenException extends ApplicationException {}
class InvalidCouponException extends ApplicationException {}
class ProductOutOfStockException extends ApplicationException {}

// ❌ Bad: Generic exceptions
throw new Exception("Error"); // Too vague
throw new RuntimeException("Something failed"); // No context

// ✅ Good: Include context
throw new PaymentException("Stripe charge failed")
    ->setContext([
        'amount' => $amount,
        'currency' => 'USD',
        'customer_id' => $customerId,
        'stripe_error' => $stripeError,
    ]);

// ✅ Good: Chain exceptions
try {
    $client->makeRequest();
} catch (GuzzleException $e) {
    throw new ExternalApiException(
        "Failed to connect to payment gateway",
        0,
        $e  // Previous exception
    );
}

// ✅ Good: Catch specific exceptions
try {
    $order->process();
} catch (InsufficientFundsException $e) {
    // Handle insufficient funds
} catch (ProductOutOfStockException $e) {
    // Handle out of stock
} catch (ApplicationException $e) {
    // Handle other app exceptions
}
```

**Follow-up:**
- When should you create a custom exception vs using built-in ones?
- How do you handle exception translation at API boundaries?
- What's the difference between checked and unchecked exceptions?

**Key Points:**
- Custom exceptions for domain-specific errors
- Include context and additional data
- Create exception hierarchy for better handling
- Integrate with Laravel's exception handler
- Chain exceptions to preserve context
- Specific exceptions > generic exceptions

---

## Question 2: Explain PHP metaprogramming techniques (Reflection, Magic Methods).

**Answer:**

Metaprogramming allows code to inspect and modify itself at runtime.

### Reflection API

```php
<?php
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class User
{
    private int $id;
    protected string $name;
    public string $email;

    public function __construct(int $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    private function getSecret(): string
    {
        return 'secret';
    }
}

// Inspect class
$reflection = new ReflectionClass(User::class);

// Get class info
echo $reflection->getName();        // "User"
echo $reflection->getNamespaceName(); // Namespace
echo $reflection->getShortName();   // "User"

// Get properties
foreach ($reflection->getProperties() as $property) {
    echo $property->getName() . ': ' . $property->getType();
    echo $property->isPublic() ? 'public' : 'private';
}

// Get methods
foreach ($reflection->getMethods() as $method) {
    echo $method->getName();
    echo $method->getNumberOfParameters();
}

// Create instance dynamically
$user = $reflection->newInstance(1, 'John');

// Access private properties
$idProperty = $reflection->getProperty('id');
$idProperty->setAccessible(true);
echo $idProperty->getValue($user); // 1
$idProperty->setValue($user, 2);

// Call private methods
$secretMethod = $reflection->getMethod('getSecret');
$secretMethod->setAccessible(true);
echo $secretMethod->invoke($user); // "secret"
```

### Reflection API Use Cases in Depth

#### 1. Building a Dependency Injection Container

```php
<?php
class Container
{
    private array $bindings = [];
    private array $instances = [];

    public function bind(string $abstract, callable $concrete)
    {
        $this->bindings[$abstract] = $concrete;
    }

    public function singleton(string $abstract, callable $concrete = null)
    {
        $this->bindings[$abstract] = function ($container) use ($concrete) {
            static $instance;
            if ($instance === null) {
                $instance = $concrete($container);
            }
            return $instance;
        };
    }

    public function resolve(string $abstract)
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            $instance = $this->bindings[$abstract]($this);
            if (is_object($instance)) {
                $this->instances[$abstract] = $instance;
            }
            return $instance;
        }

        return $this->build($abstract);
    }

    private function build(string $concrete)
    {
        $reflection = new ReflectionClass($concrete);

        if (!$reflection->isInstantiable()) {
            throw new Exception("{$concrete} is not instantiable");
        }

        $constructor = $reflection->getConstructor();

        // No constructor = simple instantiation
        if (is_null($constructor)) {
            return new $concrete;
        }

        // Get constructor parameters
        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if ($type && !$type->isBuiltin()) {
                // Resolve dependency recursively
                $dependencies[] = $this->resolve($type->getName());
            } else {
                // Use default value or throw error
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new Exception("Cannot resolve {$parameter->getName()}");
                }
            }
        }

        return $reflection->newInstanceArgs($dependencies);
    }
}

// Usage
$container = new Container();
$service = $container->resolve(UserService::class);
// Automatically resolves and injects dependencies
```

### Magic Methods

```php
<?php
class MagicModel
{
    private array $attributes = [];
    private array $relations = [];

    // __get - Property access
    public function __get(string $name)
    {
        // Check attributes
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        // Check relations
        if (array_key_exists($name, $this->relations)) {
            return $this->relations[$name];
        }

        throw new Exception("Property {$name} does not exist");
    }

    // __set - Property assignment
    public function __set(string $name, $value)
    {
        $this->attributes[$name] = $value;
    }

    // __isset - isset() and empty()
    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    // __unset - unset()
    public function __unset(string $name)
    {
        unset($this->attributes[$name]);
    }

    // __call - Method call
    public function __call(string $name, array $arguments)
    {
        // Magic where methods
        if (str_starts_with($name, 'where')) {
            $field = strtolower(substr($name, 5));
            return $this->where($field, $arguments[0]);
        }

        // Magic get methods
        if (str_starts_with($name, 'get')) {
            $field = strtolower(substr($name, 3));
            return $this->attributes[$field] ?? null;
        }

        throw new Exception("Method {$name} does not exist");
    }

    // __callStatic - Static method call
    public static function __callStatic(string $name, array $arguments)
    {
        if ($name === 'create') {
            $instance = new static();
            foreach ($arguments[0] as $key => $value) {
                $instance->$key = $value;
            }
            return $instance;
        }

        throw new Exception("Static method {$name} does not exist");
    }

    // __toString - String conversion
    public function __toString(): string
    {
        return json_encode($this->attributes);
    }

    // __invoke - Call as function
    public function __invoke(...$args)
    {
        return $this->execute(...$args);
    }

    // __serialize - Custom serialization (PHP 7.4+)
    public function __serialize(): array
    {
        return $this->attributes;
    }

    // __unserialize - Custom unserialization (PHP 7.4+)
    public function __unserialize(array $data): void
    {
        $this->attributes = $data;
    }

    // __clone - Object cloning
    public function __clone()
    {
        // Deep clone relations
        $this->relations = [];
    }

    // __debugInfo - var_dump() output
    public function __debugInfo(): array
    {
        return [
            'attributes' => $this->attributes,
            'relations' => array_keys($this->relations),
        ];
    }
}

// Usage
$model = new MagicModel();
$model->name = 'John';           // Calls __set
echo $model->name;               // Calls __get
echo isset($model->name);        // Calls __isset
$model->whereEmail('john@example.com'); // Calls __call
echo $model;                     // Calls __toString
$clone = clone $model;           // Calls __clone
```

## Question 5: How do PHP annotations and attributes (PHP 8) work under the hood?

**Answer:**

PHP has two approaches for adding metadata to classes, methods, and properties: annotations (docblock comments) and PHP 8 attributes.

### Annotations (DocBlock Comments)

Annotations are comments parsed by external libraries like Doctrine Annotations:

```php
<?php
use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class Route
{
    public string $method;
    public string $path;

    public function __construct(array $data)
    {
        if (!isset($data['method']) || !isset($data['path'])) {
            throw new \InvalidArgumentException('Missing method or path');
        }
        $this->method = $data['method'];
        $this->path = $data['path'];
    }
}

/**
 * @Route(method="GET", path="/users")
 */
class UserController
{
    /**
     * @Route(method="POST", path="/users")
     */
    public function store() {}
}

// Reading annotations (using Doctrine)
$reader = new \Doctrine\Common\Annotations\AnnotationReader();
$reflectionClass = new \ReflectionClass(UserController::class);

// Get class annotations
$classAnnotations = $reader->getClassAnnotations($reflectionClass);
foreach ($classAnnotations as $annotation) {
    if ($annotation instanceof Route) {
        echo "Route: {$annotation->method} {$annotation->path}\n";
    }
}

// Get method annotations
$reflectionMethod = new \ReflectionMethod(UserController::class, 'store');
$methodAnnotations = $reader->getMethodAnnotations($reflectionMethod);
```

### How Annotations Work (Under the Hood)

```
Source Code → DocBlock Parser → AST/Array → Annotation Processor

1. PHP reads the file as text
2. DocBlock parser extracts comment content using regex
3. Parses @annotation(value) syntax
4. Creates objects via reflection on annotation classes
5. Caches results (important for performance)
```

### PHP 8 Attributes (Native)

PHP 8 introduced native attributes as first-class language syntax:

```php
<?php
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(
        public string $method,
        public string $path,
        public array $middleware = []
    ) {}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(
        public string $name,
        public string $type = 'string'
    ) {}
}

#[Route(method: 'GET', path: '/users', middleware: ['auth'])]
class UserController
{
    #[Route(method: 'POST', path: '/users')]
    #[Authenticate]
    public function store(
        #[Column(name: 'user_data', type: 'json')]
        array $data
    ) {}
}
```

### How PHP 8 Attributes Work (Under the Hood)

```
Source Code → Tokenizer (php -w or token_get_all) 
           → AST Parser (php-ast extension)
           → ReflectionAttribute objects
           → Application code

Key insight: PHP 8 attributes are parsed at compile time into the AST,
not at runtime like docblock comments!
```

### Attribute Reflection API

```php
<?php
$reflection = new ReflectionClass(UserController::class);

// Get all attributes
$attributes = $reflection->getAttributes();

// Filter by type
$routeAttributes = $reflection->getAttributes(Route::class);

// Read attribute data
foreach ($routeAttributes as $attribute) {
    $route = $attribute->newInstance();
    echo $route->method;  // GET
    echo $route->path;    // /users
}

// Check if attribute exists (without instantiating)
$hasRoute = $reflection->hasAttribute(Route::class);

// Get arguments without instantiation
$args = $attribute->getArguments();  // ['method' => 'GET', 'path' => '/users']
```

### Attribute Flags

```php
<?php
#[Attribute]                                    // Default: TARGET_CLASS
#[Attribute(Attribute::TARGET_CLASS)]           // Class only
#[Attribute(Attribute::TARGET_METHOD)]          // Methods only
#[Attribute(Attribute::TARGET_PROPERTY)]        // Properties only
#[Attribute(Attribute::TARGET_FUNCTION)]        // Functions only
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)] // Class constants
#[Attribute(Attribute::TARGET_PARAMETER)]      // Function parameters
#[Attribute(Attribute::TARGET_ALL)]            // Anywhere

// Can also specify if multiple targets
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Route {}
```

### Performance Comparison

```php
<?php
// Doctrine Annotations (runtime parsing - slower)
$start = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    $reader->getClassAnnotations($reflectionClass);
}
echo "Doctrine: " . (microtime(true) - $start) . "s\n";  // ~0.1s

// PHP 8 Attributes (compile-time - faster)
$start = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    $reflectionClass->getAttributes(Route::class);
}
echo "Attributes: " . (microtime(true) - $start) . "s\n";  // ~0.001s
// ~100x faster!
```

### Building a Custom Attribute Framework

```php
<?php
// 1. Define attribute
#[Attribute]
class Entity
{
    public function __construct(
        public string $table,
        public string $primaryKey = 'id'
    ) {}
}

#[Attribute]
class Field
{
    public function __construct(
        public string $column,
        public bool $fillable = false
    ) {}
}

// 2. Metadata reader
class AttributeMetadataReader
{
    public function read(string $class): array
    {
        $reflection = new ReflectionClass($class);
        
        $metadata = [
            'table' => null,
            'fields' => [],
        ];

        // Get class-level attributes
        foreach ($reflection->getAttributes(Entity::class) as $attr) {
            $entity = $attr->newInstance();
            $metadata['table'] = $entity->table;
            $metadata['primaryKey'] = $entity->primaryKey;
        }

        // Get property-level attributes
        foreach ($reflection->getProperties() as $property) {
            foreach ($property->getAttributes(Field::class) as $attr) {
                $field = $attr->newInstance();
                $metadata['fields'][$property->getName()] = [
                    'column' => $field->column,
                    'fillable' => $field->fillable,
                ];
            }
        }

        return $metadata;
    }
}

// 3. Use in application
#[Entity(table: 'users', primaryKey: 'id')]
class User
{
    #[Field(column: 'name', fillable: true)]
    public string $name;

    #[Field(column: 'email', fillable: true)]
    public string $email;

    #[Field(column: 'password')]
    public string $password;
}

$reader = new AttributeMetadataReader();
$metadata = $reader->read(User::class);
print_r($metadata);
// [
//     'table' => 'users',
//     'primaryKey' => 'id',
//     'fields' => [
//         'name' => ['column' => 'name', 'fillable' => true],
//         'email' => ['column' => 'email', 'fillable' => true],
//         'password' => ['column' => 'password', 'fillable' => false],
//     ]
// ]
```

### Migration from Annotations to Attributes

```php
<?php
// Doctrine annotation
/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class User {}

// PHP 8 attribute
#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User {}

// Laravel migration example
// Before (docblock):
/**
 * @authenticated
 * @middleware(auth)
 */

// After (attribute):
#[Authenticated]
#[Middleware(['auth'])]
class UserController {}
```

### When to Use Which

| Feature | Annotations (DocBlock) | PHP 8 Attributes |
|---------|----------------------|------------------|
| Performance | Slower (runtime parsing) | Faster (compile-time) |
| IDE Support | Limited | Full (native syntax) |
| Syntax | Comment-based | First-class language |
| Libraries | Doctrine, Symfony | Native PHP 8+ |
| Backwards | PHP 7.4+ | PHP 8.0+ |

**Key Points:**
- Annotations: parsed from docblocks at runtime via reflection + regex
- Attributes: native language feature, parsed into AST at compile time
- PHP 8 attributes are ~100x faster than Doctrine annotations
- Use attributes for new code, annotations for legacy compatibility
- Both work with Reflection API to read metadata
- Can combine both: docblocks for docs, attributes for code

---

### Attribute-Based Metaprogramming (PHP 8.0+)

```php
<?php
// Define custom attributes
#[Attribute]
class Route
{
    public function __construct(
        public string $method,
        public string $path
    ) {}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Validate
{
    public function __construct(
        public array $rules
    ) {}
}

// Use attributes
class UserController
{
    #[Route('GET', '/users')]
    public function index() {}

    #[Route('POST', '/users')]
    public function store() {}
}

class CreateUserRequest
{
    #[Validate(['required', 'string', 'max:255'])]
    public string $name;

    #[Validate(['required', 'email', 'unique:users'])]
    public string $email;
}

// Read attributes with reflection
$reflection = new ReflectionClass(UserController::class);

foreach ($reflection->getMethods() as $method) {
    $attributes = $method->getAttributes(Route::class);

    foreach ($attributes as $attribute) {
        $route = $attribute->newInstance();
        echo "{$route->method} {$route->path} -> {$method->getName()}\n";
    }
}

// Validation with attributes
function validate(object $request): array
{
    $reflection = new ReflectionClass($request);
    $errors = [];

    foreach ($reflection->getProperties() as $property) {
        $attributes = $property->getAttributes(Validate::class);

        foreach ($attributes as $attribute) {
            $validator = $attribute->newInstance();
            $value = $property->getValue($request);

            foreach ($validator->rules as $rule) {
                if (!validateRule($value, $rule)) {
                    $errors[$property->getName()][] = "Validation failed: {$rule}";
                }
            }
        }
    }

    return $errors;
}
```

### Dynamic Code Generation

```php
<?php
// Generate class at runtime
$className = 'DynamicClass';
$code = <<<PHP
class {$className} {
    public function sayHello() {
        return "Hello from dynamic class";
    }
}
PHP;

eval($code); // ⚠️ Use with caution!

$instance = new DynamicClass();
echo $instance->sayHello();

// Better: Use anonymous classes (PHP 7.0+)
$object = new class {
    public function sayHello() {
        return "Hello from anonymous class";
    }
};

echo $object->sayHello();
```

**Follow-up:**
- When should you use Reflection vs magic methods?
- What are the performance implications?
- How does Laravel use Reflection for IoC container?

**Key Points:**
- Reflection: Inspect and modify classes at runtime
- Magic methods: Override default behavior
- Use for: DI containers, ORMs, serialization
- Performance cost: Cache reflection results
- PHP 8 attributes: Modern metaprogramming
- Avoid eval() when possible

---

## Question 3: How do you implement concurrency in PHP?

**Answer:**

PHP traditionally runs in a single-threaded synchronous model, but several approaches enable concurrency.

### Async/Await with ReactPHP

```php
<?php
require 'vendor/autoload.php';

use React\EventLoop\Loop;
use React\Promise\Promise;

// Async HTTP requests
use React\Http\Browser;

$browser = new Browser();

// Sequential (slow)
$response1 = file_get_contents('https://api1.example.com');
$response2 = file_get_contents('https://api2.example.com');
$response3 = file_get_contents('https://api3.example.com');
// Total: 3 seconds (1s each)

// Concurrent (fast)
$promises = [
    $browser->get('https://api1.example.com'),
    $browser->get('https://api2.example.com'),
    $browser->get('https://api3.example.com'),
];

\React\Promise\all($promises)->then(
    function (array $responses) {
        foreach ($responses as $response) {
            echo $response->getBody();
        }
    },
    function (Exception $error) {
        echo "Error: " . $error->getMessage();
    }
);

// Total: 1 second (all parallel)
```

### Parallel Processing with amphp

```php
<?php
use Amp\Loop;
use Amp\Promise;
use function Amp\call;
use function Amp\Promise\all;

// Concurrent tasks
$promises = [
    call(function () {
        $result = yield httpGet('https://api1.example.com');
        return $result;
    }),
    call(function () {
        $result = yield httpGet('https://api2.example.com');
        return $result;
    }),
    call(function () {
        $result = yield httpGet('https://api3.example.com');
        return $result;
    }),
];

$results = Promise\wait(all($promises));
```

### Multi-Processing with parallel Extension

```php
<?php
// Requires ext-parallel

use parallel\{Runtime, Channel, Events};

// Create parallel tasks
$runtime1 = new Runtime();
$runtime2 = new Runtime();

$future1 = $runtime1->run(function() {
    return heavyComputation1();
});

$future2 = $runtime2->run(function() {
    return heavyComputation2();
});

// Get results
$result1 = $future1->value();
$result2 = $future2->value();

// More complex example
$channel = new Channel();

$runtime = new Runtime();
$future = $runtime->run(function(Channel $channel) {
    for ($i = 0; $i < 10; $i++) {
        $channel->send(processItem($i));
    }
}, [$channel]);

while ($channel->recv($value)) {
    echo "Received: {$value}\n";
}
```

### Laravel Queues for Concurrency

```php
<?php
// Dispatch multiple jobs
$users = User::all();

foreach ($users as $user) {
    SendEmailJob::dispatch($user);
}

// All emails sent concurrently by queue workers
// Run multiple workers:
// php artisan queue:work --queue=emails --sleep=3 --tries=3

// Job chaining
ProcessOrder::withChain([
    new SendInvoice($order),
    new UpdateInventory($order),
    new NotifyWarehouse($order),
])->dispatch($order);

// Job batching (concurrent)
$batch = Bus::batch([
    new ProcessRow($row1),
    new ProcessRow($row2),
    // ... thousands more
])->dispatch();

// Concurrent API calls in job
class FetchMultipleAPIs implements ShouldQueue
{
    public function handle()
    {
        $client = new \GuzzleHttp\Client();

        $promises = [
            'user' => $client->getAsync('https://api.example.com/user'),
            'posts' => $client->getAsync('https://api.example.com/posts'),
            'comments' => $client->getAsync('https://api.example.com/comments'),
        ];

        $results = \GuzzleHttp\Promise\Utils::unwrap($promises);

        // All requests executed concurrently
    }
}
```

### Guzzle Concurrent Requests

```php
<?php
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

$client = new Client();

// Create promises
$promises = [
    'user' => $client->getAsync('https://api.example.com/user/1'),
    'posts' => $client->getAsync('https://api.example.com/posts'),
    'comments' => $client->getAsync('https://api.example.com/comments'),
];

// Wait for all
$results = Promise\Utils::unwrap($promises);

echo $results['user']->getBody();
echo $results['posts']->getBody();
echo $results['comments']->getBody();

// Or with each()
$promises = [];
for ($i = 1; $i <= 100; $i++) {
    $promises[] = $client->getAsync("https://api.example.com/users/{$i}");
}

// Process as they complete
$eachPromise = new Promise\EachPromise($promises, [
    'concurrency' => 10, // Limit concurrent requests
    'fulfilled' => function ($response, $index) {
        echo "Request {$index} completed\n";
    },
    'rejected' => function ($reason, $index) {
        echo "Request {$index} failed\n";
    },
]);

$eachPromise->promise()->wait();
```

### Swoole (Async PHP Runtime)

```php
<?php
// Requires swoole extension

use Swoole\Coroutine;
use function Swoole\Coroutine\run;

run(function () {
    // Concurrent coroutines
    $results = [];

    Coroutine::create(function () use (&$results) {
        $results['api1'] = file_get_contents('https://api1.example.com');
    });

    Coroutine::create(function () use (&$results) {
        $results['api2'] = file_get_contents('https://api2.example.com');
    });

    Coroutine::create(function () use (&$results) {
        $results['api3'] = file_get_contents('https://api3.example.com');
    });

    // All execute concurrently
});

// Swoole HTTP server (concurrent request handling)
$server = new Swoole\HTTP\Server("0.0.0.0", 9501);

$server->on("request", function ($request, $response) {
    // Each request handled in its own coroutine
    $response->end("Hello World");
});

$server->start();
```

### Process Forking (pcntl extension)

```php
<?php
// ⚠️ Only works on Unix-like systems

$pids = [];

for ($i = 0; $i < 5; $i++) {
    $pid = pcntl_fork();

    if ($pid == -1) {
        die("Fork failed");
    } elseif ($pid) {
        // Parent process
        $pids[] = $pid;
    } else {
        // Child process
        processTask($i);
        exit(0);
    }
}

// Wait for all children
foreach ($pids as $pid) {
    pcntl_waitpid($pid, $status);
}
```

**Follow-up:**
- What's the difference between async and parallel?
- When should you use queues vs async libraries?
- What are the limitations of PHP concurrency?

**Key Points:**
- Async: ReactPHP, amphp (single-threaded event loop)
- Parallel: ext-parallel, Swoole (multi-threaded)
- Laravel Queues: Distribute work across workers
- Guzzle: Concurrent HTTP requests
- Process forking: True multiprocessing (Unix only)
- Choose based on use case: I/O bound → async, CPU bound → parallel

---

## Question 4: SPL Libraries. What's the purpose and use cases?

**Answer**

In a Laravel-centric career, it is common to rely entirely on the framework's abstractions. However, the advantage of the
Standard PHP Library (SPL) lies in performance at scale and architectural precision where Laravel’s high-level wrappers may be too heavy or insufficiently granular.

### 1. Significant Memory Savings (SplFixedArray)

Laravel’s `Collection` is a wrapper around standard PHP arrays, which are actually ordered hash maps. This flexibility comes with high memory overhead.

- **The Use Case:** If your Laravel backend needs to process a massive list of integers or objects (e.g., millions of IDs for a reporting job or a search index), `SplFixedArray` uses 30% to 50% less memory and can be 25%+ faster than standard arrays because it allocates a contiguous block of memory.
- **Laravel Context:** Use this inside Artisan Commands or Job Queues where long-running processes must stay within strict memory limits.
  - [Stack Overflow](https://stackoverflow.com/questions/11827668/does-really-splfixedarray-perform-better-than-arrays)
  - [CodeEngineered](https://codeengineered.com/blog/11/9/splfixedarray-underutilized-php-gem/)
  - [medium.com](https://medium.com/@haijerome/php-arrays-memory-performance-when-and-when-not-to-use-them-ba435d30e6b7)

### 2. High-Performance File Streaming (SplFileObject)

While Laravel’s Storage Facade is great for cloud storage, it often lacks the granular control needed for complex local file parsing.

- **The Use Case:** When parsing a multi-gigabyte CSV or log file, `SplFileObject` allows you to seek to specific lines or iterate without loading the file into RAM.
- **Advantage:** You can extend `SplFileObject` to create custom file-type handlers (e.g., a `LogParser` class), which is more architecturally sound than writing procedural `fopen` logic inside a Controller.
  - [Reddit](https://www.reddit.com/r/PHP/comments/6cwdd9/process_large_files_using_php/ 'Process Large Files Using PHP')
  - [StackOverflow](https://stackoverflow.com/questions/11850726/splfileobject-vs-fopen-in-php 'SplFileObject vs fopen in PHP')
  - [write.corbpie.com](https://write.corbpie.com/reading-large-files-in-php-with-splfileobject/ 'Reading large files in PHP with SplFileObject')

### 3. Advanced Logic (SplPriorityQueue)

In complex Laravel applications involving many related objects, you might need to map data to objects rather than string keys.

- **The Use Case:** Managing a **Shopping Cart** where the key is the actual `Product` model instance rather than its ID. 
- **Advantage:** It provides a native way to attach data to an object and ensures uniqueness without the manual id lookups required in a standard Laravel Collection.
  - [codemystify](https://codemystify.com/articles/harnessing-the-power-of-spl-classes-in-php-a-deep-dive-with-practical-scenarios 'Harnessing the Power of SPL Classes in PHP: A Deep Dive with Practical Scenarios')

### 4. Advanced Logic (SplPriorityQueue)

Standard Laravel queues handle tasks in the order they arrive (FIFO).
- **The Use Case:** If you are building an in-memory task scheduler or a complex notification engine where certain items (like "Password Reset") must be processed before others (like "Monthly Newsletter").
- **Advantage:** `SplPriorityQueue` handles the sorting logic automatically based on a priority integer you provide, which is much faster than manually sorting a Collection every time a new item is added.

### When should you start using them?

You don't need to swap every array for an SPL structure. Instead, consider them when:

* **Performance is a Bottleneck:** You are hitting `memory_limit` errors in background jobs.
* **Building a Library:** You are writing a reusable package and want to avoid a hard dependency on the Laravel framework.
* **Low-Level Interaction:** You are building a custom file importer or a complex data-processing engine.

### Code Examples

#### 1. SplFixedArray - Processing Large Datasets in Laravel

```php
<?php
class ProcessUserIdsJob implements ShouldQueue
{
    public int $timeout = 600;

    public function handle(): void
    {
        // Get millions of user IDs from database (chunked)
        $userIds = $this->getUserIdsFromDatabase();
        
        // Use SplFixedArray for better memory efficiency
        $fixedArray = new SplFixedArray(count($userIds));
        
        foreach ($userIds as $index => $userId) {
            $fixedArray[$index] = $userId;
        }
        
        // Process in chunks
        $this->processInChunks($fixedArray);
    }

    private function getUserIdsFromDatabase(): array
    {
        return DB::table('users')
            ->where('active', true)
            ->pluck('id')
            ->toArray();
    }

    private function processInChunks(SplFixedArray $ids): void
    {
        $chunkSize = 1000;
        
        for ($i = 0; $i < $ids->getSize(); $i += $chunkSize) {
            $chunk = array_slice($ids->toArray(), $i, $chunkSize);
            
            // Process chunk - e.g., generate reports, sync to external service
            foreach ($chunk as $userId) {
                $this->processUser($userId);
            }
            
            // Free memory after each chunk
            gc_collect_cycles();
        }
    }
}

// Alternative: Direct memory comparison
function compareMemoryUsage(): void
{
    $count = 1000000;
    
    // Standard array (PHP hash map)
    $startMemory = memory_get_usage(true);
    $standardArray = range(1, $count);
    $standardMemory = memory_get_usage(true) - $startMemory;
    
    // SplFixedArray (contiguous memory)
    $startMemory = memory_get_usage(true);
    $fixedArray = new SplFixedArray($count);
    for ($i = 0; $i < $count; $i++) {
        $fixedArray[$i] = $i + 1;
    }
    $fixedMemory = memory_get_usage(true) - $startMemory;
    
    echo "Standard Array: " . ($standardMemory / 1024 / 1024) . " MB\n";
    echo "SplFixedArray: " . ($fixedMemory / 1024 / 1024) . " MB\n";
    echo "Memory Savings: " . (($standardMemory - $fixedMemory) / $standardMemory * 100) . "%\n";
}
```

#### 2. SplFileObject - Large File Processing in Laravel

```php
<?php
// Custom Log Parser using SplFileObject
class AccessLogParser
{
    private SplFileObject $file;
    private int $currentLine = 0;

    public function __construct(string $filePath)
    {
        $this->file = new SplFileObject($filePath, 'r');
        $this->file->setFlags(SplFileObject::DROP_NEW_LINE);
    }

    public function process(int $batchSize = 1000): array
    {
        $results = [];
        
        while (!$this->file->eof()) {
            $line = $this->file->fgets();
            
            if (empty($line)) continue;
            
            $parsed = $this->parseLine($line);
            if ($parsed) {
                $results[] = $parsed;
            }
            
            $this->currentLine++;
            
            // Yield to prevent timeout in Laravel
            if ($this->currentLine % $batchSize === 0) {
                $this->saveBatch($results);
                $results = [];
            }
        }
        
        // Save remaining
        if (!empty($results)) {
            $this->saveBatch($results);
        }
        
        return ['processed' => $this->currentLine];
    }

    private function parseLine(string $line): ?array
    {
        // Example: 127.0.0.1 - - [10/Oct/2024:13:55:36] "GET /api/users HTTP/1.1" 200 2326
        $pattern = '/^(\S+) \S+ \S+ \[([^\]]+)\] "(\S+) (\S+)" (\d+) (\d+)$/';
        
        if (preg_match($pattern, $line, $matches)) {
            return [
                'ip' => $matches[1],
                'timestamp' => $matches[2],
                'method' => $matches[3],
                'path' => $matches[4],
                'status' => (int) $matches[5],
                'size' => (int) $matches[6],
            ];
        }
        
        return null;
    }

    private function saveBatch(array $batch): void
    {
        // Insert into database or process batch
        DB::table('access_logs')->insert($batch);
    }

    public function seekToLine(int $lineNumber): void
    {
        $this->file->seek($lineNumber);
        $this->currentLine = $lineNumber;
    }
}

// Laravel Artisan Command for log processing
class ProcessLogsCommand extends Command
{
    protected $signature = 'logs:process {file : Path to log file} {--start=0 : Start line}';
    
    public function handle(): int
    {
        $filePath = $this->argument('file');
        $startLine = (int) $this->option('start');
        
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return self::FAILURE;
        }
        
        $parser = new AccessLogParser($filePath);
        
        if ($startLine > 0) {
            $parser->seekToLine($startLine);
        }
        
        $this->info('Starting log processing...');
        $result = $parser->process();
        
        $this->info("Processed {$result['processed']} lines");
        
        return self::SUCCESS;
    }
}

// CSV processing with SplFileObject
class CsvImporter
{
    private SplFileObject $file;
    private array $headers = [];

    public function __construct(string $filePath)
    {
        $this->file = new SplFileObject($filePath, 'r');
        $this->file->setCsvControl(',', '"', '\\');
    }

    public function import(int $batchSize = 500): void
    {
        // Read headers from first line
        $this->headers = $this->file->current();
        $this->file->next();
        
        $batch = [];
        
        foreach ($this->file as $row) {
            if (empty($row)) continue;
            
            $batch[] = array_combine($this->headers, $row);
            
            if (count($batch) >= $batchSize) {
                $this->insertBatch($batch);
                $batch = [];
            }
        }
        
        if (!empty($batch)) {
            $this->insertBatch($batch);
        }
    }

    private function insertBatch(array $batch): void
    {
        DB::table('imported_records')->insert($batch);
    }
}
```

#### 3. SplPriorityQueue - Priority-Based Task Processing

```php
<?php
// Priority-based notification queue
class NotificationQueue
{
    private SplPriorityQueue $queue;

    public function __construct()
    {
        $this->queue = new SplPriorityQueue();
    }

    public function enqueue(Notification $notification, int $priority): void
    {
        $this->queue->insert($notification, $priority);
    }

    public function processAll(): void
    {
        while ($this->queue->valid()) {
            $notification = $this->queue->current();
            $notification->send();
            $this->queue->next();
        }
    }

    public function count(): int
    {
        return $this->queue->count();
    }
}

// Example usage in Laravel Service
class NotificationService
{
    private NotificationQueue $queue;

    public function __construct()
    {
        $this->queue = new NotificationQueue();
    }

    public function sendPasswordReset(User $user): void
    {
        $this->queue->enqueue(
            new EmailNotification($user->email, 'Password Reset'),
            100  // High priority
        );
    }

    public function sendWelcomeEmail(User $user): void
    {
        $this->queue->enqueue(
            new EmailNotification($user->email, 'Welcome'),
            50   // Medium priority
        );
    }

    public function sendMonthlyNewsletter(User $user): void
    {
        $this->queue->enqueue(
            new EmailNotification($user->email, 'Newsletter'),
            10   // Low priority
        );
    }

    public function flush(): void
    {
        $this->queue->processAll();
    }
}

// Priority-based job scheduling
class JobScheduler
{
    private SplPriorityQueue $scheduler;

    public function __construct()
    {
        $this->scheduler = new SplPriorityQueue();
        $this->scheduler->setExtractFlags(SplPriorityQueue::EXTR_BOTH);
    }

    public function schedule(Job $job, int $priority, int $delaySeconds = 0): void
    {
        $executeAt = time() + $delaySeconds;
        
        // Store job with priority and execution time
        $this->scheduler->insert([
            'job' => $job,
            'executeAt' => $executeAt,
            'priority' => $priority,
        ], $priority);
    }

    public function run(): void
    {
        $now = time();
        
        while ($this->scheduler->valid()) {
            $item = $this->scheduler->current();
            $data = $item['data'];
            
            if ($data['executeAt'] <= $now) {
                $data['job']->execute();
            } else {
                // Re-insert for later
                $this->scheduler->insert($data, $data['priority']);
                break;
            }
            
            $this->scheduler->next();
        }
    }
}
```

#### 4. SplObjectStorage - Object-Based Collections

```php
<?php
// Shopping cart using SplObjectStorage
class ShoppingCart
{
    private SplObjectStorage $items;
    private array $quantities = [];

    public function __construct()
    {
        $this->items = new SplObjectStorage();
    }

    public function addItem(Product $product, int $quantity = 1): void
    {
        if ($this->items->contains($product)) {
            $this->quantities[spl_object_id($product)] += $quantity;
        } else {
            $this->items->attach($product);
            $this->quantities[spl_object_id($product)] = $quantity;
        }
    }

    public function removeItem(Product $product): void
    {
        $this->items->detach($product);
        unset($this->quantities[spl_object_id($product)]);
    }

    public function getQuantity(Product $product): int
    {
        return $this->quantities[spl_object_id($product)] ?? 0;
    }

    public function getTotal(): float
    {
        $total = 0.0;
        
        foreach ($this->items as $product) {
            $total += $product->price * $this->quantities[spl_object_id($product)];
        }
        
        return $total;
    }

    public function getItems(): array
    {
        $items = [];
        
        foreach ($this->items as $product) {
            $items[] = [
                'product' => $product,
                'quantity' => $this->quantities[spl_object_id($product)],
            ];
        }
        
        return $items;
    }
}

// Laravel controller usage
class CartController extends Controller
{
    public function addToCart(Request $request): RedirectResponse
    {
        $product = Product::findOrFail($request->input('product_id'));
        $quantity = $request->input('quantity', 1);
        
        $cart = session()->get('cart', new ShoppingCart());
        $cart->addItem($product, $quantity);
        
        session()->put('cart', $cart);
        
        return redirect()->route('cart.index');
    }
}
```

#### 5. SplDoublyLinkedList - Custom Queue Implementation

```php
<?php
class RateLimitedQueue
{
    private SplDoublyLinkedList $queue;
    private int $maxRequests;
    private int $timeWindow;
    private array $requestTimestamps = [];

    public function __construct(int $maxRequests = 10, int $timeWindow = 60)
    {
        $this->queue = new SplDoublyLinkedList();
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
    }

    public function add(callable $task): void
    {
        $this->queue->push($task);
    }

    public function process(): void
    {
        while (!$this->queue->isEmpty()) {
            $task = $this->queue->shift();
            
            if ($this->canProcess()) {
                $this->recordRequest();
                $task();
            } else {
                // Re-add to queue and wait
                $this->queue->unshift($task);
                sleep(1);
            }
        }
    }

    private function canProcess(): bool
    {
        $now = time();
        
        // Clean old timestamps
        $this->requestTimestamps = array_filter(
            $this->requestTimestamps,
            fn($timestamp) => $timestamp > ($now - $this->timeWindow)
        );
        
        return count($this->requestTimestamps) < $this->maxRequests;
    }

    private function recordRequest(): void
    {
        $this->requestTimestamps[] = time();
    }
}

// Laravel queue job with rate limiting
class ExternalApiSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $queue = new RateLimitedQueue(10, 60);
        
        $users = User::where('synced', false)->cursor();
        
        foreach ($users as $user) {
            $queue->add(function () use ($user) {
                $this->syncToExternalService($user);
            });
        }
        
        $queue->process();
    }

    private function syncToExternalService(User $user): void
    {
        // API call with rate limiting
    }
}
```

---

## Question 6: How do you handle errors in PHP 8?

**Answer:**

PHP 8 introduced significant improvements to error handling with typed properties, union types, and better exception handling.

### PHP 8 Error Handling Features

#### 1. Throw Expression

```php
<?php
// PHP 8: throw as expression
$value = $data['user'] ?? throw new InvalidArgumentException('User required');

// In arrow functions
$id = array_key_first($items) ?? throw new RuntimeException('No items');

// In null coalescing assignment
$data['cache'] ??= throw new CacheException('Cache unavailable');
```

#### 2. Mixed Type and Error Handling

```php
<?php
class UserRepository
{
    // Mixed accepts any type
    public function find(int $id): mixed
    {
        $result = DB::table('users')->find($id);
        
        // Return type is mixed, no warning
        return $result;
    }

    // Strict return type with union (PHP 8.0+)
    public function findOrFail(int $id): User|null
    {
        $user = DB::table('users')->find($id);
        
        if (!$user) {
            throw new ModelNotFoundException(User::class, $id);
        }
        
        return $user;
    }

    // Union types (PHP 8.0+)
    public function getStatus(): int|false
    {
        $status = $this->fetchStatus();
        
        if ($status === null) {
            return false;  // Explicit false return
        }
        
        return $status;
    }
}
```

#### 3. Never Return Type

```php
<?php
class PaymentProcessor
{
    // Never returns - always throws or exits
    public function process(string $paymentId): never
    {
        if (!$this->validatePayment($paymentId)) {
            throw new PaymentException('Invalid payment');
        }
        
        // Or exits
        exit('Payment processed');
    }

    // PHP knows this function never returns normally
    // Can help static analysis tools
    public function redirectAndExit(string $url): never
    {
        header("Location: {$url}");
        exit();
    }
}
```

#### 4. First-Class Callable Syntax

```php
<?php
class ErrorHandler
{
    public function register(): void
    {
        // PHP 8: First-class callable
        set_error_handler($this->handleError(...));
        set_exception_handler($this->handleException(...));
    }

    public function handleError(
        int $errno,
        string $errstr,
        string $errfile = '',
        int $errline = 0
    ): bool {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    public function handleException(Throwable $e): void
    {
        Log::error($e->getMessage(), ['exception' => $e]);
        echo "Error: " . $e->getMessage();
    }
}
```

### Custom Error Handler

```php
<?php
class ApplicationErrorHandler
{
    public function register(): void
    {
        // Report all errors
        error_reporting(E_ALL);

        // Set custom handler
        set_error_handler([$this, 'handleError']);

        // Exception handler
        set_exception_handler([$this, 'handleException']);

        // Shutdown handler (for fatal errors)
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError(
        int $errno,
        string $errstr,
        string $errfile,
        int $errline
    ): bool {
        // Don't handle if error reporting is disabled
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $message = "Error [{$errno}]: {$errstr} in {$errfile}:{$errline}";

        throw new ErrorException($message, 0, $errno, $errfile, $errline);
    }

    public function handleException(Throwable $e): void
    {
        // JSON API response
        if (request()->expectsJson()) {
            response()->json([
                'error' => class_basename($e),
                'message' => $e->getMessage(),
            ], $this->getStatusCode($e))->send();
            return;
        }

        // Debug mode details
        if (config('app.debug')) {
            echo "<h1>Error</h1>";
            echo "<p>{$e->getMessage()}</p>";
            echo "<pre>{$e->getTraceAsString()}</pre>";
            return;
        }

        // Production: generic message
        echo "An error occurred. Please try again.";
        
        Log::critical($e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR])) {
            Log::critical('Fatal error', $error);
        }
    }

    private function getStatusCode(Throwable $e): int
    {
        if ($e instanceof HttpExceptionInterface) {
            return $e->getStatusCode();
        }

        if ($e instanceof ValidationException) {
            return 422;
        }

        return 500;
    }
}
```

### Error Handling with Union Types

```php
<?php
class DataParser
{
    // Union type: returns string or false
    public function parse(string $input): string|false
    {
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    // Using match (PHP 8.0+)
    public function handleResult(string|false $result): void
    {
        $message = match ($result) {
            false => 'Parsing failed',
            default => "Parsed: {$result}",
        };
        
        echo $message;
    }

    // Nullable union (PHP 8.1+)
    public function findUser(int $id): ?User
    {
        try {
            return User::findOrFail($id);
        } catch (ModelNotFoundException) {
            return null;
        }
    }
}
```

### Throwable Catch Filtering (PHP 8.0+)

```php
<?php
try {
    $this->processPayment($order);
} catch (PaymentDeclined $e) {
    // Specific: Handle declined
    $this->notifyUser('Payment declined');
} catch (PaymentException $e) {
    // Broader: Handle other payment errors
    $this->logPaymentError($e);
    $this->refund($order);
} catch (Throwable $e) {
    // Catch all other exceptions
    report($e);
    throw $e;
}

// PHP 8.1+ catch with multiple types
try {
    $result = $service->call();
} catch (InvalidArgumentException|RuntimeException $e) {
    // Handle multiple specific exceptions
    Log::warning($e->getMessage());
}
```

### PHP 8.1 Enums in Error Handling

```php
<?php
enum ErrorCode: int
{
    case SUCCESS = 0;
    case VALIDATION_ERROR = 1;
    case NOT_FOUND = 2;
    case SERVER_ERROR = 3;

    public function getHttpStatus(): int
    {
        return match ($this) {
            self::SUCCESS => 200,
            self::VALIDATION_ERROR => 422,
            self::NOT_FOUND => 404,
            self::SERVER_ERROR => 500,
        };
    }
}

class ApiResponse
{
    public function error(ErrorCode $code, string $message): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code->value,
                'message' => $message,
            ],
        ], $code->getHttpStatus());
    }
}
```

### Monolog Integration

```php
<?php
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\GitProcessor;
use Monolog\Processor\MemoryUsageProcessor;

class LoggedErrorHandler
{
    private Logger $logger;

    public function __construct()
    {
        $this->logger = new Logger('app');
        $this->logger->pushHandler(new StreamHandler('storage/logs/app.log', Level::Debug));
        $this->logger->pushProcessor(new GitProcessor());
        $this->logger->pushProcessor(new MemoryUsageProcessor());
    }

    public function handle(Throwable $e): void
    {
        $level = match (true) {
            $e instanceof LogicException => Level::Warning,
            $e instanceof RuntimeException => Level::Error,
            $e instanceof Throwable => Level::Critical,
        };

        $this->logger->log($level, $e->getMessage(), [
            'exception' => $e::class,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'context' => request()->all() ?? [],
        ]);
    }
}
```

### Best Practices

```php
<?php
// ✅ Good: Specific exception types
throw new UserNotFoundException($userId);
throw new InsufficientFundsException($required, $available);

// ✅ Good: Use typed properties (PHP 7.4+, PHP 8.0+ improvements)
class Order
{
    public int $id;
    public string $status;
    public ?\DateTime $shippedAt = null;  // Nullable
}

// ✅ Good: Union types for flexible returns (PHP 8.0+)
function findUser(int $id): User|null { ... }

// ✅ Good: Never return type for noreturn functions
function redirect(string $url): never { ... }

// ✅ Good: Custom error handler for centralized logging
set_error_handler([new ErrorHandler(), 'handle']);

// ❌ Bad: Suppressing errors
@file_get_contents($url);

// ❌ Bad: Catching generic Exception
catch (Exception $e) { ... }  // Too broad!

// ❌ Bad: Empty catch blocks
catch (Exception $e) { }  // Silent failures!
```

**Key Points:**
- PHP 8: throw expressions, union types, mixed type
- PHP 8.1: never return type, catch filtering, enums
- Use custom error handler for centralized error management
- Catch specific exceptions, not generic Throwable
- Use typed properties for better type safety
- Log errors consistently with context
- Use shutdown handler for fatal errors

---

## Question 7: What are anonymous classes in PHP and what are their use cases?

**Answer:**

Anonymous classes allow you to create one-off objects without defining a formal class.

### Basic Anonymous Classes

```php
<?php
// Simple anonymous class
$object = new class {
    public function greet(): string
    {
        return 'Hello!';
    }
};

echo $object->greet();  // Hello!
```

### Extending Classes

```php
<?php
class User
{
    public function __construct(
        public string $name
    ) {}

    public function greet(): string
    {
        return "Hello, {$this->name}!";
    }
}

// Extend with anonymous class
$admin = new class extends User {
    public function greet(): string
    {
        return "Hello, Admin {$this->name}!";
    }
};

echo $admin->greet();  // Hello, Admin John!
```

### Implementing Interfaces

```php
<?php
interface LoggerInterface
{
    public function log(string $message): void;
}

interface FormatterInterface
{
    public function format(string $message): string;
}

// Anonymous class implementing interface
$logger = new class implements LoggerInterface {
    public function log(string $message): void
    {
        echo "LOG: {$message}\n";
    }
};

$formatter = new class implements FormatterInterface {
    public function format(string $message): string
    {
        return strtoupper($message);
    }
};
```

### Use Cases

#### 1. Quick Test Doubles

```php
<?php
// Instead of creating a full mock class
class OrderServiceTest extends TestCase
{
    public function test_process_order(): void
    {
        // Anonymous class as mock
        $paymentGateway = new class implements PaymentGatewayInterface {
            public bool $charged = false;

            public function charge(int $amount): bool
            {
                $this->charged = true;
                return true;
            }
        };

        $orderService = new OrderService($paymentGateway);
        $order = $orderService->process(new Order(['total' => 100]));

        $this->assertTrue($paymentGateway->charged);
        $this->assertEquals('processed', $order->status);
    }
}
```

#### 2. Strategy Pattern Implementation

```php
<?php
interface DiscountStrategy
{
    public function calculate(float $price): float;
}

class PriceCalculator
{
    public function calculate(float $price, DiscountStrategy $strategy): float
    {
        return $strategy->calculate($price);
    }
}

$calculator = new PriceCalculator();

// Use different strategies on-the-fly
$regularPrice = $calculator->calculate(100, new class implements DiscountStrategy {
    public function calculate(float $price): float
    {
        return $price;
    }
});

$salePrice = $calculator->calculate(100, new class implements DiscountStrategy {
    public function calculate(float $price): float
    {
        return $price * 0.8;  // 20% off
    }
});

$memberPrice = $calculator->calculate(100, new class implements DiscountStrategy {
    public function calculate(float $price): float
    {
        return $price * 0.9;  // 10% off for members
    }
});
```

#### 3. Decorator Pattern

```php
<?php
interface CacheInterface
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value): void;
}

class FileCache implements CacheInterface
{
    public function get(string $key): mixed
    {
        // File-based implementation
    }

    public function set(string $key, mixed $value): void
    {
        // File-based implementation
    }
}

// Add logging decorator with anonymous class
$loggedCache = new class(new FileCache()) implements CacheInterface {
    public function __construct(
        private CacheInterface $cache
    ) {}

    public function get(string $key): mixed
    {
        $value = $this->cache->get($key);
        Log::info("Cache get: {$key}", ['hit' => $value !== null]);
        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        $this->cache->set($key, $value);
        Log::info("Cache set: {$key}");
    }
};
```

#### 4. One-off Event Listeners

```php
<?php
$user->save();

// One-off anonymous listener
Event::listen(UserCreated::class, new class {
    public function handle(UserCreated $event): void
    {
        // Send welcome email just for this operation
        Mail::to($event->user->email)->send(new WelcomeMail());
        
        // Log activity
        activity()
            ->causedBy($event->user)
            ->log('user.created');
    }
});
```

#### 5. Custom Collection Sorting

```php
<?php
$users = User::all();

// Custom sort without creating a separate Comparator class
$sorted = collect($users)->sortBy(
    fn(User $a, User $b) => $a->name <=> $b->name
);

// Or with anonymous class for complex logic
$sorted = (new class implements Comparator {
    public function compare($a, $b): int
    {
        // Complex comparison logic
        $nameCompare = $a->name <=> $b->name;
        if ($nameCompare !== 0) return $nameCompare;
        
        return $a->created_at <=> $b->created_at;
    }
});
```

#### 6. Creating Adapters

```php
<?php
interface ExternalServiceInterface
{
    public function fetch(array $params): array;
}

// Adapter for third-party API
$adapter = new class implements ExternalServiceInterface {
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.example.com',
            'timeout' => 5,
        ]);
    }

    public function fetch(array $params): array
    {
        $response = $this->client->get('/data', ['query' => $params]);
        return json_decode($response->getBody(), true);
    }
};
```

### Constructor Arguments

```php
<?php
class Service
{
    public function __construct(
        private string $apiKey
    ) {}
}

class Logger
{
    public function __construct(
        private string $path
    ) {}
}

// Anonymous class with constructor arguments
$service = new class(new Logger('/tmp/app.log')) extends Service {
    public function __construct(Logger $logger)
    {
        parent::__construct('secret-key');
        $this->logger = $logger;
    }
};
```

### Anonymous vs Named Classes

| Feature | Anonymous Class | Named Class |
|---------|----------------|-------------|
| Definition | Inline | Separate file |
| Reusability | Single use | Multiple uses |
| Name | None (generated) | Explicit |
| Testing | Harder to mock | Easy to mock |
| IDE Support | Limited | Full |
| Performance | Slightly slower (first call) | Faster |

### When to Use Anonymous Classes

```php
<?php
// ✅ Good: One-off implementation
$processor = new class implements ProcessorInterface {
    public function process($data) { /* ... */ }
};

// ✅ Good: Test doubles
$mock = new class extends MockObject { /* ... */ };

// ✅ Good: Quick prototyping
$demo = new class { public function run() { /* ... */ } };

// ❌ Bad: Reusable code
// Don't use if the class will be used in multiple places

// ❌ Bad: Complex logic
// Don't use for classes with many methods
```

**Key Points:**
- Anonymous classes: inline, one-off implementations
- Can extend classes, implement interfaces
- Great for: test doubles, adapters, decorators, strategies
- Avoid for: reusable code, complex classes
- Performance: negligible difference after first instantiation
- IDE support limited but improving

---

## Notes

Add more questions covering:
- Creating and maintaining custom libraries (Composer packages)
- Advanced communication protocols (WebSockets, gRPC)
- Math operations libraries (BCMath, GMP)
- Profiling tools (Xdebug, Blackfire, Tideways)
- Cryptography libraries (sodium, openssl)
