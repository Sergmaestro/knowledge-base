# PostgreSQL Database Questions

## Question 1: What are PostgreSQL-specific features that differentiate it from MySQL?

**Answer:**

### 1. Advanced Data Types

```sql
-- JSON/JSONB (binary JSON)
CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255),
    attributes JSONB
);

INSERT INTO products (name, attributes) VALUES
('Laptop', '{"brand": "Dell", "ram": "16GB", "storage": "512GB"}');

-- Query JSON
SELECT * FROM products WHERE attributes->>'brand' = 'Dell';
SELECT * FROM products WHERE attributes @> '{"ram": "16GB"}';

-- Index JSONB
CREATE INDEX idx_products_attributes ON products USING GIN (attributes);

-- Array types
CREATE TABLE posts (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255),
    tags TEXT[]
);

INSERT INTO posts (title, tags) VALUES
('PostgreSQL Tutorial', ARRAY['database', 'postgresql', 'sql']);

-- Query arrays
SELECT * FROM posts WHERE 'postgresql' = ANY(tags);
SELECT * FROM posts WHERE tags @> ARRAY['database'];

-- Range types
CREATE TABLE events (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255),
    duration TSRANGE -- timestamp range
);

INSERT INTO events (name, duration) VALUES
('Conference', '[2024-01-15 09:00, 2024-01-15 17:00)');

-- Query ranges
SELECT * FROM events WHERE duration @> '2024-01-15 12:00'::timestamp;
SELECT * FROM events WHERE duration && '[2024-01-15 14:00, 2024-01-15 16:00)'::tsrange;

-- UUID
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email VARCHAR(255)
);

-- Enum types
CREATE TYPE user_status AS ENUM ('active', 'inactive', 'banned');

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    status user_status DEFAULT 'active'
);
```

### 2. ACID Compliance & MVCC

```sql
-- PostgreSQL uses MVCC (Multi-Version Concurrency Control)
-- Readers never block writers, writers never block readers

-- Transaction isolation
BEGIN;
SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;

UPDATE accounts SET balance = balance - 100 WHERE id = 1;
UPDATE accounts SET balance = balance + 100 WHERE id = 2;

COMMIT;

-- True serializable isolation (not just snapshot isolation)
-- Prevents write skew anomalies
```

### 3. Full-Text Search (Built-in)

```sql
-- Add tsvector column
ALTER TABLE posts ADD COLUMN search_vector tsvector;

-- Populate search vector
UPDATE posts SET search_vector =
    to_tsvector('english', title || ' ' || body);

-- Create GIN index
CREATE INDEX idx_posts_search ON posts USING GIN (search_vector);

-- Search
SELECT * FROM posts
WHERE search_vector @@ to_tsquery('english', 'postgresql & tutorial');

-- With ranking
SELECT title, ts_rank(search_vector, query) AS rank
FROM posts, to_tsquery('english', 'postgresql & tutorial') query
WHERE search_vector @@ query
ORDER BY rank DESC;

-- Automatic update with trigger
CREATE TRIGGER posts_search_update
BEFORE INSERT OR UPDATE ON posts
FOR EACH ROW EXECUTE FUNCTION
    tsvector_update_trigger(search_vector, 'pg_catalog.english', title, body);
```

### 4. Window Functions (Advanced)

```sql
-- Row number
SELECT
    name,
    salary,
    ROW_NUMBER() OVER (ORDER BY salary DESC) as rank
FROM employees;

-- Partition by department
SELECT
    name,
    department,
    salary,
    RANK() OVER (PARTITION BY department ORDER BY salary DESC) as dept_rank,
    AVG(salary) OVER (PARTITION BY department) as dept_avg
FROM employees;

-- Running totals
SELECT
    date,
    amount,
    SUM(amount) OVER (ORDER BY date) as running_total
FROM transactions;

-- Moving average
SELECT
    date,
    revenue,
    AVG(revenue) OVER (
        ORDER BY date
        ROWS BETWEEN 6 PRECEDING AND CURRENT ROW
    ) as moving_avg_7days
FROM daily_revenue;

-- Lag/Lead
SELECT
    date,
    revenue,
    LAG(revenue) OVER (ORDER BY date) as prev_day,
    revenue - LAG(revenue) OVER (ORDER BY date) as diff
FROM daily_revenue;
```

