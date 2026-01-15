# CLC System Blueprint: PHP/Laravel Implementation

## Database Schema

### Table 1: Glossary (layer1_human_map)
```sql
CREATE TABLE glossaries (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    coordinate_key VARCHAR(64) UNIQUE NOT NULL,
    label VARCHAR(255) NOT NULL,
    description TEXT,
    seo_keywords JSON,
    schema_type VARCHAR(128),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INDEX idx_coordinate_key (coordinate_key);
```

### Table 2: Registry (layer2_coordinate_registry)
```sql
CREATE TABLE coordinates (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    coordinate_key VARCHAR(64) UNIQUE NOT NULL,
    address VARCHAR(128) NOT NULL,
    registry_version INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INDEX idx_coordinate_key (coordinate_key);
INDEX idx_address (address);
```

### Table 3: Bitmask Policy (layer3_bitmask_core)
```sql
CREATE TABLE bitmask_policies (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    coordinate_key VARCHAR(64) UNIQUE NOT NULL,
    bitmask_value INT NOT NULL,
    type_bits INT,
    security_bits INT,
    visibility_bits INT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INDEX idx_coordinate_key (coordinate_key);
INDEX idx_bitmask_value (bitmask_value);
```

### Table 4: Projection Templates (Deception/Public/Private)
```sql
CREATE TABLE projections (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    coordinate_key VARCHAR(64) NOT NULL,
    projection_type ENUM('glossary', 'deception', 'private') NOT NULL,
    payload JSON NOT NULL,
    caller_mask INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (coordinate_key) REFERENCES glossaries(coordinate_key),
    UNIQUE KEY unique_projection (coordinate_key, projection_type)
);

INDEX idx_coordinate_key (coordinate_key);
INDEX idx_projection_type (projection_type);
```

## Class Structure (Laravel)

### Core Layer: Bitmask Calculator
```php
class BitmaskCalculator {
    const TYPE_STRING = 0x0001;
    const TYPE_NUMBER = 0x0002;
    const TYPE_SECRET = 0x0004;
    const TYPE_EXECUTABLE = 0x0008;
    
    const VIS_PUBLIC = 0x0010;
    const VIS_PRIVATE = 0x0020;
    
    const CALLER_BOT = 0x0100;
    const CALLER_AUTH = 0x0200;
    const CALLER_ATTACKER = 0x0400;
    
    private int $mask = 0x0000;
    
    public function detect(Request $request): int {
        $userAgent = $request->header('User-Agent', '');
        $token = $request->header('Authorization', '');
        
        $this->mask = 0x0000;
        
        if ($this->isSeoBot($userAgent)) {
            $this->mask |= self::CALLER_BOT;
        }
        
        if ($token && $this->validateToken($token)) {
            $this->mask |= self::CALLER_AUTH;
        }
        
        if (!$token) {
            $this->mask |= self::CALLER_ATTACKER;
        }
        
        return $this->mask;
    }
    
    private function isSeoBot(string $ua): bool {
        return preg_match('/googlebot|bingbot|slurp/i', $ua) === 1;
    }
    
    private function validateToken(string $token): bool {
        return !empty($token);
    }
    
    public function getMask(): int {
        return $this->mask;
    }
}
```

### Repository: Coordinate Resolver
```php
class CoordinateRepository {
    protected $glossary;
    protected $registry;
    protected $bitmaskPolicy;
    
    public function __construct(
        GlossaryModel $glossary,
        CoordinateModel $registry,
        BitmaskPolicyModel $policy
    ) {
        $this->glossary = $glossary;
        $this->registry = $registry;
        $this->bitmaskPolicy = $policy;
    }
    
    public function resolve(string $coordinateKey): ?array {
        $glossary = $this->glossary->where('coordinate_key', $coordinateKey)->first();
        $registry = $this->registry->where('coordinate_key', $coordinateKey)->first();
        $policy = $this->bitmaskPolicy->where('coordinate_key', $coordinateKey)->first();
        
        if (!$glossary || !$registry || !$policy) {
            return null;
        }
        
        return [
            'glossary' => $glossary,
            'registry' => $registry,
            'policy' => $policy,
        ];
    }
    
    public function getPolicyBitmask(string $coordinateKey): int {
        return $this->bitmaskPolicy
            ->where('coordinate_key', $coordinateKey)
            ->value('bitmask_value') ?? 0x0000;
    }
}
```

### Middleware: Projection Renderer
```php
class ProjectionMiddleware {
    protected $repository;
    protected $calculator;
    
    public function __construct(
        CoordinateRepository $repository,
        BitmaskCalculator $calculator
    ) {
        $this->repository = $repository;
        $this->calculator = $calculator;
    }
    
    public function handle(Request $request, Closure $next) {
        $callerMask = $this->calculator->detect($request);
        
        $coordinateKey = $request->input('target');
        $resolved = $this->repository->resolve($coordinateKey);
        
        if (!$resolved) {
            return response()->json(['status' => 404, 'data' => null]);
        }
        
        $projection = $this->renderProjection(
            $coordinateKey,
            $resolved,
            $callerMask
        );
        
        $request->attributes->set('projection', $projection);
        $request->attributes->set('caller_mask', $callerMask);
        
        return $next($request);
    }
    
    private function renderProjection(string $key, array $resolved, int $callerMask): array {
        $policyMask = $resolved['policy']['bitmask_value'];
        
        if ($callerMask & BitmaskCalculator::CALLER_BOT) {
            return $this->renderGlossary($resolved);
        }
        
        if ($callerMask & BitmaskCalculator::CALLER_AUTH) {
            return $this->renderPrivate($key, $resolved);
        }
        
        return $this->renderDeception($key);
    }
    
    private function renderGlossary(array $resolved): array {
        return [
            'type' => 'glossary',
            'label' => $resolved['glossary']['label'],
            'description' => $resolved['glossary']['description'],
            'schema' => $resolved['glossary']['schema_type'],
        ];
    }
    
    private function renderPrivate(string $key, array $resolved): array {
        return [
            'type' => 'private',
            'coordinate' => $key,
            'address' => $resolved['registry']['address'],
            'bitmask' => $resolved['policy']['bitmask_value'],
        ];
    }
    
    private function renderDeception(string $key): array {
        $deception = DB::table('projections')
            ->where('coordinate_key', $key)
            ->where('projection_type', 'deception')
            ->value('payload');
        
        return [
            'type' => 'deception',
            'data' => $deception ?? 'INVALID_COORDINATE',
        ];
    }
}
```

### Single Tunnel Controller
```php
class TunnelController extends Controller {
    public function sync(Request $request) {
        $projection = $request->attributes->get('projection');
        $callerMask = $request->attributes->get('caller_mask');
        
        return response()->json([
            'status' => 200,
            'data' => $projection,
            'mask' => dechex($callerMask),
        ]);
    }
}
```

### Routes
```php
// Single tunnel only
Route::post('/sync', [TunnelController::class, 'sync'])
    ->middleware([ProjectionMiddleware::class]);
```

## Key Principles Applied

1. **No Semantic in Core**
   - All logic driven by bitmask values from `bitmask_policies` table
   - String labels only in Glossary layer

2. **API-Less Architecture**
   - Single `/sync` endpoint
   - No semantic routing

3. **Projection over Transmission**
   - Three projection types stored in DB
   - Middleware selects based on caller bitmask
   - Database enforces the "truth" via bitmask_policies
