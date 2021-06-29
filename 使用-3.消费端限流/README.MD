## 消费端限流

* 只有consumer已经处理并确认了上一条message时queue才分派新的message给它,消费前加上下面这个句
* $this->channel->basic_qos(null, 1, null);
