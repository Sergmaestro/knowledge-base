# Advanced Database Topics

## Question 1: Explain database sharding strategies and when to use them.

**Answer:**

Sharding is horizontal partitioning where data is distributed across multiple database servers.

### Sharding Strategies

#### 1. Range-Based Sharding

```
Users 1-1M      → Shard 1
Users 1M-2M     → Shard 2
Users 2M-3M     → Shard 3

Pros:
- Simple to implement
- Range queries are efficient

Cons:
- Uneven distribution (hotspots)
- Difficult to rebalance
```

```php
// Laravel implementation
class ShardManager {
    public function getShardForUser(int $userId): string {
        if ($userId <= 1000000) {
            return 'shard_1';
        } elseif ($userId <= 2000000) {
            return 'shard_2';
        } else {
            return 'shard_3';
        }
    }

    public function getUserConnection(int $userId): Connection {
        $shard = $this->getShardForUser($userId);
        return DB::connection($shard);
    }
}

// config/database.php
'connections' => [
    'shard_1' => [
        'driver' => 'mysql',
        'host' => 'shard1.mysql.com',
        // ...
    ],
    'shard_2' => [
        'driver' => 'mysql',
        'host' => 'shard2.mysql.com',
        // ...
    ],
],

// Usage
$shard = (new ShardManager())->getUserConnection($userId);
$user = $shard->table('users')->find($userId);
```

#### 2. Hash-Based Sharding

```
user_id % 3 = 0 → Shard 0
user_id % 3 = 1 → Shard 1
user_id % 3 = 2 → Shard 2

Pros:
- Even distribution
- No hotspots

Cons:
- Difficult to add/remove shards
- Cross-shard queries are complex
```

```php
class HashShardManager {
    private const SHARD_COUNT = 3;

    public function getShardForUser(int $userId): string {
        $shardId = $userId % self::SHARD_COUNT;
        return "shard_{$shardId}";
    }

    public function getShardForKey(string $key): string {
        $hash = crc32($key);
        $shardId = $hash % self::SHARD_COUNT;
        return "shard_{$shardId}";
    }
}
```

#### 3. Directory-Based Sharding

```
Lookup Table:
user_id | shard
--------|-------
1       | shard_1
2       | shard_2
3       | shard_1
...

Pros:
- Flexible (easy to move data)
- Can rebalance

Cons:
- Extra lookup required
- Lookup table is bottleneck
```

```php
class DirectoryShardManager {
    public function getShardForUser(int $userId): string {
        // Lookup in Redis or separate database
        $shard = Cache::remember("user_shard:{$userId}", 3600, function() use ($userId) {
            return DB::table('shard_directory')
                ->where('user_id', $userId)
                ->value('shard');
        });

        return $shard ?? 'shard_default';
    }

    public function moveUserToShard(int $userId, string $targetShard): void {
        DB::transaction(function() use ($userId, $targetShard) {
            // 1. Copy data to new shard
            $user = $this->getUserData($userId);
            DB::connection($targetShard)->table('users')->insert($user);

            // 2. Update directory
            DB::table('shard_directory')
                ->where('user_id', $userId)
                ->update(['shard' => $targetShard]);

            // 3. Delete from old shard
            $oldShard = $this->getShardForUser($userId);
            DB::connection($oldShard)->table('users')->where('id', $userId)->delete();
        });
    }
}
```

#### 4. Geographic Sharding

```
US users      → US Shard
EU users      → EU Shard
APAC users    → APAC Shard

Pros:
- Lower latency
- Data residency compliance

Cons:
- Uneven distribution
- Complex cross-region queries
```

```php
class GeoShardManager {
    private array $regionShards = [
        'US' => 'shard_us',
        'EU' => 'shard_eu',
        'APAC' => 'shard_apac',
    ];

    public function getShardForUser(int $userId): string {
        $user = Cache::remember("user_region:{$userId}", 3600, function() use ($userId) {
            return DB::table('users')->select('region')->find($userId);
        });

        return $this->regionShards[$user->region] ?? 'shard_us';
    }
}
```

### Cross-Shard Queries

```php
class CrossShardQuery {
    public function searchUsers(string $email): array {
        $results = [];

        // Query all shards in parallel
        $promises = [];
        foreach ($this->getAllShards() as $shard) {
            $promises[] = async(function() use ($shard, $email) {
                return DB::connection($shard)
                    ->table('users')
                    ->where('email', $email)
                    ->first();
            });
        }

        // Wait for all queries
        $results = Promise\wait(Promise\all($promises));

        return array_filter($results);
    }

    public function getUsersCount(): int {
        $counts = [];

        foreach ($this->getAllShards() as $shard) {
            $counts[] = DB::connection($shard)
                ->table('users')
                ->count();
        }

        return array_sum($counts);
    }
}
```

### Shard Management

```php
class ShardRebalancer {
    public function addNewShard(string $newShard): void {
        // 1. Add new shard to configuration
        config(["database.connections.{$newShard}" => [
            'driver' => 'mysql',
            'host' => "new-shard.mysql.com",
            // ...
        ]]);

        // 2. Migrate data (consistent hashing can minimize)
        // 3. Update routing logic
    }

    public function rebalance(): void {
        // Move data between shards to balance load
        $stats = $this->getShardStats();

        foreach ($stats as $shard => $stat) {
            if ($stat['load'] > $this->getAverageLoad() * 1.2) {
                // Shard overloaded, move some data
                $this->moveDataFromShard($shard);
            }
        }
    }
}
```

### When to Shard

