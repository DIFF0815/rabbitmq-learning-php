<?php

require_once '../../vendor/autoload.php';
include_once '../RabbitMQ.php';

use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use \PhpAmqpLib\Wire\AMQPTable;
use RabbitMQ\RabbitMQ;

/**
 * 消费端ack和重回队列
 **/


/**
 * Class Producer
 * 这里用路由模型做例子 ，其他模型类似
 */

class ProducerRequeue{

    private $rabbit;

    private $exchangeName;
    private $queueName;
    private $routingKey;

    public function __construct(){

        $this->rabbit = new RabbitMQ;

        $this->exchangeName = 'test_use_ack_requeue_ex';
        $this->queueName = 'test_use_ack_requeue_queue';
        $this->routingKey = 'test_use_ack_requeue_key';

        //创建交换机
        $this->rabbit->createExchange($this->exchangeName, AMQPExchangeType::DIRECT, false, true, false);
        $this->rabbit->createQueue($this->queueName, false, true,false,false,false,
            new AMQPTable([
                "x-message-ttl" => 10000, //过期时间 单位毫秒, 10秒钟
                "x-dead-letter-exchange" => "test_use_ack_ex", // 过期后 发送到的死信路由
                "x-dead-letter-routing-key" => 'test_use_ack_key', // 过期后 发送到的死信队列 routing_key
                //       "x-max-length-bytes" // 最大字节数
                //        "x-max-length" => 10, //容量个数
                //        "x-expires" => 16000,// 自动过期时间
                //        "x-max-priority"  // 权重
            ])
        );
        $this->rabbit->bindQueue($this->queueName, $this->exchangeName, $this->routingKey);

    }


    /**
     * 启动
     * @throws ErrorException
     */
    public function run(){
        //该消费者只做声明创建用 没有逻辑

    }
}


//测试
$consumer = new ProducerRequeue();
$consumer->run();



