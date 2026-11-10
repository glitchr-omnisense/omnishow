<?php

namespace Omnishow\Policy;

/**
 * A provider whose operations consume an amount only their arguments say:
 * YouTube's videos() reads 50 videos a request, so 120 of them spend 3
 * quota units where the policy's cost says 1. The registry's guard counts
 * what usage() gives in place of the cost, by unit; a provider without it
 * is counted by its policy's costs alone.
 */
interface MeasuresUsageInterface
{
    /**
     * What one call of $operation with $arguments consumes, by the policy's
     * units (["units" => 3]); [] when the policy's cost says it.
     *
     * @param list<mixed> $arguments the contract method's arguments, in order
     *
     * @return array<string, int>
     */
    public function usage(string $operation, array $arguments): array;
}
