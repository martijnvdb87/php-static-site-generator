<?php

namespace Martijnvdb\StaticSiteGenerator;

use Symfony\Component\Yaml\Yaml;

class Config
{
    private static $path = 'config.yaml';
    private static $config = null;
    
    private static $content_path = 'content';
    private static $templates_path = 'templates';
    private static $public_path = 'public';
    private static $api_path = 'api';

    private static function load(): void
    {
        if(!isset(self::$config)) {
            if(file_exists(self::$path)) {
                self::$config = Yaml::parse(file_get_contents(self::$path)) ?? [];
            }

            foreach([
                'path.content' => self::$content_path,
                'path.templates' => self::$templates_path,
                'path.public' => self::$public_path,
                'path.api' => self::$api_path,
            ] as $key => $value) {
                self::set($key, self::get($key) ?? $value);
            }
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

        $data = &self::$config;
        
        $parts = explode('.', $key);

        foreach($parts as $part) {
            if(!isset($data[$part])) {
                $data[$part] = [];
            }
            
            $data = &$data[$part];
        }

        $data = $value;
    }

    public static function save(): void
    {
        file_put_contents(self::$path, Yaml::dump(self::$config));
    }
}