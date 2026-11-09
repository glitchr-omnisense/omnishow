<?php

namespace Omnishow\Testing;

use Omnishow\Config;
use Omnishow\Model\Offer;
use Omnishow\Model\Work;
use Omnishow\ProviderFactory;
use Omnishow\ProviderInterface;

/** FixedProvider's factory: "fixed", answering from the works it holds. */
final class FixedProviderFactory extends ProviderFactory
{
    /**
     * @param list<Work>                                $works
     * @param array<string, array<string, list<Offer>>> $offers
     */
    public function __construct(private readonly array $works = [], private readonly array $offers = [])
    {
    }

    protected function populate(Config $c): void
    {
        $c->defaults(['omnishow.factory_name' => 'fixed', 'omnishow.factory_title' => 'Fixed works', 'omnishow.required_options' => []]);
    }

    protected function build(Config $c): ProviderInterface
    {
        return new FixedProvider($this->works, $this->offers);
    }
}
