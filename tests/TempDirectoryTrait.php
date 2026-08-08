<?php

namespace Oliezekat\MyLastRss\Tests;

trait TempDirectoryTrait
{
    private static $testTempDirectoryPath = null;

    private static function createTempDirectory()
    {
        if (self::$testTempDirectoryPath !== null) {
            return true;
        }
        $path = null;
        while (($path === null) || is_dir($path) || file_exists($path)) {
            $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phpunit_' . md5(__CLASS__ . '::' . date('U') . rand(1000, 9999), false);
        }
        if (mkdir($path, 0777, true)) {
            self::$testTempDirectoryPath = $path;
            return true;
        }
        return false;
    }

    private function getTempDirectoryPath()
    {
        return self::$testTempDirectoryPath;
    }

    private function assertTempDirectoryDefined()
    {
        $this->assertTrue($this->createTempDirectory(), 'Path not null');
    }

    private function assertTempDirectoryDeleted()
    {
        self::deleteTempDirectory();
        $this->assertTrue($this->getTempDirectoryPath() === null, 'Path is null');
    }

    private static function deleteTempDirectory()
    {
        if (self::$testTempDirectoryPath === null) {
            return;
        }
        self::deleteDirectory(self::$testTempDirectoryPath);
        self::$testTempDirectoryPath = null;
    }

    private static function deleteDirectory($path)
    {
        if (is_dir($path) === false) {
            return;
        }
        foreach (scandir($path, SCANDIR_SORT_NONE) as $key => $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $filepath = $path . DIRECTORY_SEPARATOR . $name;
            if (is_dir($filepath)) {
                self::deleteDirectory($filepath);
            } else {
                @unlink($filepath);
            }
        }
        @rmdir($path);
    }
}
