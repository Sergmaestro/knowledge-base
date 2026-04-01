# System Design & Architecture

## Question 1: Explain common architectural patterns for web applications.

**Answer:**

### 1. Monolithic Architecture

```
┌─────────────────────────────────┐
│      Monolithic Application     │
├─────────────────────────────────┤
│  UI Layer                       │
│  Business Logic Layer           │
│  Data Access Layer              │
│  Database                       │
└─────────────────────────────────┘

Pros:
- Simple to develop and deploy
- Easy to test
- Single codebase

Cons:
- Hard to scale specific features
- Technology lock-in
- Large codebase becomes complex
```

### 2. Microservices Architecture

```
┌──────────┐  ┌──────────┐  ┌──────────┐
│  User    │  │  Order   │  │ Payment  │
│ Service  │  │ Service  │  │ Service  │
├──────────┤  ├──────────┤  ├──────────┤
│   DB     │  │   DB     │  │   DB     │
└──────────┘  └──────────┘  └──────────┘
       ↓            ↓            ↓
        ───────  API Gateway  ───────
                     ↓
              ┌──────────┐
              │  Client  │
              └──────────┘

Pros:
- Independent scaling
- Technology diversity
- Fault isolation
- Team autonomy

Cons:
- Complex deployment
- Distributed system challenges
- Network latency
- Data consistency
```

### 3. Layered Architecture

```php
// Presentation Layer (Controllers)
class UserController {
    public function __construct(
        private UserService $userService
    ) {}

    public function index() {
        $users = $this->userService->getAllUsers();
        return view('users.index', compact('users'));
    }
}

// Business Logic Layer (Services)
class UserService {
    public function __construct(
        private UserRepository $repository,
        private Mailer $mailer
    ) {}

    public function getAllUsers() {
        return $this->repository->findAll();
    }

    public function createUser(array $data) {
        $user = $this->repository->create($data);
        $this->mailer->sendWelcome($user);
        return $user;
    }
}

// Data Access Layer (Repositories)
class UserRepository {
    public function findAll() {
        return User::all();
    }

    public function create(array $data) {
        return User::create($data);
    }
}

// Database Layer
// Eloquent ORM / Query Builder
```

### 4. Hexagonal Architecture (Ports & Adapters)

```php
// Domain Layer (Core Business Logic)
class Order {
    public function __construct(
        private int $id,
        private array $items,
        private OrderStatus $status
    ) {}

    public function complete() {
        if ($this->status !== OrderStatus::Pending) {
            throw new InvalidOrderStateException();
        }
        $this->status = OrderStatus::Completed;
    }
}

// Ports (Interfaces)
interface OrderRepository {
    public function save(Order $order): void;
    public function findById(int $id): ?Order;
}

interface PaymentGateway {
    public function charge(int $amount): bool;
}

// Adapters (Implementations)
class MySQLOrderRepository implements OrderRepository {
    public function save(Order $order): void {
        // MySQL specific implementation
    }
}

class StripePaymentGateway implements PaymentGateway {
    public function charge(int $amount): bool {
        // Stripe API call
    }
}

// Application Layer
class PlaceOrderUseCase {
    public function __construct(
        private OrderRepository $repository,
        private PaymentGateway $payment
    ) {}

    public function execute(array $items, int $total) {
        $order = new Order(items: $items);

        if ($this->payment->charge($total)) {
            $order->complete();
            $this->repository->save($order);
        }
    }
}
```

### 5. Event-Driven Architecture

```php
// Event
class OrderPlaced {
    public function __construct(
        public Order $order
    ) {}
}

// Event Dispatcher
Event::dispatch(new OrderPlaced($order));

// Event Listeners (decoupled)
class SendOrderConfirmation {
    public function handle(OrderPlaced $event) {
        Mail::to($event->order->user)->send(new OrderConfirmationMail());
    }
}

class UpdateInventory {
    public function handle(OrderPlaced $event) {
        foreach ($event->order->items as $item) {
            $item->product->decrement('stock', $item->quantity);
        }
    }
}

class NotifyWarehouse {
    public function handle(OrderPlaced $event) {
        Http::post('warehouse.com/api/ship', $event->order);
    }
}
```

### 6. CQRS (Command Query Responsibility Segregation)

