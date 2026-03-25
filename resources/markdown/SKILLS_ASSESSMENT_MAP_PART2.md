# Skills Assessment Map - Part 2

## DevOps & Infrastructure Skills

### 1. Environment Variables & Secrets Management
**Question:** "How do you handle environment variables and secrets in production?"
**Location:** `DevOps/infrastructure.md` → Question 1
**Quick Assessment:**
```bash
# Ask candidate to explain
"Where should you NEVER store secrets?"
# Expected: Git, logs, error messages, client-side code

"What's the difference between .env and secrets manager?"
# Expected: .env for config, secrets manager for sensitive data with rotation
```

### 2. Code Review Process Setup
**Question:** "How do you set up an effective code review process?"
**Expected Answer:**
- **PR Template:** Checklist (tests added, docs updated, breaking changes)
- **Branch Protection:** Require approvals (2+ for critical code)
- **Automated Checks:** Linters, tests, coverage must pass
- **Review Guidelines:** Max PR size (400 lines), focus areas
- **Turnaround Time:** Reviews within 24 hours
- **Tools:** GitHub/GitLab/Bitbucket, CodeClimate, SonarQube

**Assessment:** Ask about their experience with code review anti-patterns

### 3. Container Configuration
**Question:** "How do you configure containers for cloud deployment?"
**Location:** `DevOps/infrastructure.md` → Question 2
**Quick Assessment:**
```dockerfile
# Show this and ask for improvements
FROM php:latest
COPY . /app
RUN composer install
CMD php artisan serve

# Expected improvements:
# - Use specific version (not latest)
# - Multi-stage build
# - Non-root user
# - .dockerignore
# - Health checks
# - Resource limits
```

### 4. Infrastructure as Code (IaC)
**Question:** "Explain Terraform vs CloudFormation. When would you use each?"
**Location:** `DevOps/infrastructure.md` → Question 3
**Quick Assessment:**
- Terraform: Multi-cloud, HCL, larger community
- CloudFormation: AWS-only, native integration, no extra cost
- Use Terraform for: Multi-cloud, reusable modules
- Use CloudFormation for: AWS-only, CDK (TypeScript/Python)

### 5. Serverless Applications
**Question:** "How do you build serverless applications?"
**Expected Answer:**

```yaml
# AWS SAM template
AWSTemplateFormatVersion: '2010-09-09'
Transform: AWS::Serverless-2016-10-31

Resources:
  ApiFunction:
    Type: AWS::Serverless::Function
    Properties:
      Runtime: provided.al2
      Handler: public/index.php
      Layers:
        - !Sub arn:aws:lambda:${AWS::Region}:534081306603:layer:php-82-fpm:50
      Events:
        Api:
          Type: HttpApi
          Properties:
            Path: /{proxy+}
            Method: ANY

# Laravel on Lambda (Bref)
composer require bref/bref
php artisan vendor:publish --tag=serverless-config
serverless deploy
```

**Use Cases for Serverless:**
- API endpoints with variable traffic
- Scheduled jobs (cron replacements)
- Event-driven processing (S3 uploads, SNS messages)
- Prototypes and MVPs

**Trade-offs:**
- ✅ Auto-scaling, pay per use, no server management
- ❌ Cold starts, vendor lock-in, stateless

---

## Database Skills

### 6. Database Security
**Question:** "How do you secure a database in production?"
**Location:** `Database/optimization-security.md` → Question 1
**Quick Assessment:**
```sql
-- Ask: What's wrong with this?
CREATE USER 'app'@'%' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON *.* TO 'app'@'%';

-- Expected issues:
-- 1. '%' allows from anywhere (use specific IP)
-- 2. 'password' is weak
-- 3. ALL PRIVILEGES is too broad (principle of least privilege)
-- 4. *.* grants access to all databases

-- Correct version:
CREATE USER 'app'@'10.0.1.0/255.255.255.0' IDENTIFIED BY 'strong_random_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON app_db.* TO 'app'@'10.0.1.0/255.255.255.0';
```

### 7. Database Optimization
**Question:** "How do you optimize a slow query?"
**Location:** `Database/optimization-security.md` → Question 2
**Assessment Process:**
```sql
-- Give this slow query
SELECT users.*, COUNT(posts.id) as post_count
FROM users
LEFT JOIN posts ON users.id = posts.user_id
WHERE users.status = 'active'
  AND YEAR(users.created_at) = 2024
GROUP BY users.id;

-- Ask for optimizations
-- Expected answers:
-- 1. Add index on status
-- 2. Change YEAR(created_at) to range (sargable)
-- 3. Consider denormalizing post_count
-- 4. Use EXPLAIN to analyze
```

