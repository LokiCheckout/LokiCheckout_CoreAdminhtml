<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck;

use LokiCheckout\CoreAdminhtml\ValueObject\CheckResult;
use LokiCheckout\CoreAdminhtml\ValueObject\CheckResultCollection;
use LokiCheckout\CoreAdminhtml\ValueObject\CheckResultCollectionFactory;

abstract class SystemCheck implements SystemCheckInterface
{
    private CheckResultCollection $checkResultCollection;

    public function __construct(
        private readonly CheckResultCollectionFactory $checkResultCollectionFactory,
    ) {
        $this->checkResultCollection = $checkResultCollectionFactory->create();
    }

    public function getCheckResultCollection(): CheckResultCollection
    {
        $this->checkResultCollection = $this->checkResultCollectionFactory->create();
        $this->execute();
        return $this->checkResultCollection;
    }

    abstract protected function execute(): void;

    public function addCheckResult(
        string $label,
        mixed $value,
        bool $valid,
        string $recommendation
    ): void {
        $this->checkResultCollection->add(
            new CheckResult(
                $label,
                $value,
                $valid,
                $recommendation,
                $this->getUrl(),
            )
        );
    }

    public function getUrl(): string|null
    {
        return null;
    }
}
