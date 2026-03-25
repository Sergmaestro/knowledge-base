# Laravel Testing

## Question 1: Explain different types of tests in Laravel.

**Answer:**

Laravel supports multiple testing approaches for different scenarios.

### Unit Tests

Test individual classes/methods in isolation.

```php
// tests/Unit/UserTest.php
namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;

class UserTest extends TestCase {
    public function test_user_full_name() {
        $user = new User([
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);

        $this->assertEquals('John Doe', $user->full_name);
    }

    public function test_user_is_admin() {
        $user = new User(['role' => 'admin']);
        $this->assertTrue($user->isAdmin());

        $user = new User(['role' => 'user']);
        $this->assertFalse($user->isAdmin());
    }
}
```

### Feature Tests

Test HTTP requests, responses, and application flow.

```php
// tests/Feature/UserRegistrationTest.php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserRegistrationTest extends TestCase {
    use RefreshDatabase;

    public function test_user_can_register() {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password'
        ]);

        $response->assertRedirect('/home');
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com'
        ]);
    }

    public function test_registration_requires_email() {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'password' => 'password'
        ]);

        $response->assertSessionHasErrors('email');
    }
}
```

### Browser Tests (Dusk)

Test JavaScript behavior and browser interactions.

```php
// tests/Browser/LoginTest.php
namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase {
    public function test_user_can_login() {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'user@example.com')
                ->type('password', 'password')
                ->press('Login')
                ->assertPathIs('/dashboard')
                ->assertSee('Welcome back!');
        });
    }
}
```

**Follow-up:**
- When should you use unit vs feature tests?
- What is Dusk used for?
- How do you test APIs?

**Key Points:**
- Unit: Test individual classes/methods
- Feature: Test HTTP requests and responses
- Browser (Dusk): Test JavaScript and UI
- Use `RefreshDatabase` to reset DB between tests

---

## Question 2: How do you use factories and seeders for testing?

**Answer:**

### Model Factories

```php
// database/factories/UserFactory.php
namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory {
    protected $model = User::class;

    public function definition() {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'remember_token' => Str::random(10),
        ];
    }

    // Factory states
    public function unverified() {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }

    public function admin() {
        return $this->state([
            'role' => 'admin',
        ]);
    }
}
```

### Using Factories in Tests

```php
use App\Models\User;
use App\Models\Post;

// Create one user
$user = User::factory()->create();

// Create multiple users
$users = User::factory()->count(5)->create();

// With specific attributes
$user = User::factory()->create([
    'email' => 'specific@example.com'
]);

// Use states
$admin = User::factory()->admin()->create();
$unverified = User::factory()->unverified()->create();

// Chain states
$user = User::factory()->admin()->unverified()->create();

// Make without saving to DB
$user = User::factory()->make();

// Create with relationships
$user = User::factory()
    ->has(Post::factory()->count(3))
    ->create();

// Or using magic methods
$user = User::factory()
    ->hasPosts(3)
    ->create();

// Inverse relationships
$post = Post::factory()
    ->for(User::factory())
    ->create();

// Many-to-many
$user = User::factory()
    ->hasAttached(Role::factory()->count(2))
    ->create();
```

### Factory Callbacks

```php
class UserFactory extends Factory {
    public function configure() {
        return $this->afterMaking(function (User $user) {
            // After model is made but not saved
        })->afterCreating(function (User $user) {
            // After model is created and saved
            $user->profile()->create([
                'bio' => fake()->paragraph()
            ]);
        });
    }
}
```

### Database Seeders

```php
// database/seeders/DatabaseSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Post;

class DatabaseSeeder extends Seeder {
    public function run() {
        // Create admin
        User::factory()->admin()->create([
            'email' => 'admin@example.com'
        ]);

        // Create regular users with posts
        User::factory()
            ->count(10)
            ->has(Post::factory()->count(5))
            ->create();

        // Call other seeders
        $this->call([
            RoleSeeder::class,
            CategorySeeder::class,
        ]);
    }
}

// Run seeders
php artisan db:seed
php artisan db:seed --class=UserSeeder

// In tests
public function test_example() {
    $this->seed();  // Run DatabaseSeeder
    $this->seed(UserSeeder::class);  // Specific seeder
}
```

