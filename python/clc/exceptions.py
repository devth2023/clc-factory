from typing import Optional


class InvalidBitmaskException(Exception):
    @staticmethod
    def invalid_bit_position(position: int) -> "InvalidBitmaskException":
        return InvalidBitmaskException(
            f"Invalid bit position: {position}. Must be between 0 and 63."
        )

    @staticmethod
    def invalid_mask_value(mask: int) -> "InvalidBitmaskException":
        return InvalidBitmaskException(
            f"Invalid mask value: {mask}. Mask must be non-negative."
        )

    @staticmethod
    def negative_flag_value(flags: int) -> "InvalidBitmaskException":
        return InvalidBitmaskException(
            f"Invalid flag value: {flags}. Flags must be non-negative."
        )


class CoordinateResolutionException(Exception):
    @staticmethod
    def coordinate_not_found(coordinate_key: str) -> "CoordinateResolutionException":
        return CoordinateResolutionException(
            f"Coordinate not found: {coordinate_key}"
        )

    @staticmethod
    def invalid_coordinate_format(address: str) -> "CoordinateResolutionException":
        return CoordinateResolutionException(
            f"Invalid coordinate address format: {address}. Expected format: XXXX.YYYY@"
        )

    @staticmethod
    def layer_resolution_failed(
        layer: int, coordinate_key: str
    ) -> "CoordinateResolutionException":
        return CoordinateResolutionException(
            f"Layer {layer} resolution failed for coordinate: {coordinate_key}"
        )
