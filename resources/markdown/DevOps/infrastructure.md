# Infrastructure & Cloud Deployment

## Question 1: How do you handle environment variables and secrets securely?

**Answer:**

### Environment Variables Management

```bash
# Local Development (.env - NOT committed)
APP_ENV=local
APP_DEBUG=true
DB_PASSWORD=local_password
API_KEY=dev_key_123

# Production (.env - deployed securely)
APP_ENV=production
APP_DEBUG=false
DB_PASSWORD=${DB_PASSWORD}  # Injected from secrets manager
API_KEY=${API_KEY}
```

### Secrets Management Solutions

#### 1. AWS Secrets Manager

```bash
# Store secret
aws secretsmanager create-secret \
    --name prod/db/password \
    --secret-string "super-secret-password"

# Rotate secret automatically
aws secretsmanager rotate-secret \
    --secret-id prod/db/password \
    --rotation-lambda-arn arn:aws:lambda:...

# Retrieve in application
aws secretsmanager get-secret-value \
    --secret-id prod/db/password
```

```php
// PHP SDK
use Aws\SecretsManager\SecretsManagerClient;

$client = new SecretsManagerClient([
    'region' => 'us-east-1',
    'version' => 'latest'
]);

$result = $client->getSecretValue([
    'SecretId' => 'prod/db/password'
]);

$secret = json_decode($result['SecretString'], true);
$password = $secret['password'];
```

#### 2. HashiCorp Vault

```bash
# Store secret
vault kv put secret/database \
    username="admin" \
    password="secret123"

# Read secret
vault kv get secret/database

# Dynamic secrets (database credentials)
vault write database/roles/my-role \
    db_name=my-database \
    creation_statements="CREATE USER '{{name}}'..." \
    default_ttl="1h" \
    max_ttl="24h"

# Get temporary credentials
vault read database/creds/my-role
```

```php
// Laravel Vault integration
// config/database.php
'mysql' => [
    'host' => env('DB_HOST'),
    'username' => vault('secret/database:username'),
    'password' => vault('secret/database:password'),
],
```

#### 3. Kubernetes Secrets

```yaml
# secret.yaml
apiVersion: v1
kind: Secret
metadata:
  name: app-secrets
type: Opaque
data:
  db-password: c3VwZXJzZWNyZXQ=  # base64 encoded
  api-key: YXBpLWtleS0xMjM=

# deployment.yaml
apiVersion: apps/v1
kind: Deployment
spec:
  template:
    spec:
      containers:
      - name: app
        env:
        - name: DB_PASSWORD
          valueFrom:
            secretKeyRef:
              name: app-secrets
              key: db-password
        # Or mount as file
        volumeMounts:
        - name: secrets
          mountPath: "/etc/secrets"
          readOnly: true
      volumes:
      - name: secrets
        secret:
          secretName: app-secrets
```

#### 4. Azure Key Vault

```bash
# Create vault
az keyvault create \
    --name myKeyVault \
    --resource-group myResourceGroup

# Store secret
az keyvault secret set \
    --vault-name myKeyVault \
    --name dbPassword \
    --value "secret123"

# Get secret
az keyvault secret show \
    --vault-name myKeyVault \
    --name dbPassword
```

### Best Practices

```php
// ✅ Good: Never hardcode secrets
class PaymentService
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = env('STRIPE_SECRET_KEY');
    }
}

// ❌ Bad: Hardcoded secret
class PaymentService
{
    private string $apiKey = 'sk_live_abc123';  // NEVER!
}

// ✅ Good: Use different secrets per environment
// .env.local
STRIPE_KEY=sk_test_...

// .env.production
STRIPE_KEY=sk_live_...

// ✅ Good: Rotate secrets regularly
// Set expiration and rotation policy
$this->scheduleSecretRotation('stripe-api-key', days: 90);

// ✅ Good: Audit secret access
Log::info('Secret accessed', [
    'secret_name' => 'stripe-api-key',
    'accessed_by' => auth()->id(),
    'ip' => request()->ip(),
]);
```

### CI/CD Integration

