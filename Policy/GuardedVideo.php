<?php

namespace Omnishow\Policy;

use Omnisense\Contracts\Policy\PolicyGuard;
use Omnishow\Model\Video;
use Omnishow\Model\VideoRef;
use Omnishow\ProviderInterface;
use Omnishow\VideoInterface;

/**
 * A provider's videos behind its guard: each call is the operation of its
 * method's name ("video", "videos").
 *
 * @extends Guarded<VideoInterface>
 */
final class GuardedVideo extends Guarded implements VideoInterface
{
    public function __construct(VideoInterface&ProviderInterface $provider, PolicyGuard $guard)
    {
        parent::__construct($provider, $guard);
    }

    public function video(VideoRef $ref): ?Video
    {
        return $this->guard->call('video', fn () => $this->provider->video($ref), amounts: $this->amounts('video', [$ref]));
    }

    public function videos(array $refs): array
    {
        return $this->guard->call('videos', fn () => $this->provider->videos($refs), amounts: $this->amounts('videos', [$refs]));
    }
}
