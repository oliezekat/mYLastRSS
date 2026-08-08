<?php

namespace Oliezekat\MyLastRss\Tests;

trait TempDirectoryTrait
{
    // Array of temporary directories for each class
    private static $testTempDirectories = []; 

    private static function createTempDirectory($className = null)
    {
        if ($className === null) {
            $className = static::class;
        }
        if (isset(self::$testTempDirectories[$className])) {
            return true;
        }
        $path = null;
        while (($path === null) || is_dir($path) || file_exists($path)) {
            $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phpunit_' . md5($className . '::' . date('U') . rand(1000, 9999), false);
        }
        if (mkdir($path, 0777, true)) {
            self::$testTempDirectories[$className] = $path;
            return true;
        }
        return false;
    }

    private function getTempDirectoryPath($className = null)
    {
        if ($className === null) {
            $className = static::class;
        }
        return (isset(self::$testTempDirectories[$className]) ? self::$testTempDirectories[$className] : null);
    }

    private static function deleteTempDirectory($className = null)
    {
        if ($className === null) {
            $className = static::class;
        }
        if (isset(self::$testTempDirectories[$className]) === false) {
            return;
        }
        self::deleteDirectory(self::$testTempDirectories[$className]);
        unset(self::$testTempDirectories[$className]);
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
