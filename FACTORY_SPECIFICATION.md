# CLC Software Factory - Complete Specification

## 1. System Philosophy & Core Concepts

### 1.1 Three Iron Laws
```
Law 1: No Semantic in Core
       - Zero string labels in logic processing
       - All decisions via bitmask only
       - Glossary (layer1) isolated from execution

Law 2: API-Less Architecture
       - Single tunnel entry point (/sync)
       - No semantic routing (no /users, /posts, /admin)
       - Stateless coordinate-based requests

Law 3: Projection over Transmission
       - Never send raw data outside
       - Three projection types: Glossary, Private, Deception
       - Caller identity determines shadow rendered
```

### 1.2 Three-Layer Registry Model
```
Layer 1: Glossary (Human Map)
         └─ String labels, descriptions, SEO keywords
            Purpose: UI display, search engines only
            Storage: coordinate_mappings.label

Layer 2: Coordinate Address (Registry)
         └─ Unique identifiers (1010.01010@)
            Purpose: System reference point
            Storage: coordinate_mappings.coordinate_address

Layer 3: Bitmask Policy (The Truth)
         └─ Bit flags defining type, security, behavior
            Purpose: Logic execution, single source of truth
            Storage: coordinate_mappings.bitmask_policy
```

---

## 2. Database Architecture

### 2.1 Core Tables

#### Table: coordinate_mappings
```sql
CREATE TABLE coordinate_mappings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    
    -- Glossary (Layer 1)
    coordinate_key VARCHAR(64) UNIQUE NOT NULL,
    label VARCHAR(255),
    description TEXT,
    seo_keywords JSON,
    schema_type VARCHAR(128),
    
    -- Registry (Layer 2)
    coordinate_address VARCHAR(128) NOT NULL,  -- Format: XXXX.YYYY@
    
    -- Bitmask Policy (Layer 3)
    bitmask_policy INT NOT NULL,  -- (0x0000 - 0xFFFF)
    
    -- Metadata
    version INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_coordinate_key (coordinate_key),
    INDEX idx_bitmask_policy (bitmask_policy),
    INDEX idx_is_active (is_active),
    UNIQUE KEY unique_address (coordinate_address)
);
```

#### Table: entities (Bit-State Storage)
```sql
CREATE TABLE entities (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    
    -- Entity Identity
    entity_type VARCHAR(64) NOT NULL,  -- "user", "post", "document"
    entity_id VARCHAR(128) NOT NULL,
    
    -- Bit-State (Single Source of Truth)
    status_flags BIGINT DEFAULT 0,  -- 64 bits of state
    
    -- Coordinate Reference
    coordinate_key VARCHAR(64),
    
    -- Metadata
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE KEY unique_entity (entity_type, entity_id),
    INDEX idx_coordinate_key (coordinate_key),
    INDEX idx_status_flags (status_flags),
    FOREIGN KEY (coordinate_key) REFERENCES coordinate_mappings(coordinate_key)
);
```

#### Table: bit_registry (Bit Position Mappings)
```sql
CREATE TABLE bit_registry (
    bit_position INT PRIMARY KEY,  -- 0-63
    
    -- Semantic mapping (only for documentation)
    bit_name VARCHAR(64) UNIQUE NOT NULL,  -- "IS_ACTIVE", "IS_VIP", etc
    bit_category VARCHAR(32),  -- "user_state", "permission", "flag"
    
    description TEXT,
    created_at TIMESTAMP
);
```

#### Table: projections (Shadow Data)
```sql
CREATE TABLE projections (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    
    -- Mapping
    coordinate_key VARCHAR(64) NOT NULL,
    projection_type ENUM('glossary', 'private', 'deception') NOT NULL,
    
    -- Payload
    payload JSON NOT NULL,
    
    -- Metadata
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE KEY unique_projection (coordinate_key, projection_type),
    FOREIGN KEY (coordinate_key) REFERENCES coordinate_mappings(coordinate_key)
);
```

