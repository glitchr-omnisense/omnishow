<?php

namespace Omnishow\Model;

/**
 * A channel at a video platform: a YouTube channel, a Vimeo user, a Twitch
 * broadcaster. A video's record carries a short one (its identifier and
 * name); ChannelInterface::channel() gives the whole of it.
 */
final readonly class Channel
{
    /**
     * @param string      $id          the platform's identifier ("UC..." at YouTube, a number at Vimeo and Twitch)
     * @param ?string     $handle      the name it is reached by ("@handle" at YouTube, the login at Twitch)
     * @param list<Image> $thumbnails  its pictures, smallest first
     * @param ?int        $subscribers where the platform shows it (YouTube rounds it to three significant figures, or hides it)
     * @param ?int        $videos      its public videos
     */
    public function __construct(
        public Platform $platform,
        public string $id,
        public string $title,
        public ?string $handle = null,
        public ?string $description = null,
        public array $thumbnails = [],
        public ?string $url = null,
        public ?\DateTimeImmutable $published = null,
        public ?string $country = null,
        public ?int $subscribers = null,
        public ?int $videos = null,
        public ?string $source = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'platform' => $this->platform->value,
            'id' => $this->id,
            'title' => $this->title,
            'handle' => $this->handle,
            'description' => $this->description,
            'thumbnails' => array_map(static fn (Image $image) => $image->toArray(), $this->thumbnails),
            'url' => $this->url,
            'published' => $this->published?->format(\DATE_ATOM),
            'country' => $this->country,
            'subscribers' => $this->subscribers,
            'videos' => $this->videos,
            'source' => $this->source,
        ];
    }
}
