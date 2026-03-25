# MySQL Database Questions

## Question 1: Explain MySQL indexing strategies and when to use different index types.

**Answer:**

### Index Types

#### 1. Primary Key (Clustered Index)

```sql
-- Automatically creates clustered index
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    name VARCHAR(255)
);

-- Data is physically ordered by primary key
-- InnoDB stores data with primary key
-- Choose smallest possible primary key for best performance
```

#### 2. Secondary Indexes (Non-Clustered)

```sql
-- Single column index
CREATE INDEX idx_users_email ON users(email);

-- Multiple single-column indexes
CREATE INDEX idx_users_created_at ON users(created_at);
CREATE INDEX idx_users_status ON users(status);

-- When to use:
-- - Frequently used in WHERE clauses
-- - JOIN conditions
-- - ORDER BY columns
```

#### 3. Composite Indexes (Multi-Column)

```sql
-- Order matters! (leftmost prefix rule)
CREATE INDEX idx_users_status_created ON users(status, created_at);

-- Efficient for:
SELECT * FROM users WHERE status = 'active' AND created_at > '2024-01-01';
SELECT * FROM users WHERE status = 'active'; -- Uses index

-- NOT efficient for:
SELECT * FROM users WHERE created_at > '2024-01-01'; -- Doesn't use index

-- General rule: Most selective column first
CREATE INDEX idx_orders_user_status_date ON orders(user_id, status, created_at);
```

#### 4. Unique Indexes

```sql
-- Enforce uniqueness
CREATE UNIQUE INDEX idx_users_email_unique ON users(email);

-- Composite unique
CREATE UNIQUE INDEX idx_user_posts_unique ON posts(user_id, slug);

-- Automatically created for UNIQUE constraints
ALTER TABLE users ADD CONSTRAINT uk_email UNIQUE (email);
```

#### 5. Full-Text Indexes

```sql
-- For text search
CREATE FULLTEXT INDEX idx_posts_content ON posts(title, body);

-- Usage
SELECT * FROM posts
WHERE MATCH(title, body) AGAINST('Laravel tutorial' IN NATURAL LANGUAGE MODE);

-- Boolean mode for advanced searches
SELECT * FROM posts
WHERE MATCH(title, body) AGAINST('+Laravel -Vue' IN BOOLEAN MODE);

-- With relevance score
SELECT *, MATCH(title, body) AGAINST('Laravel') as relevance
FROM posts
WHERE MATCH(title, body) AGAINST('Laravel')
ORDER BY relevance DESC;
```

#### 6. Spatial Indexes

```sql
-- For geographic data
CREATE TABLE locations (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    coordinates POINT NOT NULL SRID 4326,
    SPATIAL INDEX idx_coordinates (coordinates)
);

-- Find nearby locations
SELECT name,
       ST_Distance_Sphere(coordinates, ST_GeomFromText('POINT(-73.9857 40.7484)', 4326)) AS distance
FROM locations
WHERE ST_Distance_Sphere(coordinates, ST_GeomFromText('POINT(-73.9857 40.7484)', 4326)) < 5000
ORDER BY distance;
```

### Index Best Practices

```sql
-- ✅ Good: Index covers query
CREATE INDEX idx_users_lookup ON users(status, created_at, email);

SELECT email FROM users
WHERE status = 'active' AND created_at > '2024-01-01';
-- Index contains all columns needed (covering index)

-- ❌ Bad: Over-indexing
CREATE INDEX idx1 ON users(email);
CREATE INDEX idx2 ON users(email, name);
CREATE INDEX idx3 ON users(email, name, status);
-- idx2 and idx3 make idx1 redundant

-- ✅ Good: Remove redundant indexes
DROP INDEX idx1;
DROP INDEX idx2;
-- Keep only idx3

-- ❌ Bad: Index on low cardinality column
CREATE INDEX idx_users_gender ON users(gender); -- Only 2-3 values
-- Index not useful when column has few distinct values

-- ✅ Good: Composite index with high cardinality first
CREATE INDEX idx_users_email_gender ON users(email, gender);
```

### Analyzing Index Usage

```sql
-- Show indexes on table
SHOW INDEX FROM users;

-- Explain query execution
EXPLAIN SELECT * FROM users WHERE email = 'test@example.com';
EXPLAIN SELECT * FROM users WHERE status = 'active' AND created_at > '2024-01-01';

-- Analyze index usage over time
SELECT
    table_name,
    index_name,
    cardinality,
    seq_in_index
FROM information_schema.statistics
WHERE table_schema = 'your_database';

-- Find unused indexes (MySQL 5.7+)
SELECT
    object_schema,
    object_name,
    index_name
FROM performance_schema.table_io_waits_summary_by_index_usage
WHERE index_name IS NOT NULL
  AND count_star = 0
  AND object_schema = 'your_database'
  AND index_name != 'PRIMARY';
```

### Index Maintenance

```sql
-- Rebuild indexes (defragment)
OPTIMIZE TABLE users;

-- Analyze table statistics
ANALYZE TABLE users;

-- Check table for errors
CHECK TABLE users;

-- Repair corrupted table
REPAIR TABLE users;
```

**Follow-up:**
- What is the leftmost prefix rule for composite indexes?
- When should you avoid indexing?
- What's the difference between clustered and non-clustered indexes?

**Key Points:**
- Primary key = clustered index (data storage order)
- Secondary indexes point to primary key
- Composite indexes: order matters (leftmost prefix)
- Full-text indexes for text search
- Monitor and remove unused indexes
- Index WHERE, JOIN, ORDER BY columns

---

## Question 2: Explain database normalization and when to denormalize.

**Answer:**

