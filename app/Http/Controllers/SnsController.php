<?php

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