### 8. Database Dumps & Backups
**Question:** "How do you backup and restore databases?"
**Location:** `Database/optimization-security.md` → Question 3
**Quick Assessment:**
```bash
# Ask about backup strategy
"How often should you backup? How long to keep?"
# Expected: Daily full, hourly incremental; 7 days local, 30 days S3

"How do you test backups?"
# Expected: Regular restore tests, automated validation

"What's point-in-time recovery?"
# Expected: Restore to specific timestamp using binary logs
```

### 9. Database Documentation
**Question:** "What tools do you use to document database schema?"
**Expected Answer:**
- **Automated Tools:**
  - SchemaSpy: Generates HTML documentation from DB
  - DbDocs: Collaborative database documentation
  - Dataedo: Enterprise database documentation
  - DBeaver: ER diagrams and metadata export

```bash
# SchemaSpy example
java -jar schemaspy.jar \
    -t mysql \
    -host localhost \
    -db mydb \
    -u root \
    -p password \
    -o docs/

# Generates HTML with:
# - ER diagrams
# - Table relationships
# - Column details
# - Constraints and indexes
```

- **Laravel Migrations as Documentation:**
```php
// Migration files serve as versioned schema docs
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('email')->unique()->comment('User email address');
    $table->string('name')->comment('Full name');
    $table->enum('status', ['active', 'inactive'])->default('active');
    $table->timestamps();
});
```

### 10. Data Access Optimization Patterns
**Question:** "What patterns do you use to optimize data access?"
**Expected Answer:**

**Repository Pattern:**
```php
interface UserRepository {
    public function find(int $id): ?User;
    public function findByEmail(string $email): ?User;
}

class CachedUserRepository implements UserRepository {
    public function find(int $id): ?User {
        return Cache::remember("user:{$id}", 3600, fn() =>
            User::find($id)
        );
    }
}
```

**Data Mapper Pattern:** Separates domain objects from persistence
**Active Record Pattern:** Laravel Eloquent (domain + persistence)
**Query Object Pattern:** Encapsulate complex queries
**Lazy Loading vs Eager Loading:** N+1 prevention

---

## Frontend / Markup Skills

### 11-18. HTML & CSS Skills
**Combined Assessment:** "Build a responsive product card"

**Question:** "Create a product card with image, title, price, and buy button"

**Expected Code:**
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Card</title>
    <style>
        /* CSS Reset */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        /* Layout with Flexbox/Grid */
        .container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            padding: 2rem;
        }

        /* Product Card */
        .card {
            display: flex;
            flex-direction: column;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .card-body {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .card-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2563eb;
            margin-bottom: 1rem;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            text-align: center;
            font-weight: 500;
            transition: background 0.2s;
        }

        .btn:hover {
            background: #1d4ed8;
        }

        /* Form styling */
        input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
        }

        /* Table styling */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <article class="card">
            <img src="product.jpg" alt="Product name">
            <div class="card-body">
                <h2 class="card-title">Product Name</h2>
                <p class="card-price">$99.99</p>
                <button class="btn">Add to Cart</button>
            </div>
        </article>
    </div>

    <!-- Form Example -->
    <form action="/submit" method="POST">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <label for="message">Message:</label>
        <textarea id="message" name="message" rows="4"></textarea>

        <button type="submit" class="btn">Submit</button>
    </form>

    <!-- Table Example -->
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Stock</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Product A</td>
                <td>$29.99</td>
                <td>50</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