**Follow-up:**
- How do you create factories for relationships?
- What's the difference between `make()` and `create()`?
- How do you use factory states?

**Key Points:**
- Factories generate test data
- `create()` saves to DB, `make()` doesn't
- States define variations (admin, unverified, etc.)
- Chain methods for relationships
- Seeders populate database for development

---

## Question 3: How do you mock dependencies and external services?

**Answer:**

### Mocking with Mockery

```php
use Mockery;
use App\Services\PaymentGateway;

public function test_order_processes_payment() {
    // Create mock
    $gateway = Mockery::mock(PaymentGateway::class);

    // Set expectations
    $gateway->shouldReceive('charge')
        ->once()
        ->with(100)
        ->andReturn(true);

    // Bind mock to container
    $this->app->instance(PaymentGateway::class, $gateway);

    // Test
    $response = $this->post('/orders', ['amount' => 100]);

    $response->assertSuccessful();
}
```

### Mocking Facades

```php
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderShipped;

public function test_order_sends_email() {
    Mail::fake();

    // Perform order...
    $order = $this->createOrder();

    // Assert email was sent
    Mail::assertSent(OrderShipped::class, function ($mail) use ($order) {
        return $mail->order->id === $order->id;
    });

    // Assert sent to specific user
    Mail::assertSent(OrderShipped::class, function ($mail) {
        return $mail->hasTo('customer@example.com');
    });

    // Assert not sent
    Mail::assertNotSent(AnotherMail::class);

    // Assert count
    Mail::assertSentTimes(OrderShipped::class, 1);
}
```

### Faking Common Services

```php
// Queue
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessOrder;

Queue::fake();
Queue::assertPushed(ProcessOrder::class);
Queue::assertPushed(ProcessOrder::class, 3);  // Times

// Event
use Illuminate\Support\Facades\Event;
use App\Events\OrderShipped;

Event::fake();
Event::assertDispatched(OrderShipped::class);

// Storage
use Illuminate\Support\Facades\Storage;

Storage::fake('s3');
Storage::disk('s3')->put('test.txt', 'content');
Storage::disk('s3')->assertExists('test.txt');
Storage::disk('s3')->assertMissing('other.txt');

// Notification
use Illuminate\Support\Facades\Notification;
use App\Notifications\OrderShipped;

Notification::fake();
Notification::assertSentTo($user, OrderShipped::class);

// Cache
use Illuminate\Support\Facades\Cache;

Cache::shouldReceive('get')
    ->with('key')
    ->andReturn('value');
```

### Partial Mocks

```php
// Mock only specific methods
$mock = Mockery::mock(UserService::class)->makePartial();

$mock->shouldReceive('sendEmail')
    ->once()
    ->andReturn(true);

// Other methods use real implementation
$mock->createUser($data);  // Real method
```

### HTTP Fake

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    'api.example.com/*' => Http::response(['status' => 'success'], 200),
    'github.com/*' => Http::response(['error' => 'Not found'], 404),
]);

// Make requests
$response = Http::get('https://api.example.com/users');

// Assert
Http::assertSent(function ($request) {
    return $request->url() === 'https://api.example.com/users' &&
           $request->method() === 'GET';
});
```

**Follow-up:**
- When should you use mocks vs fakes?
- How do you test external API calls?
- What's the difference between mock and spy?

**Key Points:**
- Mock dependencies with Mockery
- Fake facades: Mail, Queue, Event, Storage
- `::fake()` prevents actual execution
- Assert with `assertSent()`, `assertPushed()`, etc.
- Use `Http::fake()` for external APIs

---

## Question 4: What are Laravel's database testing features?

**Answer:**

### RefreshDatabase

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase {
    use RefreshDatabase;  // Migrate fresh DB before each test

    public function test_user_creation() {
        $user = User::factory()->create();
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
```

### DatabaseTransactions

```php
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UserTest extends TestCase {
    use DatabaseTransactions;  // Wrap test in transaction, rollback after

    public function test_user_creation() {
        User::create(['name' => 'John']);
        // Changes rolled back after test
    }
}
```

### Database Assertions

