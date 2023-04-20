<?php

use Illuminate\Database\Migrations\Migration;
use Riomigal\Languages\Models\Language;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $language = collect(Language::LANGUAGES)->where('code', config('app.fallback_locale'))->first();
        Language::query()->create(
            [
                'code' => $language['code'],
                'name' => $language['name'],
                'native_name' => $language['native_name'],
            ]
        );

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Language::query()->delete();
    }
};