```

**Assessment Checklist:**
- ✅ Semantic HTML (header, article, section, etc.)
- ✅ Accessibility (alt text, labels, ARIA)
- ✅ Flexbox/Grid for layouts
- ✅ CSS selectors (class, ID, attribute, pseudo)
- ✅ Responsive design (media queries)
- ✅ Form validation attributes
- ✅ Proper table structure (thead, tbody)

**Quick Questions:**
- "What's the difference between `<div>` and `<section>`?" → Semantic meaning
- "How do you center a div?" → Multiple approaches (flex, grid, margin auto)
- "What's specificity?" → Inline > ID > Class > Element
- "What's the box model?" → content, padding, border, margin

---

## Process Management Skills

### 19. Estimation Process
**Question:** "How do you refine estimation based on historical data?"
**Expected Answer:**
- **Velocity Tracking:** Average story points per sprint
- **Confidence Levels:** Use ranges (best case, likely, worst case)
- **Historical Reference:** "Similar to feature X which took Y days"
- **Cone of Uncertainty:** Estimates refine over time
- **Adjust for:** Technical debt, team changes, complexity

**Estimation Techniques:**
- Story points (Fibonacci: 1, 2, 3, 5, 8, 13)
- T-shirt sizes (XS, S, M, L, XL)
- Planning poker (team consensus)
- Three-point estimation (optimistic, likely, pessimistic)

### 20. Risk Management
**Question:** "How do you identify and mitigate project risks?"
**Expected Answer:**

**Risk Register:**
| Risk | Probability | Impact | Mitigation |
|------|------------|--------|------------|
| Key developer leaves | Medium | High | Documentation, pair programming |
| Third-party API down | Low | High | Circuit breaker, fallback |
| Scope creep | High | Medium | Change request process |
| Security vulnerability | Medium | Critical | Regular audits, updates |

**Mitigation Strategies:**
- **Avoid:** Don't use risky technology
- **Reduce:** Code reviews, testing
- **Transfer:** Insurance, SLAs
- **Accept:** Document and monitor

### 21. Technical Debt Policy
**Question:** "How do you manage technical debt?"
**Expected Answer:**
- **Track Debt:** Label issues, estimate cost
- **20% Rule:** Allocate 20% of sprint to tech debt
- **Definition:** Code that slows future development
- **Types:** Deliberate, accidental, bit rot
- **Prioritization:** Business impact × ease of fixing

**Tech Debt Quadrant:**
```
   Reckless    |    Prudent
Deliberate     |    "We must ship now"
"We don't have |    (Accept trade-off)
 time"         |
---------------|----------------
"What's       |    "Now we know
 layering?"    |     better"
Inadvertent    |    (Learn and refactor)
```

### 22-24. Documentation & Requirements
**Question:** "How do you document requirements and validate them?"
**Expected Answer:**

**Requirements Specification:**
```markdown
# Feature: User Authentication

## User Stories
As a user, I want to log in so that I can access my account.

## Acceptance Criteria
- User can log in with email and password
- Invalid credentials show error message
- Successful login redirects to dashboard
- Failed login attempts are rate-limited

## Functional Requirements
- FR1: System shall validate email format
- FR2: System shall hash passwords with bcrypt
- FR3: System shall lock account after 5 failed attempts

## Non-Functional Requirements
- NFR1: Login shall complete within 2 seconds (p95)
- NFR2: System shall handle 1000 concurrent users
- NFR3: Passwords shall meet OWASP guidelines

## Technical Design
- Use Laravel Sanctum for API authentication
- Redis for session storage
- Rate limiting: 5 attempts per 15 minutes

## Test Cases
1. Valid credentials → Success
2. Invalid password → Error
3. Non-existent email → Error
4. 5 failed attempts → Account locked
```

**Validation Techniques:**
- Prototypes and mockups
- User acceptance testing (UAT)
- Peer reviews
- Traceability matrix (requirement → test case)

### 25. Quality Management
**Question:** "How do you improve quality management processes?"
**Expected Answer:**

**Quality Metrics:**
- Code coverage (aim for 80%+)
- Defect density (bugs per 1000 LOC)
- Mean time to resolution (MTTR)
- Customer satisfaction (NPS, CSAT)

**Quality Documentation:**
```markdown
# Quality Management Plan

## Code Quality
- PHPStan level 8
- PSR-12 coding standards
- Minimum 80% test coverage
- All PRs require 2 approvals

## Testing Strategy
- Unit tests for business logic
- Feature tests for user flows
- E2E tests for critical paths
- Load testing before release

## Definition of Done
- [ ] Code reviewed and approved
- [ ] Tests written and passing
- [ ] Documentation updated
- [ ] Security scan passed
- [ ] Deployed to staging
- [ ] Product owner approval
```

### 26. Release Process
**Question:** "Describe your application delivery and release process."
**Expected Answer:**

**Release Pipeline:**
```
Developer → PR → Code Review → CI Tests → Staging → QA → Production

