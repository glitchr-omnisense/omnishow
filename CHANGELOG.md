# Changelog

## Unreleased (1.x)

### Added
- On omnisense/contracts, the kernel every family builds on (2026-11-10). Config, ProviderInterface,
  ProviderFactoryInterface, ProviderFactory (populate() and build() as before), Registry,
  Http\JsonClient, Data, Redaction, Testing\RecordedHttpClient, RecordedResponse and BareTestCase
  keep their names and their API, each a thin subclass of the kernel's (`bin/load-all.php` moved
  there); JsonClient gains `bytes()` and takes the provider's Backoff. Usage policies:
  `Capabilities::policy()` (given, or made of `$attribution`, `$terms` and `$keyless`, now
  deprecated), every call through `search()`, `lookup()`, `episodes()`, `availability()`, `video()`,
  `videoSearch()`, `channels()`, `playlists()` and `live()` guarded (`Policy\Guarded*`), `work()`
  and `findVideo()` through them; a site's configuration tightening a policy (`policy`, `usage`),
  `Registry::policy()`, `policies()`, `guard()`, `names()` by contract, a shared limiter as the
  registry's third argument, `Exception\UsageLimitReachedException`. The harness installs
  omnisense/contracts from its checkout (OMNISENSE_CONTRACTS) and runs its tests too.
  docs/policies.md.

### Changed
- `omnisense/omnishow` continues `omnisense/omnifilm`, merged into it on 2026-11-09: the namespace
  `Omnifilm\` is `Omnishow\` (`OmnifilmException` is `OmnishowException`), the configuration keys
  `omnishow.*`, the providers `omnishow/<provider>`. omnifilm's contracts are the work family.
- The registry's messages name a provider, no longer a "film provider": `No "x" provider;
  configured: ...`.

### Added
- The video family: `VideoInterface` (a video's record, several in one call),
  `VideoSearchInterface`, `ChannelInterface` (a channel, its videos), `PlaylistInterface` (a
  channel's playlists, a playlist's videos); the models `Video`, `VideoRef` (with `fromUrl()`),
  `Platform` (YouTube, Vimeo, Twitch: page, player, privacy-friendly player), `Channel`,
  `Playlist`, `VideoQuery`, `Page`; `ImageKind::Thumbnail`.
- The live family: `LiveInterface` (`live()`, `schedule()`), the models `Stream` and `LiveState`.
- `Work::$videos` (`VideoLink`s to the trailers and clips a work provider names) and
  `Work::trailer()`; `Registry::findVideo()` reads a video from the provider that hosts its
  platform.
- `Capabilities`: `video`, `videoSearch`, `channels`, `playlists`, `live`, `schedule`,
  `platforms`, `hosts()`, `families()`; `Registry::video()`, `videoSearch()`, `channels()`,
  `playlists()`, `live()`; `NotSupportedException::video()`.
- `Http\Data::dateTime()` (an RFC 3339 moment, in UTC) and `Http\Data::duration()` (ISO 8601
  durations, or seconds).
- `Testing\FixedVideoProvider` and its factory; the bare script and `BareTest` read a work's
  trailer, a channel, a playlist and what is live from it.

### Fixed
- No exception `JsonClient::request()` throws keeps its trace's arguments: an error answered (a refused key,
  nothing there, a server error, a body that is not JSON) kept the request's options - the query,
  headers and token with the key - in its trace (`zend.exception_ignore_args` off), as the
  `UnreachableException` did before its fix.
- An `UnreachableException`'s chain no longer holds the key. Its previous exception was the HTTP
  client's own, whose message quotes the whole URL (OMDb's `apikey=`, TMDB's `api_key=`), and
  every trace kept the arguments of the calls under it, the request's query and headers among
  them. The previous is now a copy of the client's exception (`Http\Redaction::copy()`: the same
  class and code, the key's parameters, the URL's user part and the request's secrets masked as
  `***`), and no trace of the chain keeps its arguments (`Redaction::withoutArguments()`).
- An `UnreachableException` no longer quotes the URL called: as its docblock promised, the message
  names the method and the path, then the HTTP client's reason without the address it quotes.
  Symfony's transport messages end with the whole URL, so OMDb's `apikey=` and TMDB's `api_key=`
  stood in the message.

### Added
- `Omnishow\Http\Redaction`: a request's secrets (`secrets()`), a text without them (`text()`),
  a failure's copy to wrap in its place (`copy()`), a trace without arguments
  (`withoutArguments()`).
- `ext-mbstring` in `composer.json`'s requirements: `ProviderException` shortens error bodies with
  `mb_substr()`, which a PHP built without mbstring did not have (no test: a requirement).
- `Omnishow\Http\JsonClient::reason()`: an HTTP client's message without the addresses it quotes.
