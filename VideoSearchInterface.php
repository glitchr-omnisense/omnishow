<?php

namespace Omnishow;

use Omnishow\Model\Page;
use Omnishow\Model\Video;
use Omnishow\Model\VideoQuery;

/** The video contract: a search, where the platform allows one. */
interface VideoSearchInterface
{
    /**
     * Videos whose words match, best first, as short as the platform's
     * search answers them (ask VideoInterface for the whole records).
     *
     * @return Page<Video>
     */
    public function searchVideos(VideoQuery $query): Page;
}