```

**Release Checklist:**
```markdown
- [ ] Feature flags configured
- [ ] Database migrations tested
- [ ] Rollback plan documented
- [ ] Monitoring alerts configured
- [ ] On-call engineer assigned
- [ ] Stakeholders notified
- [ ] Release notes published
```

**Deployment Strategies:**
- **Blue-Green:** Two environments, switch traffic
- **Canary:** Gradual rollout (5% → 25% → 100%)
- **Rolling:** Replace instances one by one
- **Feature Flags:** Toggle features without deployment

---

## Leadership & Mentoring Skills

### 27-28. Conducting Evaluations
**Question:** "How do you conduct job interviews and performance reviews?"

**Job Interview Structure:**
```
1. Introduction (5 min)
   - Role overview
   - Interview structure

2. Experience Discussion (15 min)
   - Walk through resume
   - Dig into specific projects
   - STAR method responses

3. Technical Assessment (30 min)
   - Code review exercise
   - System design question
   - Problem-solving challenge

4. Behavioral Questions (20 min)
   - Teamwork
   - Conflict resolution
   - Learning ability

5. Candidate Questions (10 min)
   - Answer their questions
   - Sell the role

6. Next Steps (5 min)
   - Timeline
   - Feedback process
```

**Performance Review Framework:**
```markdown
# Performance Review: [Name]

## Period: Q1 2024

## Key Achievements
- Delivered feature X ahead of schedule
- Mentored 2 junior developers
- Reduced bug count by 30%

## Areas of Strength
- Technical expertise (9/10)
- Code quality (8/10)
- Communication (7/10)

## Growth Areas
- Public speaking
- Architecture design
- Project estimation

## Goals for Next Quarter
1. Lead architecture for project Y
2. Present at team tech talk
3. Improve estimation accuracy

## Career Development
- Interest in moving toward Staff Engineer
- Recommend: System design course, conference attendance
```

### 29. Contributing to Learning Solutions
**Question:** "How do you contribute to team learning and development?"
**Expected Answer:**
- **Tech Talks:** Monthly presentations on topics
- **Documentation:** Architecture decision records, runbooks
- **Pair Programming:** Knowledge sharing sessions
- **Code Reviews:** Teaching through review comments
- **Lunch & Learns:** Informal learning sessions
- **Internal Wiki:** Centralized knowledge base
- **Mentorship Programs:** Formal mentor/mentee pairs

**Example Learning Initiative:**
```markdown
# Laravel Best Practices Workshop

## Topics
1. Eloquent performance optimization
2. Testing strategies
3. Security best practices
4. Deployment automation

## Format
- 4-week series
- 1 hour per week
- Hands-on exercises
- Open to all developers

## Materials
- Slide decks
- Code examples (GitHub)
- Recorded sessions
- Practice exercises
```

### 30. Sharing Professional Experience
**Question:** "How do you share knowledge at company events?"
**Expected Answer:**
- **Conference Talks:** Internal tech summits
- **Blog Posts:** Company engineering blog
- **Show & Tell:** Demo projects to team
- **Post-Mortems:** Incident learnings
- **Onboarding Sessions:** New hire training
- **Office Hours:** Open Q&A sessions

---

## Generative AI Skills

### 31. Advanced Prompting Strategies
**Question:** "Explain advanced prompt engineering techniques."
**Expected Answer:**

**Zero-Shot Prompting:**
```
Task: Classify sentiment
Input: "I love this product!"
Output: Positive
```

**Few-Shot Prompting:**
```
Classify customer feedback sentiment:

Review: "Great quality, fast shipping" → Positive
Review: "Product broke after one use" → Negative
Review: "It's okay, nothing special" → Neutral
Review: "Amazing! Would buy again" → ?
```

**Chain-of-Thought:**
```
Calculate total: 3 items at $25.99 each, 15% discount

Let's think step by step:
1. Price per item: $25.99
2. Total before discount: $25.99 × 3 = $77.97
3. Discount amount: $77.97 × 0.15 = $11.70
4. Final total: $77.97 - $11.70 = $66.27
```

**ReAct (Reasoning + Acting):**
```
Question: What's the weather in Paris?

