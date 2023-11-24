<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if(!Schema::connection(config('languages.db_connection'))->hasTable(config('languages.table_translator_language'))) {
            Schema::connection(config('languages.db_connection'))->create(config('languages.table_translator_language'), function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('language_id');
                $table->unsignedBigInteger('translator_id');
                $table->foreign('language_id')->references('id')
                    ->on(config('languages.table_languages'))->cascadeOnDelete();
                $table->foreign('translator_id')->references('id')
                    ->on(config('languages.table_translators'))->cascadeOnDelete();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        if(Schema::connection(config('languages.db_connection'))->hasTable(config('languages.table_translator_language'))) {
            Schema::connection(config('languages.db_connection'))->dropIfExists(config('languages.table_translator_language'));
        }
    }
};
