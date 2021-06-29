<?php

require_once '../../vendor/autoload.php';
include_once '../RabbitMQ.php';

use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use RabbitMQ\RabbitMQ;


/**
 * confirm消息确认机制
 *
 * 消息的确认是指生产者投递消息后，如果Broker接收到消息，则会给生产者一个应答。
 * 生产者进行接收应答，用来确认这条消息是否正常的发送到Broker，
 * 这种方式也是消息可靠性投递的核心保障
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


        $this->exchangeName = 'test_use_reliable_ex';
        $this->queueName = 'test_use_reliable_queue';
        $this->routingKey = 'test_use_reliable_key';

        //特别说明：一般情况下 创建交换机和创建队列都可以在消费者端创建，哪边创建都可以，两边都创建也可以，因为创建时会判断有该交换机或队列就不创建了，但是两边都创建保险点，因为你不知道是消费者小启动还是生产者先启动

        //创建交换机
        $this->rabbit->createExchange($this->exchangeName, AMQPExchangeType::DIRECT, false, true, false);

        //创建队列
        $this->rabbit->createQueue($this->queueName, false, true);

        //绑定到交换机
        $this->rabbit->bindQueue($this->queueName, $this->exchangeName, $this->routingKey);

    }

    /**
     * 成功投递函数
     * @param AMQPMessage $message
     */
    public function ackCallback(AMQPMessage $message){
        echo " ---ack--- success" . PHP_EOL;
    }

    /**
     * 失败投递函数
     * @param AMQPMessage $message
     */
    public function nackCallback(AMQPMessage $message){
        echo " ---no ack--- fail" . PHP_EOL;
    }

    /**
     * 启动
     */
    public function run(){

        //开启确认模式
        //$this->rabbit->getChannel()->set_ack_handler(array($this,'ackCallback'));
        //$this->rabbit->getChannel()->set_nack_handler(array($this,'nackCallback'));
        //$this->rabbit->getChannel()->confirm_select();

        $this->rabbit->setAckHandle(array($this,'ackCallback'));
        $this->rabbit->setNackHandle(array($this,'ackCallback'));
        $this->rabbit->confirmSelect();

        //发送消息
        $msg = 'a message from reliable producer';
        $this->rabbit->sendMessage($msg,$this->routingKey,$this->exchangeName);

        //等待消息确认回调
        $this->rabbit->getChannel()->wait_for_pending_acks_returns();

    }

}


//测试
$producer = new Producer();
$producer->run();