### Normalization Forms

#### First Normal Form (1NF)

```sql
-- ❌ Not 1NF: Multiple values in single column
CREATE TABLE orders_bad (
    id INT,
    customer VARCHAR(255),
    products VARCHAR(255) -- 'Product1,Product2,Product3'
);

-- ✅ 1NF: Atomic values
CREATE TABLE orders (
    id INT PRIMARY KEY,
    customer_id INT
);

CREATE TABLE order_items (
    id INT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);
```

#### Second Normal Form (2NF)

```sql
-- ❌ Not 2NF: Partial dependency
CREATE TABLE order_items_bad (
    order_id INT,
    product_id INT,
    product_name VARCHAR(255),    -- Depends only on product_id
    product_price DECIMAL(10,2),  -- Depends only on product_id
    quantity INT,
    PRIMARY KEY (order_id, product_id)
);

-- ✅ 2NF: Remove partial dependencies
CREATE TABLE order_items (
    order_id INT,
    product_id INT,
    quantity INT,
    price_at_time DECIMAL(10,2), -- Price when ordered
    PRIMARY KEY (order_id, product_id)
);

CREATE TABLE products (
    id INT PRIMARY KEY,
    name VARCHAR(255),
    current_price DECIMAL(10,2)
);
```

#### Third Normal Form (3NF)

```sql
-- ❌ Not 3NF: Transitive dependency
CREATE TABLE employees_bad (
    id INT PRIMARY KEY,
    name VARCHAR(255),
    department_id INT,
    department_name VARCHAR(255),    -- Depends on department_id
    department_location VARCHAR(255) -- Depends on department_id
);

-- ✅ 3NF: Remove transitive dependencies
CREATE TABLE employees (
    id INT PRIMARY KEY,
    name VARCHAR(255),
    department_id INT,
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

CREATE TABLE departments (
    id INT PRIMARY KEY,
    name VARCHAR(255),
    location VARCHAR(255)
);
```

### When to Denormalize

#### 1. Read-Heavy Applications

```sql
-- Normalized (3 joins required)
SELECT
    orders.id,
    customers.name,
    products.name,
    order_items.quantity
FROM orders
JOIN customers ON orders.customer_id = customers.id
JOIN order_items ON orders.id = order_items.order_id
JOIN products ON order_items.product_id = products.id;

-- Denormalized (no joins)
CREATE TABLE order_summary (
    order_id INT PRIMARY KEY,
    customer_name VARCHAR(255),     -- Denormalized
    customer_email VARCHAR(255),    -- Denormalized
    total_amount DECIMAL(10,2),     -- Calculated
    item_count INT,                 -- Calculated
    created_at TIMESTAMP
);

-- Update via triggers or application code
```

#### 2. Reporting Tables

```sql
-- Daily aggregations table (denormalized)
CREATE TABLE daily_sales_summary (
    date DATE PRIMARY KEY,
    total_orders INT,
    total_revenue DECIMAL(12,2),
    avg_order_value DECIMAL(10,2),
    unique_customers INT,
    top_product_id INT,
    top_product_name VARCHAR(255) -- Denormalized
);

-- Populate via scheduled job
INSERT INTO daily_sales_summary
SELECT
    DATE(created_at) as date,
    COUNT(*) as total_orders,
    SUM(total) as total_revenue,
    AVG(total) as avg_order_value,
    COUNT(DISTINCT customer_id) as unique_customers,
    (SELECT product_id FROM order_items WHERE DATE(created_at) = DATE(orders.created_at)
     GROUP BY product_id ORDER BY COUNT(*) DESC LIMIT 1) as top_product_id,
    (SELECT products.name FROM products WHERE id = top_product_id) as top_product_name
FROM orders
WHERE DATE(created_at) = CURDATE() - INTERVAL 1 DAY;
```

#### 3. Avoiding N+1 Queries

```sql
-- Denormalize for common access patterns
CREATE TABLE posts (
    id BIGINT PRIMARY KEY,
    title VARCHAR(255),
    body TEXT,
    author_id BIGINT,
    author_name VARCHAR(255),        -- Denormalized
    author_avatar VARCHAR(255),      -- Denormalized
    comments_count INT DEFAULT 0,    -- Denormalized
    likes_count INT DEFAULT 0,       -- Denormalized
    created_at TIMESTAMP
);

-- Update counts via triggers
DELIMITER //
CREATE TRIGGER increment_comment_count
AFTER INSERT ON comments
FOR EACH ROW
BEGIN
    UPDATE posts SET comments_count = comments_count + 1
    WHERE id = NEW.post_id;
END//
DELIMITER ;
```

#### 4. Caching in Database

```sql
-- Materialized view equivalent
CREATE TABLE user_statistics (
    user_id BIGINT PRIMARY KEY,
    posts_count INT,
    followers_count INT,
    following_count INT,
    total_likes_received INT,
    last_post_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Refresh periodically or on-demand
CREATE EVENT refresh_user_statistics
ON SCHEDULE EVERY 1 HOUR
DO
    INSERT INTO user_statistics
    SELECT
        u.id,
        COUNT(DISTINCT p.id),
        COUNT(DISTINCT f.follower_id),
        COUNT(DISTINCT f2.following_id),
        SUM(p.likes_count),
        MAX(p.created_at),
        NOW()
    FROM users u
    LEFT JOIN posts p ON u.id = p.user_id
    LEFT JOIN follows f ON u.id = f.user_id
    LEFT JOIN follows f2 ON u.id = f2.follower_id
    GROUP BY u.id
    ON DUPLICATE KEY UPDATE
        posts_count = VALUES(posts_count),
        followers_count = VALUES(followers_count),
        following_count = VALUES(following_count),
        total_likes_received = VALUES(total_likes_received),
        last_post_at = VALUES(last_post_at),
        updated_at = VALUES(updated_at);
```

