---
title: Verified, and not
order: 90
---

# Verified, and not

## What the tests prove

Run on 2026-10-26 in the [harness](harness.md), every package from its checkout, PHP 8.4,
PHPUnit 11.5: 53 tests, 325 assertions, green; PHPStan at its highest level on the six packages,
no error, the baselines empty.

- The core (`Tests/ModelTest.php`, `Tests/RegistryTest.php`, `Tests/JsonClientTest.php`):
  identifiers dropping empty values and merging, references, the query's limits, a work's refs,
  pictures and people, capabilities and `resolves()`; providers built once, a capability lacking,
  a missing option, an unknown provider or factory, `Registry::work()` asking the first provider
  that resolves the register; `Data` reading any shape; `JsonClient`'s answers, errors and
  unreachable providers.
- In plain PHP (`Tests/BareTest.php`): records read by hand loading nothing but Omnishow and what it
  requires.
- Each provider: its calls and its reading of the answers, not found as `null`, a refusal as an
  exception, and a `BareTest`.

| Provider | Answers its tests use |
|---|---|
| wikidata | recorded from Wikidata on 2026-10-24 (claims trimmed to the properties read, references dropped) |
| tmdb | shaped after TMDB's API reference, written by hand; through php-tmdb/api on a `MockHttpClient`, with the token and with the key |
| omdb | shaped after OMDb's documented example, written by hand |
| tvdb | shaped after TheTVDB's v4 reference, written by hand (the `sourceName` of remote identifiers is the reference's, not seen live) |
| trakt | shaped after Trakt's API reference, written by hand |

There is no conformance suite shared by the providers: each tests its own mapping.

## What was run live

Nothing of the video and live families: the first site with a key runs a video, a channel's
videos and `live()` once.


- `docker compose run --rm omnishow live` (`Omnishow\Wikidata\Tests\LiveTest`), 2026-10-24:
  Stalker found by a search narrowed to 1979, The Shawshank Redemption by its IMDb number (TMDB
  278, Frank Darabont), Game of Thrones by its TMDB series number (Q23572, tt0944947, TheTVDB
  121361, 2011).
- For this manual, 2026-10-26, 15:08-15:22 UTC, Wikidata again: the
  [Installation](installation.md#a-providers-api) script (a search, Stalker by its IMDb number in
  French: five calls) and the [registry's chain](usage-lookup.md#one-record-from-several-providers)
  (Game of Thrones by its TMDB number: three calls), then the Wikidata manual's example (five calls).

## What is not verified

- TMDB, OMDb, TheTVDB and Trakt were never called: no account was used. Their answers in the tests
  follow their published references; the first site with a key runs a search and a lookup once.
- YouTube, Vimeo and Twitch were never called (2026-11-09): no key, token or client id was used.
  Their answers follow the references read that day; the tests record the exact requests. Not
  settled by the references: the most ids `videos.list` takes (50 is used, not documented), whether
  Vimeo lists another user's showcase to a public token, the body of a spent YouTube quota.
- Wikidata's episodes are not implemented (its season qualifiers vary too much), nor people's IMDb
  numbers (one more call per person).
- The [Symfony](symfony.md) and [Laravel](laravel.md) pages were written from the classes'
  constructors, not run in a kernel or an application.

## Known defects

None left: those the manuals found were fixed on 2026-10-29.

## Responsibility

`omnisense/omnishow` and its `omnishow/*` providers are independent, unofficial clients: none is
affiliated with, endorsed by or sponsored by the services it reaches. Each service keeps its own
terms (accounts and keys, rate limits, commercial or non-commercial use, caching, attribution),
named with a link in the README and the manual of each provider. The MIT licence covers this code
only and grants no right to any service or to its data; whoever runs these packages complies with
those terms.