### 5. Common Table Expressions (CTEs) and Recursive Queries

```sql
-- Basic CTE
WITH active_users AS (
    SELECT * FROM users WHERE status = 'active'
),
user_orders AS (
    SELECT user_id, COUNT(*) as order_count
    FROM orders
    GROUP BY user_id
)
SELECT u.name, uo.order_count
FROM active_users u
LEFT JOIN user_orders uo ON u.id = uo.user_id;

-- Recursive CTE (organizational hierarchy)
WITH RECURSIVE employee_hierarchy AS (
    -- Anchor: Top-level employees
    SELECT id, name, manager_id, 1 as level
    FROM employees
    WHERE manager_id IS NULL

    UNION ALL

    -- Recursive: Subordinates
    SELECT e.id, e.name, e.manager_id, eh.level + 1
    FROM employees e
    JOIN employee_hierarchy eh ON e.manager_id = eh.id
)
SELECT * FROM employee_hierarchy ORDER BY level, name;

-- Recursive CTE (category tree)
WITH RECURSIVE category_tree AS (
    SELECT id, name, parent_id, name as path
    FROM categories
    WHERE parent_id IS NULL

    UNION ALL

    SELECT c.id, c.name, c.parent_id, ct.path || ' > ' || c.name
    FROM categories c
    JOIN category_tree ct ON c.parent_id = ct.id
)
SELECT * FROM category_tree;
```

### 6. Extensibility

```sql
-- Custom functions
CREATE OR REPLACE FUNCTION calculate_discount(
    price NUMERIC,
    discount_percent NUMERIC
) RETURNS NUMERIC AS $$
BEGIN
    RETURN price * (1 - discount_percent / 100);
END;
$$ LANGUAGE plpgsql;

SELECT calculate_discount(100, 10); -- Returns 90

-- Custom aggregates
CREATE AGGREGATE array_accum (anyelement) (
    sfunc = array_append,
    stype = anyarray,
    initcond = '{}'
);

-- Extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";
CREATE EXTENSION IF NOT EXISTS "hstore"; -- Key-value store
CREATE EXTENSION IF NOT EXISTS "pg_trgm"; -- Trigram similarity
```

### 7. PostGIS (Geospatial)

```sql
-- Enable PostGIS
CREATE EXTENSION postgis;

-- Create location table
CREATE TABLE locations (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255),
    location GEOGRAPHY(POINT, 4326)
);

-- Insert points (longitude, latitude)
INSERT INTO locations (name, location) VALUES
('New York', ST_GeographyFromText('POINT(-73.9857 40.7484)')),
('Los Angeles', ST_GeographyFromText('POINT(-118.2437 34.0522)'));

-- Find nearby locations (within 5000 meters)
SELECT name, ST_Distance(location, ST_GeographyFromText('POINT(-73.9857 40.7484)')) as distance
FROM locations
WHERE ST_DWithin(location, ST_GeographyFromText('POINT(-73.9857 40.7484)'), 5000)
ORDER BY distance;

-- Spatial index
CREATE INDEX idx_locations_geom ON locations USING GIST (location);
```

### Comparison: PostgreSQL vs MySQL

