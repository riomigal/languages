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
            $table->text('namespace')->nullable()->after('type');
            $table->text('group')->nullable()->after('namespace');
            $table->boolean('is_vendor')->default(false);
            $table->bigInteger('updated_by')->unsigned()->nullable();
            $table->bigInteger('previous_updated_by')->unsigned()->nullable();
            $table->bigInteger('approved_by')->unsigned()->nullable();
            $table->bigInteger('previous_approved_by')->unsigned()->nullable();
            $table->boolean('exported')->default(true);
        });

        Schema::table(config('languages.table_translations'), function (Blueprint $table) {
            Schema::dropColumns(config('languages.table_translations'), ['relative_path']);
            Schema::dropColumns(config('languages.table_translations'), ['relative_pathname']);
            Schema::dropColumns(config('languages.table_translations'), ['file']);
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(config('languages.table_translations'), function (Blueprint $table) {
                Schema::dropColumns(config('languages.table_translations'), ['old_value','namespace', 'group', 'is_vendor', 'updated_by', 'previous_updated_by', 'approved_by',  'previous_approved_by', 'exported']);
        });

        Schema::table(config('languages.table_translations'), function (Blueprint $table) {
            $table->string('relative_path');
            $table->string('relative_pathname');
            $table->string('file');
        });
    }
};