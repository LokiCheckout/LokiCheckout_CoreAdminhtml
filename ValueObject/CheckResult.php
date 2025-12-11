<?php declare(strict_types=1);

namespace LokiCheckout\CoreAdminhtml\ValueObject;

class CheckResult
{
    public function __construct(
        private readonly string $label,
        private readonly mixed $value,
        private readonly bool $valid,
        private readonly string $recommendation,
        private readonly string|null $url = null,
    ) {
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getRecommendation(): string
    {
        return $this->recommendation;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }
}
