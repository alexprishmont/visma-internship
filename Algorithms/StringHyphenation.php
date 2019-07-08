<?php
declare(strict_types = 1);

namespace Algorithms;

use Algorithms\Interfaces\AlgorithmInterface;

class StringHyphenation implements AlgorithmInterface
{
    private $algorithm;

    public function __construct(Hyphenation $algorithm)
    {
        $this->algorithm = $algorithm;
    }

    public function hyphenate(string $string): string
    {
        $words = $this->extractWordsFromString($string);
        foreach ($words as $word) {
            $word_with_syllable = $this->algorithm->hyphenate($word);
            $string = str_replace($word, $word_with_syllable, $string);
        }
        return $string;
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
