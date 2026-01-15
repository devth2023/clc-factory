# CLC Factory - Coordinate Logic Core (Production)

A production-grade Laravel implementation of the Coordinate Logic Core system following the three iron laws of bit-driven architecture.

## Philosophy

The CLC system implements three core principles:

1. **No Semantic in Core** - All logic is driven by bitmasks, not string labels
2. **API-Less Architecture** - Single tunnel entry point (`/sync`), no semantic routing
3. **Projection over Transmission** - Data is projected based on caller identity, never sent raw

## Architecture

```
Request → Tunnel Controller (/sync)
    ↓
Caller Detector (identify bot/auth/attacker)
    ↓
Coordinate Resolver (resolve 3 layers: glossary, address, bitmask)
    ↓
Projection Renderer (select glossary/private/deception)
    ↓
Response (shadow data only)
```

## System Requirements

- PHP 8.3+
- Laravel 11.0+
- MySQL 8.0+ or PostgreSQL 15+
- Redis (optional, for caching)

## Installation

### 1. Clone Repository

```bash
git clone <repository-url> clc-factory
cd clc-factory
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

Configure database in `.env`:

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

This will create:
- `coordinate_mappings` - 3-layer registry
- `entities` - Bit-driven state storage
- `bit_registry` - Bit position documentation
- `projections` - Shadow data (glossary/private/deception)
- `tunnel_logs` - Audit trail

### 5. Start Application

```bash
php artisan serve
```

Application runs on `http://localhost:8000`

## API Usage

### Single Tunnel Endpoint: `/api/sync`

**Request:**
```bash
curl -X POST http://localhost:8000/api/sync \
  -H "Content-Type: application/json" \
  -H "User-Agent: Mozilla/5.0 (compatible; Googlebot/2.1)" \
  -d '{"target": "COORD_X101"}'
```

**Response (SEO Bot):**
```json
{
  "status": 200,
  "request_id": "550e8400-e29b-41d4-a716-446655440000",
  "data": {
    "type": "glossary",
    "data": {
      "label": "User_Profile_Name",
      "description": "ชื่อจริงสำหรับแสดงผล",
      "keywords": ["user", "profile", "name"],
      "schema": "Person"
    },
    "mask": "0x100"
  }
}
```

**Response (Authenticated User):**
```bash
curl -X POST http://localhost:8000/api/sync \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer valid_token" \
  -d '{"target": "COORD_X101"}'
```

```json
{
  "status": 200,
  "request_id": "550e8400-e29b-41d4-a716-446655440001",
  "data": {
    "type": "private",
    "data": {
      "coordinate_key": "COORD_X101",
      "address": "1010.01010@",
      "bitmask": "1",
      "version": 1
    },
    "mask": "0x200"
  }
}
```

**Response (Unknown Caller):**
```json
{
  "status": 200,
  "request_id": "550e8400-e29b-41d4-a716-446655440002",
  "data": {
    "type": "deception",
    "data": {
      "error": "HoneyPot_Data_UserName"
    },
    "mask": "0x400"
  }
}
```

### Request Validation

| Field | Required | Type | Constraints |
|-------|----------|------|-------------|
| `target` | Yes | string | 1-64 chars, uppercase/numbers/underscores only |
| `payload` | No | JSON | Valid JSON object |

### Caller Detection

Callers are automatically identified:

| User-Agent Pattern | Type | Projection |
|-------------------|------|-----------|
| googlebot, bingbot, slurp, etc | SEO Bot | Glossary (public metadata) |
| Authorization: Bearer/Token | Authenticated | Private (full data) |
| None of above | Attacker | Deception (honeypot) |

## Development

### Running Tests

```bash
# All tests
php artisan test

# Unit tests only
php artisan test --unit

# Feature tests only
php artisan test --feature

# With coverage
php artisan test --coverage
```

### Code Quality

```bash
# Static analysis
./vendor/bin/phpstan analyse

# Code style check
./vendor/bin/pint --test

# Fix code style
./vendor/bin/pint
```

### Database

```bash
# Migrate
php artisan migrate

# Rollback
php artisan migrate:rollback

# Seed
php artisan db:seed

# Seed specific seeder
php artisan db:seed --class=CoordinateMappingSeeder
```

## Services

### BitMaskEngine
Core atomic bitwise operations (O(1) complexity).

```php
$engine = app(BitMaskEngine::class);

// Set bit 5
$flags = $engine->setBit(0, 5); // 0b00100000

// Check if bit 5 is set
$engine->hasBit($flags, 5); // true

// Apply mask
$result = $engine->applyMask($flags, 0xFF);

// Build mask from positions
$mask = $engine->buildMask(BitPosition::IS_ACTIVE, BitPosition::CAN_READ);
```

### CoordinateResolver
Resolves coordinates through all 3 layers.

```php
$resolver = app(CoordinateResolver::class);

// Full resolution
$coordData = $resolver->resolve('COORD_X101');

// Get only mask (Layer 3)
$mask = $resolver->resolveMask('COORD_X101');

// Get only glossary (Layer 1)
$glossary = $resolver->resolveGlossary('COORD_X101');

// Get only address (Layer 2)
$address = $resolver->resolveAddress('COORD_X101');
```

