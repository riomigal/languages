<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('languages.db_connection');
        $tableName = config('languages.table_settings');
        $schema = Schema::connection($connection);

        if (!$schema->hasTable($tableName) || $schema->hasColumn($tableName, 'enable_multi_host')) {
            return;
        }

        $schema->table($tableName, function (Blueprint $table) {
            $table->boolean('enable_multi_host')->default(false);
        });

        // Preserve coordination for installations that already configured shared hosts.
        DB::connection($connection)->table($tableName)
            ->whereNotNull('domains')
            ->whereRaw("TRIM(domains) <> ''")
            ->update(['enable_multi_host' => true]);

        Cache::forget(config('languages.cache_key') . '_settings');
    }

    public function down(): void
    {
        $tableName = config('languages.table_settings');
        $schema = Schema::connection(config('languages.db_connection'));

        if (!$schema->hasTable($tableName) || !$schema->hasColumn($tableName, 'enable_multi_host')) {
            return;
        }

        $schema->table($tableName, function (Blueprint $table) {
            $table->dropColumn('enable_multi_host');
        });

        Cache::forget(config('languages.cache_key') . '_settings');
    }
};
