<?php

namespace Martijnvdb\StaticSiteGenerator;

use Martijnvdb\StaticSiteGenerator\Config;

class Api
{
    public function __construct()
    {
    }

    public static function create(): self
    {
        return new self;
    }

    public function build(): self
    {
        $api_endpoints = [];

        foreach (File::getContent() as $file) {
            $page_variables = Page::create($file)->getTemplateVariables();
            //$page = Page::create($file)->generateImages(false);
            //$page_variables = $page->getTemplateVariables();

            $url = $page_variables['url'];

            //$url = $page_variables['url'];
            $path = explode('/', $url);
            $file = array_pop($path);


            if (!isset($api_endpoints[$url])) {
                $api_endpoints[$url] = [];
            }

            $item_data = [];

            foreach (['title', 'date', 'content'] as $property) {
                $api_endpoints[$url][$property] = $page_variables[$property];
                $item_data[$property] = $page_variables[$property];
            }


            $api_endpoints[$url]['url'] = Config::get('url') . '/' . $page_variables['url'];
            $item_data['url'] = Config::get('url') . '/' . $page_variables['url'];

            if (empty($url)) {
                $api_endpoints[$url]['resource'] = Config::get('url') . '/api.json';
                $item_data['resource'] = Config::get('url') . '/api.json';
            } else {
                $api_endpoints[$url]['resource'] = Config::get('url') . '/api/' . $page_variables['url'] . '.json';
                $item_data['resource'] = Config::get('url') . '/api/' . $page_variables['url'] . '.json';
            }

            $parent_url = implode('/', $path);

            if (!empty($url)) {
                if (!isset($api_endpoints[$parent_url])) {
                    $api_endpoints[$parent_url] = [];
                }

                if (!isset($api_endpoints[$parent_url]['items'])) {
                    $api_endpoints[$parent_url]['items'] = [];
                }

                $api_endpoints[$parent_url]['items'][] = $item_data;
            }
        }

        $build_path = Config::get('path.public') . '/' . Config::get('path.api') . '/';

        if(!is_dir($build_path)) {
            mkdir($build_path, 0777, true);
        }

        foreach ($api_endpoints as $url => $data) {
            $path = explode('/', $url);
            array_pop($path);

            $dir = [];
            foreach ($path as $part) {
                $dir = implode('/', array_merge($dir, [$part]));

                if (!file_exists($build_path . $dir)) {
                    mkdir($build_path . $dir);
                }
            }

            if (isset($data['items'])) {
                $items = $data['items'];
                unset($data['items']);
                $data['items'] = $items;
            }

            if (empty($url)) {
                file_put_contents($build_path . '/../api.json', json_encode($data));
            } else {
                file_put_contents($build_path . $url . '.json', json_encode($data));
            }
        }

        return $this;
    }
}
