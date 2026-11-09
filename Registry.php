<?php

namespace Omnishow;

use Omnishow\Exception\InvalidConfigException;
use Omnishow\Exception\NotSupportedException;
use Omnishow\Model\Video;
use Omnishow\Model\VideoRef;
use Omnishow\Model\Work;
use Omnishow\Model\WorkRef;

/**
 * The application's providers by name, each built once from its factory and
 * options:
 *
 *   $registry = new Registry([new TmdbFactory($http), new WikidataFactory($http)], [
 *       'films' => ['factory' => 'tmdb', 'options' => ['token' => '...']],
 *       'open' => ['factory' => 'wikidata', 'options' => ['user_agent' => 'MySite/1.0 (https://example.org; films@example.org)']],
 *   ]);
 *
 *   $registry->search('films')->search(new Query('Stalker', WorkType::Movie));
 *   $registry->work(WorkRef::imdb('tt0079944'));   // the first provider that finds it
 *
 * Video and live providers sit in the same registry:
 *
 *   $registry->channels('youtube')->channelVideos('@example');
 *   $registry->live('twitch')->live('example');
 *   $registry->findVideo($work->trailer()->ref);   // the first provider that hosts its platform
 */
final class Registry
{
    /** @var array<string, ProviderFactoryInterface> */
    private array $factories = [];

    /** @var array<string, ProviderInterface> */
    private array $providers = [];

    /**
     * @param iterable<ProviderFactoryInterface>                                     $factories
     * @param array<string, array{factory: string, options?: array<string, mixed>}> $config
     */
    public function __construct(iterable $factories, private readonly array $config)
    {
        foreach ($factories as $factory) {
            $this->factories[$factory->getName()] = $factory;
        }
    }

    public function get(string $name): ProviderInterface
    {
        return $this->providers[$name] ??= $this->factory($name)->create($this->config[$name]['options'] ?? []);
    }

    public function has(string $name): bool
    {
        return isset($this->config[$name]);
    }

    public function search(string $name): SearchInterface
    {
        $provider = $this->get($name);

        return $provider instanceof SearchInterface ? $provider : throw NotSupportedException::capability($provider, $name, 'search');
    }

    public function lookup(string $name): LookupInterface
    {
        $provider = $this->get($name);

        return $provider instanceof LookupInterface ? $provider : throw NotSupportedException::capability($provider, $name, 'look a work up');
    }

    public function episodes(string $name): EpisodesInterface
    {
        $provider = $this->get($name);

        return $provider instanceof EpisodesInterface ? $provider : throw NotSupportedException::capability($provider, $name, 'list episodes');
    }

    public function availability(string $name): AvailabilityInterface
    {
        $provider = $this->get($name);

        return $provider instanceof AvailabilityInterface ? $provider : throw NotSupportedException::capability($provider, $name, 'say where to watch');
    }

    public function video(string $name): VideoInterface
    {
        $provider = $this->get($name);

        return $provider instanceof VideoInterface ? $provider : throw NotSupportedException::capability($provider, $name, 'give videos');
    }

    public function videoSearch(string $name): VideoSearchInterface
    {
        $provider = $this->get($name);

        return $provider instanceof VideoSearchInterface ? $provider : throw NotSupportedException::capability($provider, $name, 'search videos');
    }

    public function channels(string $name): ChannelInterface
    {
        $provider = $this->get($name);

        return $provider instanceof ChannelInterface ? $provider : throw NotSupportedException::capability($provider, $name, 'give channels');
    }

    public function playlists(string $name): PlaylistInterface
    {
        $provider = $this->get($name);

        return $provider instanceof PlaylistInterface ? $provider : throw NotSupportedException::capability($provider, $name, 'give playlists');
    }

    public function live(string $name): LiveInterface
    {
        $provider = $this->get($name);

        return $provider instanceof LiveInterface ? $provider : throw NotSupportedException::capability($provider, $name, 'say what is live');
    }

    /**
     * The video's record from the first provider, in the configuration's
     * order (or the order named), that hosts its platform: a trailer TMDB
     * links to, read from YouTube.
     */
    public function findVideo(VideoRef $ref, string ...$names): ?Video
    {
        foreach ($names ?: $this->names() as $name) {
            $provider = $this->get($name);
            if ($provider instanceof VideoInterface && $provider->capabilities()->hosts($ref) && null !== $video = $provider->video($ref)) {
                return $video;
            }
        }

        return null;
    }

    /**
     * The work from the first provider, in the configuration's order (or the
     * order named), that finds it by this register: an IMDb number read from
     * Wikidata looked up at TMDB, a TMDB number at Trakt.
     */
    public function work(WorkRef $ref, ?string $language = null, string ...$names): ?Work
    {
        foreach ($names ?: $this->names() as $name) {
            $provider = $this->get($name);
            if ($provider instanceof LookupInterface && $provider->capabilities()->resolves($ref) && null !== $work = $provider->work($ref, $language)) {
                return $work;
            }
        }

        return null;
    }

    /** @return array<string, mixed> the options a provider is configured with */
    public function options(string $name): array
    {
        return $this->config[$name]['options'] ?? [];
    }

    /** @return list<string> the configured providers' names */
    public function names(): array
    {
        return array_keys($this->config);
    }

    /** @return list<string> the factories installed: what `factory:` may name */
    public function factories(): array
    {
        return array_keys($this->factories);
    }

    private function factory(string $name): ProviderFactoryInterface
    {
        $provider = $this->config[$name] ?? throw new InvalidConfigException(\sprintf('No "%s" provider; configured: %s.', $name, implode(', ', array_keys($this->config)) ?: 'none'));

        return $this->factories[$provider['factory']] ?? throw new InvalidConfigException(\sprintf('No "%s" factory for the "%s" provider; installed: %s.', $provider['factory'], $name, implode(', ', array_keys($this->factories)) ?: 'none'));
    }
}
