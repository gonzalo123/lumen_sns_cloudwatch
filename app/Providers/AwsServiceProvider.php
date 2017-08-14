<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Aws\Sns\SnsClient;

class AwsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $awsCredentials = [
            'region'      => getenv('AWS_REGION'),
            'version'     => getenv('AWS_VERSION'),
            'credentials' => [
                'key'    => getenv('AWS_CREDENTIALS_KEY'),
                'secret' => getenv('AWS_CREDENTIALS_SECRET'),
            ],
        ];

        $this->app->instance(SnsClient::class, new SnsClient($awsCredentials));
    }
}