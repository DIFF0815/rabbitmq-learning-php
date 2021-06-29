<?php

require_once '../../vendor/autoload.php';
include_once '../RabbitMQ.php';

use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use RabbitMQ\RabbitMQ;


$rabbit = new RabbitMQ();

$queueName    = 'test-fanout-queue2-email';

$exchangeName = 'test-fanout-ex-register';

//创建队列
$rabbit->createQueue($queueName, false, true);
//创建交换机
$rabbit->createExchange($exchangeName, AMQPExchangeType::FANOUT, false, true, false);
//绑定到交换机
$rabbit->bindQueue($queueName, $exchangeName, '');

//消费
$callback = function ($message) {
    var_dump("email Received Message : " . $message->body);//print message
    sleep(1);//处理耗时任务
    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);//ack
};
$rabbit->consumeMessage($queueName, $callback);

unset($rabbit);//关闭连接
