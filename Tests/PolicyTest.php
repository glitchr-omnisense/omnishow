<?php

namespace Omnishow\Tests;

use Omnisense\Contracts\Policy\Caching;
use Omnisense\Contracts\Policy\InvalidPolicyException;
use Omnisense\Contracts\Policy\Permission;
use Omnisense\Contracts\Policy\RateLimit;
use Omnisense\Contracts\Policy\UsageNotPermitted;
use Omnisense\Contracts\Policy\UsagePolicy;
use Omnishow\Capabilities;
use Omnishow\Config;
use Omnishow\Exception\OmnishowException;
use Omnishow\Exception\UsageLimitReachedException;
use Omnishow\Http\JsonClient;
use Omnishow\Model\Query;
use Omnishow\Model\WorkRef;
use Omnishow\Policy\GuardedLookup;
use Omnishow\Policy\GuardedSearch;
use Omnishow\ProviderFactory;
use Omnishow\ProviderInterface;
use Omnishow\Registry;
use Omnishow\SearchInterface;
use Omnishow\Testing\FixedProvider;
use Omnishow\Testing\FixedProviderFactory;
use Omnishow\Testing\FixedVideoProviderFactory;
use Omnishow\Testing\RecordedHttpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/** Every call through a contract is guarded by the provider's usage policy, as the site tightened it. */
final class PolicyTest extends TestCase
{
    public function testCallsThroughAContractCountUnderTheSitesLimit(): void
    {
        $registry = new Registry([new FixedProviderFactory()], ['films' => ['factory' => 'fixed', 'policy' => ['rateLimits' => [['max' => 2, 'window' => 'minute', 'operations' => ['search', 'work']]]]]]);

        self::assertInstanceOf(GuardedSearch::class, $registry->search('films'));
        self::assertInstanceOf(FixedProvider::class, $registry->search('films')->inner());
        self::assertInstanceOf(FixedProvider::class, $registry->get('films'), 'get() gives the provider itself');
        self::assertSame('fixed', $registry->search('films')->getName());
        $registry->search('films')->search(new Query('Stalker'));
        $lookup = $registry->lookup('films');
        self::assertInstanceOf(GuardedLookup::class, $lookup);
        $lookup->work(WorkRef::imdb('tt0079944'));
        $registry->episodes('films')->episodes(WorkRef::tvdb(1), 1);
        try {
            $registry->work(WorkRef::imdb('tt0079944'));
            self::fail('A third counted call went through.');
        } catch (OmnishowException $e) {
            self::assertInstanceOf(UsageLimitReachedException::class, $e);
            self::assertSame('fixed\'s usage policy allows 2 a minute for search, work: reached, try again in 60 s.', $e->getMessage());
        }
        self::assertSame(0, $registry->guard('films')->remaining('search'));
        self::assertNull($registry->guard('films')->remaining('episodes'), 'episodes are not counted');
    }

    public function testVideoAndLiveCallsAreGuardedToo(): void
    {
        $registry = new Registry([new FixedVideoProviderFactory()], ['videos' => ['factory' => 'fixed-video', 'options' => ['policy' => ['rateLimits' => [['max' => 1, 'window' => 'day']]]]]]);

        self::assertNull($registry->live('videos')->live('example'));
        $this->expectException(UsageLimitReachedException::class);
        $registry->channels('videos')->channel('example');
    }

    public function testA429AndItsRetryAfterHoldEveryCallUntilTheWaitIsOver(): void
    {
        $http = RecordedHttpClient::routes(['~/search~' => [429, '{"error":"too many requests"}', ['retry-after' => ['120']]]]);
        $registry = new Registry([self::overHttp($http)], ['films' => ['factory' => 'http']]);

        try {
            $registry->search('films')->search(new Query('Stalker'));
            self::fail('A 429 was read as works.');
        } catch (UsageLimitReachedException $e) {
            self::assertSame(120, $e->retryAfter);
            self::assertSame('http answered 429 Too Many Requests: try again in 120 s.', $e->getMessage());
        }
        try {
            $registry->search('films')->search(new Query('Solaris'));
            self::fail('A call went out while the provider asked to wait.');
        } catch (UsageLimitReachedException $e) {
            self::assertGreaterThanOrEqual(119, $e->retryAfter);
        }
        self::assertCount(1, $http->requests, 'the wait held the next request back');
    }

