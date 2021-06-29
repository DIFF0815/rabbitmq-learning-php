<?php

require_once '../../vendor/autoload.php';
include_once '../RabbitMQ.php';

use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use \PhpAmqpLib\Wire\AMQPTable;
use RabbitMQ\RabbitMQ;


/**
 * 延时队列 利用delayed 插件 消费者
 **/


/**
 * Class Consumer
 * 这里用路由模型做例子 ，其他模型类似
 */

class ConsumerDelayed
{

    private $rabbit;

    private $exchangeName;
    private $queueName;
    private $routingKey;

    public function __construct()
    {

        $this->rabbit = new RabbitMQ;

        $this->exchangeName = 'test_use_delayed_ex';
        $this->queueName = 'test_use_delayed_queue';
        $this->routingKey = 'test_use_delayed_key';

        //创建交换机
        $this->rabbit->getChannel()->exchange_declare($this->exchangeName,'x-delayed-message',false,true,false,false,false,
            new AMQPTable(["x-delayed-type" => "direct"])
        );
        $this->rabbit->getChannel()->queue_declare($this->queueName,false,true,false,false,false,
            new AMQPTable(["x-dead-letter-exchange" => "delayed"])
        );

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
$consumer = new ConsumerDelayed();
$consumer->run();
