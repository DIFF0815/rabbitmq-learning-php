<?php

require_once '../../vendor/autoload.php';
include_once '../RabbitMQ.php';

use PhpAmqpLib\Exchange\AMQPExchangeType;
use RabbitMQ\RabbitMQ;

/**
 * 消息的return机属性和自定义消息头
 **/


/**
 * Class Producer
 * 这里用路由模型做例子 ，其他模型类似
 */

class Consumer{

    private $rabbit;

    private $exchangeName;
    private $queueName;
    private $routingKey;

    public function __construct(){

        $this->rabbit = new RabbitMQ;

        $this->exchangeName = 'test_use_header_ex';
        $this->queueName = 'test_use_header_queue';
        $this->routingKey = 'test_use_header_key';

        //创建交换机
        $this->rabbit->createExchange($this->exchangeName, AMQPExchangeType::DIRECT, false, true, false);
        $this->rabbit->createQueue($this->queueName, false, true);
        $this->rabbit->bindQueue($this->queueName, $this->exchangeName, $this->routingKey);

    }


    /**
     * 模拟数据处理函数
     * @param $message
     */
    public function dealData($message){
        echo '----------------------';
        echo $message->body.PHP_EOL;
        echo "deliver_tag    ". $message->delivery_info['delivery_tag'].PHP_EOL;
        echo "routing_key    ". $message->delivery_info['routing_key'].PHP_EOL;
        echo "exchange   ". $message->delivery_info['exchange'].PHP_EOL;
        echo "consumer_tag    ". $message->delivery_info['consumer_tag'].PHP_EOL;

        // 获取自定义的消息头
        $header = $message->get('application_headers')->getNativeData();
        var_dump($header);
        //所有设置的属性
        $messageAllPro = $message->get_properties();
        var_dump($messageAllPro);
        //所有设置的属性序列化
        $messageSerPro = $message->serialize_properties();
        var_dump($messageSerPro);


        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);

    }

    /**
     * 启动
     * @throws ErrorException
     */
    public function run(){

        //接收消息 并设置处理函数处理
        $this->rabbit->consumeMessage($this->queueName,array($this,'dealData'));

        //等待
        while ($this->rabbit->getChannel()->is_consuming()){
            $this->rabbit->getChannel()->wait();
        }

    }
}


//测试
$consumer = new Consumer();
$consumer->run();



