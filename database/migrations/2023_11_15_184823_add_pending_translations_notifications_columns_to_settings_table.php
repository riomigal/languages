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
        Schema::connection(config('languages.db_connection'))->table(config('languages.table_settings'), function (Blueprint $table) {
            $table->boolean('enable_pending_notifications')->default(false)->after('import_vendor');
            $table->boolean('enable_automatic_pending_notifications')->default(false)->after('enable_pending_notifications');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection(config('languages.db_connection'))->table(config('languages.table_settings'), function (Blueprint $table) {
                Schema::connection(config('languages.db_connection'))->dropColumns(config('languages.table_settings'), ['enable_pending_notifications','enable_automatic_pending_notifications']);
        });
    }
};
