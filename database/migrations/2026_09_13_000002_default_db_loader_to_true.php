<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Change only the default for future rows; preserve every saved loader mode.
     */
    public function up(): void
    {
        $this->setDefault(true);
    }

    /**
     * Restore the previous default without changing existing settings.
     */
    public function down(): void
    {
        $this->setDefault(false);
    }

    private function setDefault(bool $enabled): void
    {
        $tableName = config('languages.table_settings');
        $schema = Schema::connection(config('languages.db_connection'));

        if (!$schema->hasTable($tableName) || !$schema->hasColumn($tableName, 'db_loader')) {
            return;
        }

        $schema->table($tableName, function (Blueprint $table) use ($enabled) {
            $table->boolean('db_loader')->default($enabled)->change();
        });

        // Console registration may cache file mode before a fresh install's table exists.
        Cache::forget(config('languages.cache_key') . '_has_db_loader_on');
    }
};
