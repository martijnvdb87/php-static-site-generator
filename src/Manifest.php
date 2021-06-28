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

    public function build(): self
    {
        $data = self::$defaults;

        $data['name'] = Config::get('name') ?? $data['name'];

        $data = json_encode($data);

        file_put_contents(Config::get('path.public') . '/manifest.json', $data);

        return $this;
    }
}