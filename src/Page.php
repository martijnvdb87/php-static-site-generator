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

    private $path;

    private $paginate = false;
    private $paginate_type;
    private $paginate_amount = 10;
    private $paginate_skip = 0;
    private $paginate_url = 'page';

    private $source_content;
    private $source_parsed;
    
    private $templates_path = __DIR__ . '/../templates';
    private $public_path = __DIR__ . '/../public';

    private $template_loader;

    public function __construct(string $source_path_relative)
    {
        $this->setPath($source_path_relative);
        $this->template_loader = new FilesystemLoader($this->templates_path);
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

    public function getSkip(): int
    {
        return $this->paginate_skip;
    }

    public function setSkip(int $skip): int
    {
        $this->paginate_skip = $skip;
        return $this->paginate_skip;
    }

    public function getUrl(): string
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

        if(isset($variables['url'])) {
            if(substr($variables['url'], 0, 1) === '/') {
                $parts = [substr($variables['url'], 1)];

            } else {
                array_pop($parts);
                $parts[] = $variables['url'];
            }

            $this->url = implode('/', $parts);

        } else {
            if(sizeof($parts) === 1 && $parts[0] === 'index') {
                array_pop($parts);
            }

            $this->url = implode('/', $parts);
        }

        return $this->url;
    }

    public function getPath(): array
    {
        if(!isset($this->path)) {
            $this->path = explode('/', $this->getUrl());
        }

        return $this->path;
    }

    private function getTemplate(): string
    {
        if(isset($this->template)) {
            return $this->template;
        }
        
        $variables = $this->getVariables();

        if(isset($variables['template'])) {
            if(substr($variables['template'], -5) === '.html') {
                $this->template = $variables['template'];
            } else {
                $this->template = $variables['template'] . '.html';
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

    public function setLoop(array $items): Page
    {
        foreach($items as $item) {
            $this->variables['loop'][] = $item->getTemplateVariables();
        }
        
        return $this;
    }

    public function getVariables(): array
    {
        if(!isset($this->variables)) {
            $this->variables = (array) $this->getSourceParsed()->getYAML();

            if(!isset($this->variables['title'])) {
                $parts = explode('/', $this->getUrl());
                $title = array_pop($parts);

                $title = ucwords($title);
                $title = str_replace('-', ' ', $title);

                $this->variables['title'] = $title;
            }

            if(!isset($this->variables['date'])) {
                $date = filemtime($this->getSourcePathAbsolute());
                $this->variables['date'] = $date;
            }
        }

        return $this->variables;
    }

    public function getPaginate(): array
    {
        $variable = $this->getVariables();

        if(isset($variable['paginate'])) {
            $this->paginate = true;
        }

        if(isset($variable['paginate']['type'])) {
            $this->paginate_type = $variable['paginate']['type'];
        }

        if(isset($variable['paginate']['amount'])) {
            $this->paginate_amount = (int) $variable['paginate']['amount'];
        }

        if(isset($variable['paginate']['skip'])) {
            $this->paginate_skip = (int) $variable['paginate']['skip'];
        }

        if(isset($variable['paginate']['url'])) {
            $this->paginate_url = $variable['paginate']['url'];
        }

        return [
            'paginate' => $this->paginate,
            'type' => $this->paginate_type,
            'amount' => $this->paginate_amount,
            'skip' => $this->paginate_skip,
            'url' => $this->paginate_url
        ];
    }

    private function getHtml(): string
    {
        if(!isset($this->html)) {
            $this->html = $this->getSourceParsed()->getContent();
        }

        return $this->html;
    }

    public function getTemplateVariables(): array
    {
        return array_merge(
            $this->getVariables(),
            ['content' => $this->getHtml()]
        );
    }

    public function build(?string $custom_url = null): void
    {
        $twig = new Environment($this->template_loader);
        $template = $twig->load($this->getTemplate());
        $content = $template->render($this->getTemplateVariables());

        $url = isset($custom_url) ? $custom_url : $this->getUrl();

        $dirs = explode('/', $url);

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