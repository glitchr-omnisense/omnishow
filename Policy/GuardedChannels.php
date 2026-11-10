<?php

namespace Omnishow\Policy;

use Omnisense\Contracts\Policy\PolicyGuard;
use Omnishow\ChannelInterface;
use Omnishow\Model\Channel;
use Omnishow\Model\Page;
use Omnishow\ProviderInterface;

/**
 * A provider's channels behind its guard: each call is the operation of its
 * method's name ("channel", "channelVideos").
 *
 * @extends Guarded<ChannelInterface>
 */
final class GuardedChannels extends Guarded implements ChannelInterface
{
    public function __construct(ChannelInterface&ProviderInterface $provider, PolicyGuard $guard)
    {
        parent::__construct($provider, $guard);
    }

    public function channel(string $channel): ?Channel
    {
        return $this->guard->call('channel', fn () => $this->provider->channel($channel), amounts: $this->amounts('channel', [$channel]));
    }

    public function channelVideos(string $channel, ?string $page = null, int $limit = 25): Page
    {
        return $this->guard->call('channelVideos', fn () => $this->provider->channelVideos($channel, $page, $limit), amounts: $this->amounts('channelVideos', [$channel, $page, $limit]));
    }
}
