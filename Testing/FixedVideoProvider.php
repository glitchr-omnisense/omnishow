<?php

namespace Omnishow\Testing;

use Omnishow\Capabilities;
use Omnishow\ChannelInterface;
use Omnishow\Exception\NotSupportedException;
use Omnishow\LiveInterface;
use Omnishow\Model\Channel;
use Omnishow\Model\Page;
use Omnishow\Model\Platform;
use Omnishow\Model\Playlist;
use Omnishow\Model\Stream;
use Omnishow\Model\Video;
use Omnishow\Model\VideoQuery;
use Omnishow\Model\VideoRef;
use Omnishow\PlaylistInterface;
use Omnishow\ProviderInterface;
use Omnishow\VideoInterface;
use Omnishow\VideoSearchInterface;

/**
 * A video and live provider that answers from what it was given, with no
 * network: an application's tests, the harness's bare script. It gives a
 * video by its reference, searches titles by their words, lists a
 * channel's videos (newest first) and a playlist's (in the order given),
 * and the streams given per channel. A page's token is the offset of its
 * first item.
 */
final class FixedVideoProvider implements ProviderInterface, VideoInterface, VideoSearchInterface, ChannelInterface, PlaylistInterface, LiveInterface
{
    /**
     * @param list<Video>                $videos
     * @param list<Channel>              $channels
     * @param array<string, Playlist>    $playlists by id
     * @param array<string, list<string>> $items     a playlist's video ids, by playlist id
     * @param array<string, list<Stream>> $streams   by channel id: the live one and the scheduled ones
     * @param list<Platform>             $platforms the platforms it hosts
     */
    public function __construct(
        private readonly array $videos = [],
        private readonly array $channels = [],
        private readonly array $playlists = [],
        private readonly array $items = [],
        private readonly array $streams = [],
        private readonly array $platforms = [Platform::YouTube],
        private readonly string $name = 'fixed-video',
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): string
    {
        return 'Fixed videos';
    }

    public function capabilities(): Capabilities
    {
        return new Capabilities(keyless: true, video: true, videoSearch: true, channels: true, playlists: true, live: true, schedule: true, platforms: $this->platforms);
    }

    public function video(VideoRef $ref): ?Video
    {
        if (!$this->capabilities()->hosts($ref)) {
            throw NotSupportedException::video($this->getTitle(), $ref);
        }
        foreach ($this->videos as $video) {
            if ((string) $ref === (string) $video->ref) {
                return $video;
            }
        }

        return null;
    }

    public function videos(array $refs): array
    {
        return array_values(array_filter(array_map($this->video(...), $refs)));
    }

    public function searchVideos(VideoQuery $query): Page
    {
        $words = preg_split('~\W+~u', mb_strtolower($query->text), -1, \PREG_SPLIT_NO_EMPTY) ?: [];
        $found = array_values(array_filter($this->videos, static function (Video $video) use ($query, $words): bool {
            $text = mb_strtolower($video->title.' '.$video->description);

            return (null === $query->channel || $query->channel === $video->channel?->id)
                && [] === array_filter($words, static fn (string $word) => !str_contains($text, $word));
        }));

        return self::page($found, $query->page, $query->limit);
    }

    public function channel(string $channel): ?Channel
    {
        foreach ($this->channels as $one) {
            if ($channel === $one->id || $channel === $one->handle) {
                return $one;
            }
        }

        return null;
    }

    public function channelVideos(string $channel, ?string $page = null, int $limit = 25): Page
    {
        $id = $this->channel($channel)->id ?? $channel;
        $videos = array_values(array_filter($this->videos, static fn (Video $video) => $id === $video->channel?->id));
        usort($videos, static fn (Video $a, Video $b) => $b->published <=> $a->published);

        return self::page($videos, $page, Page::limit($limit, 50));
    }

    public function playlist(string $id): ?Playlist
    {
        return $this->playlists[$id] ?? null;
    }

    public function playlists(string $channel, ?string $page = null, int $limit = 25): Page
    {
        $id = $this->channel($channel)->id ?? $channel;

        return self::page(array_values(array_filter($this->playlists, static fn (Playlist $playlist) => $id === $playlist->channel?->id)), $page, Page::limit($limit, 50));
    }

    public function playlistVideos(string $id, ?string $page = null, int $limit = 25): Page
    {
        $videos = [];
        foreach ($this->items[$id] ?? [] as $position => $videoId) {
            foreach ($this->videos as $video) {
                if ($videoId === $video->ref->id) {
                    $videos[] = new Video($video->ref, $video->title, $video->source, $video->description, $video->duration, $video->published, $video->channel, $video->thumbnails, $video->url, $video->embedUrl, $video->privateEmbedUrl, $video->embeddable, $video->tags, $video->views, $video->language, $video->privacy, $video->stream, $position);
                }
            }
        }

        return self::page($videos, $page, Page::limit($limit, 50));
    }

    public function live(string $channel): ?Stream
    {
        foreach ($this->streams[$this->channel($channel)->id ?? $channel] ?? [] as $stream) {
            if ($stream->isLive()) {
                return $stream;
            }
        }

        return null;
    }

    public function schedule(string $channel, int $limit = 10): array
    {
        $upcoming = array_values(array_filter($this->streams[$this->channel($channel)->id ?? $channel] ?? [], static fn (Stream $stream) => !$stream->isLive() && null !== $stream->scheduled && null === $stream->ended));
        usort($upcoming, static fn (Stream $a, Stream $b) => $a->scheduled <=> $b->scheduled);

        return \array_slice($upcoming, 0, max(1, $limit));
    }

    /**
     * @template T
     *
     * @param list<T> $items
     *
     * @return Page<T>
     */
    private static function page(array $items, ?string $page, int $limit): Page
    {
        $offset = max(0, (int) $page);
        $next = $offset + $limit < \count($items) ? (string) ($offset + $limit) : null;

        return new Page(\array_slice($items, $offset, $limit), $next, \count($items));
    }
}
