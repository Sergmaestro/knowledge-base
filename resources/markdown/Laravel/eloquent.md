# Laravel Eloquent ORM

## Question 1: Explain the N+1 Query Problem and how to solve it.

**Answer:**

The N+1 problem occurs when you execute one query to get N records, then N additional queries to get related data.

### Problem

```php
// 1 query to get all users
$users = User::all();  // SELECT * FROM users

// N queries (one per user) to get posts
foreach ($users as $user) {
    echo $user->posts->count();  // SELECT * FROM posts WHERE user_id = ?
}

// Total: 1 + N queries (if 100 users = 101 queries!)
```

### Solution 1: Eager Loading

```php
// 2 queries total (1 for users, 1 for all posts)
$users = User::with('posts')->get();
// SELECT * FROM users
// SELECT * FROM posts WHERE user_id IN (1, 2, 3, ...)

foreach ($users as $user) {
    echo $user->posts->count();  // Already loaded, no query
}

// Multiple relationships
$users = User::with(['posts', 'profile', 'comments'])->get();

// Nested relationships
$users = User::with('posts.comments.author')->get();

// Constrain eager loads
$users = User::with(['posts' => function ($query) {
    $query->where('published', true)
          ->orderBy('created_at', 'desc')
          ->limit(5);
}])->get();
```

### Solution 2: Lazy Eager Loading

```php
// Already have users
$users = User::all();

// Lazy load relationships
$users->load('posts');

// Conditional lazy loading
if ($needsPosts) {
    $users->load('posts');
}
```

### Solution 3: Eager Loading Counts

```php
// Load relationship counts without loading actual data
$users = User::withCount('posts')->get();

foreach ($users as $user) {
    echo $user->posts_count;  // No additional query
}

// Multiple counts
$users = User::withCount(['posts', 'comments'])->get();

// Constrained counts
$users = User::withCount([
    'posts as published_posts_count' => function ($query) {
        $query->where('published', true);
    }
])->get();
```

### Solution 4: Exists Queries

```php
// Check existence without loading data
$users = User::withExists('posts')->get();

foreach ($users as $user) {
    if ($user->posts_exists) {
        // User has posts
    }
}
```

### Detection with Debugbar

```php
// Install Laravel Debugbar to see queries
composer require barryvdh/laravel-debugbar --dev

// Will show all executed queries and duplicate N+1 patterns
```

**Follow-up:**
- What's the difference between `with()` and `load()`?
- How do you eager load with conditions?
- Can you eager load multiple levels?

**Key Points:**
- N+1: 1 query + N additional queries for relations
- Use `with()` for eager loading
- Use `withCount()` for counts only
- Use `load()` for lazy eager loading
- Always monitor queries in development

---

## Question 2: Explain different types of Eloquent relationships with examples.

**Answer:**

### One to One

```php
// User has one Profile
class User extends Model {
    public function profile() {
        return $this->hasOne(Profile::class);
    }
}

class Profile extends Model {
    public function user() {
        return $this->belongsTo(User::class);
    }
}

// Usage
$profile = User::find(1)->profile;
$user = Profile::find(1)->user;
```

### One to Many

```php
// User has many Posts
class User extends Model {
    public function posts() {
        return $this->hasMany(Post::class);
    }
}

class Post extends Model {
    public function user() {
        return $this->belongsTo(User::class);
    }
}

// Usage
$posts = User::find(1)->posts;
$user = Post::find(1)->user;
```

### Many to Many

```php
// User has many Roles, Role has many Users
class User extends Model {
    public function roles() {
        return $this->belongsToMany(Role::class)
            ->withPivot('expires_at')  // Additional pivot columns
            ->withTimestamps()  // created_at, updated_at on pivot
            ->using(RoleUser::class);  // Custom pivot model
    }
}

class Role extends Model {
    public function users() {
        return $this->belongsToMany(User::class);
    }
}

// Database: users, roles, role_user (pivot table)

// Usage
$roles = User::find(1)->roles;

// Attach/Detach
$user->roles()->attach($roleId);
$user->roles()->attach($roleId, ['expires_at' => now()->addYear()]);
$user->roles()->detach($roleId);
$user->roles()->sync([1, 2, 3]);  // Sync to exactly these IDs

// Access pivot data
foreach ($user->roles as $role) {
    echo $role->pivot->expires_at;
}
```

