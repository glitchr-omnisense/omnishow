<?php

namespace Omnishow;

use Omnishow\Exception\NotSupportedException;
use Omnishow\Model\Video;
use Omnishow\Model\VideoRef;

/** The video contract: the record of a video hosted at the provider's platform. */
interface VideoInterface
{
    /**
     * The video's record, null when the platform does not show it (unknown,
     * private, removed).
     *
     * @throws NotSupportedException when the video is hosted elsewhere (Capabilities::hosts())
     */
    public function video(VideoRef $ref): ?Video;

    /**
     * Several videos' records in as few calls as the platform allows, in the
     * order given, those it does not show left out.
     *
     * @param list<VideoRef> $refs
     *
     * @return list<Video>
     */
    public function videos(array $refs): array;
}
