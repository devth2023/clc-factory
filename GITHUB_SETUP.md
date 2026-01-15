# GitHub Upload Instructions

## Steps to Upload CLC Factory to GitHub

### 1. Create New Repository on GitHub

Visit https://github.com/new and:
- Repository name: `clc-factory` (or your preferred name)
- Description: "Coordinate Logic Core - Production-Grade Bit-Driven Architecture"
- Make it **Private** (for proprietary code)
- Do NOT initialize with README (you have one)
- Click "Create Repository"

### 2. Initialize Local Git Repository

```bash
cd c:/Users/acer/Pure\ sembolic/CLC/laravel

# Initialize git
git init

# Add all files
git add .

# Create initial commit
git commit -m "Initial commit: CLC Factory v1.0.0

- BitMaskEngine: Core atomic bitwise operations
- CoordinateResolver: 3-layer registry resolution
- CallerDetector: Bot/Auth/Attacker identification
- ProjectionRenderer: Glossary/Private/Deception shadows
- TunnelController: Single /sync endpoint
- Complete test suite (30+ tests)
- Production-ready with PHPStan Level 9
- Docker & Docker Compose support
- Database migrations & seeders"
```

### 3. Add Remote and Push

Replace `YOUR_USERNAME` and use the URL from GitHub:

```bash
# Add remote
git remote add origin https://github.com/YOUR_USERNAME/clc-factory.git

# Set default branch to main
git branch -M main

# Push to GitHub
git push -u origin main
```

Or with SSH (if configured):

```bash
git remote add origin git@github.com:YOUR_USERNAME/clc-factory.git
git branch -M main
git push -u origin main
```

### 4. Verify Upload

Visit: `https://github.com/YOUR_USERNAME/clc-factory`

You should see:
- ✅ All PHP files
- ✅ Migrations
- ✅ Tests
- ✅ Configuration files
- ✅ README.md, INSTALLATION.md
- ✅ Dockerfile, docker-compose.yml
- ✅ composer.json with dependencies
- ✅ .gitignore (hides .env, vendor, etc)

---

## Setup GitHub Branch Protection (Recommended)

1. Go to **Settings** → **Branches**
2. Add rule for `main` branch
3. Enable:
   - "Require pull request reviews before merging"
   - "Require status checks to pass before merging"
   - "Require branches to be up to date before merging"

---

## Setup CI/CD with GitHub Actions

Create `.github/workflows/test.yml`:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_DATABASE: clc_factory_test
          MYSQL_ROOT_PASSWORD: root
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
        ports:
          - 3306:3306

    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
          extensions: mbstring, json, pdo, pdo_mysql
          coverage: pcov
      
      - name: Install Dependencies
        run: composer install
      
      - name: Run Tests
        run: php artisan test --coverage
        env:
          DB_HOST: localhost
          DB_DATABASE: clc_factory_test
          DB_USERNAME: root
          DB_PASSWORD: root
      
      - name: Run PHPStan
        run: ./vendor/bin/phpstan analyse
      
      - name: Run Pint
        run: ./vendor/bin/pint --test
```

---

## Deployment via GitHub

### Option 1: Manual Deployment

After pushing to GitHub:

```bash
# On production server
git clone https://github.com/YOUR_USERNAME/clc-factory.git
cd clc-factory

composer install --no-dev --optimize-autoloader

php artisan migrate
php artisan db:seed

php artisan serve
```

### Option 2: Automatic Deployment via GitHub Actions + SSH

Create `.github/workflows/deploy.yml`:

```yaml
name: Deploy

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Deploy to Production
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.HOST }}
          username: ${{ secrets.USERNAME }}
          key: ${{ secrets.SSH_KEY }}
          script: |
            cd /var/www/clc-factory
            git pull origin main
            composer install --no-dev
            php artisan migrate
            php artisan cache:clear
```

### Option 3: Docker Hub Integration

Push Docker image automatically:

```yaml
name: Docker Build and Push

on:
  push:
    branches: [main]

jobs:
  build:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v2
      
      - name: Login to Docker Hub
        uses: docker/login-action@v2
        with:
          username: ${{ secrets.DOCKER_USERNAME }}
          password: ${{ secrets.DOCKER_PASSWORD }}
      
      - name: Build and Push
        uses: docker/build-push-action@v4
        with:
          context: .
          push: true
          tags: ${{ secrets.DOCKER_USERNAME }}/clc-factory:latest
```

---

## Secrets Setup for CI/CD

In GitHub **Settings** → **Secrets and variables** → **Actions**, add:

```
HOST              = production.server.com
USERNAME          = deploy_user
SSH_KEY           = (private key for SSH)
DOCKER_USERNAME   = your_dockerhub_username
DOCKER_PASSWORD   = your_dockerhub_password
```

---

## Project Setup Checklist

- [ ] GitHub repository created
- [ ] Local git initialized
- [ ] Remote added (`git remote -v` to verify)
- [ ] All files committed and pushed
- [ ] .env file is NOT in repository (.gitignore)
- [ ] Branch protection rules configured
- [ ] GitHub Actions workflows created
- [ ] Secrets configured for CI/CD
- [ ] README displays correctly on GitHub
- [ ] Tests pass in GitHub Actions

---

## Update Local After GitHub Setup

```bash
# Ensure local is synced
git status

# Pull latest from GitHub
git pull origin main

# Update .env for local development
cp .env.example .env.local
```

---

## Quick Reference

| Command | Purpose |
|---------|---------|
| `git status` | Check current status |
| `git add .` | Stage all changes |
| `git commit -m "message"` | Create commit |
| `git push origin main` | Push to GitHub |
| `git pull origin main` | Pull from GitHub |
| `git log --oneline` | View commit history |
| `git branch -a` | View all branches |

---

**After upload is complete, the system is ready for:**
- ✅ Team collaboration
- ✅ CI/CD pipelines
- ✅ Automated testing
- ✅ Automatic deployment
- ✅ Version control & rollback
- ✅ Production monitoring

---

Contact: your-email@example.com
License: Proprietary - Production Use Only
