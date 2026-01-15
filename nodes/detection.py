from typing import Any, Dict
from nodes.base import Node

class MaskDetectorNode(Node):
    def __init__(self):
        super().__init__('mask_detector')
        self.seo_bots = ['googlebot', 'bingbot', 'slurp']
    
    def process(self, data: Any) -> Dict[str, Any]:
        ua = data.get('user_agent', '')
        token = data.get('auth_token', '')
        
        mask = 0x0000
        mask |= 0x0100 if any(bot in ua.lower() for bot in self.seo_bots) else 0
        mask |= 0x0200 if token else 0
        mask |= 0x0400
        
        data['mask'] = mask
        return data
