# Database Optimization & Security

## Question 1: How do you apply security techniques for databases?

**Answer:**

### Access Control & Authentication

```sql
-- Create users with specific permissions
CREATE USER 'app_user'@'%' IDENTIFIED BY 'strong_password';
CREATE USER 'readonly_user'@'%' IDENTIFIED BY 'readonly_pass';

-- Grant minimal necessary privileges
GRANT SELECT, INSERT, UPDATE, DELETE ON app_db.* TO 'app_user'@'%';
GRANT SELECT ON app_db.* TO 'readonly_user'@'%';

-- Revoke dangerous privileges
REVOKE FILE, PROCESS, SUPER ON *.* FROM 'app_user'@'%';

-- Use specific hosts instead of '%'
CREATE USER 'app_user'@'10.0.1.0/255.255.255.0' IDENTIFIED BY 'password';
```

### Encryption

```sql
-- Encryption at rest
-- my.cnf
[mysqld]
innodb_encrypt_tables = ON
innodb_encrypt_log = ON
innodb_encrypt_temporary_tables = ON

-- Transparent Data Encryption (TDE)
ALTER TABLE users ENCRYPTION='Y';

-- Application-level encryption
-- Laravel: Use encrypted cast
class User extends Model
{
    protected $casts = [
        'ssn' => 'encrypted',
        'credit_card' => 'encrypted',
    ];
}

-- Column-level encryption
INSERT INTO users (name, encrypted_ssn)
VALUES ('John', AES_ENCRYPT('123-45-6789', 'encryption_key'));

SELECT name, CAST(AES_DECRYPT(encrypted_ssn, 'encryption_key') AS CHAR) AS ssn
FROM users;
```

### SQL Injection Prevention

```php
// ✅ Good: Prepared statements
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);

// ✅ Good: Laravel Query Builder
DB::table('users')->where('email', $email)->first();

// ✅ Good: Eloquent
User::where('email', $email)->first();

// ❌ Bad: String concatenation
$query = "SELECT * FROM users WHERE email = '$email'";  // VULNERABLE!
DB::select($query);

// ✅ Good: Named parameters
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);

// ✅ Good: Laravel with bindings
DB::select('SELECT * FROM users WHERE email = ?', [$email]);
```

### Audit Logging

```sql
-- Enable audit log
-- my.cnf
[mysqld]
plugin-load-add=audit_log.so
audit_log_policy=ALL
audit_log_format=JSON
audit_log_file=audit.log

-- Query log for debugging (performance impact!)
SET GLOBAL general_log = 'ON';
SET GLOBAL log_output = 'TABLE';

-- Check who did what
SELECT *
FROM mysql.general_log
WHERE command_type = 'Query'
  AND argument LIKE '%DELETE%'
ORDER BY event_time DESC
LIMIT 100;
```

### Row-Level Security

```php
// Laravel Global Scopes
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $builder->where('tenant_id', auth()->user()->tenant_id);
    }
}

class User extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}

// Now all queries automatically filter by tenant
User::all();  // WHERE tenant_id = ?

// PostgreSQL Row-Level Security
ALTER TABLE users ENABLE ROW LEVEL SECURITY;

CREATE POLICY user_policy ON users
FOR ALL
TO app_user
USING (tenant_id = current_setting('app.tenant_id')::int);
```

### Database Firewall Rules

```bash
# AWS RDS Security Group
aws ec2 authorize-security-group-ingress \
    --group-id sg-xxxxxx \
    --protocol tcp \
    --port 3306 \
    --source-group sg-yyyyyy  # Only from app servers

# PostgreSQL pg_hba.conf
# TYPE  DATABASE    USER        ADDRESS         METHOD
host    all         all         10.0.1.0/24     md5
host    all         all         0.0.0.0/0       reject
```

### Backup Security

```bash
# Encrypted backups
mysqldump --single-transaction --routines \
  --triggers --all-databases | \
  openssl enc -aes-256-cbc -salt -out backup.sql.enc

# Decrypt
openssl enc -d -aes-256-cbc -in backup.sql.enc -out backup.sql

# AWS RDS automated encrypted backups
aws rds modify-db-instance \
    --db-instance-identifier mydb \
    --backup-retention-period 7 \
    --storage-encrypted \
    --apply-immediately
```

### Vulnerability Scanning

```bash
# Check for weak passwords
SELECT user, host
FROM mysql.user
WHERE authentication_string = PASSWORD('password')
   OR authentication_string = PASSWORD('admin')
   OR authentication_string = '';

# Check for unnecessary privileges
SELECT user, host, Super_priv, File_priv, Grant_priv
FROM mysql.user
WHERE (Super_priv = 'Y' OR File_priv = 'Y' OR Grant_priv = 'Y')
  AND user != 'root';
```

