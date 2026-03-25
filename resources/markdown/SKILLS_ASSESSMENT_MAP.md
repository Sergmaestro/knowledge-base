# Skills Assessment Map

This document maps required knowledge areas to interview questions and their locations in the knowledge base.

## PHP / Language Skills

### 1. Performance Optimization Methodology
**Question:** "How do you apply performance optimization techniques in PHP?"
**Location:** `PHP/performance.md`
- Covers: OPcache, query optimization, caching strategies, profiling
- **Assessment:** Ask candidate to explain how they'd optimize a slow PHP application

### 2. Custom Exceptions
**Question:** "How do you create and use custom exceptions? Provide an example."
**Location:** `PHP/advanced.md` → Question 1
- Covers: Exception hierarchy, context, Laravel integration
- **Assessment:** Ask for real-world exception design example

### 3. Metaprogramming Techniques
**Question:** "Explain PHP Reflection API and magic methods. When would you use each?"
**Location:** `PHP/advanced.md` → Question 2
- Covers: Reflection, magic methods, attributes (PHP 8)
- **Assessment:** Ask how Laravel's IoC container uses Reflection

### 4. Creating Custom Libraries
**Question:** "How do you create a reusable Composer package?"
**Expected Answer:**
```bash
composer init
# Define autoloading in composer.json
# Create src/ directory with namespaced classes
# Add to Packagist
# Version with git tags
```
**Assessment:** Ask about package structure and dependency management

### 5. Concurrency Concepts
**Question:** "How do you implement concurrency in PHP?"
**Location:** `PHP/advanced.md` → Question 3
- Covers: ReactPHP, amphp, Swoole, queues, parallel extension
- **Assessment:** Ask difference between async and parallel processing

### 6. Advanced Communication Protocols
**Question:** "How do you implement WebSockets or gRPC in PHP?"
**Expected Answer:**
- WebSockets: Ratchet, Swoole, Laravel WebSockets
- gRPC: php-grpc extension, protocol buffers
**Assessment:** Ask about real-time communication use cases

### 7. Math Operations Libraries
**Question:** "When and how do you use BCMath or GMP for precision math?"
**Expected Answer:**
```php
// BCMath for financial calculations
bcadd('0.1', '0.2', 2); // "0.30" (no float precision issues)
bcmul('123.45', '678.90', 2); // Precise multiplication

// GMP for large integers
gmp_add('99999999999999999999', '1');
```
**Assessment:** Ask about float precision problems

### 8. Profiling Libraries
**Question:** "What tools do you use for profiling PHP applications?"
**Expected Answer:**
- Xdebug profiler (cachegrind files)
- Blackfire.io (production profiling)
- Tideways
- New Relic APM
**Location:** `PHP/performance.md`
**Assessment:** Ask how to identify bottlenecks

### 9. Logging Libraries
**Question:** "How do you implement structured logging in PHP?"
**Location:** `Laravel/advanced.md` → Question 7
- Covers: Monolog, PSR-3, multiple channels
**Assessment:** Ask about log levels and context

### 10. Cryptography Libraries
**Question:** "How do you handle encryption and hashing in PHP?"
**Expected Answer:**
```php
// Sodium (modern, recommended)
sodium_crypto_secretbox($message, $nonce, $key);

// OpenSSL
openssl_encrypt($data, 'aes-256-gcm', $key, 0, $iv);

// Password hashing
password_hash($password, PASSWORD_ARGON2ID);
password_verify($password, $hash);
```
**Assessment:** Ask difference between encryption and hashing

---

## Laravel / Framework Skills

### 11. Performance Optimization with Framework
**Question:** "How do you optimize Laravel performance?"
**Location:** `Laravel/advanced.md`, `PHP/performance.md`
- Covers: Query optimization, caching, opcache, eager loading
**Assessment:** Ask about N+1 queries and caching strategies