#### Table: tunnel_logs (Audit Trail)
```sql
CREATE TABLE tunnel_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    
    -- Request
    request_id VARCHAR(64) UNIQUE NOT NULL,
    coordinate_key VARCHAR(64),
    caller_mask INT,
    user_agent TEXT,
    ip_address VARCHAR(45),
    
    -- Response
    response_code INT,
    response_bits INT,
    
    -- Timing
    execution_time_ms INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_coordinate_key (coordinate_key),
    INDEX idx_caller_mask (caller_mask),
    INDEX idx_created_at (created_at)
);
```

### 2.2 Indexing Strategy
```sql
-- Bitmask queries are critical
CREATE INDEX idx_status_flags_range ON entities(status_flags);

-- Coordinate lookups are frequent
CREATE INDEX idx_coordinate_key_active 
    ON coordinate_mappings(coordinate_key) 
    WHERE is_active = TRUE;

-- Projection selection
CREATE INDEX idx_projection_type 
    ON projections(projection_type);
```

---

## 3. Application Architecture

### 3.1 Folder Structure
```
laravel-clc-factory/
├── app/
│   ├── Models/
│   │   ├── BitEntity.php              # Base model for all bit-driven entities
│   │   ├── CoordinateMapping.php
│   │   ├── User.php                   # Extends BitEntity
│   │   ├── Document.php
│   │   └── ...
│   │
│   ├── Services/
│   │   ├── BitMaskEngine.php          # Core bitmask operations
│   │   ├── CoordinateResolver.php     # Layer 1/2/3 resolution
│   │   ├── ProjectionRenderer.php     # Shadow data rendering
│   │   ├── CallerDetector.php         # Identify caller type
│   │   └── BitQueryBuilder.php        # Bitwise query construction
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── TunnelController.php   # /sync endpoint only
│   │   ├── Middleware/
│   │   │   ├── BitPermissionGuard.php
│   │   │   ├── CallerDetectionMiddleware.php
│   │   │   └── AuditLoggingMiddleware.php
│   │   └── Requests/
│   │       └── TunnelRequest.php
│   │
│   ├── Casts/
│   │   ├── BitFieldCast.php           # Convert bits to objects
│   │   └── CoordinateAddressCast.php
│   │
│   ├── Enums/
│   │   ├── BitPosition.php            # Bit 0-63 constants
│   │   ├── CallerType.php             # BOT, AUTH, ATTACKER
│   │   ├── ProjectionType.php         # GLOSSARY, PRIVATE, DECEPTION
│   │   └── CoordinateType.php
│   │
│   └── Observers/
│       ├── EntityObserver.php         # Hook into bit changes
│       └── CoordinateObserver.php
│
├── database/
│   ├── migrations/
│   │   ├── 2024_coordinate_mappings.php
│   │   ├── 2024_entities.php
│   │   ├── 2024_bit_registry.php
│   │   ├── 2024_projections.php
│   │   └── 2024_tunnel_logs.php
│   │
│   ├── seeders/
│   │   ├── CoordinateMappingSeeder.php
│   │   ├── BitRegistrySeeder.php
│   │   └── ProjectionSeeder.php
│   │
│   └── factories/
│       ├── EntityFactory.php
│       └── CoordinateMappingFactory.php
│
├── routes/
│   └── api.php                        # Only /sync
│
├── resources/
│   └── registries/
│       └── master_registry.yaml       # IMMUTABLE source
│
├── tests/
│   ├── Unit/
│   │   ├── BitMaskEngineTest.php
│   │   ├── CoordinateResolverTest.php
│   │   └── BitQueryTest.php
│   ├── Feature/
│   │   ├── TunnelEndpointTest.php
│   │   ├── ProjectionTest.php
│   │   └── PermissionTest.php
│   └── Integration/
│       └── FullFlowTest.php
│
├── docs/
│   ├── ARCHITECTURE.md
│   ├── API_SPECIFICATION.md
│   ├── BITMASK_GUIDE.md
│   ├── MIGRATION_GUIDE.md
│   └── EXAMPLES.md
│
└── config/
    └── clc.php                        # CLC-specific config
```

