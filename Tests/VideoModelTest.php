<?php

namespace Omnishow\Tests;

use Omnishow\Capabilities;
use Omnishow\Exception\InvalidArgumentException;
use Omnishow\Http\Data;
use Omnishow\Model\Channel;
use Omnishow\Model\Identifiers;
use Omnishow\Model\Image;
use Omnishow\Model\ImageKind;
use Omnishow\Model\LiveState;
use Omnishow\Model\Page;
use Omnishow\Model\Platform;
use Omnishow\Model\Stream;
use Omnishow\Model\Video;
use Omnishow\Model\VideoLink;
use Omnishow\Model\VideoQuery;
use Omnishow\Model\VideoRef;
use Omnishow\Model\Work;
use Omnishow\Model\WorkRef;
use Omnishow\Model\WorkType;
use PHPUnit\Framework\TestCase;

final class VideoModelTest extends TestCase
{
    public function testEachPlatformGivesItsPageItsPlayerAndItsPrivatePlayer(): void
    {
        self::assertSame(['https://www.youtube.com/watch?v=aqz-KE-bpKQ', 'https://www.youtube.com/embed/aqz-KE-bpKQ', 'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ'], [VideoRef::youtube('aqz-KE-bpKQ')->url(), VideoRef::youtube('aqz-KE-bpKQ')->embedUrl(), VideoRef::youtube('aqz-KE-bpKQ')->embedUrl(true)]);
        self::assertSame(['https://vimeo.com/76979871', 'https://player.vimeo.com/video/76979871', 'https://player.vimeo.com/video/76979871?dnt=1'], [VideoRef::vimeo(76979871)->url(), VideoRef::vimeo(76979871)->embedUrl(), VideoRef::vimeo(76979871)->embedUrl(true)]);
        self::assertSame('https://player.vimeo.com/video/76979871?h=8272103f6e&dnt=1', Platform::Vimeo->privateEmbedUrl('https://player.vimeo.com/video/76979871?h=8272103f6e'));
        self::assertSame(['https://www.twitch.tv/videos/335921245', 'https://player.twitch.tv/?video=v335921245', null], [VideoRef::twitch('v335921245')->url(), VideoRef::twitch(335921245)->embedUrl(), VideoRef::twitch(335921245)->embedUrl(true)]);
        self::assertSame(Platform::YouTube, Platform::fromName('YouTube'));
        self::assertNull(Platform::fromName('Dailymotion'));
        self::assertSame('youtube:aqz-KE-bpKQ', (string) VideoRef::youtube('aqz-KE-bpKQ'));

        $this->expectException(InvalidArgumentException::class);
        VideoRef::vimeo(' ');
    }

    public function testAVideoIsReadFromItsAddress(): void
    {
        foreach ([
            'https://www.youtube.com/watch?v=aqz-KE-bpKQ&t=42' => 'youtube:aqz-KE-bpKQ',
            'https://www.youtube.com/watch?feature=share&v=aqz-KE-bpKQ' => 'youtube:aqz-KE-bpKQ',
            'youtu.be/aqz-KE-bpKQ?si=x' => 'youtube:aqz-KE-bpKQ',
            'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ' => 'youtube:aqz-KE-bpKQ',
            'https://youtube.com/shorts/aqz-KE-bpKQ' => 'youtube:aqz-KE-bpKQ',
            'https://vimeo.com/76979871' => 'vimeo:76979871',
            'https://player.vimeo.com/video/76979871?h=8272103f6e' => 'vimeo:76979871',
            'https://www.twitch.tv/videos/335921245' => 'twitch:335921245',
        ] as $url => $ref) {
            self::assertSame($ref, (string) VideoRef::fromUrl($url), $url);
        }
        foreach (['https://www.youtube.com/@example', 'https://www.youtube.com/playlist?list=PL0123456789', 'https://vimeo.com/channels/staffpicks', 'https://example.org/watch?v=aqz-KE-bpKQ'] as $url) {
            self::assertNull(VideoRef::fromUrl($url), $url);
        }
    }

    public function testAVideoDefaultsToItsPlatformsPlayers(): void
    {
        $video = new Video(VideoRef::youtube('aqz-KE-bpKQ'), 'Big Buck Bunny', 'test', duration: 635, thumbnails: [new Image(ImageKind::Thumbnail, 'https://i.ytimg.com/vi/aqz-KE-bpKQ/hqdefault.jpg', 480, 360), new Image(ImageKind::Thumbnail, 'https://i.ytimg.com/vi/aqz-KE-bpKQ/default.jpg', 120, 90)]);

        self::assertSame('https://www.youtube.com/watch?v=aqz-KE-bpKQ', $video->url);
        self::assertSame('https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ', $video->embed(true));
        self::assertSame('https://www.youtube.com/embed/aqz-KE-bpKQ', $video->embed(false, 'example.org'), 'YouTube needs no parent');
        self::assertSame(480, $video->thumbnail()?->width);
        self::assertSame(480, $video->thumbnail(200)?->width);
        self::assertFalse($video->isLive());

        $vod = new Video(VideoRef::twitch(335921245), 'A VOD', 'test');
        self::assertNull($vod->privateEmbedUrl);
        self::assertSame('https://player.twitch.tv/?video=v335921245&parent=example.org', $vod->embed(true, 'example.org'));

        $live = new Video(VideoRef::youtube('aqz-KE-bpKQ'), 'Live', 'test', stream: new Stream(Platform::YouTube, LiveState::Live, 'test', viewers: 12));
        self::assertTrue($live->isLive());
        self::assertSame('live', Data::of($live->toArray())->string('stream.state'));
    }

