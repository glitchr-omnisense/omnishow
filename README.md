# omnisense/omnishow

What is shown, under any PHP host, whoever keeps it - in three families of contracts:

- **work**: the catalogue record of a film, a series or an episode - titles, dates, cast and crew,
  pictures, identifiers across registers (IMDb, TMDB, TheTVDB, Trakt, Wikidata), a season's
  episodes, where to watch, and the trailers it links to;
- **video**: a video hosted elsewhere - its title, description, duration, thumbnails, date, channel,
  its player and the player's privacy-friendly variant (youtube-nocookie, Vimeo's `dnt=1`), a
  channel's videos, playlists, a search where the platform allows one;
- **live**: a live stream - whether a channel is on air, its stream, its schedule.

One set of models and contracts; each source is a package of its own and implements the contracts it
can fulfil.

```php
use Omnishow\Model\{Query, VideoRef, WorkRef, WorkType};
use Omnishow\Registry;
use Omnishow\Tmdb\TmdbFactory;
use Omnishow\Twitch\TwitchFactory;
use Omnishow\Wikidata\WikidataFactory;
use Omnishow\Youtube\YoutubeFactory;
use Symfony\Component\HttpClient\HttpClient;

$http = HttpClient::create();
$shows = new Registry([new TmdbFactory($http), new WikidataFactory($http), new YoutubeFactory($http), new TwitchFactory($http)], [
    'open' => ['factory' => 'wikidata', 'options' => ['user_agent' => 'MySite/1.0 (https://example.org; films@example.org)']],
    'tmdb' => ['factory' => 'tmdb', 'options' => ['token' => getenv('TMDB_TOKEN')]],
    'youtube' => ['factory' => 'youtube', 'options' => ['key' => getenv('YOUTUBE_KEY')]],
    'twitch' => ['factory' => 'twitch', 'options' => ['client_id' => getenv('TWITCH_CLIENT_ID'), 'client_secret' => getenv('TWITCH_CLIENT_SECRET')]],
]);

// work
$stalker = $shows->lookup('open')->work(WorkRef::wikidata('Q200437'), 'fr');
$matrix = $shows->lookup('tmdb')->work(WorkRef::tmdb(603), 'fr');
$episodes = $shows->episodes('tmdb')->episodes(WorkRef::tmdb(1920, WorkType::Series), 1);

// video: the work's trailer, read from the platform that hosts it
$trailer = $shows->findVideo($matrix->trailer()->ref);
echo $trailer->embed(private: true);                 // https://www.youtube-nocookie.com/embed/...
$uploads = $shows->channels('youtube')->channelVideos('@GoogleDevelopers');
$clip = $shows->findVideo(VideoRef::fromUrl('https://youtu.be/aqz-KE-bpKQ'));

// live
$now = $shows->live('twitch')->live('twitchdev');    // null when off air
echo $now?->embed(parent: 'example.org');
$next = $shows->live('twitch')->schedule('twitchdev');
```

Plain PHP: no container, no framework. The core requires `omnisense/contracts` (the kernel every
omnisense family builds on: provider, factory, registry, JSON client, usage policies, testing kit)
and `symfony/http-client-contracts` (interfaces); a provider requires `symfony/http-client` and
takes the application's client.

## Install and a first call

```sh
composer require omnisense/omnishow omnishow/wikidata
```

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Omnishow\Model\WorkRef;
use Omnishow\Registry;
use Omnishow\Wikidata\WikidataFactory;

