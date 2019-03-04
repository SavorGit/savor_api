<?php
namespace Common\Lib;
use AliyunMNS\Client;
use AliyunMNS\Requests\SendMessageRequest;
use AliyunMNS\Requests\PublishMessageRequest;
use AliyunMNS\Exception\MnsException;

require_once APP_PATH.'Common/Lib/AliyunMSN/mns-autoloader.php';
class AliyunMsn{
    private $accessId;
    private $accessKey;
    private $endPoint;
    private $client=null;

    public function __construct($accessId,$accessKey,$endPoint){
        $this->accessId = $accessId;
        $this->accessKey = $accessKey;
        $this->endPoint = $endPoint;
        $this->client = new Client($this->endPoint, $this->accessId, $this->accessKey);
    }

    public function sendQueueMessage($queueName,$messageBody){
        $queue = $this->client->getQueueRef($queueName);
        $request = new SendMessageRequest($messageBody);
        try{
            $res = $queue->sendMessage($request);
            return $res;
        }catch (MnsException $e) {
            die("sendQueueMessage Failed: " . $e);
        }
    }

    public function sendTopicMessage($topicName,$messageBody,$messageTag){
        $topic = $this->client->getTopicRef($topicName);
        $request = new PublishMessageRequest($messageBody,$messageTag);
        try {
            $res = $topic->publishMessage($request);
            return $res;
        }catch (MnsException $e){
            die("sendTopicMessage Failed: " . $e);
        }
    }
}



?>