```php
// Assert record exists
$this->assertDatabaseHas('users', [
    'email' => 'user@example.com',
    'active' => true
]);

// Assert record missing
$this->assertDatabaseMissing('users', [
    'email' => 'deleted@example.com'
]);

// Assert count
$this->assertDatabaseCount('users', 5);

// Soft deletes
$this->assertSoftDeleted($user);
$this->assertNotSoftDeleted($user);

// Model exists
$this->assertModelExists($user);
$this->assertModelMissing($user);
```

### Seeding in Tests

```php
public function test_with_seeded_data() {
    $this->seed();  // Run DatabaseSeeder

    $this->assertDatabaseCount('users', 10);
}

public function test_with_specific_seeder() {
    $this->seed(AdminSeeder::class);

    $this->assertDatabaseHas('users', [
        'role' => 'admin'
    ]);
}
```

### Testing Relationships

```php
public function test_user_has_posts() {
    $user = User::factory()
        ->has(Post::factory()->count(3))
        ->create();

    $this->assertCount(3, $user->posts);
    $this->assertInstanceOf(Post::class, $user->posts->first());
}

public function test_post_belongs_to_user() {
    $post = Post::factory()->create();

    $this->assertInstanceOf(User::class, $post->user);
}
```

**Follow-up:**
- What's the difference between RefreshDatabase and DatabaseTransactions?
- How do you test database transactions?
- When should you seed data in tests?

**Key Points:**
- `RefreshDatabase`: Fresh migration each test
- `DatabaseTransactions`: Rollback after test
- Assert with `assertDatabaseHas()`, `assertDatabaseCount()`
- Use factories for test data
- Seed complex scenarios with seeders

---

## Question 5: How do you test authentication and authorization?

**Answer:**

### Testing Authentication

```php
use App\Models\User;

public function test_authenticated_user_can_access_dashboard() {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertSee('Welcome, ' . $user->name);
}

public function test_guest_cannot_access_dashboard() {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
}

public function test_user_can_login() {
    $user = User::factory()->create([
        'password' => bcrypt($password = 'password')
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => $password
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/home');
}

public function test_user_can_logout() {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
}
```

### Testing Authorization

```php
use App\Models\Post;

public function test_user_can_update_own_post() {
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->put("/posts/{$post->id}", [
        'title' => 'Updated Title'
    ]);

    $response->assertSuccessful();
    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'title' => 'Updated Title'
    ]);
}

public function test_user_cannot_update_others_post() {
    $user = User::factory()->create();
    $otherPost = Post::factory()->create();

    $response = $this->actingAs($user)->put("/posts/{$otherPost->id}", [
        'title' => 'Updated Title'
    ]);

    $response->assertForbidden();
}

public function test_admin_can_delete_any_post() {
    $admin = User::factory()->admin()->create();
    $post = Post::factory()->create();

    $response = $this->actingAs($admin)->delete("/posts/{$post->id}");

    $response->assertSuccessful();
    $this->assertModelMissing($post);
}
```

### Testing Policies

```php
use App\Models\Post;
use App\Policies\PostPolicy;

public function test_user_can_update_own_post_policy() {
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);

    $this->assertTrue($user->can('update', $post));
}

public function test_user_cannot_delete_others_post_policy() {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->assertFalse($user->can('delete', $post));
}
```

### Testing Gates

```php
use Illuminate\Support\Facades\Gate;

public function test_admin_gate() {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->assertTrue(Gate::forUser($admin)->allows('admin-only'));
    $this->assertFalse(Gate::forUser($user)->allows('admin-only'));
}
```

### API Authentication

```php
use Laravel\Sanctum\Sanctum;

public function test_authenticated_user_can_access_api() {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/user');

    $response->assertSuccessful();
    $response->assertJson(['id' => $user->id]);
}

public function test_unauthenticated_request_returns_401() {
    $response = $this->getJson('/api/user');

    $response->assertUnauthorized();
}
```

**Follow-up:**
- How do you test middleware?
- What's the difference between authentication and authorization?
- How do you test API token authentication?

**Key Points:**
- Use `actingAs()` to authenticate user
- `assertAuthenticated()` / `assertGuest()` check auth state
- Test both positive and negative cases
- Test policies with `$user->can()`
- Use `Sanctum::actingAs()` for API tests

---

## Notes

Add more questions covering:
- Test-driven development (TDD) workflow
- Code coverage
- Testing console commands
- Testing validation
- Testing file uploads
- Pest PHP (alternative testing framework)
- Continuous Integration setup
