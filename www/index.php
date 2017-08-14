<?php

use Laravel\Lumen\Application;

require __DIR__ . '/../vendor/autoload.php';

(new Dotenv\Dotenv(__DIR__ . "/../env"))->load();

$app = new Application();

$app->register(App\Providers\LogServiceProvider::class);
$app->register(App\Providers\AwsServiceProvider::class);

$app->group(['namespace' => 'App\Http\Controllers'], function (Application $app) {
    $app->get("/push", "SnsController@push");
    $app->post("/read", "SnsController@read");
});

$app->run();
