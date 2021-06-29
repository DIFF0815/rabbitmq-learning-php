<?php

require_once '../../vendor/autoload.php';
include_once '../RabbitMQ.php';

use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use \PhpAmqpLib\Wire\AMQPTable;
use RabbitMQ\RabbitMQ;


/**
 * 延迟队列存储的对象肯定是对应的延时消息，所谓”延时消息”是指当消息被发送以后，并不想让消费者立即拿到消息，
 * 而是等待指定时间后，消费者才拿到这个消息进行消费。
 *
 * 使用场景：当我们的系统数据库比较小的时候，我们可以直接数据库定时轮询，查询时间有没有 超出半个小时这样子，
 * 但是一旦数据库比较大，牵扯到分库分表这样的话，定时轮询就是很耗费系统资源的，这时候就是延迟队列的用武之地了。
 *
 * 场景一：在订单系统中，一个用户下单之后通常有30分钟的时间进行支付，如果30分钟之内没有支付成功，那么这个订单将进行一场处理。
 *       这是就可以使用延时队列将订单信息发送到延时队列。
 * 场景二：用户希望通过手机远程遥控家里的智能设备在指定的时间进行工作。这时候就可以将用户指令发送到延时队列，
 *        当指令设定的时间到了再将指令推送到只能设备。
 *
 * 实现方式：
 *  方式一：使用消息的TTL结合DLX(死信路由)
 *  方式二：使用rabbitmq-delayed-message-exchange插件
 **/


/**
 * Class Producer
 * 这里用路由模型做例子 ，其他模型类似
 * 方式二：使用rabbitmq-delayed-message-exchange插件
 */
class ProducerDelayed
{

    private $rabbit;

    private $exchangeName;
    private $queueName;
    private $routingKey;

    public function __construct()
    {

        $this->rabbit = new RabbitMQ;

        $this->exchangeName = 'test_use_delayed_ex';
        $this->queueName = 'test_use_delayed_queue';
        $this->routingKey = 'test_use_delayed_key';

        //
        //创建交换机

        //创建交换机
        $this->rabbit->getChannel()->exchange_declare($this->exchangeName,'x-delayed-message',false,true,false,false,false,
            new AMQPTable(["x-delayed-type" => "direct"])
        );
        $this->rabbit->getChannel()->queue_declare($this->queueName,false,true,false,false,false,
            new AMQPTable(["x-dead-letter-exchange" => "delayed"])
        );

        $this->rabbit->bindQueue($this->queueName, $this->exchangeName, $this->routingKey);



    }

    /**
     * 启动
     */
    public function run(){

        for($i=1;$i<=10;$i++){

            echo $i.PHP_EOL;

            $msg = "this a test message " . $i ;

            //为消息添加消息头
            $headers = new AMQPTable();
            $headers->set('x-delay',20000); //30秒

            //第二种 创建消息时直接指定
            $message = new AMQPMessage($msg,[
                //持久化
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                //自定义消息头
                'application_headers' => $headers
            ]);

            //
            $this->rabbit->getChannel()->basic_publish($message,$this->exchangeName,$this->routingKey);

        }

    }

}

$producer = new ProducerDelayed();
$producer->run();