```
Consider sharding when:
✅ Single database can't handle load (> 1TB)
✅ Write throughput exceeds single server
✅ Geographic distribution needed
✅ Need to isolate tenants (multi-tenant SaaS)

Avoid sharding if:
❌ Can scale vertically
❌ Can use read replicas
❌ Can optimize queries/indexes
❌ Can use caching

Sharding is complex - exhaust other options first!
```

**Follow-up:**
- What is consistent hashing and how does it help with sharding?
- How do you handle transactions across shards?
- What are the challenges of joining data across shards?

**Key Points:**
- Sharding = horizontal partitioning across servers
- Hash sharding for even distribution
- Directory-based for flexibility
- Geographic sharding for compliance/latency
- Cross-shard queries are expensive
- Last resort after other optimizations

---

## Question 2: Explain database partitioning strategies.

**Answer:**

Partitioning divides a large table into smaller pieces within the same database.

### PostgreSQL Partitioning

#### 1. Range Partitioning

```sql
-- Parent table
CREATE TABLE orders (
    id BIGSERIAL,
    user_id BIGINT,
    total DECIMAL(10,2),
    created_at TIMESTAMP NOT NULL
) PARTITION BY RANGE (created_at);

-- Partitions
CREATE TABLE orders_2023_q1 PARTITION OF orders
    FOR VALUES FROM ('2023-01-01') TO ('2023-04-01');

CREATE TABLE orders_2023_q2 PARTITION OF orders
    FOR VALUES FROM ('2023-04-01') TO ('2023-07-01');

CREATE TABLE orders_2023_q3 PARTITION OF orders
    FOR VALUES FROM ('2023-07-01') TO ('2023-10-01');

CREATE TABLE orders_2023_q4 PARTITION OF orders
    FOR VALUES FROM ('2023-10-01') TO ('2024-01-01');

-- Indexes on partitions
CREATE INDEX idx_orders_2023_q1_user ON orders_2023_q1(user_id);
CREATE INDEX idx_orders_2023_q2_user ON orders_2023_q2(user_id);

-- Query automatically routes to correct partition
SELECT * FROM orders WHERE created_at >= '2023-05-01' AND created_at < '2023-06-01';
-- Only scans orders_2023_q2

-- Insert routes to correct partition
INSERT INTO orders (user_id, total, created_at)
VALUES (123, 99.99, '2023-05-15');
-- Goes to orders_2023_q2
```

#### 2. List Partitioning

```sql
-- Partition by country
CREATE TABLE users (
    id BIGSERIAL,
    email VARCHAR(255),
    country VARCHAR(2) NOT NULL
) PARTITION BY LIST (country);

CREATE TABLE users_us PARTITION OF users
    FOR VALUES IN ('US');

CREATE TABLE users_eu PARTITION OF users
    FOR VALUES IN ('UK', 'DE', 'FR', 'ES', 'IT');

CREATE TABLE users_asia PARTITION OF users
    FOR VALUES IN ('JP', 'CN', 'IN', 'SG');

CREATE TABLE users_other PARTITION OF users
    DEFAULT;
```

#### 3. Hash Partitioning

```sql
-- Partition by hash for even distribution
CREATE TABLE events (
    id BIGSERIAL,
    user_id BIGINT,
    event_type VARCHAR(50),
    created_at TIMESTAMP
) PARTITION BY HASH (user_id);

-- Create 4 partitions
CREATE TABLE events_0 PARTITION OF events
    FOR VALUES WITH (MODULUS 4, REMAINDER 0);

CREATE TABLE events_1 PARTITION OF events
    FOR VALUES WITH (MODULUS 4, REMAINDER 1);

CREATE TABLE events_2 PARTITION OF events
    FOR VALUES WITH (MODULUS 4, REMAINDER 2);

CREATE TABLE events_3 PARTITION OF events
    FOR VALUES WITH (MODULUS 4, REMAINDER 3);
```

### MySQL Partitioning

```sql
-- Range partitioning
CREATE TABLE orders (
    id BIGINT AUTO_INCREMENT,
    user_id BIGINT,
    total DECIMAL(10,2),
    created_at TIMESTAMP NOT NULL,
    PRIMARY KEY (id, created_at)
)
PARTITION BY RANGE (YEAR(created_at)) (
    PARTITION p2021 VALUES LESS THAN (2022),
    PARTITION p2022 VALUES LESS THAN (2023),
    PARTITION p2023 VALUES LESS THAN (2024),
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);

-- List partitioning
CREATE TABLE users (
    id BIGINT AUTO_INCREMENT,
    email VARCHAR(255),
    status ENUM('active', 'inactive', 'banned'),
    PRIMARY KEY (id, status)
)
PARTITION BY LIST(status) (
    PARTITION p_active VALUES IN ('active'),
    PARTITION p_inactive VALUES IN ('inactive'),
    PARTITION p_banned VALUES IN ('banned')
);

-- Hash partitioning
CREATE TABLE events (
    id BIGINT AUTO_INCREMENT,
    user_id BIGINT,
    event_type VARCHAR(50),
    PRIMARY KEY (id, user_id)
)
PARTITION BY HASH(user_id)
PARTITIONS 4;

-- Key partitioning (hash on primary key)
CREATE TABLE logs (
    id BIGINT AUTO_INCREMENT,
    message TEXT,
    created_at TIMESTAMP,
    PRIMARY KEY (id, created_at)
)
PARTITION BY KEY()
PARTITIONS 8;
```

### Partition Management