```yaml
# GitHub Actions
name: Deploy

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2

      # Use GitHub Secrets
      - name: Deploy
        env:
          DB_PASSWORD: ${{ secrets.DB_PASSWORD }}
          API_KEY: ${{ secrets.API_KEY }}
        run: |
          echo "DB_PASSWORD=${DB_PASSWORD}" >> .env
          php artisan deploy

# GitLab CI
deploy:
  script:
    - echo "DB_PASSWORD=${DB_PASSWORD}" >> .env
    - php artisan deploy
  variables:
    DB_PASSWORD: $DB_PASSWORD  # From GitLab CI/CD variables
```

### Secret Scanning

```bash
# Prevent commits with secrets
# Install git-secrets
git secrets --install
git secrets --register-aws

# Scan repository
git secrets --scan

# Pre-commit hook
#!/bin/sh
if git diff --cached | grep -E 'password|api_key|secret'; then
    echo "❌ Possible secret detected!"
    exit 1
fi
```

**Follow-up:**
- How do you rotate secrets without downtime?
- What's the difference between Vault and Secrets Manager?
- How do you handle secrets in local development?

**Key Points:**
- Never commit secrets to version control
- Use secrets management services (Vault, AWS Secrets Manager)
- Rotate secrets regularly
- Different secrets per environment
- Audit secret access
- Secret scanning in CI/CD

---

## Question 2: How do you configure containers for cloud deployment?

**Answer:**

### Docker Configuration

```dockerfile
# Dockerfile
FROM php:8.2-fpm-alpine

# Install dependencies
RUN apk add --no-cache \
    nginx \
    mysql-client \
    nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql opcache

# Configure PHP
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Set working directory
WORKDIR /var/www/html

# Copy application
COPY . .

# Install Composer dependencies
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# Install NPM dependencies and build
RUN npm ci && npm run build

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage
RUN chmod -R 775 /var/www/html/storage

# Expose port
EXPOSE 9000

# Health check
HEALTHCHECK --interval=30s --timeout=3s \
  CMD php artisan health:check || exit 1

# Start PHP-FPM
CMD ["php-fpm"]
```

### Multi-Stage Builds (Optimization)

```dockerfile
# Build stage
FROM node:18 AS node-builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY resources/ ./resources/
RUN npm run build

# PHP dependencies stage
FROM composer:latest AS composer-builder
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --optimize-autoloader

# Production stage
FROM php:8.2-fpm-alpine
WORKDIR /var/www/html

# Copy built assets
COPY --from=node-builder /app/public/build ./public/build
COPY --from=composer-builder /app/vendor ./vendor

# Copy application
COPY . .

# Configure and run
RUN chown -R www-data:www-data storage bootstrap/cache
CMD ["php-fpm"]
```

### Docker Compose (Local Development)

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    volumes:
      - .:/var/www/html
      - ./docker/php.ini:/usr/local/etc/php/conf.d/custom.ini
    environment:
      - APP_ENV=local
      - DB_HOST=mysql
      - REDIS_HOST=redis
    depends_on:
      - mysql
      - redis
    networks:
      - app-network

  nginx:
    image: nginx:alpine
    ports:
      - "8000:80"
    volumes:
      - .:/var/www/html
      - ./docker/nginx.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app
    networks:
      - app-network

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_DATABASE: laravel
    volumes:
      - mysql-data:/var/lib/mysql
    ports:
      - "3306:3306"
    networks:
      - app-network

  redis:
    image: redis:alpine
    ports:
      - "6379:6379"
    networks:
      - app-network

volumes:
  mysql-data:

networks:
  app-network:
    driver: bridge
```

### Kubernetes Deployment

```yaml
# deployment.yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: laravel-app
  labels:
    app: laravel
spec:
  replicas: 3
  selector:
    matchLabels:
      app: laravel
  template:
    metadata:
      labels:
        app: laravel
    spec:
      containers:
      - name: app
        image: myregistry/laravel-app:1.0.0
        ports:
        - containerPort: 9000
        env:
        - name: APP_ENV
          value: "production"
        - name: DB_HOST
          value: "mysql-service"
        - name: DB_PASSWORD
          valueFrom:
            secretKeyRef:
              name: app-secrets
              key: db-password
        resources:
          requests:
            memory: "256Mi"
            cpu: "250m"
          limits:
            memory: "512Mi"
            cpu: "500m"
        livenessProbe:
          httpGet:
            path: /health
            port: 9000
          initialDelaySeconds: 30
          periodSeconds: 10
        readinessProbe:
          httpGet:
            path: /ready
            port: 9000
          initialDelaySeconds: 5
          periodSeconds: 5
        volumeMounts:
        - name: storage
          mountPath: /var/www/html/storage
      volumes:
      - name: storage
        persistentVolumeClaim:
          claimName: app-storage-pvc

