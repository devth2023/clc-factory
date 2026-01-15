from typing import Any, Dict
from clc.enums import CallerType, ProjectionType
from clc.services.coordinate_resolver import CoordinateData


class ProjectionRenderer:
    def __init__(self, projection_data: Dict[str, Dict[str, Any]]):
        self.projections = projection_data

    def render(
        self, coordinate_key: str, coordinate_data: CoordinateData, caller_mask: int
    ) -> Dict[str, Any]:
        projection_type = self._select_projection_type(caller_mask)
        payload = self._get_projection_payload(coordinate_key, projection_type)

        return {
            "type": projection_type.value,
            "data": payload,
            "mask": hex(caller_mask),
        }

    def render_glossary(self, coordinate_data: CoordinateData) -> Dict[str, Any]:
        return {
            "label": coordinate_data.label,
            "description": coordinate_data.description,
            "keywords": coordinate_data.seo_keywords,
            "schema": coordinate_data.schema_type,
        }

    def render_private(self, coordinate_data: CoordinateData) -> Dict[str, Any]:
        return {
            "coordinate_key": coordinate_data.coordinate_key,
            "address": coordinate_data.coordinate_address,
            "bitmask": coordinate_data.get_bitmask_hex(),
            "version": coordinate_data.version,
        }

    def render_deception(self, coordinate_key: str) -> Dict[str, Any]:
        payload = self.projections.get(coordinate_key, {}).get(
            ProjectionType.DECEPTION.value
        )
        return payload or {"error": "INVALID_COORDINATE"}

    def _select_projection_type(self, caller_mask: int) -> ProjectionType:
        if (caller_mask & CallerType.BOT.value) == CallerType.BOT.value:
            return ProjectionType.GLOSSARY

        if (caller_mask & CallerType.AUTHENTICATED.value) == CallerType.AUTHENTICATED.value:
            return ProjectionType.PRIVATE

        return ProjectionType.DECEPTION

    def _get_projection_payload(
        self, coordinate_key: str, projection_type: ProjectionType
    ) -> Any:
        payload = self.projections.get(coordinate_key, {}).get(projection_type.value)
        return payload or {"error": "PROJECTION_NOT_FOUND"}
