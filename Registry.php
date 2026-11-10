<?php

namespace Omnishow;

use Omnisense\Contracts\Policy\RateLimiterInterface;
use Omnisense\Contracts\Provider\ProviderInterface as BaseProvider;
use Omnisense\Contracts\Provider\Registry as BaseRegistry;
use Omnishow\Exception\InvalidConfigException;
use Omnishow\Exception\NotSupportedException;
use Omnishow\Model\Video;
use Omnishow\Model\VideoRef;
use Omnishow\Model\Work;
use Omnishow\Model\WorkRef;
use Omnishow\Policy\GuardedAvailability;
use Omnishow\Policy\GuardedChannels;
use Omnishow\Policy\GuardedEpisodes;
use Omnishow\Policy\GuardedLive;
use Omnishow\Policy\GuardedLookup;
use Omnishow\Policy\GuardedPlaylists;
use Omnishow\Policy\GuardedSearch;
use Omnishow\Policy\GuardedVideo;
use Omnishow\Policy\GuardedVideoSearch;

/**
 * The application's providers by name, each built once from its factory and
 * options, each call through a contract guarded by the provider's usage
 * policy:
 *
 *   $registry = new Registry([new TmdbFactory($http), new WikidataFactory($http), new YoutubeFactory($http)], [
 *       'films' => ['factory' => 'tmdb', 'options' => ['token' => '...']],
 *       'open' => ['factory' => 'wikidata', 'options' => ['user_agent' => 'MySite/1.0 (https://example.org; films@example.org)']],
 *       'videos' => [
 *           'factory' => 'youtube',
 *           'options' => ['key' => '...'],
 *           'policy' => ['rateLimits' => [['max' => 50, 'window' => 'day', 'operations' => ['searchVideos', 'live']]]],
 *       ],
 *   ], $limiter);
 *
 *   $registry->search('films')->search(new Query('Stalker', WorkType::Movie));
 *   $registry->work(WorkRef::imdb('tt0079944'));   // the first provider that finds it
 *
 * Video and live providers sit in the same registry:
 *
 *   $registry->channels('videos')->channelVideos('@example');
 *   $registry->live('twitch')->live('example');
 *   $registry->findVideo($work->trailer()->ref);   // the first provider that hosts its platform
 *
 * search(), lookup(), episodes(), availability(), video(), videoSearch(),
 * channels(), playlists() and live() give the provider behind its guard
 * (Policy\Guarded*): each call is checked against the policy's rate limits
 * for its operation - the contract's method name, "search", "work",
 * "episodes", "offers", "video", "videos", "searchVideos", "channel",
 * "channelVideos", "playlist", "playlists", "playlistVideos", "live",
 * "schedule" - then counted, and refused with
 * Exception\UsageLimitReachedException once a limit is reached or while
 * the provider asks to wait; work() and findVideo() ask through them too.
 * get() gives the provider itself, unguarded. "policy" tightens the
 * provider's policy, never loosens it; "usage" says what the site is
 * (omnisense/contracts' Registry).
 */
final class Registry extends BaseRegistry
{
    /** @var string */
    public const PREFIX = Config::PREFIX;

    /** @var array<string, ProviderInterface> the guarded contracts, by "contract name" */
    private array $guarded = [];

    /**
     * @param iterable<ProviderFactoryInterface>                                                                                         $factories
     * @param array<string, array{factory: string, options?: array<string, mixed>, policy?: array<string, mixed>, usage?: array<string, mixed>}> $config
     * @param ?RateLimiterInterface                                                                                                      $limiter   where every provider's calls are counted: an in-memory one by default, a shared one for an application served by many processes
     */
    public function __construct(iterable $factories, array $config, ?RateLimiterInterface $limiter = null)
    {
        parent::__construct($factories, $config, $limiter);
    }

    public function get(string $name): ProviderInterface
    {
        $provider = parent::get($name);

        return $provider instanceof ProviderInterface ? $provider : throw new InvalidConfigException(\sprintf('The "%s" provider (%s) is no omnishow provider.', $name, $provider::class));
    }

    public function search(string $name): SearchInterface
    {
        $guarded = $this->guarded['search '.$name] ??= new GuardedSearch($this->provider($name, SearchInterface::class, 'search'), $this->guard($name));

        return $guarded instanceof SearchInterface ? $guarded : throw new \LogicException('A guarded contract of another kind.');
    }

    public function lookup(string $name): LookupInterface
    {
        $guarded = $this->guarded['lookup '.$name] ??= new GuardedLookup($this->provider($name, LookupInterface::class, 'look a work up'), $this->guard($name));

        return $guarded instanceof LookupInterface ? $guarded : throw new \LogicException('A guarded contract of another kind.');
    }

