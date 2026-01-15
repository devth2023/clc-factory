from typing import Any, Dict, Optional
from nodes.base import Node
import hashlib

class CacheNode(Node):
    def __init__(self, ttl: int = 300):
        super().__init__('cache')
        self.store: Dict[str, Any] = {}
        self.ttl = ttl
    
    def process(self, data: Any) -> Dict[str, Any]:
        target = data.get('target', '')
        ua = data.get('user_agent', '')
        key = hashlib.sha256(f"{target}:{ua}".encode()).hexdigest()[:16]
        
        if key in self.store:
            data['cached'] = self.store[key]
        else:
            data['cached'] = None
        
        data['cache_key'] = key
        return data
