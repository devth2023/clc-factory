from types import Payload, Context, Response, Mask
from registry import MasterRegistry
from mask_detector import MaskDetector
from swap_vault import SwapVault
from validator import Validator
from sanitizer import Sanitizer
from transformer import Transformer
from cache_store import CacheStore

class UniversalResolver:
    def __init__(self, registry: MasterRegistry):
        self.registry = registry
        self.detector = MaskDetector()
        self.vault = SwapVault()
        self.validator = Validator()
        self.sanitizer = Sanitizer()
        self.transformer = Transformer()
        self.cache = CacheStore()
        self._initialize_vault()
    
    def _initialize_vault(self) -> None:
        coords = list(self.registry.layer2.map.keys())
        for coord_id in coords:
            human = self.registry.get_human(coord_id)
            deception = self.registry.get_deception(coord_id)
            
            if human and deception:
                public_data = {
                    'label': human.get('label'),
                    'description': human.get('description'),
                    'schema_type': human.get('schema_type')
                }
                self.vault.store(coord_id, public_data, {'full_data': coord_id})
    
    def resolve(self, payload_dict: dict, user_agent: str = '', auth_token: str = '') -> Response:
        payload = Payload(payload_dict.get('target'), payload_dict.get('payload'))
        
        if not self.validator.validate_payload(payload):
            return Response(400, None, Mask(0x0000))
        
        if not self.validator.validate_content(payload.content):
            return Response(400, None, Mask(0x0000))
        
        mask = self.detector.detect(user_agent, auth_token)
        context = Context(user_agent, auth_token, mask)
        
        cache_key = self.cache.key_from_payload(payload.target, user_agent)
        cached = self.cache.get(cache_key)
        if cached:
            return cached
        
        sanitized = self.sanitizer.sanitize(payload.content, mask)
        transformed = self.transformer.transform(sanitized, mask)
        
        swapped = self.vault.retrieve(payload.target, mask)
        
        response = Response(
            200,
            swapped if swapped is not None else transformed,
            mask
        )
        
        self.cache.set(cache_key, response)
        return response
