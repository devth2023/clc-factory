from typing import Any, Dict
from core.loader import TypeLoader
from nodes.resolver import TypeResolverNode
from nodes.detection import MaskDetectorNode
from nodes.validation import ValidatorNode
from nodes.processing import SanitizerNode, TransformerNode
from nodes.swapping import SwapNode
from nodes.caching import CacheNode
from nodes.response import ResponseBuilderNode

class Pipeline:
    def __init__(self, yaml_path: str):
        self.loader = TypeLoader(yaml_path)
        
        self.resolver = TypeResolverNode(self.loader)
        self.detector = MaskDetectorNode()
        self.validator = ValidatorNode()
        self.sanitizer = SanitizerNode()
        self.swap = SwapNode(self.loader)
        self.transformer = TransformerNode()
        self.cache = CacheNode()
        self.response = ResponseBuilderNode()
        
        self._build_chain()
    
    def _build_chain(self) -> None:
        self.resolver.next_nodes = [self.detector]
        self.detector.next_nodes = [self.validator]
        self.validator.next_nodes = [self.sanitizer]
        self.sanitizer.next_nodes = [self.swap]
        self.swap.next_nodes = [self.transformer]
        self.transformer.next_nodes = [self.cache]
        self.cache.next_nodes = [self.response]
    
    def execute(self, payload: dict, user_agent: str = '', auth_token: str = '') -> dict:
        request_data = {
            'target': payload.get('target'),
            'payload': payload.get('payload'),
            'user_agent': user_agent,
            'auth_token': auth_token,
            'mask': 0x0000,
            'valid': True
        }
        
        return self.resolver.execute(request_data)
