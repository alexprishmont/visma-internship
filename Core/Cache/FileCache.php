<?php

namespace Core\Cache;

use Core\Cache\Interfaces\CacheInterface;
use Core\Exceptions\InvalidArgumentException;
use DateInterval;

class FileCache implements CacheInterface
{
    private const PSR16_RESERVED = '/\{|\}|\(|\)|\/|\\\\|\@|\:/u';

    private $cachePath;
    private $dirMode;
    private $defaultTTL;
    private $fileMode;

    public function __construct($cachePath, $defaultTTL, $dirMode = 0775, $fileMode = 0664)
    {
        $this->defaultTTL = $defaultTTL;
        $this->dirMode = $dirMode;
        $this->fileMode = $fileMode;

        if (!file_exists($cachePath) && file_exists(dirname($cachePath)))
            $this->mkdir($cachePath);

        $path = realpath($cachePath);

        if ($path === false)
            throw new InvalidArgumentException("Cache path does not exist: {$cachePath}");

        if (!is_writable($path . DIRECTORY_SEPARATOR))
            throw new InvalidArgumentException("Cache path is not writable: {$cachePath}");

        $this->cachePath = $path;
    }

    public function get($key, $default = null)
    {
        $path = $this->getPath($key);
        $expiresAt = @filemtime($path);

        if ($expiresAt === false)
            return $default;

        if ($this->getTime() >= $expiresAt) {
            @unlink($path);
            return $default;
        }

        $data = @file_get_contents($path);

        if ($data === false)
            return $default;

        if ($data === 'b:0;')
            return $default;

        $value = @unserialize($data);

        if ($value === false)
            return $default;

        return $value;
    }

    public function set($key, $value, $ttl = null)
    {
        $path = $this->getPath($key);

        $dir = dirname($path);

        if (!file_exists($dir))
            $this->mkdir($dir);

        $tempPath = $this->cachePath . DIRECTORY_SEPARATOR . uniqid('', true);

        if (is_int($ttl))
            $expiresAt = $this->getTime + $ttl;
        elseif ($ttl instanceof DateInterval)
            $expiresAt = date_create_from_format("U", $this->getTime())->add($ttl)->getTimestamp();
        elseif ($ttl === null)
            $expiresAt = $this->getTime() + $this->defaultTTL;
        else
            throw new InvalidArgumentException("invalid TTL: " . print_r($ttl, true));

        if (@file_put_contents($tempPath, serialize($value)) == false)
            return false;

        if (@chmod($tempPath, $this->fileMode) === false)
            return false;

        if (@touch($tempPath, $expiresAt) && @rename($tempPath, $path))
            return true;

        @unlink($tempPath);
        return false;
    }

    public function delete($key)
    {
        $this->validateKey($key);
        $path = $this->getPath($key);
        return !file_exists($path) || @unlink($path);
    }

    public function clear()
    {
    }

    public function getMultiple($keys, $default = null)
    {
    }

    public function setMultiple($values, $ttl = null)
    {
    }

    public function deleteMultiple($keys)
    {
    }

    public function has($key)
    {
    }

    private function getTime()
    {
        return time();
    }

    private function getPath($key)
    {
        $this->validateKey($key);

        $hash = hash("sha256", $key);

        return $this->$cachePath
            . DIRECTORY_SEPARATOR
            . strtoupper($hash[0])
            . DIRECTORY_SEPARATOR
            . strtoupper($hash[1])
            . DIRECTORY_SEPARATOR
            . substr($hash, 2);
    }

    private function validateKey($key)
    {
        if (!is_string($key)) {
            $type = is_object($key) ? get_class($key) : gettype($key);
            throw new InvalidArgumentException("invalid key type: {$type} given");
        }

        if ($key === "")
            throw new InvalidArgumentException("invalid key: empty string given");

        if ($key === null)
            throw new InvalidArgumentException("invalid key: null given");

        if (preg_match(self::PSR16_RESERVED, $key, $match) === 1)
            throw new InvalidArgumentException("invalid character in key: {$match[0]}");
    }

    private function mkdir($path)
    {
        $parent_path = dirname($path);

        if (!file_exists($parent_path))
            $this->mkdir($parent_path);

        mkdir($path);
        chmod($path, $this->dirMode);
    }
}