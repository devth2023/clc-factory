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
        Schema::create('projections', function (Blueprint $table): void {
            $table->id();

            // Coordinate Reference
            $table->string('coordinate_key', 64)->index();

            // Projection Type
            $table->enum('projection_type', ['glossary', 'private', 'deception']);

            // Payload (Shadow Data)
            $table->json('payload');

            // Metadata
            $table->timestamps();

            // Unique constraint per coordinate + type
            $table->unique(
                ['coordinate_key', 'projection_type'],
                'unique_projection'
            );

            // Foreign key
            $table->foreign('coordinate_key')
                ->references('coordinate_key')
                ->on('coordinate_mappings')
                ->onDelete('cascade');

            // Index for projection type lookups
            $table->index('projection_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projections');
    }
};
