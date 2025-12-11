<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\ViewModel\SystemCheck;

class AddressLines extends ScopeConfigValue
{
    protected function getScopeConfigPath(): string
    {
        return 'customer/address/street_lines';
    }

    protected function isValid($value): bool
    {
        return $value > 2;
    }
    protected function getRecommendation(): string
    {
        return 'Most of the time, 3 or 4 is a good idea';
    }

    public function getUrl(): string|null
    {
        return $this->urlFactory->create()->getUrl(
            'admin/system_config/edit',
            ['section' => 'customer']
            ).'#customer_address';
    }
}
