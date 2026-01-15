from typing import Any, Dict, Optional
import hashlib

class CacheStore:
    def __init__(self, ttl: int = 300):
        self.store: Dict[str, Any] = {}
        self.ttl = ttl
    
    def key_from_payload(self, target: str, user_agent: str) -> str:
        combined = f"{target}:{user_agent}"
        return hashlib.sha256(combined.encode()).hexdigest()[:16]
    
    def get(self, key: str) -> Optional[Any]:
        return self.store.get(key)
    
    def set(self, key: str, value: Any) -> None:
        self.store[key] = value
    
    def clear(self) -> None:
        self.store.clear()
