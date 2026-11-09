<?php

namespace Omnishow\Testing;

use Omnishow\Config;
use Omnishow\Model\Channel;
use Omnishow\Model\Playlist;
use Omnishow\Model\Stream;
use Omnishow\Model\Video;
use Omnishow\ProviderFactory;
use Omnishow\ProviderInterface;

/** FixedVideoProvider's factory: "fixed-video", answering from the videos, channels, playlists and streams it holds. */
final class FixedVideoProviderFactory extends ProviderFactory
{
    /**
     * @param list<Video>                 $videos
     * @param list<Channel>               $channels
     * @param array<string, Playlist>     $playlists
     * @param array<string, list<string>> $items
     * @param array<string, list<Stream>> $streams
     */
    public function __construct(private readonly array $videos = [], private readonly array $channels = [], private readonly array $playlists = [], private readonly array $items = [], private readonly array $streams = [])
    {
    }

    protected function populate(Config $c): void
    {
        $c->defaults(['omnishow.factory_name' => 'fixed-video', 'omnishow.factory_title' => 'Fixed videos', 'omnishow.required_options' => []]);
    }

    protected function build(Config $c): ProviderInterface
    {
        return new FixedVideoProvider($this->videos, $this->channels, $this->playlists, $this->items, $this->streams);
    }
}
