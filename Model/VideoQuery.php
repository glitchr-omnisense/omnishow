<?php

namespace Omnishow\Model;

use Omnishow\Exception\InvalidArgumentException;

/** A search for videos: the words, and what narrows them. */
final readonly class VideoQuery
{
    public string $text;

    /**
     * @param ?string $channel  one channel's videos only, by its identifier
     * @param ?string $language the language the videos are most relevant to (ISO 639-1), where the platform weighs it
     * @param int     $limit    at most this many videos per page (1 to 50)
     * @param ?string $page     the token of the page asked (Page::$next)
     */
    public function __construct(string $text, public ?string $channel = null, public ?string $language = null, public int $limit = 10, public ?string $page = null)
    {
        $text = trim($text);
        if ('' === $text) {
            throw new InvalidArgumentException('A search needs words.');
        }
        if ($limit < 1 || $limit > 50) {
            throw new InvalidArgumentException('A search answers 1 to 50 videos per page.');
        }
        $this->text = $text;
    }
}