$shows = new Registry([new WikidataFactory()], [
    // Wikimedia asks every client to say who it is: a name and a contact.
    'open' => ['factory' => 'wikidata', 'options' => ['user_agent' => 'MySite/1.0 (https://example.org; films@example.org)']],
]);
$stalker = $shows->lookup('open')->work(WorkRef::imdb('tt0079944'), 'en');
echo $stalker->title, ' (', $stalker->year, ') ', $stalker->ref(\Omnishow\Model\Scheme::Wikidata), "\n";
// Stalker (1979) movie:wikidata:Q200437
```

Wikidata needs no account and no key: this call reaches the real service. Every other provider
needs an account (the table under [Providers](#providers) says which key); with none, the core's
`Testing\FixedProvider` answers from records given in advance
([docs/installation.md](docs/installation.md) has that script, no network) and
`Testing\RecordedHttpClient` replays recorded answers.

## Usage policies

Each provider declares its usage policy as data (its `policy.json`: rate limits, rights,
attribution, requirements, where and when it was read), and every call through the registry's
contracts (`search()`, `lookup()`, `episodes()`, `availability()`, `video()`, `videoSearch()`,
`channels()`, `playlists()`, `live()`, and `work()`, `findVideo()` through them) is held to it:
checked before, counted after, refused with `UsageLimitReachedException` once a limit is reached or
while the provider asks to wait (a 429 and its `Retry-After`, a `RateLimit` field with nothing
left). A site may tighten a policy in its configuration, never loosen it:

```php
'videos' => [
    'factory' => 'youtube',
    'options' => ['key' => '...'],
    'policy' => ['rateLimits' => [['max' => 50, 'window' => 'day', 'operations' => ['searchVideos', 'live', 'schedule']]]],
    'usage' => ['commercial' => false],
],
```

`$shows->policy('videos')` gives the policy as the site runs it, `policies()` every one (for a
page of the data's terms). Pass a shared `RateLimiterInterface` as the registry's third argument
when the application runs in many processes. See [docs/policies.md](docs/policies.md).

## The shape

- **Work models** (`Omnishow\Model`): `Work` (type, titles, year and date, runtime, overview,
  genres, countries, languages, `Identifiers`, `Image`s, `Credit`s, `Rating`s, page, the
  `VideoLink`s to its trailers and clips; an episode's series, season and number), `WorkRef` (a
  type, a `Scheme`, an identifier), `Query`, `Offer`.
- **Video models**: `Video` (a `VideoRef`, title, description, duration in seconds, thumbnails, the
  date published, its `Channel`, page, player and privacy-friendly player, embeddable, tags, views,
  the broadcast it is), `VideoRef` (a `Platform` and an identifier; `fromUrl()` reads YouTube, Vimeo
  and Twitch addresses), `Channel`, `Playlist`, `VideoQuery`, `Page` (items and the provider's
  token for the next page).
- **Live models**: `Stream` (state - `LiveState` upcoming, live, ended, cancelled -, channel,
  title, the video it is where the platform makes it one, scheduled and actual times, viewers,
  category, thumbnail, page and player).
- What a provider does not give stays empty: nothing is guessed. See [docs/models.md](docs/models.md).
- **Contracts by family**:
  - work: `SearchInterface`, `LookupInterface`, `EpisodesInterface`, `AvailabilityInterface`;
  - video: `VideoInterface`, `VideoSearchInterface`, `ChannelInterface`, `PlaylistInterface`;
  - live: `LiveInterface` (`live()`, `schedule()`).

  `ProviderInterface::capabilities()` says which a provider has (`contracts()`, `families()`),
  which registers it finds a work by (`resolves()`), which platforms' videos it gives (`hosts()`),
  what its terms ask to be shown (`attribution`) and whether it needs a key.
- **Registry, Config, ProviderFactory** as in the other omni families: providers by name, each
  built once from its factory and options; `Registry::work()` asks, in order, each provider that
  resolves the register, `Registry::findVideo()` each provider that hosts the platform.
- **Http\JsonClient** and **Http\Data**: a provider's JSON calls (a 404 is "not known", an error
  status names the path and never the query where a key sits) and the answers read by key without
  trusting their shape (dates, moments, ISO 8601 durations).
- **Testing**: `FixedProvider` (works given in advance, no network), `FixedVideoProvider` (videos,
  channels, playlists and streams given in advance), `RecordedHttpClient`, `BareTestCase` (the
  check every package of the family runs).

## Providers

| Package | Family | Contracts | Key |
|---|---|---|---|
| [omnishow/tmdb](https://github.com/glitchr-omnisense/omnishow-tmdb) (over php-tmdb/api) | work | search; lookup by tmdb, imdb, tvdb, wikidata; episodes; where to watch (JustWatch's data); its works' trailers and clips as `VideoLink`s to YouTube and Vimeo | token or API key |
| [omnishow/omdb](https://github.com/glitchr-omnisense/omnishow-omdb) | work | search; lookup by imdb; episodes | API key |
| [omnishow/tvdb](https://github.com/glitchr-omnisense/omnishow-tvdb) | work | search; lookup by tvdb, imdb, tmdb; episodes | project key (+ PIN) |
| [omnishow/trakt](https://github.com/glitchr-omnisense/omnishow-trakt) | work | search; lookup by trakt, imdb, tmdb, tvdb; episodes | client id |
| [omnishow/wikidata](https://github.com/glitchr-omnisense/omnishow-wikidata) | work | search; lookup by wikidata, imdb, tmdb, tvdb | none |
| [omnishow/youtube](https://github.com/glitchr-omnisense/omnishow-youtube) | video, live | video; search; channels (by id or @handle); playlists; live (search's `eventType=live`); schedule (upcoming broadcasts) | API key |
| [omnishow/vimeo](https://github.com/glitchr-omnisense/omnishow-vimeo) | video | video; search; channels (a user's videos); playlists (showcases) | access token, or client id and secret |
| [omnishow/twitch](https://github.com/glitchr-omnisense/omnishow-twitch) | live | live; schedule | client id and secret (app token), or client id and token |

A provider of one family links to another through the models, never by duplicating a provider:
TMDB's trailers are `VideoLink`s (`Work::$videos`, `Work::trailer()`), whose record comes from the
provider of their platform (`Registry::findVideo()`); a YouTube broadcast is a `Video` whose
`$stream` says its state.

Next, when a site needs them: dailymotion, peertube (video); YouTube's live chat and Vimeo's live
events need the owner's OAuth and are left out. The Symfony and Laravel wiring will live in
omnibridge when a site needs it.

## Documentation

The manual, in `docs/`:

- [Overview and providers](docs/index.md)
- [Installation, in plain PHP](docs/installation.md)
- [Symfony](docs/symfony.md) and [Laravel](docs/laravel.md)
- [Models](docs/models.md): contracts, capabilities, the registry, every model and enum
- Usage: [search](docs/usage-search.md), [records and identifiers](docs/usage-lookup.md), [episodes](docs/usage-episodes.md), [where to watch](docs/usage-availability.md), [videos](docs/usage-video.md), [live](docs/usage-live.md)
- [Configuration reference](docs/configuration.md)
- [Usage policies](docs/policies.md): each provider's, the guard, a site's tightening
- [Errors](docs/errors.md)
- [Testing](docs/testing.md)
- [The harness](docs/harness.md)
- [Writing a provider](docs/providers.md)
- [Verified, and not](docs/verified.md)

## Verified, and not

- Verified (the harness, PHP 8.4, 2026-11-09): the core's tests and the bare script (a registry of a
  work provider and a video provider: a search, a lookup, episodes, offers, a work's trailer read
  from the video provider, a channel's videos, a playlist, what is live and scheduled, a JSON call
  answered, a 404, an error without its key) in a PHP process that loads nothing but the package
  and the HTTP client contracts (`BareTest`); every provider's tests and `BareTest`; PHPStan at its
  highest level on every package, the baseline empty.
- Verified live: Wikidata (no key) - a search, a film by its IMDb number, a series by its TMDB
  number (`docker compose run --rm omnishow live`), on 2026-10-26 under omnifilm.
- Not verified live: TMDB, OMDb, TheTVDB, Trakt, YouTube, Vimeo, Twitch - no account was used.
  Their tests run on answers shaped after each API's reference, written by hand, and record the
  exact requests; the first site that has a key runs them once against the real service.

## History

`omnisense/omnishow` continues `omnisense/omnifilm` (and its `omnifilm/*` providers), merged into
it on 2026-11-09: omnifilm's contracts are the work family, its namespace `Omnifilm\` is
`Omnishow\`. The `omnishow/youtube` package name, held since 2026-10-05, is its first video and live
provider.

## Responsibility

`omnisense/omnishow` and its `omnishow/*` providers are independent, unofficial clients: none is
affiliated with, endorsed by or sponsored by the services it reaches. Each service keeps its own
terms (accounts and keys, rate limits and quotas, commercial or non-commercial use, caching,
attribution, how an embedded player must be shown), named with a link in the README of each
provider. The MIT licence covers this code only and grants no right to any service or to its data.

Whoever installs and runs these packages is responsible for complying with those terms, for
obtaining whatever they require, and for any use made of what they return. The authors publish them
free of charge, make no commercial use of them, and accept no liability for how they are used (see
[LICENSE](LICENSE)).

## Licence

MIT.
