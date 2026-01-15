from typing import Any, Dict
from nodes.base import Node

class SwapNode(Node):
    def __init__(self, loader):
        super().__init__('swap')
        self.loader = loader
    
    def process(self, data: Any) -> Dict[str, Any]:
        target = data.get('target')
        mask = data.get('mask', 0x0000)
        coord_def = data.get('coord_def', {})
        
        public = {
            'label': coord_def.get('label'),
            'description': coord_def.get('description'),
            'schema': coord_def.get('schema_type')
        }
        private = {'coord': target, 'full_def': coord_def}
        
        data['swapped'] = private if (mask & 0x0200) else public
        return data