### Has Many Through

```php
// Country has many Users, User has many Posts
// Get all Posts for a Country through Users
class Country extends Model {
    public function posts() {
        return $this->hasManyThrough(
            Post::class,
            User::class,
            'country_id',  // Foreign key on users table
            'user_id',     // Foreign key on posts table
            'id',          // Local key on countries table
            'id'           // Local key on users table
        );
    }
}

// Usage
$posts = Country::find(1)->posts;
```

### Has One Through

```php
// Supplier has one Account through User
class Supplier extends Model {
    public function account() {
        return $this->hasOneThrough(Account::class, User::class);
    }
}
```

### Polymorphic Relations

```php
// Comments can belong to Post or Video
class Comment extends Model {
    public function commentable() {
        return $this->morphTo();
    }
}

class Post extends Model {
    public function comments() {
        return $this->morphMany(Comment::class, 'commentable');
    }
}

class Video extends Model {
    public function comments() {
        return $this->morphMany(Comment::class, 'commentable');
    }
}

// Database: comments table has commentable_id and commentable_type

// Usage
$post = Post::find(1);
$comments = $post->comments;

$comment = Comment::find(1);
$commentable = $comment->commentable;  // Returns Post or Video
```

### Many to Many Polymorphic

```php
// Posts and Videos can have Tags
class Post extends Model {
    public function tags() {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}

class Video extends Model {
    public function tags() {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}

class Tag extends Model {
    public function posts() {
        return $this->morphedByMany(Post::class, 'taggable');
    }

    public function videos() {
        return $this->morphedByMany(Video::class, 'taggable');
    }
}

// Database: taggables (taggable_id, taggable_type, tag_id)

// Usage
$post->tags()->attach($tagId);
$tags = $video->tags;
```

**Follow-up:**
- When would you use `hasManyThrough`?
- How do polymorphic relations work under the hood?
- What is a pivot table and custom pivot models?

**Key Points:**
- One-to-One: `hasOne`, `belongsTo`
- One-to-Many: `hasMany`, `belongsTo`
- Many-to-Many: `belongsToMany`, needs pivot table
- Polymorphic: Relation can be multiple types
- `withPivot()` for extra pivot columns

---

## Question 3: What are Query Scopes and when should you use them?

**Answer:**

Scopes encapsulate reusable query logic in models.

### Local Scopes

```php
class Post extends Model {
    // Local scope: scope{Name}
    public function scopePublished($query) {
        return $query->where('published', true);
    }

    public function scopePopular($query) {
        return $query->where('views', '>', 1000);
    }

    public function scopeOfType($query, $type) {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, $days = 7) {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}

// Usage
$posts = Post::published()->get();
$popularPosts = Post::published()->popular()->get();
$recentNews = Post::ofType('news')->recent(30)->get();

// Chainable
Post::published()->popular()->recent()->orderBy('created_at')->get();
```

### Global Scopes

```php
// Apply to all queries automatically
namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class PublishedScope implements Scope {
    public function apply(Builder $builder, Model $model) {
        $builder->where('published', true);
    }
}

// Register in model
class Post extends Model {
    protected static function booted() {
        static::addGlobalScope(new PublishedScope);
    }
}

// Every query automatically filters published
Post::all();  // WHERE published = true
Post::find(1);  // WHERE id = 1 AND published = true

// Remove global scope
Post::withoutGlobalScope(PublishedScope::class)->get();

// Anonymous global scope
protected static function booted() {
    static::addGlobalScope('published', function (Builder $builder) {
        $builder->where('published', true);
    });
}
```

### Soft Delete Global Scope

```php
// Laravel's SoftDeletes trait uses global scope
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model {
    use SoftDeletes;
}

// Automatically filters deleted_at IS NULL
Post::all();

// Include soft deleted
Post::withTrashed()->get();

// Only soft deleted
Post::onlyTrashed()->get();
```

### Dynamic Scopes

```php
class Post extends Model {
    public function scopeFilter($query, array $filters) {
        $query->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        });

        $query->when($filters['type'] ?? null, function ($query, $type) {
            $query->where('type', $type);
        });

        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where('title', 'like', "%{$search}%");
        });

        return $query;
    }
}

// Usage
Post::filter($request->only(['status', 'type', 'search']))->get();
```

### Scope Chaining

