# API Design & Best Practices

## Question 1: How do you design a scalable REST API?

**Answer:**

### REST Principles

```
1. Resource-based URLs (nouns, not verbs)
2. HTTP methods for actions (GET, POST, PUT, DELETE)
3. Stateless communication
4. Standard HTTP status codes
5. Proper use of HTTP headers
6. HATEOAS (optional but recommended)
```

### Resource Naming

```
✅ Good RESTful URLs:
GET    /api/v1/users              - List users
POST   /api/v1/users              - Create user
GET    /api/v1/users/123          - Get user 123
PUT    /api/v1/users/123          - Update user 123 (full)
PATCH  /api/v1/users/123          - Update user 123 (partial)
DELETE /api/v1/users/123          - Delete user 123

# Nested resources
GET    /api/v1/users/123/posts    - User's posts
POST   /api/v1/users/123/posts    - Create post for user
GET    /api/v1/posts/456/comments - Post's comments

# Query parameters for filtering, sorting, pagination
GET    /api/v1/users?status=active&sort=created_at&page=2&per_page=20

❌ Bad URLs:
GET  /api/getAllUsers
POST /api/createUser
GET  /api/user/delete/123
GET  /api/posts?action=create
POST /api/users/123/delete
```

### HTTP Status Codes

```php
200 OK                - Successful GET, PUT, PATCH
201 Created           - Successful POST
204 No Content        - Successful DELETE
400 Bad Request       - Invalid input
401 Unauthorized      - Authentication required
403 Forbidden         - Insufficient permissions
404 Not Found         - Resource doesn't exist
422 Unprocessable     - Validation errors
429 Too Many Requests - Rate limit exceeded
500 Internal Error    - Server error
```

### Laravel Implementation

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::apiResource('users', UserController::class);
    Route::apiResource('posts', PostController::class);
});

// app/Http/Controllers/UserController.php
class UserController extends Controller {
    public function index(Request $request) {
        $query = User::query();

        // Filtering
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 15), 100); // Max 100
        $users = $query->paginate($perPage);

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request) {
        $user = User::create($request->validated());

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('users.show', $user));
    }

    public function show(User $user) {
        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user) {
        $user->update($request->validated());

        return new UserResource($user);
    }

    public function destroy(User $user) {
        $user->delete();

        return response()->noContent();
    }
}
```

### API Resources (Response Transformation)

```php
// app/Http/Resources/UserResource.php
class UserResource extends JsonResource {
    public function toArray($request) {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Conditional attributes
            'is_admin' => $this->when($this->isAdmin(), true),
            'email_verified_at' => $this->when($this->email_verified_at,
                $this->email_verified_at->toIso8601String()),

            // Relationships (only when loaded)
            'posts' => PostResource::collection($this->whenLoaded('posts')),
            'profile' => new ProfileResource($this->whenLoaded('profile')),

            // Meta data
            '_links' => [
                'self' => route('users.show', $this->id),
                'posts' => route('users.posts.index', $this->id),
            ],
        ];
    }
}

// Usage
return UserResource::collection(User::paginate());
return new UserResource($user);
```

### Error Handling

```php
// app/Exceptions/Handler.php
public function render($request, Throwable $exception) {
    if ($request->expectsJson()) {
        return $this->handleApiException($request, $exception);
    }

    return parent::render($request, $exception);
}

protected function handleApiException($request, Throwable $exception) {
    $status = 500;
    $message = 'Server error';

    if ($exception instanceof ModelNotFoundException) {
        $status = 404;
        $message = 'Resource not found';
    } elseif ($exception instanceof ValidationException) {
        $status = 422;
        return response()->json([
            'error' => 'Validation failed',
            'errors' => $exception->errors()
        ], $status);
    } elseif ($exception instanceof AuthenticationException) {
        $status = 401;
        $message = 'Unauthenticated';
    } elseif ($exception instanceof AuthorizationException) {
        $status = 403;
        $message = 'Forbidden';
    }

    return response()->json([
        'error' => $message,
        'message' => $exception->getMessage(),
        'code' => $exception->getCode()
    ], $status);
}
```

### Rate Limiting

```php
// app/Providers/RouteServiceProvider.php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

// Different limits for authenticated users
RateLimiter::for('api', function (Request $request) {
    return $request->user()
        ? Limit::perMinute(120)->by($request->user()->id)
        : Limit::perMinute(20)->by($request->ip());
});

