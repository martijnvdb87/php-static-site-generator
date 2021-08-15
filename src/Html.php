<?php

namespace Martijnvdb\StaticSiteGenerator;

use Martijnvdb\StaticSiteGenerator\Page;

use Twig\Loader\FilesystemLoader;
use Twig\Environment;

class Html
{
    private static $template_loader;

    public function __construct()
    {
    }

    public static function create(): self
    {
        return new self;
    }

    private static function getTemplateLoader(): FilesystemLoader
    {
        if (!isset(self::$template_loader)) {
            self::$template_loader = new FilesystemLoader(__DIR__ . '/../' . Config::get('path.templates'));
        }

        return self::$template_loader;
    }

    private function buildSingle(Page $page): void
    {
        $variables = $page->getVariables();

        $url = isset($custom_url) ? $custom_url : $page->getRelativeUrl();

        $dirs = explode('/', $url);

        $file_path = [Config::get('path.public')];

        $page->getType();

        $files = File::getContent();

        if(!empty($page->getType())) {
            $same_type_items = [];
            $sort = null;
            $main_type_item = null;
    
            foreach($files as $file) {
                $file_page = Page::create($file);
    
                if($file_page->getType() === $page->getType()) {
                    $same_type_items[] = $file_page->getVariables();
                }

                if(empty($main_type_item)) {
                    $file_page_variables = $file_page->getVariables();
                    
                    if(
                        isset($file_page_variables['paginate']) &&
                        isset($file_page_variables['paginate']['type']) &&
                        isset($file_page_variables['paginate']['sort']) &&
                        $file_page_variables['paginate']['type'] === $page->getType()
                    ) {
                        $main_type_item = $file_page->getVariables();
                        $sort = $file_page_variables['paginate']['sort'];
                    }
                }
            }

            if(!empty($sort)) {
                array_multisort(array_column($same_type_items, $sort['type']), SORT_DESC, SORT_REGULAR, $same_type_items);

                if($sort['asc']) {
                    $same_type_items = array_reverse($same_type_items);
                }
            }
            
            $found_current = false;
            $items_before_current = [];
            $items_after_current = [];

            foreach($same_type_items as $item) {
                if($item['source_path_relative'] === $page->getSourcePathRelative()) {
                    $found_current = true;
                    continue;
                }

                if($found_current) {
                    $items_after_current[] = $item;
                    
                } else {
                    $items_before_current[] = $item;
                }
            }

            if(!empty($main_type_item)) {
                $variables['overview_page'] = $main_type_item;
            }

            $variables['next_item'] = count($items_after_current) > 0 ? $items_after_current[0] : null;
            $variables['previous_item'] = count($items_before_current) > 0 ? $items_before_current[(count($items_before_current) - 1)] : null;

            $variables['items_before_current'] = $items_before_current;
            $variables['items_after_current'] = $items_after_current;
        }

        foreach ($dirs as $dir) {
            if (empty($dir)) {
                continue;
            }

            $file_path[] = $dir;

            $dir_path = implode('/', $file_path);

            if (!file_exists($dir_path)) {
                mkdir($dir_path);
            }
        }

        $file_path[] = 'index.html';

        $build_url = implode('/', $file_path);
        
        $twig = new Environment(self::getTemplateLoader());
        $template = $twig->load($page->getTemplate());
        $content = $template->render($variables);

        file_put_contents($build_url, $content);
    }

    private function buildPagination(Page $page): void
    {
        $variables = $page->getVariables();

        $paginate_items = $variables['paginate']['items'];
        $amount_per_page = $variables['paginate']['amount'];
        $skip = $variables['paginate']['skip'];
        $total_items = count($paginate_items) - $skip;

        $total_pages = ceil($total_items / $amount_per_page);

        for ($current_page = 1; $current_page <= $total_pages; $current_page++) {

            $child_variables = $page->getVariables();
            $child_variables['paginate']['items'] = [];

            for ($current_pagina_item = 0; $current_pagina_item < count($paginate_items); $current_pagina_item++) {
                if($amount_per_page <= $current_pagina_item) {
                    break;
                }

                $current_child = (($current_page - 1) * $amount_per_page) + $current_pagina_item + $skip;

                if (isset($paginate_items[$current_child])) {

                    $child_page_item = $paginate_items[$current_child]->getVariables();
                    $child_variables['paginate']['items'][] = $child_page_item;
                }
            }

            if($current_page == 2) {
                $child_variables['previous_page'] = $page->getAbsoluteUrl();

            } else if($current_page > 2) {
                $child_variables['previous_page'] = $page->getAbsoluteUrl() . '/' . Config::get('path.page')  . '/' . ($current_page - 1);
            }
            
            if($current_page < $total_pages) {
                $child_variables['next_page'] = $page->getAbsoluteUrl() . '/' . Config::get('path.page')  . '/' . ($current_page + 1);
            }

            $twig = new Environment(self::getTemplateLoader());
            $template = $twig->load($page->getTemplate());
            $content = $template->render($child_variables);

            $url = isset($custom_url) ? $custom_url : $page->getRelativeUrl();

            $dirs = explode('/', $url);

            if ($current_page > 1) {
                $dirs[] = Config::get('path.page');
                $dirs[] = $current_page;
            }

            $file_path = [Config::get('path.public')];

            foreach ($dirs as $dir) {
                if (empty($dir)) {
                    continue;
                }

                $file_path[] = $dir;

                $dir_path = implode('/', $file_path);

                if (!file_exists($dir_path)) {
                    mkdir($dir_path);
                }
            }

            $file_path[] = 'index.html';

            $build_url = implode('/', $file_path);

            file_put_contents($build_url, $content);
        }
    }

    public function build(): self
    {
        $files = File::getContent();

        $percentage_per_file = 0.2 / count($files);
        $start_percentage = 0;
        $current_percentage = $start_percentage;

        foreach ($files as $file) {
            $page = Page::create($file);

            if ($page->hasPaginate()) {
                $this->buildPagination($page);
            } else {
                $this->buildSingle($page);
            }

            $current_percentage += $percentage_per_file;

            $GLOBALS['progress']->set($current_percentage);
        }

        return $this;
    }
}