```php
class User extends Model {
    public function scopeActive($query) {
        return $query->where('active', true);
    }

    public function scopeVerified($query) {
        return $query->whereNotNull('email_verified_at');
    }

    public function scopeAdmin($query) {
        return $query->where('role', 'admin');
    }
}

// Chain multiple scopes
$admins = User::active()->verified()->admin()->get();
```

**Follow-up:**
- What's the difference between local and global scopes?
- How do you remove a global scope?
- Can scopes accept parameters?

**Key Points:**
- Local scopes: Reusable query constraints
- Global scopes: Automatically applied to all queries
- Define with `scope{Name}` prefix
- Chainable and composable
- SoftDeletes is a global scope

---

## Question 4: Explain Eloquent Events and Observers.

**Answer:**

Eloquent fires events during model lifecycle. Observers listen to these events.

### Model Events

```php
class User extends Model {
    protected static function booted() {
        // Before creating
        static::creating(function ($user) {
            $user->uuid = Str::uuid();
        });

        // After created
        static::created(function ($user) {
            $user->sendWelcomeEmail();
        });

        // Before updating
        static::updating(function ($user) {
            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }
        });

        // After updated
        static::updated(function ($user) {
            Cache::forget("user:{$user->id}");
        });

        // Before saving (create or update)
        static::saving(function ($user) {
            $user->slug = Str::slug($user->name);
        });

        // Before deleting
        static::deleting(function ($user) {
            $user->posts()->delete();
        });

        // After deleted
        static::deleted(function ($user) {
            Log::info("User {$user->id} deleted");
        });
    }
}
```

### Available Events

- `retrieved`: After model fetched from database
- `creating`: Before creating
- `created`: After created
- `updating`: Before updating
- `updated`: After updated
- `saving`: Before saving (create or update)
- `saved`: After saved
- `deleting`: Before deleting
- `deleted`: After deleted
- `restoring`: Before soft-deleted model restored
- `restored`: After restored
- `replicating`: Before model replicated

### Observers

```php
// Create observer
php artisan make:observer UserObserver --model=User

// app/Observers/UserObserver.php
namespace App\Observers;

class UserObserver {
    public function creating(User $user) {
        $user->uuid = Str::uuid();
    }

    public function created(User $user) {
        Mail::to($user)->send(new WelcomeEmail);
    }

    public function updating(User $user) {
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
    }

    public function updated(User $user) {
        Cache::forget("user:{$user->id}");
    }

    public function deleted(User $user) {
        $user->posts()->delete();
        $user->profile()->delete();
    }

    public function forceDeleted(User $user) {
        Storage::disk('s3')->deleteDirectory("users/{$user->id}");
    }
}

// Register observer
// app/Providers/EventServiceProvider.php
use App\Models\User;
use App\Observers\UserObserver;

public function boot() {
    User::observe(UserObserver::class);
}
```

### Preventing Events

```php
// Save without firing events
$user->saveQuietly();

// Update without events
User::withoutEvents(function () {
    User::find(1)->update(['name' => 'New Name']);
});

// Disable events for model
public static function boot() {
    parent::boot();
    static::$dispatcher = null;  // Disable all events
}
```

### Queued Observers

```php
class UserObserver implements ShouldQueue {
    public function created(User $user) {
        // This runs asynchronously in queue
        Mail::to($user)->send(new WelcomeEmail);
    }
}
```

### Event Listeners (Alternative)

```php
// app/Providers/EventServiceProvider.php
use App\Models\User;
use App\Listeners\SendWelcomeEmail;

protected $listen = [
    'eloquent.created: ' . User::class => [
        SendWelcomeEmail::class,
    ],
];

// Listener class
class SendWelcomeEmail {
    public function handle($event) {
        Mail::to($event->user)->send(new WelcomeEmail);
    }
}
```

**Follow-up:**
- What's the difference between `creating` and `saving`?
- How do you prevent events from firing?
- Can observers be queued?

**Key Points:**
- Events fire during model lifecycle
- Observers centralize event handling
- Use for: logging, cache invalidation, side effects
- Queue observers for slow operations
- Use `saveQuietly()` to skip events

---

## Question 5: What are Accessors, Mutators, and Casts?

**Answer:**

### Accessors (Getters)

