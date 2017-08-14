Handling Amazon SNS messages with PHP and Lumen and CloudWatch
======

This days I'm involve with Amazon and since I am migrating my backends to Lumen I'm going to play a little bit with AWS and Lumen. Today I want to create a simple Lumen server to handle SNS notifications. One end point to listen to SNS and another one emit notifications. I also want to register logs within CloudWatch. Let's start.


First the Lumen server. 

```php
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
```

As we can see there's a route to push notifications and another one to read messages.

To work with SNS I will create a simple service provider

```php
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
```

Now We can create the routes in SnsController. Sns has a confirmation mechanism to validate endpoints. It's well explained here: http://docs.aws.amazon.com/sns/latest/dg/SendMessageToHttp.html#SendMessageToHttp.retry

```php
namespace App\Http\Controllers;

use Aws\Sns\SnsClient;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;
use Monolog\Logger;

class SnsController extends Controller
{
    private $request;
    private $logger;

    public function __construct(Request $request, Logger $logger)
    {
        $this->request = $request;
        $this->logger  = $logger;
    }

    public function hi()
    {
        return ['hi'];
    }

    public function push(SnsClient $snsClient)
    {
        $snsClient->publish([
            'TopicArn' => getenv('AWS_SNS_TOPIC1'),
            'Message'  => 'hi',
            'Subject'  => 'Subject',
        ]);

        return ['push'];
    }

    public function read(SnsClient $snsClient)
    {
        $data = $this->request->json()->all();

        if ($this->request->headers->get('X-Amz-Sns-Message-Type') == 'SubscriptionConfirmation') {
            $this->logger->notice("sns:confirmSubscription");
            $snsClient->confirmSubscription([
                'TopicArn' => getenv('AWS_SNS_TOPIC1'),
                'Token'    => $data['Token'],
            ]);
        } else {
            $this->logger->warn("read", [
                'Subject'   => $data['Subject'],
                'Message'   => $data['Message'],
                'Timestamp' => $data['Timestamp'],
            ]);
        }

        return "OK";
    }
}
```

Finally I want to use CloudWatch so I will configure Monolog with another service provider. It's also well explained here: https://aws.amazon.com/es/blogs/developer/php-application-logging-with-amazon-cloudwatch-logs-and-monolog/

```php
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
```

And that's up. Lumen and SNS up and running. 