from typing import Dict, Any

class Layer1:
    def __init__(self, loader):
        self.loader = loader
    
    def resolve(self, coord_id: str) -> Dict[str, Any]:
        data = self.loader.get_layer('layer1_human_map')
        return data.get(coord_id, {})
