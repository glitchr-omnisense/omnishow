<?php

namespace Omnishow;

/**
 * One source, configured: TMDB, Wikidata, YouTube, Twitch... Its
 * Capabilities say what it can be asked, by family - work: search by title
 * (SearchInterface), a work by an identifier (LookupInterface), a season's
 * episodes (EpisodesInterface), where to watch (AvailabilityInterface);
 * video: a video's record (VideoInterface), a search (VideoSearchInterface),
 * a channel's videos (ChannelInterface), playlists (PlaylistInterface);
 * live: a channel's stream and schedule (LiveInterface) - and what its
 * terms ask of whoever shows its records.
 */
interface ProviderInterface
{
    /** The factory's name: "tmdb", "wikidata", "youtube"... */
    public function getName(): string;

    public function getTitle(): string;

    public function capabilities(): Capabilities;
}
