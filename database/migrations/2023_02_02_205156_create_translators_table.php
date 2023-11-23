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
        if(!Schema::connection(config('languages.db_connection'))->hasTable(config('languages.table_translators'))) {
            Schema::connection(config('languages.db_connection'))->create(config('languages.table_translators'), function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('first_name');
                $table->string('last_name');
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->boolean('admin')->default(false);
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password')->nullable();
                $table->rememberToken();
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
        if(Schema::connection(config('languages.db_connection'))->hasTable(config('languages.table_translators'))) {
            Schema::connection(config('languages.db_connection'))->dropIfExists(config('languages.table_translators'));
        }
    }
};
