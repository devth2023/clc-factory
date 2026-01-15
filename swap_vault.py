from typing import Dict, Any
from types import SwapPair, Mask

class SwapVault:
    def __init__(self):
        self.vault: Dict[str, SwapPair] = {}
    
    def store(self, coord_id: str, public: Any, private: Any) -> None:
        self.vault[coord_id] = SwapPair(public, private)
    
    def retrieve(self, coord_id: str, mask: Mask) -> Any:
        pair = self.vault.get(coord_id)
        if not pair:
            return None
        
        if mask.bits & 0x0200:
            return pair.private
        if mask.bits & 0x0100:
            return pair.public
        
        return pair.public
