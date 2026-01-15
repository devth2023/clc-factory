<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\CoordinateResolutionException;
use App\Models\CoordinateMapping;
use App\Services\CoordinateResolver;
use App\ValueObjects\CoordinateData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @covers \App\Services\CoordinateResolver
 */
final class CoordinateResolverTest extends TestCase
{
    use RefreshDatabase;

    private CoordinateResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(CoordinateResolver::class);
    }

    /**
     * @test
     */
    public function resolveReturnsCoordinateDataForValidKey(): void
    {
        $mapping = CoordinateMapping::create([
            'coordinate_key' => 'COORD_X101',
            'label' => 'User Profile',
            'description' => 'User profile data',
            'seo_keywords' => ['user', 'profile'],
            'schema_type' => 'Person',
            'coordinate_address' => '1010.0101@',
            'bitmask_policy' => 0x0001,
            'is_active' => true,
        ]);

        $result = $this->resolver->resolve('COORD_X101');

        self::assertInstanceOf(CoordinateData::class, $result);
        self::assertSame('COORD_X101', $result->coordinateKey);
        self::assertSame('User Profile', $result->label);
        self::assertSame(0x0001, $result->bitmaskPolicy);
    }

    /**
     * @test
     */
    public function resolveThrowsForMissingCoordinate(): void
    {
        $this->expectException(CoordinateResolutionException::class);
        $this->resolver->resolve('NONEXISTENT');
    }

    /**
     * @test
     */
    public function resolveIgnoresInactiveCoordinates(): void
    {
        CoordinateMapping::create([
            'coordinate_key' => 'COORD_X101',
            'coordinate_address' => '1010.0101@',
            'bitmask_policy' => 0x0001,
            'is_active' => false,
        ]);

        $this->expectException(CoordinateResolutionException::class);
        $this->resolver->resolve('COORD_X101');
    }

    /**
     * @test
     */
    public function resolveMaskReturnsOnlyBitmaskPolicy(): void
    {
        CoordinateMapping::create([
            'coordinate_key' => 'COORD_X101',
            'coordinate_address' => '1010.0101@',
            'bitmask_policy' => 0xFFFF,
            'is_active' => true,
        ]);

        $mask = $this->resolver->resolveMask('COORD_X101');

        self::assertSame(0xFFFF, $mask);
    }

    /**
     * @test
     */
    public function resolveGlossaryReturnsOnlyLayer1Data(): void
    {
        CoordinateMapping::create([
            'coordinate_key' => 'COORD_X101',
            'label' => 'Test Label',
            'description' => 'Test Description',
            'seo_keywords' => ['test'],
            'schema_type' => 'Thing',
            'coordinate_address' => '1010.0101@',
            'bitmask_policy' => 0x0001,
            'is_active' => true,
        ]);

        $glossary = $this->resolver->resolveGlossary('COORD_X101');

        self::assertSame('Test Label', $glossary['label']);
        self::assertSame('Test Description', $glossary['description']);
        self::assertSame(['test'], $glossary['seo_keywords']);
        self::assertSame('Thing', $glossary['schema_type']);
    }

    /**
     * @test
     */
    public function resolveAddressReturnsOnlyLayer2Data(): void
    {
        CoordinateMapping::create([
            'coordinate_key' => 'COORD_X101',
            'coordinate_address' => '1234.5678@',
            'bitmask_policy' => 0x0001,
            'is_active' => true,
        ]);

        $address = $this->resolver->resolveAddress('COORD_X101');

        self::assertSame('1234.5678@', $address);
    }

    /**
     * @test
     */
    public function resolveManyReturnsMultipleCoordinates(): void
    {
        CoordinateMapping::create([
            'coordinate_key' => 'COORD_X101',
            'coordinate_address' => '1010.0101@',
            'bitmask_policy' => 0x0001,
            'is_active' => true,
        ]);

        CoordinateMapping::create([
            'coordinate_key' => 'COORD_X102',
            'coordinate_address' => '1020.0102@',
            'bitmask_policy' => 0x0002,
            'is_active' => true,
        ]);

        $results = $this->resolver->resolveMany('COORD_X101', 'COORD_X102');

        self::assertCount(2, $results);
        self::assertArrayHasKey('COORD_X101', $results);
        self::assertArrayHasKey('COORD_X102', $results);
        self::assertSame(0x0001, $results['COORD_X101']->bitmaskPolicy);
        self::assertSame(0x0002, $results['COORD_X102']->bitmaskPolicy);
    }

    /**
     * @test
     */
    public function resolveManyThrowsIfAnyCoordinateNotFound(): void
    {
        CoordinateMapping::create([
            'coordinate_key' => 'COORD_X101',
            'coordinate_address' => '1010.0101@',
            'bitmask_policy' => 0x0001,
            'is_active' => true,
        ]);

        $this->expectException(CoordinateResolutionException::class);
        $this->resolver->resolveMany('COORD_X101', 'NONEXISTENT');
    }

    /**
     * @test
     */
    public function resolveOrNullReturnsNullForMissing(): void
    {
        $result = $this->resolver->resolveOrNull('NONEXISTENT');

        self::assertNull($result);
    }

    /**
     * @test
     */
    public function resolveOrNullReturnsCoordinateDataIfFound(): void
    {
        CoordinateMapping::create([
            'coordinate_key' => 'COORD_X101',
            'coordinate_address' => '1010.0101@',
            'bitmask_policy' => 0x0001,
            'is_active' => true,
        ]);

        $result = $this->resolver->resolveOrNull('COORD_X101');

        self::assertInstanceOf(CoordinateData::class, $result);
    }

    /**
     * @test
     */
    public function existsReturnsTrueForActiveCoordinate(): void
    {
        CoordinateMapping::create([
            'coordinate_key' => 'COORD_X101',
            'coordinate_address' => '1010.0101@',
            'bitmask_policy' => 0x0001,
            'is_active' => true,
        ]);

        self::assertTrue($this->resolver->exists('COORD_X101'));
    }

    /**
     * @test
     */
    public function existsReturnsFalseForMissingCoordinate(): void
    {
        self::assertFalse($this->resolver->exists('NONEXISTENT'));
    }

    /**
     * @test
     */
    public function validateCoordinateAddressAcceptsValidFormat(): void
    {
        $this->resolver->validateCoordinateAddress('1010.0101@');
        $this->resolver->validateCoordinateAddress('ABCD.EFGH@');
        $this->resolver->validateCoordinateAddress('0000.0000@');

        self::assertTrue(true); // No exception thrown
    }

    /**
     * @test
     * @dataProvider invalidAddressProvider
     */
    public function validateCoordinateAddressRejectsInvalidFormat(string $address): void
    {
        $this->expectException(CoordinateResolutionException::class);
        $this->resolver->validateCoordinateAddress($address);
    }

    /**
     * Provider for invalid coordinate addresses.
     *
     * @return array<array<string>>
     */
    public static function invalidAddressProvider(): array
    {
        return [
            ['1010.0101'], // Missing terminator
            ['1010.0101@@'], // Double terminator
            ['101.0101@'], // Too few hex digits in first part
            ['1010.101@'], // Too few hex digits in second part
            ['1010.0101@extra'], // Extra characters
            ['GGGG.HHHH@'], // Invalid hex characters
            [''], // Empty string
            ['@'], // Just terminator
        ];
    }
}
