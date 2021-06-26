<?php

namespace Martijnvdb\StaticSiteGenerator;

use Symfony\Component\Yaml\Yaml;

class Config
{
    private static $path = __DIR__ . '/../config.yaml';
    private static $config = [];

    private static function load()
    {
        if(empty(self::$config)) {
            if(file_exists(self::$path)) {
                self::$config = Yaml::parse(file_get_contents(self::$path));
            }
        }
    }

    public static function get(string $key): ?string
    {
        self::load();

        if(isset(self::$config[$key])) {
            return self::$config[$key];
        }

        return null;
    }

    public static function set(string $key, string $value): void
    {
        self::load();

        self::$config[$key] = $value;
    }

    public static function save(): void
    {
        file_put_contents(self::$path, Yaml::dump(self::$config));
    }
}