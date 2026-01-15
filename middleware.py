from types import Response
from universal_resolver import UniversalResolver

class ValueSwapMiddleware:
    def __init__(self, resolver: UniversalResolver):
        self.resolver = resolver
    
    def process(self, payload_dict: dict, user_agent: str = '', auth_token: str = '') -> dict:
        response = self.resolver.resolve(payload_dict, user_agent, auth_token)
        
        return {
            'status': response.status_code,
            'data': response.data,
            'mask': hex(response.mask.bits)
        }
