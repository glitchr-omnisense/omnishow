<?php

namespace Omnishow\Policy;

use Omnisense\Contracts\Policy\PolicyGuard;
use Omnishow\AvailabilityInterface;
use Omnishow\Model\WorkRef;
use Omnishow\ProviderInterface;

/**
 * A provider's offers behind its guard: each call is the operation
 * "offers".
 *
 * @extends Guarded<AvailabilityInterface>
 */
final class GuardedAvailability extends Guarded implements AvailabilityInterface
{
    public function __construct(AvailabilityInterface&ProviderInterface $provider, PolicyGuard $guard)
    {
        parent::__construct($provider, $guard);
    }

    public function offers(WorkRef $ref, string $country): array
    {
        return $this->guard->call('offers', fn () => $this->provider->offers($ref, $country), amounts: $this->amounts('offers', [$ref, $country]));
    }
}