```php
class User extends Model {
    // Old syntax
    public function getFullNameAttribute() {
        return "{$this->first_name} {$this->last_name}";
    }

    // PHP 8+ attribute syntax
    use Illuminate\Database\Eloquent\Casts\Attribute;

    protected function fullName(): Attribute {
        return Attribute::make(
            get: fn () => "{$this->first_name} {$this->last_name}"
        );
    }
}

// Usage
$user = User::find(1);
echo $user->full_name;  // "John Doe"
```

### Mutators (Setters)

```php
class User extends Model {
    // Old syntax
    public function setPasswordAttribute($value) {
        $this->attributes['password'] = bcrypt($value);
    }

    // PHP 8+ attribute syntax
    protected function password(): Attribute {
        return Attribute::make(
            set: fn ($value) => bcrypt($value)
        );
    }
}

// Usage
$user->password = 'secret';  // Automatically hashed
$user->save();
```

### Combined Accessor and Mutator

```php
protected function name(): Attribute {
    return Attribute::make(
        get: fn ($value) => ucfirst($value),
        set: fn ($value) => strtolower($value)
    );
}

// Set: stored as lowercase
$user->name = 'JOHN';  // Stored as 'john'

// Get: returned as ucfirst
echo $user->name;  // 'John'
```

### Casts

```php
class User extends Model {
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
        'options' => 'array',
        'metadata' => 'json',
        'price' => 'decimal:2',
        'birthday' => 'date',
    ];
}

// Usage
$user = User::find(1);

// Datetime cast
$user->email_verified_at->format('Y-m-d');  // Carbon instance

// Boolean cast
if ($user->is_admin) {  // Automatically boolean
    // ...
}

// Array cast
$user->options = ['theme' => 'dark', 'lang' => 'en'];
$user->save();  // Stored as JSON in database

$theme = $user->options['theme'];  // 'dark'
```

### Custom Casts

```php
// Create cast
php artisan make:cast Json

// app/Casts/Json.php
namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class Json implements CastsAttributes {
    public function get($model, string $key, $value, array $attributes) {
        return json_decode($value, true);
    }

    public function set($model, string $key, $value, array $attributes) {
        return json_encode($value);
    }
}

// Use custom cast
protected $casts = [
    'settings' => Json::class,
];
```

### Encrypted Casting

```php
protected $casts = [
    'secret' => 'encrypted',
    'sensitive_data' => 'encrypted:array',
    'private_info' => 'encrypted:json',
];

// Automatically encrypted in DB, decrypted when accessed
$user->secret = 'confidential';
$user->save();  // Encrypted in database
echo $user->secret;  // 'confidential' (decrypted)
```

### Date Casting

```php
protected $casts = [
    'created_at' => 'datetime:Y-m-d H:i:s',
    'updated_at' => 'datetime',
];

// Customize date format
protected function serializeDate(DateTimeInterface $date) {
    return $date->format('Y-m-d H:i:s');
}
```

**Follow-up:**
- What's the difference between accessors and casts?
- How do you create custom casts?
- Can you cast to custom objects?

**Key Points:**
- Accessors: Transform on retrieval
- Mutators: Transform on assignment
- Casts: Automatic type conversion
- Use `Attribute` class (Laravel 9+)
- Built-in casts: array, json, datetime, encrypted

---

## Question 6: How do you avoid N+1 query problems in Laravel?

**Answer:**

The N+1 problem occurs when loading a collection and then accessing a relationship for each item.

### Detection

```php
// Install Debugbar to see queries
composer require barryvdh/laravel-debugbar --dev

// Or enable query log
DB::enableQueryLog();
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->author->name;
}
dd(DB::getQueryLog());  // Shows all queries
```

### Solutions

#### 1. Eager Loading with `with()`

```php
// ❌ N+1 Problem
$posts = Post::all();  // 1 query
foreach ($posts as $post) {
    echo $post->author->name;  // N queries
}
// Total: 1 + N queries

// ✅ Solution: Eager load
$posts = Post::with('author')->get();  // 2 queries total
foreach ($posts as $post) {
    echo $post->author->name;  // No query
}

// Multiple relationships
$posts = Post::with(['author', 'comments', 'tags'])->get();

// Nested relationships
$posts = Post::with('comments.author')->get();

// Select specific columns
$posts = Post::with('author:id,name,email')->get();
```

#### 2. Lazy Eager Loading with `load()`

