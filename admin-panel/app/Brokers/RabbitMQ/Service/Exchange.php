<?php

namespace App\Brokers\RabbitMQ\Service;

use App\Brokers\RabbitMQ\Connector;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Exchange
{
    private AMQPStreamConnection $connection;

    private AMQPChannel $channel;

    private string $exchange;

    private string $type;

    private string $data;

    private string $routingKey;

    public function __construct()
    {
        $this->connection = (new Connector)->openChannel();
        $this->channel = $this->connection->channel();
    }

    public function withDirectType()
    {
        $this->type = 'direct';

        return $this;
    }

    public function withTopicType()
    {
        $this->type = 'topic';

        return $this;
    }

    public function setData($data): Exchange
    {
        $this->data = is_array($data)
            ? json_encode($data)
            : $data;

        return $this;
    }

    public function setRoutingKey($key): Exchange
    {
        $this->routingKey = $key;

        return $this;
    }

    public function exchange($exchange): Exchange
    {
        $this->exchange = $exchange;

        return $this;
    }

    public function dispatch()
    {
        $this->channel->exchange_declare($this->exchange, $this->type, false, true, false);
        $msg = new AMQPMessage($this->data, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
        $this->channel->basic_publish($msg, $this->exchange, $this->routingKey);

    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