**Follow-up:**
- How do you handle PII (Personally Identifiable Information)?
- What's the difference between encryption at rest and in transit?
- How do you secure database backups?

**Key Points:**
- Principle of least privilege
- Prepared statements prevent SQL injection
- Encryption at rest and in transit
- Audit logging for compliance
- Row-level security for multi-tenancy
- Network isolation (security groups)
- Encrypted backups
- Regular security audits

---

## Question 2: How do you optimize database performance?

**Answer:**

### Indexing Strategies

```sql
-- Primary key (automatically indexed)
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    name VARCHAR(255)
);

-- Single column index
CREATE INDEX idx_email ON users(email);

-- Composite index (order matters!)
CREATE INDEX idx_user_status_created ON users(status, created_at);
-- Good for: WHERE status = 'active' AND created_at > '2024-01-01'
-- Also good for: WHERE status = 'active'
-- Bad for: WHERE created_at > '2024-01-01' (doesn't use index)

-- Covering index (includes all needed columns)
CREATE INDEX idx_users_lookup ON users(email, name, status);
-- Query can be answered entirely from index
SELECT name, status FROM users WHERE email = 'john@example.com';

-- Unique index
CREATE UNIQUE INDEX idx_email_unique ON users(email);

-- Partial index (PostgreSQL)
CREATE INDEX idx_active_users ON users(email)
WHERE status = 'active';

-- Full-text index
CREATE FULLTEXT INDEX idx_content_fulltext ON posts(title, body);
SELECT * FROM posts WHERE MATCH(title, body) AGAINST('laravel');
```

### Query Optimization

```sql
-- ❌ Bad: SELECT *
SELECT * FROM users WHERE id = 1;

-- ✅ Good: SELECT specific columns
SELECT id, name, email FROM users WHERE id = 1;

-- ❌ Bad: Function on indexed column
SELECT * FROM users WHERE YEAR(created_at) = 2024;

-- ✅ Good: Sargable query (can use index)
SELECT * FROM users
WHERE created_at >= '2024-01-01'
  AND created_at < '2025-01-01';

-- ❌ Bad: Leading wildcard
SELECT * FROM users WHERE email LIKE '%@gmail.com';

-- ✅ Good: Prefix search
SELECT * FROM users WHERE email LIKE 'john%';

-- Use EXPLAIN to analyze
EXPLAIN SELECT * FROM users WHERE status = 'active';
```

### N+1 Query Prevention

```php
// ❌ Bad: N+1 queries
$posts = Post::all();  // 1 query
foreach ($posts as $post) {
    echo $post->author->name;  // N queries
}

// ✅ Good: Eager loading
$posts = Post::with('author')->get();  // 2 queries
foreach ($posts as $post) {
    echo $post->author->name;  // No additional query
}

// ✅ Good: Lazy eager loading
$posts = Post::all();
if ($needAuthors) {
    $posts->load('author');
}

// ✅ Good: Constrained eager loading
$posts = Post::with(['comments' => function ($query) {
    $query->where('approved', true)->limit(5);
}])->get();
```

### Database Caching

```php
// Query result caching
$users = Cache::remember('active_users', 3600, function () {
    return User::where('status', 'active')->get();
});

// Model caching
class User extends Model
{
    public static function findCached($id)
    {
        return Cache::remember("user:{$id}", 3600, function () use ($id) {
            return static::find($id);
        });
    }
}

// Query builder caching
DB::table('users')
    ->where('active', true)
    ->remember(3600)  // Custom macro
    ->get();
```

### Connection Pooling

```php
// Laravel database configuration
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST'),
    'database' => env('DB_DATABASE'),
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
    'options' => [
        PDO::ATTR_PERSISTENT => true,  // Persistent connections
    ],
    'pool' => [
        'min' => 2,
        'max' => 20,
    ],
],

// PgBouncer for PostgreSQL
[databases]
mydb = host=localhost port=5432 dbname=mydb

[pgbouncer]
pool_mode = transaction
max_client_conn = 1000
default_pool_size = 20
```

### Read/Write Splitting

```php
// config/database.php
'mysql' => [
    'read' => [
        'host' => [
            'replica1.mysql.com',
            'replica2.mysql.com',
        ],
    ],
    'write' => [
        'host' => [
            'primary.mysql.com',
        ],
    ],
    'driver' => 'mysql',
    'database' => 'mydb',
],

// Automatic routing
User::create([...]);  // Goes to write (primary)
User::all();          // Goes to read (replica)

// Force read from primary
User::onWriteConnection()->where('id', 1)->first();
```

### Partitioning

