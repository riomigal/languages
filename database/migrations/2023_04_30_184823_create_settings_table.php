<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Riomigal\Languages\Models\Setting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if(!Schema::connection(config('languages.db_connection'))->hasTable(config('languages.table_settings'))) {
            Schema::connection(config('languages.db_connection'))->create(config('languages.table_settings'), function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->boolean('db_loader')->default(false);
                $table->boolean('import_vendor')->default(false);
                $table->timestamps();
            });

            /**
             * Creates first setting
             */
            if(!Setting::query()->first()) {
                Setting::query()->create(
                    ['db_loader' => false]
                );
            }
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if(Schema::connection(config('languages.db_connection'))->hasTable(config('languages.table_settings'))) {
            Schema::connection(config('languages.db_connection'))->dropIfExists(config('languages.table_settings'));
        }
    }
};
