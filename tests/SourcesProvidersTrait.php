<?php

namespace Oliezekat\MyLastRss\Tests;

trait SourcesProvidersTrait
{
    public static function sampleSourceProvider()
    {
        return [
            'RSS 2.0' => [
                implode(DIRECTORY_SEPARATOR, [__DIR__, 'samples', 'rss20.xml']),
                9,
            ],
            'Atom' => [
                implode(DIRECTORY_SEPARATOR, [__DIR__, 'samples', 'atom.xml']),
                1,
            ],
            'RDF 1.0' => [
                implode(DIRECTORY_SEPARATOR, [__DIR__, 'samples', 'rdf10.xml']),
                4,
            ],
            'xmlns:files' => [
                implode(DIRECTORY_SEPARATOR, [__DIR__, 'samples', 'xmlns-files.xml']),
                10,
            ],
        ];
    }

    public static function samplesSourcesProvider()
    {
        $samples = self::sampleSourceProvider();
        $nbSamples = count($samples);
        $nbItems = 0;
        $sources = [];
        foreach ($samples as $sample) {
            $sources[] = $sample[0];
            $nbItems += $sample[1];
        }
        $data = [
            $nbSamples . ' samples' => [
                $sources,
                $nbItems,
            ],
        ];
        return $data;
    }

    public static function urlSourceProvider()
    {
        return [
            'packagist.org/package' => [
                'https://packagist.org/feeds/package.oliezekat/mylastrss.rss',
                1,
            ],
            'github.com/releases' => [
                'https://github.com/oliezekat/mYLastRSS/releases.atom',
                1,
            ],
            'oliezekat.wordpress.com/mylastrss' => [
                'https://oliezekat.wordpress.com/tag/mylastrss/feed/rdf/',
                1,
            ],
            'sourceforge.net/news' => [
                'https://sourceforge.net/p/mylastrss/news/feed.rss',
                1,
            ],
            'sourceforge.net/sources' => [
                'https://sourceforge.net/projects/mylastrss/rss?path=/sources',
                1,
            ],
        ];
    }

    public static function urlsSourcesProvider()
    {
        $urls = self::urlSourceProvider();
        $nbUrls = count($urls);
        $nbItems = 0;
        $sources = [];
        foreach ($urls as $url) {
            $sources[] = $url[0];
            $nbItems += $url[1];
        }
        $data = [
            $nbUrls . ' URLs' => [
                $sources,
                $nbItems,
            ],
        ];
        return $data;
    }
}
