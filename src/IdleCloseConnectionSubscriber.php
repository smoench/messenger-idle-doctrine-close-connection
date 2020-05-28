<?php

declare(strict_types=1);

namespace Smoench;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;

class IdleCloseConnectionSubscriber implements EventSubscriberInterface
{
    /** @var ManagerRegistry */
    private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    public static function getSubscribedEvents(): iterable
    {
        yield WorkerRunningEvent::class => 'onWorkerRunning';
    }

    public function onWorkerRunning(WorkerRunningEvent $event): void
    {
        if (!$event->isWorkerIdle()) {
            return;
        }

        foreach ($this->managerRegistry->getConnections() as $connection) {
            $connection->close();
        }
    }
}
