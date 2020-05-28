<?php

declare(strict_types=1);

namespace Smoench;

use Doctrine\Persistence\ManagerRegistry;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Event\WorkerStoppedEvent;

class IdleCloseConnectionSubscriber implements EventSubscriberInterface
{
    /** @var ManagerRegistry */
    private $managerRegistry;

    /** @var LoopInterface */
    private $loop;

    /** @var int */
    private $interval;

    /** @var TimerInterface|null */
    private $currentTimer;

    /**
     * @param float $interval The number of seconds to wait in idle before closing the connections
     */
    public function __construct(ManagerRegistry $managerRegistry, float $interval, LoopInterface $loop = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->loop = $loop ?? Factory::create();
        $this->interval = $interval;
    }

    public static function getSubscribedEvents(): iterable
    {
        yield WorkerMessageReceivedEvent::class => 'onWorkerMessageReceived';
        yield WorkerRunningEvent::class => 'onWorkerRunning';
        yield WorkerStoppedEvent::class => 'onWorkerStopped';
    }

    public function onWorkerMessageReceived(): void
    {
        $this->stopTimer();
    }

    public function onWorkerRunning(WorkerRunningEvent $event): void
    {
        if ($event->isWorkerIdle()) {
            $this->startTimer();
        }
    }

    public function onWorkerStopped(): void
    {
        $this->stopTimer();
    }

    private function startTimer(): void
    {
        $this->stopTimer();

        $this->loop->addTimer(
            $this->interval,
            function () {
                foreach ($this->managerRegistry->getConnections() as $connection) {
                    if (method_exists($connection, 'close')) {
                        $connection->close();
                    }
                }
            }
        );
    }

    private function stopTimer(): void
    {
        if (null === $this->currentTimer) {
            return;
        }

        $this->loop->cancelTimer($this->currentTimer);
    }
}