### 12. Security Practices
**Question:** "How do you secure a Laravel application?"
**Location:** `Laravel/advanced.md` → Question 6
- Covers: XSS, CSRF, SQL injection, authentication, authorization
**Assessment:** Ask about OWASP Top 10 mitigation

### 13. Secure Environment Creation
**Question:** "How do you create a secure deployment environment?"
**Location:** `DevOps/monitoring.md` → Question 3
- Covers: Environment variables, secrets management, permissions
**Assessment:** Ask about secret storage (Vault, AWS Secrets Manager)

### 14. Custom Bundler Plugins
**Question:** "How do you create custom Vite or Webpack plugins?"
**Expected Answer:**
```javascript
// Vite plugin
export default {
  name: 'custom-plugin',
  transform(code, id) {
    if (id.endsWith('.vue')) {
      // Custom transformation
      return { code, map: null };
    }
  }
}
```
**Assessment:** Ask about build optimization techniques

### 15. Logging with Framework
**Question:** "How do you implement logging in Laravel?"
**Location:** `Laravel/advanced.md` → Question 7
**Assessment:** Ask about channels, log levels, and context

### 16. Process and Environment Management
**Question:** "How do you manage processes and environment in Laravel?"
**Location:** `Laravel/advanced.md` → Question 7 (inline above)
- Covers: env(), config(), Process facade, Supervisor
**Assessment:** Ask about long-running processes and workers

---

## Testing Skills

### 17. Code Coverage Configuration
**Question:** "How do you configure and interpret code coverage reports?"
**Expected Answer:**
```xml
<!-- phpunit.xml -->
<coverage processUncoveredFiles="true">
    <include>
        <directory suffix=".php">./app</directory>
    </include>
    <report>
        <html outputDirectory="coverage"/>
        <clover outputFile="coverage.xml"/>
    </report>
</coverage>
```
```bash
php artisan test --coverage --min=80
```
**Location:** `Laravel/testing.md`
**Assessment:** Ask about coverage targets and what to test

### 18. Test Tool Configuration
**Question:** "How do you configure PHPUnit and other testing tools?"
**Expected Answer:**
- PHPUnit: phpunit.xml configuration
- Pest PHP: Pest.php configuration
- Database: RefreshDatabase, DatabaseTransactions
- Mocking: Mockery configuration
**Location:** `Laravel/testing.md`
**Assessment:** Ask about test environment setup

---

## Architecture / Design Skills

### 19. Design Patterns for Responsibility Distribution
**Question:** "How do you use design patterns to distribute responsibility?"
**Expected Answer:**
- **Single Responsibility:** Each class has one reason to change
- **Repository Pattern:** Separate data access from business logic
- **Service Layer:** Isolate business logic from controllers
- **Strategy Pattern:** Encapsulate algorithms
- **Command Pattern:** Encapsulate requests as objects

**Location:** `System-Design/architecture.md`
**Assessment:** Ask to design a payment processing system

### 20. Composition with Design Patterns
**Question:** "How do you ensure flexible composition with design patterns?"
**Expected Answer:**
- **Decorator Pattern:** Add behavior dynamically
- **Adapter Pattern:** Make incompatible interfaces work together
- **Composite Pattern:** Treat individual and composite objects uniformly
- **Dependency Injection:** Inject dependencies rather than hardcode

**Location:** `System-Design/architecture.md`
**Assessment:** Ask about extending functionality without modifying code

### 21. Software Design Evaluation
**Question:** "How do you evaluate and modify software design for quality?"
**Expected Answer:**
- Code reviews focusing on SOLID principles
- Refactoring techniques (Extract Method, Move Method, etc.)
- Design smell detection (God Object, Feature Envy)
- Metrics: Cyclomatic complexity, coupling, cohesion
- Architecture Decision Records (ADRs)

**Assessment:** Give code sample and ask for improvement suggestions