### Trade-offs

| Aspect | Normalized | Denormalized |
|--------|-----------|--------------|
| Data Integrity | ✅ High | ⚠️ Requires maintenance |
| Storage | ✅ Efficient | ❌ Redundant data |
| Write Speed | ✅ Fast | ⚠️ Multiple updates |
| Read Speed | ⚠️ Joins required | ✅ Fast |
| Maintenance | ✅ Easy | ❌ Complex |
| Consistency | ✅ Automatic | ⚠️ Manual sync |

**Follow-up:**
- What are the trade-offs of denormalization?
- How do you maintain consistency in denormalized data?
- What is BCNF (Boyce-Codd Normal Form)?

**Key Points:**
- Normalize to 3NF by default
- Denormalize for read-heavy workloads
- Use triggers or app code to maintain consistency
- Reporting tables are good candidates for denormalization
- Cache calculated values (counts, sums)
- Monitor and update denormalized data

---

## Question 3: Explain MySQL transactions and isolation levels.

**Answer:**

### ACID Properties

```sql
-- Atomicity: All or nothing
START TRANSACTION;
UPDATE accounts SET balance = balance - 100 WHERE id = 1;
UPDATE accounts SET balance = balance + 100 WHERE id = 2;
COMMIT; -- Both succeed or both fail

-- Consistency: Valid state to valid state
-- Integrity constraints enforced

-- Isolation: Transactions don't interfere
-- Controlled by isolation levels

-- Durability: Committed data persists
-- Written to disk, survives crashes
```

### Transaction Basics

```sql
-- Explicit transaction
START TRANSACTION;
-- or
BEGIN;

UPDATE users SET credits = credits - 10 WHERE id = 1;
INSERT INTO purchases (user_id, item_id) VALUES (1, 5);

-- Success
COMMIT;

-- Failure
ROLLBACK;

-- Savepoints
START TRANSACTION;
UPDATE accounts SET balance = balance - 100 WHERE id = 1;
SAVEPOINT sp1;
UPDATE accounts SET balance = balance + 50 WHERE id = 2;
SAVEPOINT sp2;
UPDATE accounts SET balance = balance + 50 WHERE id = 3;

-- Partial rollback
ROLLBACK TO sp1; -- Undo updates to accounts 2 and 3
COMMIT;
```

### Isolation Levels

#### 1. READ UNCOMMITTED (Lowest Isolation)

```sql
SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED;
START TRANSACTION;

-- Can read uncommitted changes from other transactions
SELECT balance FROM accounts WHERE id = 1;
-- May see "dirty read" - data from uncommitted transaction

-- Problems:
-- - Dirty reads (reading uncommitted data)
-- - Non-repeatable reads
-- - Phantom reads

COMMIT;
```

#### 2. READ COMMITTED

```sql
SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
START TRANSACTION;

-- Only reads committed data
SELECT balance FROM accounts WHERE id = 1; -- Returns 100

-- Another transaction commits change (100 -> 200)

SELECT balance FROM accounts WHERE id = 1; -- Returns 200

-- Problems:
-- - Non-repeatable reads (same query, different results)
-- - Phantom reads

COMMIT;
```

#### 3. REPEATABLE READ (MySQL Default)

```sql
SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
START TRANSACTION;

SELECT balance FROM accounts WHERE id = 1; -- Returns 100

-- Another transaction commits change (100 -> 200)

SELECT balance FROM accounts WHERE id = 1; -- Still returns 100
-- Consistent snapshot of data

-- Problems:
-- - Phantom reads (new rows appearing)

COMMIT;
```

#### 4. SERIALIZABLE (Highest Isolation)

```sql
SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;
START TRANSACTION;

SELECT * FROM accounts WHERE balance > 1000;
-- Locks rows, prevents other transactions from modifying or inserting

-- Another transaction trying to insert will wait

-- No problems, but:
-- - Slowest performance
-- - Highest lock contention

COMMIT;
```

### Isolation Level Comparison

| Isolation Level | Dirty Read | Non-Repeatable Read | Phantom Read | Performance |
|----------------|------------|---------------------|--------------|-------------|
| READ UNCOMMITTED | ❌ Yes | ❌ Yes | ❌ Yes | ✅ Fastest |
| READ COMMITTED | ✅ No | ❌ Yes | ❌ Yes | ⚠️ Fast |
| REPEATABLE READ | ✅ No | ✅ No | ⚠️ Possible* | ⚠️ Medium |
| SERIALIZABLE | ✅ No | ✅ No | ✅ No | ❌ Slowest |

*MySQL InnoDB prevents phantom reads in REPEATABLE READ

### Practical Examples

#### Bank Transfer

```sql
START TRANSACTION;

-- Deduct from sender
UPDATE accounts SET balance = balance - 100 WHERE id = 1;

-- Check if balance is sufficient
SELECT balance FROM accounts WHERE id = 1;
-- If negative, rollback

IF balance < 0 THEN
    ROLLBACK;
ELSE
    -- Add to receiver
    UPDATE accounts SET balance = balance + 100 WHERE id = 2;

    -- Log transaction
    INSERT INTO transactions (from_account, to_account, amount)
    VALUES (1, 2, 100);

    COMMIT;
END IF;
```

#### Optimistic Locking

