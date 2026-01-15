from typing import Any, Dict
from nodes.base import Node

class SanitizerNode(Node):
    def __init__(self, max_len: int = 1000):
        super().__init__('sanitizer')
        self.max_len = max_len
    
    def process(self, data: Any) -> Dict[str, Any]:
        content = data.get('payload')
        
        if isinstance(content, str):
            data['payload'] = content[:self.max_len]
        elif isinstance(content, dict):
            data['payload'] = {k: str(v)[:self.max_len] if isinstance(v, str) else v 
                              for k, v in content.items()}
        elif isinstance(content, list):
            data['payload'] = [str(x)[:self.max_len] if isinstance(x, str) else x for x in content]
        
        return data

class TransformerNode(Node):
    def __init__(self):
        super().__init__('transformer')
        self.transforms: Dict[int, Any] = {}
    
    def register(self, mask_bits: int, fn: Any) -> None:
        self.transforms[mask_bits] = fn
    
    def process(self, data: Any) -> Dict[str, Any]:
        mask = data.get('mask', 0x0000)
        payload = data.get('payload')
        
        if mask & 0x0800:
            fn = self.transforms.get(mask)
            if fn:
                data['payload'] = fn(payload)
        
        return data
