# CLC Factory - Installation & Deployment Guide

## Quick Start

### 1. Clone Repository
```bash
git clone https://github.com/yourusername/clc-factory.git
cd clc-factory
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and configure database:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=clc_factory
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Database Setup
```bash
php artisan migrate
php artisan db:seed
```

### 5. Run Tests
```bash
php artisan test
```

### 6. Start Development Server
```bash
php artisan serve
```

Application available at `http://localhost:8000/api/sync`

---

## Production Deployment

### Docker

#### Build Image
```bash
docker build -t clc-factory:latest .
```

#### Run Container
```bash
docker run -d \
  --name clc-factory \
  -p 8000:8000 \
  -e DB_HOST=db.example.com \
  -e DB_DATABASE=clc_factory \
  -e DB_USERNAME=user \
  -e DB_PASSWORD=secret \
  clc-factory:latest
```

### Docker Compose

```yaml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "8000:8000"
    environment:
      APP_ENV: production
      APP_DEBUG: false
      DB_HOST: db
      DB_DATABASE: clc_factory
    depends_on:
      - db

  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: clc_factory
      MYSQL_ROOT_PASSWORD: rootpassword
    volumes:
      - db_data:/var/lib/mysql

  redis:
    image: redis:7-alpine

volumes:
  db_data:
```

Start with:
```bash
docker-compose up -d
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

---

## Cloud Deployment

### Heroku

```bash
heroku create clc-factory
heroku config:set APP_KEY=$(php artisan key:generate --show)
heroku addons:create cleardb:ignite
git push heroku main
heroku run php artisan migrate
heroku run php artisan db:seed
```

### AWS Lambda + RDS

1. Create RDS MySQL instance
2. Deploy to Lambda with Bref
3. Configure environment variables
4. Run migrations

### DigitalOcean App Platform

1. Connect GitHub repository
2. Set environment variables in `.env`
3. Configure database (DigitalOcean MySQL)
4. Deploy

---

## Testing

### Unit Tests
```bash
php artisan test --unit
```

### Feature Tests
```bash
php artisan test --feature
```

### Coverage Report
```bash
php artisan test --coverage
```

### Static Analysis
```bash
./vendor/bin/phpstan analyse
```

### Code Style
```bash
./vendor/bin/pint
```

---

## Monitoring

### View Tunnel Logs
```bash
php artisan tinker
>>> TunnelLog::latest()->limit(50)->get()
```

### Database Stats
```bash
# Total requests
>>> TunnelLog::count()

# Requests by caller type
>>> TunnelLog::where('caller_mask', 0x0100)->count() // BOTs
>>> TunnelLog::where('caller_mask', 0x0200)->count() // Auth
>>> TunnelLog::where('caller_mask', 0x0400)->count() // Attacker

# Success rate
>>> TunnelLog::where('response_code', 200)->count() / TunnelLog::count()

# Average response time
>>> TunnelLog::avg('execution_time_ms')
```

---

## Troubleshooting

### Database Connection Error
```bash
# Test connection
php artisan db:show

# Check .env
cat .env | grep DB_
```

### Migration Failed
```bash
# Reset database
php artisan migrate:reset

# Re-migrate
php artisan migrate
php artisan db:seed
```

### Tests Failing
```bash
# Ensure test database is clean
php artisan migrate:refresh --env=testing

# Run tests with verbose output
php artisan test --verbose
```

### Cache Issues
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Performance Tuning

### Enable Caching
```env
CACHE_DRIVER=redis
```

### Optimize Autoloader
```bash
composer install --optimize-autoloader --no-dev
```

### Cache Configuration
```bash
php artisan config:cache
php artisan route:cache
```

### Database Indexes
Already configured in migrations (coordinate_key, bitmask_policy, etc)

---

## Security Checklist

- [ ] `.env` is in `.gitignore`
- [ ] `APP_DEBUG=false` in production
- [ ] `APP_ENV=production`
- [ ] Strong database password
- [ ] SSL/TLS configured
- [ ] Firewall rules configured
- [ ] Regular backups enabled
- [ ] Error logs monitored
- [ ] Access logs enabled

---

## Support & Documentation

- **README.md** - API usage and architecture
- **FACTORY_SPECIFICATION.md** - Complete system specification
- **ATOMIC_COORDINATE.md** - Coordinate format details
- **LARAVEL_BIT_DRIVEN.md** - Implementation patterns

---

**Last Updated:** 2024
**Version:** 1.0.0
