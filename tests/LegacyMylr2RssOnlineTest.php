<?php

namespace Oliezekat\MyLastRss\Tests;

final class LegacyMylr2RssOnlineTest extends AbstractMylr2RssTestCase
{
    use SourcesProvidersTrait;

    protected function createClassInstance()
    {
        $instance                            = parent::createClassInstance();
        $instance->cache_feeds_if_failed     = true;
        return $instance;
    }

    public static function sourcesProvider()
    {
        $urls = self::urlSourceProvider();
        $nbUrls = count($urls);
        // simulate a URL that return error
        $urls['github.com/406-error'] = [
            'https://github.com/oliezekat/mYLastRSS/releases.rss',
            0,
        ];
        $sources = [];
        foreach ($urls as $url) {
            $sources[] = $url[0];
        }
        $data = [
            $nbUrls . '+1 URLs' => [
                $sources,
                1, // Hope one URL return feed content
            ],
        ];
        return $data;
    }
}
