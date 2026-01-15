from typing import Any, Dict
import yaml

class TypeLoader:
    def __init__(self, yaml_path: str):
        self.yaml_path = yaml_path
        self._raw = None
    
    def load(self) -> Dict[str, Any]:
        if self._raw is None:
            with open(self.yaml_path, 'r', encoding='utf-8') as f:
                self._raw = yaml.safe_load(f)
        return self._raw
    
    def get_layer(self, layer_name: str) -> Dict[str, Any]:
        data = self.load()
        return data.get(layer_name, {})
