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
        Schema::create('bit_registry', function (Blueprint $table): void {
            $table->unsignedTinyInteger('bit_position')->primary();

            // Semantic mapping (documentation only, not for logic)
            $table->string('bit_name', 64)->unique()->index();
            $table->string('bit_category', 32)->nullable()->index();

            // Description
            $table->text('description')->nullable();

            // Metadata
            $table->timestamps();

            // Index for category lookups
            $table->index(['bit_category', 'bit_position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bit_registry');
    }
};