```sql
-- Range partitioning by date
CREATE TABLE orders (
    id BIGINT,
    user_id BIGINT,
    total DECIMAL(10,2),
    created_at DATETIME
)
PARTITION BY RANGE (YEAR(created_at)) (
    PARTITION p2022 VALUES LESS THAN (2023),
    PARTITION p2023 VALUES LESS THAN (2024),
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);

-- Query automatically uses partition pruning
SELECT * FROM orders
WHERE created_at >= '2024-01-01'
  AND created_at < '2024-02-01';
-- Only scans p2024 partition

-- List partitioning
CREATE TABLE users (
    id BIGINT,
    country VARCHAR(2)
)
PARTITION BY LIST (country) (
    PARTITION p_us VALUES IN ('US'),
    PARTITION p_eu VALUES IN ('UK', 'FR', 'DE'),
    PARTITION p_asia VALUES IN ('JP', 'CN', 'IN')
);
```

### Denormalization for Performance

```php
// Store computed values
class Post extends Model
{
    protected static function booted()
    {
        static::saving(function ($post) {
            $post->comments_count = $post->comments()->count();
            $post->likes_count = $post->likes()->count();
        });
    }
}

// Use instead of counting every time
$post->comments_count;  // Fast
// Instead of:
$post->comments()->count();  // Slow (query each time)

// Materialized views (PostgreSQL)
CREATE MATERIALIZED VIEW user_stats AS
SELECT
    user_id,
    COUNT(*) as post_count,
    SUM(views) as total_views
FROM posts
GROUP BY user_id;

-- Refresh periodically
REFRESH MATERIALIZED VIEW user_stats;
```

### Database Monitoring

```sql
-- Slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;  -- Log queries > 1 second
SET GLOBAL slow_query_log_file = '/var/log/mysql/slow.log';

-- Current queries
SHOW FULL PROCESSLIST;

-- Query performance schema
SELECT
    DIGEST_TEXT,
    COUNT_STAR,
    AVG_TIMER_WAIT / 1000000000 AS avg_ms
FROM performance_schema.events_statements_summary_by_digest
ORDER BY AVG_TIMER_WAIT DESC
LIMIT 10;

-- Index usage
SELECT
    TABLE_NAME,
    INDEX_NAME,
    SEQ_IN_INDEX,
    COLUMN_NAME
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = 'mydb'
  AND TABLE_NAME = 'users';
```

**Follow-up:**
- How do you identify slow queries?
- When should you denormalize data?
- What's the difference between read replicas and sharding?

**Key Points:**
- Index frequently queried columns
- Avoid SELECT * and functions on indexed columns
- Eager load relationships to prevent N+1
- Cache query results
- Use read replicas for scaling reads
- Partition large tables by date/region
- Monitor slow queries
- Connection pooling for efficiency

---

## Question 3: How do you create and use database dumps?

**Answer:**

### Creating Backups

```bash
# MySQL/MariaDB: Full database dump
mysqldump -u root -p mydb > backup.sql

# With structure and data
mysqldump -u root -p \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --databases mydb > backup.sql

# Only structure (no data)
mysqldump -u root -p --no-data mydb > schema.sql

# Only data (no structure)
mysqldump -u root -p --no-create-info mydb > data.sql

# Specific tables
mysqldump -u root -p mydb users orders > tables_backup.sql

# Compressed backup
mysqldump -u root -p mydb | gzip > backup.sql.gz

# All databases
mysqldump -u root -p --all-databases > all_databases.sql

# PostgreSQL
pg_dump -U postgres mydb > backup.sql
pg_dump -U postgres -F c mydb > backup.dump  # Custom format (faster)
pg_dumpall -U postgres > all_databases.sql  # All databases
```

### Restoring Backups

```bash
# MySQL/MariaDB
mysql -u root -p mydb < backup.sql

# Restore from compressed
gunzip < backup.sql.gz | mysql -u root -p mydb

# PostgreSQL
psql -U postgres mydb < backup.sql
pg_restore -U postgres -d mydb backup.dump  # From custom format

# Laravel: Refresh database from backup
php artisan db:wipe
mysql -u root -p mydb < backup.sql
php artisan migrate:status
```

### Automated Backups