```php
// Command (Write)
class CreateUserCommand {
    public function __construct(
        public string $name,
        public string $email
    ) {}
}

class CreateUserHandler {
    public function handle(CreateUserCommand $command) {
        $user = User::create([
            'name' => $command->name,
            'email' => $command->email
        ]);

        event(new UserCreated($user));
    }
}

// Query (Read)
class GetUserQuery {
    public function __construct(
        public int $userId
    ) {}
}

class GetUserHandler {
    public function handle(GetUserQuery $query) {
        // Read from optimized read model
        return Cache::remember("user:{$query->userId}", 3600, fn() =>
            DB::table('users_read_model')->find($query->userId)
        );
    }
}

// Separate write and read models
// Write: Normalized database
// Read: Denormalized, cached, optimized for queries
```

**Follow-up:**
- When would you choose microservices over monolith?
- What are the trade-offs of CQRS?
- How do you handle transactions across microservices?

**Key Points:**
- Monolith: Simple, single deployment
- Microservices: Scalable, complex
- Layered: Separation of concerns
- Hexagonal: Business logic independence
- Event-Driven: Decoupled, asynchronous
- CQRS: Separate read/write optimization

---

## Question 2: How do you design a RESTful API?

**Answer:**

### REST Principles

```
1. Resource-based URLs
2. HTTP methods for actions
3. Stateless
4. HATEOAS (Hypermedia)
5. Standard status codes
```

### Resource Design

```
✅ Good RESTful URLs:
GET    /api/users              - List users
POST   /api/users              - Create user
GET    /api/users/123          - Get user 123
PUT    /api/users/123          - Update user 123
PATCH  /api/users/123          - Partial update
DELETE /api/users/123          - Delete user 123

GET    /api/users/123/posts    - User's posts
POST   /api/users/123/posts    - Create post for user
GET    /api/posts/456/comments - Post's comments

❌ Bad URLs:
GET  /api/getAllUsers
POST /api/createUser
GET  /api/user/delete/123
GET  /api/posts?action=create
```

### Laravel API Implementation

```php
// routes/api.php
Route::apiResource('users', UserController::class);

// Equivalent to:
// GET    /api/users       -> index()
// POST   /api/users       -> store()
// GET    /api/users/{id}  -> show()
// PUT    /api/users/{id}  -> update()
// DELETE /api/users/{id}  -> destroy()

// Controller
class UserController extends Controller {
    public function index() {
        $users = User::paginate(20);

        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'total' => $users->total(),
                'per_page' => $users->perPage()
            ]
        ]);
    }

    public function store(StoreUserRequest $request) {
        $user = User::create($request->validated());

        return response()->json($user, 201)
            ->header('Location', route('users.show', $user));
    }

    public function show(User $user) {
        return response()->json($user);
    }

    public function update(UpdateUserRequest $request, User $user) {
        $user->update($request->validated());

        return response()->json($user);
    }

    public function destroy(User $user) {
        $user->delete();

        return response()->json(null, 204);
    }
}
```

### API Resources (Transformers)

```php
// app/Http/Resources/UserResource.php
class UserResource extends JsonResource {
    public function toArray($request) {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at->toIso8601String(),
            'posts' => PostResource::collection($this->whenLoaded('posts')),
            'is_admin' => $this->when($this->isAdmin(), true),
            'meta' => [
                'link' => route('users.show', $this->id)
            ]
        ];
    }
}

// Usage
return UserResource::collection(User::paginate());
return new UserResource($user);
```

### Versioning

```php
// URL versioning
Route::prefix('v1')->group(function () {
    Route::apiResource('users', UserController::class);
});

Route::prefix('v2')->group(function () {
    Route::apiResource('users', V2\UserController::class);
});

// Header versioning
Route::middleware('api.version:v1')->group(function () {
    Route::apiResource('users', UserController::class);
});
```

### Error Handling

```php
// app/Exceptions/Handler.php
public function render($request, Throwable $exception) {
    if ($request->expectsJson()) {
        if ($exception instanceof ModelNotFoundException) {
            return response()->json([
                'error' => 'Resource not found',
                'message' => $exception->getMessage()
            ], 404);
        }

        if ($exception instanceof ValidationException) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $exception->errors()
            ], 422);
        }

        return response()->json([
            'error' => 'Server error',
            'message' => $exception->getMessage()
        ], 500);
    }

    return parent::render($request, $exception);
}
```

### Filtering, Sorting, Pagination

```php
class UserController {
    public function index(Request $request) {
        $query = User::query();

        // Filtering
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        return UserResource::collection($users);
    }
}

// Usage:
// GET /api/users?status=active&search=john&sort_by=name&sort_order=asc&per_page=20
```

