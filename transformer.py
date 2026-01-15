from typing import Any, Callable, Dict
from types import Mask
import time

class Transformer:
    def __init__(self):
        self.transforms: Dict[int, Callable[[Any], Any]] = {}
    
    def register_transform(self, mask_bits: int, fn: Callable[[Any], Any]) -> None:
        self.transforms[mask_bits] = fn
    
    def transform(self, data: Any, mask: Mask) -> Any:
        if mask.bits & 0x0800:
            transform = self.transforms.get(mask.bits)
            if transform:
                return transform(data)
        
        return data
