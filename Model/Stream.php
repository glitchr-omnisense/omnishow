<?php

namespace Omnishow\Model;

/**
 * A live broadcast: on air now (LiveInterface::live()), or scheduled
 * (LiveInterface::schedule()), or over. At YouTube a broadcast is a video
 * ($video names it, its player plays it); at Twitch it is the channel's
 * player, and a scheduled segment has no stream yet.
 */
final readonly class Stream
{
    /**
     * @param ?string   $id          the platform's identifier of the stream or of the scheduled segment
     * @param ?VideoRef $video       the video the broadcast is, where the platform makes it one (YouTube)
     * @param ?int      $viewers     watching now, where the platform shows it
     * @param ?string   $category    what is played or shown, as the platform names it (Twitch's game or category)
     * @param ?string   $url         the page to watch it at
     * @param ?string   $embedUrl    the player (Twitch's still needs parent=, see embed())
     * @param bool      $recurring   a segment of a weekly schedule (Twitch)
     */
    public function __construct(
        public Platform $platform,
        public LiveState $state,
        public string $source,
        public ?Channel $channel = null,
        public ?string $title = null,
        public ?string $id = null,
        public ?VideoRef $video = null,
        public ?\DateTimeImmutable $scheduled = null,
        public ?\DateTimeImmutable $scheduledEnd = null,
        public ?\DateTimeImmutable $started = null,
        public ?\DateTimeImmutable $ended = null,
        public ?int $viewers = null,
        public ?string $category = null,
        public ?string $language = null,
        public ?Image $thumbnail = null,
        public ?string $url = null,
        public ?string $embedUrl = null,
        public ?string $privateEmbedUrl = null,
        public bool $recurring = false,
    ) {
    }

    /** The player, privacy-friendly when asked and the platform has one, with parent= where needed; null when there is none (a scheduled segment). */
    public function embed(bool $private = false, ?string $parent = null): ?string
    {
        $url = $private ? ($this->privateEmbedUrl ?? $this->embedUrl) : $this->embedUrl;

        return null === $url ? null : Video::withParent($this->platform, $url, $parent);
    }

    public function isLive(): bool
    {
        return LiveState::Live === $this->state;
    }

    /** When it starts or started: the actual start, else the scheduled one. */
    public function start(): ?\DateTimeImmutable
    {
        return $this->started ?? $this->scheduled;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'platform' => $this->platform->value,
            'state' => $this->state->value,
            'id' => $this->id,
            'title' => $this->title,
            'channel' => $this->channel?->toArray(),
            'video' => null === $this->video ? null : (string) $this->video,
            'scheduled' => $this->scheduled?->format(\DATE_ATOM),
            'scheduledEnd' => $this->scheduledEnd?->format(\DATE_ATOM),
            'started' => $this->started?->format(\DATE_ATOM),
            'ended' => $this->ended?->format(\DATE_ATOM),
            'viewers' => $this->viewers,
            'category' => $this->category,
            'language' => $this->language,
            'thumbnail' => $this->thumbnail?->toArray(),
            'url' => $this->url,
            'embedUrl' => $this->embedUrl,
            'privateEmbedUrl' => $this->privateEmbedUrl,
            'recurring' => $this->recurring,
            'source' => $this->source,
        ];
    }
}
