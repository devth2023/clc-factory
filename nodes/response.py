from typing import Any, Dict
from nodes.base import Node

class ResponseBuilderNode(Node):
    def __init__(self):
        super().__init__('response_builder')
    
    def process(self, data: Any) -> Dict[str, Any]:
        return {
            'status': 200 if data.get('valid') else 400,
            'data': data.get('swapped') or data.get('payload'),
            'mask': hex(data.get('mask', 0x0000))
        }