```sql
-- PostgreSQL: Add new partition
CREATE TABLE orders_2024_q1 PARTITION OF orders
    FOR VALUES FROM ('2024-01-01') TO ('2024-04-01');

-- Drop old partition
DROP TABLE orders_2021_q1;

-- Detach partition (keeps data, removes from parent)
ALTER TABLE orders DETACH PARTITION orders_2021_q1;

-- Attach existing table as partition
ALTER TABLE orders ATTACH PARTITION orders_2024_q2
    FOR VALUES FROM ('2024-04-01') TO ('2024-07-01');

-- MySQL: Add partition
ALTER TABLE orders ADD PARTITION (
    PARTITION p2025 VALUES LESS THAN (2026)
);

-- Drop partition
ALTER TABLE orders DROP PARTITION p2021;

-- Reorganize partitions
ALTER TABLE orders REORGANIZE PARTITION p_future INTO (
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

### Automated Partition Management

```sql
-- PostgreSQL: Function to create partitions
CREATE OR REPLACE FUNCTION create_monthly_partitions(
    table_name TEXT,
    start_date DATE,
    end_date DATE
) RETURNS VOID AS $$
DECLARE
    partition_date DATE;
    partition_name TEXT;
BEGIN
    partition_date := DATE_TRUNC('month', start_date);

    WHILE partition_date < end_date LOOP
        partition_name := table_name || '_' || TO_CHAR(partition_date, 'YYYY_MM');

        EXECUTE format(
            'CREATE TABLE IF NOT EXISTS %I PARTITION OF %I
             FOR VALUES FROM (%L) TO (%L)',
            partition_name,
            table_name,
            partition_date,
            partition_date + INTERVAL '1 month'
        );

        partition_date := partition_date + INTERVAL '1 month';
    END LOOP;
END;
$$ LANGUAGE plpgsql;

-- Create partitions for next 12 months
SELECT create_monthly_partitions('orders', NOW()::DATE, NOW()::DATE + INTERVAL '12 months');

-- Scheduled job to create future partitions
CREATE EXTENSION IF NOT EXISTS pg_cron;

SELECT cron.schedule('create-partitions', '0 0 1 * *', -- First day of month
    $$SELECT create_monthly_partitions('orders', NOW()::DATE, NOW()::DATE + INTERVAL '3 months')$$
);
```

### Laravel Integration

```php
// Model with partition awareness
class Order extends Model {
    protected $table = 'orders';

    // Helper to query specific partition
    public static function inPartition(string $partition) {
        return (new static)->setTable($partition);
    }

    // Scope for date range (automatically uses correct partitions)
    public function scopeForDateRange($query, $start, $end) {
        return $query->whereBetween('created_at', [$start, $end]);
    }
}

// Usage
// Query automatically routed to correct partition
$orders = Order::where('created_at', '>=', '2023-05-01')
    ->where('created_at', '<', '2023-06-01')
    ->get();

// Explicitly query partition
$q1Orders = Order::inPartition('orders_2023_q1')->get();
```

### Benefits and Trade-offs

```
Benefits:
✅ Improved query performance (partition pruning)
✅ Faster bulk deletes (drop partition vs DELETE)
✅ Better index performance (smaller indexes)
✅ Parallel query execution
✅ Easy data archival

Trade-offs:
⚠️ Increased complexity
⚠️ Partition key must be in WHERE clause for pruning
⚠️ Some operations slower (e.g., UPDATE changing partition key)
⚠️ More management overhead

When to use:
- Tables > 100GB
- Time-series data
- Clear partitioning key (date, region, tenant)
- Frequent range queries
- Need to archive old data
```

**Follow-up:**
- What is partition pruning?
- How does partitioning differ from sharding?
- What happens when you update a row's partition key?

**Key Points:**
- Partitioning divides table within same database
- Range partitioning for time-series data
- List partitioning for discrete values
- Hash partitioning for even distribution
- Improves query performance via partition pruning
- Easier data archival (drop old partitions)
- PostgreSQL: declarative, MySQL: requires partition key in PK

---

## Question 3: Explain the CAP theorem and its implications.

**Answer:**

CAP theorem states that a distributed system can only guarantee 2 out of 3 properties simultaneously:

### CAP Properties

```
C - Consistency
  All nodes see the same data at the same time
  Every read receives the most recent write

A - Availability
  Every request receives a response (success or failure)
  System remains operational even with node failures

P - Partition Tolerance
  System continues operating despite network partitions
  (Message loss or delays between nodes)

CAP Theorem: Choose 2 out of 3
Reality: Network partitions happen, so P is mandatory
Real choice: CP or AP
```

### System Classifications

#### CP Systems (Consistency + Partition Tolerance)

```
Examples: PostgreSQL (with synchronous replication), MongoDB, HBase, Redis

Trade-off: Sacrifice availability during network partition

Scenario:
┌─────────┐     Network     ┌─────────┐
│ Node 1  │ ────X────       │ Node 2  │
│ Primary │   Partition     │ Replica │
└─────────┘                 └─────────┘

Behavior:
- Nodes can't communicate
- System rejects writes to maintain consistency
- Returns errors until partition heals
- No stale reads guaranteed

Use cases:
- Financial transactions
- Inventory management
- User authentication
- Any scenario where correctness > availability
```

```sql
-- PostgreSQL synchronous replication (CP)
-- postgresql.conf
synchronous_commit = on
synchronous_standby_names = 'standby1'

-- Transaction waits for replica confirmation
BEGIN;
UPDATE accounts SET balance = balance - 100 WHERE id = 1;
COMMIT; -- Waits for standby, or fails if unreachable
```

#### AP Systems (Availability + Partition Tolerance)

```
Examples: Cassandra, DynamoDB, CouchDB, Riak

Trade-off: Sacrifice strong consistency (eventual consistency)

Scenario:
┌─────────┐     Network     ┌─────────┐
│ Node 1  │ ────X────       │ Node 2  │
└─────────┘   Partition     └─────────┘