### 3.2 Core Classes

#### BitMaskEngine.php
```php
class BitMaskEngine {
    // Bit operations (atomic, no side effects)
    public function setBit(int &$flags, int $position, bool $value): void
    public function getBit(int $flags, int $position): bool
    public function applyMask(int $flags, int $mask): int
    public function clearMask(int $flags, int $mask): int
    public function toggleBit(int &$flags, int $position): void
    
    // Validation
    public function isValidBitPosition(int $pos): bool
    public function validateMask(int $mask): bool
    
    // Query generation
    public function buildBitwiseWhere(int $mask, string $op = 'AND'): string
    public function buildBitInclusion(array $positions): int
    public function buildBitExclusion(array $positions): int
}
```

#### CoordinateResolver.php
```php
class CoordinateResolver {
    // Layer operations (immutable)
    public function resolveGlossary(string $coordinateKey): ?array
    public function resolveAddress(string $coordinateKey): ?string
    public function resolveMask(string $coordinateKey): int
    
    // Full resolution (all 3 layers)
    public function resolve(string $coordinateKey): ?CoordinateData
    
    // Validation
    public function validateCoordinateAddress(string $addr): bool
    public function validateCoordinateKey(string $key): bool
}
```

#### ProjectionRenderer.php
```php
class ProjectionRenderer {
    // Render based on caller type
    public function render(
        string $coordinateKey,
        CoordinateData $coordData,
        int $callerMask
    ): array
    
    // Individual projection types
    public function renderGlossary(string $coordinateKey): array
    public function renderPrivate(CoordinateData $coordData): array
    public function renderDeception(string $coordinateKey): array
}
```

#### CallerDetector.php
```php
class CallerDetector {
    const CALLER_BOT = 0x0100;
    const CALLER_AUTH = 0x0200;
    const CALLER_ATTACKER = 0x0400;
    
    public function detect(Request $request): int
    
    private function isSeoBot(string $userAgent): bool
    private function hasValidAuth(string $token): bool
}
```

---

## 4. Request/Response Protocol

### 4.1 Tunnel Request Format
```json
{
    "target": "COORD_X101",
    "payload": {
        "entity_id": "user:12345",
        "action": "READ"
    }
}
```

### 4.2 Tunnel Response Format
```json
{
    "status": 200,
    "data": {
        "type": "glossary|private|deception",
        "payload": {...}
    },
    "mask": "0x0300",
    "request_id": "req_xxxxx"
}
```

### 4.3 Single Tunnel Route
```php
Route::post('/sync', [TunnelController::class, 'sync'])
    ->middleware([
        BitPermissionGuard::class,
        CallerDetectionMiddleware::class,
        AuditLoggingMiddleware::class,
    ]);
```

---

## 5. Bit Position Registry (0-63)

### 5.1 User Entity Bits
```
0-7:    User State Flags
  0: IS_ACTIVE
  1: IS_VIP
  2: IS_VERIFIED
  3: IS_BANNED
  4: IS_PREMIUM
  5: IS_2FA_ENABLED
  6: EMAIL_CONFIRMED
  7: PHONE_CONFIRMED

8-15:   User Permissions
  8:  CAN_READ
  9:  CAN_WRITE
  10: CAN_DELETE
  11: CAN_ADMIN
  12: CAN_PUBLISH
  13: CAN_MODERATE
  14: CAN_VERIFY_OTHERS
  15: CAN_EXPORT

16-23:  User Roles
  16: ROLE_USER
  17: ROLE_MODERATOR
  18: ROLE_ADMIN
  19: ROLE_SYSTEM
  20: ROLE_BOT
  21: ROLE_GUEST
  22-23: Reserved

24-31:  User Attributes
  24: HAS_PROFILE_PICTURE
  25: HAS_BIO
  26: HAS_VERIFIED_EMAIL
  27: HAS_STRIPE_PAYMENT
  28: HAS_OAUTH_LOGIN
  29-31: Reserved

32-63:  Custom/Extension Bits
```

