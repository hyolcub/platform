<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use SuperV\Platform\Domains\Auth\Account;

$factory->define(SuperV\Platform\Domains\Auth\Account::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->title,
    ];
});

$factory->define(SuperV\Platform\Domains\Auth\User::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'account_id'     => function () {
            return factory(Account::class)->create()->id;
        },
        'name'           => $faker->name,
        'email'          => $faker->unique()->safeEmail,
        'password'       => $password ?: $password = '$2y$10$lEElUpT9ssdSw4XVVEUt5OaJnBzgcmcE6MJ2Rrov4dKPEjuRD6dd.',
        // secret,
        'remember_token' => str_random(10),
    ];
});

