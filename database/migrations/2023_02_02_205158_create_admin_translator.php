<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Translator;

class CreateAdminTranslator extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $translator = Translator::create(
            [
                'email' => 'admin@admin.com',
                'password' => Hash::make('aaaaaaaa'),
                'admin' => true,
                'first_name' => 'admin',
                'last_name' => 'admin'
            ]
        );
        $translator->languages()->attach(Language::first()->id);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $translator = Translator::first();
        $translator->languages()->detach();
        $translator->delete();
    }
}
