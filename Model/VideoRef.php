<?php

namespace Omnishow\Model;

use Omnishow\Exception\InvalidArgumentException;

/**
 * A video named by its platform and its identifier there:
 * VideoRef::youtube('aqz-KE-bpKQ'), VideoRef::vimeo(76979871), or read from
 * an address (VideoRef::fromUrl()). A work's trailer at TMDB is one
 * (VideoLink::$ref), which the provider of that platform gives the record of.
 */
final readonly class VideoRef implements \Stringable
{
    public string $id;

    public function __construct(public Platform $platform, string|int $id)
    {
        $id = trim((string) $id);
        if ('' === $id) {
            throw new InvalidArgumentException('A video is named by a non-empty identifier.');
        }
        $this->id = $id;
    }

    public static function youtube(string $id): self
    {
        return new self(Platform::YouTube, $id);
    }

    public static function vimeo(string|int $id): self
    {
        return new self(Platform::Vimeo, $id);
    }

    public static function twitch(string|int $id): self
    {
        return new self(Platform::Twitch, ltrim(trim((string) $id), 'v'));
    }

    /**
     * The video an address names: YouTube's watch, youtu.be, embed (nocookie
     * too), shorts and live addresses; Vimeo's page and player; a Twitch
     * video. Null for anything else (a channel, a playlist, another site).
     */
    public static function fromUrl(string $url): ?self
    {
        $url = trim($url);
        $patterns = [
            '~^(?:https?://)?(?:www\.|m\.|music\.)?youtube\.com/watch\?(?:.*&)?v=([A-Za-z0-9_-]{11})(?:[&#]|$)~' => Platform::YouTube,
            '~^(?:https?://)?youtu\.be/([A-Za-z0-9_-]{11})(?:[?&#/]|$)~' => Platform::YouTube,
            '~^(?:https?://)?(?:www\.|m\.)?youtube(?:-nocookie)?\.com/(?:embed|shorts|live|v)/([A-Za-z0-9_-]{11})(?:[?&#/]|$)~' => Platform::YouTube,
            '~^(?:https?://)?(?:www\.)?vimeo\.com/(\d+)(?:[?#/]|$)~' => Platform::Vimeo,
            '~^(?:https?://)?player\.vimeo\.com/video/(\d+)(?:[?#/]|$)~' => Platform::Vimeo,
            '~^(?:https?://)?(?:www\.|m\.)?twitch\.tv/videos/(\d+)(?:[?#/]|$)~' => Platform::Twitch,
        ];
        foreach ($patterns as $pattern => $platform) {
            if (preg_match($pattern, $url, $match)) {
                return new self($platform, $match[1]);
            }
        }

        return null;
    }

    /** The video's page at its platform. */
    public function url(): string
    {
        return $this->platform->url($this->id);
    }

    /** The platform's player, or its privacy-friendly variant (null where there is none). */
    public function embedUrl(bool $private = false): ?string
    {
        $url = $this->platform->embedUrl($this->id);

        return $private ? $this->platform->privateEmbedUrl($url) : $url;
    }

    /** "youtube:aqz-KE-bpKQ" */
    public function __toString(): string
    {
        return $this->platform->value.':'.$this->id;
    }
}
