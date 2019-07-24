<?php
declare(strict_types=1);

namespace Core\Input;

use Core\Application;
use Core\DI\Container;
use Core\DI\DependenciesLoader;
use Core\Exceptions\InvalidFlagException;
use Core\Input\Interfaces\ResolverInterface;

class Resolver implements ResolverInterface
{
    private static $objectName;

    public static function resolve(string $flag)
    {
        if (!Validator::validateFlag($flag)) {
            throw new InvalidFlagException('Such flag [' . $flag . '] does not exist.');
        }
        $objectName = self::getObjectName($flag);
        $object = self::getObject($objectName);
        self::$objectName = $objectName;
        return $object;
    }

    public static function callMethod(object $object, string $target)
    {
        $result = '';
        switch (self::$objectName) {
            case 'proxy':
                $result = 'Result: ' . $object->hyphenate($target);
                break;
            case 'stringHyphenation':
                $result = 'Result: ' . $object->hyphenate($target);
                break;
            case 'fileHyphenation':
                $result = 'Result: ' . $object->hyphenate($target);
                break;
            case 'cacheController':
                $object->clear();
                $result = 'Cache successfully cleared.';
                break;
            case 'import':
                $scan = self::getObject('scan');
                $patterns = $scan->readDataFromFile(Application::$settings['PATTERNS_SOURCE']);
                $object
                    ->patterns($patterns)
                    ->importPatterns();
                $result = 'Patterns successfully imported from ' . Application::$settings['PATTERNS_SOURCE'];
                break;
            case 'migration':
                $object->migrate($target);
                $result = 'Migration successfully done.';
                break;
        }
        return $result;
    }

    private static function getObject(string $objectName)
    {
        $container = new Container;
        return $container->get(
            DependenciesLoader::get()[$objectName]
        );
    }

    private static function getObjectName(string $method): string
    {
        switch ($method) {
            case '-word':
                return 'proxy';
                break;
            case '-sentence':
                return 'stringHyphenation';
                break;
            case '-file':
                return 'fileHyphenation';
                break;
            case '-reset':
                return 'cacheController';
                break;
            case '-import':
                return 'import';
                break;
            case '-migrate':
                return 'migration';
                break;
        }
    }
}