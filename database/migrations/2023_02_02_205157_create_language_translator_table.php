<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class AddUpdatedValueToTranslations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table(config('languages.table_translations'), function (Blueprint $table) {
            $table->text('updated_value')->nullable()->after('value');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('updated_value')) {
            Schema::dropColumns(config('languages.table_translations'), 'updated_value');
        }
    }
}

