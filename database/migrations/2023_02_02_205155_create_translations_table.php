<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if(!Schema::connection(config('languages.db_connection'))->hasTable(config('languages.table_translations'))) {
            Schema::connection(config('languages.db_connection'))->create(config('languages.table_translations'), function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('language_id');
                $table->foreign('language_id')->references('id')
                    ->on(config('languages.table_languages'))->cascadeOnDelete();
                $table->string('language_code');
                $table->string('relative_path');
                $table->string('relative_pathname');
                $table->text('shared_identifier');
                $table->string('file');
                $table->enum('type', ['json', 'php', 'model']);
                $table->text('key');
                $table->text('value')->nullable();
                $table->boolean('approved')->default(true);
                $table->boolean('needs_translation');
                $table->boolean('updated_translation')->default(false);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if(Schema::connection(config('languages.db_connection'))->hasTable(config('languages.table_translations'))) {
            Schema::connection(config('languages.db_connection'))->dropIfExists(config('languages.table_translations'));
        }
    }
};
