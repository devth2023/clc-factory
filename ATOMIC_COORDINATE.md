# Atomic Coordinate Specification (ACS)

## Coordinate Atom Structure

```
XXXX . YYYY @ 
│    │  │   └─ Terminator (Always @)
│    │  └────── Subtype Bits (4 hex digits)
│    └───────── Separator (Always .)
└─────────────── Type Bits (4 hex digits)
```

### Format: `TTTT.SSSS@`
- **TTTT** = Type Nibble (4 bits = 1 hex digit × 4)
- **SSSS** = Subtype Nibble (4 bits = 1 hex digit × 4)
- **@** = Terminator (Atomic boundary marker)

---

## Type Bits (TTTT) - First Nibble

Each position is 1 bit:

```
Position: 3 2 1 0
          X X X X
          │ │ │ └─ Bit 0: Data Type Flag
          │ │ └─── Bit 1: Security Flag
          │ └───── Bit 2: Execution Flag
          └─────── Bit 3: Reserved
```

### Bit 0: Data Type (Mutually Exclusive)
- `0` = Object/Structure (0x0)
- `1` = String/Text (0x1)

### Bit 1: Security Level (Mutually Exclusive)
- `0` = Public (readable by all)
- `1` = Secret (auth required)

### Bit 2: Execution
- `0` = Passive (read-only)
- `1` = Active (executable)

### Bit 3: Reserved
- `0` = Standard
- `1` = Experimental

---

## Subtype Bits (SSSS) - Second Nibble

```
Position: 3 2 1 0
          X X X X
          │ │ │ └─ Bit 4: Persistence
          │ │ └─── Bit 5: Visibility
          │ └───── Bit 6: Transformation
          └─────── Bit 7: Hydration
```

### Bit 4: Persistence (Mutually Exclusive)
- `0` = Ephemeral (transient)
- `1` = Persistent (stored)

### Bit 5: Visibility (Mutually Exclusive)
- `0` = Hidden (not broadcast)
- `1` = Visible (SEO-indexable)

### Bit 6: Transformation
- `0` = Raw (no modification)
- `1` = Transformed (value swapped)

### Bit 7: Hydration
- `0` = Dry (no projection)
- `1` = Hydrated (projected shadow)

---

## Real Examples from master_registry.yaml

### COORD_X101: "1010.01010@"
```
Type:    1 (hex) = 0001 (binary)
         │││└─ Bit 0: 1 = String
         ││└── Bit 1: 0 = Public
         │└─── Bit 2: 0 = Passive
         └──── Bit 3: 0 = Standard
Result: Standard, Public, Passive String

Subtype: 0 (hex) = 0000 (binary)
         │││└─ Bit 4: 0 = Ephemeral
         ││└── Bit 5: 0 = Hidden
         │└─── Bit 6: 0 = Raw
         └──── Bit 7: 0 = Dry
Result: Ephemeral, Hidden, Raw, Dry

Bitmask Policy (layer3): 0x0001 = String + Public
Full: "User_Profile_Name" - Public String, Passive, Ephemeral, not projected
```

### COORD_X102: "1020.02020@"
```
Type:    1 (hex) = 0001 (binary)
         Result: Standard, Public, Passive String

Subtype: 0 (hex) = 0000 (binary)
         Result: Ephemeral, Hidden, Raw, Dry

Bitmask Policy (layer3): 0x0002 = Number + Public
Full: "Dashboard_Stats" - Number type despite coordinate format
```

### COORD_NAV_PROFILE: "2000.00000@"
```
Type:    2 (hex) = 0010 (binary)
         │││└─ Bit 0: 0 = Object
         ││└── Bit 1: 0 = Public
         │└─── Bit 2: 0 = Passive
         └──── Bit 3: 0 = Standard
Result: Standard, Public, Passive Object

Subtype: 0 (hex) = 0000 (binary)
         Result: Ephemeral, Hidden, Raw, Dry

Bitmask Policy (layer3): 0x0010 = Public
Full: Navigation Profile - Public Object, Passive, Ephemeral
```

### COORD_NAV_DASHBOARD: "2001.00000@"
```
Type:    2 (hex) = 0010 (binary)
         Result: Standard, Public, Passive Object

Subtype: 0 (hex) = 0000 (binary)
         Result: Ephemeral, Hidden, Raw, Dry

Bitmask Policy (layer3): 0x0010 = Public
Full: Navigation Dashboard - Public Object, Passive, Ephemeral
```

---

## Atomic Operation Rules

### Rule 1: Type Bits are Read-Only
Type bits (TTTT) are set once and cannot change during processing.

### Rule 2: Subtype Bits are Conditional
Subtype bits (SSSS) can be modified based on caller context:
- BOT caller: Force Bit 5 = 1 (Visible)
- AUTH caller: Force Bit 5 = 0 (Hidden)
- ATTACKER: Toggle all bits to deception mode

### Rule 3: Projection Rule
```
IF Subtype Bit 7 = 1 (Hydrated):
  THEN render shadow based on caller type
  ELSE render raw value
```

### Rule 4: Terminator Validation
Every coordinate MUST end with `@`. Invalid terminator = INVALID_COORDINATE

---

## Database Mapping

```sql
-- Extract atomic bits from coordinate
SELECT 
    coordinate_key,
    HEX(SUBSTRING(address, 1, 1)) AS type_bits,
    HEX(SUBSTRING(address, 3, 1)) AS subtype_bits,
    SUBSTRING(address, 5, 1) AS terminator
FROM coordinates
WHERE address REGEXP '^[0-9a-f]{2}\.[0-9a-f]{2}@$';
```

---

## Caller Bitmask Overlay

Request time, caller mask modifies Subtype:

```php
$atomicCoord = "1010.0110@";
$callerMask = 0x0100; // BOT

// Force Bit 5 (Visibility) = 1 for BOT
$modifiedSubtype = 0x06 | 0x20; // = 0x26

// Effective coordinate becomes: "1010.0110@" → "1010.0110@" (processed as 0x26)
```

---

## Validation Rule

All operations validate against atomic grammar:
- Type: `[0-9a-f]{1}`
- Subtype: `[0-9a-f]{1}`
- Terminator: `@`

Invalid format → Deception projection returned
