<?php

require_once '../../vendor/autoload.php';
include_once '../RabbitMQ.php';

use RabbitMQ\RabbitMQ;


/**
 * 简单基本消息模型  消费者
 */

$rabbit = new RabbitMQ();

$queueName = 'test-simple-queue';
$callback = function ($message){
    var_dump("Received Message : " . $message->body);//print message
    sleep(1);//处理耗时任务
    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);//ack
};
$rabbit->consumeMessage($queueName,$callback);

unset($rabbit);//关闭连接


