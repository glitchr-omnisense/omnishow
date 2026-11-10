<?php

namespace Omnishow\Policy;

use Omnisense\Contracts\Policy\PolicyGuard;
use Omnishow\EpisodesInterface;
use Omnishow\Model\WorkRef;
use Omnishow\ProviderInterface;

/**
 * A provider's episodes behind its guard: each call is the operation
 * "episodes".
 *
 * @extends Guarded<EpisodesInterface>
 */
final class GuardedEpisodes extends Guarded implements EpisodesInterface
{
    public function __construct(EpisodesInterface&ProviderInterface $provider, PolicyGuard $guard)
    {
        parent::__construct($provider, $guard);
    }

    public function episodes(WorkRef $series, int $season, ?string $language = null): array
    {
        return $this->guard->call('episodes', fn () => $this->provider->episodes($series, $season, $language));
    }
}