```sql
-- Add version column
ALTER TABLE products ADD COLUMN version INT DEFAULT 0;

-- Read with version
SELECT id, name, stock, version FROM products WHERE id = 1;
-- Returns: id=1, stock=10, version=5

-- Update with version check
UPDATE products
SET stock = stock - 1, version = version + 1
WHERE id = 1 AND version = 5;

-- If affected rows = 0, someone else updated first
-- Retry or show error
```

#### Pessimistic Locking

```sql
START TRANSACTION;

-- Lock row for update
SELECT * FROM products WHERE id = 1 FOR UPDATE;
-- Other transactions must wait

UPDATE products SET stock = stock - 1 WHERE id = 1;

COMMIT; -- Releases lock
```

### Deadlock Handling

```sql
-- Transaction 1
START TRANSACTION;
UPDATE accounts SET balance = balance - 100 WHERE id = 1; -- Locks row 1
UPDATE accounts SET balance = balance + 100 WHERE id = 2; -- Waits for row 2

-- Transaction 2 (simultaneously)
START TRANSACTION;
UPDATE accounts SET balance = balance - 50 WHERE id = 2; -- Locks row 2
UPDATE accounts SET balance = balance + 50 WHERE id = 1; -- Waits for row 1

-- DEADLOCK! MySQL detects and rolls back one transaction

-- Prevention:
-- 1. Always access resources in same order
-- 2. Keep transactions short
-- 3. Use appropriate isolation level
-- 4. Retry on deadlock

-- Laravel example
DB::transaction(function () {
    // Transaction code
}, 5); // Retry 5 times on deadlock
```

### Monitoring Transactions

```sql
-- Show current transactions
SELECT * FROM information_schema.innodb_trx;

-- Show locks
SELECT * FROM information_schema.innodb_locks;

-- Show lock waits
SELECT * FROM information_schema.innodb_lock_waits;

-- Kill long-running transaction
KILL <transaction_id>;
```

**Follow-up:**
- What is the difference between optimistic and pessimistic locking?
- How does MySQL detect deadlocks?
- When would you use SERIALIZABLE isolation level?

**Key Points:**
- Default isolation: REPEATABLE READ
- Use transactions for multi-step operations
- FOR UPDATE for pessimistic locking
- Version column for optimistic locking
- Keep transactions short
- Handle deadlocks with retries

---

### Advanced Transaction Patterns

#### PHP/Laravel Transactions

```php
<?php
// Basic transaction
DB::transaction(function () {
    $user->orders()->create($orderData);
    $user->decrement('credits', $credits);
    $this->sendNotification($user);
});

// With isolation level
DB::transaction(function () {
    // Set isolation level per transaction
    DB::statement("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
    
    $account->transfer($amount, $recipient);
}, 5);  // 5 retries on deadlock

// Manual transaction control
try {
    DB::beginTransaction();
    
    $order->create();
    $payment->process();
    
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    throw $e;
}

// Savepoints for partial rollback
DB::transaction(function () {
    try {
        DB::statement("SAVEPOINT step1");
        $this->processStep1();
        
        DB::statement("SAVEPOINT step2");
        $this->processStep2();
        
    } catch (Step2Exception $e) {
        DB::statement("ROLLBACK TO SAVEPOINT step1");
        // Continue with alternative flow
        $this->processAlternative();
    }
});
```

#### Optimistic vs Pessimistic Locking

```php
<?php
// Optimistic Locking (use version/timestamp column)
class Product extends Model
{
    protected $fillable = ['name', 'price', 'version'];
    
    // Check version on update
    public function updatePrice(float $newPrice): bool
    {
        return static::where('id', $this->id)
            ->where('version', $this->version)
            ->update([
                'price' => $newPrice,
                'version' => DB::raw('version + 1')
            ]) > 0;
    }
}

// Usage with retry
$product = Product::find($id);
$attempts = 0;

while ($attempts < 3) {
    if ($product->updatePrice(99.99)) {
        return true;
    }
    $product->refresh();  // Reload fresh data
    $attempts++;
}

throw new ConcurrentModificationException();

// Pessimistic Locking (SELECT FOR UPDATE)
class Order extends Model
{
    public function process(): void
    {
        // Lock rows until transaction completes
        $order = static::lockForUpdate()->find($this->id);
        
        // Or with shared lock for reading
        $order = static::sharedLock()->find($this->id);
        
        // Process...
        $order->status = 'processing';
        $order->save();
    }
}

// Laravel lock on query
$user = DB::table('accounts')
    ->where('id', $accountId)
    ->lockForUpdate()  // SELECT ... FOR UPDATE
    ->first();

$user->balance -= $amount;
DB::table('accounts')
    ->where('id', $accountId)
    ->update(['balance' => $user->balance]);
```

#### Distributed Transactions (2PC - Two Phase Commit)

```php
<?php
// When transactions span multiple databases

// Phase 1: Prepare
try {
    // Prepare local database
    DB::connection('main')->transaction(function () {
        DB::connection('main')->table('orders')->insert($orderData);
    });
    
    // Prepare analytics database  
    DB::connection('analytics')->transaction(function () {
        DB::connection('analytics')->table('order_events')->insert($eventData);
    });
    
    // Phase 2: Commit all
    // (all prepared, now commit)
    
} catch (\Exception $e) {
    // Rollback all on any failure
    // In reality, need saga pattern for distributed systems
    DB::connection('main')->rollBack();
    DB::connection('analytics')->rollBack();
}

// Saga Pattern (for microservices)
class OrderSaga
{
    public function createOrder(array $data): void
    {
        try {
            // Step 1: Create order
            $order = $this->createOrderStep($data);
            
            // Step 2: Reserve inventory
            $this->reserveInventory($order);
            
            // Step 3: Process payment
            $this->processPayment($order);
            
        } catch (PaymentFailedException $e) {
            // Compensate: Refund and release inventory
            $this->compensate($order);
        }
    }
    
    private function compensate(Order $order): void
    {
        // Release inventory
        Inventory::release($order->items);
        
        // Cancel order (already in DB, need to mark as cancelled)
        $order->update(['status' => 'cancelled']);
    }
}
```

