from typing import Any
from types import Mask

class Sanitizer:
    def __init__(self, max_length: int = 1000):
        self.max_length = max_length
    
    def sanitize(self, data: Any, mask: Mask) -> Any:
        if isinstance(data, str):
            return data[:self.max_length]
        
        if isinstance(data, dict):
            return {k: self._sanitize_value(v) for k, v in data.items()}
        
        if isinstance(data, list):
            return [self._sanitize_value(x) for x in data]
        
        return data
    
    def _sanitize_value(self, value: Any) -> Any:
        if isinstance(value, str):
            return value[:self.max_length]
        return value
