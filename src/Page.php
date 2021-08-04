<?php

namespace Martijnvdb\StaticSiteGenerator;

use Mni\FrontYAML\Parser;
use Mni\FrontYAML\Document;

use Martijnvdb\StaticSiteGenerator\Config;
use Martijnvdb\StaticSiteGenerator\File;
use Martijnvdb\StaticSiteGenerator\Image;

class Page
{
    private static $pages = [];

    private $template;
    private $title;
    private $type;
    private $date;
    private $content;

    private $user_settings;

    private $relative_url;
    private $absolute_url;
    
    private $source_content;
    private $source_parsed;

    private $source_path_relative;
    private $source_path_absolute;

    private $template_loader;

    private $paginate;
    private $paginate_items;

    private $is_paginate_item = false;

    public function __construct(string $source_path_relative)
    {
        $this->setSourcePathRelative($source_path_relative);
    }

    public static function create(string $source_path_relative): self
    {
        $pages = self::getAll();

        if(isset($pages[$source_path_relative])) {
            return $pages[$source_path_relative];
        }

        $page = new Page($source_path_relative);
        return $page;
    }

    public static function getAll(): array
    {
        if(empty(self::$pages)) {
            $content_files = File::getContent();

            foreach ($content_files as $file) {
                self::$pages[$file] = new self($file);
            }
        }

        return self::$pages;
    }
    
    private function setSourcePathRelative(string $source_path_relative): self
    {
        $this->source_path_relative = $source_path_relative;

        return $this;
    }

    private function getSourcePathRelative(): string
    {
        return $this->source_path_relative;
    }

    private function getSourcePathAbsolute(): string
    {
        if (!isset($this->source_path_absolute)) {
            $this->source_path_absolute = implode('/', array_filter([
                __DIR__,
                '..',
                Config::get('path.content'),
                $this->getSourcePathRelative()
            ]));
        }

        return $this->source_path_absolute;
    }

    private function getSourceContent(): string
    {
        if (!isset($this->source_content)) {
            $this->source_content = file_get_contents($this->getSourcePathAbsolute());
        }

        return $this->source_content;
    }

    private function getSourceParsed(): Document
    {
        if (!isset($this->source_parsed)) {
            $parser = new Parser();

            $source_content = $this->getSourceContent();

            $source_content = preg_replace_callback('/\!\[(.+?)\]\((.+?)\)/', function($matches) {
                return Image::create($matches[2], $matches[1])->getHtml();
            }, $source_content);

            $this->source_parsed = $parser->parse($source_content);
        }

        return $this->source_parsed;
    }

    private function getContent(): string
    {
        if(!isset($this->content)) {
            $this->content = $this->getSourceParsed()->getContent();
        }

        return $this->content;
    }

    private function getUserSettings()
    {
        if(!isset($this->user_settings)) {
            $this->user_settings = (array) $this->getSourceParsed()->getYAML();
        }

        return $this->user_settings;
    }

    private function getUserSetting(string $key)
    {
        $user_settings = $this->getUserSettings();
        
        $parts = explode('.', $key);

        while ($part = array_shift($parts)) {
            if (!isset($user_settings[$part])) {
                return null;
            }

            $user_settings = $user_settings[$part];
        }

        return $user_settings;
    }

    public function getRelativeUrl(): string
    {
        if(!isset($this->relative_url)) {
            $this->relative_url = $this->getUserSetting('url');

            if(is_null($this->relative_url)) {
                $parts = explode('.', $this->getSourcePathRelative());
                array_pop($parts);
                $this->relative_url = implode('.', $parts);
            }
        }
        
        return $this->relative_url;
    }

    private function getTitle(): string
    {
        if(!isset($this->title)) {
            $this->title = $this->getUserSetting('title');

            if(is_null($this->title)) {
                $parts = explode('.', $this->getSourcePathRelative());
                array_pop($parts);
                $title = array_pop(explode('/', implode('.', $parts)));

                $this->title = ucfirst(str_replace('-', ' ', $title));
            }
        }
        
        return $this->title;
    }

