from typing import Any
from types import Payload

class Validator:
    def validate_payload(self, payload: Payload) -> bool:
        if not payload.target:
            return False
        
        if not isinstance(payload.target, str):
            return False
        
        if len(payload.target) > 256:
            return False
        
        return True
    
    def validate_content(self, content: Any) -> bool:
        if content is None:
            return True
        
        allowed = (str, int, float, bool, dict, list)
        return isinstance(content, allowed)
