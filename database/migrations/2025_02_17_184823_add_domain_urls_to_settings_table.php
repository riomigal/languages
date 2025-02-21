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
        if(!Schema::connection(config('languages.db_connection'))->hasColumns(config('languages.table_settings'), ['domains', 'import_only_from_root_language', 'allow_deleting_languages'])) {
            Schema::connection(config('languages.db_connection'))->table(config('languages.table_settings'), function (Blueprint $table) {
                $table->string('domains')->nullable()->after('import_vendor');
                $table->boolean('import_only_from_root_language')->default(false)->after('import_vendor');
                $table->boolean('allow_deleting_languages')->default(false)->after('import_vendor');
            });
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if(Schema::connection(config('languages.db_connection'))->hasColumns(config('languages.table_settings'), ['domains', 'import_only_from_root_language', 'allow_deleting_languages'])) {
            Schema::connection(config('languages.db_connection'))->table(config('languages.table_settings'), function (Blueprint $table) {
                Schema::connection(config('languages.db_connection'))->dropColumns(config('languages.table_settings'), ['domains', 'import_only_from_root_language', 'allow_deleting_languages']);
            });
        }
    }
};
