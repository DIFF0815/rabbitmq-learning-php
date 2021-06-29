<?php

require_once '../../vendor/autoload.php';
include_once '../RabbitMQ.php';

use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use \PhpAmqpLib\Wire\AMQPTable;
use RabbitMQ\RabbitMQ;


/**
 * 消费端ack和重回队列
 * 消费端进行消费的时候，如果由于业务异常导致失败了，返回NACK达到最大重试次数，此时我们可以进行日志的记录，
 * 然后手动ACK回去，最后对这个记录进行补偿。
 * 或者由于服务器宕机等严重问题，导致ACK和NACK都没有，那我们就需要手工进行ACK保障消费端消费成功，再通过补偿机制补偿。
 **/


/**
 * Class Producer
 * 这里用路由模型做例子 ，其他模型类似
 */
class Producer{

    private $rabbit;

    private $exchangeName;
    private $queueName;
    private $routingKey;

    public function __construct(){

        $this->rabbit = new RabbitMQ;

        $this->exchangeName = 'test_use_ack_ex';
        $this->queueName = 'test_use_ack_queue';
        $this->routingKey = 'test_use_ack_key';

        //创建交换机
        $this->rabbit->createExchange($this->exchangeName, AMQPExchangeType::DIRECT, false, true, false);
        $this->rabbit->createQueue($this->queueName, false, true);
        $this->rabbit->bindQueue($this->queueName, $this->exchangeName, $this->routingKey);

    }


    /**
     * 启动
     */
    public function run(){

        for($i=1;$i<=4;$i++){

            echo $i.PHP_EOL;

            // 创建头消息,各种数据格式
            $headers = new AMQPTable();
            //为消息头添加数据
            $headers->set('num',$i);
            //设置一个重试次数
            $headers->set('retry_nums',0);

            //为消息添加消息头
            $msg = "this a ack test message ";

            //第二种 创建消息时直接指定
            $message = new AMQPMessage($msg,[
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'application_headers' => $headers
            ]);

            //
            $this->rabbit->getChannel()->basic_publish($message,$this->exchangeName,$this->routingKey);

        }


    }

}


//测试
$producer = new Producer();
$producer->run();