### 22. Design Documentation
**Question:** "How do you document software design?"
**Expected Answer:**
- **C4 Model:** Context, Container, Component, Code diagrams
- **UML Diagrams:** Class, sequence, activity diagrams
- **Architecture Decision Records (ADRs)**
- **API Documentation:** OpenAPI/Swagger
- **README:** Architecture overview, setup instructions
- **Code Comments:** For complex logic only

**Assessment:** Ask to explain their last major architectural decision

### 23. Modeling Techniques for Requirements
**Question:** "What modeling techniques do you use for requirements analysis?"
**Expected Answer:**
- **User Stories:** As a [role], I want [feature] so that [benefit]
- **Use Case Diagrams:** Actors and their interactions
- **Domain Model:** Entities, value objects, aggregates (DDD)
- **Event Storming:** Identify domain events and commands
- **State Machines:** Model entity lifecycle
- **ERD:** Entity Relationship Diagrams for data modeling

**Assessment:** Ask to model a booking system

### 24. Requirements Validation
**Question:** "How do you validate requirements?"
**Expected Answer:**
- **Review Sessions:** Stakeholder walkthroughs
- **Prototypes:** Quick mockups for feedback
- **Acceptance Criteria:** Clear, testable conditions
- **Test Cases:** Write tests before implementation (TDD)
- **User Acceptance Testing (UAT)**
- **Traceability Matrix:** Link requirements to tests

**Assessment:** Ask about handling unclear or conflicting requirements

---

## DevOps / CI-CD Skills

### 25. Version Control Workflow
**Question:** "How do you configure contribution workflow using Git?"
**Expected Answer:**
- **Branching Strategy:**
  - GitFlow: master, develop, feature/, release/, hotfix/
  - GitHub Flow: main, feature branches
  - Trunk-based: Short-lived branches

```bash
# Feature workflow
git checkout -b feature/user-authentication
# Make changes, commit
git push origin feature/user-authentication
# Create PR, code review, merge
```

- **PR Templates:** Checklist for reviewers
- **Branch Protection:** Require reviews, CI pass
- **Commit Conventions:** Conventional Commits (feat:, fix:, docs:)

**Assessment:** Ask about merge vs rebase strategy

### 26. CI Tool Configuration
**Question:** "How do you configure CI pipelines?"
**Expected Answer:**

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_DATABASE: testing
          MYSQL_ROOT_PASSWORD: password
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, pdo_mysql
      - name: Install Dependencies
        run: composer install
      - name: Run Tests
        run: php artisan test --coverage
      - name: Upload Coverage
        uses: codecov/codecov-action@v2
```

**Location:** Referenced in `DevOps/monitoring.md`
**Assessment:** Ask about pipeline optimization and caching

### 27. Code Quality Measurement
**Question:** "What tools do you use to control code quality?"
**Expected Answer:**
- **Static Analysis:**
  - PHPStan (level 0-9)
  - Psalm
  - PHP_CodeSniffer (PSR-12)
  - PHP-CS-Fixer

```bash
# PHPStan
vendor/bin/phpstan analyse app --level=8

