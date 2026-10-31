<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\ViewModel;

use LokiCheckout\CoreAdminhtml\ValueObject\CheckResult;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck\SystemCheckInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class SystemCheckCollection implements ArgumentInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly array $systemChecks = []
    ) {
    }

    /**
     * @return SystemCheckInterface[]
     */
    public function getSystemChecks(): array
    {
        return $this->systemChecks;
    }

    /**
     * @return CheckResult[]
     */
    public function getCheckResults(): array
    {
        $checkResults = [];
        foreach ($this->systemChecks as $name => $systemCheck) {
            $name = (string)$name;
            if (!$systemCheck instanceof SystemCheckInterface) {
                $this->logger->error('LokiCheckout system check "' . $name . '" does not implement ' . SystemCheckInterface::class);
                $checkResults[] = $this->createErrorResult($name, (string)__('Invalid system check'));
                continue;
            }

            try {
                array_push($checkResults, ...$systemCheck->getCheckResultCollection()->get());
            } catch (Throwable $exception) {
                $this->logger->error('LokiCheckout system check "' . $name . '" failed', ['exception' => $exception]);
                $checkResults[] = $this->createErrorResult($name, $exception->getMessage());
            }
        }

        return $checkResults;
    }

    private function createErrorResult(string $name, string $message): CheckResult
    {
        return new CheckResult(
            $name,
            (string)__('Error'),
            false,
            (string)__('This system check failed: %1', $message)
        );
    }
}
