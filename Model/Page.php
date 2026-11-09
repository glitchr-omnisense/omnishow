<?php

namespace Omnishow\Model;

/**
 * One page of a longer list (a channel's videos, a search): its items and
 * the token that asks for the next page, null on the last. The token is the
 * provider's own (YouTube's pageToken, Vimeo's page number, Twitch's
 * cursor): it is given back as it came.
 *
 * @template T
 */
final readonly class Page implements \Countable
{
    /**
     * @param list<T> $items
     * @param ?int    $total all the items, where the platform counts them (YouTube's search: an approximation)
     */
    public function __construct(public array $items = [], public ?string $next = null, public ?int $total = null)
    {
    }

    public function count(): int
    {
        return \count($this->items);
    }

    /** A limit kept within what a platform answers per page. */
    public static function limit(int $limit, int $max): int
    {
        return max(1, min($limit, $max));
    }
}
