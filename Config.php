<?php

namespace Omnishow;

use Omnishow\Exception\InvalidConfigException;

/**
 * A provider's configuration while its factory builds it: the options
 * given, the factory's defaults under them, and the keys that must be
 * filled - omnisense/contracts' Config, with omnishow's prefix
 * ("omnishow.factory_name", "omnishow.backoff"...) and its
 * InvalidConfigException. Every provider takes two options besides its own:
 * "policy" (the site's tightening of its usage policy) and "usage" (what
 * the site says of itself: commercial, open_source, contract).
 */
final class Config extends \Omnisense\Contracts\Provider\Config
{
    /** @var string */
    public const PREFIX = 'omnishow';

    protected function invalid(string $message): \Throwable
    {
        return new InvalidConfigException($message);
    }
}