### 5.2 Document Entity Bits
```
0-7:    Document State
  0: IS_PUBLISHED
  1: IS_ARCHIVED
  2: IS_DELETED
  3: IS_LOCKED
  4: IS_REVIEWED
  5: IS_FEATURED
  6: IS_SPONSORED
  7: NEEDS_APPROVAL

8-15:   Document Properties
  8: HAS_COMMENTS
  9: HAS_ATTACHMENTS
  10: IS_PUBLIC
  11: IS_SHARED
  12: REQUIRES_AUTH
  13: HAS_EXPIRY
  14: IS_SENSITIVE
  15: HAS_ENCRYPTION
```

---

## 6. Development Workflow

### 6.1 Creating New Coordinates
```
1. Define in master_registry.yaml (Layer 1, 2, 3)
2. Create migration to add to coordinate_mappings
3. Add bit_registry entries
4. Create projections (glossary, private, deception)
5. Write tests for all three projections
6. Deploy (immutable - no rollback)
```

### 6.2 Adding New Bit Positions
```
1. Check bit_registry for free positions
2. Document in BITMASK_GUIDE.md
3. Add Enum entry in app/Enums/BitPosition.php
4. Create migration to update schema documentation
5. Update tests for new bit behavior
```

### 6.3 Testing Strategy
```
Unit Tests:
  - BitMaskEngine: all bitwise operations
  - CoordinateResolver: layer resolution
  - CallerDetector: bot/auth detection

Feature Tests:
  - /sync endpoint with various caller masks
  - Projection rendering for all types
  - Permission checks
  - Error cases

Integration Tests:
  - Full request flow
  - Database consistency
  - Concurrent operations
```

---

## 7. Query Examples

### 7.1 Bitwise Queries
```php
// Find all active, verified, non-banned users
$activeMask = (1 << BitPosition::IS_ACTIVE)
            | (1 << BitPosition::IS_VERIFIED);
$bannedBit = 1 << BitPosition::IS_BANNED;

$users = User::whereRaw("(status_flags & ? = ?)", [$activeMask, $activeMask])
             ->whereRaw("(status_flags & ? = 0)", [$bannedBit])
             ->get();

// Check if user has READ + WRITE permissions
$permMask = (1 << BitPosition::CAN_READ)
          | (1 << BitPosition::CAN_WRITE);

if (($user->status_flags & $permMask) == $permMask) {
    // Has both permissions
}

// Find published documents that are not archived
$pubBit = 1 << BitPosition::IS_PUBLISHED;
$archBit = 1 << BitPosition::IS_ARCHIVED;

$docs = Document::whereRaw("(status_flags & ? > 0)", [$pubBit])
                 ->whereRaw("(status_flags & ? = 0)", [$archBit])
                 ->get();
```

---

## 8. Performance Characteristics

### 8.1 Expected Performance
| Operation | Latency | Notes |
|-----------|---------|-------|
| Single bit check | <1µs | CPU register op |
| Bitwise query | 1-5ms | Single INDEX scan |
| Coordinate resolve | 1-3ms | 3 table lookups |
| Projection render | 2-4ms | Template rendering |
| /sync endpoint | 5-15ms | E2E with logging |

### 8.2 Scaling Characteristics
```
Database: 
  - Single-table scan (status_flags) vs multi-table JOINs
  - Index size reduced by 80% vs traditional approach
  - Concurrent updates safe (atomic bit ops)

Memory:
  - Entity state: 8 bytes per 64 flags vs 50+ columns
  - Query cache: fewer variations = better hit rate

CPU:
  - Bitwise ops: 1-2 CPU cycles
  - No loop logic: O(1) vs O(n) permission checks
```

