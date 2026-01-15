import os
from type_loader import TypeLoader
from nodes_core import (
    TypeResolverNode, MaskDetectorNode, ValidatorNode,
    SanitizerNode, SwapNode, TransformerNode, CacheNode, ResponseBuilderNode
)

class Pipeline:
    def __init__(self, yaml_path: str):
        self.type_loader = TypeLoader(yaml_path)
        self.type_resolver = TypeResolverNode(self.type_loader)
        self.mask_detector = MaskDetectorNode()
        self.validator = ValidatorNode()
        self.sanitizer = SanitizerNode()
        self.swap = SwapNode(self.type_loader)
        self.transformer = TransformerNode()
        self.cache = CacheNode()
        self.response_builder = ResponseBuilderNode()
        
        self._build_chain()
    
    def _build_chain(self) -> None:
        self.type_resolver.next_nodes = [self.mask_detector]
        self.mask_detector.next_nodes = [self.validator]
        self.validator.next_nodes = [self.sanitizer]
        self.sanitizer.next_nodes = [self.swap]
        self.swap.next_nodes = [self.transformer]
        self.transformer.next_nodes = [self.cache]
        self.cache.next_nodes = [self.response_builder]
    
    def execute(self, payload: dict, user_agent: str = '', auth_token: str = '') -> dict:
        request_data = {
            'target': payload.get('target'),
            'payload': payload.get('payload'),
            'user_agent': user_agent,
            'auth_token': auth_token,
            'mask': 0x0000,
            'valid': True
        }
        
        return self.type_resolver.execute(request_data)
