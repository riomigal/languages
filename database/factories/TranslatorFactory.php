<?php

use Faker\Generator;
use Illuminate\Support\Facades\Hash;
use Riomigal\Languages\Models\Translator;

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(Translator::class, function (Generator $faker) {
    return [
        'first_name' => $faker->firstName(),
        'last_name' => $faker->lastName(),
        'email' => $faker->email(),
        'phone' => $faker->phoneNumber(),
        'admin' => false,
        'email_verified_at' => null,
        'password' => Hash::make('aaaaaaaa')
    ];
});
