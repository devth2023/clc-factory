import os
from registry import MasterRegistry
from universal_resolver import UniversalResolver
from middleware import ValueSwapMiddleware

yaml_path = os.path.join(os.path.dirname(__file__), '..', 'master_registry.yaml')
registry = MasterRegistry(yaml_path)
resolver = UniversalResolver(registry)
middleware = ValueSwapMiddleware(resolver)

def handle_request(payload_dict: dict, user_agent: str = '', auth_token: str = '') -> dict:
    return middleware.process(payload_dict, user_agent, auth_token)
