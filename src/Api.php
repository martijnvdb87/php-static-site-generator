<?php

namespace Martijnvdb\StaticSiteGenerator;

class Api
{
    private $api_dir = __DIR__ . '/../public/api/';

    public function __construct()
    {
        $api_endpoints = [];

        foreach (File::getContent() as $file) {
            $page_variables = Page::create($file)->getTemplateVariables();

            $url = $page_variables['url'];
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


            $api_endpoints[$url]['url'] = '/' . $page_variables['url'];
            $item_data['url'] = '/' . $page_variables['url'];

            if (empty($url)) {
                $api_endpoints[$url]['resource'] = '/api.json';
                $item_data['resource'] = '/api.json';
            } else {
                $api_endpoints[$url]['resource'] = '/api/' . $page_variables['url'] . '.json';
                $item_data['resource'] = '/api/' . $page_variables['url'] . '.json';
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

        mkdir($this->api_dir);

        foreach ($api_endpoints as $url => $data) {
            $path = explode('/', $url);
            array_pop($path);

            $dir = [];
            foreach ($path as $part) {
                $dir = implode('/', array_merge($dir, [$part]));

                if (!file_exists($this->api_dir . $dir)) {
                    mkdir($this->api_dir . $dir);
                }
            }

            if (isset($data['items'])) {
                $items = $data['items'];
                unset($data['items']);
                $data['items'] = $items;
            }

            if (empty($url)) {
                file_put_contents($this->api_dir . '/../api.json', json_encode($data));
            } else {
                file_put_contents($this->api_dir . $url . '.json', json_encode($data));
            }
        }
    }

    public static function build(): self
    {
        $api = new self;
        return $api;
    }
}
