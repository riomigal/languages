<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Riomigal\Languages\Languages;
use Riomigal\Languages\Models\Language;

class CreateLanguagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create(config('languages.table_languages'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('native_name');
            $table->string('code')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists(config('languages.table_languages'));
    }
}
