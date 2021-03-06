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
            $page_variables = Page::create($file)->getVariables();

            $url = $page_variables['relative_url'];

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

            $api_endpoints[$url]['variables'] = $page_variables;

            $api_endpoints[$url]['url'] = $page_variables['url'];
            $item_data['url'] = $page_variables['url'];

            if (empty($url)) {
                $api_endpoints[$url]['resource'] = Config::get('url') . '/api.json';
                $item_data['resource'] = Config::get('url') . '/api.json';
            } else {
                $api_endpoints[$url]['resource'] = Config::get('url') . '/api/' . $page_variables['relative_url'] . '.json';
                $item_data['resource'] = Config::get('url') . '/api/' . $page_variables['relative_url'] . '.json';
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

        if (!is_dir($build_path)) {
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

                // Move items to bottom of the object
                $items = $data['items'];
                unset($data['items']);

                $sort_type = false;
                $sort_asc = false;

                if(
                    isset($data['variables']['paginate']) &&
                    isset($data['variables']['paginate']['sort']) &&
                    isset($data['variables']['paginate']['sort']['type'])
                ) {
                    $sort_asc = isset($data['variables']['paginate']['sort']['asc']) ? $data['variables']['paginate']['sort']['asc'] : false;
                    $sort_type = $data['variables']['paginate']['sort']['type'];
                }

                if($sort_type) {
                    array_multisort(array_column($items, $sort_type), SORT_DESC, SORT_REGULAR, $items);
    
                    if($sort_asc) {
                        $items = array_reverse($items);
                    }
                }

                $data['items'] = $items;
            }

            unset($data['variables']);

            if (empty($url)) {
                file_put_contents($build_path . '/../api.json', json_encode($data));
            } else {
                file_put_contents($build_path . $url . '.json', json_encode($data));
            }
        }

        return $this;
    }
}
