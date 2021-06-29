<?php

namespace Martijnvdb\StaticSiteGenerator;

use Martijnvdb\StaticSiteGenerator\Config;

class Manifest
{
    private static $defaults = [
        'name' => '',
        'short_name' => '',
        'icons' => [
            'src' => '/assets/icons/192.png',
            'type' => 'image/png',
            'sizes' => '192x192'
        ],
        'start_url' => '/',
        'background_color' => '#3367D6',
        'display' => 'browser',
        'scope' => '/',
        'theme_color' => '#3367D6',
        'shortcuts' => [
            'name' => '',
            'short_name' => '',
            'description' => '',
            'url' => '',
            'icons' => [
                'src' => '/assets/shortcuts/1.png',
                'sizes' => '192x192'
            ]
        ],
        'description' => '',
        'screenshots' => [
              'src' => '/assets/screenshot/1.png',
              'type' => 'image/png',
              'sizes' => '540x720'
        ]
    ];

    public static function create(): self
    {
        $manifest = new self();

        return $manifest;
    }

    private static function get(string $key)
    {
        $data = self::$defaults;
        
        $parts = explode('.', $key);
        
        while($part = array_shift($parts)) {
            if(!isset($data[$part])) {
                return null;
            }

            $data = $data[$part];
        }

        return $data;
    }

    private static function set(string $key, $value): void
    {
        $data = &self::$defaults;
        
        $parts = explode('.', $key);

        foreach($parts as $part) {
            if(!isset($data[$part])) {
                $data = [
                    $part => []
                ];
            }
            
            $data = &$data[$part];
        }

        $data = $value;
    }

    public function build(): self
    {
        $data = &self::$defaults;

        $config_mapping = [
            'name' => 'name',
            'short_name' => 'short_name',
            'start_url' => 'start_url',
            'background_color' => 'background_color',
            'display' => 'display',
            'scope' => 'scope',
            'theme_color' => 'theme_color',
            'description' => 'description',
        ];

        foreach($config_mapping as $key => $config) {
            self::set($key, Config::get($config) ?? self::get($key));
        }

        $data = json_encode($data);

        file_put_contents(Config::get('path.public') . '/manifest.json', $data);

        return $this;
    }
}