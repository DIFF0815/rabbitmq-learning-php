<?php

require_once '../../vendor/autoload.php';
include_once '../RabbitMQ.php';

use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use RabbitMQ\RabbitMQ;

/**
 *  通配符消息模型 生产者
 *

 *
 *  每个消费者监听自己的队列，并且设置带统配符的routingkey,生产者将消息发给broker，由交换机根据routingkey来转发消息到指定的队列。
    Routingkey一般都是有一个或者多个单词组成，多个单词之间以“.”分割，例如：inform.sms
    通配符规则：
    #：匹配一个或多个词
    *：匹配不多不少恰好1个词
 *
 *
 *  广播消息模型 消费者
 *
 *
 *  举例：
 *      路由key有如下：
 *      test.quick.orange.rabbit        //消费者1能收到
 *      test.lazy.orange.elephant       //消费者1能收到
 *      test.quick.orange.fox           //消费者1能收到  消费者2能收到
 *      test.lazy.pink.rabbit           //消费者2能收到
 *      test.quick.brown.fox            //没有匹配 会丢弃
 *
 *      消费者1的路由key: 绑定了 “*.orange.*”，
 *      消费者2的路由key: 绑定了 “.*.*.rabbit” 和 “test.lazy.＃”。
 */

$rabbit = new RabbitMQ();


$exchangeName = 'test-topics-ex';

//创建交换机
$rabbit->createExchange($exchangeName, AMQPExchangeType::TOPIC, false, true, false);

$dataArr = [
    'test.quick.orange.rabbit',
    'test.lazy.orange.elephant',
    'test.quick.orange.fox',
    'test.lazy.pink.rabbit',
    'test.quick.brown.fox',
];

foreach ($dataArr as $val){

    //直接向交换机的路由key 发送消息
    $rabbit->sendMessage($val . " this is a topics successful message.", $val, $exchangeName, [
        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT //消息持久化，重启rabbitmq，消息不会丢失
    ]);

}


unset($rabbit);//关闭连接



