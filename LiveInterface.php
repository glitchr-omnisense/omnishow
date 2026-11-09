<?php

namespace Omnishow;

use Omnishow\Model\Stream;

/** The live contract: whether a channel is on air, its stream, its schedule. */
interface LiveInterface
{
    /**
     * The stream the channel is broadcasting now, null when it is not live
     * (or the platform does not know the channel).
     *
     * @param string $channel its identifier, or the name it is reached by where the platform resolves one (a Twitch login, a YouTube "@handle")
     */
    public function live(string $channel): ?Stream;

    /**
     * The broadcasts the channel has scheduled, soonest first; empty when it
     * has none or the platform keeps no schedule (Capabilities::$schedule).
     *
     * @return list<Stream>
     */
    public function schedule(string $channel, int $limit = 10): array;
}
