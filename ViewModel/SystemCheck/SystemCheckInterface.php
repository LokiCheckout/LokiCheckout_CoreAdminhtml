<?php

declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck;

use LokiCheckout\CoreAdminhtml\ValueObject\CheckResultCollection;

interface SystemCheckInterface
{
    public function getCheckResultCollection(): CheckResultCollection;
    public function getUrl(): string|null;
}
