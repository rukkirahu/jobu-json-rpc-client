<?php

declare(strict_types=1);

namespace Jobu\Service;

use Jobu\Dto\Request\RunId;
use Jobu\Dto\Request\RunList;
use Jobu\Dto\Result\Cancellation;
use Jobu\Dto\Result\Run;
use Jobu\Dto\Result\RunPage;

final readonly class RunService extends Service
{
    public function get(RunId $request): Run
    {
        return $this->result('run.get', $request, Run::class);
    }

    public function list(RunList $query = new RunList()): RunPage
    {
        return $this->result('run.list', $query, RunPage::class);
    }

    public function cancel(RunId $request): Cancellation
    {
        return $this->result('run.cancel', $request, Cancellation::class);
    }
}