    public function getType(): ?string
    {
        if(!isset($this->type)) {
            $this->type = $this->getUserSetting('type');

            if(is_null($this->type)) {
                $parts = explode('.', $this->getSourcePathRelative());
                array_pop($parts);
                $parts = explode('/', implode('.', $parts));
                array_pop($parts);

                if(!empty($parts)) {
                    $this->type = implode('/', $parts);
                }
            }
        }
        
        return $this->type;
    }

    private function getDate(): string
    {
        if(!isset($this->date)) {
            $date = $this->getUserSetting('date');

            if(empty($date)) {
                $date = filemtime($this->getSourcePathAbsolute());
            }

            if(((string) (int) $date === $date) && ($date <= PHP_INT_MAX) && ($date >= ~PHP_INT_MAX)) {
                $date = strtotime($date);
            }

            $this->date = $date;
        }

        return $this->date;
    }

    public function getTemplate(): ?string
    {
        if(!isset($this->template)) {
            $template = $this->getUserSetting('template');

            if(!empty($template) && substr($template, -5) !== '.html') {
                $template .= '.html';
            }

            if(empty($template)) {
                $parts = explode('/', $this->getSourcePathRelative());
                array_pop($parts);
    
                if(sizeof($parts) > 0) {
                    $template = implode('.', $parts) . '.html';
                }
            }
            
            if(empty($template) || !file_exists(Config::get('path.templates') . "/{$template}")) {
                $template = 'index.html';
            }

            $this->template = $template;
        }

        return $this->template;
    }

    private function isPaginateItem(bool $is_paginate_item = true): self
    {
        $this->is_paginate_item = $is_paginate_item;

        return $this;
    }

    private function getPaginateItems(string $type, ?string $sort = null, bool $asc = false): array
    {
        if(!isset($this->paginate_items)) {
            
            $this->paginate_items = [];
            
            $pages = self::getAll();

            $pages_variables = [];

            foreach($pages as $page) {
                if($page->getType() === $type) {                    
                    $variables = $page->getVariables();
                    $variables['self'] = $page;
                    $pages_variables[] = $variables;
                }
            }
    
            $sort_item = array_column($pages_variables, $sort);
    
            array_multisort($pages_variables, SORT_DESC, $sort_item);
    
            $this->paginate_items = array_column($pages_variables, 'self');
    
            if($asc) {
                $this->paginate_items = array_reverse($this->paginate_items);
            }
        }
        
        return $this->paginate_items;
    }

    private function getPaginate()
    {
        if(!isset($this->paginate)) {
            if($this->getUserSetting('paginate') && $this->getUserSetting('paginate.type')) {
                $this->paginate = [
                    'type' => $this->getUserSetting('paginate.type'),
                    'amount' => $this->getUserSetting('paginate.amount') ?? 10,
                    'skip' => $this->getUserSetting('paginate.skip') ?? 0,
                    'url' => $this->getUserSetting('paginate.url') ?? 'page',
                    'sort' => [
                        'type' => $this->getUserSetting('paginate.sort.type') ?? 'date',
                        'asc' => (bool) $this->getUserSetting('paginate.sort.asc'),
                    ],
                    'items' => $this->getPaginateItems($this->getUserSetting('paginate.type'), $this->getUserSetting('paginate.sort.type') ?? 'date', (bool) $this->getUserSetting('paginate.sort.asc'))
                ];
            }
        }

        return $this->paginate;
    }

    public function hasPaginate() {
        return !empty($this->getPaginate());
    }

    public function getVariables(): array
    {
        $variables = [
            'title' => $this->getTitle(),
            'url' => $this->getRelativeUrl(),
            'type' => $this->getType(),
            'date' => $this->getDate(),
            'content' => $this->getContent(),
            'template' => $this->getTemplate(),
            'source_path_relative' => $this->getSourcePathRelative(),
            'source_path_absolute' => $this->getSourcePathAbsolute(),
            'paginate' => $this->getPaginate()
        ];

        return $variables;
    }
}
