# PHP Object-Oriented Programming

## Question 1: Explain the SOLID principles with PHP examples.

**Answer:**

SOLID is an acronym for five design principles that make software more maintainable:

### S - Single Responsibility Principle
A class should have only one reason to change.

```php
// Bad: Multiple responsibilities
class User {
    public function save() { /* DB logic */ }
    public function sendEmail() { /* Email logic */ }
    public function generateReport() { /* Report logic */ }
}

// Good: Single responsibility
class User {
    public function __construct(
        private string $name,
        private string $email
    ) {}
}

class UserRepository {
    public function save(User $user): void { /* DB logic */ }
}

class UserMailer {
    public function sendWelcomeEmail(User $user): void { /* Email logic */ }
}

class UserReportGenerator {
    public function generate(User $user): Report { /* Report logic */ }
}
```

### O - Open/Closed Principle
Open for extension, closed for modification.

```php
// Bad: Must modify class to add payment methods
class PaymentProcessor {
    public function process($type) {
        if ($type === 'stripe') { /* ... */ }
        elseif ($type === 'paypal') { /* ... */ }
    }
}

// Good: Extend without modifying
interface PaymentGateway {
    public function charge(float $amount): bool;
}

class StripeGateway implements PaymentGateway {
    public function charge(float $amount): bool {
        // Stripe implementation
    }
}

class PayPalGateway implements PaymentGateway {
    public function charge(float $amount): bool {
        // PayPal implementation
    }
}

class PaymentProcessor {
    public function __construct(
        private PaymentGateway $gateway
    ) {}

    public function process(float $amount): bool {
        return $this->gateway->charge($amount);
    }
}
```

### L - Liskov Substitution Principle
Subtypes must be substitutable for their base types.

```php
// Bad: Violates LSP
class Rectangle {
    protected float $width;
    protected float $height;

    public function setWidth(float $width): void {
        $this->width = $width;
    }

    public function setHeight(float $height): void {
        $this->height = $height;
    }

    public function getArea(): float {
        return $this->width * $this->height;
    }
}

class Square extends Rectangle {
    public function setWidth(float $width): void {
        $this->width = $width;
        $this->height = $width;  // Violates expectations
    }

    public function setHeight(float $height): void {
        $this->width = $height;
        $this->height = $height;
    }
}

// Good: Proper abstraction
interface Shape {
    public function getArea(): float;
}

class Rectangle implements Shape {
    public function __construct(
        private float $width,
        private float $height
    ) {}

    public function getArea(): float {
        return $this->width * $this->height;
    }
}

class Square implements Shape {
    public function __construct(
        private float $side
    ) {}

    public function getArea(): float {
        return $this->side * $this->side;
    }
}
```

### I - Interface Segregation Principle
Clients shouldn't depend on interfaces they don't use.

```php
// Bad: Fat interface
interface Worker {
    public function work(): void;
    public function eat(): void;
    public function sleep(): void;
}

class Robot implements Worker {
    public function work(): void { /* OK */ }
    public function eat(): void { /* Robot doesn't eat! */ }
    public function sleep(): void { /* Robot doesn't sleep! */ }
}

// Good: Segregated interfaces
interface Workable {
    public function work(): void;
}

interface Feedable {
    public function eat(): void;
}

interface Sleepable {
    public function sleep(): void;
}

class Human implements Workable, Feedable, Sleepable {
    public function work(): void { /* ... */ }
    public function eat(): void { /* ... */ }
    public function sleep(): void { /* ... */ }
}

class Robot implements Workable {
    public function work(): void { /* ... */ }
}
```

### D - Dependency Inversion Principle
Depend on abstractions, not concretions.

```php
// Bad: Depends on concrete class
class UserService {
    private MySQLDatabase $database;

    public function __construct() {
        $this->database = new MySQLDatabase();
    }
}

// Good: Depends on abstraction
interface Database {
    public function query(string $sql): array;
}

class MySQLDatabase implements Database {
    public function query(string $sql): array { /* ... */ }
}

class PostgreSQLDatabase implements Database {
    public function query(string $sql): array { /* ... */ }
}

class UserService {
    public function __construct(
        private Database $database
    ) {}

    public function findUsers(): array {
        return $this->database->query('SELECT * FROM users');
    }
}
```

**Follow-up:**
- How do SOLID principles relate to each other?
- Can you give a real-world example where you applied SOLID?

**Key Points:**
- SRP: One class, one responsibility
- OCP: Extend via interfaces, not modification
- LSP: Child classes must not break parent behavior
- ISP: Small, focused interfaces
- DIP: Depend on abstractions (interfaces)

---

## Question 2: What are design patterns? Explain Singleton, Factory, and Observer patterns.

