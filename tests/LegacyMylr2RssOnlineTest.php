<?php

namespace Oliezekat\MyLastRss\Tests;

final class LegacyMylr2RssOnlineTest extends AbstractMylr2RssTestCase
{
    use SourcesProvidersTrait;

    public static function set_up_before_class()
    {
        parent::set_up_before_class();
        set_time_limit(90);
    }

    protected function createClassInstance()
    {
        $instance                            = parent::createClassInstance();
        $instance->cache_feeds_if_failed     = true;
        $instance->max_execution_time        = 50;
        $instance->kidx_rule                 = 'date+title/link';
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

    /**
     * @testdox Class interface with OpenSSL extension
     * @requires extension openssl
     * @requires extension mbstring
     */
    public function testClassInterface()
    {
        parent::testClassInterface();
    }

    /**
     * @testdox Get sources force-cached over HTTPS
     * @requires extension openssl
     * @large
     * @depends testClassInterface
     * @dataProvider sourcesProvider
     */
    public function testGet($sources, $minItems)
    {
        parent::testGet($sources, $minItems);
    }
}
