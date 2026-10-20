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
use Jobu\Exception\UnsupportedFeatureException;

final readonly class QueueService extends Service
{
    public function create(QueueCreate $request): Queue
    {
        $this->requireQueueRevisions();
        return $this->result('queue.create', $request, Queue::class);
    }

    public function get(QueueSelector $selector): Queue
    {
        $this->requireQueueRevisions();
        return $this->result('queue.get', $selector, Queue::class);
    }

    public function list(QueueList $query = new QueueList()): QueuePage
    {
        $this->requireQueueRevisions();
        return $this->result('queue.list', $query, QueuePage::class);
    }

    public function update(QueueUpdate $request): Queue
    {
        $this->requireQueueRevisions();
        return $this->result('queue.update', $request, Queue::class);
    }

    public function suspend(QueueSelector $selector): Queue
    {
        $this->requireQueueRevisions();
        return $this->result('queue.suspend', $selector, Queue::class);
    }

    public function resume(QueueSelector $selector): Queue
    {
        $this->requireQueueRevisions();
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

    private function requireQueueRevisions(): void
    {
        if ($this->client->info()->api_version->minor < 6) {
            throw new UnsupportedFeatureException('Typed queue results and updates require JobU API 1.6 or newer');
        }
    }
}