    public function testAStreamGivesItsPlayerWithTheParentWhereNeeded(): void
    {
        $stream = new Stream(Platform::Twitch, LiveState::Live, 'test', new Channel(Platform::Twitch, '141981764', 'TwitchDev', 'twitchdev'), 'Building', embedUrl: 'https://player.twitch.tv/?channel=twitchdev', started: new \DateTimeImmutable('2026-11-09T18:00:00Z'));

        self::assertSame('https://player.twitch.tv/?channel=twitchdev&parent=example.org', $stream->embed(true, 'example.org'));
        self::assertSame('2026-11-09T18:00:00+00:00', $stream->start()?->format(\DATE_ATOM));
        self::assertNull((new Stream(Platform::Twitch, LiveState::Upcoming, 'test'))->embed());
    }

    public function testAQueryAndAPageKeepToTheirBounds(): void
    {
        self::assertSame('bunny', (new VideoQuery(' bunny '))->text);
        foreach ([[' '], ['bunny', null, null, 0], ['bunny', null, null, 51]] as $arguments) {
            try {
                new VideoQuery(...$arguments);
                self::fail('Refused: '.json_encode($arguments));
            } catch (InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
        self::assertSame([1, 50, 20], [Page::limit(0, 50), Page::limit(80, 50), Page::limit(20, 50)]);
        self::assertCount(2, new Page(['a', 'b'], 'next'));
    }

    public function testAWorkLinksToItsVideosHostedElsewhere(): void
    {
        $work = new Work(WorkType::Movie, 'The Matrix', Identifiers::of(['tmdb' => 603]), 'test', videos: [
            new VideoLink(VideoRef::youtube('m8e-FF8MsqU'), 'Teaser', 'teaser', 'en', true),
            new VideoLink(VideoRef::vimeo(1234567), 'Trailer (fan)', 'trailer', 'en', false),
            new VideoLink(VideoRef::youtube('vKQi3bBA1y8'), 'Official Trailer', 'trailer', 'en', true, new \DateTimeImmutable('2021-03-01T12:00:00Z')),
        ]);

        self::assertSame('youtube:vKQi3bBA1y8', (string) $work->trailer()?->ref);
        self::assertSame('vimeo:1234567', (string) $work->trailer(Platform::Vimeo)?->ref);
        self::assertNull((new Work(WorkType::Movie, 'x', Identifiers::none(), 'test'))->trailer());
        self::assertSame(['platform' => 'youtube', 'id' => 'vKQi3bBA1y8', 'name' => 'Official Trailer', 'kind' => 'trailer', 'language' => 'en', 'official' => true, 'published' => '2021-03-01T12:00:00+00:00'], Data::of($work->toArray())->list('videos')[2]->toArray());
        self::assertSame('movie:tmdb:603', (string) WorkRef::tmdb(603));
    }

    public function testCapabilitiesSayTheirFamiliesAndThePlatformsHosted(): void
    {
        $youtube = new Capabilities(video: true, channels: true, playlists: true, live: true, platforms: [Platform::YouTube]);

        self::assertSame(['video', 'live'], $youtube->families());
        self::assertSame(['video', 'channels', 'playlists', 'live'], $youtube->contracts());
        self::assertTrue($youtube->hosts(VideoRef::youtube('aqz-KE-bpKQ')));
        self::assertFalse($youtube->hosts(VideoRef::vimeo(1)));
        self::assertFalse((new Capabilities(platforms: [Platform::YouTube]))->hosts(VideoRef::youtube('aqz-KE-bpKQ')), 'no video contract, nothing hosted');
        self::assertSame(['work'], (new Capabilities(search: true))->families());
        self::assertSame(['youtube'], $youtube->toArray()['platforms']);
    }

    public function testDataReadsMomentsAndDurations(): void
    {
        $data = new Data(['at' => '2009-10-25T06:57:33Z', 'paris' => '2026-11-09T20:00:00+01:00', 'day' => '2009-10-25', 'd1' => 'PT3M33S', 'd2' => 'PT1H2M', 'd3' => 'P1DT2H', 'd4' => 'P0D', 'd5' => 635, 'd6' => 'PT', 'd7' => 'soon', 'd8' => 'PT10.5S']);

        self::assertSame('2009-10-25T06:57:33+00:00', $data->dateTime('at')?->format(\DATE_ATOM));
        self::assertSame('2026-11-09T19:00:00+00:00', $data->dateTime('paris')?->format(\DATE_ATOM));
        self::assertNull($data->dateTime('day'));
        self::assertSame([213, 3720, 93600, 0, 635, null, null, 10], [$data->duration('d1'), $data->duration('d2'), $data->duration('d3'), $data->duration('d4'), $data->duration('d5'), $data->duration('d6'), $data->duration('d7'), $data->duration('d8')]);
    }
}