// Apply in routes
Route::middleware('throttle:api')->group(function () {
    Route::apiResource('users', UserController::class);
});
```

### Pagination & Meta Data

```php
class UserController {
    public function index() {
        $users = User::paginate(20);

        return response()->json([
            'data' => UserResource::collection($users->items()),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
            'links' => [
                'first' => $users->url(1),
                'last' => $users->url($users->lastPage()),
                'prev' => $users->previousPageUrl(),
                'next' => $users->nextPageUrl(),
            ]
        ]);
    }
}
```

### API Versioning

```php
// URL versioning (most common)
Route::prefix('v1')->group(function () {
    Route::apiResource('users', V1\UserController::class);
});

Route::prefix('v2')->group(function () {
    Route::apiResource('users', V2\UserController::class);
});

// Header versioning
Route::middleware('api.version:v1')->group(function () {
    Route::apiResource('users', UserController::class);
});

// Custom middleware
class ApiVersion {
    public function handle($request, Closure $next, $version) {
        config(['app.api_version' => $version]);
        return $next($request);
    }
}
```

### Authentication

```php
// Laravel Sanctum (simple token-based)
Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => new UserResource($user)
    ]);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return new UserResource($request->user());
    });
});
```

### Documentation

```php
// Use OpenAPI/Swagger
composer require darkaonline/l5-swagger

/**
 * @OA\Get(
 *     path="/api/v1/users",
 *     summary="List users",
 *     tags={"Users"},
 *     @OA\Parameter(name="status", in="query", required=false),
 *     @OA\Parameter(name="page", in="query", required=false),
 *     @OA\Response(response=200, description="Successful operation"),
 *     security={{"bearer":{}}}
 * )
 */
public function index() {
    // ...
}
```

**Follow-up:**
- How do you handle API versioning without breaking clients?
- What is HATEOAS and should you use it?
- How do you implement cursor-based pagination for large datasets?

**Key Points:**
- Resource-based URLs (nouns)
- HTTP methods for CRUD
- Proper status codes (200, 201, 404, 422, 500)
- Use API Resources for transformation
- Pagination, filtering, sorting
- Rate limiting for protection
- Versioning (URL or header)
- Document with OpenAPI/Swagger

---

## Question 2: When would you choose REST vs GraphQL?

**Answer:**

### REST

**When to use:**
- Simple CRUD operations
- Public APIs with many clients
- Caching is important (HTTP caching works well)
- Team familiar with REST
- Multiple response formats needed

**Pros:**
- Simple and well-understood
- HTTP caching works out of the box
- Stateless
- Good tooling

**Cons:**
- Over-fetching (getting more data than needed)
- Under-fetching (need multiple requests)
- Versioning can be complex

### GraphQL

**When to use:**
- Complex nested data requirements
- Mobile apps (minimize requests)
- Frequently changing requirements
- Multiple clients with different needs
- Real-time data with subscriptions

**Pros:**
- Single endpoint
- Clients request exactly what they need
- Strong typing
- No versioning (just add fields)
- Introspection (self-documenting)

**Cons:**
- More complex to implement
- HTTP caching harder
- Query complexity (can be slow)
- Learning curve

### Comparison

```php
// REST - Multiple requests
GET /api/users/123          → { id, name, email }
GET /api/users/123/posts    → [ { id, title }, ... ]
GET /api/posts/1/comments   → [ { id, body }, ... ]

// 3 requests to get user with posts and comments

// GraphQL - Single request
query {
  user(id: 123) {
    id
    name
    email
    posts {
      id
      title
      comments {
        id
        body
      }
    }
  }
}

// 1 request gets everything
```

### When to Use Each

| Use Case | REST | GraphQL |
|----------|------|---------|
| Simple CRUD | ✅ | ❌ |
| Public API | ✅ | ⚠️ |
| Mobile app | ⚠️ | ✅ |
| Many clients with different needs | ❌ | ✅ |
| Real-time updates | ⚠️ | ✅ |
| HTTP caching important | ✅ | ❌ |
| Microservices | ✅ | ✅ (with federation) |

**Key Points:**
- REST: Simple, cacheable, well-known
- GraphQL: Flexible, single endpoint, exactly what you need
- REST for public APIs, GraphQL for apps with complex data needs
- Can use both (REST for simple, GraphQL for complex)

---

## Question 3: How do you version APIs without breaking existing clients?

**Answer:**

### Versioning Strategies

#### 1. URL Versioning (Most Common)

```php
// Old version (v1)
GET /api/v1/users/123
{
  "id": 123,
  "name": "John Doe"
}

// New version (v2) - added email field
GET /api/v2/users/123
{
  "id": 123,
  "name": "John Doe",
  "email": "john@example.com"
}

// Both versions run simultaneously
Route::prefix('v1')->group(function () {
    Route::apiResource('users', V1\UserController::class);
});

