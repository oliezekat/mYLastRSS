<?php

namespace Oliezekat\MyLastRss\Tests;

abstract class AbstractMylr2RssTestCase extends AbstractClassTestCase
{
    use TempDirectoryTrait;

    /* TempDirectoryTrait */

    public static function set_up_before_class()
    {
        self::createTempDirectory();
    }

    public static function tear_down_after_class()
    {
        // self::deleteTempDirectory();
    }

    /* AbstractClassTestCase */

    final protected function getClassName()
    {
        return '\mYLR2RSS';
    }

    final protected function getClassMethods()
    {
        return ['Get', 'GetCache', 'Output'];
    }

    public function testClassInterface()
    {
        parent::testClassInterface();
    }

    /* AbstractMylr2RssTestCase */

    private function getTestCachePath()
    {
        return $this->getTempDirectoryPath() . DIRECTORY_SEPARATOR . 'cache';
    }

    private function getTestOutputFilePath()
    {
        return implode(DIRECTORY_SEPARATOR, [$this->getTempDirectoryPath(), str_replace('\\', '-', static::class) . '-Output.xml']);
    }

    protected function createClassInstance()
    {
        $className = $this->getClassName();
        $instance                = new $className();
        $instance->cache_dir     = $this->getTestCachePath();
        $instance->feed_title    = static::class . ' - Output';
        $instance->feed_link     = 'https://github.com/oliezekat/mYLastRSS';
        return $instance;
    }

    /**
     * @testdox Get array of sources with cache
     * @depends testClassInterface
     * @dataProvider sourcesProvider
     */
    public function testGet($sources, $minItems)
    {
        $instance = $this->createClassInstance();
        $result = $instance->Get($sources);
        $this->assertTrue(is_array($result), 'Result is array');
        $this->assertArrayHasKey('items', $result, 'Result has "items" key');
        $this->assertTrue(is_array($result['items']), 'Result has array of items');
        $this->assertGreaterThanOrEqual($minItems, count($result['items']), "Minimum number of items expected");
    }

    /**
     * @testdox Get sources from cache
     * @depends testGet
     * @dataProvider sourcesProvider
     */
    public function testGetCache($sources, $minItems)
    {
        $instance = $this->createClassInstance();
        $result = $instance->GetCache($sources);
        $this->assertTrue(is_array($result), 'Result is array');
        $this->assertArrayHasKey('items', $result, 'Result has "items" key');
        $this->assertTrue(is_array($result['items']), 'Result has array of items');
        $this->assertGreaterThanOrEqual($minItems, count($result['items']), "Minimum number of items expected");
    }

    /**
     * @testdox Output RSS 2.0 feed
     * @depends testGetCache
     * @dataProvider sourcesProvider
     */
    public function testOutput($sources, $minItems)
    {
        $instance = $this->createClassInstance();
        $output = $instance->Output($sources, true);
        $this->assertTrue($output !== null, 'Output not null');
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
     * @dataProvider sourcesProvider
     */
    public function testGetOutputSaved($sources, $minItems)
    {
        $outputFilePath = $this->getTestOutputFilePath();
        $this->assertFileIsReadable($outputFilePath, 'Output saved file readable');
        $instance = $this->createClassInstance();
        $result = $instance->Get($outputFilePath);
        $this->assertTrue(is_array($result), 'Result is array');
        $this->assertArrayHasKey('items', $result, 'Result has "items" key');
        $this->assertTrue(is_array($result['items']), 'Result has array of items');
        $this->assertGreaterThanOrEqual($minItems, count($result['items']), "Minimum number of items expected");
    }
}
