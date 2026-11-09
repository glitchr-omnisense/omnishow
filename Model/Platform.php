<?php

namespace Omnishow\Model;

/**
 * Where a video is hosted: its watch page, its player, and the player's
 * privacy-friendly variant where the platform documents one - YouTube's
 * privacy-enhanced mode (www.youtube-nocookie.com), Vimeo's dnt=1. Twitch
 * documents none, and its player needs the embedding page's domain
 * (parent=).
 */
enum Platform: string
{
    case YouTube = 'youtube';
    case Vimeo = 'vimeo';
    case Twitch = 'twitch';

    /** The platform a provider names ("YouTube", "Vimeo" at TMDB), null when it is none of these. */
    public static function fromName(string $name): ?self
    {
        return self::tryFrom(strtolower(trim($name)));
    }

    public function title(): string
    {
        return match ($this) {
            self::YouTube => 'YouTube',
            self::Vimeo => 'Vimeo',
            self::Twitch => 'Twitch',
        };
    }

    /** The video's page at the platform. */
    public function url(string $id): string
    {
        return match ($this) {
            self::YouTube => 'https://www.youtube.com/watch?v='.rawurlencode($id),
            self::Vimeo => 'https://vimeo.com/'.rawurlencode($id),
            self::Twitch => 'https://www.twitch.tv/videos/'.rawurlencode($id),
        };
    }

    /** The platform's player for the video (Twitch's still needs parent=, see Video::embed()). */
    public function embedUrl(string $id): string
    {
        return match ($this) {
            self::YouTube => 'https://www.youtube.com/embed/'.rawurlencode($id),
            self::Vimeo => 'https://player.vimeo.com/video/'.rawurlencode($id),
            self::Twitch => 'https://player.twitch.tv/?video=v'.rawurlencode(ltrim($id, 'v')),
        };
    }

    /**
     * The privacy-friendly variant of one of the platform's player URLs:
     * YouTube's on www.youtube-nocookie.com, Vimeo's with dnt=1 (its hash
     * h= kept); null where the platform documents none (Twitch).
     */
    public function privateEmbedUrl(string $embedUrl): ?string
    {
        return match ($this) {
            self::YouTube => (string) preg_replace('~^https?://(?:www\.)?youtube(?:-nocookie)?\.com/~', 'https://www.youtube-nocookie.com/', $embedUrl),
            self::Vimeo => preg_match('~[?&]dnt=~', $embedUrl) ? $embedUrl : $embedUrl.(str_contains($embedUrl, '?') ? '&' : '?').'dnt=1',
            self::Twitch => null,
        };
    }

    /** Whether the player needs the embedding page's domain (Twitch: parent=). */
    public function needsParent(): bool
    {
        return self::Twitch === $this;
    }
}
