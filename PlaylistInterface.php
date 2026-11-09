<?php

namespace Omnishow;

use Omnishow\Model\Page;
use Omnishow\Model\Playlist;
use Omnishow\Model\Video;

/** The video contract: a channel's playlists and their videos. */
interface PlaylistInterface
{
    /** The playlist's record, null when the platform does not show it. */
    public function playlist(string $id): ?Playlist;

    /**
     * A channel's playlists, one page at a time.
     *
     * @return Page<Playlist>
     */
    public function playlists(string $channel, ?string $page = null, int $limit = 25): Page;

    /**
     * A playlist's videos in its order, one page at a time; an unknown
     * playlist is an empty page. Videos the platform no longer shows
     * (private, removed) are left out.
     *
     * @return Page<Video>
     */
    public function playlistVideos(string $id, ?string $page = null, int $limit = 25): Page;
}
