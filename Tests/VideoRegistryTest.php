<?php

namespace Omnishow\Tests;

use Omnishow\Exception\NotSupportedException;
use Omnishow\Model\Channel;
use Omnishow\Model\Identifiers;
use Omnishow\Model\LiveState;
use Omnishow\Model\Platform;
use Omnishow\Model\Playlist;
use Omnishow\Model\Stream;
use Omnishow\Model\Video;
use Omnishow\Model\VideoLink;
use Omnishow\Model\VideoQuery;
use Omnishow\Model\VideoRef;
use Omnishow\Model\Work;
use Omnishow\Model\WorkType;
use Omnishow\Registry;
use Omnishow\Testing\FixedProviderFactory;
use Omnishow\Testing\FixedVideoProviderFactory;
use PHPUnit\Framework\TestCase;

final class VideoRegistryTest extends TestCase
{
    public function testWorkVideoAndLiveProvidersShareOneRegistry(): void
    {
        $registry = self::registry();

        self::assertSame(['work'], $registry->get('films')->capabilities()->families());
        self::assertSame(['video', 'live'], $registry->get('videos')->capabilities()->families());
        self::assertSame('Big Buck Bunny', $registry->video('videos')->video(VideoRef::youtube('aqz-KE-bpKQ'))?->title);
        self::assertSame(['vKQi3bBA1y8', 'aqz-KE-bpKQ'], array_map(static fn (Video $v) => $v->ref->id, $registry->video('videos')->videos([VideoRef::youtube('vKQi3bBA1y8'), VideoRef::youtube('missing0000'), VideoRef::youtube('aqz-KE-bpKQ')])));
        self::assertSame(['aqz-KE-bpKQ'], array_map(static fn (Video $v) => $v->ref->id, $registry->videoSearch('videos')->searchVideos(new VideoQuery('bunny'))->items));
        self::assertSame('Example Channel', $registry->channels('videos')->channel('@example')?->title);

        $page = $registry->channels('videos')->channelVideos('@example', null, 1);
        self::assertSame(['vKQi3bBA1y8'], array_map(static fn (Video $v) => $v->ref->id, $page->items), 'newest first');
        self::assertSame('1', $page->next);
        self::assertSame(['aqz-KE-bpKQ'], array_map(static fn (Video $v) => $v->ref->id, $registry->channels('videos')->channelVideos('@example', $page->next, 1)->items));

        self::assertSame(['Shorts'], array_map(static fn (Playlist $p) => $p->title, $registry->playlists('videos')->playlists('UCexample')->items));
        self::assertSame([[0, 'aqz-KE-bpKQ'], [1, 'vKQi3bBA1y8']], array_map(static fn (Video $v) => [$v->position, $v->ref->id], $registry->playlists('videos')->playlistVideos('PLshorts')->items));
        self::assertSame([], $registry->playlists('videos')->playlistVideos('PLnone')->items);

        self::assertSame('On air', $registry->live('videos')->live('@example')?->title);
        self::assertSame(['Tomorrow', 'Next week'], array_map(static fn (Stream $s) => $s->title, $registry->live('videos')->schedule('UCexample')));
        self::assertNull($registry->live('videos')->live('UCnobody'));
    }

    public function testAWorksTrailerIsReadFromTheProviderThatHostsItsPlatform(): void
    {
        $registry = self::registry();
        $work = $registry->work(\Omnishow\Model\WorkRef::tmdb(603));

        self::assertNotNull($work);
        $trailer = $work->trailer();
        self::assertNotNull($trailer);
        self::assertSame('Trailer', $registry->findVideo($trailer->ref)?->title);
        self::assertNull($registry->findVideo(VideoRef::vimeo(1)), 'no provider hosts Vimeo here');
        self::assertNull($registry->findVideo($trailer->ref, 'films'), 'a work provider gives no video');
    }

    public function testAContractTheProviderLacksIsSaid(): void
    {
        $registry = self::registry();
        foreach (['video' => 'give videos', 'live' => 'say what is live', 'channels' => 'give channels', 'playlists' => 'give playlists', 'videoSearch' => 'search videos'] as $method => $said) {
            try {
                $registry->{$method}('films');
                self::fail($method);
            } catch (NotSupportedException $e) {
                self::assertStringContainsString('does not '.$said, $e->getMessage());
            }
        }
        $this->expectExceptionMessage('Fixed videos does not host Vimeo videos.');
        $registry->video('videos')->video(VideoRef::vimeo(1));
    }

    private static function registry(): Registry
    {
        $channel = new Channel(Platform::YouTube, 'UCexample', 'Example Channel', '@example');
        $videos = [
            new Video(VideoRef::youtube('aqz-KE-bpKQ'), 'Big Buck Bunny', 'fixed-video', 'A bunny', 635, new \DateTimeImmutable('2014-11-10T14:05:55Z'), $channel),
            new Video(VideoRef::youtube('vKQi3bBA1y8'), 'Trailer', 'fixed-video', null, 150, new \DateTimeImmutable('2021-03-01T12:00:00Z'), $channel),
        ];
        $matrix = new Work(WorkType::Movie, 'The Matrix', Identifiers::of(['tmdb' => 603]), 'fixed', videos: [new VideoLink(VideoRef::youtube('vKQi3bBA1y8'), 'Trailer', 'trailer', 'en', true)]);

        return new Registry([
            new FixedProviderFactory([$matrix]),
            new FixedVideoProviderFactory($videos, [$channel], ['PLshorts' => new Playlist(Platform::YouTube, 'PLshorts', 'Shorts', 'fixed-video', channel: $channel)], ['PLshorts' => ['aqz-KE-bpKQ', 'vKQi3bBA1y8']], ['UCexample' => [
                new Stream(Platform::YouTube, LiveState::Upcoming, 'fixed-video', $channel, 'Next week', scheduled: new \DateTimeImmutable('2026-11-16T18:00:00Z')),
                new Stream(Platform::YouTube, LiveState::Live, 'fixed-video', $channel, 'On air', video: VideoRef::youtube('aqz-KE-bpKQ'), started: new \DateTimeImmutable('2026-11-09T18:00:00Z')),
                new Stream(Platform::YouTube, LiveState::Upcoming, 'fixed-video', $channel, 'Tomorrow', scheduled: new \DateTimeImmutable('2026-11-10T18:00:00Z')),
            ]]),
        ], ['films' => ['factory' => 'fixed'], 'videos' => ['factory' => 'fixed-video']]);
    }
}
