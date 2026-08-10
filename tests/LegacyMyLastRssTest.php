<?php

namespace Oliezekat\MyLastRss\Tests;

final class LegacyMyLastRssTest extends AbstractClassTestCase
{
    use SourcesProvidersTrait;

    protected function getClassName()
    {
        return '\mYLastRSS';
    }

    protected function getClassMethods()
    {
        return ['Get', 'GetCache'];
    }

    /**
     * @requires extension mbstring
     */
    public function testClassInterface()
    {
        parent::testClassInterface();
    }

    /**
     * @depends testClassInterface
     * @dataProvider sampleSourceProvider
     */
    public function testGetSample($source, $nbItems)
    {
        $className = $this->getClassName();
        $rss = new $className();
        $result = $rss->Get($source);
        $this->assertTrue(is_array($result), 'Result is array');
        $this->assertArrayHasKey('items', $result, 'Result has "items" key');
        $this->assertTrue(is_array($result['items']), 'Result has array of items');
        $this->assertEquals($nbItems, count($result['items']), "Number of items expected");
    }

    /**
     * @testdox Get array of sources
     * @depends testClassInterface
     * @dataProvider samplesSourcesProvider
     */
    public function testGetSamples($sources, $nbItems)
    {
        $className = $this->getClassName();
        $rss = new $className();
        $result = $rss->Get($sources);
        $this->assertTrue(is_array($result), 'Result is array');
        $this->assertArrayHasKey('items', $result, 'Result has "items" key');
        $this->assertTrue(is_array($result['items']), 'Result has array of items');
        $this->assertEquals($nbItems, count($result['items']), "Number of items expected");
    }
}
