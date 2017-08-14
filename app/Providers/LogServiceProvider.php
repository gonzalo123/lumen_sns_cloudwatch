<?php

namespace App\Providers;

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Illuminate\Support\ServiceProvider;
use Maxbanton\Cwh\Handler\CloudWatch;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;

class LogServiceProvider extends ServiceProvider
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

        $cwClient = new CloudWatchLogsClient($awsCredentials);

        $cwRetentionDays      = getenv('CW_RETENTIONDAYS');
        $cwGroupName          = getenv('CW_GROUPNAME');
        $cwStreamNameInstance = getenv('CW_STREAMNAMEINSTANCE');
        $loggerName           = getenv('CF_LOGGERNAME');

        $logger  = new Logger($loggerName);
        $handler = new CloudWatch($cwClient, $cwGroupName, $cwStreamNameInstance, $cwRetentionDays);
        $handler->setFormatter(new LineFormatter(null, null, false, true));

        $logger->pushHandler($handler);

        $this->app->instance(Logger::class, $logger);
    }
}