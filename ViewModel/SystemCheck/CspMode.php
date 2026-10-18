<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck;

class CspMode extends ScopeConfigValue
{
    protected function getScopeConfigPath(): string
    {
        return 'csp/mode/storefront_checkout_index_index/report_only';
    }

    protected function isValid($value): bool
    {
        return (string)$value !== '1';
    }

    protected function isWarning(): bool
    {
        return true;
    }

    protected function getRecommendation(): string
    {
        return 'For PCI DSS 4.0 compliance, use CSP Strict Mode (report_only = 0) on the checkout. '
            . 'Make sure your theme and extensions are compatible first.';
    }
}