Thought: I need to search for current weather
Action: search("Paris weather today")
Observation: Sunny, 22°C
Thought: I have the information needed
Answer: The weather in Paris is sunny with a temperature of 22°C.
```

### 32. Designing Prompt Templates
**Question:** "Create a prompt template for code review."
**Expected Answer:**
```
# Code Review Prompt Template

You are an experienced software engineer conducting a code review.

## Context
- Language: {language}
- Framework: {framework}
- Purpose: {feature_description}

## Code to Review
```{code}```

## Review Criteria
1. Code quality and readability
2. Performance considerations
3. Security vulnerabilities
4. Best practices adherence
5. Test coverage

## Output Format
### Summary
[Brief overview]

### Issues Found
- [ ] **Critical**: [Description + solution]
- [ ] **Major**: [Description + solution]
- [ ] **Minor**: [Description + suggestion]

### Suggestions
- [Improvement 1]
- [Improvement 2]

### Overall Rating
Code quality: X/10
```

### 33. Discovering AI Use Cases
**Question:** "What AI use cases have you identified for development?"
**Expected Answer:**

**Development Use Cases:**
1. **Code Generation:** Boilerplate, CRUD operations, tests
2. **Documentation:** Auto-generate docs from code
3. **Code Review:** Automated suggestions, bug detection
4. **Testing:** Test case generation, edge case discovery
5. **Debugging:** Error explanation, fix suggestions
6. **Refactoring:** Code improvement recommendations
7. **Learning:** Explain complex code, best practices
8. **Database:** Query optimization, schema design
9. **API Design:** OpenAPI spec generation
10. **Security:** Vulnerability scanning, fix recommendations

**Use Case Template:**
```markdown
# Use Case: Automated Test Generation

## Problem
Writing comprehensive tests is time-consuming

## Solution
AI generates unit tests from function signatures and docstrings

## Implementation
```php
// Input: Function with docstring
/**
 * Calculate order total with tax and discount
 * @param float $subtotal
 * @param float $taxRate (0-1)
 * @param float $discount (0-1)
 * @return float
 */
function calculateTotal($subtotal, $taxRate, $discount) {
    $afterDiscount = $subtotal * (1 - $discount);
    return $afterDiscount * (1 + $taxRate);
}

// Output: AI-generated tests
public function test_calculate_total_with_tax_and_discount() {
    $this->assertEquals(108.00, calculateTotal(100, 0.20, 0.10));
}

public function test_calculate_total_no_discount() {
    $this->assertEquals(120.00, calculateTotal(100, 0.20, 0));
}

public function test_calculate_total_edge_cases() {
    $this->assertEquals(0, calculateTotal(0, 0.20, 0.10));
    // More test cases...
}
```

## Impact
- 50% reduction in test writing time
- Better edge case coverage
- Consistent test patterns

## Metrics
- Test coverage: 60% → 85%
- Time saved: 5 hours per week
```

### 34. Refining Prompts & Decomposition
**Question:** "How do you improve prompt accuracy through decomposition?"
**Expected Answer:**

**Problem:** Complex task → Poor results
**Solution:** Break down into subtasks

**Example: Feature Development**

**Bad Prompt (Monolithic):**
```
Build a user authentication system with registration, login,
password reset, email verification, and admin panel.
```

**Good Prompt (Decomposed):**
```
Step 1: Database Schema
Design a users table with email, password_hash, email_verified_at,
remember_token, created_at, updated_at.

Step 2: Registration
Create registration form with email, password, password_confirmation.
Validate email uniqueness and password strength.
Hash password with bcrypt.

Step 3: Email Verification
Generate verification token on registration.
Send verification email.
Verify token endpoint.

Step 4: Login
Authenticate user with email and password.
Generate session/token.
Rate limit login attempts.

Step 5: Password Reset
Request reset link endpoint.
Send reset email with token.
Reset password with valid token.
```

**Prompt Refinement Techniques:**
1. **Add Context:** Role, constraints, examples
2. **Specify Format:** JSON, table, bullet points
3. **Include Examples:** Show desired output
4. **Iterate:** Refine based on results
5. **Use Delimiters:** Separate sections clearly

**Prompt Flow Example:**
```
# Multi-step code review flow

## Step 1: Analyze code structure
{code}

Output: List of functions and their purposes

## Step 2: Check for issues
Based on the analysis above, identify:
- Security vulnerabilities
- Performance bottlenecks
- Code smells

## Step 3: Suggest improvements
For each issue found, provide:
- Specific fix
- Code example
- Explanation

