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
        Schema::table('attribute_terms', function (Blueprint $table) {
            $table->integer('parent_id')->after('attribute_id')->nullable();

            $table->foreign('parent_id')
                ->references('id')
                ->on('attribute_terms')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attribute_terms', function (Blueprint $table) {
            $table->dropColumn('parent_id');
        });
    }
};
