<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tunnel log model - Audit trail for all /sync requests.
 *
 * Records all requests and responses for security, debugging, and analytics.
 *
 * @property int $id
 * @property string $request_id Unique request identifier
 * @property string|null $coordinate_key The coordinate requested
 * @property int $caller_mask The detected caller type mask
 * @property string|null $user_agent The User-Agent header
 * @property string|null $ip_address The client IP address
 * @property int $response_code HTTP response code
 * @property int|null $response_bits The response bitmask
 * @property int $execution_time_ms Request execution time in milliseconds
 * @property \Carbon\Carbon $created_at
 */
final class TunnelLog extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'request_id',
        'coordinate_key',
        'caller_mask',
        'user_agent',
        'ip_address',
        'response_code',
        'response_bits',
        'execution_time_ms',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'caller_mask' => 'integer',
        'response_code' => 'integer',
        'response_bits' => 'integer',
        'execution_time_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Get caller mask as hexadecimal.
     *
     * @return string
     */
    public function getCallerMaskHex(): string
    {
        return dechex($this->caller_mask);
    }

    /**
     * Get response bits as hexadecimal.
     *
     * @return string|null
     */
    public function getResponseBitsHex(): ?string
    {
        return $this->response_bits ? dechex($this->response_bits) : null;
    }

    /**
     * Check if request was successful (2xx response).
     *
     * @return bool
     */
    public function wasSuccessful(): bool
    {
        return $this->response_code >= 200 && $this->response_code < 300;
    }

    /**
     * Check if request was denied (4xx response).
     *
     * @return bool
     */
    public function wasDenied(): bool
    {
        return $this->response_code >= 400 && $this->response_code < 500;
    }

    /**
     * Check if request had error (5xx response).
     *
     * @return bool
     */
    public function hadError(): bool
    {
        return $this->response_code >= 500;
    }
}