---
# service.yaml
apiVersion: v1
kind: Service
metadata:
  name: laravel-service
spec:
  selector:
    app: laravel
  ports:
  - protocol: TCP
    port: 80
    targetPort: 9000
  type: LoadBalancer

---
# ingress.yaml
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: laravel-ingress
  annotations:
    cert-manager.io/cluster-issuer: "letsencrypt-prod"
spec:
  tls:
  - hosts:
    - myapp.example.com
    secretName: laravel-tls
  rules:
  - host: myapp.example.com
    http:
      paths:
      - path: /
        pathType: Prefix
        backend:
          service:
            name: laravel-service
            port:
              number: 80
```

### ECS (AWS Elastic Container Service)

```json
{
  "family": "laravel-app",
  "networkMode": "awsvpc",
  "requiresCompatibilities": ["FARGATE"],
  "cpu": "256",
  "memory": "512",
  "containerDefinitions": [
    {
      "name": "app",
      "image": "123456789.dkr.ecr.us-east-1.amazonaws.com/laravel:latest",
      "portMappings": [
        {
          "containerPort": 9000,
          "protocol": "tcp"
        }
      ],
      "environment": [
        {
          "name": "APP_ENV",
          "value": "production"
        }
      ],
      "secrets": [
        {
          "name": "DB_PASSWORD",
          "valueFrom": "arn:aws:secretsmanager:us-east-1:123456789:secret:prod/db/password"
        }
      ],
      "logConfiguration": {
        "logDriver": "awslogs",
        "options": {
          "awslogs-group": "/ecs/laravel-app",
          "awslogs-region": "us-east-1",
          "awslogs-stream-prefix": "ecs"
        }
      },
      "healthCheck": {
        "command": ["CMD-SHELL", "curl -f http://localhost:9000/health || exit 1"],
        "interval": 30,
        "timeout": 5,
        "retries": 3,
        "startPeriod": 60
      }
    }
  ]
}
```

### Container Best Practices

```dockerfile
# ✅ Use specific versions (not 'latest')
FROM php:8.2.10-fpm-alpine

# ✅ Minimize layers
RUN apk add --no-cache nginx mysql-client \
    && docker-php-ext-install pdo pdo_mysql opcache

# ❌ Multiple RUN commands create more layers
RUN apk add --no-cache nginx
RUN apk add --no-cache mysql-client
RUN docker-php-ext-install pdo

# ✅ Use .dockerignore
# .dockerignore
node_modules/
.git/
.env
tests/
*.log

# ✅ Run as non-root user
RUN addgroup -g 1000 appuser && \
    adduser -D -u 1000 -G appuser appuser
USER appuser

# ✅ Use COPY instead of ADD
COPY . /var/www/html

# ✅ Clean up in same layer
RUN apk add --no-cache build-base \
    && composer install \
    && apk del build-base

# ✅ Use multi-stage builds
FROM node:18 AS builder
# Build assets
FROM php:8.2-fpm-alpine
COPY --from=builder /app/public/build ./public/build
```

**Follow-up:**
- How do you optimize Docker image size?
- What's the difference between CMD and ENTRYPOINT?
- How do you handle database migrations in containers?

**Key Points:**
- Multi-stage builds for smaller images
- Use specific version tags (not latest)
- Run as non-root user
- Health checks for reliability
- Resource limits (CPU, memory)
- Secrets via environment variables
- Use .dockerignore to exclude files

---

## Question 3: How do you manage and provision infrastructure through code (IaC)?

**Answer:**

### Terraform

```hcl
# main.tf
terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }

  backend "s3" {
    bucket = "terraform-state-bucket"
    key    = "prod/terraform.tfstate"
    region = "us-east-1"
    encrypt = true
  }
}

provider "aws" {
  region = var.aws_region
}

# VPC
resource "aws_vpc" "main" {
  cidr_block           = "10.0.0.0/16"
  enable_dns_hostnames = true

  tags = {
    Name        = "main-vpc"
    Environment = var.environment
  }
}

