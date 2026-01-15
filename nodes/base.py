from typing import Any, List
from abc import ABC, abstractmethod

class Node(ABC):
    def __init__(self, node_id: str):
        self.node_id = node_id
        self.next_nodes: List[Node] = []
    
    @abstractmethod
    def process(self, data: Any) -> Any:
        pass
    
    def pipe(self, next_node: 'Node') -> 'Node':
        self.next_nodes.append(next_node)
        return next_node
    
    def execute(self, data: Any) -> Any:
        result = self.process(data)
        
        if self.next_nodes:
            for node in self.next_nodes:
                result = node.execute(result)
        
        return result
