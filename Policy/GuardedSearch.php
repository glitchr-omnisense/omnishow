<?php

namespace Omnishow\Policy;

use Omnisense\Contracts\Policy\PolicyGuard;
use Omnishow\Model\Query;
use Omnishow\ProviderInterface;
use Omnishow\SearchInterface;

/**
 * A provider's search behind its guard: each call is the operation
 * "search".
 *
 * @extends Guarded<SearchInterface>
 */
final class GuardedSearch extends Guarded implements SearchInterface
{
    public function __construct(SearchInterface&ProviderInterface $provider, PolicyGuard $guard)
    {
        parent::__construct($provider, $guard);
    }

    public function search(Query $query): array
    {
        return $this->guard->call('search', fn () => $this->provider->search($query), amounts: $this->amounts('search', [$query]));
    }
}
