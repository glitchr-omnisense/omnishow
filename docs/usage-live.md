---
title: Live
order: 37
---

# Live

The live family says whether a channel is on air, gives its stream, and reads its schedule:

```php
$now = $registry->live('twitch')->live('twitchdev');          // ?Stream: null when off air
if (null !== $now) {
    echo $now->title, ' - ', $now->viewers, ' watching since ', $now->started?->format('H:i');
    echo $now->embed(parent: 'example.org');                   // Twitch's player needs the page's domain
}

foreach ($registry->live('youtube')->schedule('@example', 5) as $next) {
    echo $next->scheduled?->format('D j M H:i'), ' ', $next->title;
}
```

## The stream

A `Stream` has a state (`LiveState::Live`, `Upcoming`, `Ended`, `Cancelled`), its channel, title,
scheduled and actual times, viewers where the platform shows them, its category (Twitch's game),
thumbnail, page and player.

- At **YouTube** a broadcast is a video: `Stream::$video` names it, and `Video::$stream` carries the
  same state on the video's record. `live()` and `schedule()` find the channel's broadcasts through
  a search (`eventType=live` or `upcoming`), each one search of the project's daily 100:
  cache the answer. The owner's own broadcasts (`liveBroadcasts.list`) need OAuth and are not
  read.
- At **Twitch** the stream is the channel's: its player is the channel's
  (`https://player.twitch.tv/?channel=<login>`), and a scheduled segment has no stream yet. A
  cancelled segment stays in the schedule with `LiveState::Cancelled`; a weekly one is
  `recurring`.

## Which provider

| Provider | `live()` | `schedule()` |
|---|---|---|
| omnishow/youtube | a search `eventType=live`, then `liveStreamingDetails` | a search `eventType=upcoming`, then `scheduledStartTime` |
| omnishow/twitch | `GET /streams?user_login=` | `GET /schedule?broadcaster_id=` (a broadcaster without a schedule: empty) |

Vimeo's live events need the owner's token: omnishow/vimeo has no live contract.