Route::prefix('v2')->group(function () {
    Route::apiResource('users', V2\UserController::class);
});
```

#### 2. Header Versioning

```php
// Client sends version in header
GET /api/users/123
Accept: application/vnd.api.v1+json

// Middleware handles version
class ApiVersion {
    public function handle($request, Closure $next) {
        $accept = $request->header('Accept');

        if (str_contains($accept, 'vnd.api.v2')) {
            $version = 'v2';
        } else {
            $version = 'v1';
        }

        config(['api.version' => $version]);

        return $next($request);
    }
}
```

#### 3. Accept Header

```php
// Client specifies version
Accept: application/json; version=2

// Or custom header
API-Version: 2
```

### Backward Compatibility Techniques

#### 1. Additive Changes Only

```php
// ✅ Good: Add new fields
{
  "id": 123,
  "name": "John",
  "email": "john@example.com",  // New field (old clients ignore)
  "avatar": "url"                // New field
}

// ❌ Bad: Remove or rename fields
{
  "id": 123,
  "full_name": "John"  // Renamed from "name" - breaks old clients!
}
```

#### 2. Deprecation Strategy

```php
class UserResource extends JsonResource {
    public function toArray($request) {
        $data = [
            'id' => $this->id,
            'full_name' => $this->name,  // New field
        ];

        // Include deprecated field for backward compatibility
        if (config('api.version') === 'v1') {
            $data['name'] = $this->name;  // Deprecated
        }

        return $data;
    }
}

// Add deprecation header
return response()->json($data)
    ->header('X-API-Deprecation', 'Field "name" is deprecated. Use "full_name" instead.')
    ->header('X-API-Sunset', '2024-12-31');  // When it will be removed
```

#### 3. Feature Flags

```php
class UserController {
    public function show(User $user) {
        $data = [
            'id' => $user->id,
            'name' => $user->name,
        ];

        // New feature, gradually rolled out
        if (FeatureFlag::isEnabled('include_user_stats', request()->user())) {
            $data['stats'] = [
                'posts_count' => $user->posts_count,
                'followers_count' => $user->followers_count,
            ];
        }

        return response()->json($data);
    }
}
```

#### 4. Shared Base with Version-Specific Additions

```php
// Base controller for shared logic
abstract class BaseUserController {
    protected function getUserData(User $user): array {
        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }
}

// V1 controller
class V1\UserController extends BaseUserController {
    public function show(User $user) {
        return response()->json($this->getUserData($user));
    }
}

// V2 controller - adds new fields
class V2\UserController extends BaseUserController {
    public function show(User $user) {
        $data = $this->getUserData($user);

        $data['email'] = $user->email;
        $data['verified'] = $user->email_verified_at !== null;

        return response()->json($data);
    }
}
```

### Migration Path

```
1. Announce deprecation (3-6 months notice)
   - Add deprecation headers
   - Update documentation
   - Notify clients via email

2. Support both versions simultaneously
   - v1 (deprecated) + v2 (current)
   - Monitor v1 usage (analytics)

3. Remove old version
   - When v1 usage < 5%
   - After sunset date
   - Keep v2, prepare v3
```

### Testing Both Versions

```php
// Test v1
public function test_v1_user_endpoint() {
    $response = $this->getJson('/api/v1/users/123');

    $response->assertJson([
        'id' => 123,
        'name' => 'John'
    ]);

    $response->assertJsonMissing(['email']);  // v1 doesn't have email
}

// Test v2
public function test_v2_user_endpoint() {
    $response = $this->getJson('/api/v2/users/123');

    $response->assertJson([
        'id' => 123,
        'name' => 'John',
        'email' => 'john@example.com'  // v2 has email
    ]);
}
```

### Best Practices

```
1. ✅ Add new fields (backward compatible)
2. ✅ Add new endpoints
3. ✅ Make optional parameters
4. ❌ Remove fields (breaks clients)
5. ❌ Rename fields (breaks clients)
6. ❌ Change data types (breaks clients)
7. ❌ Make required parameters

When breaking changes needed:
- Create new version (v2)
- Support both simultaneously
- Deprecate old version
- Remove after sunset period
```

**Follow-up:**
- How long should you support old API versions?
- How do you handle breaking changes?
- What is semantic versioning for APIs?

**Key Points:**
- URL versioning most common (`/api/v1`, `/api/v2`)
- Add fields, don't remove (backward compatible)
- Deprecate before removing (3-6 months notice)
- Support multiple versions simultaneously
- Use deprecation headers
- Test all versions
- Document changes clearly

---

## Notes

Add more questions covering:
- API security (OAuth2, JWT)
- API gateways and rate limiting
- Webhooks and callbacks
- Long-running operations (async APIs)
- Bulk operations
- Partial responses and field selection
- API monitoring and analytics
