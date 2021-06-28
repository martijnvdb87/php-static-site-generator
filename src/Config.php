<?php

namespace Martijnvdb\StaticSiteGenerator;

use Symfony\Component\Yaml\Yaml;

class Config
{
    private static $path = __DIR__ . '/../config.yaml';
    private static $config = [];
    
    private static $content_path = __DIR__ . '/../content';
    private static $public_path = __DIR__ . '/../public';

    private static function load()
    {
        if(empty(self::$config)) {
            if(file_exists(self::$path)) {
                self::$config = Yaml::parse(file_get_contents(self::$path));
            }

            self::$config['path'] = [
                'content' => self::$content_path,
                'public' => self::$public_path
            ];
        }
    }

    public static function get(string $key)
    {
        self::load();

        $parts = explode('.', $key);

        $config = self::$config;
        
        while($part = array_shift($parts)) {
            if(!isset($config[$part])) {
                return null;
            }

            $config = $config[$part];
        }


        return $config;
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