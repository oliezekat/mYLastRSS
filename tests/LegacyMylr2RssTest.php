<?php

namespace Oliezekat\MyLastRss\Tests;

final class LegacyMylr2RssTest extends AbstractClassTestCase
{
    use TempDirectoryTrait;
    use SourcesProvidersTrait;

    protected function getClassName()
    {
        return '\mYLR2RSS';
    }

    protected function getClassMethods()
    {
        return ['Get', 'GetCache', 'Output'];
    }

    public function testClassInterface()
    {
        parent::testClassInterface();
    }

    /**
     * @depends testClassInterface
     */
    public function testTempDirectoryDefined()
    {
        $this->assertTempDirectoryDefined();
    }

    private function getTestCachePath()
    {
        return $this->getTempDirectoryPath() . DIRECTORY_SEPARATOR . 'cache';
    }

    /**
     * @testdox Get array of sources with cache
     * @requires extension openssl
     * @depends testClassInterface
     * @depends testTempDirectoryDefined
     * @dataProvider urlsSourcesProvider
     */
    public function testGet($sources, $minItems)
    {
        $className = $this->getClassName();
        $rss = new $className();
        $rss->cache_dir = $this->getTestCachePath();
        $result = $rss->Get($sources);
        $this->assertTrue(is_array($result), 'Result is array');
        $this->assertArrayHasKey('items', $result, 'Result has "items" key');
        $this->assertTrue(is_array($result['items']), 'Result has array of items');
        $this->assertGreaterThanOrEqual($minItems, count($result['items']), "Minimum number of items expected");
    }

    /**
     * @testdox Get sources from cache
     * @depends testGet
     * @dataProvider urlsSourcesProvider
     */
    public function testGetCache($sources, $minItems)
    {
        $className = $this->getClassName();
        $rss = new $className();
        $rss->cache_dir = $this->getTestCachePath();
        $result = $rss->GetCache($sources);
        $this->assertTrue(is_array($result), 'Result is array');
        $this->assertArrayHasKey('items', $result, 'Result has "items" key');
        $this->assertTrue(is_array($result['items']), 'Result has array of items');
        $this->assertGreaterThanOrEqual($minItems, count($result['items']), "Minimum number of items expected");
    }

    private function getTestOutputFilePath()
    {
        return implode(DIRECTORY_SEPARATOR, ['var','outputs','phpunit','mYLR2RSS-Output-Test.xml']);
    }

    /**
     * @testdox Output RSS 2.0 feed
     * @depends testGetCache
     * @dataProvider urlsSourcesProvider
     */
    public function testOutput($sources, $minItems)
    {
        $className = $this->getClassName();
        $rss = new $className();
        $rss->cache_dir = $this->getTestCachePath();
        $rss->feed_title = 'mYLastRSS - PHPUnit - LegacyMylr2RssTest - Output';
        $rss->feed_link = 'https://github.com/oliezekat/mYLastRSS';
        ob_start();
        $result = $rss->Output($sources);
        $output = $this->getActualOutput();
        ob_end_clean();
        $this->assertTrue($result, 'Result is success');
        $this->assertTrue(trim($output) !== '', 'Output not empty');
        $outputFilePath = $this->getTestOutputFilePath();
        $saved = file_put_contents(
            $outputFilePath,
            $output
        );
        $this->assertTrue(($saved !== false && $saved > 0), 'Output saved');
        $this->assertFileExists($outputFilePath, 'Output saved file exists');
    }

    /**
     * @testdox Get output feed saved 
     * @depends testOutput
     * @dataProvider urlsSourcesProvider
     */
    public function testGetOutputSaved($sources, $minItems)
    {
        $outputFilePath = $this->getTestOutputFilePath();
        $this->assertFileIsReadable($outputFilePath, 'Output saved file readable');
        $className = $this->getClassName();
        $rss = new $className();
        $result = $rss->Get($outputFilePath);
        $this->assertTrue(is_array($result), 'Result is array');
        $this->assertArrayHasKey('items', $result, 'Result has "items" key');
        $this->assertTrue(is_array($result['items']), 'Result has array of items');
        $this->assertGreaterThanOrEqual($minItems, count($result['items']), "Minimum number of items expected");
    }

    /**
     * @depends testTempDirectoryDefined
     */
    public function testTempDirectoryDeleted()
    {
        self::deleteTempDirectory();
        $this->assertTrue($this->getTempDirectoryPath() === null, 'Path is null');
    }
}
