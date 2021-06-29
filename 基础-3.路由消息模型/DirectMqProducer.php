<?php


require_once '../../vendor/autoload.php';
include_once '../RabbitMQ.php';

use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use RabbitMQ\RabbitMQ;

/**
 *  路由消息模型 生产者
 *  1.需要声明交换机，声明队列,把队列绑定在交换机（路由可以填可以不填(正常填一下)，不填用默认的路由''）
 */

/**
 *  路由消息模型 消费者
 *  1.需要声明交换机，声明队列,把队列绑定在交换机（路由可以填可以不填，不填用默认的路由''）
 */

/**
 *  举例：
 *
 */


$rabbit = new RabbitMQ();

$routingKey1  = 'test-direct-key1';
$routingKey2  = 'test-direct-key2';

$queueName1 = 'test-direct-queue1';
$queueName2 = 'test-direct-queue2';

$exchangeName = 'test-direct-ex';

//创建交换机
$rabbit->createExchange($exchangeName, AMQPExchangeType::DIRECT, false, true, false);

//创建队列
$rabbit->createQueue($queueName1, false, true);
$rabbit->createQueue($queueName2, false, true);
//绑定到交换机
$rabbit->bindQueue($queueName1, $exchangeName, $routingKey1);
$rabbit->bindQueue($queueName2, $exchangeName, $routingKey2);

//向交换机和routingkey1中推送100条数据
for ($i = 0; $i < 100; $i++) {
    $rabbit->sendMessage($i . "this is a queue1 message.", $routingKey1, $exchangeName, [
        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT //消息持久化，重启rabbitmq，消息不会丢失
    ]);
}
//向交换机和routingkey2中推送100条数据
for ($i = 0; $i < 100; $i++) {
    $rabbit->sendMessage($i . "this is a queue2 message.", $routingKey2, $exchangeName, [
        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT //消息持久化，重启rabbitmq，消息不会丢失
    ]);
}

unset($rabbit);//关闭连接

