<?php

require_once '../../vendor/autoload.php';
include_once '../RabbitMQ.php';

use RabbitMQ\RabbitMQ;




$rabbit = new RabbitMQ();

$queueName    = 'test-direct-queue1';
$routingKey   = 'test-direct-key1';//消费规则定义

$exchangeName = 'test-direct-ex';

//创建队列
$rabbit->createQueue($queueName, false, true);
//绑定到交换机
$rabbit->bindQueue($queueName, $exchangeName, $routingKey);

//消费
$callback = function ($message) {
    var_dump("Received Message : " . $message->body);//print message
    sleep(1);//处理耗时任务
    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);//ack
};
$rabbit->consumeMessage($queueName, $callback);

unset($rabbit);//关闭连接
