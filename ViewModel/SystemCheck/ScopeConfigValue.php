<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck;

use Magento\Framework\App\Config\ScopeConfigInterface;
use LokiCheckout\CoreAdminhtml\ValueObject\CheckResultCollectionFactory;
use Magento\Framework\UrlFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

abstract class ScopeConfigValue extends SystemCheck
{
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager,
        protected readonly UrlFactory $urlFactory,
        CheckResultCollectionFactory $checkResultCollectionFactory,
    ) {
        parent::__construct($checkResultCollectionFactory);
    }

    protected function execute(): void
    {
        $path = $this->getScopeConfigPath();
        $values = $this->getStoreValues($path);

        if (empty($values) || $this->hasIdenticalValues($values)) {
            $value = empty($values) ? $this->scopeConfig->getValue($path) : reset($values);
            $this->addCheckResult(
                $path,
                $value,
                $this->isValid($value),
                $this->getRecommendation(),
                $this->isWarning()
            );
            return;
        }

        foreach ($values as $storeCode => $value) {
            $this->addCheckResult(
                $path . ' (' . $storeCode . ')',
                $value,
                $this->isValid($value),
                $this->getRecommendation(),
                $this->isWarning()
            );
        }
    }

    protected function isValid($value): bool
    {
        return true;
    }

    protected function isWarning(): bool
    {
        return false;
    }

    abstract protected function getScopeConfigPath(): string;

    abstract protected function getRecommendation(): string;

    /**
     * @return array<string, mixed> Config values indexed by store code
     */
    private function getStoreValues(string $path): array
    {
        $values = [];
        foreach ($this->storeManager->getStores() as $store) {
            $values[(string)$store->getCode()] = $this->scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORE,
                $store->getId()
            );
        }

        return $values;
    }

    private function hasIdenticalValues(array $values): bool
    {
        $firstValue = reset($values);
        foreach ($values as $value) {
            if ($value !== $firstValue) {
                return false;
            }
        }

        return true;
    }
}
