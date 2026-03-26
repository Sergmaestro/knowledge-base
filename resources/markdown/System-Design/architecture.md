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

## Notes

Add more questions covering:
- CAP theorem
- Database replication and sharding
- Message queues (RabbitMQ, Redis)
- API Gateway patterns
- Load balancing strategies
- Microservices communication (REST, gRPC, messaging)
- Distributed transactions and saga pattern
- Domain-Driven Design (DDD)
