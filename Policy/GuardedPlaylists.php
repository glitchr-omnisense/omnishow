<?php

namespace Omnishow\Policy;

use Omnisense\Contracts\Policy\PolicyGuard;
use Omnishow\Model\Page;
use Omnishow\Model\Playlist;
use Omnishow\PlaylistInterface;
use Omnishow\ProviderInterface;

/**
 * A provider's playlists behind its guard: each call is the operation of its
 * method's name ("playlist", "playlists", "playlistVideos").
 *
 * @extends Guarded<PlaylistInterface>
 */
final class GuardedPlaylists extends Guarded implements PlaylistInterface
{
    public function __construct(PlaylistInterface&ProviderInterface $provider, PolicyGuard $guard)
    {
        parent::__construct($provider, $guard);
    }

    public function playlist(string $id): ?Playlist
    {
        return $this->guard->call('playlist', fn () => $this->provider->playlist($id), amounts: $this->amounts('playlist', [$id]));
    }

    public function playlists(string $channel, ?string $page = null, int $limit = 25): Page
    {
        return $this->guard->call('playlists', fn () => $this->provider->playlists($channel, $page, $limit), amounts: $this->amounts('playlists', [$channel, $page, $limit]));
    }

    public function playlistVideos(string $id, ?string $page = null, int $limit = 25): Page
    {
        return $this->guard->call('playlistVideos', fn () => $this->provider->playlistVideos($id, $page, $limit), amounts: $this->amounts('playlistVideos', [$id, $page, $limit]));
    }
}
