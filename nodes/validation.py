from typing import Any, Dict
from nodes.base import Node

class ValidatorNode(Node):
    def __init__(self):
        super().__init__('validator')
    
    def process(self, data: Any) -> Dict[str, Any]:
        target = data.get('target')
        content = data.get('payload')
        
        if not target or not isinstance(target, str) or len(target) > 256:
            data['valid'] = False
            return data
        
        allowed = (str, int, float, bool, dict, list, type(None))
        data['valid'] = isinstance(content, allowed)
        return data