---

## 9. Deployment Architecture

### 9.1 Environment Setup
```
Production:
  - Read-only master_registry.yaml
  - Immutable coordinate_mappings (no runtime changes)
  - Audit logging to separate DB
  - Cache layer on coordinate resolution

Staging:
  - Test registry values
  - Dry-run projections
  - Load test bitmask queries

Development:
  - Local registries
  - Mutable for testing
  - Full audit logging
```

### 9.2 Monitoring & Observability
```
Metrics:
  - /sync endpoint response time
  - Projection type distribution (% glossary, private, deception)
  - Caller type distribution (% bot, auth, attacker)
  - Bit position usage heatmap

Logs:
  - tunnel_logs table (all requests)
  - Projection type selected
  - Caller mask detected
  - Coordinate resolution time

Alerts:
  - Invalid coordinates (deception count > 5%)
  - Slow projections (>100ms)
  - Invalid bitmasks
  - Coordinator resolver failures
```

---

## 10. Migration from Traditional MVC

### 10.1 Migration Steps
```
Phase 1: Parallel Deployment
  - Deploy CLC alongside traditional API
  - Route subset to /sync
  - Compare responses

Phase 2: Incremental Migration
  - Move 20% of traffic to /sync
  - Monitor metrics
  - Gradually increase percentage

Phase 3: Decommission
  - Archive old controllers
  - Consolidate to single tunnel
  - Full bit-driven operation
```

### 10.2 Data Migration
```
Traditional Columns → Bit Positions:
  is_active → Bit 0
  is_verified → Bit 2
  can_read → Bit 8
  ... (computed in migration)

Create computed column:
  ALTER TABLE users 
  ADD status_flags BIGINT GENERATED ALWAYS AS (...)
  
Verify, then make permanent.
```

---

## 11. Security Considerations

### 11.1 Bitmask Injection Prevention
```php
// Validate caller mask
if (!CallerDetector::isValidMask($request->detectedMask)) {
    return response()->json(['status' => 403, 'data' => null]);
}

// Strict type checking
if (!is_int($statusFlags) || $statusFlags < 0 || $statusFlags > PHP_INT_MAX) {
    return response()->json(['status' => 400, 'data' => null]);
}
```

### 11.2 Projection Access Control
```php
// Caller cannot override projection type
// Middleware enforces:
if (CallerDetector::isBotMask($detectedMask)) {
    $projection = ProjectionRenderer::renderGlossary(...);
    // Cannot request 'private' projection as bot
}
```

### 11.3 Audit Trail
```
Every /sync request logged:
  - Caller mask
  - Coordinate requested
  - Projection type returned
  - User agent
  - IP address
  - Response time
```

---

## 12. Documentation Requirements

Each component MUST have:
- Purpose statement (one sentence)
- Input/output contracts
- Bitmask implications (if relevant)
- Example usage
- Performance notes

No inline comments explaining logic - let bitwise operations speak for themselves.

---

## Summary: Factory Readiness Checklist

- [ ] master_registry.yaml finalized (3 layers locked)
- [ ] Database schema deployed
- [ ] BitMaskEngine fully tested
- [ ] CoordinateResolver validated against registry
- [ ] ProjectionRenderer renders all 3 types correctly
- [ ] CallerDetector identifies bot/auth/attacker
- [ ] TunnelController /sync working
- [ ] BitPermissionGuard enforcing permissions
- [ ] Audit logging to tunnel_logs
- [ ] Performance tested (target: <15ms /sync)
- [ ] Migration guide written
- [ ] Documentation complete
- [ ] Security review passed
- [ ] Load test passed (1000+ req/sec)

Ready for factory production deployment.
