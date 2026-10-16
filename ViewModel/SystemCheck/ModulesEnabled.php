<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck;

use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Module\ModuleList;
use LokiCheckout\Core\Config\LokiCheckoutModules;
use LokiCheckout\CoreAdminhtml\ValueObject\CheckResultCollectionFactory;

class ModulesEnabled extends SystemCheck
{
    public function __construct(
        private readonly ModuleList $moduleList,
        private readonly ModuleManager $moduleManager,
        private readonly LokiCheckoutModules $lokiCheckoutModules,
        CheckResultCollectionFactory $checkResultCollectionFactory
    ) {
        parent::__construct($checkResultCollectionFactory);
    }

    protected function execute(): void
    {
        $moduleDependencies = [];

        foreach ($this->moduleList->getAll() as $module) {
            $moduleName = $module['name'];
            if (false === str_starts_with($moduleName, 'Loki')
                && false === $this->lokiCheckoutModules->seemsModule($moduleName)
            ) {
                continue;
            }

            foreach ($module['sequence'] ?? [] as $moduleDependency) {
                $moduleDependencies[$moduleDependency][] = $moduleName;
            }
        }

        foreach ($moduleDependencies as $moduleDependency => $moduleNames) {
            $isEnabled = $this->moduleManager->isEnabled($moduleDependency);
            $this->addCheckResult(
                $moduleDependency,
                $isEnabled ? 'Enabled' : 'Disabled',
                $isEnabled,
                (string)__('The following modules depend on module %1 to be enabled', $moduleDependency) . ': ' . implode(', ', $moduleNames)
            );
        }
    }
}
