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
        Schema::create('coordinate_mappings', function (Blueprint $table): void {
            $table->id();

            // Layer 1: Glossary (Human-readable metadata)
            $table->string('coordinate_key', 64)->unique()->index();
            $table->string('label', 255)->nullable();
            $table->text('description')->nullable();
            $table->json('seo_keywords')->nullable();
            $table->string('schema_type', 128)->nullable();

            // Layer 2: Coordinate Address (Registry)
            $table->string('coordinate_address', 128)->unique()->index();

            // Layer 3: Bitmask Policy (The Truth)
            $table->unsignedBigInteger('bitmask_policy')->index();

            // Metadata
            $table->unsignedTinyInteger('version')->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['is_active', 'bitmask_policy']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coordinate_mappings');
    }
};
