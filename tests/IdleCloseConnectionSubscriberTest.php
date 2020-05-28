<?php

declare(strict_types=1);

namespace Tests\Smoench;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Smoench\IdleCloseConnectionSubscriber;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Worker;

class IdleCloseConnectionSubscriberTest extends TestCase
{
    public function testIdleCloseConnection(): void
    {
        $worker = $this->createMock(Worker::class);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $connection = $this->createMock(Connection::class);

        $connection
            ->expects(self::once())
            ->method('close');

        $managerRegistry
            ->expects(self::once())
            ->method('getConnections')
            ->willReturn([$connection]);

        $subscriber = new IdleCloseConnectionSubscriber($managerRegistry);
        $subscriber->onWorkerRunning(new WorkerRunningEvent($worker, true));
    }
}

interface Connection {
    public function close();
}
