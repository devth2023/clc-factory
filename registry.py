import yaml
from typing import Dict, Any, Optional
from types import Coordinate, Mask

class Layer1Registry:
    def __init__(self, data: Dict[str, Any]):
        self.map = data
    
    def resolve_human(self, coord_id: str) -> Optional[Dict[str, Any]]:
        return self.map.get(coord_id)

class Layer2Registry:
    def __init__(self, data: Dict[str, Any]):
        self.map = data
    
    def resolve_coordinate(self, coord_id: str) -> Optional[Coordinate]:
        addr = self.map.get(coord_id)
        if addr:
            return Coordinate(coord_id, addr)
        return None

class Layer3Registry:
    def __init__(self, data: Dict[str, Any]):
        self.map = data
    
    def resolve_mask(self, coord_id: str) -> Optional[Mask]:
        bits = self.map.get(coord_id)
        if bits is not None:
            return Mask(bits)
        return None

class MasterRegistry:
    def __init__(self, yaml_path: str):
        with open(yaml_path, 'r', encoding='utf-8') as f:
            raw = yaml.safe_load(f)
        
        self.layer1 = Layer1Registry(raw.get('layer1_human_map', {}))
        self.layer2 = Layer2Registry(raw.get('layer2_coordinate_registry', {}))
        self.layer3 = Layer3Registry(raw.get('layer3_bitmask_core', {}))
        self.deceptions = raw.get('deception_payloads', {})
    
    def get_human(self, coord_id: str) -> Optional[Dict[str, Any]]:
        return self.layer1.resolve_human(coord_id)
    
    def get_coordinate(self, coord_id: str) -> Optional[Coordinate]:
        return self.layer2.resolve_coordinate(coord_id)
    
    def get_mask(self, coord_id: str) -> Optional[Mask]:
        return self.layer3.resolve_mask(coord_id)
    
    def get_deception(self, coord_id: str) -> Optional[Any]:
        return self.deceptions.get(coord_id)