# Subnets
resource "aws_subnet" "public" {
  count             = 2
  vpc_id            = aws_vpc.main.id
  cidr_block        = "10.0.${count.index + 1}.0/24"
  availability_zone = data.aws_availability_zones.available.names[count.index]

  tags = {
    Name = "public-subnet-${count.index + 1}"
  }
}

# RDS Database
resource "aws_db_instance" "main" {
  identifier           = "laravel-db"
  engine              = "mysql"
  engine_version      = "8.0"
  instance_class      = "db.t3.micro"
  allocated_storage   = 20
  storage_encrypted   = true

  db_name  = var.db_name
  username = var.db_username
  password = var.db_password

  vpc_security_group_ids = [aws_security_group.db.id]
  db_subnet_group_name   = aws_db_subnet_group.main.name

  backup_retention_period = 7
  skip_final_snapshot    = false
  final_snapshot_identifier = "laravel-db-final-snapshot"

  tags = {
    Name = "laravel-database"
  }
}

# ECS Cluster
resource "aws_ecs_cluster" "main" {
  name = "laravel-cluster"

  setting {
    name  = "containerInsights"
    value = "enabled"
  }
}

# ECS Task Definition
resource "aws_ecs_task_definition" "app" {
  family                   = "laravel-app"
  network_mode             = "awsvpc"
  requires_compatibilities = ["FARGATE"]
  cpu                      = "256"
  memory                   = "512"
  execution_role_arn       = aws_iam_role.ecs_execution_role.arn

  container_definitions = jsonencode([
    {
      name  = "app"
      image = "${aws_ecr_repository.app.repository_url}:latest"
      portMappings = [
        {
          containerPort = 9000
          protocol      = "tcp"
        }
      ]
      environment = [
        {
          name  = "APP_ENV"
          value = var.environment
        }
      ]
      secrets = [
        {
          name      = "DB_PASSWORD"
          valueFrom = aws_secretsmanager_secret.db_password.arn
        }
      ]
      logConfiguration = {
        logDriver = "awslogs"
        options = {
          "awslogs-group"         = aws_cloudwatch_log_group.app.name
          "awslogs-region"        = var.aws_region
          "awslogs-stream-prefix" = "ecs"
        }
      }
    }
  ])
}

# Variables
variable "environment" {
  description = "Environment name"
  type        = string
  default     = "production"
}

variable "aws_region" {
  description = "AWS region"
  type        = string
  default     = "us-east-1"
}

variable "db_password" {
  description = "Database password"
  type        = string
  sensitive   = true
}

# Outputs
output "db_endpoint" {
  value       = aws_db_instance.main.endpoint
  description = "Database endpoint"
}

output "ecs_cluster_name" {
  value       = aws_ecs_cluster.main.name
  description = "ECS cluster name"
}
```

```bash
# Terraform workflow
terraform init          # Initialize
terraform plan          # Preview changes
terraform apply         # Apply changes
terraform destroy       # Destroy infrastructure

# Use workspaces for environments
terraform workspace new production
terraform workspace new staging
terraform workspace select production
```

### AWS CloudFormation

```yaml
# template.yaml
AWSTemplateFormatVersion: '2010-09-09'
Description: Laravel Application Infrastructure

Parameters:
  Environment:
    Type: String
    Default: production
    AllowedValues:
      - production
      - staging

Resources:
  # VPC
  VPC:
    Type: AWS::EC2::VPC
    Properties:
      CidrBlock: 10.0.0.0/16
      EnableDnsHostnames: true
      Tags:
        - Key: Name
          Value: !Sub ${Environment}-vpc

  # Database
  Database:
    Type: AWS::RDS::DBInstance
    Properties:
      DBInstanceIdentifier: !Sub ${Environment}-db
      Engine: mysql
      EngineVersion: '8.0'
      DBInstanceClass: db.t3.micro
      AllocatedStorage: 20
      StorageEncrypted: true
      MasterUsername: !Ref DBUsername
      MasterUserPassword: !Ref DBPassword
      BackupRetentionPeriod: 7
      PreferredBackupWindow: '03:00-04:00'
      Tags:
        - Key: Environment
          Value: !Ref Environment

  # ElastiCache Redis
  CacheCluster:
    Type: AWS::ElastiCache::CacheCluster
    Properties:
      CacheNodeType: cache.t3.micro
      Engine: redis
      NumCacheNodes: 1
      VpcSecurityGroupIds:
        - !Ref CacheSecurityGroup