#### Transaction Best Practices

```php
<?php
// ✅ Good: Keep transactions short
public function transfer(Account $from, Account $to, float $amount): void
{
    DB::transaction(function () use ($from, $to, $amount) {
        $from->decrement('balance', $amount);
        $to->increment('balance', $amount);
        // No external API calls in transaction!
    });
}

// ❌ Bad: Long-running transactions with external calls
public function badExample(Account $from, Account $to, float $amount): void
{
    DB::transaction(function () use ($from, $to, $amount) {
        $from->decrement('balance', $amount);
        $to->increment('balance', $amount);
        
        // BAD: External API call in transaction!
        $this->paymentGateway->charge($amount);  // Blocks DB connection
        $this->sendEmail();  // Slow operation
    });
}

// ✅ Good: Proper error handling with retry
public function processWithRetry(int $maxRetries = 3): void
{
    $attempt = 0;
    
    while ($attempt < $maxRetries) {
        try {
            DB::transaction(function () {
                $this->processOrder();
            });
            return;
        } catch (\Illuminate\Database\QueryException $e) {
            if ($this->isDeadlock($e) && $attempt < $maxRetries) {
                $attempt++;
                usleep(random_int(10000, 100000));  // Random backoff
                continue;
            }
            throw $e;
        }
    }
}

// ✅ Good: Nested transactions (using savepoints in MySQL)
public function outerOperation(): void
{
    DB::transaction(function () {
        $this->firstOperation();
        
        // Nested transaction uses savepoint
        $this->innerOperation();  // Can independently rollback
        
        $this->thirdOperation();
    });
}

// MySQL automatically creates savepoints for nested transactions
// Laravel's beginTransaction() calls SAVEPOINT when already in transaction
```

#### Monitoring Transactions

```sql
-- Check active transactions
SELECT 
    trx_id,
    trx_state,
    trx_started,
    trx_mysql_thread_id,
    trx_query,
    (NOW() - trx_started) AS duration
FROM information_schema.INNODB_TRX
WHERE trx_state = 'RUNNING'
ORDER BY trx_started;

-- Check locks
SELECT 
    l.lock_id,
    l.lock_mode,
    l.lock_type,
    l.lock_table,
    l.lock_index,
    r.trx_id AS waiting_trx_id,
    r.trx_query AS waiting_query,
    b.trx_id AS blocking_trx_id,
    b.trx_query AS blocking_query
FROM information_schema.INNODB_LOCKS l
JOIN information_schema.INNODB_TRX r ON l.lock_trx_id = r.trx_id
JOIN information_schema.INNODB_TRX b ON l.lock_blocking_trx_id = b.trx_id;

-- Check for long queries
SHOW PROCESSLIST;

-- Transaction isolation level
SELECT @@transaction_isolation;
SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
```

---

## Question 4: How do you optimize slow MySQL queries?

**Answer:**

### Identifying Slow Queries

```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1; -- Queries taking > 1 second
SET GLOBAL slow_query_log_file = '/var/log/mysql/slow-query.log';

-- Check current settings
SHOW VARIABLES LIKE 'slow_query%';
SHOW VARIABLES LIKE 'long_query_time';

-- Analyze slow query log
mysqldumpslow -s t -t 10 /var/log/mysql/slow-query.log
```

### Using EXPLAIN

```sql
-- Analyze query execution plan
EXPLAIN SELECT * FROM users WHERE email = 'test@example.com';

-- Key columns:
-- type: ALL (table scan, bad), index, range, ref, eq_ref, const
-- possible_keys: Indexes that could be used
-- key: Index actually used
-- rows: Estimated rows scanned
-- Extra: Additional information (Using filesort, Using temporary, etc.)

-- Extended information
EXPLAIN FORMAT=JSON SELECT ...;

-- Analyze actual execution
EXPLAIN ANALYZE SELECT ...;
```

### Common Query Problems and Solutions

#### 1. Full Table Scan

```sql
-- ❌ Bad: Full table scan
EXPLAIN SELECT * FROM users WHERE YEAR(created_at) = 2024;
-- type: ALL, rows: 1,000,000

-- ✅ Good: Use index range
EXPLAIN SELECT * FROM users
WHERE created_at >= '2024-01-01' AND created_at < '2025-01-01';
-- type: range, rows: 50,000

-- Create index
CREATE INDEX idx_users_created_at ON users(created_at);
```

#### 2. Function on Indexed Column

```sql
-- ❌ Bad: Function prevents index usage
SELECT * FROM users WHERE LOWER(email) = 'test@example.com';

-- ✅ Good: Store lowercase, or use generated column
ALTER TABLE users ADD COLUMN email_lower VARCHAR(255)
    AS (LOWER(email)) STORED;
CREATE INDEX idx_users_email_lower ON users(email_lower);

SELECT * FROM users WHERE email_lower = 'test@example.com';

-- Or ensure consistent case in application
SELECT * FROM users WHERE email = 'test@example.com';
```

#### 3. Leading Wildcard in LIKE

```sql
-- ❌ Bad: Leading wildcard prevents index usage
SELECT * FROM products WHERE name LIKE '%laptop%';

-- ✅ Good: No leading wildcard
SELECT * FROM products WHERE name LIKE 'laptop%';

-- For full-text search, use FULLTEXT index
CREATE FULLTEXT INDEX idx_products_name ON products(name);
SELECT * FROM products WHERE MATCH(name) AGAINST('laptop');
```

