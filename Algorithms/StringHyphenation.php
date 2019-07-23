<?php
declare(strict_types=1);

namespace Algorithms;

use Algorithms\Interfaces\HyphenationInterface;
use Core\Application;
use Core\Cache\FileCache;
use Core\Tools;

class StringHyphenation implements HyphenationInterface
{
    private $algorithm;
    private $cache;

    public function __construct(Hyphenation $algorithm)
    {
        $this->algorithm = $algorithm;
        $this->cache = FileCache::getInstanceOf();
    }

    public function hyphenate(string $string): string
    {
        $words = $this->extractWordsFromString($string);
        $result = $string;

        foreach($words as $word) {
            $result = str_replace($word, $this->algorithm->hyphenate($word), $result);
        }

        return $result;
    }

    private function extractWordsFromString(string $string): array
    {
        $temp = preg_split('/(\s+)/', $this->clearString($string), -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $spaces = [];
        $words = array_reduce($temp, function (&$result, $item) use (&$spaces) {
            if (strlen(trim($item)) === 0)
                $spaces[] = strlen($item);
            else
                $result[] = $item;
            return $result;
        }, []);
        return $words;
    }

    private function clearString(string $string): string
    {
        return preg_replace("/[^a-zA-Z]/", " ", $string);
    }
}
