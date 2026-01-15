from node import Node
from typing import Any, Dict, Callable
from type_loader import TypeLoader

class TypeResolverNode(Node):
    def __init__(self, type_loader: TypeLoader):
        super().__init__('type_resolver')
        self.loader = type_loader
    
    def process(self, data: Any) -> Dict[str, Any]:
        target = data.get('target')
        layer1 = self.loader.get_layer('layer1_human_map')
        layer3 = self.loader.get_layer('layer3_bitmask_core')
        
        data['coord_def'] = layer1.get(target, {})
        data['coord_mask'] = layer3.get(target, 0x0000)
        return data

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

class ValidatorNode(Node):
    def __init__(self):
        super().__init__('validator')
    
    def process(self, data: Any) -> Dict[str, Any]:
        target = data.get('target')
        content = data.get('payload')
        
        if not target or not isinstance(target, str) or len(target) > 256:
            data['valid'] = False
            return data
        
        allowed = (str, int, float, bool, dict, list, type(None))
        data['valid'] = isinstance(content, allowed)
        return data

class SanitizerNode(Node):
    def __init__(self, max_len: int = 1000):
        super().__init__('sanitizer')
        self.max_len = max_len
    
    def process(self, data: Any) -> Dict[str, Any]:
        content = data.get('payload')
        
        if isinstance(content, str):
            data['payload'] = content[:self.max_len]
        elif isinstance(content, dict):
            data['payload'] = {k: str(v)[:self.max_len] if isinstance(v, str) else v 
                              for k, v in content.items()}
        elif isinstance(content, list):
            data['payload'] = [str(x)[:self.max_len] if isinstance(x, str) else x for x in content]
        
        return data

class SwapNode(Node):
    def __init__(self, type_loader: TypeLoader):
        super().__init__('swap')
        self.loader = type_loader
    
    def process(self, data: Any) -> Dict[str, Any]:
        target = data.get('target')
        mask = data.get('mask', 0x0000)
        coord_def = data.get('coord_def', {})
        deceptions = self.loader.get_layer('deception_payloads')
        
        public = {
            'label': coord_def.get('label'),
            'description': coord_def.get('description'),
            'schema': coord_def.get('schema_type')
        }
        private = {'coord': target, 'full_def': coord_def}
        
        data['swapped'] = private if (mask & 0x0200) else public
        return data

class TransformerNode(Node):
    def __init__(self):
        super().__init__('transformer')
        self.transforms: Dict[int, Callable] = {}
    
    def register_transform(self, mask_bits: int, fn: Callable) -> None:
        self.transforms[mask_bits] = fn
    
    def process(self, data: Any) -> Dict[str, Any]:
        mask = data.get('mask', 0x0000)
        payload = data.get('payload')
        
        if mask & 0x0800:
            fn = self.transforms.get(mask)
            if fn:
                data['payload'] = fn(payload)
        
        return data

class CacheNode(Node):
    def __init__(self, ttl: int = 300):
        super().__init__('cache')
        self.store: Dict[str, Any] = {}
        self.ttl = ttl
    
    def process(self, data: Any) -> Dict[str, Any]:
        import hashlib
        
        target = data.get('target', '')
        ua = data.get('user_agent', '')
        key = hashlib.sha256(f"{target}:{ua}".encode()).hexdigest()[:16]
        
        if key in self.store:
            data['cached'] = self.store[key]
        else:
            data['cached'] = None
        
        data['cache_key'] = key
        return data

class ResponseBuilderNode(Node):
    def __init__(self):
        super().__init__('response_builder')
    
    def process(self, data: Any) -> Dict[str, Any]:
        return {
            'status': 200 if data.get('valid') else 400,
            'data': data.get('swapped') or data.get('payload'),
            'mask': hex(data.get('mask', 0x0000))
        }