## Step 4: Prioritize
Rank issues by:
1. Critical (security, data loss)
2. Major (performance, maintainability)
3. Minor (style, optimization)
```

---

## Assessment Scoring Rubric (Extended)

### Senior Engineer (5+ years)

**Technical Skills:**
- [ ] Deep expertise in PHP/Laravel
- [ ] Database optimization and security
- [ ] Infrastructure as Code (Terraform/CloudFormation)
- [ ] Container orchestration (Docker/Kubernetes)
- [ ] Performance profiling and optimization

**Process Skills:**
- [ ] Leads technical design discussions
- [ ] Establishes code review standards
- [ ] Improves engineering processes
- [ ] Contributes to technical roadmap

**Leadership Skills:**
- [ ] Mentors mid-level developers
- [ ] Conducts technical interviews
- [ ] Facilitates knowledge sharing
- [ ] Resolves technical conflicts

**AI/Automation Skills:**
- [ ] Uses AI for productivity
- [ ] Creates prompt templates
- [ ] Identifies automation opportunities

**Evaluation:**
- 80%+ technical → Strong Senior
- 60-79% technical + 80% leadership → Senior with leadership potential
- <60% technical → Mid-level

### Staff/Principal Engineer (8+ years)

**Additional Requirements:**
- [ ] Drives architecture decisions across teams
- [ ] Identifies and mitigates technical risks
- [ ] Establishes engineering standards
- [ ] Influences technology strategy
- [ ] Mentors senior engineers
- [ ] Represents engineering in exec meetings
- [ ] Contributes to open source/industry

---

## Quick Phone Screen Questions (15 min)

Use these for initial screening:

1. **Recent Project:** "Describe your most recent project and your role"
2. **Technical Challenge:** "What was the biggest technical challenge and how did you solve it?"
3. **Code Review:** "How do you approach code reviews?"
4. **Learning:** "What's something new you learned in the last 6 months?"
5. **Production Issue:** "Describe a production incident you resolved"
6. **Availability:** "When can you start? Notice period?"
7. **Expectations:** "What are you looking for in your next role?"

**Disqualifiers:**
- Can't explain recent projects clearly
- No experience with listed required technologies
- Poor communication skills
- Unrealistic salary expectations
- Availability mismatch

---

## Practical Assignment (Take-Home)

**Laravel API Development (3-4 hours)**

**Task:** Build a simple blog API

**Requirements:**
1. User authentication (Sanctum)
2. CRUD for posts (title, body, user_id)
3. Comments on posts
4. Tests (Feature + Unit)
5. README with setup instructions

**Evaluation Criteria:**
- [ ] Code quality and structure
- [ ] Database design and relationships
- [ ] API design (RESTful)
- [ ] Authentication implementation
- [ ] Test coverage (>70%)
- [ ] Error handling
- [ ] Documentation
- [ ] Git commit history

**Bonus Points:**
- [ ] Docker setup
- [ ] CI/CD configuration
- [ ] API documentation (OpenAPI)
- [ ] Rate limiting
- [ ] Caching implementation

---

## Red Flags During Interview

**Technical Red Flags:**
- Can't explain code they wrote
- No testing experience
- Unfamiliar with version control
- No understanding of security basics
- Can't discuss trade-offs

**Process Red Flags:**
- Blames others for failures
- No experience with code review
- Doesn't ask clarifying questions
- No interest in learning
- Unrealistic about technical debt

**Cultural Red Flags:**
- Speaks negatively about former employers
- Not interested in collaboration
- Dismissive of junior developers
- Resistant to feedback
- Only motivated by money

---

## Post-Interview Decision Matrix

| Criteria | Weight | Score (1-5) | Weighted |
|----------|--------|-------------|----------|
| Technical Depth | 30% | | |
| Code Quality | 20% | | |
| System Design | 15% | | |
| Communication | 15% | | |
| Cultural Fit | 10% | | |
| Learning Ability | 10% | | |
| **Total** | **100%** | | |

**Decision Thresholds:**
- 4.0+: Strong hire
- 3.5-3.9: Hire
- 3.0-3.4: Borderline (team discussion)
- <3.0: No hire

**Final Check:**
- Would you want this person reviewing your code?
- Would you feel comfortable asking them for help?
- Would they raise the bar for the team?

If "yes" to all three → Hire
