<?php

namespace Omnishow\Model;

/**
 * A video of a work, hosted elsewhere: TMDB's trailers and clips name a
 * YouTube or a Vimeo video. The link is all a work's provider gives; the
 * video's record is the platform's provider's (Registry::findVideo()).
 */
final readonly class VideoLink
{
    /**
     * @param ?string $kind     as the provider names it, in lower case: "trailer", "teaser", "clip", "featurette"...
     * @param ?string $language ISO 639-1
     */
    public function __construct(
        public VideoRef $ref,
        public ?string $name = null,
        public ?string $kind = null,
        public ?string $language = null,
        public ?bool $official = null,
        public ?\DateTimeImmutable $published = null,
    ) {
    }

    /** @return array{platform: string, id: string, name: ?string, kind: ?string, language: ?string, official: ?bool, published: ?string} */
    public function toArray(): array
    {
        return ['platform' => $this->ref->platform->value, 'id' => $this->ref->id, 'name' => $this->name, 'kind' => $this->kind, 'language' => $this->language, 'official' => $this->official, 'published' => $this->published?->format(\DATE_ATOM)];
    }
}
