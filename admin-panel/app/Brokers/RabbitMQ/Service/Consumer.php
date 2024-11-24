<?php

namespace App\Brokers\RabbitMQ\Service;

use App\Brokers\RabbitMQ\Connector;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Throwable;

class Consumer
{
    private AMQPStreamConnection $connection;

    private AMQPChannel $channel;

    private string $queue;

    public function __construct()
    {
        $this->connection = (new Connector)->openChannel();
        $this->channel = $this->connection->channel();
    }

    public function queue($queue): Consumer
    {
        $this->queue = $queue;

        return $this;
    }

    public function listen(): void
    {
        $callback = function ($msg) {
            $this->legacy_resolver($msg->getBody());
        };
        $this->channel->basic_consume($this->queue, '', false, true, false, false, $callback);
        while ($this->channel->is_open()) {
            $this->channel->wait();
        }

    }

    private function legacy_resolver($data)
    {
        $data = json_decode($data);
        if (property_exists($data, 'event')
            && property_exists($data, 'action')
            && property_exists($data, 'data')
        ) {
            $resolver = 'App\Legacy\Listeners\\'.$data->event;
            if (class_exists($resolver)) {
                try {
                    $resolverObject = new $resolver;
                    $resolverObject->handle($data->data);
                } catch (Throwable $e) {
                    Log::channel('legacy')->error($e);
                }

            }
        }
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
