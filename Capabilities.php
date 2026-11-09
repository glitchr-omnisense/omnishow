<?php

namespace Omnishow;

use Omnishow\Model\Platform;
use Omnishow\Model\Scheme;
use Omnishow\Model\VideoRef;
use Omnishow\Model\WorkRef;
use Omnishow\Model\WorkType;

/**
 * What a provider is, said before it is asked anything: what it can do, the
 * registers it finds a work by, the platforms whose videos it gives, and
 * what its terms ask of whoever shows its records.
 *
 * Its contracts fall in three families (families()): work - a film's, a
 * series' or an episode's record (search, lookup, episodes, availability);
 * video - a video hosted elsewhere (video, videoSearch, channels,
 * playlists); live - a live stream (live, and its schedule).
 */
final readonly class Capabilities
{
    /**
     * @param bool                      $search       it searches by title (SearchInterface)
     * @param bool                      $lookup       it gives a work's record by an identifier (LookupInterface)
     * @param bool                      $episodes     it lists a season's episodes (EpisodesInterface)
     * @param bool                      $availability it says where to watch (AvailabilityInterface)
     * @param list<WorkType>            $types        the types it knows
     * @param array<string, list<Scheme>> $lookupBy   per type value, the registers it finds a work by
     * @param bool                      $images       its records carry pictures
     * @param bool                      $credits      its records carry the cast and crew
     * @param ?string                   $attribution  what its terms ask to be shown beside its records
     * @param list<string>              $terms        the terms whoever runs it must follow (URLs)
     * @param bool                      $keyless      it needs no account nor key
     * @param bool                      $video        it gives a video's record (VideoInterface)
     * @param bool                      $videoSearch  it searches videos (VideoSearchInterface)
     * @param bool                      $channels     it gives a channel and its videos (ChannelInterface)
     * @param bool                      $playlists    it gives playlists and their videos (PlaylistInterface)
     * @param bool                      $live         it says whether a channel is live, and its stream (LiveInterface)
     * @param bool                      $schedule     LiveInterface::schedule() reads a schedule the platform keeps
     * @param list<Platform>            $platforms    the platforms whose videos and streams it gives
     */
    public function __construct(
        public bool $search = false,
        public bool $lookup = false,
        public bool $episodes = false,
        public bool $availability = false,
        public array $types = [],
        public array $lookupBy = [],
        public bool $images = false,
        public bool $credits = false,
        public ?string $attribution = null,
        public array $terms = [],
        public bool $keyless = false,
        public bool $video = false,
        public bool $videoSearch = false,
        public bool $channels = false,
        public bool $playlists = false,
        public bool $live = false,
        public bool $schedule = false,
        public array $platforms = [],
    ) {
    }

    /** Whether LookupInterface::work() finds this work by this register. */
    public function resolves(WorkRef $ref): bool
    {
        return $this->lookup && \in_array($ref->scheme, $this->lookupBy[$ref->type->value] ?? [], true);
    }

    /** Whether VideoInterface::video() gives this video's record: its platform is one of the provider's. */
    public function hosts(VideoRef $ref): bool
    {
        return $this->video && \in_array($ref->platform, $this->platforms, true);
    }

    public function knows(WorkType $type): bool
    {
        return \in_array($type, $this->types, true);
    }

    /** @return list<string> what it can be asked, by contract: search, lookup, episodes, availability, video, videoSearch, channels, playlists, live */
    public function contracts(): array
    {
        return array_keys(array_filter([
            'search' => $this->search, 'lookup' => $this->lookup, 'episodes' => $this->episodes, 'availability' => $this->availability,
            'video' => $this->video, 'videoSearch' => $this->videoSearch, 'channels' => $this->channels, 'playlists' => $this->playlists,
            'live' => $this->live,
        ]));
    }

    /** @return list<string> the families of its contracts: work, video, live */
    public function families(): array
    {
        return array_keys(array_filter([
            'work' => $this->search || $this->lookup || $this->episodes || $this->availability,
            'video' => $this->video || $this->videoSearch || $this->channels || $this->playlists,
            'live' => $this->live,
        ]));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'families' => $this->families(),
            'contracts' => $this->contracts(),
            'types' => array_map(static fn (WorkType $type) => $type->value, $this->types),
            'lookupBy' => array_map(static fn (array $schemes) => array_map(static fn (Scheme $scheme) => $scheme->value, $schemes), $this->lookupBy),
            'images' => $this->images,
            'credits' => $this->credits,
            'attribution' => $this->attribution,
            'terms' => $this->terms,
            'keyless' => $this->keyless,
            'platforms' => array_map(static fn (Platform $platform) => $platform->value, $this->platforms),
            'schedule' => $this->schedule,
        ];
    }
}