| Feature | PostgreSQL | MySQL |
|---------|-----------|-------|
| ACID compliance | ✅ Full | ⚠️ InnoDB only |
| Concurrency | ✅ MVCC | Locking |
| JSON support | ✅ JSONB (binary) | ⚠️ JSON (text) |
| Full-text search | ✅ Built-in | ⚠️ Limited |
| Window functions | ✅ Advanced | ⚠️ Basic (8.0+) |
| CTEs | ✅ Recursive | ✅ Non-recursive |
| Arrays | ✅ Native | ❌ No |
| Materialized views | ✅ Yes | ❌ No |
| Extensions | ✅ Many | ❌ Limited |
| License | ✅ Open source | ⚠️ GPL/Commercial |

**Follow-up:**
- What is MVCC and how does it work?
- When would you use JSONB over separate columns?
- What are materialized views?

**Key Points:**
- Advanced data types (JSONB, arrays, ranges)
- True ACID compliance with MVCC
- Built-in full-text search
- Powerful window functions
- Recursive CTEs
- Highly extensible

---

## Question 2: Explain PostgreSQL indexing strategies.

**Answer:**

### Index Types in PostgreSQL

#### 1. B-tree (Default)

```sql
-- Most common, good for equality and range queries
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_orders_created ON orders(created_at);

-- Composite index
CREATE INDEX idx_users_status_created ON users(status, created_at);

-- Operators: <, <=, =, >=, >, BETWEEN, IN, IS NULL, IS NOT NULL
SELECT * FROM users WHERE email = 'test@test.com'; -- Uses B-tree
SELECT * FROM orders WHERE created_at >= '2024-01-01'; -- Uses B-tree
```

#### 2. Hash Index

```sql
-- Only for equality (=) comparisons
CREATE INDEX idx_users_token_hash ON users USING HASH (api_token);

-- Good for: Exact matches on large values
-- Bad for: Range queries, ordering, pattern matching
-- Note: Hash indexes are WAL-logged since PostgreSQL 10
```

#### 3. GiST (Generalized Search Tree)

```sql
-- For complex data types: geometric, full-text, etc.

-- Full-text search
CREATE INDEX idx_documents_search ON documents USING GIST (search_vector);

-- Geometric data
CREATE TABLE locations (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255),
    boundary BOX
);
CREATE INDEX idx_locations_boundary ON locations USING GIST (boundary);

-- Range types
CREATE INDEX idx_events_duration ON events USING GIST (duration);
```

#### 4. GIN (Generalized Inverted Index)

```sql
-- For composite values: arrays, JSONB, full-text

-- JSONB
CREATE INDEX idx_products_attrs ON products USING GIN (attributes);
SELECT * FROM products WHERE attributes @> '{"brand": "Apple"}';

-- Arrays
CREATE INDEX idx_posts_tags ON posts USING GIN (tags);
SELECT * FROM posts WHERE tags @> ARRAY['postgresql'];

-- Full-text search (faster than GiST for static data)
CREATE INDEX idx_posts_search ON posts USING GIN (search_vector);

-- Trigram similarity (fuzzy search)
CREATE EXTENSION pg_trgm;
CREATE INDEX idx_users_name_trgm ON users USING GIN (name gin_trgm_ops);
SELECT * FROM users WHERE name % 'John'; -- Similarity search
```

#### 5. BRIN (Block Range Index)

```sql
-- For very large tables with natural ordering
-- Much smaller than B-tree, but less precise

-- Good for time-series data
CREATE INDEX idx_logs_created_brin ON logs USING BRIN (created_at);

-- Benefits:
-- - Tiny size (1000x smaller than B-tree)
-- - Fast creation
-- - Good for append-only tables

-- Drawbacks:
-- - Less precise (scans more rows)
-- - Only good for correlated data
```

#### 6. SP-GiST (Space-Partitioned GiST)

```sql
-- For non-balanced data structures

-- Phone numbers
CREATE INDEX idx_contacts_phone ON contacts USING SPGIST (phone_number);

-- IP addresses
CREATE INDEX idx_logs_ip ON logs USING SPGIST (ip_address inet_ops);

-- Good for: Prefix searches, quadtrees, tries
```

