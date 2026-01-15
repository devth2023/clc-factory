from typing import Dict, Any

class Layer2:
    def __init__(self, loader):
        self.loader = loader
    
    def resolve(self, coord_id: str) -> str:
        data = self.loader.get_layer('layer2_coordinate_registry')
        return data.get(coord_id, '')
