<?php

declare(strict_types=1);

namespace Jobu\Service;

use Jobu\Dto\Request\SystemStats;
use Jobu\Dto\Result\StatisticsPage;
use Jobu\Dto\Result\SystemInfo;

final readonly class SystemService extends Service
{
    public function info(): SystemInfo
    {
        return SystemInfo::from($this->client->info());
    }

    public function stats(SystemStats $query = new SystemStats()): StatisticsPage
    {
        return $this->result('system.stats', $query, StatisticsPage::class);
    }
}