**Answer:**

### Singleton Pattern
Ensures only one instance of a class exists.

```php
class Database {
    private static ?Database $instance = null;
    private PDO $connection;

    private function __construct() {
        $this->connection = new PDO(/* ... */);
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // Prevent cloning
    private function __clone() {}

    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }

    public function getConnection(): PDO {
        return $this->connection;
    }
}

$db = Database::getInstance();
```

**Note:** Singletons are often considered an anti-pattern. Use dependency injection instead.

### Factory Pattern
Creates objects without specifying exact class.

```php
interface Logger {
    public function log(string $message): void;
}

class FileLogger implements Logger {
    public function __construct(private string $path) {}

    public function log(string $message): void {
        file_put_contents($this->path, $message . PHP_EOL, FILE_APPEND);
    }
}

class DatabaseLogger implements Logger {
    public function __construct(private PDO $db) {}

    public function log(string $message): void {
        $stmt = $this->db->prepare('INSERT INTO logs (message) VALUES (?)');
        $stmt->execute([$message]);
    }
}

class LoggerFactory {
    public static function create(string $type): Logger {
        return match($type) {
            'file' => new FileLogger('/var/log/app.log'),
            'database' => new DatabaseLogger(new PDO(/* ... */)),
            default => throw new InvalidArgumentException("Unknown logger type")
        };
    }
}

$logger = LoggerFactory::create('file');
$logger->log('Application started');
```

### Observer Pattern
Objects notify subscribers when state changes.

```php
interface Observer {
    public function update(string $event, $data): void;
}

interface Subject {
    public function attach(Observer $observer): void;
    public function detach(Observer $observer): void;
    public function notify(string $event, $data): void;
}

class Order implements Subject {
    private array $observers = [];
    private string $status;

    public function attach(Observer $observer): void {
        $this->observers[] = $observer;
    }

    public function detach(Observer $observer): void {
        $key = array_search($observer, $this->observers, true);
        if ($key !== false) {
            unset($this->observers[$key]);
        }
    }

    public function notify(string $event, $data): void {
        foreach ($this->observers as $observer) {
            $observer->update($event, $data);
        }
    }

    public function setStatus(string $status): void {
        $this->status = $status;
        $this->notify('status_changed', ['status' => $status]);
    }
}

class EmailNotifier implements Observer {
    public function update(string $event, $data): void {
        if ($event === 'status_changed') {
            echo "Sending email: Order status changed to {$data['status']}\n";
        }
    }
}

class SMSNotifier implements Observer {
    public function update(string $event, $data): void {
        if ($event === 'status_changed') {
            echo "Sending SMS: Order status changed to {$data['status']}\n";
        }
    }
}

$order = new Order();
$order->attach(new EmailNotifier());
$order->attach(new SMSNotifier());
$order->setStatus('shipped');  // Both notifiers triggered
```

**Follow-up:**
- What are the problems with Singleton?
- When would you use Factory vs Abstract Factory?
- How does Laravel's event system implement Observer pattern?

**Key Points:**
- Singleton: Single instance (often an anti-pattern)
- Factory: Centralized object creation
- Observer: Event-driven communication
- Patterns solve recurring problems
- Laravel uses many patterns (Facade, Repository, etc.)

---

## Question 3: Explain abstract classes vs interfaces. When to use each?

**Answer:**

```php
// Interface: Contract only, no implementation
interface PaymentGateway {
    public function charge(float $amount): bool;
    public function refund(string $transactionId): bool;
}

// Abstract class: Partial implementation
abstract class BasePaymentGateway {
    protected array $config;

    public function __construct(array $config) {
        $this->config = $config;
        $this->validateConfig();
    }

    // Concrete method
    protected function validateConfig(): void {
        if (empty($this->config['api_key'])) {
            throw new InvalidArgumentException('API key required');
        }
    }

    // Abstract methods
    abstract public function charge(float $amount): bool;
    abstract public function refund(string $transactionId): bool;

    // Concrete helper method
    protected function log(string $message): void {
        error_log("[{$this->getGatewayName()}] {$message}");
    }

    abstract protected function getGatewayName(): string;
}

// Implementation
class StripeGateway extends BasePaymentGateway implements PaymentGateway {
    public function charge(float $amount): bool {
        $this->log("Charging {$amount}");
        // Stripe-specific implementation
        return true;
    }

    public function refund(string $transactionId): bool {
        $this->log("Refunding {$transactionId}");
        // Stripe-specific implementation
        return true;
    }

    protected function getGatewayName(): string {
        return 'Stripe';
    }
}

// Class can implement multiple interfaces
class PayPalGateway extends BasePaymentGateway implements PaymentGateway {
    public function charge(float $amount): bool {
        $this->log("Charging {$amount}");
        return true;
    }

    public function refund(string $transactionId): bool {
        $this->log("Refunding {$transactionId}");
        return true;
    }

    protected function getGatewayName(): string {
        return 'PayPal';
    }
}
```