### Partial Indexes

```sql
-- Index only subset of rows
CREATE INDEX idx_users_active ON users(email) WHERE status = 'active';

-- Smaller, faster for specific queries
SELECT * FROM users WHERE status = 'active' AND email = 'test@test.com';

-- Index on non-null values only
CREATE INDEX idx_users_verified ON users(email) WHERE email_verified_at IS NOT NULL;
```

### Expression Indexes

```sql
-- Index on computed values
CREATE INDEX idx_users_lower_email ON users(LOWER(email));
SELECT * FROM users WHERE LOWER(email) = 'test@test.com';

-- Function-based index
CREATE INDEX idx_orders_year ON orders(EXTRACT(YEAR FROM created_at));
SELECT * FROM orders WHERE EXTRACT(YEAR FROM created_at) = 2024;

-- Conditional expression
CREATE INDEX idx_products_discount_price ON products((price * 0.9)) WHERE on_sale = true;
```

### Covering Indexes (Index-Only Scans)

```sql
-- Include non-key columns in index (PostgreSQL 11+)
CREATE INDEX idx_users_email_include ON users(email) INCLUDE (name, created_at);

-- Query uses only index, no table access
SELECT name, created_at FROM users WHERE email = 'test@test.com';
-- EXPLAIN shows "Index Only Scan"

-- Equivalent in older versions: add columns to index
CREATE INDEX idx_users_email_name ON users(email, name, created_at);
```

### Index Maintenance

```sql
-- Rebuild index (fix bloat)
REINDEX INDEX idx_users_email;
REINDEX TABLE users;

-- Concurrent rebuild (no locks)
CREATE INDEX CONCURRENTLY idx_users_email_new ON users(email);
DROP INDEX idx_users_email;
ALTER INDEX idx_users_email_new RENAME TO idx_users_email;

-- Analyze table statistics
ANALYZE users;

-- Vacuum and analyze
VACUUM ANALYZE users;

-- Find unused indexes
SELECT
    schemaname,
    tablename,
    indexname,
    idx_scan,
    idx_tup_read,
    idx_tup_fetch
FROM pg_stat_user_indexes
WHERE idx_scan = 0
  AND indexname NOT LIKE '%_pkey'
ORDER BY pg_relation_size(indexrelid) DESC;

-- Index size
SELECT
    indexname,
    pg_size_pretty(pg_relation_size(indexrelid)) as size
FROM pg_stat_user_indexes
ORDER BY pg_relation_size(indexrelid) DESC;
```

### Best Practices

```sql
-- ✅ Good: Selective index
CREATE INDEX idx_orders_pending ON orders(user_id) WHERE status = 'pending';

-- ❌ Bad: Index on low cardinality
CREATE INDEX idx_users_gender ON users(gender); -- Only 2-3 values

-- ✅ Good: Composite index order (high cardinality first)
CREATE INDEX idx_users_email_status ON users(email, status);

-- ❌ Bad: Redundant indexes
CREATE INDEX idx1 ON users(email);
CREATE INDEX idx2 ON users(email, name);
-- idx1 is redundant, drop it

-- ✅ Good: Concurrent index creation (no locks)
CREATE INDEX CONCURRENTLY idx_users_email ON users(email);

-- ✅ Good: Index foreign keys
CREATE INDEX idx_posts_user_id ON posts(user_id);
CREATE INDEX idx_comments_post_id ON comments(post_id);
```

**Follow-up:**
- When should you use GIN vs GiST?
- What are partial indexes and when to use them?
- How do you monitor index usage?

**Key Points:**
- B-tree for most cases (equality, range)
- GIN for JSONB, arrays, full-text
- GiST for geometric data
- BRIN for huge time-series tables
- Partial indexes for subset of rows
- INCLUDE for covering indexes
- Create CONCURRENTLY in production

---

## Question 3: How do you optimize PostgreSQL queries?