Behavior:
- Both nodes accept writes independently
- Data diverges during partition
- Eventual consistency when partition heals
- Possible stale reads

Use cases:
- Social media feeds
- Product catalogs
- Analytics
- DNS
- Any scenario where availability > consistency
```

```javascript
// Cassandra (AP system)
// Multi-datacenter replication
CREATE KEYSPACE my_keyspace WITH replication = {
  'class': 'NetworkTopologyStrategy',
  'DC1': 3,
  'DC2': 3
};

// Tunable consistency (per query)
// CL = QUORUM (stronger consistency)
SELECT * FROM users WHERE id = 123;
  CONSISTENCY QUORUM;

// CL = ONE (higher availability, eventual consistency)
SELECT * FROM users WHERE id = 123;
  CONSISTENCY ONE;
```

### Practical Implications

#### Scenario: E-commerce Inventory

```php
// CP Approach (Strong consistency)
class InventoryService {
    public function decrementStock(int $productId, int $quantity): bool {
        return DB::transaction(function() use ($productId, $quantity) {
            $product = Product::lockForUpdate()->find($productId);

            if ($product->stock < $quantity) {
                throw new InsufficientStockException();
            }

            $product->decrement('stock', $quantity);
            return true;
        });
    }
}

// During network partition:
// - Write fails (or waits) if can't reach primary
// - Guarantees no overselling
// - But: Customers see errors/timeouts

// AP Approach (Eventual consistency)
class InventoryService {
    public function decrementStock(int $productId, int $quantity): void {
        // Optimistic update
        Product::decrement('stock', $quantity);

        // Queue reconciliation job
        ReconcileInventory::dispatch($productId);
    }

    public function reconcileInventory(int $productId): void {
        // Compare with authoritative source
        // Resolve conflicts (last-write-wins, merge, etc.)
        // May need to cancel orders if oversold
    }
}

// During network partition:
// - Writes always succeed
// - Possible overselling (resolved later)
// - But: System remains available
```

### Consistency Models

```
Strong Consistency (CP)
- Linearizability
- Sequential consistency
- All reads see latest write

Eventual Consistency (AP)
- Reads may see stale data
- Eventually all replicas converge
- Time window of inconsistency

Tunable Consistency
- Per-operation consistency level
- Read/write quorums
- Example: Cassandra, DynamoDB

Read-your-writes Consistency
- User sees their own writes
- May not see others' recent writes
- Good for user sessions
```

### Choosing the Right System

```php
// Use CP (PostgreSQL, MySQL) when:
✅ Financial transactions
✅ User authentication
✅ Inventory management
✅ Order processing
✅ Medical records

// Use AP (Cassandra, DynamoDB) when:
✅ Social media posts/likes
✅ Analytics/metrics
✅ Product catalogs
✅ Logging
✅ Caching

// Hybrid approach
class OrderService {
    // CP for critical data
    public function placeOrder(Order $order): void {
        DB::transaction(function() use ($order) {
            // PostgreSQL - strong consistency
            $order->save();
            $this->decrementInventory($order->items);
        });

        // AP for non-critical data
        $this->recordAnalytics($order); // Cassandra - eventual consistency
        $this->updateRecommendations($order); // Redis - eventually consistent cache
    }
}
```

### Beyond CAP: PACELC

```
PACELC extends CAP:

If there is a Partition (P), choose between:
  Availability (A) and Consistency (C)
Else (E), even when system is running normally, choose between:
  Latency (L) and Consistency (C)

Examples:
- PA/EL: Cassandra (available during partition, low latency normally)
- PC/EC: PostgreSQL (consistent during partition, consistent normally)
- PA/EC: DynamoDB (available during partition, consistent normally)
```

**Follow-up:**
- What is eventual consistency?
- How do you handle conflicts in AP systems?
- What is the difference between CAP and ACID?

**Key Points:**
- CAP: Consistency, Availability, Partition tolerance
- Pick 2, but partitions happen, so really CP vs AP
- CP: Strong consistency, may sacrifice availability
- AP: Availability, eventual consistency
- Choose based on business requirements
- Can use different systems for different data

---

## Question 4: How do you handle database migration strategies in production?

**Answer:**

### Migration Best Practices

#### 1. Backward Compatible Migrations

```php
// ❌ Bad: Breaking change in one migration
Schema::table('users', function (Blueprint $table) {
    $table->dropColumn('phone');
    $table->string('phone_number')->after('email');
});

// Deployed code will break immediately!

// ✅ Good: Multi-step migration
// Step 1: Add new column (deploy code that uses both)
Schema::table('users', function (Blueprint $table) {
    $table->string('phone_number')->after('email')->nullable();
});

// Step 2: Backfill data
DB::table('users')->whereNotNull('phone')->update([
    'phone_number' => DB::raw('phone')
]);

// Step 3: Deploy code that only uses phone_number

// Step 4: Drop old column (after verification)
Schema::table('users', function (Blueprint $table) {
    $table->dropColumn('phone');
});
```

#### 2. Large Table Migrations

```php
// ❌ Bad: Locks table for long time
Schema::table('users', function (Blueprint $table) {
    $table->string('bio', 500)->change(); // Locks table
});

// ✅ Good: Use background jobs
class AddBioColumnJob implements ShouldQueue {
    public function handle() {
        // PostgreSQL: Add column with default (instant in 11+)
        DB::statement('ALTER TABLE users ADD COLUMN bio VARCHAR(500) DEFAULT NULL');

        // Or process in chunks
        User::chunk(1000, function ($users) {
            foreach ($users as $user) {
                $user->bio = $this->generateBio($user);
                $user->save();
            }
        });
    }
}

