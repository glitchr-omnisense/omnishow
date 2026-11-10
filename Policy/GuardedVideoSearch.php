<?php

namespace Omnishow\Policy;

use Omnisense\Contracts\Policy\PolicyGuard;
use Omnishow\Model\Page;
use Omnishow\Model\VideoQuery;
use Omnishow\ProviderInterface;
use Omnishow\VideoSearchInterface;

/**
 * A provider's video search behind its guard: each call is the operation
 * "searchVideos".
 *
 * @extends Guarded<VideoSearchInterface>
 */
final class GuardedVideoSearch extends Guarded implements VideoSearchInterface
{
    public function __construct(VideoSearchInterface&ProviderInterface $provider, PolicyGuard $guard)
    {
        parent::__construct($provider, $guard);
    }

    public function searchVideos(VideoQuery $query): Page
    {
        return $this->guard->call('searchVideos', fn () => $this->provider->searchVideos($query), amounts: $this->amounts('searchVideos', [$query]));
    }
}