**Answer:**

### Query Analysis

#### EXPLAIN and EXPLAIN ANALYZE

```sql
-- Execution plan (estimates)
EXPLAIN SELECT * FROM users WHERE email = 'test@test.com';

-- Actual execution (runs query)
EXPLAIN ANALYZE SELECT * FROM users WHERE email = 'test@test.com';

-- Detailed output
EXPLAIN (ANALYZE, BUFFERS, VERBOSE) SELECT ...;

-- JSON format
EXPLAIN (ANALYZE, FORMAT JSON) SELECT ...;

-- Key metrics:
-- - Seq Scan (bad for large tables)
-- - Index Scan (good)
-- - Index Only Scan (best)
-- - Nested Loop (can be slow)
-- - Hash Join (fast for large datasets)
```

#### Common Issues

```sql
-- 1. Sequential Scan
EXPLAIN SELECT * FROM users WHERE LOWER(email) = 'test@test.com';
-- Seq Scan on users (cost=0.00..1000.00 rows=50000 width=100)

-- Fix: Create expression index
CREATE INDEX idx_users_lower_email ON users(LOWER(email));

-- 2. Missing Index
EXPLAIN SELECT * FROM posts WHERE user_id = 123;
-- Seq Scan (cost=0.00..5000.00)

-- Fix: Add index
CREATE INDEX idx_posts_user_id ON posts(user_id);

-- 3. Poor Join Order
EXPLAIN SELECT * FROM orders o
JOIN users u ON o.user_id = u.id
WHERE u.email = 'test@test.com';

-- PostgreSQL optimizer usually handles this, but can force:
SET join_collapse_limit = 1;
```

### Query Optimization Techniques

#### 1. Avoid SELECT *

```sql
-- ❌ Bad
SELECT * FROM posts;

-- ✅ Good
SELECT id, title, created_at FROM posts;

-- Enables covering indexes
CREATE INDEX idx_posts_title ON posts(id) INCLUDE (title, created_at);
```

#### 2. Use WHERE Efficiently

```sql
-- ❌ Bad: Function on indexed column
SELECT * FROM users WHERE EXTRACT(YEAR FROM created_at) = 2024;

-- ✅ Good: Range query
SELECT * FROM users WHERE created_at >= '2024-01-01' AND created_at < '2025-01-01';

-- ❌ Bad: Negation
SELECT * FROM users WHERE status != 'banned';

-- ✅ Good: Positive condition with partial index
CREATE INDEX idx_users_not_banned ON users(id) WHERE status != 'banned';
```

#### 3. JOINs

```sql
-- Use appropriate JOIN type

-- INNER JOIN: Only matching rows
SELECT p.title, u.name
FROM posts p
INNER JOIN users u ON p.user_id = u.id;

-- LEFT JOIN: All from left, matching from right
SELECT u.name, COUNT(p.id) as post_count
FROM users u
LEFT JOIN posts p ON u.id = p.user_id
GROUP BY u.id, u.name;

-- Avoid unnecessary JOINs
-- ❌ Bad: JOIN just to check existence
SELECT * FROM orders o
JOIN users u ON o.user_id = u.id
WHERE o.status = 'pending';

-- ✅ Good: No JOIN needed
SELECT * FROM orders WHERE status = 'pending';
```

#### 4. Subqueries vs JOINs

```sql
-- ❌ Bad: Correlated subquery
SELECT p.*,
    (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
FROM posts p;

-- ✅ Good: JOIN with GROUP BY
SELECT p.*, COUNT(c.id) as comment_count
FROM posts p
LEFT JOIN comments c ON p.id = c.post_id
GROUP BY p.id;

-- Use EXISTS for checking existence
-- ❌ Bad
SELECT * FROM users WHERE id IN (SELECT user_id FROM orders);

-- ✅ Good
SELECT * FROM users u WHERE EXISTS (
    SELECT 1 FROM orders WHERE user_id = u.id
);
```

