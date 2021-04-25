<?php

namespace Martijnvdb\StaticSiteGenerator;

use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use Mni\FrontYAML\Parser;
use Mni\FrontYAML\Document;

class Page {
    private $source_path_relative;
    private $source_path_absolute;
    private $url;
    private $variables;
    private $html;
    private $template;

    private $source_content;
    private $source_parsed;
    
    private $templates_path = __DIR__ . '/../templates';
    private $public_path = __DIR__ . '/../public';

    public function __construct(string $source_path_relative)
    {
        $this->setPath($source_path_relative);
    }

    public static function create(string $source_path_relative): Page
    {
        $page = new Page($source_path_relative);
        return $page;
    }

    private function setPath(string $source_path_relative): Page
    {
        $this->source_path_relative = $source_path_relative;

        return $this;
    }

    private function getSourcePathRelative()
    {
        return $this->source_path_relative;
    }

    private function getSourcePathAbsolute()
    {
        if(!isset($this->source_path_absolute)) {
            $this->source_path_absolute = __DIR__ . "/../content/{$this->getSourcePathRelative()}";
        }

        return $this->source_path_absolute;
    }

    private function getSourceContent(): string
    {
        if(!isset($this->source_content)) {
            $this->source_content = file_get_contents($this->getSourcePathAbsolute());
        }

        return $this->source_content;
    }

    private function getSourceParsed(): Document
    {
        if(!isset($this->source_parsed)) {
            $parser = new Parser();
            $this->source_parsed = $parser->parse($this->getSourceContent());
        }
        
        return $this->source_parsed;
    }

    private function getUrl(): string
    {
        if(isset($this->url)) {
            return $this->url;
        }

        // Remove extension
        $parts = explode('.', $this->getSourcePathRelative());
        array_pop($parts);

        // Get URL parts
        $parts = explode('/', implode('.', $parts));

        $variables = $this->getVariables();

        if(isset($variables->url)) {
            if(substr($variables->url, 0, 1) === '/') {
                $parts = [substr($variables->url, 1)];

            } else {
                array_pop($parts);
                $parts[] = $variables->url;
            }

            $this->url = implode('/', $parts);

        } else {
            if($parts[sizeof($parts) - 1] === 'index') {
                array_pop($parts);
            }

            $this->url = implode('/', $parts);
        }

        return $this->url;
    }

    private function getTemplate(): string
    {
        if(isset($this->template)) {
            return $this->template;
        }
        
        $variables = $this->getVariables();

        if(isset($variables->template)) {
            if(substr($variables->template, -5) === '.html') {
                $this->template = $variables->template;
            } else {
                $this->template = $variables->template . '.html';
            }
        }

        if(!isset($this->template)) {
            $parts = explode('/', $this->getSourcePathRelative());
            array_pop($parts);
            
            if(sizeof($parts) > 0) {
                $this->template = implode('.', $parts) . '.html';   
            }
        }

        if(!isset($this->template) || !file_exists("{$this->templates_path}/{$this->template}")) {
            $this->template = 'index.html';
        }
        
        return $this->template;
    }

    private function getVariables(): object
    {
        if(!isset($this->variables)) {
            $this->variables = (object) $this->getSourceParsed()->getYAML();
        }

        return $this->variables;
    }

    private function getHtml(): string
    {
        if(!isset($this->html)) {
            $this->html = $this->getSourceParsed()->getContent();
        }

        return $this->html;
    }

    public function build(): void
    {
        $loader = new FilesystemLoader($this->templates_path);
        $twig = new Environment($loader);
        $template = $twig->load($this->getTemplate());
        $content = $template->render(['the' => 'variables', 'go' => 'here']);

        $dirs = explode('/', $this->getUrl());

        $file_path = [$this->public_path];

        foreach ($dirs as $dir) {
            $file_path[] = $dir;

            $dir_path = implode('/', $file_path);

            if (!file_exists($dir_path)) {
                mkdir($dir_path);
            }
        }

        $file_path[] = 'index.html';

        file_put_contents(implode('/', $file_path), $content);
    }
}