### Rate Limiting

```php
// routes/api.php
Route::middleware('throttle:60,1')->group(function () {
    Route::apiResource('users', UserController::class);
});

// Custom rate limit
Route::middleware('throttle:api')->group(function () {
    // config/sanctum.php or custom RateLimiter
});

// app/Providers/RouteServiceProvider.php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

**Follow-up:**
- What is HATEOAS?
- How do you version APIs?
- What are the differences between PUT and PATCH?

**Key Points:**
- Resource-based URLs (/users, /posts)
- HTTP methods (GET, POST, PUT, DELETE)
- Proper status codes (200, 201, 404, 422, 500)
- API Resources for transformation
- Pagination, filtering, sorting
- Rate limiting and authentication

---

## Question 3: Explain database design best practices.

**Answer:**

### Normalization

```sql
-- First Normal Form (1NF): Atomic values
❌ Bad:
CREATE TABLE users (
    id INT,
    name VARCHAR(255),
    emails VARCHAR(255) -- 'email1@test.com, email2@test.com'
);

✅ Good:
CREATE TABLE users (
    id INT,
    name VARCHAR(255)
);

CREATE TABLE user_emails (
    id INT,
    user_id INT,
    email VARCHAR(255)
);

-- Second Normal Form (2NF): No partial dependencies
-- Third Normal Form (3NF): No transitive dependencies

-- Example: Order system
CREATE TABLE orders (
    id INT PRIMARY KEY,
    user_id INT,
    total DECIMAL(10,2),
    created_at TIMESTAMP
);

CREATE TABLE order_items (
    id INT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT,
    price DECIMAL(10,2) -- Store price at time of order
);

CREATE TABLE products (
    id INT PRIMARY KEY,
    name VARCHAR(255),
    current_price DECIMAL(10,2)
);
```

### Indexing Strategy

```sql
-- Primary key (automatic index)
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT
);

-- Foreign keys
CREATE INDEX idx_posts_user_id ON posts(user_id);
CREATE INDEX idx_comments_post_id ON comments(post_id);

-- Frequently queried columns
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_orders_status ON orders(status);

-- Composite indexes
CREATE INDEX idx_posts_user_status ON posts(user_id, status);
-- Good for: WHERE user_id = ? AND status = ?
-- Also works: WHERE user_id = ? (leftmost prefix)
-- Won't use index: WHERE status = ?

-- Unique indexes
CREATE UNIQUE INDEX idx_users_email_unique ON users(email);

-- Full-text search
CREATE FULLTEXT INDEX idx_posts_content ON posts(title, body);
```

### Relationships in Laravel

```php
// One-to-One
class User extends Model {
    public function profile() {
        return $this->hasOne(Profile::class);
    }
}

// Schema
Schema::create('profiles', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('bio');
});

// One-to-Many
class Post extends Model {
    public function comments() {
        return $this->hasMany(Comment::class);
    }
}

// Many-to-Many
class User extends Model {
    public function roles() {
        return $this->belongsToMany(Role::class);
    }
}

// Pivot table
Schema::create('role_user', function (Blueprint $table) {
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('role_id')->constrained()->cascadeOnDelete();
    $table->timestamps();
    $table->primary(['user_id', 'role_id']);
});

// Polymorphic
class Comment extends Model {
    public function commentable() {
        return $this->morphTo();
    }
}

Schema::create('comments', function (Blueprint $table) {
    $table->id();
    $table->morphs('commentable'); // Creates commentable_id and commentable_type
    $table->text('body');
});
```

### Soft Deletes

```php
// Migration
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->softDeletes(); // adds deleted_at column
});

// Model
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model {
    use SoftDeletes;
}

// Usage
$post->delete(); // Sets deleted_at
Post::withTrashed()->get(); // Include soft deleted
Post::onlyTrashed()->get(); // Only soft deleted
$post->restore(); // Restore soft deleted
$post->forceDelete(); // Permanently delete
```

### Data Integrity

```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')
        ->constrained()
        ->cascadeOnDelete(); // Delete orders when user deleted

    $table->enum('status', ['pending', 'completed', 'cancelled'])
        ->default('pending');

    $table->decimal('total', 10, 2)->unsigned();

    $table->timestamp('completed_at')->nullable();
    $table->timestamps();

    // Check constraints (MySQL 8.0.16+)
    $table->check('total >= 0');
});
```

### Query Optimization

```php
// N+1 Problem
❌ $users = User::all();
   foreach ($users as $user) {
       echo $user->profile->bio; // N queries
   }

