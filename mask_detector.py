from types import Context, Mask
from registry import MasterRegistry

class MaskDetector:
    def __init__(self):
        self.SEO_BOTS = ['googlebot', 'bingbot', 'slurp', 'duckduckbot']
        self.AUTH_HEADER = 'auth_token'
    
    def detect(self, user_agent: str, auth_token: str) -> Mask:
        bits = 0x0000
        
        if any(bot in user_agent.lower() for bot in self.SEO_BOTS):
            bits |= 0x0100
        
        if auth_token:
            bits |= 0x0200
        
        bits |= 0x0400
        
        return Mask(bits)
