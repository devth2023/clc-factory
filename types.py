from dataclasses import dataclass
from typing import Any, Dict

@dataclass(frozen=True)
class Coordinate:
    identity: str
    address: str

@dataclass(frozen=True)
class Mask:
    bits: int

@dataclass(frozen=True)
class Payload:
    target: str
    content: Any

@dataclass(frozen=True)
class Context:
    user_agent: str
    auth_token: str
    mask: Mask

@dataclass(frozen=True)
class SwapPair:
    public: Any
    private: Any

@dataclass(frozen=True)
class Response:
    status_code: int
    data: Any
    mask: Mask