#### 5. CTEs vs Subqueries

```sql
-- CTEs are optimization fences in PostgreSQL < 12
-- PostgreSQL 12+ can inline CTEs

-- Materialized CTE (always executed separately)
WITH recent_orders AS MATERIALIZED (
    SELECT * FROM orders WHERE created_at > NOW() - INTERVAL '7 days'
)
SELECT * FROM recent_orders WHERE total > 100;

-- Non-materialized CTE (can be inlined)
WITH recent_orders AS NOT MATERIALIZED (
    SELECT * FROM orders WHERE created_at > NOW() - INTERVAL '7 days'
)
SELECT * FROM recent_orders WHERE total > 100;
```

#### 6. Pagination

```sql
-- ❌ Bad: OFFSET with large values
SELECT * FROM posts ORDER BY created_at DESC LIMIT 20 OFFSET 10000;
-- Scans and discards 10,000 rows

-- ✅ Good: Keyset pagination
SELECT * FROM posts
WHERE created_at < '2024-01-15 10:00:00'
ORDER BY created_at DESC
LIMIT 20;

-- Or use cursor
BEGIN;
DECLARE posts_cursor CURSOR FOR
    SELECT * FROM posts ORDER BY created_at DESC;
FETCH 20 FROM posts_cursor;
-- Next page: FETCH 20 FROM posts_cursor;
COMMIT;
```

#### 7. Aggregations

```sql
-- Use appropriate aggregate
-- ❌ Bad: COUNT(*) on huge table
SELECT COUNT(*) FROM logs;

-- ✅ Good: Estimate
SELECT reltuples::BIGINT AS estimate FROM pg_class WHERE relname = 'logs';

-- Partial aggregates
SELECT status, COUNT(*) FROM orders WHERE created_at > NOW() - INTERVAL '30 days'
GROUP BY status;

-- Avoid HAVING when possible
-- ❌ Bad
SELECT user_id, COUNT(*) as order_count FROM orders
GROUP BY user_id
HAVING COUNT(*) > 5;

-- ✅ Good: Use WHERE if possible
-- (Not always applicable, depends on logic)
```

### PostgreSQL-Specific Optimizations

#### 1. JSONB Queries

```sql
-- Index JSONB
CREATE INDEX idx_products_attrs ON products USING GIN (attributes);

-- Efficient queries
SELECT * FROM products WHERE attributes @> '{"brand": "Apple"}';
SELECT * FROM products WHERE attributes ? 'brand';
SELECT * FROM products WHERE attributes ?| ARRAY['brand', 'color'];

-- Extract values
SELECT id, attributes->>'brand' as brand FROM products;
```

#### 2. Array Operations

```sql
-- Index arrays
CREATE INDEX idx_posts_tags ON posts USING GIN (tags);

-- Efficient queries
SELECT * FROM posts WHERE tags @> ARRAY['postgresql'];
SELECT * FROM posts WHERE tags && ARRAY['postgresql', 'database'];
SELECT * FROM posts WHERE 'postgresql' = ANY(tags);
```

#### 3. Parallel Queries (PostgreSQL 9.6+)

```sql
-- Enable parallelism
SET max_parallel_workers_per_gather = 4;

-- Check if query uses parallelism
EXPLAIN SELECT * FROM large_table WHERE status = 'active';
-- Look for "Parallel Seq Scan" or "Parallel Index Scan"

-- Force parallel scan
SET parallel_setup_cost = 0;
SET parallel_tuple_cost = 0;
```

### Monitoring and Profiling