#### 4. OR Conditions

```sql
-- ❌ Bad: OR may not use index efficiently
SELECT * FROM users WHERE email = 'test@test.com' OR username = 'testuser';

-- ✅ Good: Use UNION
SELECT * FROM users WHERE email = 'test@test.com'
UNION
SELECT * FROM users WHERE username = 'testuser';

-- Or ensure composite index exists
CREATE INDEX idx_users_email_username ON users(email, username);
```

#### 5. SELECT *

```sql
-- ❌ Bad: Retrieves unnecessary data
SELECT * FROM users WHERE status = 'active';

-- ✅ Good: Select only needed columns
SELECT id, name, email FROM users WHERE status = 'active';

-- Covering index (includes all columns in SELECT and WHERE)
CREATE INDEX idx_users_status_name_email ON users(status, id, name, email);
```

#### 6. Subqueries

```sql
-- ❌ Bad: Correlated subquery (runs for each row)
SELECT p.*,
    (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
FROM posts p;

-- ✅ Good: JOIN with GROUP BY
SELECT p.*, COUNT(c.id) as comment_count
FROM posts p
LEFT JOIN comments c ON p.id = c.post_id
GROUP BY p.id;

-- Or denormalize
ALTER TABLE posts ADD COLUMN comments_count INT DEFAULT 0;
```

#### 7. N+1 Query Problem

```sql
-- ❌ Bad: N+1 queries in application
-- Query 1: Get all posts
SELECT * FROM posts LIMIT 10;

-- Query 2-11: Get author for each post (10 queries)
SELECT * FROM users WHERE id = 1;
SELECT * FROM users WHERE id = 2;
-- ...

-- ✅ Good: Single query with JOIN
SELECT p.*, u.name as author_name, u.email as author_email
FROM posts p
JOIN users u ON p.user_id = u.id
LIMIT 10;

-- Or use IN clause
SELECT * FROM posts LIMIT 10; -- Get IDs: 1,2,3,4,5
SELECT * FROM users WHERE id IN (1,2,3,4,5);
```

### Query Optimization Techniques

#### 1. Pagination

```sql
-- ❌ Bad: OFFSET with large numbers
SELECT * FROM posts ORDER BY created_at DESC LIMIT 100 OFFSET 10000;
-- Scans and discards 10,000 rows

-- ✅ Good: Keyset pagination
SELECT * FROM posts
WHERE created_at < '2024-01-15 10:00:00'
ORDER BY created_at DESC
LIMIT 100;

-- Or use WHERE id
SELECT * FROM posts
WHERE id < 10000
ORDER BY id DESC
LIMIT 100;
```

#### 2. Counting Rows

```sql
-- ❌ Bad: COUNT(*) on large table
SELECT COUNT(*) FROM users; -- Very slow

-- ✅ Good: Approximate count
SELECT TABLE_ROWS FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'your_database' AND TABLE_NAME = 'users';

-- Or cache the count
-- Update via triggers or scheduled job
```

#### 3. Batch Operations

```sql
-- ❌ Bad: Multiple single inserts
INSERT INTO logs (message) VALUES ('Log 1');
INSERT INTO logs (message) VALUES ('Log 2');
-- ... 1000 times

-- ✅ Good: Batch insert
INSERT INTO logs (message) VALUES
('Log 1'),
('Log 2'),
('Log 3'),
-- ... 1000 rows
('Log 1000');

-- Or use LOAD DATA INFILE for bulk imports
LOAD DATA INFILE '/path/to/file.csv'
INTO TABLE logs
FIELDS TERMINATED BY ','
LINES TERMINATED BY '\n';
```

#### 4. Avoid Filesort and Temporary Tables

```sql
-- Check for "Using filesort" in EXPLAIN
EXPLAIN SELECT * FROM posts ORDER BY views DESC LIMIT 10;

-- Create index on ORDER BY column
CREATE INDEX idx_posts_views ON posts(views);

-- Check for "Using temporary"
EXPLAIN SELECT status, COUNT(*) FROM users GROUP BY status;

-- Create index on GROUP BY column
CREATE INDEX idx_users_status ON users(status);
```

### Query Cache (Deprecated in MySQL 8.0)

```sql
-- MySQL 8.0+: Use application-level caching (Redis)
-- Laravel example
$users = Cache::remember('users.active', 3600, function() {
    return User::where('status', 'active')->get();
});
```

### Monitoring Query Performance

```sql
-- Performance Schema
SELECT * FROM performance_schema.events_statements_summary_by_digest
ORDER BY SUM_TIMER_WAIT DESC
LIMIT 10;

-- Table statistics
SELECT * FROM sys.schema_table_statistics
WHERE table_schema = 'your_database'
ORDER BY total_latency DESC;

-- Slow queries
SELECT * FROM sys.statements_with_full_table_scans
LIMIT 10;
```

**Follow-up:**
- What does "Using filesort" mean in EXPLAIN?
- How do you optimize GROUP BY queries?
- What is a covering index?

**Key Points:**
- Use EXPLAIN to analyze queries
- Avoid functions on indexed columns
- Index WHERE, JOIN, ORDER BY columns
- Use JOINs instead of subqueries
- Batch operations when possible
- Monitor with slow query log
- SELECT only needed columns

---

## Question 5: How do transactions and isolation levels work?

**Answer:**

See [PHP/Core → Question on transactions] for detailed answer with examples.

### Quick Summary

