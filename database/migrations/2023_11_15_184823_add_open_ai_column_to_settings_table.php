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
        Schema::table(config('languages.table_settings'), function (Blueprint $table) {
            $table->boolean('enable_open_ai_translations')->default(false)->after('import_vendor');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(config('languages.table_settings'), function (Blueprint $table) {
                Schema::dropColumns(config('languages.table_settings'), ['enable_open_ai_translations']);
        });
    }
};
