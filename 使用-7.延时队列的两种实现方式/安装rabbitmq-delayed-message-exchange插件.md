## 安装rabbitmq-delayed-message-exchange插件
* 下载地址 
  [下载地址传送门](https://www.rabbitmq.com/community-plugins.html)
  
* tips
  
  我用rabbitmq 版本是3.8.16
  [rabbitmq_delayed_message_exchange-3.8.0.ez](https://github.com/rabbitmq/rabbitmq-delayed-message-exchange/releases/download/v3.8.0/rabbitmq_delayed_message_exchange-3.8.0.ez)
  
* docker容器里面安装
  1. 主机docker ps 查看rabbitmq 的容器id
  2. 主机docker cp .\services\rabbitmq\plugins\rabbitmq_delayed_message_exchange-3.8.0.ez f8663d990824:/plugins 
  3. 激活 进入rabbitmq容器执行：rabbitmq-plugins enable rabbitmq_delayed_message_exchange
    显然如下：
     ```shell
      Enabling plugins on node rabbit@f8663d990824:
      rabbitmq_delayed_message_exchange
      The following plugins have been configured:
      rabbitmq_delayed_message_exchange
      rabbitmq_management
      rabbitmq_management_agent
      rabbitmq_prometheus
      rabbitmq_web_dispatch
      Applying plugin configuration to rabbit@f8663d990824...
      The following plugins have been enabled:
      rabbitmq_delayed_message_exchange
      
      started 1 plugins.
    
  4. 查看是否安装成功 rabbitmq-plugins list   或者 rabbitmq-plugins list rabbitmq_delayed_message_exchange 
    

    