    public function episodes(string $name): EpisodesInterface
    {
        $guarded = $this->guarded['episodes '.$name] ??= new GuardedEpisodes($this->provider($name, EpisodesInterface::class, 'list episodes'), $this->guard($name));

        return $guarded instanceof EpisodesInterface ? $guarded : throw new \LogicException('A guarded contract of another kind.');
    }

    public function availability(string $name): AvailabilityInterface
    {
        $guarded = $this->guarded['availability '.$name] ??= new GuardedAvailability($this->provider($name, AvailabilityInterface::class, 'say where to watch'), $this->guard($name));

        return $guarded instanceof AvailabilityInterface ? $guarded : throw new \LogicException('A guarded contract of another kind.');
    }

    public function video(string $name): VideoInterface
    {
        $guarded = $this->guarded['video '.$name] ??= new GuardedVideo($this->provider($name, VideoInterface::class, 'give videos'), $this->guard($name));

        return $guarded instanceof VideoInterface ? $guarded : throw new \LogicException('A guarded contract of another kind.');
    }

    public function videoSearch(string $name): VideoSearchInterface
    {
        $guarded = $this->guarded['videoSearch '.$name] ??= new GuardedVideoSearch($this->provider($name, VideoSearchInterface::class, 'search videos'), $this->guard($name));

        return $guarded instanceof VideoSearchInterface ? $guarded : throw new \LogicException('A guarded contract of another kind.');
    }

    public function channels(string $name): ChannelInterface
    {
        $guarded = $this->guarded['channels '.$name] ??= new GuardedChannels($this->provider($name, ChannelInterface::class, 'give channels'), $this->guard($name));

        return $guarded instanceof ChannelInterface ? $guarded : throw new \LogicException('A guarded contract of another kind.');
    }

    public function playlists(string $name): PlaylistInterface
    {
        $guarded = $this->guarded['playlists '.$name] ??= new GuardedPlaylists($this->provider($name, PlaylistInterface::class, 'give playlists'), $this->guard($name));

        return $guarded instanceof PlaylistInterface ? $guarded : throw new \LogicException('A guarded contract of another kind.');
    }

    public function live(string $name): LiveInterface
    {
        $guarded = $this->guarded['live '.$name] ??= new GuardedLive($this->provider($name, LiveInterface::class, 'say what is live'), $this->guard($name));

        return $guarded instanceof LiveInterface ? $guarded : throw new \LogicException('A guarded contract of another kind.');
    }

    /**
     * The video's record from the first provider, in the configuration's
     * order (or the order named), that hosts its platform: a trailer TMDB
     * links to, read from YouTube. Each provider is asked through its guard.
     */
    public function findVideo(VideoRef $ref, string ...$names): ?Video
    {
        foreach ($names ?: $this->names() as $name) {
            $provider = $this->get($name);
            if ($provider instanceof VideoInterface && $provider->capabilities()->hosts($ref) && null !== $video = $this->video($name)->video($ref)) {
                return $video;
            }
        }

        return null;
    }

    /**
     * The work from the first provider, in the configuration's order (or the
     * order named), that finds it by this register: an IMDb number read from
     * Wikidata looked up at TMDB, a TMDB number at Trakt. Each provider is
     * asked through its guard.
     */
    public function work(WorkRef $ref, ?string $language = null, string ...$names): ?Work
    {
        foreach ($names ?: $this->names() as $name) {
            $provider = $this->get($name);
            if ($provider instanceof LookupInterface && $provider->capabilities()->resolves($ref) && null !== $work = $this->lookup($name)->work($ref, $language)) {
                return $work;
            }
        }

        return null;
    }

    /**
     * The configured providers' names, in the configuration's order; those
     * keeping a contract ("search", "lookup", "video", "live"...) when one is
     * named.
     *
     * @return list<string>
     */
    public function names(?string $contract = null): array
    {
        return parent::names($contract);
    }

    /**
     * A provider that keeps a contract, or NotSupportedException.
     *
     * @template T of object
     *
     * @param class-string<T> $contract
     *
     * @return T&ProviderInterface
     */
    private function provider(string $name, string $contract, string $capability): object
    {
        $provider = $this->contract($name, $contract, $capability);

        return $provider instanceof ProviderInterface ? $provider : throw new InvalidConfigException(\sprintf('The "%s" provider (%s) is no omnishow provider.', $name, $provider::class));
    }

    protected function invalid(string $message): \Throwable
    {
        return new InvalidConfigException($message);
    }

    protected function notSupported(BaseProvider $provider, string $name, string $capability): \Throwable
    {
        return NotSupportedException::capability($provider, $name, $capability);
    }
}
