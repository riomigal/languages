<?php

use Faker\Generator;
use Riomigal\Languages\Models\Language;

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(Language::class, function (Generator $faker) {
    $languages = collect(Language::LANGUAGES);
    $codes = array_diff($languages->pluck('code')->all(), Language::query()->get()->pluck('code')->all());
    $code = $faker->unique()->randomElement($codes);
    $language = $languages->where('code', $code)->first();

    return [
        'name' => $language['name'],
        'native_name' => $language['native_name'],
        'code' => $language['code'],
    ];
});

