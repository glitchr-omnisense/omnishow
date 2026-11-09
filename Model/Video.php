<?php

namespace Omnishow\Model;

/**
 * The record of a video hosted elsewhere, as its platform's provider gives
 * it: title, description, duration, thumbnails, the date it was published,
 * its channel, its page, its player and the player's privacy-friendly
 * variant. What the platform does not give is empty or null, never guessed;
 * the player's addresses default to the platform's (Platform::embedUrl()).
 */
final readonly class Video
{
    public string $url;

    public string $embedUrl;

    /** The privacy-friendly player (youtube-nocookie, Vimeo's dnt=1); null where the platform has none. */
    public ?string $privateEmbedUrl;

    /**
     * @param string       $source     the provider's name ("youtube", "vimeo"...)
     * @param ?int         $duration   seconds (YouTube's may differ from the real length by a second; 0 while a broadcast is live)
     * @param list<Image>  $thumbnails smallest first
     * @param ?bool        $embeddable whether its owner lets other sites play it (the platform may still refuse in some places)
     * @param list<string> $tags
     * @param ?string      $privacy    as the platform says it: "public", "unlisted"...
     * @param ?Stream      $stream     the broadcast it is or was (scheduled, live, ended), null for an upload
     * @param ?int         $position   its place in the playlist it was listed from, from 0
     */
    public function __construct(
        public VideoRef $ref,
        public string $title,
        public string $source,
        public ?string $description = null,
        public ?int $duration = null,
        public ?\DateTimeImmutable $published = null,
        public ?Channel $channel = null,
        public array $thumbnails = [],
        ?string $url = null,
        ?string $embedUrl = null,
        ?string $privateEmbedUrl = null,
        public ?bool $embeddable = null,
        public array $tags = [],
        public ?int $views = null,
        public ?string $language = null,
        public ?string $privacy = null,
        public ?Stream $stream = null,
        public ?int $position = null,
    ) {
        $this->url = $url ?? $ref->url();
        $this->embedUrl = $embedUrl ?? (string) $ref->embedUrl();
        $this->privateEmbedUrl = $privateEmbedUrl ?? $ref->platform->privateEmbedUrl($this->embedUrl);
    }

    /**
     * The player to put in a page: the privacy-friendly one when asked and
     * the platform has one (the plain one otherwise), with the embedding
     * page's domain where the platform needs it (Twitch: parent=).
     */
    public function embed(bool $private = false, ?string $parent = null): string
    {
        $url = $private ? ($this->privateEmbedUrl ?? $this->embedUrl) : $this->embedUrl;

        return self::withParent($this->ref->platform, $url, $parent);
    }

    public function isLive(): bool
    {
        return LiveState::Live === $this->stream?->state;
    }

    /** The largest thumbnail, or the first at least this wide. */
    public function thumbnail(?int $minWidth = null): ?Image
    {
        $thumbnails = $this->thumbnails;
        usort($thumbnails, static fn (Image $a, Image $b) => ($a->width ?? 0) <=> ($b->width ?? 0));
        foreach ($thumbnails as $image) {
            if (null !== $minWidth && ($image->width ?? 0) >= $minWidth) {
                return $image;
            }
        }

        return [] === $thumbnails ? null : $thumbnails[\count($thumbnails) - 1];
    }

    /** @internal a player URL with parent= where the platform needs it */
    public static function withParent(Platform $platform, string $url, ?string $parent): string
    {
        if (!$platform->needsParent() || null === $parent || '' === trim($parent)) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').'parent='.rawurlencode(trim($parent));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'platform' => $this->ref->platform->value,
            'id' => $this->ref->id,
            'title' => $this->title,
            'description' => $this->description,
            'duration' => $this->duration,
            'published' => $this->published?->format(\DATE_ATOM),
            'channel' => $this->channel?->toArray(),
            'thumbnails' => array_map(static fn (Image $image) => $image->toArray(), $this->thumbnails),
            'url' => $this->url,
            'embedUrl' => $this->embedUrl,
            'privateEmbedUrl' => $this->privateEmbedUrl,
            'embeddable' => $this->embeddable,
            'tags' => $this->tags,
            'views' => $this->views,
            'language' => $this->language,
            'privacy' => $this->privacy,
            'stream' => $this->stream?->toArray(),
            'position' => $this->position,
            'source' => $this->source,
        ];
    }
}
