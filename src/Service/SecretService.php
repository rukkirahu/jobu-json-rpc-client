<?php

declare(strict_types=1);

namespace Jobu\Service;

use Jobu\Dto\Request\SecretList;
use Jobu\Dto\Request\SecretName;
use Jobu\Dto\Request\SecretSet;
use Jobu\Dto\Result\SecretMetadata;
use Jobu\Dto\Result\SecretPage;

final readonly class SecretService extends Service
{
    public function set(SecretSet $request): SecretMetadata
    {
        return $this->result('secret.set', $request, SecretMetadata::class);
    }

    public function list(SecretList $query = new SecretList()): SecretPage
    {
        return $this->result('secret.list', $query, SecretPage::class);
    }

    public function delete(SecretName $request): void
    {
        $this->voidResult('secret.delete', $request);
    }
}
