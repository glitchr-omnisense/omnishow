<?php

namespace Omnishow;

use Omnishow\Model\Channel;
use Omnishow\Model\Page;
use Omnishow\Model\Video;

/** The video contract: a channel and the videos it uploaded. */
interface ChannelInterface
{
    /**
     * The channel's record, null when the platform does not know it.
     *
     * @param string $channel its identifier, or the name it is reached by where the platform resolves one ("@handle" at YouTube)
     */
    public function channel(string $channel): ?Channel;

    /**
     * The channel's videos, newest first, one page at a time.
     *
     * @param ?string $page  the token of the page asked (Page::$next)
     * @param int     $limit at most this many per page (kept within what the platform answers)
     *
     * @return Page<Video>
     */
    public function channelVideos(string $channel, ?string $page = null, int $limit = 25): Page;
}
