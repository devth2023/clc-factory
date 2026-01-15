# CLC Factory - Python Edition

Production-grade Python/Flask implementation of the Coordinate Logic Core system.

## Architecture

Single `/api/sync` tunnel endpoint implementing:
- **BitMaskEngine** - Atomic bitwise operations
- **CoordinateResolver** - 3-layer registry resolution
- **CallerDetector** - Bot/Auth/Attacker identification
- **ProjectionRenderer** - Glossary/Private/Deception shadows

## Installation

### 1. Install Dependencies

```bash
pip install -r requirements.txt
```

### 2. Environment Setup

```bash
cp .env.example .env
```

### 3. Run Development Server

```bash
python -m flask --app clc.app run
```

Server runs on `http://localhost:5000/api/sync`

## API Usage

### Request

```bash
curl -X POST http://localhost:5000/api/sync \
  -H "Content-Type: application/json" \
  -H "User-Agent: Mozilla/5.0 (compatible; Googlebot/2.1)" \
  -d '{"target": "COORD_X101"}'
```

### Response (SEO Bot)

```json
{
  "status": 200,
  "request_id": "uuid-here",
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

## Testing

```bash
pytest
pytest --cov=clc tests/
```

## Project Structure

```
python/
├── clc/
│   ├── __init__.py
│   ├── app.py              # Flask application
│   ├── models.py           # Pydantic models
│   ├── enums.py            # BitPosition, CallerType, ProjectionType
│   ├── exceptions.py       # Custom exceptions
│   └── services/
│       ├── bitmask_engine.py
│       ├── coordinate_resolver.py
│       ├── caller_detector.py
│       └── projection_renderer.py
├── tests/
│   └── test_bitmask_engine.py
├── requirements.txt
├── setup.py
└── README.md
```

## Quality Standards

- ✅ Type hints (Python 3.10+)
- ✅ Pydantic validation
- ✅ Exception handling
- ✅ Unit tests with pytest
- ✅ Code coverage tracking

## Deployment

### Docker

```bash
docker build -t clc-factory:latest .
docker run -p 5000:5000 clc-factory:latest
```

## License

Proprietary - Production Use Only