✅ $users = User::with('profile')->get(); // 2 queries

// Select specific columns
✅ User::select('id', 'name', 'email')->get();

// Use cursor for large datasets
foreach (User::cursor() as $user) {
    // Process one at a time
}

// Chunk large updates
User::chunk(100, function ($users) {
    foreach ($users as $user) {
        $user->update(['status' => 'active']);
    }
});
```

**Follow-up:**
- When should you denormalize data?
- How do you handle database migrations in production?
- What are the trade-offs of soft deletes?

**Key Points:**
- Normalize to 3NF for data integrity
- Index foreign keys and frequently queried columns
- Use composite indexes for multi-column queries
- Enforce constraints (foreign keys, unique, check)
- Eager load relationships to avoid N+1
- Use soft deletes for auditing

---

## Question 7: How do you guarantee each microservice updates its database when an action affects multiple microservices?

**Answer:**

When an action requires updating multiple microservices, you can't use traditional database transactions because each microservice has its own database. Instead, you use patterns for distributed transactions.

### 1. Saga Pattern

The Saga pattern breaks a distributed transaction into a series of local transactions. Each service performs its local transaction and publishes an event that triggers the next service. If one step fails, compensating transactions undo the previous steps.

```
Order Service:
1. Create order (pending) → Publish "OrderCreated"
     ↓
Payment Service:
2. Process payment → Publish "PaymentProcessed"
     ↓
Inventory Service:
3. Reserve items → Publish "ItemsReserved"
     ↓
Order Service:
4. Confirm order → Complete
```

### 2. Choreography vs Orchestration

#### Choreography (Decentralized)
```php
// Each service listens to events and reacts

// OrderService
event(new OrderCreated($order));

// PaymentService - listens to OrderCreated
public function handle(OrderCreated $event): void
{
    $payment = $this->processPayment($event->order);
    event(new PaymentProcessed($payment));
}

// InventoryService - listens to PaymentProcessed
public function handle(PaymentProcessed $event): void
{
    $this->reserveItems($event->payment->order);
    event(new ItemsReserved($event->payment->order));
}
```

#### Orchestration (Centralized)
```php
// OrderCoordinator orchestrates the entire flow

class OrderCoordinator
{
    public function execute(CreateOrder $command): void
    {
        try {
            // Step 1: Create order
            $order = $this->orderService->create($command->data);
            
            // Step 2: Process payment
            $payment = $this->paymentService->process($order);
            
            // Step 3: Reserve inventory
            $this->inventoryService->reserve($order);
            
            // Step 4: Confirm order
            $this->orderService->confirm($order);
        } catch (PaymentFailed $e) {
            // Compensate: Cancel order
            $this->orderService->cancel($order);
            throw $e;
        } catch (InventoryUnavailable $e) {
            // Compensate: Refund payment
            $this->paymentService->refund($payment);
            $this->orderService->cancel($order);
            throw $e;
        }
    }
}
```

### 3. Compensation Strategies

```php
class PaymentService
{
    public function refund(Payment $payment): void
    {
        // Reverse the payment
        $this->paymentGateway->refund($payment->transactionId);
        $payment->update(['status' => 'refunded']);
    }
}

class InventoryService
{
    public function release(Reservation $reservation): void
    {
        // Release reserved items
        foreach ($reservation->items as $item) {
            $item->increment('reserved_quantity', $reservation->quantity);
        }
        $reservation->delete();
    }
}
```

### 4. Outbox Pattern (Reliable Events)

To ensure events are published reliably, use the outbox pattern:

```php
// Instead of directly publishing events, write to an outbox table

class OrderService
{
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = Order::create($data);
            
            // Write to outbox (same transaction)
            Outbox::create([
                'type' => 'OrderCreated',
                'payload' => json_encode(['order_id' => $order->id]),
            ]);
            
            return $order;
        });
    }
}