```sql
-- Transaction basics
START TRANSACTION;
UPDATE accounts SET balance = balance - 100 WHERE id = 1;
UPDATE accounts SET balance = balance + 100 WHERE id = 2;
COMMIT;  -- or ROLLBACK;

-- Isolation levels
SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED;  -- Dirty reads
SET TRANSACTION ISOLATION LEVEL READ COMMITTED;    -- Default in some DBs
SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;   -- MySQL default
SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;      -- Strictest

-- Laravel example
DB::transaction(function () {
    User::find(1)->decrement('credits', 10);
    Purchase::create([...]);
}, 5);  // Retry 5 times on deadlock
```

**Key Points:**
- ACID: Atomicity, Consistency, Isolation, Durability
- MySQL default: REPEATABLE READ
- Use FOR UPDATE for pessimistic locking
- Version column for optimistic locking
- Handle deadlocks with retries

---

---

## Question 6: How to guarantee that either both columns have values or both are null?

**Answer:**

This is a common requirement for optional paired fields (e.g., start/end dates, from/to values). There are several approaches:

### 1. CHECK Constraint (MySQL 8.0.16+)

```sql
-- Both columns must have values or both be null
CREATE TABLE reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    guest_name VARCHAR(255),
    check_in DATE,
    check_out DATE,
    CONSTRAINT chk_dates CHECK (
        (check_in IS NULL AND check_out IS NULL) OR
        (check_in IS NOT NULL AND check_out IS NOT NULL)
    )
);

-- Alternative using XOR-like logic
CONSTRAINT chk_dates CHECK (
    (check_in IS NOT NULL) = (check_out IS NOT NULL)
);
```

### 2. Trigger-Based Solution (All MySQL Versions)

```sql
-- Create trigger to validate on insert/update
DELIMITER //
CREATE TRIGGER trg_reservations_check
BEFORE INSERT ON reservations
FOR EACH ROW
BEGIN
    IF (NEW.check_in IS NULL) != (NEW.check_out IS NULL) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Both check_in and check_out must be set or both must be NULL';
    END IF;
END//
DELIMITER ;

-- Same for UPDATE
DELIMITER //
CREATE TRIGGER trg_reservations_update
BEFORE UPDATE ON reservations
FOR EACH ROW
BEGIN
    IF (NEW.check_in IS NULL) != (NEW.check_out IS NULL) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Both check_in and check_out must be set or both must be NULL';
    END IF;
END//
DELIMITER ;
```

### 3. Application-Level Validation (Laravel)

```php
// Laravel model validation
class Reservation extends Model
{
    protected static function booted()
    {
        static::saving(function ($model) {
            $hasCheckIn = $model->check_in !== null;
            $hasCheckOut = $model->check_out !== null;

            if ($hasCheckIn !== $hasCheckOut) {
                throw new \InvalidArgumentException(
                    'Both check_in and check_out must be set or both must be null'
                );
            }
        });
    }
}

// Or using custom validation rule
Validator::extend('both_or_neither', function ($attribute, $value, $parameters) {
    $other = $parameters[0];
    $current = request()->input($attribute);
    $otherValue = request()->input($other);

    return ($current === null) === ($otherValue === null);
});

// In validation
$request->validate([
    'check_in' => 'required_with:check_out|both_or_neither:check_out',
    'check_out' => 'required_with:check_in|both_or_neither:check_in',
]);
```

### 4. Computed Column Approach

```sql
-- Use a computed column to enforce the logic
CREATE TABLE reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    guest_name VARCHAR(255),
    check_in DATE NULL,
    check_out DATE NULL,
    is_draft GENERATED ALWAYS AS (
        CASE WHEN check_in IS NULL THEN 1 ELSE 0 END
    ) STORED,
    CONSTRAINT chk_dates CHECK (
        (check_in IS NULL AND check_out IS NULL) OR
        (check_in IS NOT NULL AND check_out IS NOT NULL)
    )
);
```

### 5. Separate Tables (Normalized Design)

```sql
-- For more complex scenarios, use separate table for optional data
CREATE TABLE reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    guest_name VARCHAR(255)
    -- No date columns here
);

CREATE TABLE reservation_dates (
    reservation_id INT PRIMARY KEY,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id)
);
-- Either a row exists in reservation_dates (both dates present)
-- or it doesn't (both null by absence)
```

### Comparison

| Approach | Pros | Cons |
|----------|------|------|
| CHECK constraint | Declarative, DB-enforced | MySQL 8.0.16+ only, limited support |
| Triggers | Works in all versions | Additional complexity |
| Application | Flexible, easy to test | Not enforced at DB level |
| Computed column | Automatic validation | MySQL 8.0+ only |
| Separate tables | Clean design | More complex queries |

**Follow-up:**
- What about validating this in PHP without a database?
- How does this work with ORM like Laravel Eloquent?
- Can CHECK constraints be tested?

**Key Points:**
- Use CHECK constraints in MySQL 8.0.16+
- Use triggers for older MySQL versions
- Application validation is not enough alone
- Test both valid and invalid scenarios

---

## Question 7: When should you use UNION? What are the valid use cases where there's no alternative?

**Answer:**

UNION is often overused when alternatives exist. However, there are legitimate cases where UNION is necessary or significantly more efficient than running separate queries.

### When UNION is Necessary (No Alternative)

#### 1. Single Result Set Required (API/UI)

```sql
-- Need single sorted list from different sources
-- Backend can't merge two separate API responses
SELECT id, name, 'user' as type FROM users WHERE status = 'active'
UNION
SELECT id, name, 'admin' as type FROM admins WHERE status = 'active'
ORDER BY name;

-- Use case: Admin panel showing all active accounts
-- Cannot make two separate queries and merge in application
-- because frontend expects single sorted list
```

