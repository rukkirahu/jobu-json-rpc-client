<?php

declare(strict_types=1);

namespace Jobu\Service;

use Jobu\Dto\Request\AttemptGet;
use Jobu\Dto\Request\AttemptList;
use Jobu\Dto\Request\AttemptOutput;
use Jobu\Dto\Result\Attempt;
use Jobu\Dto\Result\AttemptPage;
use Jobu\Dto\Result\OutputChunk;

final readonly class AttemptService extends Service
{
    public function get(AttemptGet $request): Attempt
    {
        return $this->result('attempt.get', $request, Attempt::class);
    }

    public function list(AttemptList $query): AttemptPage
    {
        return $this->result('attempt.list', $query, AttemptPage::class);
    }

    public function output(AttemptOutput $request): OutputChunk
    {
        return $this->result('attempt.output', $request, OutputChunk::class);
    }
}
