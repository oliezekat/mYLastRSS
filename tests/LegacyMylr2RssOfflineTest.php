<?php

namespace Oliezekat\MyLastRss\Tests;

final class LegacyMylr2RssOfflineTest extends AbstractMylr2RssTestCase
{
    use SourcesProvidersTrait;

    public static function sourcesProvider()
    {
        return self::samplesSourcesProvider();
    }

}
