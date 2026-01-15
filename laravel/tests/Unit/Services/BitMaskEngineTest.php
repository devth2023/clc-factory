<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\BitPosition;
use App\Exceptions\InvalidBitmaskException;
use App\Services\BitMaskEngine;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Services\BitMaskEngine
 */
final class BitMaskEngineTest extends TestCase
{
    private BitMaskEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new BitMaskEngine();
    }

    /**
     * @test
     * @dataProvider validBitPositionProvider
     */
    public function setBitSetsCorrectBitValue(int $position): void
    {
        $flags = 0;
        $result = $this->engine->setBit($flags, $position);

        $expected = 1 << $position;
        self::assertSame($expected, $result);
    }

    /**
     * @test
     * @dataProvider validBitPositionProvider
     */
    public function clearBitClearsCorrectBit(int $position): void
    {
        $flags = (1 << 63); // All bits set (max int)
        $result = $this->engine->clearBit($flags, $position);

        self::assertFalse($this->engine->hasBit($result, $position));
    }

    /**
     * @test
     * @dataProvider validBitPositionProvider
     */
    public function hasBitDetectsBitCorrectly(int $position): void
    {
        $flags = 1 << $position;
        self::assertTrue($this->engine->hasBit($flags, $position));

        self::assertFalse($this->engine->hasBit(0, $position));
    }

    /**
     * @test
     */
    public function toggleBitTogglesCorrectBit(): void
    {
        $flags = 0;
        $toggled = $this->engine->toggleBit($flags, 5);

        self::assertTrue($this->engine->hasBit($toggled, 5));

        $toggledAgain = $this->engine->toggleBit($toggled, 5);
        self::assertFalse($this->engine->hasBit($toggledAgain, 5));
    }

    /**
     * @test
     */
    public function applyMaskReturnsIntersection(): void
    {
        $flags = 0b1111_0000; // 240
        $mask = 0b1100_1100;  // 204

        $result = $this->engine->applyMask($flags, $mask);

        self::assertSame(0b1100_0000, $result); // 192
    }

    /**
     * @test
     */
    public function hasMaskDetectsWhenAllBitsAreSet(): void
    {
        $flags = 0b1111_0000;
        $mask = 0b1100_0000;

        self::assertTrue($this->engine->hasMask($flags, $mask));
    }

    /**
     * @test
     */
    public function hasMaskReturnsFalseWhenNotAllBitsSet(): void
    {
        $flags = 0b1100_0000;
        $mask = 0b1111_0000;

        self::assertFalse($this->engine->hasMask($flags, $mask));
    }

    /**
     * @test
     */
    public function hasAnyMaskDetectsAnyOverlap(): void
    {
        $flags = 0b1000_0000;
        $mask = 0b1111_0000;

        self::assertTrue($this->engine->hasAnyMask($flags, $mask));
    }

    /**
     * @test
     */
    public function hasAnyMaskReturnsFalseWhenNoOverlap(): void
    {
        $flags = 0b0000_0011;
        $mask = 0b1111_1100;

        self::assertFalse($this->engine->hasAnyMask($flags, $mask));
    }

    /**
     * @test
     */
    public function setMaskCombinesBits(): void
    {
        $flags = 0b1100_0000;
        $mask = 0b0000_0011;

        $result = $this->engine->setMask($flags, $mask);

        self::assertSame(0b1100_0011, $result);
    }

    /**
     * @test
     */
    public function clearMaskRemovesBits(): void
    {
        $flags = 0b1111_1111;
        $mask = 0b0000_1111;

        $result = $this->engine->clearMask($flags, $mask);

        self::assertSame(0b1111_0000, $result);
    }

    /**
     * @test
     */
    public function buildMaskCombinesMultiplePositions(): void
    {
        $mask = $this->engine->buildMask(
            BitPosition::IS_ACTIVE,
            BitPosition::IS_VERIFIED,
            BitPosition::CAN_READ
        );

        self::assertTrue($this->engine->hasBit($mask, BitPosition::IS_ACTIVE->value));
        self::assertTrue($this->engine->hasBit($mask, BitPosition::IS_VERIFIED->value));
        self::assertTrue($this->engine->hasBit($mask, BitPosition::CAN_READ->value));
        self::assertFalse($this->engine->hasBit($mask, BitPosition::IS_BANNED->value));
    }

    /**
     * @test
     */
    public function countSetBitsReturnsCorrectCount(): void
    {
        $flags = 0b1111_0000; // 4 bits set
        self::assertSame(4, $this->engine->countSetBits($flags));

        $flags = 0b1111_1111; // 8 bits set
        self::assertSame(8, $this->engine->countSetBits($flags));

        self::assertSame(0, $this->engine->countSetBits(0));
    }

    /**
     * @test
     */
    public function setBitThrowsOnInvalidPosition(): void
    {
        $this->expectException(InvalidBitmaskException::class);
        $this->engine->setBit(0, 64); // Out of range
    }

    /**
     * @test
     */
    public function clearBitThrowsOnInvalidPosition(): void
    {
        $this->expectException(InvalidBitmaskException::class);
        $this->engine->clearBit(0, -1); // Negative
    }

    /**
     * @test
     */
    public function applyMaskThrowsOnNegativeFlags(): void
    {
        $this->expectException(InvalidBitmaskException::class);
        $this->engine->applyMask(-1, 0xFF);
    }

    /**
     * @test
     */
    public function applyMaskThrowsOnNegativeMask(): void
    {
        $this->expectException(InvalidBitmaskException::class);
        $this->engine->applyMask(0xFF, -1);
    }

    /**
     * Provider for valid bit positions (0-63).
     *
     * @return array<array<int>>
     */
    public static function validBitPositionProvider(): array
    {
        return array_map(
            fn (int $pos) => [$pos],
            range(0, 63)
        );
    }
}
