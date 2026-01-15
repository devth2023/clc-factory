<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The database connection to use.
     */
    protected string $connection = 'mysql';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tunnel_logs', function (Blueprint $table): void {
            $table->id();

            // Request Identity
            $table->string('request_id', 64)->unique()->index();
            $table->string('coordinate_key', 64)->nullable()->index();

            // Caller Information
            $table->unsignedBigInteger('caller_mask')->index();
            $table->text('user_agent')->nullable();
            $table->string('ip_address', 45)->nullable();

            // Response
            $table->unsignedTinyInteger('response_code');
            $table->unsignedBigInteger('response_bits')->nullable();

            // Performance
            $table->unsignedSmallInteger('execution_time_ms');

            // Metadata
            $table->timestamp('created_at')->useCurrent();

            // Indexes for analytics
            $table->index(['coordinate_key', 'created_at']);
            $table->index(['caller_mask', 'created_at']);
            $table->index(['response_code', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tunnel_logs');
    }
};
