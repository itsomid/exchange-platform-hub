<?php

namespace App\Mail;

use App\Mail\Transport\FailureLoggingTransport;
use App\Services\Mail\MailFailureLogger;
use Illuminate\Mail\MailManager as BaseMailManager;

class LoggingMailManager extends BaseMailManager
{
    public function createSymfonyTransport(array $config)
    {
        return new FailureLoggingTransport(
            parent::createSymfonyTransport($config),
            $this->app->make(MailFailureLogger::class),
        );
    }
}