```sql
-- Slow query log
ALTER DATABASE mydb SET log_min_duration_statement = 1000; -- 1 second

-- Current queries
SELECT pid, age(clock_timestamp(), query_start), usename, query
FROM pg_stat_activity
WHERE state != 'idle'
  AND query NOT ILIKE '%pg_stat_activity%'
ORDER BY query_start;

-- Kill long-running query
SELECT pg_terminate_backend(pid);

-- Table statistics
SELECT schemaname, tablename, n_live_tup, n_dead_tup, last_vacuum, last_autovacuum
FROM pg_stat_user_tables;

-- Index usage
SELECT schemaname, tablename, indexname, idx_scan, idx_tup_read, idx_tup_fetch
FROM pg_stat_user_indexes
ORDER BY idx_scan DESC;

-- Cache hit ratio
SELECT
    sum(heap_blks_read) as heap_read,
    sum(heap_blks_hit)  as heap_hit,
    sum(heap_blks_hit) / (sum(heap_blks_hit) + sum(heap_blks_read)) as ratio
FROM pg_statio_user_tables;
-- Aim for > 0.99 (99% hit rate)
```

**Follow-up:**
- What is the difference between EXPLAIN and EXPLAIN ANALYZE?
- How do you optimize JSONB queries?
- What is the purpose of VACUUM?

**Key Points:**
- Use EXPLAIN ANALYZE for query plans
- Index WHERE, JOIN, ORDER BY columns
- Avoid SELECT *, functions on indexed columns
- Use JOINs over correlated subqueries
- Keyset pagination over OFFSET
- Monitor with pg_stat_activity
- VACUUM regularly

---

## Question 4: Explain PostgreSQL replication and high availability.

**Answer:**

### Replication Types

#### 1. Streaming Replication (Built-in)

```bash
# Primary server configuration (postgresql.conf)
wal_level = replica
max_wal_senders = 10
max_replication_slots = 10
synchronous_commit = on

# Standby server configuration
hot_standby = on
```

```sql
-- On primary: Create replication user
CREATE ROLE replicator WITH REPLICATION LOGIN PASSWORD 'password';

-- pg_hba.conf on primary
host replication replicator standby_ip/32 md5

-- On standby: recovery.conf or postgresql.auto.conf (PG 12+)
primary_conninfo = 'host=primary_ip port=5432 user=replicator password=password'
restore_command = 'cp /archive/%f %p'
```

#### 2. Logical Replication (PostgreSQL 10+)

```sql
-- On primary
CREATE PUBLICATION my_publication FOR ALL TABLES;
-- Or specific tables
CREATE PUBLICATION user_publication FOR TABLE users, orders;

-- On subscriber
CREATE SUBSCRIPTION my_subscription
    CONNECTION 'host=primary_ip dbname=mydb user=replicator password=password'
    PUBLICATION my_publication;

-- Benefits:
-- - Selective replication (specific tables/databases)
-- - Different PostgreSQL versions
-- - Bi-directional replication possible
```

#### 3. Synchronous vs Asynchronous

```sql
-- Synchronous replication (no data loss)
synchronous_commit = on
synchronous_standby_names = 'standby1, standby2'

-- Transaction waits for standby confirmation
-- Slower, but zero data loss

-- Asynchronous replication (default)
synchronous_commit = off

-- Faster, but potential data loss on primary failure
```

### High Availability Setup

#### Using Patroni (HA Cluster)

```yaml
# Patroni configuration
scope: postgres-cluster
name: node1

restapi:
  listen: 0.0.0.0:8008
  connect_address: node1:8008

etcd:
  hosts: etcd1:2379,etcd2:2379,etcd3:2379

bootstrap:
  dcs:
    ttl: 30
    loop_wait: 10
    retry_timeout: 10
    maximum_lag_on_failover: 1048576
    postgresql:
      use_pg_rewind: true
      parameters:
        wal_level: replica
        max_connections: 100
        max_wal_senders: 10

postgresql:
  listen: 0.0.0.0:5432
  connect_address: node1:5432
  data_dir: /var/lib/postgresql/data
  pgpass: /tmp/pgpass
  authentication:
    replication:
      username: replicator
      password: password
    superuser:
      username: postgres
      password: password
```