### CallerDetector
Identifies caller type from request.

```php
$detector = app(CallerDetector::class);

$callerMask = $detector->detect($request);
// Returns: 0x0100 (bot), 0x0200 (auth), or 0x0400 (attacker)

// Get human label
$label = $detector->getLabel($callerMask); // "SEO Bot"
```

### ProjectionRenderer
Renders shadow data based on caller type.

```php
$renderer = app(ProjectionRenderer::class);

$projection = $renderer->render(
    'COORD_X101',
    $coordinateData,
    $callerMask
);

// Projection contains:
// - type: 'glossary' | 'private' | 'deception'
// - data: Shadow payload
// - mask: Hexadecimal caller mask
```

## Database Schema

### coordinate_mappings
```
id (PK)
coordinate_key (UNIQUE)           # Layer 1+2 ref
label                             # Layer 1
description                       # Layer 1
seo_keywords (JSON)               # Layer 1
schema_type                       # Layer 1
coordinate_address (UNIQUE)       # Layer 2 (XXXX.YYYY@)
bitmask_policy                    # Layer 3 (The Truth)
version
is_active
timestamps
```

### entities
```
id (PK)
entity_type
entity_id
status_flags (64-bit)             # Single state storage
coordinate_key (FK)
timestamps
```

### projections
```
id (PK)
coordinate_key (FK)
projection_type (glossary|private|deception)
payload (JSON)                    # Shadow data
timestamps
```

### tunnel_logs
```
id (PK)
request_id (UNIQUE)               # UUID
coordinate_key
caller_mask
user_agent
ip_address
response_code
response_bits
execution_time_ms
created_at
```

## Bit Position Registry

**User Bits (0-31):**
- 0: IS_ACTIVE
- 1: IS_VIP
- 2: IS_VERIFIED
- 3: IS_BANNED
- 4: IS_PREMIUM
- 5: IS_2FA_ENABLED
- 6: EMAIL_CONFIRMED
- 7: PHONE_CONFIRMED
- 8-15: Permissions (CAN_READ, CAN_WRITE, CAN_DELETE, CAN_ADMIN, etc)
- 16-23: Roles (ROLE_USER, ROLE_ADMIN, ROLE_MODERATOR, etc)
- 24-31: Attributes (HAS_PROFILE_PICTURE, HAS_BIO, etc)

**Document Bits (32-47):**
- 32-39: Document state (IS_PUBLISHED, IS_ARCHIVED, etc)
- 40-47: Document properties (HAS_COMMENTS, HAS_ATTACHMENTS, etc)

**Custom Bits (48-63):**
Reserved for application-specific flags.

See `app/Enums/BitPosition.php` for complete registry.

## Caller Mask Values

| Caller | Mask | Hex |
|--------|------|-----|
| SEO Bot | 0x0100 | 100 |
| Authenticated User | 0x0200 | 200 |
| Unknown/Attacker | 0x0400 | 400 |

## Performance Characteristics

| Operation | Time | Complexity |
|-----------|------|-----------|
| Bit check | <1µs | O(1) |
| Bitwise query | 1-5ms | O(1) |
| Coordinate resolve | 1-3ms | O(1) |
| Projection render | 2-4ms | O(1) |
| /sync endpoint E2E | 5-15ms | O(1) |

## Security Features

- **Projection-based security**: Raw data never sent outside
- **Honeypot deception**: Trap attackers with fake data
- **Bit-driven permissions**: Microsecond permission checks
- **Audit logging**: All requests logged to tunnel_logs
- **Request validation**: Strict input validation
- **Static analysis**: PHPStan Level 9

## Deployment

### Docker

```dockerfile
FROM php:8.3-fpm

# Install dependencies...
COPY . /app
WORKDIR /app

RUN composer install --no-dev
RUN php artisan key:generate
RUN php artisan migrate --force
RUN php artisan db:seed --force
```

### Environment Variables

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...

DB_CONNECTION=mysql
DB_HOST=db.example.com
DB_DATABASE=clc_factory
DB_USERNAME=user
DB_PASSWORD=secret

CACHE_DRIVER=redis
SESSION_DRIVER=cookie
```

## Monitoring

Access audit trail:

```bash
# View recent tunnel logs
php artisan tinker
>>> TunnelLog::latest()->limit(20)->get();

# Analytics
>>> TunnelLog::where('response_code', 200)->count();
>>> TunnelLog::where('caller_mask', 0x0100)->count(); // BOTs
```

## Documentation

- `FACTORY_SPECIFICATION.md` - Complete factory specification
- `ATOMIC_COORDINATE.md` - Atomic coordinate format specification
- `LARAVEL_BIT_DRIVEN.md` - Bit-driven Laravel architecture

## License

Proprietary - Production Use Only

## Support

For issues, contact the CLC team.

---

**Last Updated:** 2024
**Version:** 1.0.0
**Status:** Production Ready