// MySQL: Use pt-online-schema-change
// pt-online-schema-change --alter "ADD COLUMN bio VARCHAR(500)" D=mydb,t=users --execute
```

#### 3. Zero-Downtime Index Creation

```sql
-- PostgreSQL: Create index concurrently
CREATE INDEX CONCURRENTLY idx_users_email ON users(email);

-- MySQL: Online DDL (5.6+)
ALTER TABLE users ADD INDEX idx_email (email), ALGORITHM=INPLACE, LOCK=NONE;

-- Or use pt-online-schema-change
pt-online-schema-change --alter "ADD INDEX idx_email (email)" D=mydb,t=users --execute
```

#### 4. Database Versioning

```php
// Track schema versions
CREATE TABLE migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255),
    batch INT,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

// Laravel migrations
php artisan migrate
php artisan migrate:rollback
php artisan migrate:status

// Check before deploy
if (Migration::pending()->exists()) {
    throw new Exception('Pending migrations exist');
}
```

### Blue-Green Deployments with Database

```
┌─────────────────────────────────────┐
│         Load Balancer               │
└─────────────────────────────────────┘
          ↓                  ↓
    ┌─────────┐        ┌─────────┐
    │  Blue   │        │  Green  │
    │ (Old)   │        │  (New)  │
    └─────────┘        └─────────┘
          ↓                  ↓
    ┌──────────────────────────────┐
    │       Database               │
    └──────────────────────────────┘

Process:
1. Deploy green with backward-compatible schema
2. Run migrations on database
3. Both blue and green work with new schema
4. Switch traffic to green
5. Remove blue after verification
```

```php
// Backward-compatible code
class UserService {
    public function getPhone(User $user): ?string {
        // Support both old and new column names
        return $user->phone_number ?? $user->phone;
    }

    public function setPhone(User $user, string $phone): void {
        // Write to both columns during transition
        $user->phone = $phone;
        $user->phone_number = $phone;
        $user->save();
    }
}
```

### Handling Migration Failures

```php
// Transactional migrations (PostgreSQL, SQLite)
Schema::transaction(function() {
    Schema::table('users', function (Blueprint $table) {
        $table->string('new_field');
    });

    // Backfill data
    DB::table('users')->update(['new_field' => 'default']);
});

// If any step fails, entire migration rolls back

// MySQL doesn't support DDL in transactions
// Use application-level safeguards
class SafeMigration {
    public function up() {
        DB::beginTransaction();
        try {
            // Make changes
            $this->addColumn();
            $this->backfillData();
            $this->addIndex();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->revertChanges();
            throw $e;
        }
    }

    private function revertChanges() {
        // Manual cleanup
        Schema::table('users', function ($table) {
            if (Schema::hasColumn('users', 'new_field')) {
                $table->dropColumn('new_field');
            }
        });
    }
}
```

### Testing Migrations

```php
class MigrationTest extends TestCase {
    public function test_migration_up() {
        // Start from previous state
        $this->artisan('migrate:rollback');

        // Run migration
        $this->artisan('migrate');

        // Verify schema
        $this->assertTrue(Schema::hasColumn('users', 'phone_number'));

        // Verify data integrity
        $user = User::factory()->create(['phone' => '1234567890']);
        $this->assertEquals('1234567890', $user->phone_number);
    }

    public function test_migration_rollback() {
        $this->artisan('migrate');
        $this->artisan('migrate:rollback');

        $this->assertFalse(Schema::hasColumn('users', 'phone_number'));
    }

    public function test_migration_with_production_data() {
        // Load production-like data volume
        User::factory()->count(10000)->create();

        $startTime = microtime(true);
        $this->artisan('migrate');
        $duration = microtime(true) - $startTime;

        // Assert migration completes in reasonable time
        $this->assertLessThan(60, $duration, 'Migration took too long');
    }
}
```

### Monitoring Migrations

```php
class MigrationMonitor {
    public function track(Migration $migration): void {
        $startTime = microtime(true);

        try {
            $migration->up();

            $duration = microtime(true) - $startTime;

            Log::info('Migration completed', [
                'migration' => get_class($migration),
                'duration' => $duration
            ]);

            if ($duration > 60) {
                Slack::send("⚠️ Slow migration: " . get_class($migration));
            }
        } catch (\Exception $e) {
            Log::error('Migration failed', [
                'migration' => get_class($migration),
                'error' => $e->getMessage()
            ]);

            Slack::send("🚨 Migration failed: " . get_class($migration));
            throw $e;
        }
    }
}
```

**Follow-up:**
- How do you handle migrations in microservices?
- What is pt-online-schema-change?
- How do you test migrations with production data?

**Key Points:**
- Make migrations backward compatible
- Multi-step migrations for breaking changes
- Use CONCURRENTLY for index creation
- Chunk large data migrations
- Test migrations before production
- Monitor migration duration
- Have rollback plan
- Use pt-online-schema-change for MySQL large tables

---

---

## Question 5: Explain database locking mechanisms (row-level vs table-level) and the use of cursors.

**Answer:**

### Lock Granularity

Databases use locks to manage concurrent access. The granularity determines how much data is locked.

```
Lock Granularity Pyramid:

          ┌─────────┐
          │Database │  ← Coarsest (entire DB locked)
          ├─────────┤
          │  Table  │  ← Table-level (entire table)
          ├─────────┤
          │  Page   │  ← Page-level (data page, ~16KB)
          ├─────────┤
          │  Row    │  ← Finest (single row)
          └─────────┘
