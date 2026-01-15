<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\CoordinateResolutionException;
use App\Http\Requests\TunnelRequest;
use App\Models\TunnelLog;
use App\Services\CallerDetector;
use App\Services\CoordinateResolver;
use App\Services\ProjectionRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Ramsey\Uuid\Uuid;
use Throwable;

/**
 * Tunnel controller - Single /sync endpoint.
 *
 * Implements the "API-Less Architecture" principle with a single tunnel
 * entry point that receives coordinate requests and returns projections.
 *
 * No semantic routing; all requests go through /sync.
 */
final class TunnelController extends Controller
{
    /**
     * Handle tunnel request and return projection.
     *
     * Flow:
     * 1. Validate request (TunnelRequest)
     * 2. Detect caller type (bot/auth/attacker)
     * 3. Resolve coordinate (3 layers)
     * 4. Render projection based on caller type
     * 5. Log request to tunnel_logs
     * 6. Return response
     *
     * @param TunnelRequest $request The tunnel request
     * @param CallerDetector $callerDetector Caller detection service
     * @param CoordinateResolver $coordinateResolver Coordinate resolution service
     * @param ProjectionRenderer $projectionRenderer Projection rendering service
     * @return JsonResponse The projected response
     */
    public function sync(
        TunnelRequest $request,
        CallerDetector $callerDetector,
        CoordinateResolver $coordinateResolver,
        ProjectionRenderer $projectionRenderer,
    ): JsonResponse {
        $startTime = microtime(true);
        $requestId = Uuid::uuid4()->toString();

        try {
            // 1. Extract validated request data
            $coordinateKey = $request->getTarget();

            // 2. Detect caller type
            $callerMask = $callerDetector->detect($request);

            // 3. Resolve coordinate through 3 layers
            $coordinateData = $coordinateResolver->resolve($coordinateKey);

            // 4. Render projection based on caller type
            $projection = $projectionRenderer->render(
                $coordinateKey,
                $coordinateData,
                $callerMask
            );

            // Calculate execution time
            $executionTime = (int)((microtime(true) - $startTime) * 1000);

            // 5. Log successful request
            $this->logTunnelRequest(
                requestId: $requestId,
                coordinateKey: $coordinateKey,
                callerMask: $callerMask,
                request: $request,
                responseCode: 200,
                responseBits: $callerMask,
                executionTimeMs: $executionTime,
            );

            // 6. Return projection response
            return response()->json([
                'status' => 200,
                'request_id' => $requestId,
                'data' => $projection,
            ], 200);

        } catch (CoordinateResolutionException $e) {
            return $this->handleCoordinateError(
                requestId: $requestId,
                callerMask: $callerDetector->detect($request),
                request: $request,
                executionTime: (int)((microtime(true) - $startTime) * 1000),
                exception: $e,
            );

        } catch (Throwable $e) {
            return $this->handleServerError(
                requestId: $requestId,
                callerMask: $callerDetector->detect($request),
                request: $request,
                executionTime: (int)((microtime(true) - $startTime) * 1000),
                exception: $e,
            );
        }
    }

    /**
     * Handle coordinate resolution errors.
     *
     * @param string $requestId Unique request identifier
     * @param int $callerMask The caller type bitmask
     * @param Request $request The HTTP request
     * @param int $executionTime Execution time in milliseconds
     * @param Throwable $exception The exception that was thrown
     * @return JsonResponse
     */
    private function handleCoordinateError(
        string $requestId,
        int $callerMask,
        Request $request,
        int $executionTime,
        Throwable $exception,
    ): JsonResponse {
        $this->logTunnelRequest(
            requestId: $requestId,
            coordinateKey: null,
            callerMask: $callerMask,
            request: $request,
            responseCode: 404,
            responseBits: null,
            executionTimeMs: $executionTime,
        );

        return response()->json([
            'status' => 404,
            'request_id' => $requestId,
            'data' => null,
        ], 404);
    }

    /**
     * Handle server errors.
     *
     * @param string $requestId Unique request identifier
     * @param int $callerMask The caller type bitmask
     * @param Request $request The HTTP request
     * @param int $executionTime Execution time in milliseconds
     * @param Throwable $exception The exception that was thrown
     * @return JsonResponse
     */
    private function handleServerError(
        string $requestId,
        int $callerMask,
        Request $request,
        int $executionTime,
        Throwable $exception,
    ): JsonResponse {
        $this->logTunnelRequest(
            requestId: $requestId,
            coordinateKey: null,
            callerMask: $callerMask,
            request: $request,
            responseCode: 500,
            responseBits: null,
            executionTimeMs: $executionTime,
        );

        // Log exception for debugging (only in development)
        if (config('app.debug')) {
            \Log::error('Tunnel error', [
                'request_id' => $requestId,
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }

        return response()->json([
            'status' => 500,
            'request_id' => $requestId,
            'data' => null,
        ], 500);
    }

    /**
     * Log tunnel request to audit trail.
     *
     * @param string $requestId Unique request identifier
     * @param string|null $coordinateKey The coordinate key requested
     * @param int $callerMask The caller type bitmask
     * @param Request $request The HTTP request
     * @param int $responseCode HTTP response code
     * @param int|null $responseBits Response bitmask
     * @param int $executionTimeMs Execution time in milliseconds
     * @return void
     */
    private function logTunnelRequest(
        string $requestId,
        ?string $coordinateKey,
        int $callerMask,
        Request $request,
        int $responseCode,
        ?int $responseBits,
        int $executionTimeMs,
    ): void {
        try {
            TunnelLog::create([
                'request_id' => $requestId,
                'coordinate_key' => $coordinateKey,
                'caller_mask' => $callerMask,
                'user_agent' => $request->header('User-Agent'),
                'ip_address' => $request->ip(),
                'response_code' => $responseCode,
                'response_bits' => $responseBits,
                'execution_time_ms' => $executionTimeMs,
            ]);
        } catch (Throwable $e) {
            // Silently fail logging to avoid breaking the response
            \Log::warning('Failed to log tunnel request', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
