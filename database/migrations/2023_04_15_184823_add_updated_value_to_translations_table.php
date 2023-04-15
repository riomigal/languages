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
        Schema::table(config('languages.table_translations'), function (Blueprint $table) {
            $table->text('old_value')->nullable()->after('value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if(Schema::hasColumn(config('languages.table_translations'), 'old_value')) {
            Schema::table(config('languages.table_translations'), function (Blueprint $table) {
                   Schema::dropColumns(config('languages.table_translations'), 'old_value');
            });
        }
    }
};
