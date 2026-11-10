---
title: Usage policies
order: 45
---

# Usage policies

Every omnishow provider carries a usage policy: omnisense/contracts' `UsagePolicy`
([its schema and rules](https://github.com/glitchr-omnisense/contracts/blob/1.x/docs/policies.md)).
Each provider package declares its own in a `policy.json` beside its classes (named in its
`composer.json`, `extra.omnisense.policy`), read at its sources on the day its `verifiedAt` says.
What the terms do not say is declared unknown (`rateLimits: null`, a right `unknown`), never
guessed.

| Provider | Rate limits (as declared) | Rights, in short |
|---|---|---|
| omnishow/omdb | 1,000 a day per key | commercial use, storage and redistribution forbidden; CC BY-NC 4.0, credited |
| omnishow/tmdb | 40 a second ("in the 40 requests per second range") | commercial use under a written agreement; cached 6 months at most; TMDB's credit and logo, JustWatch's for where to watch |
| omnishow/trakt | 500 every 5 minutes (`"every": 5`) | commercial use free; no redistribution as a feed; the credit on an About or credits page; a User-Agent with a version |
| omnishow/tvdb | unknown (none published) | free with the credit under US$50,000 of revenue, licences above (contract); no redistribution; images not covered |
| omnishow/wikidata | 200 a minute (Wikimedia's 2026 limit for a client with a compliant User-Agent), one request at a time (`"concurrency": 1`) | CC0; Commons pictures under their own licences; a User-Agent with a name, a version and a contact |
| omnishow/youtube | 100 a day on `searchVideos`, `live`, `schedule` (search.list's own bucket); 10,000 units a day, each operation costing the units of the methods it makes | data kept 30 days at most; no redistribution; YouTube shown as the source |
| omnishow/vimeo | unknown (the account's plan: 25 to 2,500 a minute) | charging users needs Vimeo's written authorisation; no datasets, no AI training |
| omnishow/twitch | unknown (the points bucket's size is not stated as policy); `Ratelimit-Remaining` and `Ratelimit-Reset` (a Unix time) read | cached 24 hours at most; no redistribution nor monetizing; a clear path to the source |

Each `policy.json`'s `provenance.notes` holds the details and the pages read.

## Where calls are checked

`Registry::search()`, `lookup()`, `episodes()`, `availability()`, `video()`, `videoSearch()`,
`channels()`, `playlists()` and `live()` give the provider behind its guard
(`Omnishow\Policy\GuardedSearch`...). Each call is one operation, named after its method:
`search`, `work`, `episodes`, `offers`, `video`, `videos`, `searchVideos`, `channel`,
`channelVideos`, `playlist`, `playlists`, `playlistVideos`, `live`, `schedule`. A limit with
`operations` counts only those; one without counts every call. `Registry::work()` and
`findVideo()` ask each provider through its guard too. `Registry::get()` gives the provider itself,
unguarded.

A call over a limit is never made: `UsageLimitReachedException` (an `OmnishowException`), with
`retryAfter` in seconds and the `limit` reached. A provider's own refusal - a 429, a 503 with
`Retry-After`, a `RateLimit: "...";r=0;t=30` field - holds every following call of that provider
until the wait is over.

The guard counts operations, and a provider's terms count requests or units: a limit says its
`unit` (`requests` by default, `units`, `points`, `characters`...) and the `cost` of an operation
in it, 1 when not named (YouTube's `channelVideos`: channels.list, playlistItems.list, videos.list,
3 units). A window of several minutes is `"every"` (`{"max": 500, "window": "minute", "every":
5}`). Where only the call's arguments say what it consumes (YouTube's `videos()`, one unit per 50
videos), the provider implements `Policy\MeasuresUsageInterface` and the guard counts what its
`usage()` gives. `concurrency` says how many requests may be in flight at once: declared, not
enforced (a PHP process sends one at a time).

## A site's configuration

```php
$shows = new Registry($factories, [
    'videos' => [
        'factory' => 'youtube',
        'options' => ['key' => '...'],
        'policy' => [
            'rateLimits' => [['max' => 50, 'window' => 'day', 'operations' => ['searchVideos', 'live', 'schedule']]],
            'rights' => ['caching' => ['maxTtl' => 86400]],
        ],
        'usage' => ['commercial' => false],
    ],
    'vimeo' => [
        'factory' => 'vimeo',
        'options' => ['token' => '...'],
        // The account's plan (Standard: 250 calls a minute), which the provider cannot know.
        'policy' => ['rateLimits' => [['max' => 250, 'window' => 'minute', 'scope' => 'user']]],
    ],
], $limiter);
```

- `policy` tightens the provider's: lower limits are added, stricter rights replace the
  declared ones. A looser value (1,000 searches a day where YouTube says 100, a commercial use
  allowed where TMDB asks a contract) is refused with `InvalidPolicyException` when the provider is
  first asked through its contract.
- `usage` says what the site is: a commercial site on OMDb is refused at once
  (`UsageNotPermitted`).
- Both may sit in `options` too.
- `$limiter` (`RateLimiterInterface`): an in-memory one by default, which counts within one PHP
  process. A web application gives a shared one (omnibridge/symfony's, over symfony/rate-limiter
  and a cache pool), or the counts start over with every request.

`$shows->policy('videos')` and `$shows->policies()` give the policies as the site runs them, for a
page listing the data's terms. `$shows->guard('videos')->cacheTtl(3600)` gives the time to live the
terms allow, and `remaining('searchVideos')` the calls left.

## Older Capabilities

`Capabilities` keeps `$attribution`, `$terms` and `$keyless` (deprecated). A provider that gives
them alone gets a policy made of them, its limits unknown (`UsagePolicy::described()`). A provider
that gives a `policy:` gets them read from it when it does not give them.