# PHP-CS-Fixer
vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php
```

- **Code Coverage:** PHPUnit, Pest
- **Complexity:** PHPMD (PHP Mess Detector)
- **Security:** Snyk, Dependabot
- **SonarQube:** Comprehensive analysis

**Assessment:** Ask about quality gates in CI

### 28. Application Monitoring Ecosystem
**Question:** "How do you create an ecosystem for application state monitoring?"
**Location:** `DevOps/monitoring.md` → Question 1
**Answer Covers:**
- **APM:** New Relic, Datadog, Scout APM
- **Logging:** ELK Stack, Loki+Grafana, Papertrail
- **Metrics:** Prometheus + Grafana
- **Tracing:** Jaeger, Zipkin
- **Uptime:** Pingdom, UptimeRobot
- **Error Tracking:** Sentry, Bugsnag

```php
// Health checks
Route::get('/health', function () {
    return [
        'status' => 'healthy',
        'database' => checkDatabase(),
        'redis' => checkRedis(),
        'queue' => checkQueue(),
    ];
});
```

**Assessment:** Ask about alerting strategy and SLA targets

---

## Distributed Systems (Bonus Advanced Topics)

### 29. Eventual Consistency
**Question:** "How do you deal with eventual consistency in distributed systems?"
**Expected Answer:**
- **CQRS:** Separate read and write models
- **Event Sourcing:** Store all changes as events
- **Saga Pattern:** Manage distributed transactions
- **Compensating Transactions:** Rollback via inverse operations
- **Read-Your-Writes Consistency:** Show user their own changes immediately

**Location:** `System-Design/architecture.md` (CQRS pattern)
**Assessment:** Ask about designing a distributed e-commerce system

### 30. Monolith to Microservices
**Question:** "How would you split a monolith into services (or decide not to)?"
**Location:** `Behavioral/questions.md` → Question 2 (disagreeing example)
**Expected Answer:**
- **When NOT to split:**
  - Small team (< 10 developers)
  - Simple domain
  - Deployment isn't a bottleneck
  - No different scaling needs

- **When to split:**
  - Different teams own different domains
  - Different scaling requirements (e.g., search service)
  - Technology diversity needed
  - Independent deployment valuable

- **How to split:**
  1. Identify bounded contexts (DDD)
  2. Start with strangler pattern (extract one service)
  3. API Gateway for routing
  4. Event-driven communication
  5. Shared nothing (separate databases)

**Assessment:** Ask about their experience with microservices

---

## Quick Assessment Questions

Use these for rapid skill validation during initial screening:

### PHP
- "What's the difference between `==` and `===`?"
- "How does autoloading work in PHP?"
- "What are PHP 8's major features?"

### Laravel
- "Explain the request lifecycle in Laravel"
- "What's the difference between `get()` and `first()`?"
- "How do you prevent N+1 queries?"

### Database
- "What's the difference between INNER JOIN and LEFT JOIN?"
- "How do you optimize a slow query?"
- "Explain database indexing"

### Architecture
- "What is SOLID?"
- "Explain the Repository pattern"
- "When would you use a Service class?"

### Testing
- "What's the difference between a unit test and a feature test?"
- "How do you test external API calls?"
- "What is TDD?"

### DevOps
- "How do you handle zero-downtime deployments?"
- "Explain blue-green deployment"
- "What metrics do you monitor in production?"

---

## Interview Structure Recommendation

### 1. Phone Screen (30 min)
- Quick assessment questions (above)
- Discuss recent project
- Availability and salary expectations

### 2. Technical Interview (60-90 min)
- **Code Review Exercise (20 min):** Review provided code sample
- **System Design (30 min):** Design a feature (e.g., rating system)
- **Problem Solving (20 min):** Live coding or algorithm question
- **Questions (20 min):** Candidate asks questions

### 3. Behavioral Interview (45-60 min)
**Use questions from:** `Behavioral/questions.md`
- Technical challenge story
- Conflict resolution
- Mentorship experience
- Production incident handling
- Technical decision under uncertainty

### 4. Architecture Review (45-60 min, Senior+)
- Present past system architecture
- Discuss trade-offs and decisions
- Code quality practices
- Team collaboration

---

## Scoring Rubric

### Junior (0-2 years)
- **Must Have:** Basic PHP syntax, Laravel fundamentals, SQL basics
- **Nice to Have:** Testing basics, Git workflow, design patterns awareness

### Mid-Level (2-5 years)
- **Must Have:** OOP principles, Laravel advanced features, testing, design patterns
- **Nice to Have:** Performance optimization, security best practices, mentoring

### Senior (5+ years)
- **Must Have:** Architecture design, system scalability, team leadership, production debugging
- **Nice to Have:** Distributed systems, microservices, infrastructure knowledge

### Staff/Principal (8+ years)
- **Must Have:** Strategic technical decisions, organization-wide impact, mentoring seniors
- **Nice to Have:** Open source contributions, conference speaking, technical writing