```

| Granularity | Concurrency | Overhead | Use Case |
|-------------|-------------|----------|----------|
| Table | Lowest | Lowest | DDL operations (ALTER TABLE), bulk operations |
| Page | Medium | Medium | Some DBMS default (MySQL MyISAM) |
| Row | Highest | Highest | OLTP, concurrent updates to different rows |

### Row-Level Locking

Most granular — allows maximum concurrency. Default in InnoDB (MySQL) and PostgreSQL.

```sql
-- MySQL/PostgreSQL: Row-level locks via FOR UPDATE
START TRANSACTION;

-- Exclusive row lock — other transactions cannot read/write this row
SELECT * FROM products WHERE id = 1 FOR UPDATE;

-- Shared row lock — others can read but not write
SELECT * FROM products WHERE id = 1 FOR SHARE;
-- MySQL: LOCK IN SHARE MODE (deprecated)
-- PostgreSQL: FOR SHARE

UPDATE products SET stock = stock - 1 WHERE id = 1;

COMMIT; -- Lock released
```

**Row lock types:**

| Lock Type | Symbol | Behavior | Who Can Read | Who Can Write |
|-----------|--------|----------|-------------|---------------|
| Shared (S) | Read lock | Multiple transactions can read | ✅ Everyone | ❌ No one |
| Exclusive (X) | Write lock | Only one transaction can modify | ✅ Only if SELECT ... FOR UPDATE | ❌ Only lock holder |
| Intention Shared (IS) | Table-level | Intends to lock rows with S locks | ✅ | ✅ (if not conflicting) |
| Intention Exclusive (IX) | Table-level | Intends to lock rows with X locks | ✅ | ✅ (if not conflicting) |

**Intention locks** — table-level flags that indicate a transaction intends to lock rows. They prevent conflicting DDL operations without scanning every row.

```
Transaction A: LOCK row 1 with X lock
  → Sets IX lock on the table
  → Sets X lock on row 1

Transaction B: ALTER TABLE users ... (needs table-level X lock)
  → Checks for IX lock at table level
  → Waits because IX conflicts with X at table level
  → Doesn't need to scan all rows
```

```sql
-- MySQL: View current row locks
SELECT * FROM performance_schema.data_locks\G

-- PostgreSQL: View current locks
SELECT
    locktype,
    relation::regclass AS table_name,
    mode,
    granted,
    pid
FROM pg_locks
WHERE NOT granted;  -- Waiting locks
```

### Table-Level Locking

Locks the entire table — low concurrency but low overhead.

```sql
-- MySQL: Explicit table lock
LOCK TABLES products WRITE;  -- Exclusive — only this session
-- Other sessions wait for this table

UPDATE products SET stock = 0 WHERE id = 1;
-- Any row, any operation — all wait

UNLOCK TABLES;

-- MySQL: READ lock (shared)
LOCK TABLES products READ;
-- All sessions can read, none can write
UNLOCK TABLES;
```

**When table locks occur implicitly:**

```sql
-- 1. DDL operations (MySQL)
ALTER TABLE products ADD COLUMN discount DECIMAL(5,2);
-- Full table lock during schema change

-- 2. No index on WHERE clause (MySQL InnoDB)
START TRANSACTION;
SELECT * FROM products WHERE description LIKE '%keyword%' FOR UPDATE;
-- No index on `description` → MySQL locks ALL rows → effectively table lock
-- Even rows that don't match are locked (gap lock + row lock)

-- 3. Bulk UPDATE/DELETE
UPDATE products SET status = 'archived' WHERE 1=1;
-- Table-level lock or many row locks depending on DBMS
```

### Lock Escalation

Some databases promote row locks to table locks when too many rows are locked (to reduce overhead).

```
SQL Server lock escalation:
  5,000+ row locks on one table in one transaction
  → Escalates to table lock
  → Frees memory, but blocks everyone

MySQL InnoDB:
  No lock escalation — uses row locks
  But: no index → row locks on all rows → looks like table lock

PostgreSQL:
  No lock escalation — uses row locks
  Each row lock stored in RAM (can hit overflow)
```

```sql
-- PostgreSQL: Prevent row lock overflow
-- Set max locks per transaction
SET max_locks_per_transaction = 64;  -- Default: 64

-- If too many row locks:
-- ERROR: out of shared memory
-- Hint: You might need to increase max_locks_per_transaction
```

### Gap Locks (MySQL InnoDB)

InnoDB locks gaps between index records to prevent phantom reads in REPEATABLE READ.

```
Index:  10  20  30  40  50
        │   │   │   │   │
Gaps:  ─┘   └───┘   └───┘   └──

SELECT * FROM products WHERE price BETWEEN 20 AND 40 FOR UPDATE;
Locks:
  - Row 20 (X lock)
  - Row 30 (X lock)
  - Row 40 (X lock)
  - Gap (20-30) — prevents INSERT with price=25
  - Gap (30-40) — prevents INSERT with price=35
  - Gap (10-20) — prevents INSERT with price=15
  - Gap (40-50) — prevents INSERT with price=45
```

```sql
-- Phantom read prevented by gap locks
-- Transaction A:
START TRANSACTION;
SELECT * FROM products WHERE price BETWEEN 20 AND 40 FOR UPDATE;
-- Returns: 20, 30, 40

-- Transaction B (tries to insert):
INSERT INTO products (name, price) VALUES ('New', 25);
-- BLOCKED — gap lock on (20-30)

-- Transaction A commits → Transaction B proceeds
```

**MySQL: Disable gap locks** (switch to READ COMMITTED):

```sql
SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED;
-- No gap locks, but may get phantom reads
-- Also: statement-based replication may break
```

### Deadlocks in Practice

```sql
-- Classic deadlock with row locks
-- Transaction A:
BEGIN;
UPDATE accounts SET balance = balance - 100 WHERE id = 1;  -- Locks row 1
UPDATE accounts SET balance = balance + 100 WHERE id = 2;  -- Waits for row 2