**When to use each:**

| Use Interface When | Use Abstract Class When |
|-------------------|------------------------|
| Defining a contract | Sharing code between related classes |
| Multiple inheritance needed | Common behavior exists |
| No shared implementation | Classes share state (properties) |
| Unrelated classes implement | Clear "is-a" relationship |

```php
// Multiple interfaces
interface Loggable {
    public function log(): void;
}

interface Cacheable {
    public function getCacheKey(): string;
}

class User implements Loggable, Cacheable {
    public function log(): void { /* ... */ }
    public function getCacheKey(): string { return "user:{$this->id}"; }
}
```

**Follow-up:**
- Can interfaces have constants?
- What is PHP 8.0's `interface` default methods?
- Can abstract classes have constructors?

**Key Points:**
- Interface: Pure contract, no implementation
- Abstract: Partial implementation, shared code
- PHP supports multiple interfaces, single parent class
- Use interfaces for "can do" relationships
- Use abstract for "is a" relationships with shared code

---

## Question 4: What is dependency injection and why is it important?

**Answer:**

Dependency Injection (DI) passes dependencies to a class instead of creating them internally:

```php
// Bad: Hard-coded dependencies
class UserController {
    private UserRepository $repository;
    private Mailer $mailer;

    public function __construct() {
        $this->repository = new UserRepository(
            new MySQLDatabase('localhost', 'root', 'password')
        );
        $this->mailer = new Mailer('smtp.gmail.com', 587);
    }
}

// Good: Dependencies injected
class UserController {
    public function __construct(
        private UserRepository $repository,
        private Mailer $mailer
    ) {}

    public function register(string $email): void {
        $user = new User($email);
        $this->repository->save($user);
        $this->mailer->sendWelcome($user);
    }
}

// Usage with DI Container (like Laravel's)
$container = new Container();

$container->bind(Database::class, function() {
    return new MySQLDatabase(
        env('DB_HOST'),
        env('DB_USER'),
        env('DB_PASS')
    );
});

$container->bind(UserRepository::class, function($container) {
    return new UserRepository(
        $container->make(Database::class)
    );
});

$container->bind(Mailer::class, function() {
    return new Mailer(
        env('MAIL_HOST'),
        env('MAIL_PORT')
    );
});

// Container auto-resolves dependencies
$controller = $container->make(UserController::class);
```

**Types of DI:**

```php
// 1. Constructor Injection (most common)
class Service {
    public function __construct(
        private Logger $logger
    ) {}
}

// 2. Setter Injection
class Service {
    private Logger $logger;

    public function setLogger(Logger $logger): void {
        $this->logger = $logger;
    }
}

// 3. Property Injection (avoid - breaks encapsulation)
class Service {
    public Logger $logger;
}

// 4. Method Injection
class Service {
    public function process(Logger $logger): void {
        $logger->log('Processing...');
    }
}
```

**Benefits:**

```php
// Easy testing with mocks
class UserControllerTest extends TestCase {
    public function test_register_sends_email() {
        $mockRepository = $this->createMock(UserRepository::class);
        $mockMailer = $this->createMock(Mailer::class);

        $mockMailer->expects($this->once())
            ->method('sendWelcome');

        $controller = new UserController($mockRepository, $mockMailer);
        $controller->register('test@example.com');
    }
}
```

**Follow-up:**
- What is a service container?
- Explain autowiring vs manual binding
- What are the downsides of DI?

**Key Points:**
- DI = passing dependencies, not creating them
- Makes code testable, flexible, maintainable
- Constructor injection is preferred
- DI containers automate dependency resolution
- Laravel's service container handles DI automatically

---

## Question 5: Explain method visibility (public, protected, private) and when to use each.

**Answer:**

