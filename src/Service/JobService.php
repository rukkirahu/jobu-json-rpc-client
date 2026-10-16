<?php

declare(strict_types=1);

namespace Jobu\Service;

use Jobu\Dto\Request\JobCreate;
use Jobu\Dto\Request\JobDelete;
use Jobu\Dto\Request\JobId;
use Jobu\Dto\Request\JobList;
use Jobu\Dto\Request\JobMove;
use Jobu\Dto\Request\JobRunNow;
use Jobu\Dto\Request\JobUpdate;
use Jobu\Dto\Result\Job;
use Jobu\Dto\Result\JobPage;
use Jobu\Dto\Result\Run;

final readonly class JobService extends Service
{
    public function create(JobCreate $request): Job
    {
        return $this->result('job.create', $request, Job::class);
    }

    public function get(JobId $request): Job
    {
        return $this->result('job.get', $request, Job::class);
    }

    public function list(JobList $query = new JobList()): JobPage
    {
        return $this->result('job.list', $query, JobPage::class);
    }

    public function update(JobUpdate $request): Job
    {
        return $this->result('job.update', $request, Job::class);
    }

    public function suspend(JobId $request): Job
    {
        return $this->result('job.suspend', $request, Job::class);
    }

    public function resume(JobId $request): Job
    {
        return $this->result('job.resume', $request, Job::class);
    }

    public function move(JobMove $request): Job
    {
        return $this->result('job.move', $request, Job::class);
    }

    public function delete(JobDelete $request): void
    {
        $this->voidResult('job.delete', $request);
    }

    public function runNow(JobRunNow $request): Run
    {
        return $this->result('job.run_now', $request, Run::class);
    }
}
