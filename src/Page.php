<?php

namespace Martijnvdb\StaticSiteGenerator;

use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use Mni\FrontYAML\Parser;
use Mni\FrontYAML\Document;

use Martijnvdb\StaticSiteGenerator\Config;
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

    private $variables;

    public function __construct(string $source_path_relative)
    {
        $this->setSourcePathRelative($source_path_relative);
    }

    public static function create(string $source_path_relative): self
    {
        if(isset(self::$pages[$source_path_relative])) {
            return self::$pages[$source_path_relative];
        }

        $page = new Page($source_path_relative);
        self::$pages[$source_path_relative] = $page;
        return $page;
    }

    private function getTemplateLoader(): ?FilesystemLoader
    {
        if(!isset($this->template_loader)) {
            $this->template_loader = new FilesystemLoader(__DIR__ . '/../' . Config::get('path.templates'));
        }

        return $this->template_loader;
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

        return $user_settings[$key] ?? null;
    }

    private function getRelativeUrl(): string
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

    private function getType(): ?string
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

            if(is_null($date)) {
                $date = filemtime($this->getSourcePathAbsolute());
            }

            $this->date = strtotime($date);
        }

        return $this->date;
    }

    public function getVariables(): array
    {
        $variables = [
            'title' => $this->getTitle(),
            'url' => $this->getRelativeUrl(),
            'type' => $this->getType(),
            'date' => $this->getDate(),
            'content' => $this->getContent(),
            'source_path_relative' => $this->getSourcePathRelative(),
            'source_path_absolute' => $this->getSourcePathAbsolute(),
        ];

        print_r($variables);

        return $variables;
    }

    public function build(): self
    {
        return $this;
    }
}
