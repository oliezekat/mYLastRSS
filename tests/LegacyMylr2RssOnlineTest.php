<?php

namespace Oliezekat\MyLastRss\Tests;

final class LegacyMylr2RssOnlineTest extends AbstractMylr2RssTestCase
{
    use SourcesProvidersTrait;

    public static function sourcesProvider()
    {
        return self::urlsSourcesProvider(1);
    }
}
