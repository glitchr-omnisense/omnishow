<?php

/*
 * The omnishow/* providers the harness installs beside the core: their tests'
 * namespace, and what their tests need besides (require-dev).
 */
return [
    'tmdb' => ['Omnishow\\Tmdb\\Tests\\'],
    'omdb' => ['Omnishow\\Omdb\\Tests\\'],
    'tvdb' => ['Omnishow\\Tvdb\\Tests\\'],
    'trakt' => ['Omnishow\\Trakt\\Tests\\'],
    'wikidata' => ['Omnishow\\Wikidata\\Tests\\'],
];
