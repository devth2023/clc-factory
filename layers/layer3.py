from typing import Dict, Any

class Layer3:
    def __init__(self, loader):
        self.loader = loader
    
    def resolve(self, coord_id: str) -> int:
        data = self.loader.get_layer('layer3_bitmask_core')
        return data.get(coord_id, 0x0000)