Outputs:
  DatabaseEndpoint:
    Description: Database endpoint
    Value: !GetAtt Database.Endpoint.Address
    Export:
      Name: !Sub ${Environment}-db-endpoint

  CacheEndpoint:
    Description: Redis endpoint
    Value: !GetAtt CacheCluster.RedisEndpoint.Address
```

```bash
# CloudFormation commands
aws cloudformation create-stack \
  --stack-name laravel-prod \
  --template-body file://template.yaml \
  --parameters ParameterKey=Environment,ParameterValue=production

aws cloudformation update-stack \
  --stack-name laravel-prod \
  --template-body file://template.yaml

aws cloudformation delete-stack \
  --stack-name laravel-prod
```

### Pulumi (Code-based IaC)

```typescript
// index.ts
import * as pulumi from "@pulumi/pulumi";
import * as aws from "@pulumi/aws";

// VPC
const vpc = new aws.ec2.Vpc("main-vpc", {
    cidrBlock: "10.0.0.0/16",
    enableDnsHostnames: true,
    tags: {
        Name: "main-vpc",
    },
});

// RDS Database
const dbSubnetGroup = new aws.rds.SubnetGroup("db-subnet-group", {
    subnetIds: [subnet1.id, subnet2.id],
});

const database = new aws.rds.Instance("laravel-db", {
    engine: "mysql",
    engineVersion: "8.0",
    instanceClass: "db.t3.micro",
    allocatedStorage: 20,
    storageEncrypted: true,
    dbName: "laravel",
    username: "admin",
    password: config.requireSecret("db-password"),
    dbSubnetGroupName: dbSubnetGroup.name,
    backupRetentionPeriod: 7,
    skipFinalSnapshot: false,
});

// ECS Cluster
const cluster = new aws.ecs.Cluster("laravel-cluster", {
    name: "laravel-cluster",
    settings: [{
        name: "containerInsights",
        value: "enabled",
    }],
});

// Export outputs
export const dbEndpoint = database.endpoint;
export const clusterName = cluster.name;
```

```bash
# Pulumi workflow
pulumi login
pulumi stack init production
pulumi config set aws:region us-east-1
pulumi up        # Preview and apply
pulumi destroy   # Destroy resources
```

### Best Practices

```hcl
# ✅ Use modules for reusability
module "vpc" {
  source = "./modules/vpc"

  environment = var.environment
  cidr_block  = "10.0.0.0/16"
}

module "database" {
  source = "./modules/rds"

  vpc_id      = module.vpc.vpc_id
  subnet_ids  = module.vpc.private_subnet_ids
  environment = var.environment
}

# ✅ Use remote state
terraform {
  backend "s3" {
    bucket         = "terraform-state"
    key            = "prod/terraform.tfstate"
    region         = "us-east-1"
    encrypt        = true
    dynamodb_table = "terraform-locks"
  }
}

# ✅ Use data sources
data "aws_ami" "amazon_linux" {
  most_recent = true
  owners      = ["amazon"]

  filter {
    name   = "name"
    values = ["amzn2-ami-hvm-*-x86_64-gp2"]
  }
}

# ✅ Tag everything
tags = merge(
  var.common_tags,
  {
    Environment = var.environment
    ManagedBy   = "Terraform"
  }
)
```

**Follow-up:**
- How do you manage Terraform state?
- What's the difference between Terraform and CloudFormation?
- How do you handle secrets in IaC?

**Key Points:**
- IaC = version-controlled infrastructure
- Terraform: Multi-cloud, HCL language
- CloudFormation: AWS-specific, YAML/JSON
- Pulumi: Real programming languages
- Use modules for reusability
- Remote state management
- Plan before apply
- Tag all resources

---

## Notes

Add more questions covering:
- Container orchestration (Kubernetes deep dive)
- Service mesh (Istio, Linkerd)
- Serverless frameworks (Lambda, Fargate)
- Infrastructure monitoring
- Cost optimization
- Disaster recovery