#### 2. Database-Side Filtering/Sorting

```sql
-- Need to filter by column that exists in one table but not another
SELECT email, created_at FROM users WHERE created_at > '2024-01-01'
UNION
SELECT email, NULL as created_at FROM newsletter_subscribers WHERE subscribed = 1;

-- Need database-level deduplication
SELECT DISTINCT email FROM (
    SELECT email FROM users
    UNION
    SELECT email FROM customers
    UNION  
    SELECT email FROM newsletter_subscribers
) as all_emails;

-- Using separate queries requires application-level deduplication
-- which is complex and error-prone
```

#### 3. Pagination Across Multiple Tables

```sql
-- Single paginated feed from multiple sources
(SELECT id, title, 'post' as source, created_at FROM posts WHERE status = 'published')
UNION
(SELECT id, title, 'article' as source, published_at as created_at FROM articles WHERE status = 'published')
ORDER BY created_at DESC
LIMIT 20 OFFSET 100;

-- Alternative: Two queries + merge + paginate in memory
-- Problem: Offset 100 from combined results is incorrect
-- because you need offset 100 from merged sorted set
```

#### 4. Different Tables, Same Schema (Log Aggregation)

```sql
-- Combine same-structure tables (partitioning alternative)
SELECT user_id, action, created_at FROM logs_2024_01
UNION ALL
SELECT user_id, action, created_at FROM logs_2024_02
UNION ALL
SELECT user_id, action, created_at FROM logs_2024_03
WHERE created_at BETWEEN '2024-03-01' AND '2024-03-31';

-- Use case: Query across monthly partitioned tables
-- Single query vs multiple queries + application merge
```

#### 5. Security/Access Control (Row-Level Security)

```sql
-- Centralized query with UNION for different access levels
SELECT id, title, content FROM documents WHERE access_level = 'public'
UNION
SELECT id, title, content FROM documents d
JOIN user_documents ud ON d.id = ud.document_id
WHERE ud.user_id = ? AND access_level = 'private';

-- Cannot split into two queries because:
-- 1. Application needs single result set
-- 2. Different WHERE clauses on same table
-- 3. Performance: single table scan vs two
```

### When NOT to Use UNION (Alternatives Exist)

```sql
-- ❌ Bad: UNION for OR on indexed columns
SELECT * FROM users WHERE email = 'test@test.com'
UNION
SELECT * FROM users WHERE username = 'testuser';

-- ✅ Better: Use OR with composite index
SELECT * FROM users WHERE email = 'test@test.com' OR username = 'testuser';

-- ✅ Or use IN with computed column
SELECT * FROM users WHERE email = 'test@test.com'
UNION ALL
SELECT * FROM users WHERE email != 'test@test.com' AND username = 'testuser';
-- (if index on (email, username))

-- ❌ Bad: UNION instead of JOIN for related data
SELECT * FROM orders WHERE status = 'pending'
UNION
SELECT * FROM order_items WHERE order_id IN (SELECT id FROM orders WHERE status = 'pending');

-- ✅ Better: JOIN
SELECT o.*, oi.* FROM orders o
JOIN order_items oi ON o.id = oi.order_id
WHERE o.status = 'pending';
```

### Performance Considerations

```sql
-- UNION vs UNION ALL
-- UNION: removes duplicates (slower)
-- UNION ALL: keeps duplicates (faster)

-- Use UNION ALL when:
-- - Tables are known to be disjoint (different sources)
-- - No duplicate possible
-- - Performance critical

-- Use UNION when:
-- - Tables might have overlap
-- - Deduplication required

-- Add indexes for UNION optimization
CREATE INDEX idx_users_status_name ON users(status, name);
CREATE INDEX idx_admins_status_name ON admins(status, name);

-- Use EXPLAIN to verify index usage
EXPLAIN SELECT id, name FROM users WHERE status = 'active'
UNION
SELECT id, name FROM admins WHERE status = 'active';
```

### Laravel Examples

```php
// UNION in Laravel
$users = DB::table('users')->select('id', 'name', 'email')
    ->where('status', 'active');

$admins = DB::table('admins')->select('id', 'name', 'email')
    ->where('status', 'active');

$combined = $users->union($admins)->orderBy('name')->get();

// UNION ALL
$combined = $users->unionAll($admins)->get();

// With pagination (complex)
$page = DB::table('logs_2024_01')->select('*')
    ->union(DB::table('logs2024_02')->select('*'))
    ->orderBy('created_at')
    ->offset($offset)
    ->limit($limit)
    ->get();
```

### Summary: When UNION is the Right Choice

| Scenario | Use UNION | Why |
|----------|-----------|-----|
| Single sorted result from multiple tables | ✅ | Can't merge sorted lists in app efficiently |
| Pagination across combined results | ✅ | Need correct offset from total sorted set |
| Different tables, same columns | ✅ | Single query is more efficient |
| Database-level deduplication | ✅ | Complex in application code |
| Complex security filtering | ✅ | Single query, single scan |
| OR on non-indexed columns | ⚠️ | Consider index first |
| Related data from same table | ❌ | Use JOIN instead |

**Key Points:**
- Use UNION when single result set is required
- Use UNION ALL for performance when duplicates not possible
- Consider alternatives: JOIN, OR, subqueries first
- Check EXPLAIN for index usage
- Laravel: use `union()` and `unionAll()` methods

---

## Notes

Add more questions covering:
- Replication (Master-Slave, Master-Master)
- Partitioning strategies
- Stored procedures and triggers
- JSON columns and queries
- Backup and recovery strategies
- MySQL 8.0 features (Window functions, CTEs)
- Database sharding
- Query profiling with pt-query-digest
