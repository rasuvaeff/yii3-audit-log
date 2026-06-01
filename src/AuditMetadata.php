<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog;

/**
 * @api
 */
final readonly class AuditMetadata
{
    public function __construct(
        private ?string $requestId = null,
        private ?string $ip = null,
        private ?string $userAgent = null,
    ) {}

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }
}