// Background job processes outbox
class ProcessOutbox
{
    public function handle(): void
    {
        foreach (Outbox::pending()->get() as $event) {
            try {
                $this->eventBus->publish($event->type, $event->payload);
                $event->markAsProcessed();
            } catch (Exception $e) {
                // Will retry later
            }
        }
    }
}
```

### 5. Idempotency

Always design for idempotency to handle duplicate messages:

```php
class PaymentService
{
    public function processPayment(array $data): Payment
    {
        // Check if already processed (idempotency key)
        $existing = Payment::where('idempotency_key', $data['idempotency_key'])->first();
        
        if ($existing) {
            return $existing;
        }
        
        return Payment::create($data);
    }
}
```

### 6. Distributed Tracing

Use correlation IDs to track requests across services:

```php
class OrderController
{
    public function store(Request $request): Response
    {
        // Generate or extract correlation ID
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        
        // Pass to all downstream services
        Http::withHeaders([
            'X-Correlation-ID' => $correlationId,
        ])->post('payment-service/api/pay', $data);
        
        return response()->json(['correlation_id' => $correlationId]);
    }
}
```

**Follow-up:**
- When would you choose choreography vs orchestration?
- How do you handle eventual consistency?
- What happens if a compensating transaction fails?

**Key Points:**
- Saga pattern for distributed transactions
- Choreography (event-driven) vs Orchestration (centralized)
- Compensation/rollback for failures
- Outbox pattern for reliable events
- Idempotency for message handling
- Correlation IDs for tracing
- Accept eventual consistency

---

## Question 17: Explain Domain-Driven Design (DDD) core concepts and implementation.

**Answer:**

### What is DDD?

Domain-Driven Design is an approach to software development that emphasizes collaboration between technical and domain experts to create a shared model of the business domain. Rather than focusing on technical structures, DDD centers on understanding the problem space—the business domain—and aligning code structure with business concepts.

### Why Use DDD?

| Problem | DDD Solution |
|---------|--------------|
| Complex business logic scattered across the codebase | Encapsulate logic within domain objects |
| Miscommunication between developers and domain experts | Shared Ubiquitous Language |
| Anemic domain models (data only, no behavior) | Rich domain models with behavior |
| Tight coupling to infrastructure | Clean separation via Aggregates and Repositories |
| Difficulty scaling complex domains | Bounded Contexts isolate subdomains |

### When to Use DDD? DDD vs Simpler Models?

- **Complex business domains** with evolving requirements
- **Large teams** needing clear domain boundaries
- **Systems where domain logic is a competitive advantage**
- **Microservices architecture** (each service as a bounded context)

**Not recommended for:** Simple CRUD applications, data-centric systems with minimal business logic.

**DDD vs Simpler Domain Models:**
- **Transaction Script**: Direct procedural code for simple operations (great for simple forms, APIs)
- **Table Module**: Single class per table, organizes logic around database structure
- **Domain Model**: Rich objects with behavior—use DDD when business rules are complex and change frequently

### Identifying Bounded Contexts

1. **Domain Expert Input**: Talk to business stakeholders about naturally separate areas
2. **Semantic Clues**: Different terms for the same concept indicate different contexts
3. **Change Patterns**: Things that change together stay in same context
4. **Team Structure**: Conway's Law—teams often map to bounded contexts
5. **Technology Boundaries**: Different databases, APIs, or deployment cycles suggest boundaries

Example signals:
- "Product" means different things in Catalog (SKU, pricing) vs Shipping (weight, dimensions)
- Accounting vs Inventory—both use "invoice" but differently
- Different teams own different business capabilities

### Trade-offs of DDD

| Trade-off | Impact |
|-----------|--------|
| Initial complexity | More classes, more abstraction—worth it for complex domains |
| Learning curve | Team needs to understand DDD concepts |
| Over-engineering risk | Don't apply DDD to simple domains |
| Performance | Domain objects may be less performant than raw queries initially |
| Flexibility | Changing domain model is harder—design thoughtfully |

### Core DDD Concepts

```
┌─────────────────────────────────────────────────────────────┐
│                    Bounded Context                          │
│  ┌───────────────────────────────────────────────────────┐  │
│  │                    Domain                              │  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐   │  │
│  │  │  Entities   │  │Value Objects│  │  Aggregates │   │  │
│  │  └─────────────┘  └─────────────┘  └─────────────┘   │  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐   │  │
│  │  │Domain Events│  │Domain Services│ │ Repositories│   │  │
│  │  └─────────────┘  └─────────────┘  └─────────────┘   │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### 1. Building Blocks

#### Entities

Objects with unique identity that persists through changes:

