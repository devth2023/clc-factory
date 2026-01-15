from typing import List
from clc.enums import BitPosition
from clc.exceptions import InvalidBitmaskException


class BitMaskEngine:
    MIN_BIT_POSITION = 0
    MAX_BIT_POSITION = 63

    def set_bit(self, flags: int, position: int) -> int:
        self._validate_bit_position(position)
        return flags | (1 << position)

    def clear_bit(self, flags: int, position: int) -> int:
        self._validate_bit_position(position)
        return flags & ~(1 << position)

    def toggle_bit(self, flags: int, position: int) -> int:
        self._validate_bit_position(position)
        return flags ^ (1 << position)

    def has_bit(self, flags: int, position: int) -> bool:
        self._validate_bit_position(position)
        return bool(((flags >> position) & 1))

    def apply_mask(self, flags: int, mask: int) -> int:
        self._validate_flags(flags)
        self._validate_flags(mask)
        return flags & mask

    def has_mask(self, flags: int, mask: int) -> bool:
        self._validate_flags(flags)
        self._validate_flags(mask)
        return (flags & mask) == mask

    def has_any_mask(self, flags: int, mask: int) -> bool:
        self._validate_flags(flags)
        self._validate_flags(mask)
        return (flags & mask) != 0

    def set_mask(self, flags: int, mask: int) -> int:
        self._validate_flags(flags)
        self._validate_flags(mask)
        return flags | mask

    def clear_mask(self, flags: int, mask: int) -> int:
        self._validate_flags(flags)
        self._validate_flags(mask)
        return flags & ~mask

    def build_mask(self, *positions: BitPosition) -> int:
        mask = 0
        for position in positions:
            mask |= 1 << position.value
        return mask

    def count_set_bits(self, flags: int) -> int:
        self._validate_flags(flags)
        return bin(flags).count("1")

    def _validate_bit_position(self, position: int) -> None:
        if position < self.MIN_BIT_POSITION or position > self.MAX_BIT_POSITION:
            raise InvalidBitmaskException.invalid_bit_position(position)

    def _validate_flags(self, flags: int) -> None:
        if flags < 0:
            raise InvalidBitmaskException.negative_flag_value(flags)