-- Transaction B (concurrent):
BEGIN;
UPDATE accounts SET balance = balance - 50 WHERE id = 2;    -- Locks row 2
UPDATE accounts SET balance = balance + 50 WHERE id = 1;    -- Waits for row 1

-- Deadlock! DBMS kills one transaction
-- ERROR: Deadlock found when trying to get lock; try restarting transaction
```

**Prevention strategies:**

```php
// 1. Consistent lock order — always lock resources in the same sequence
class TransferService {
    public function transfer(int $fromId, int $toId, float $amount): void
    {
        // Lock in consistent order (by ID, ascending)
        $ids = [$fromId, $toId];
        sort($ids);

        DB::transaction(function () use ($ids, $amount) {
            $from = DB::table('accounts')
                ->where('id', $ids[0])
                ->lockForUpdate()
                ->first();

            $to = DB::table('accounts')
                ->where('id', $ids[1])
                ->lockForUpdate()
                ->first();

            // Perform transfer
            DB::table('accounts')
                ->where('id', $from->id)
                ->decrement('balance', $amount);

            DB::table('accounts')
                ->where('id', $to->id)
                ->increment('balance', $amount);
        });
    }
}

// 2. Keep transactions short
// ❌ Bad: Long transaction with external calls
DB::transaction(function () {
    $order = Order::lockForUpdate()->find(1);
    $this->chargePaymentGateway($order); // HTTP call — takes seconds!
    $order->update(['status' => 'paid']);
});

// ✅ Good: Lock only what you need, release quickly
$order = Order::find(1);
$this->chargePaymentGateway($order); // External call outside transaction

DB::transaction(function () use ($order) {
    $order = Order::lockForUpdate()->find($order->id);
    // Quick DB operation
    $order->update(['status' => 'paid']);
});

// 3. Retry on deadlock (Laravel)
DB::transaction(function () {
    // Your queries
}, 5); // 5 retry attempts
```

### Cursors

Cursors allow row-by-row processing of query results. They're useful for batch operations but should be used carefully.

#### Explicit Cursors

```sql
-- PostgreSQL: Explicit cursor
BEGIN;

DECLARE product_cursor CURSOR FOR
    SELECT id, name, price, stock
    FROM products
    WHERE category_id = 10
    ORDER BY id;

-- Fetch next row
FETCH NEXT FROM product_cursor;
-- Returns: id=101, name='Widget', price=9.99, stock=50

-- Fetch 10 rows
FETCH 10 FROM product_cursor;

-- Fetch all remaining
FETCH ALL FROM product_cursor;

-- Move without fetching
MOVE NEXT FROM product_cursor;
MOVE 5 FROM product_cursor;

-- Close cursor
CLOSE product_cursor;

COMMIT;

-- Scrollable cursor (can move backwards)
DECLARE scroll_cursor SCROLL CURSOR FOR
    SELECT * FROM products ORDER BY id;

FETCH NEXT FROM scroll_cursor;   -- Row 1
FETCH NEXT FROM scroll_cursor;   -- Row 2
FETCH PRIOR FROM scroll_cursor;  -- Back to Row 1
FETCH LAST FROM scroll_cursor;   -- Last row
FETCH FIRST FROM scroll_cursor;  -- First row

-- Cursor with hold (survives transaction)
DECLARE hold_cursor CURSOR WITH HOLD FOR
    SELECT * FROM large_table;

COMMIT; -- Cursor stays open
FETCH FROM hold_cursor; -- Still works
CLOSE hold_cursor;
```

```sql
-- MySQL: Stored procedure with cursor
DELIMITER //

CREATE PROCEDURE process_expired_orders()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_order_id INT;
    DECLARE v_total DECIMAL(10,2);

    -- Declare cursor
    DECLARE order_cursor CURSOR FOR
        SELECT id, total
        FROM orders
        WHERE status = 'pending'
          AND created_at < NOW() - INTERVAL 7 DAY;

    -- Handler for when cursor has no more rows
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN order_cursor;

    read_loop: LOOP
        FETCH order_cursor INTO v_order_id, v_total;

        IF done THEN
            LEAVE read_loop;
        END IF;

        -- Process each row
        UPDATE orders
        SET status = 'cancelled',
            cancelled_at = NOW()
        WHERE id = v_order_id;

        INSERT INTO order_audit (order_id, action, total)
        VALUES (v_order_id, 'auto_cancelled', v_total);
    END LOOP;

    CLOSE order_cursor;
END //

DELIMITER ;

-- Execute
CALL process_expired_orders();
```

#### Cursors in Application Code

```php
// PostgreSQL: Cursor in application code (Laravel)
use Illuminate\Support\Facades\DB;

class OrderProcessor {
    public function processLargeBatch(): void
    {
        // PostgreSQL and MySQL both support streaming/cursors
        // Method 1: Chunk (Laravel built-in — uses ORDER BY + LIMIT/OFFSET)
        Order::where('status', 'pending')
            ->chunk(1000, function ($orders) {
                foreach ($orders as $order) {
                    $this->processOrder($order);
                }
            });

        // Method 2: Cursor (Laravel built-in — uses actual cursor when available)
        foreach (Order::where('status', 'pending')->cursor() as $order) {
            $this->processOrder($order);
        }

        // Method 3: Lazy collection (memory efficient)
        Order::where('status', 'pending')
            ->lazy(500)
            ->each(function ($order) {
                $this->processOrder($order);
            });
    }

    private function processOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->update(['status' => 'processing']);
            // Process...
        });
    }
}

