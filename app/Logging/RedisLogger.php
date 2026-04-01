<?php

namespace App\Logging;

use Illuminate\Support\Facades\Redis;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Monolog\Logger;

class RedisHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        try {
            Redis::lpush('logs', json_encode($record->toArray(), JSON_THROW_ON_ERROR));
        } catch (\Throwable) {
            // Не ломаем приложение и цепочку логирования при недоступном Redis / phpredis.
        }
    }
}

class RedisLogger
{
    public function __invoke(array $config)
    {
        $logger = new Logger('redis');
        $logger->pushHandler(new RedisHandler($config['level'] ?? Logger::DEBUG));
        return $logger;
    }
}