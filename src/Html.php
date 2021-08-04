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
        if(!isset(self::$template_loader)) {
            self::$template_loader = new FilesystemLoader(__DIR__ . '/../' . Config::get('path.templates'));
        }

        return self::$template_loader;
    }

    private function buildSingle(Page $page): void
    {
        $twig = new Environment(self::getTemplateLoader());
        $template = $twig->load($page->getTemplate());
        $content = $template->render($page->getVariables());
        
        $url = isset($custom_url) ? $custom_url : $page->getRelativeUrl();

        $dirs = explode('/', $url);

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

    private function buildPagination(Page $page): void
    {
        $variables = $page->getVariables();

        $paginate_items = $variables['paginate']['items'];
        $amount_per_page = $variables['paginate']['amount'];
        $skip = $variables['paginate']['skip'];
        $total_items = count($paginate_items) - $skip;

        $total_pages = ceil($total_items / $amount_per_page);

        for($i = 0; $i < $total_pages; $i++) {

            $child_variables = $page->getVariables();
            $child_variables['paginate']['items'] = [];

            for($x = 0; $x < count($paginate_items); $x++) {
                $current_child = ($i * $amount_per_page) + $x + $skip;

                if(isset($paginate_items[$current_child])) {
                    $child_variables['paginate']['items'][] = $paginate_items[$current_child];
                }
            }

            $twig = new Environment(self::getTemplateLoader());
            $template = $twig->load($page->getTemplate());
            $content = $template->render($child_variables);
            
            $url = isset($custom_url) ? $custom_url : $page->getRelativeUrl();
    
            $dirs = explode('/', $url);

            if($i > 0) {
                $dirs[] = 'page';
                $dirs[] = ($i + 1);
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

            if($page->hasPaginate()) {
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
