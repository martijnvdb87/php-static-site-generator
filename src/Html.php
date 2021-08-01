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

    public function build(): self
    {
        $files = File::getContent();
        
        foreach ($files as $file) {
            $page = Page::create($file);
            
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

        return $this;
    }
}