    public function testASiteMayTightenAPolicyNeverLoosenIt(): void
    {
        $declared = UsagePolicy::builder('http')->limit(RateLimit::perDay(1000))->commercial(Permission::Forbidden)->build();
        $tighter = new Registry([self::overHttp(RecordedHttpClient::routes([]), $declared)], ['films' => ['factory' => 'http', 'policy' => ['rateLimits' => [['max' => 100, 'window' => 'day']], 'rights' => ['caching' => ['permission' => 'forbidden']]]]]);

        self::assertSame(100, $tighter->guard('films')->remaining('search'));
        self::assertSame(Permission::Forbidden, $tighter->policy('films')->rights->caching->permission);

        $looser = new Registry([self::overHttp(RecordedHttpClient::routes([]), $declared)], ['films' => ['factory' => 'http', 'policy' => ['rateLimits' => [['max' => 5000, 'window' => 'day']]]]]);
        try {
            $looser->search('films');
            self::fail('A higher limit than the provider\'s was accepted.');
        } catch (InvalidPolicyException $e) {
            self::assertStringContainsString('5000 a day', $e->getMessage());
        }

        $commercial = new Registry([self::overHttp(RecordedHttpClient::routes([]), $declared)], ['films' => ['factory' => 'http', 'usage' => ['commercial' => true]]]);
        $this->expectException(UsageNotPermitted::class);
        $commercial->search('films');
    }

    public function testCapabilitiesWrittenTheOlderWayGiveAPolicy(): void
    {
        $capabilities = new Capabilities(search: true, attribution: 'Data from Example', terms: ['https://example.org/terms'], keyless: true);
        $policy = $capabilities->policy();

        self::assertSame(UsagePolicy::UNNAMED, $policy->provider);
        self::assertSame('Data from Example', $policy->rights->attribution->text);
        self::assertFalse($policy->requirements->key);
        self::assertNull($policy->rateLimits);
        self::assertSame('https://example.org/terms', $policy->provenance->terms);
        self::assertSame($policy->toArray(), $capabilities->toArray()['policy']);
        $registry = new Registry([new FixedProviderFactory()], ['films' => ['factory' => 'fixed']]);
        self::assertSame('fixed', $registry->policy('films')->provider, 'the registry names it after its provider');
        self::assertSame(['films' => $registry->policy('films')], $registry->policies());
    }

    public function testTheOlderFieldsAreReadFromAPolicy(): void
    {
        $policy = UsagePolicy::builder('x')->limit(RateLimit::perDay(10))->attribution('X data', 'https://x.example')
            ->caching(Caching::upTo(60))->commercial(Permission::Allowed)->provenance(terms: 'https://x.example/terms', policy: 'https://x.example/api')->build();
        $capabilities = new Capabilities(search: true, policy: $policy);

        self::assertSame($policy, $capabilities->policy());
        self::assertSame('X data', $capabilities->attribution);
        self::assertSame(['https://x.example/terms', 'https://x.example/api'], $capabilities->terms);
        self::assertTrue($capabilities->keyless);
        self::assertSame('Mine', (new Capabilities(attribution: 'Mine', policy: $policy))->attribution, 'a field given stays as given');
    }

    /** A provider over HTTP, its JsonClient given the provider's Backoff: a search is one GET /search. */
    private static function overHttp(HttpClientInterface $http, ?UsagePolicy $policy = null): ProviderFactory
    {
        return new class($http, $policy) extends ProviderFactory {
            public function __construct(private readonly HttpClientInterface $http, private readonly ?UsagePolicy $policy)
            {
            }

            protected function populate(Config $c): void
            {
                $c->defaults(['omnishow.factory_name' => 'http', 'omnishow.required_options' => []]);
            }

            protected function build(Config $c): ProviderInterface
            {
                $json = new JsonClient($this->http, 'Example', [], $c->backoff());

                return new class($json, $this->policy) implements ProviderInterface, SearchInterface {
                    public function __construct(private readonly JsonClient $json, private readonly ?UsagePolicy $policy)
                    {
                    }

                    public function getName(): string
                    {
                        return 'http';
                    }

                    public function getTitle(): string
                    {
                        return 'Over HTTP';
                    }

                    public function capabilities(): Capabilities
                    {
                        return new Capabilities(search: true, policy: $this->policy);
                    }

                    public function search(Query $query): array
                    {
                        $this->json->get('https://api.example.org/search', ['q' => $query->text]);

                        return [];
                    }
                };
            }
        };
    }
}
