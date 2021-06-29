<?php

require_once '../../vendor/autoload.php';
include_once '../RabbitMQ.php';

use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use RabbitMQ\RabbitMQ;


class Consumer{

    private $rabbit;

    private $exchangeName;
    private $queueName;
    private $routingKey;

    public function __construct(){

        $this->rabbit = new RabbitMQ;

        $this->exchangeName = 'test_use_reliable_ex';
        $this->queueName = 'test_use_reliable_queue';
        $this->routingKey = 'test_use_reliable_key';

        //特别说明：一般情况下 创建交换机和创建队列都可以在消费者端创建，哪边创建都可以，两边都创建也可以，因为创建时会判断有该交换机或队列就不创建了，但是两边都创建保险点，因为你不知道是消费者小启动还是生产者先启动
        //但是：两边创建的各个参数要一致 要不然会有问题

        //创建交换机
        $this->rabbit->createExchange($this->exchangeName, AMQPExchangeType::DIRECT, false, true, false);

        //创建队列
        $this->rabbit->createQueue($this->queueName, false, true);

        //绑定到交换机
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
$consumer = new Consumer();
$consumer->run();

