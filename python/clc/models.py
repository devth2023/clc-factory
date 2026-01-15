from pydantic import BaseModel, Field
from typing import Optional, Any


class TunnelRequest(BaseModel):
    target: str = Field(..., min_length=1, max_length=64)
    payload: Optional[Any] = None


class TunnelResponse(BaseModel):
    status: int
    request_id: str
    data: Optional[Any] = None


class ErrorResponse(BaseModel):
    status: int
    error: str
    request_id: Optional[str] = None
