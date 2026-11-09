---
title: Videos
order: 36
---

# Videos

The video family reads a video hosted elsewhere - at YouTube, at Vimeo - and gives the same `Video`
whatever the platform: its title, description, duration in seconds, thumbnails, the date published,
its channel, its page, its player and the player's privacy-friendly variant.

```php
use Omnishow\Model\VideoQuery;
use Omnishow\Model\VideoRef;

$video = $registry->video('youtube')->video(VideoRef::youtube('aqz-KE-bpKQ'));
$same = $registry->findVideo(VideoRef::fromUrl('https://youtu.be/aqz-KE-bpKQ'));   // the first provider that hosts YouTube

echo $video->title, ' (', intdiv((int) $video->duration, 60), ' min)';
echo $video->embed(private: true);    // https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ
echo $video->thumbnail(480)?->url;     // the first thumbnail at least 480 pixels wide
```

## The player

`embedUrl` is the platform's player, `privateEmbedUrl` its privacy-friendly variant where the
platform documents one: YouTube's privacy-enhanced mode (`www.youtube-nocookie.com`), Vimeo's
`dnt=1` (the player collects no session data nor analytics). Twitch has none, and its player needs
the embedding page's domain: `$video->embed(parent: 'example.org')`. A consent banner decides which
to use; the family only gives both. Each platform's terms say how a player must be shown (YouTube:
the page's Referer sent, at least 200x200 pixels).

## Several videos, a search

```php
$videos = $registry->video('youtube')->videos([VideoRef::youtube('aqz-KE-bpKQ'), VideoRef::youtube('vKQi3bBA1y8')]);
$page = $registry->videoSearch('youtube')->searchVideos(new VideoQuery('big buck bunny', limit: 10));
$more = $registry->videoSearch('youtube')->searchVideos(new VideoQuery('big buck bunny', limit: 10, page: $page->next));
```

`videos()` asks as few calls as the platform allows and keeps the order given; a video the platform
no longer shows is left out. A search answers short records (YouTube's: no duration, no views):
ask `videos()` for the whole ones.

## A channel, its playlists

```php
$channel = $registry->channels('youtube')->channel('@example');      // by @handle or UC... at YouTube, by number at Vimeo
$latest = $registry->channels('youtube')->channelVideos('@example', limit: 12);
$lists = $registry->playlists('youtube')->playlists($channel->id);
$first = $registry->playlists('youtube')->playlistVideos($lists->items[0]->id);
```

Every list is a `Page`: its `items`, and `next`, the token to give back for the next page (null on
the last). The token is the platform's own - YouTube's `pageToken`, Vimeo's page number.

## A work's trailer

A work provider names the videos of a work without reading them: TMDB's trailers are `VideoLink`s
in `Work::$videos`. The video comes from the provider of its platform:

```php
$matrix = $registry->lookup('tmdb')->work(WorkRef::tmdb(603));
$trailer = $matrix->trailer();                          // the official one, else the first
$video = null === $trailer ? null : $registry->findVideo($trailer->ref);
```

## Caching

Nothing is stored. YouTube counts every call against the project's daily quota (a search against
100 a day by default): an application caches what it shows.