```bash
#!/bin/bash
# backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups"
DB_NAME="mydb"
DB_USER="root"
DB_PASS="password"

# Create backup
mysqldump -u $DB_USER -p$DB_PASS \
    --single-transaction \
    --routines \
    --triggers \
    $DB_NAME | gzip > "$BACKUP_DIR/${DB_NAME}_${DATE}.sql.gz"

# Upload to S3
aws s3 cp "$BACKUP_DIR/${DB_NAME}_${DATE}.sql.gz" \
    s3://my-backups/mysql/

# Delete local backups older than 7 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +7 -delete

# Delete S3 backups older than 30 days
aws s3 ls s3://my-backups/mysql/ | \
    while read -r line; do
        createDate=$(echo $line | awk {'print $1" "$2'})
        createDate=$(date -d "$createDate" +%s)
        olderThan=$(date -d "30 days ago" +%s)
        if [[ $createDate -lt $olderThan ]]; then
            fileName=$(echo $line | awk {'print $4'})
            aws s3 rm s3://my-backups/mysql/$fileName
        fi
    done

# Crontab
# 0 2 * * * /scripts/backup.sh >> /var/log/backup.log 2>&1
```

### Laravel Database Seeding from Dumps

```php
// Export production data for development
// database/seeders/ProductionDataSeeder.php
class ProductionDataSeeder extends Seeder
{
    public function run()
    {
        // Anonymize sensitive data
        DB::statement("
            UPDATE users
            SET email = CONCAT('user', id, '@example.com'),
                password = ?
        ", [Hash::make('password')]);

        // Export to SQL
        $users = DB::table('users')->get();

        foreach ($users as $user) {
            echo "INSERT INTO users VALUES (...);\\n";
        }
    }
}

// Or use mysqldump with WHERE clause
// mysqldump mydb users --where="created_at >= '2024-01-01'" > recent_users.sql
```

### Point-in-Time Recovery

```bash
# MySQL: Enable binary logging
# my.cnf
[mysqld]
log-bin=mysql-bin
binlog_format=ROW
expire_logs_days=7

# Create full backup
mysqldump --single-transaction --flush-logs --master-data=2 mydb > backup.sql

# Restore to specific point in time
# 1. Restore full backup
mysql mydb < backup.sql

# 2. Apply binary logs up to incident time
mysqlbinlog --stop-datetime="2024-01-15 10:30:00" \
    mysql-bin.000001 mysql-bin.000002 | mysql mydb

# PostgreSQL: Point-in-Time Recovery (PITR)
# postgresql.conf
archive_mode = on
archive_command = 'cp %p /mnt/server/archivedir/%f'

# Create base backup
pg_basebackup -U postgres -D /backup/base -Fp -Xs -P

# recovery.conf
restore_command = 'cp /mnt/server/archivedir/%f %p'
recovery_target_time = '2024-01-15 10:30:00'
```

### Sanitizing Data for Development

```bash
# Script to anonymize production data
# anonymize.sql

-- Anonymize emails
UPDATE users
SET email = CONCAT('user', id, '@example.com')
WHERE email NOT LIKE '%@example.com';

-- Anonymize names
UPDATE users
SET
    name = CONCAT('User ', id),
    phone = NULL,
    address = NULL;

-- Anonymize sensitive tables
TRUNCATE TABLE credit_cards;
TRUNCATE TABLE payment_methods;

-- Keep essential data
-- (Don't truncate: users, products, categories, etc.)

# Usage
mysqldump -u root -p proddb | \
    mysql -u root -p devdb && \
    mysql -u root -p devdb < anonymize.sql
```

### Testing Backups

```bash
#!/bin/bash
# test_backup.sh

BACKUP_FILE="$1"
TEST_DB="test_restore_$(date +%s)"

# Create test database
mysql -u root -p -e "CREATE DATABASE $TEST_DB"

# Restore backup
mysql -u root -p $TEST_DB < $BACKUP_FILE

# Verify data
TABLES=$(mysql -u root -p $TEST_DB -e "SHOW TABLES" | wc -l)
USERS=$(mysql -u root -p $TEST_DB -e "SELECT COUNT(*) FROM users" | tail -1)

echo "Tables: $TABLES"
echo "Users: $USERS"

# Cleanup
mysql -u root -p -e "DROP DATABASE $TEST_DB"

if [[ $TABLES -gt 0 ]] && [[ $USERS -gt 0 ]]; then
    echo "✅ Backup valid"
    exit 0
else
    echo "❌ Backup invalid"
    exit 1
fi
```

**Follow-up:**
- How do you ensure backup integrity?
- What's the difference between full and incremental backups?
- How do you handle large database backups (terabytes)?

**Key Points:**
- Regular automated backups (daily/hourly)
- Test restore process regularly
- Store backups offsite (S3, separate region)
- Encrypt backups
- Retention policy (7 days local, 30 days S3)
- Point-in-time recovery with binary logs
- Anonymize production data for development
- Monitor backup success/failure

---

## Notes

Add more questions covering:
- Database replication (master-slave, multi-master)
- Sharding strategies
- Database migration tools
- Schema versioning
- Data warehousing
- NoSQL vs SQL trade-offs