```php
// Entity - has identity that matters
class Order extends Entity
{
    private OrderId $id;
    private CustomerId $customerId;
    private Money $total;
    private OrderStatus $status;

    public functionId(): OrderId
    {
        return $this->id;
    }

    public function changeStatus(OrderStatus $newStatus): void
    {
        if (!$this->canTransitionTo($newStatus)) {
            throw new InvalidTransitionException($this->status, $newStatus);
        }
        $this->status = $newStatus;
        $this->recordDomainEvent(new OrderStatusChanged($this->id, $newStatus));
    }
}
```

#### Value Objects

Immutable objects defined by their attributes, no identity:

```php
// Value Object - no identity, immutable
readonly class Money
{
    public function __construct(
        private int $amount,
        private Currency $currency
    ) {}

    public function add(Money $other): Money
    {
        if ($this->currency !== $other->currency) {
            throw new CurrencyMismatchException();
        }
        return new Money($this->amount + $other->amount, $this->currency);
    }

    public function multiply(int $multiplier): Money
    {
        return new Money($this->amount * $multiplier, $this->currency);
    }
}

// Usage
$price = new Money(1000, Currency::USD);
$total = $price->multiply(3); // Returns new Money object
```

#### Aggregates

Cluster of related entities and value objects with one root entity:

```php
// Aggregate Root - the only entry point
class Order extends AggregateRoot
{
    private OrderId $id;
    private CustomerId $customerId;
    private array $items = [];
    private OrderStatus $status;

    public function addProduct(Product $product, int $quantity): void
    {
        if ($this->status !== OrderStatus::PENDING) {
            throw new OrderAlreadyPlacedException();
        }

        $lineItem = new OrderLineItem(
            LineItemId::generate(),
            $product->id(),
            $quantity,
            $product->price()
        );
        $this->items[] = $lineItem;
    }

    public function place(): void
    {
        if (empty($this->items)) {
            throw new EmptyOrderException();
        }
        $this->status = OrderStatus::PLACED;
        $this->recordDomainEvent(new OrderPlaced($this->id, $this->customerId));
    }
}

// Aggregate Root Base
abstract class AggregateRoot
{
    private array $domainEvents = [];

    protected function recordDomainEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }
}
```

### 2. Bounded Contexts

Logical boundaries where specific domain terminology applies:

```
┌─────────────────────────────────────────────────────────────┐
│                     E-Commerce System                       │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │
│  │   Catalog    │  │    Order     │  │   Shipping   │    │
│  │   Context    │  │   Context    │  │   Context    │    │
│  │              │  │              │  │              │    │
│  │  - Product   │  │  - Order     │  │  - Shipment  │    │
│  │  - SKU       │  │  - Cart      │  │  - Delivery  │    │
│  │  - Inventory │  │  - Payment   │  │  - Tracking  │    │
│  │              │  │              │  │              │    │
│  └──────────────┘  └──────────────┘  └──────────────┘    │
│        │                 │                  │              │
│        └─────────────────┼──────────────────┘              │
│                          │                                  │
│                   ┌──────┴──────┐                          │
│                   │   Integr    │                          │
│                   │   Context   │                          │
│                   └─────────────┘                          │
└─────────────────────────────────────────────────────────────┘
```

Context Mapping:

```php
// Anti-Corruption Layer - protect your context from others
class OrderContextAdapter
{
    public function __construct(
        private CatalogClient $catalogClient,
        private ShippingGateway $shippingGateway
    ) {}

    public function getProductDetails(ProductId $id): OrderProductDto
    {
        // Transform external format to internal format
        $catalogProduct = $this->catalogClient->getProduct($id->value);
        
        return new OrderProductDto(
            id: new ProductId($catalogProduct['sku']),
            name: $catalogProduct['title'],
            price: new Money($catalogProduct['unit_price'], Currency::USD)
        );
    }
}
```

### 3. Domain Services

Encapsulates domain logic that doesn't belong to entities/value objects:

```php
// Domain Service - stateless domain logic
class PricingService
{
    public function calculateTotal(Order $order, ?Promotion $promotion): Money
    {
        $subtotal = array_reduce(
            $order->lineItems(),
            fn(Money $total, LineItem $item) => $total->add($item->subtotal()),
            new Money(0, Currency::USD)
        );

        if ($promotion === null) {
            return $subtotal;
        }

        $discount = $promotion->calculateDiscount($subtotal);
        return $subtotal->subtract($discount);
    }
}
```

### 4. Domain Events

Represent something significant that happened in the domain:

