<?php

declare(strict_types=1);

use App\Http\Controllers\TunnelController;
use Illuminate\Support\Facades\Route;

/**
 * API Routes - Single Tunnel Only
 *
 * The CLC system implements an API-Less architecture with a single
 * tunnel entry point for coordinate requests and projections.
 *
 * No semantic routing; all requests go through /sync.
 */

Route::post('/sync', [TunnelController::class, 'sync'])->name('tunnel.sync');
