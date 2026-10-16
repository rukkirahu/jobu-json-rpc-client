<?php

declare(strict_types=1);

namespace Jobu\Service;

use Jobu\Dto\Request\QueueCreate;
use Jobu\Dto\Request\QueueList;
use Jobu\Dto\Request\QueueSelector;
use Jobu\Dto\Request\QueueStats;
use Jobu\Dto\Request\QueueUpdate;
use Jobu\Dto\Result\Queue;
use Jobu\Dto\Result\QueuePage;
use Jobu\Dto\Result\StatisticsPage;

final readonly class QueueService extends Service
{
    public function create(QueueCreate $request): Queue
    {
        return $this->result('queue.create', $request, Queue::class);
    }

    public function get(QueueSelector $selector): Queue
    {
        return $this->result('queue.get', $selector, Queue::class);
    }

    public function list(QueueList $query = new QueueList()): QueuePage
    {
        return $this->result('queue.list', $query, QueuePage::class);
    }

    public function update(QueueUpdate $request): Queue
    {
        return $this->result('queue.update', $request, Queue::class);
    }

    public function suspend(QueueSelector $selector): Queue
    {
        return $this->result('queue.suspend', $selector, Queue::class);
    }

    public function resume(QueueSelector $selector): Queue
    {
        return $this->result('queue.resume', $selector, Queue::class);
    }

    public function delete(QueueSelector $selector): void
    {
        $this->voidResult('queue.delete', $selector);
    }

    public function stats(QueueStats $query): StatisticsPage
    {
        return $this->result('queue.stats', $query, StatisticsPage::class);
    }
}
