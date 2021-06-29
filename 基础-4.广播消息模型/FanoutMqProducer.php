<?php

require_once '../../vendor/autoload.php';
include_once '../RabbitMQ.php';

use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use RabbitMQ\RabbitMQ;

/**
 *  广播消息模型 生产者
 *  1.声明Exchange，不再声明Queue
 *  2.发送消息到Exchange，不再发送到Queue
 *
 *  广播消息模型 消费者
 *  1.声明队列 绑定到交换机上(路由可填可不填)
 *  2.每个绑定了该交换机的消费者队列都会受到消息
 *
 *
 *  举例：
 *   一个注册服务: 在用户注册完成后，发送短信和邮件通知
 *   1.启动一个发送短信的消费者(FanoutMqConsumer1.php)声明队列（test-fanout-queue1_sms）并绑定在交换机（test-fanout-ex-register）上
 *   1.启动一个发送短信的消费者(FanoutMqConsumer2.php)声明队列（test-fanout-queue2_email）并绑定在交换机（test-fanout-ex-register）上
 *   3.用户注册完成后（生产者FanoutMqProducer.php）发送消息到交换机（test-fanout-ex-register）
 */

$rabbit = new RabbitMQ();


$exchangeName = 'test-fanout-ex-register';

//创建交换机
$rabbit->createExchange($exchangeName, AMQPExchangeType::FANOUT, false, true, false);

//向交换机中推送100条数据
for ($i = 0; $i < 100; $i++) {
    $rabbit->sendMessage($i . " this is a fanout register successful message.", '', $exchangeName, [
        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT //消息持久化，重启rabbitmq，消息不会丢失
    ]);
}

unset($rabbit);//关闭连接



