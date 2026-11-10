<?php

namespace Omnishow\Policy;

use Omnisense\Contracts\Policy\PolicyGuard;
use Omnishow\LookupInterface;
use Omnishow\Model\Work;
use Omnishow\Model\WorkRef;
use Omnishow\ProviderInterface;

/**
 * A provider's records behind its guard: each call is the operation
 * "work".
 *
 * @extends Guarded<LookupInterface>
 */
final class GuardedLookup extends Guarded implements LookupInterface
{
    public function __construct(LookupInterface&ProviderInterface $provider, PolicyGuard $guard)
    {
        parent::__construct($provider, $guard);
    }

    public function work(WorkRef $ref, ?string $language = null): ?Work
    {
        return $this->guard->call('work', fn () => $this->provider->work($ref, $language), amounts: $this->amounts('work', [$ref, $language]));
    }
}