```bash
# Start Patroni
patroni /etc/patroni/config.yml

# Check cluster status
patronictl -c /etc/patroni/config.yml list

# Failover
patronictl -c /etc/patroni/config.yml failover
```

#### Connection Pooling with PgBouncer

```ini
# pgbouncer.ini
[databases]
mydb = host=localhost port=5432 dbname=mydb

[pgbouncer]
listen_addr = *
listen_port = 6432
auth_type = md5
auth_file = /etc/pgbouncer/userlist.txt
pool_mode = transaction
max_client_conn = 1000
default_pool_size = 20
```

```bash
# Application connects to PgBouncer
psql -h localhost -p 6432 -U myuser mydb
```

### Backup Strategies

#### 1. pg_dump (Logical Backup)

```bash
# Full database dump
pg_dump mydb > backup.sql

# Compressed dump
pg_dump mydb | gzip > backup.sql.gz

# Custom format (supports parallel restore)
pg_dump -Fc mydb > backup.dump

# Specific tables
pg_dump -t users -t orders mydb > tables_backup.sql

# Schema only
pg_dump --schema-only mydb > schema.sql

# Data only
pg_dump --data-only mydb > data.sql

# Restore
psql mydb < backup.sql
pg_restore -d mydb backup.dump
```

#### 2. pg_basebackup (Physical Backup)

```bash
# Base backup for replication
pg_basebackup -h primary_host -D /var/lib/postgresql/standby -U replicator -P -v -X stream

# Compressed backup
pg_basebackup -h localhost -D - -Ft -z -P > backup.tar.gz

# Restore: Extract to data directory and configure recovery
```

#### 3. Continuous Archiving (WAL Archiving)

```bash
# postgresql.conf
archive_mode = on
archive_command = 'cp %p /archive/%f'

# Backup script
pg_basebackup -D /backup/base
# Save archive logs: /archive/

# Point-in-time recovery (PITR)
# 1. Restore base backup
# 2. Create recovery.conf
restore_command = 'cp /archive/%f %p'
recovery_target_time = '2024-01-15 12:00:00'
```

### Monitoring Replication

```sql
-- On primary: Check replication status
SELECT
    client_addr,
    state,
    sent_lsn,
    write_lsn,
    flush_lsn,
    replay_lsn,
    sync_state
FROM pg_stat_replication;

-- Replication lag
SELECT
    client_addr,
    pg_wal_lsn_diff(pg_current_wal_lsn(), replay_lsn) AS lag_bytes
FROM pg_stat_replication;

-- On standby: Check lag
SELECT
    now() - pg_last_xact_replay_timestamp() AS replication_lag;
```

### Failover Procedures

```bash
# Manual failover

# 1. Promote standby to primary
pg_ctl promote -D /var/lib/postgresql/data

# Or create trigger file
touch /var/lib/postgresql/data/promote

# 2. Point application to new primary

# 3. Rebuild old primary as new standby
pg_basebackup -h new_primary -D /var/lib/postgresql/data -U replicator

# Automatic failover with Patroni
# Patroni handles this automatically using etcd/consul/zookeeper for consensus
```

**Follow-up:**
- What's the difference between streaming and logical replication?
- How do you monitor replication lag?
- What is point-in-time recovery (PITR)?

**Key Points:**
- Streaming replication for HA
- Logical replication for selective sync
- Patroni for automatic failover
- PgBouncer for connection pooling
- pg_basebackup for physical backups
- WAL archiving for PITR
- Monitor replication lag

---

## Notes

Add more questions covering:
- Partitioning strategies (range, list, hash)
- Vacuum and autovacuum tuning
- Connection pooling best practices
- Foreign data wrappers (FDW)
- Materialized views
- Row-level security (RLS)
- Listen/Notify for pub-sub
- Performance tuning (shared_buffers, work_mem, etc.)