// Raw PostgreSQL cursor in PHP
class PgCursorProcessor {
    private PDO $pdo;

    public function processBatch(int $batchSize = 1000): void
    {
        $this->pdo->beginTransaction();

        // Declare cursor
        $this->pdo->exec('DECLARE batch_cursor CURSOR FOR
            SELECT id, data FROM large_table WHERE processed = false');

        while (true) {
            $stmt = $this->pdo->prepare('FETCH :limit FROM batch_cursor');
            $stmt->bindValue(':limit', $batchSize, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                break;
            }

            foreach ($rows as $row) {
                $this->processRow($row);
            }
        }

        $this->pdo->exec('CLOSE batch_cursor');
        $this->pdo->commit();
    }
}
```

#### When to Use Cursors

```
✅ Good cursor use cases:
  - Processing large datasets row-by-row (millions of rows)
  - Batch jobs that transform data
  - Generating reports with complex per-row logic
  - Migration scripts with per-row transformations
  - Memory-efficient pagination (instead of loading all into memory)

❌ Bad cursor use cases:
  - Simple aggregations (use SQL)
  - Set-based operations that can be done in one UPDATE
  - Real-time user-facing queries
  - When a single SQL statement can do the job

Performance comparison:
  Set-based SQL:   UPDATE orders SET status = 'cancelled' WHERE ...  ✓
  Cursor-based:    Process each row individually                     ✗ (10-100x slower)
```

```sql
-- ❌ Bad: Cursor when set-based UPDATE works
DECLARE bad_cursor CURSOR FOR
    SELECT id FROM orders WHERE status = 'pending' AND created_at < '2024-01-01';

-- This entire loop can be replaced with a single UPDATE
FOR rec IN bad_cursor LOOP
    UPDATE orders SET status = 'archived' WHERE id = rec.id;
END LOOP;

-- ✅ Good: Single SQL statement
UPDATE orders SET status = 'archived'
WHERE status = 'pending' AND created_at < '2024-01-01';
```

#### Cursor vs. Keyset Pagination

```sql
-- Cursor-based pagination (database cursor)
BEGIN;
DECLARE page_cursor CURSOR FOR
    SELECT id, title, created_at
    FROM posts
    ORDER BY created_at DESC, id DESC;
FETCH 20 FROM page_cursor;  -- Page 1
FETCH 20 FROM page_cursor;  -- Page 2
-- ...
CLOSE page_cursor;
COMMIT;

-- Keyset pagination (application-level cursor — more scalable)
SELECT id, title, created_at
FROM posts
WHERE (created_at, id) < ('2024-05-01 10:00:00', 12345)  -- Last seen values
ORDER BY created_at DESC, id DESC
LIMIT 20;

-- Comparison:
-- SQL cursor: Server-side state, can timeout, holds locks
-- Keyset pagination: Stateless, works across requests, no locks held
```

### Lock Monitoring

```sql
-- MySQL: Current locks
SELECT
    OBJECT_SCHEMA,
    OBJECT_NAME,
    LOCK_TYPE,       -- RECORD, TABLE, etc.
    LOCK_MODE,       -- X, S, IX, IS, etc.
    LOCK_STATUS,     -- GRANTED, WAITING
    ENGINE_TRANSACTION_ID
FROM performance_schema.data_locks;

-- MySQL: Blocked transactions
SELECT
    r.trx_id AS waiting_trx_id,
    r.trx_mysql_thread_id AS waiting_thread,
    b.trx_id AS blocking_trx_id,
    b.trx_mysql_thread_id AS blocking_thread,
    b.trx_query AS blocking_query
FROM information_schema.INNODB_LOCK_WAITS w
JOIN information_schema.INNODB_TRX r ON w.requesting_trx_id = r.trx_id
JOIN information_schema.INNODB_TRX b ON w.blocking_trx_id = b.trx_id;

-- PostgreSQL: Current locks
SELECT
    blocked.pid AS blocked_pid,
    blocked.query AS blocked_query,
    blocking.pid AS blocking_pid,
    blocking.query AS blocking_query
FROM pg_catalog.pg_locks blocked
JOIN pg_catalog.pg_locks blocking ON blocked.pid != blocking.pid
WHERE NOT blocked.granted;

-- PostgreSQL: Blocked query timeout
SET lock_timeout = '5s';  -- Wait max 5 seconds for a lock
-- If lock not acquired in 5s → ERROR: canceling statement due to lock timeout
```

**Follow-up:**
- How does MVCC (Multi-Version Concurrency Control) reduce the need for locks?
- What is the difference between optimistic and pessimistic locking?
- How do gap locks in MySQL differ from PostgreSQL's approach to preventing phantom reads?
- When would you choose table-level locking over row-level locking?
- What are the memory implications of thousands of row-level locks?

**Key Points:**
- Row-level: highest concurrency, most overhead (default in InnoDB, PostgreSQL)
- Table-level: lowest concurrency, least overhead (DDL, bulk operations)
- Intention locks prevent conflicts between row and table locks without scanning
- No index on WHERE → MySQL locks all rows (effectively table lock)
- Gap locks prevent phantom reads but reduce concurrency
- Cursors enable row-by-row processing but are 10-100x slower than set-based SQL
- Keyset pagination is usually better than server-side cursors for web apps
- Always lock resources in consistent order to avoid deadlocks
- Monitor and set timeouts for lock contention

---

## Notes

Add more questions covering:
- Distributed transactions (2PC, Saga pattern)
- Database connection pooling
- Query optimization tools (EXPLAIN, pg_stat_statements)
- Database monitoring and alerting
- Data archival strategies
- Multi-tenancy patterns
- Database security (encryption, access control)
- Compliance (GDPR, data residency)
