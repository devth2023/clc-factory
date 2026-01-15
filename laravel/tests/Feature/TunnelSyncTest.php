<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProjectionType;
use App\Models\CoordinateMapping;
use App\Models\Projection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @covers \App\Http\Controllers\TunnelController
 */
final class TunnelSyncTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function syncSuccessfullyResolvesAndRendersGlossary(): void
    {
        // Setup: Create coordinate and projection
        CoordinateMapping::create([
            'coordinate_key' => 'COORD_X101',
            'label' => 'User Profile',
            'description' => 'User profile data',
            'seo_keywords' => ['user', 'profile'],
            'schema_type' => 'Person',
            'coordinate_address' => '1010.0101@',
            'bitmask_policy' => 0x0001,
            'is_active' => true,
        ]);

        Projection::create([
            'coordinate_key' => 'COORD_X101',
            'projection_type' => ProjectionType::GLOSSARY->value,
            'payload' => [
                'label' => 'User Profile',
                'description' => 'User profile data',
                'keywords' => ['user', 'profile'],
                'schema' => 'Person',
            ],
        ]);

        // Act: Send request as GoogleBot
        $response = $this->postJson('/api/sync', [
            'target' => 'COORD_X101',
        ], [
            'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'request_id',
            'data' => ['type', 'data', 'mask'],
        ]);
        $response->assertJsonPath('data.type', 'glossary');
        $response->assertJsonPath('status', 200);
    }

    /**
     * @test
     */
    public function syncSuccessfullyRendersPrivateForAuthenticatedUser(): void
    {
        // Setup
        CoordinateMapping::create([
            'coordinate_key' => 'COORD_X101',
            'label' => 'User Profile',
            'coordinate_address' => '1010.0101@',
            'bitmask_policy' => 0x0001,
            'is_active' => true,
        ]);

        Projection::create([
            'coordinate_key' => 'COORD_X101',
            'projection_type' => ProjectionType::PRIVATE->value,
            'payload' => [
                'coordinate_key' => 'COORD_X101',
                'address' => '1010.0101@',
                'bitmask' => '1',
                'version' => 1,
            ],
        ]);

        // Act: Send request with auth token
        $response = $this->postJson('/api/sync', [
            'target' => 'COORD_X101',
        ], [
            'Authorization' => 'Bearer valid_token_xyz',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.type', 'private');
    }

    /**
     * @test
     */
    public function syncRendersDeceptionForUnknownCaller(): void
    {
        // Setup
        CoordinateMapping::create([
            'coordinate_key' => 'COORD_X101',
            'coordinate_address' => '1010.0101@',
            'bitmask_policy' => 0x0001,
            'is_active' => true,
        ]);

        Projection::create([
            'coordinate_key' => 'COORD_X101',
            'projection_type' => ProjectionType::DECEPTION->value,
            'payload' => ['error' => 'INVALID_COORDINATE'],
        ]);

        // Act: Send request without auth or recognized bot
        $response = $this->postJson('/api/sync', [
            'target' => 'COORD_X101',
        ], [
            'User-Agent' => 'Mozilla/5.0 (Unknown Browser)',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.type', 'deception');
    }

    /**
     * @test
     */
    public function syncReturns404ForMissingCoordinate(): void
    {
        // Act: Request nonexistent coordinate
        $response = $this->postJson('/api/sync', [
            'target' => 'NONEXISTENT_COORD',
        ]);

        // Assert
        $response->assertStatus(404);
        $response->assertJsonPath('status', 404);
        $response->assertJsonStructure(['status', 'request_id', 'data']);
    }

    /**
     * @test
     */
    public function syncValidatesTargetIsRequired(): void
    {
        // Act: Send request without target
        $response = $this->postJson('/api/sync', []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('target');
    }

    /**
     * @test
     */
    public function syncValidatesTargetFormat(): void
    {
        // Act: Send request with invalid target format
        $response = $this->postJson('/api/sync', [
            'target' => 'invalid-coord-with-dashes',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('target');
    }

    /**
     * @test
     */
    public function syncIncludesUniqueRequestId(): void
    {
        // Setup
        CoordinateMapping::create([
            'coordinate_key' => 'COORD_X101',
            'coordinate_address' => '1010.0101@',
            'bitmask_policy' => 0x0001,
            'is_active' => true,
        ]);

        Projection::create([
            'coordinate_key' => 'COORD_X101',
            'projection_type' => ProjectionType::GLOSSARY->value,
            'payload' => [],
        ]);

        // Act: Send two requests
        $response1 = $this->postJson('/api/sync', ['target' => 'COORD_X101']);
        $response2 = $this->postJson('/api/sync', ['target' => 'COORD_X101']);

        // Assert: Request IDs should be different
        $requestId1 = $response1->json('request_id');
        $requestId2 = $response2->json('request_id');

        self::assertNotSame($requestId1, $requestId2);
        self::assertNotEmpty($requestId1);
        self::assertNotEmpty($requestId2);
    }

    /**
     * @test
     */
    public function syncLogsRequestToTunnelLogs(): void
    {
        // Setup
        CoordinateMapping::create([
            'coordinate_key' => 'COORD_X101',
            'coordinate_address' => '1010.0101@',
            'bitmask_policy' => 0x0001,
            'is_active' => true,
        ]);

        Projection::create([
            'coordinate_key' => 'COORD_X101',
            'projection_type' => ProjectionType::GLOSSARY->value,
            'payload' => [],
        ]);

        // Act
        $response = $this->postJson('/api/sync', [
            'target' => 'COORD_X101',
        ], [
            'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)',
        ]);

        // Assert: Log entry should exist
        $requestId = $response->json('request_id');

        $this->assertDatabaseHas('tunnel_logs', [
            'request_id' => $requestId,
            'coordinate_key' => 'COORD_X101',
            'response_code' => 200,
        ]);
    }

    /**
     * @test
     */
    public function syncHandlesInactiveCoordinate(): void
    {
        // Setup: Create inactive coordinate
        CoordinateMapping::create([
            'coordinate_key' => 'COORD_X101',
            'coordinate_address' => '1010.0101@',
            'bitmask_policy' => 0x0001,
            'is_active' => false,  // Inactive
        ]);

        // Act
        $response = $this->postJson('/api/sync', [
            'target' => 'COORD_X101',
        ]);

        // Assert: Should return 404
        $response->assertStatus(404);
    }

    /**
     * @test
     */
    public function syncReturnsBotCallerMaskInResponse(): void
    {
        // Setup
        CoordinateMapping::create([
            'coordinate_key' => 'COORD_X101',
            'coordinate_address' => '1010.0101@',
            'bitmask_policy' => 0x0001,
            'is_active' => true,
        ]);

        Projection::create([
            'coordinate_key' => 'COORD_X101',
            'projection_type' => ProjectionType::GLOSSARY->value,
            'payload' => [],
        ]);

        // Act
        $response = $this->postJson('/api/sync', [
            'target' => 'COORD_X101',
        ], [
            'User-Agent' => 'Googlebot',
        ]);

        // Assert: Mask should be 0x0100 (BOT flag)
        $response->assertJsonPath('data.mask', '100');
    }
}
