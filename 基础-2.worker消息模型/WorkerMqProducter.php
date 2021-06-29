<?php

require_once '../vendor/autoload.php';
include_once 'RabbitMQ.php';

use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use RabbitMQ\RabbitMQ;



/**
 *  work消息模型 生产者
 *  特点： 1. 一个队列 和 多个消费者
 *        2. 没有设置交换机，使用了默认的交换机(direct)
 */

$rabbit  = new RabbitMQ();
$channel = $rabbit->getChannel();

$queueName = 'test-worker-queue';
$rabbit->createQueue($queueName, false, true, false, false);

for ($i = 0; $i < 500; $i++) {
    $rabbit->sendMessage($i . "this is a test message . from " . $queueName, $queueName, '', [
        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT //消息持久化，重启rabbitmq，消息不会丢失
    ]);
}

unset($rabbit);//关闭连接

