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
        Schema::create('entities', function (Blueprint $table): void {
            $table->id();

            // Entity Identity
            $table->string('entity_type', 64)->index();
            $table->string('entity_id', 128);

            // Bit-State (Single Source of Truth)
            // 64 bits of state flags (0-63)
            $table->unsignedBigInteger('status_flags')->default(0)->index();

            // Coordinate Reference
            $table->string('coordinate_key', 64)->nullable()->index();

            // Metadata
            $table->timestamps();

            // Composite unique constraint
            $table->unique(['entity_type', 'entity_id'], 'unique_entity');

            // Foreign key to coordinate_mappings
            $table->foreign('coordinate_key')
                ->references('coordinate_key')
                ->on('coordinate_mappings')
                ->onDelete('set null');

            // Indexes for common queries
            $table->index(['entity_type', 'status_flags']);
            $table->index(['coordinate_key', 'entity_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};
