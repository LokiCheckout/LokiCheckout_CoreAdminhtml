<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\ValueObject;

class CheckResultCollection
{
    public function __construct(
        private array $checkResults = []
    ) {
    }

    public function add(CheckResult $checkResult): void
    {
        $this->checkResults[] = $checkResult;
    }

    /**
     * @return CheckResult[]
     */
    public function get(): array
    {
        return $this->checkResults;
    }
}
