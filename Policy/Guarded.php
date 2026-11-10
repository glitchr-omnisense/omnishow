<?php

namespace Omnishow\Policy;

use Omnisense\Contracts\Policy\PolicyGuard;
use Omnishow\Capabilities;
use Omnishow\ProviderInterface;

/**
 * A provider behind its guard: what every Guarded* shares - its name, title
 * and capabilities as they are, the guard, the provider itself (inner()).
 *
 * @template P of object the contract it keeps (a provider's contracts do not extend ProviderInterface)
 */
abstract class Guarded implements ProviderInterface
{
    /** @param P&ProviderInterface $provider */
    public function __construct(protected readonly ProviderInterface $provider, protected readonly PolicyGuard $guard)
    {
    }

    public function getName(): string
    {
        return $this->provider->getName();
    }

    public function getTitle(): string
    {
        return $this->provider->getTitle();
    }

    public function capabilities(): Capabilities
    {
        return $this->provider->capabilities();
    }

    /** @return P&ProviderInterface the provider, unguarded */
    public function inner(): ProviderInterface
    {
        return $this->provider;
    }

    public function guard(): PolicyGuard
    {
        return $this->guard;
    }

    /**
     * What a call consumes where the provider measures it
     * (MeasuresUsageInterface), by unit; [] for the policy's costs.
     *
     * @param list<mixed> $arguments
     *
     * @return array<string, int>
     */
    protected function amounts(string $operation, array $arguments): array
    {
        return $this->provider instanceof MeasuresUsageInterface ? $this->provider->usage($operation, $arguments) : [];
    }
}
