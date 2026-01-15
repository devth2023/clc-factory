from typing import Any, Dict
from nodes.base import Node
from layers.layer1 import Layer1
from layers.layer2 import Layer2
from layers.layer3 import Layer3

class TypeResolverNode(Node):
    def __init__(self, loader):
        super().__init__('type_resolver')
        self.l1 = Layer1(loader)
        self.l2 = Layer2(loader)
        self.l3 = Layer3(loader)
    
    def process(self, data: Any) -> Dict[str, Any]:
        target = data.get('target')
        data['coord_def'] = self.l1.resolve(target)
        data['coord_addr'] = self.l2.resolve(target)
        data['coord_mask'] = self.l3.resolve(target)
        return data
