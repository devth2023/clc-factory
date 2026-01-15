import re
from typing import Optional, Dict, Any
from pydantic import BaseModel


class CoordinateData(BaseModel):
    coordinate_key: str
    label: str
    description: Optional[str] = None
    seo_keywords: Optional[list[str]] = None
    schema_type: Optional[str] = None
    coordinate_address: str
    bitmask_policy: int
    version: int
    is_active: bool

    def to_dict(self) -> Dict[str, Any]:
        return self.model_dump()

    def get_bitmask_hex(self) -> str:
        return hex(self.bitmask_policy)


class CoordinateResolver:
    COORDINATE_FORMAT = r"^[0-9a-f]{4}\.[0-9a-f]{4}@$"
    
    def __init__(self, registry_data: Dict[str, Any]):
        self.layer1 = registry_data.get("layer1_human_map", {})
        self.layer2 = registry_data.get("layer2_coordinate_registry", {})
        self.layer3 = registry_data.get("layer3_bitmask_core", {})

    def resolve(self, coordinate_key: str) -> CoordinateData:
        from clc.exceptions import CoordinateResolutionException

        if coordinate_key not in self.layer1:
            raise CoordinateResolutionException.coordinate_not_found(coordinate_key)

        glossary = self.layer1[coordinate_key]
        address = self.layer2.get(coordinate_key)
        mask = self.layer3.get(coordinate_key, 0)

        if not address:
            raise CoordinateResolutionException.layer_resolution_failed(2, coordinate_key)

        self._validate_coordinate_address(address)

        return CoordinateData(
            coordinate_key=coordinate_key,
            label=glossary.get("label", ""),
            description=glossary.get("description"),
            seo_keywords=glossary.get("seo_keywords"),
            schema_type=glossary.get("schema_type"),
            coordinate_address=address,
            bitmask_policy=mask,
            version=1,
            is_active=True,
        )

    def resolve_mask(self, coordinate_key: str) -> int:
        from clc.exceptions import CoordinateResolutionException

        if coordinate_key not in self.layer3:
            raise CoordinateResolutionException.coordinate_not_found(coordinate_key)

        return self.layer3[coordinate_key]

    def resolve_glossary(self, coordinate_key: str) -> Dict[str, Any]:
        from clc.exceptions import CoordinateResolutionException

        if coordinate_key not in self.layer1:
            raise CoordinateResolutionException.coordinate_not_found(coordinate_key)

        glossary = self.layer1[coordinate_key]
        return {
            "label": glossary.get("label", ""),
            "description": glossary.get("description"),
            "seo_keywords": glossary.get("seo_keywords"),
            "schema_type": glossary.get("schema_type"),
        }

    def resolve_address(self, coordinate_key: str) -> str:
        from clc.exceptions import CoordinateResolutionException

        if coordinate_key not in self.layer2:
            raise CoordinateResolutionException.coordinate_not_found(coordinate_key)

        return self.layer2[coordinate_key]

    def exists(self, coordinate_key: str) -> bool:
        return coordinate_key in self.layer1

    def _validate_coordinate_address(self, address: str) -> None:
        from clc.exceptions import CoordinateResolutionException

        if not re.match(self.COORDINATE_FORMAT, address):
            raise CoordinateResolutionException.invalid_coordinate_format(address)
