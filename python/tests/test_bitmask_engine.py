import pytest
from clc.services.bitmask_engine import BitMaskEngine
from clc.enums import BitPosition
from clc.exceptions import InvalidBitmaskException


class TestBitMaskEngine:
    @pytest.fixture
    def engine(self):
        return BitMaskEngine()

    def test_set_bit(self, engine):
        flags = 0
        result = engine.set_bit(flags, 5)
        assert result == (1 << 5)

    def test_clear_bit(self, engine):
        flags = 0xFF
        result = engine.clear_bit(flags, 5)
        assert not engine.has_bit(result, 5)

    def test_toggle_bit(self, engine):
        flags = 0
        toggled = engine.toggle_bit(flags, 5)
        assert engine.has_bit(toggled, 5)

        toggled_again = engine.toggle_bit(toggled, 5)
        assert not engine.has_bit(toggled_again, 5)

    def test_has_bit(self, engine):
        flags = 1 << 5
        assert engine.has_bit(flags, 5)
        assert not engine.has_bit(0, 5)

    def test_apply_mask(self, engine):
        flags = 0b1111_0000
        mask = 0b1100_1100
        result = engine.apply_mask(flags, mask)
        assert result == 0b1100_0000

    def test_has_mask(self, engine):
        flags = 0b1111_0000
        mask = 0b1100_0000
        assert engine.has_mask(flags, mask)

    def test_has_mask_false(self, engine):
        flags = 0b1100_0000
        mask = 0b1111_0000
        assert not engine.has_mask(flags, mask)

    def test_has_any_mask(self, engine):
        flags = 0b1000_0000
        mask = 0b1111_0000
        assert engine.has_any_mask(flags, mask)

    def test_has_any_mask_false(self, engine):
        flags = 0b0000_0011
        mask = 0b1111_1100
        assert not engine.has_any_mask(flags, mask)

    def test_set_mask(self, engine):
        flags = 0b1100_0000
        mask = 0b0000_0011
        result = engine.set_mask(flags, mask)
        assert result == 0b1100_0011

    def test_clear_mask(self, engine):
        flags = 0b1111_1111
        mask = 0b0000_1111
        result = engine.clear_mask(flags, mask)
        assert result == 0b1111_0000

    def test_build_mask(self, engine):
        mask = engine.build_mask(
            BitPosition.IS_ACTIVE,
            BitPosition.IS_VERIFIED,
            BitPosition.CAN_READ,
        )
        assert engine.has_bit(mask, BitPosition.IS_ACTIVE.value)
        assert engine.has_bit(mask, BitPosition.IS_VERIFIED.value)
        assert engine.has_bit(mask, BitPosition.CAN_READ.value)
        assert not engine.has_bit(mask, BitPosition.IS_BANNED.value)

    def test_count_set_bits(self, engine):
        flags = 0b1111_0000
        assert engine.count_set_bits(flags) == 4

        flags = 0b1111_1111
        assert engine.count_set_bits(flags) == 8

        assert engine.count_set_bits(0) == 0

    def test_invalid_bit_position(self, engine):
        with pytest.raises(InvalidBitmaskException):
            engine.set_bit(0, 64)

    def test_negative_flags(self, engine):
        with pytest.raises(InvalidBitmaskException):
            engine.apply_mask(-1, 0xFF)