```php
// Already have collection without relationships
$posts = Post::all();

// Conditionally load relationship
if ($needsAuthors) {
    $posts->load('author');
}

// Load multiple
$posts->load(['author', 'comments']);

// With constraints
$posts->load(['comments' => function ($query) {
    $query->where('approved', true)->orderBy('created_at', 'desc');
}]);
```

#### 3. Eager Load Counts with `withCount()`

```php
// ❌ N+1 for counts
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->comments->count();  // N queries
}

// ✅ Solution: withCount
$posts = Post::withCount('comments')->get();
foreach ($posts as $post) {
    echo $post->comments_count;  // No query, already loaded
}

// Multiple counts
$posts = Post::withCount(['comments', 'likes'])->get();

// Conditional counts
$posts = Post::withCount([
    'comments',
    'comments as approved_comments_count' => function ($query) {
        $query->where('approved', true);
    }
])->get();
```

#### 4. Prevent Lazy Loading

```php
// Throw exception instead of lazy loading (development)
// AppServiceProvider.php
public function boot() {
    Model::preventLazyLoading(! $this->app->isProduction());
}

// Now this throws exception in development:
$posts = Post::all();
$posts[0]->author;  // Exception: Attempted to lazy load [author]

// Forces you to use eager loading:
$posts = Post::with('author')->get();
$posts[0]->author;  // ✅ Works
```

#### 5. Always Load Relationships

```php
// Model automatically eager loads relationships
class Post extends Model {
    protected $with = ['author'];  // Always loaded

    // Disable for specific query
    public static function withoutAuthor() {
        return static::without('author');
    }
}

// Automatically includes author
$posts = Post::all();  // Author already loaded

// Exclude when not needed
$posts = Post::without('author')->get();
```

#### 6. Constrained Eager Loading

```php
// Load only specific related records
$posts = Post::with(['comments' => function ($query) {
    $query->where('approved', true)
          ->orderBy('created_at', 'desc')
          ->limit(5);
}])->get();

// Load based on parent condition
$posts = Post::with(['author' => function ($query) {
    $query->where('active', true);
}])->where('published', true)->get();
```

### Real-World Examples

#### Blog Post Listing

```php
// ❌ N+1 Problem
public function index() {
    $posts = Post::paginate(20);  // 1 query

    return view('posts.index', compact('posts'));
}

// In Blade: @foreach($posts as $post)
// {{ $post->author->name }}         N queries
// {{ $post->comments->count() }}    N queries
// {{ $post->category->name }}       N queries
// Total: 1 + 3N queries for 20 posts = 61 queries!

// ✅ Solution
public function index() {
    $posts = Post::with(['author', 'category'])
        ->withCount('comments')
        ->paginate(20);  // 3 queries total

    return view('posts.index', compact('posts'));
}

// In Blade:
// {{ $post->author->name }}         No query
// {{ $post->comments_count }}       No query
// {{ $post->category->name }}       No query
// Total: 3 queries regardless of pagination size
```

#### API Response

```php
// ❌ N+1 in API
public function index() {
    $users = User::all();

    return response()->json([
        'users' => $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'posts_count' => $user->posts->count(),  // N queries
                'latest_post' => $user->posts()->latest()->first(),  // N queries
            ];
        })
    ]);
}

// ✅ Solution
public function index() {
    $users = User::withCount('posts')
        ->with(['posts' => function ($query) {
            $query->latest()->limit(1);
        }])
        ->get();

    return response()->json([
        'users' => $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'posts_count' => $user->posts_count,  // Already loaded
                'latest_post' => $user->posts->first(),  // Already loaded
            ];
        })
    ]);
}
```

**Follow-up:**
- What's the difference between `with()` and `load()`?
- How do you debug N+1 queries in production?
- Can eager loading hurt performance?

**Key Points:**
- N+1 = 1 query + N additional queries for relationships
- Use `with()` for eager loading
- Use `withCount()` for relationship counts
- Use `load()` for conditional lazy eager loading
- Prevent lazy loading in development with `preventLazyLoading()`
- Always check query counts with Debugbar
- Profile API endpoints for N+1 issues

---

## Notes

Add more questions covering:
- Query builder vs Eloquent
- Chunking and lazy collections
- Mass assignment and $fillable/$guarded
- Eager loading constraints
- Subquery selects
- Raw expressions
- Database transactions
- Model serialization (toArray, toJson)
