<?php

namespace Omnishow\Model;

/** A list of videos a channel keeps: a YouTube playlist, a Vimeo showcase. */
final readonly class Playlist
{
    /**
     * @param list<Image> $thumbnails smallest first
     * @param ?int        $count      the videos it holds, as the platform counts them (private ones included at YouTube)
     */
    public function __construct(
        public Platform $platform,
        public string $id,
        public string $title,
        public string $source,
        public ?string $description = null,
        public ?Channel $channel = null,
        public array $thumbnails = [],
        public ?int $count = null,
        public ?string $url = null,
        public ?\DateTimeImmutable $published = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'platform' => $this->platform->value,
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'channel' => $this->channel?->toArray(),
            'thumbnails' => array_map(static fn (Image $image) => $image->toArray(), $this->thumbnails),
            'count' => $this->count,
            'url' => $this->url,
            'published' => $this->published?->format(\DATE_ATOM),
            'source' => $this->source,
        ];
    }
}
