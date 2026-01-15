<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\CallerType;
use App\Enums\ProjectionType;
use App\Models\CoordinateMapping;
use App\Models\Projection;
use App\Services\ProjectionRenderer;
use App\ValueObjects\CoordinateData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @covers \App\Services\ProjectionRenderer
 */
final class ProjectionRendererTest extends TestCase
{
    use RefreshDatabase;

    private ProjectionRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = app(ProjectionRenderer::class);
    }

    /**
     * @test
     */
    public function renderReturnsGlossaryForBotCaller(): void
    {
        $coordinateData = $this->createCoordinateData();
        $callerMask = CallerType::BOT->value;

        Projection::create([
            'coordinate_key' => 'COORD_X101',
            'projection_type' => ProjectionType::GLOSSARY->value,
            'payload' => ['label' => 'Public Label'],
        ]);

        $result = $this->renderer->render('COORD_X101', $coordinateData, $callerMask);

        self::assertSame('glossary', $result['type']);
        self::assertSame('0x100', $result['mask']);
    }

    /**
     * @test
     */
    public function renderReturnsPrivateForAuthenticatedCaller(): void
    {
        $coordinateData = $this->createCoordinateData();
        $callerMask = CallerType::AUTHENTICATED->value;

        Projection::create([
            'coordinate_key' => 'COORD_X101',
            'projection_type' => ProjectionType::PRIVATE->value,
            'payload' => ['coordinate_key' => 'COORD_X101', 'secret' => 'data'],
        ]);

        $result = $this->renderer->render('COORD_X101', $coordinateData, $callerMask);

        self::assertSame('private', $result['type']);
        self::assertSame('0x200', $result['mask']);
    }

    /**
     * @test
     */
    public function renderReturnsDeceptionForUnknownCaller(): void
    {
        $coordinateData = $this->createCoordinateData();
        $callerMask = CallerType::ATTACKER->value;

        Projection::create([
            'coordinate_key' => 'COORD_X101',
            'projection_type' => ProjectionType::DECEPTION->value,
            'payload' => ['error' => 'INVALID_COORDINATE'],
        ]);

        $result = $this->renderer->render('COORD_X101', $coordinateData, $callerMask);

        self::assertSame('deception', $result['type']);
        self::assertSame('0x400', $result['mask']);
    }

    /**
     * @test
     */
    public function renderGlossaryReturnsPublicMetadata(): void
    {
        $coordinateData = new CoordinateData(
            coordinateKey: 'COORD_X101',
            label: 'User Profile',
            description: 'Profile description',
            seoKeywords: ['user', 'profile'],
            schemaType: 'Person',
            coordinateAddress: '1010.0101@',
            bitmaskPolicy: 0x0001,
            version: 1,
            isActive: true,
        );

        $glossary = $this->renderer->renderGlossary($coordinateData);

        self::assertSame('User Profile', $glossary['label']);
        self::assertSame('Profile description', $glossary['description']);
        self::assertSame(['user', 'profile'], $glossary['keywords']);
        self::assertSame('Person', $glossary['schema']);
    }

    /**
     * @test
     */
    public function renderPrivateReturnsCoordinateData(): void
    {
        $coordinateData = new CoordinateData(
            coordinateKey: 'COORD_X101',
            label: 'User Profile',
            description: null,
            seoKeywords: null,
            schemaType: null,
            coordinateAddress: '1010.0101@',
            bitmaskPolicy: 0x0001,
            version: 2,
            isActive: true,
        );

        $private = $this->renderer->renderPrivate($coordinateData);

        self::assertSame('COORD_X101', $private['coordinate_key']);
        self::assertSame('1010.0101@', $private['address']);
        self::assertSame('1', $private['bitmask']);
        self::assertSame(2, $private['version']);
    }

    /**
     * @test
     */
    public function renderDeceptionReturnsHoneypotData(): void
    {
        Projection::create([
            'coordinate_key' => 'COORD_X101',
            'projection_type' => ProjectionType::DECEPTION->value,
            'payload' => ['honeypot' => 'trap_data'],
        ]);

        $deception = $this->renderer->renderDeception('COORD_X101');

        self::assertSame('trap_data', $deception['honeypot']);
    }

    /**
     * @test
     */
    public function renderDeceptionReturnsErrorWhenProjectionMissing(): void
    {
        $deception = $this->renderer->renderDeception('NONEXISTENT');

        self::assertSame('INVALID_COORDINATE', $deception['error']);
    }

    /**
     * @test
     */
    public function renderGivesBotPriorityOverAuth(): void
    {
        $coordinateData = $this->createCoordinateData();
        // Combine both flags - bot should be selected
        $callerMask = CallerType::BOT->value | CallerType::AUTHENTICATED->value;

        Projection::create([
            'coordinate_key' => 'COORD_X101',
            'projection_type' => ProjectionType::GLOSSARY->value,
            'payload' => ['type' => 'glossary'],
        ]);

        $result = $this->renderer->render('COORD_X101', $coordinateData, $callerMask);

        self::assertSame('glossary', $result['type']);
    }

    /**
     * Create a test coordinate data object.
     *
     * @return CoordinateData
     */
    private function createCoordinateData(): CoordinateData
    {
        return new CoordinateData(
            coordinateKey: 'COORD_X101',
            label: 'Test Coordinate',
            description: 'Test description',
            seoKeywords: ['test'],
            schemaType: 'Thing',
            coordinateAddress: '1010.0101@',
            bitmaskPolicy: 0x0001,
            version: 1,
            isActive: true,
        );
    }
}
