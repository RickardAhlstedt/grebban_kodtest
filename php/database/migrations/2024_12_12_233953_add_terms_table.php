<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attributes', function(Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->timestampsTz(3);
        });

        Schema::create('attribute_terms', function(Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(false);
            $table->string('code')->nullable(false);
            $table->unsignedInteger('attribute_id');
            $table->timestampsTz(3);
            
            $table->foreign('attribute_id')
                ->references('id')
                ->on('attributes')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terms');
        Schema::dropIfExists('attributes');
    }
};