```php
// Domain Event
readonly class OrderPlaced implements DomainEvent
{
    public function __construct(
        public OrderId $orderId,
        public CustomerId $customerId,
        public DateTimeImmutable $occurredAt
    ) {}
}

// Event Handler
class SendOrderConfirmationHandler
{
    public function handle(OrderPlaced $event): void
    {
        $this->mailer->sendOrderConfirmation(
            $event->customerId,
            $event->orderId
        );
    }
}

// Event Dispatcher
class EventDispatcher
{
    private array $handlers = [];

    public function register(string $eventClass, callable $handler): void
    {
        $this->handlers[$eventClass][] = $handler;
    }

    public function dispatch(DomainEvent $event): void
    {
        $eventClass = get_class($event);
        
        foreach ($this->handlers[$eventClass] ?? [] as $handler) {
            $handler($event);
        }
    }
}
```

### 5. Repository Pattern

Abstracts data access, works with aggregates:

```php
// Repository Interface (in Domain layer)
interface OrderRepository
{
    public function findById(OrderId $id): ?Order;
    public function save(Order $order): void;
    public function findByCustomer(CustomerId $customerId): array;
}

// Repository Implementation (in Infrastructure layer)
class EloquentOrderRepository implements OrderRepository
{
    public function findById(OrderId $id): ?Order
    {
        $order = OrderModel::with('items.product')->find($id->value);
        
        if ($order === null) {
            return null;
        }
        
        return $this->mapToAggregate($order);
    }

    public function save(Order $order): void
    {
        $orderModel = OrderModel::findOrNew($order->id()->value);
        $orderModel->customer_id = $order->customerId()->value;
        $orderModel->status = $order->status()->value;
        $orderModel->save();

        // Save items, handle domain events, etc.
    }

    private function mapToAggregate(OrderModel $model): Order
    {
        $order = new Order(
            OrderId::fromString($model->id),
            CustomerId::fromString($model->customer_id),
            OrderStatus::from($model->status)
        );
        
        foreach ($model->items as $item) {
            $order->addItem(/* ... */);
        }
        
        return $order;
    }
}
```

### Laravel Implementation Structure

```
app/
├── Domain/
│   ├── Entities/
│   │   └── Order.php
│   ├── ValueObjects/
│   │   ├── Money.php
│   │   └── OrderId.php
│   ├── Aggregates/
│   │   └── Order.php
│   ├── Events/
│   │   └── OrderPlaced.php
│   ├── Services/
│   │   └── PricingService.php
│   └── Repositories/
│       └── OrderRepository.php
│
├── Application/
│   ├── Commands/
│   │   └── PlaceOrderCommand.php
│   ├── Queries/
│   │   └── GetOrderQuery.php
│   └── Services/
│       └── OrderApplicationService.php
│
└── Infrastructure/
    ├── Persistence/
    │   └── EloquentOrderRepository.php
    └── Messaging/
        └── EventDispatcher.php
```

### Application Service (Orchestration)

```php
class OrderApplicationService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private EventDispatcher $eventDispatcher,
        private InventoryService $inventoryService,
        private PaymentGateway $paymentGateway
    ) {}

    public function placeOrder(PlaceOrderCommand $command): OrderResult
    {
        $customer = $this->getCustomer($command->customerId);
        
        $order = Order::create(
            customerId: $customer->id(),
            items: $command->items
        );

        // Reserve inventory
        foreach ($command->items as $item) {
            $this->inventoryService->reserve($item->productId, $item->quantity);
        }

        // Process payment
        $this->paymentGateway->charge($customer, $order->total());

        // Persist
        $this->orderRepository->save($order);

        // Dispatch domain events
        foreach ($order->pullDomainEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }

        return new OrderResult($order->id());
    }
}
```

**Key Points:**
- Entities: Identity-based objects that persist through changes
- Value Objects: Immutable, defined by attributes, no identity
- Aggregates: Cluster with one root entity, boundary for consistency
- Bounded Context: Logical boundary with specific domain language
- Domain Events: Significant domain occurrences
- Repository: Abstracts data access, works with aggregates
- Domain Services: Stateless logic that doesn't fit entities

---

## Notes

Add more questions covering:
- CAP theorem
- Database replication and sharding
- Message queues (RabbitMQ, Redis)
- API Gateway patterns
- Load balancing strategies
- Microservices communication (REST, gRPC, messaging)
- Distributed transactions and saga pattern