```php
class BankAccount {
    // Public: Accessible everywhere
    public string $accountNumber;

    // Protected: Accessible in class and subclasses
    protected float $balance = 0;

    // Private: Only accessible in this class
    private array $transactionHistory = [];

    public function __construct(string $accountNumber) {
        $this->accountNumber = $accountNumber;
    }

    // Public interface
    public function deposit(float $amount): void {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be positive');
        }

        $this->balance += $amount;
        $this->recordTransaction('deposit', $amount);
    }

    public function getBalance(): float {
        return $this->balance;
    }

    // Protected: Subclasses can override
    protected function recordTransaction(string $type, float $amount): void {
        $this->transactionHistory[] = [
            'type' => $type,
            'amount' => $amount,
            'timestamp' => time()
        ];
    }

    // Private: Cannot be overridden
    private function validateAmount(float $amount): bool {
        return $amount > 0 && $amount < 1000000;
    }
}

class SavingsAccount extends BankAccount {
    private float $interestRate;

    public function __construct(string $accountNumber, float $interestRate) {
        parent::__construct($accountNumber);
        $this->interestRate = $interestRate;
    }

    public function addInterest(): void {
        $interest = $this->balance * $this->interestRate;  // Can access protected
        $this->balance += $interest;
        $this->recordTransaction('interest', $interest);  // Can call protected method

        // $this->transactionHistory;  // ERROR: Cannot access private property
        // $this->validateAmount(100);  // ERROR: Cannot call private method
    }
}

// Usage
$account = new BankAccount('123456');
echo $account->accountNumber;  // OK: public
// echo $account->balance;  // ERROR: protected
// echo $account->transactionHistory;  // ERROR: private
```

**Visibility Rules:**

| Modifier | Same Class | Subclass | Outside |
|----------|-----------|----------|---------|
| public | ✓ | ✓ | ✓ |
| protected | ✓ | ✓ | ✗ |
| private | ✓ | ✗ | ✗ |

**Best Practices:**

```php
class User {
    // Default to private
    private string $password;

    // Use protected for inheritance
    protected string $email;

    // Public only for API
    public function setPassword(string $password): void {
        $this->password = password_hash($password, PASSWORD_BCRYPT);
    }

    // Getters for controlled access
    public function getEmail(): string {
        return $this->email;
    }
}
```

**Follow-up:**
- What is property promotion (PHP 8.0)?
- Can you change visibility when overriding?
- What about `final` keyword?

**Key Points:**
- Public: External API, accessible everywhere
- Protected: Inheritance, extensibility
- Private: Implementation details, encapsulation
- Default to most restrictive visibility
- Use getters/setters for controlled access

---

## Question 6: What is late static binding? Explain `self` vs `static`.

**Answer:**

```php
class Animal {
    protected static string $type = 'Generic Animal';

    public static function getType(): string {
        return self::$type;  // Early binding - refers to Animal
    }

    public static function getTypeStatic(): string {
        return static::$type;  // Late static binding - refers to called class
    }

    public static function create(): static {
        return new static();  // Returns instance of called class
    }
}

class Dog extends Animal {
    protected static string $type = 'Dog';
}

class Cat extends Animal {
    protected static string $type = 'Cat';
}

// self - refers to the class where it's defined
echo Animal::getType();  // "Generic Animal"
echo Dog::getType();     // "Generic Animal" (not what we want!)
echo Cat::getType();     // "Generic Animal"

// static - refers to the class that called it
echo Animal::getTypeStatic();  // "Generic Animal"
echo Dog::getTypeStatic();     // "Dog" ✓
echo Cat::getTypeStatic();     // "Cat" ✓

// Creating instances
$dog = Dog::create();  // Returns Dog instance
$cat = Cat::create();  // Returns Cat instance
```

**Practical Example:**

```php
abstract class Model {
    protected static string $table;

    public static function find(int $id): ?static {
        $table = static::$table;  // Late binding
        $sql = "SELECT * FROM {$table} WHERE id = ?";
        // ... database query
        return new static($data);  // Create instance of child class
    }

    public static function all(): array {
        $table = static::$table;
        $sql = "SELECT * FROM {$table}";
        // ... returns array of child class instances
    }
}

class User extends Model {
    protected static string $table = 'users';
}

class Post extends Model {
    protected static string $table = 'posts';
}

// Late static binding in action
$user = User::find(1);  // Queries 'users' table, returns User
$post = Post::find(1);  // Queries 'posts' table, returns Post
```

**When to use each:**

```php
class Example {
    private const CONFIG = ['key' => 'value'];

    // Use self for constants and non-overridable
    public function getConfig(): array {
        return self::CONFIG;
    }

    // Use static for overridable behavior
    public static function getInstance(): static {
        return new static();
    }

    // parent - explicit parent reference
    public function parentMethod(): void {
        parent::someMethod();  // Call parent's implementation
    }
}
```

**Follow-up:**
- What problem does late static binding solve?
- Can you use `static` in non-static methods?
- What is the performance impact?

**Key Points:**
- `self`: Early binding, refers to defining class
- `static`: Late binding, refers to calling class
- Use `static` for inheritable static methods
- Essential for framework base classes
- Enables method chaining in inheritance

---

## Notes

Add more questions covering:
- Composition vs Inheritance
- Method overloading and overriding
- Type hinting and return types
- Immutability and value objects
- Repository pattern
- Service layer pattern
- DTOs (Data Transfer Objects)
