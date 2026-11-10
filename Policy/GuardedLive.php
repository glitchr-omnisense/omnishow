<?php

namespace Omnishow\Policy;

use Omnisense\Contracts\Policy\PolicyGuard;
use Omnishow\LiveInterface;
use Omnishow\Model\Stream;
use Omnishow\ProviderInterface;

/**
 * A provider's live streams behind its guard: each call is the operation of its
 * method's name ("live", "schedule").
 *
 * @extends Guarded<LiveInterface>
 */
final class GuardedLive extends Guarded implements LiveInterface
{
    public function __construct(LiveInterface&ProviderInterface $provider, PolicyGuard $guard)
    {
        parent::__construct($provider, $guard);
    }

    public function live(string $channel): ?Stream
    {
        return $this->guard->call('live', fn () => $this->provider->live($channel));
    }

    public function schedule(string $channel, int $limit = 10): array
    {
        return $this->guard->call('schedule', fn () => $this->provider->schedule($channel, $limit));
    }
}
