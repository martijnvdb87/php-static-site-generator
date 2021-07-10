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

    private $source_path_relative;
    private $source_path_absolute;

    private $variables;

    public function __construct(string $source_path_relative)
    {
        $this->setSourcePathRelative($source_path_relative);
        $this->template_loader = new FilesystemLoader(__DIR__ . '/../' . Config::get('path.templates'));
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

    private function getTemplate(): string
    {
        if (isset($this->template)) {
            return $this->template;
        }

        $variables = $this->getVariables();

        if (isset($variables['template'])) {
            if (substr($variables['template'], -5) === '.html') {
                $this->template = $variables['template'];
            } else {
                $this->template = $variables['template'] . '.html';
            }
        }

        if (!isset($this->template)) {
            $parts = explode('/', $this->getSourcePathRelative());
            array_pop($parts);

            if (sizeof($parts) > 0) {
                $this->template = implode('.', $parts) . '.html';
            }
        }

        if (!isset($this->template) || !file_exists(Config::get('path.templates') . "/{$this->template}")) {
            $this->template = 'index.html';
        }

        return $this->template;
    }

    public function setPaginatePages(int $current_page, int $last_page): self
    {
        $this->getVariables();

        $base = "{$this->getRelativeUrl()}/{$this->paginate_url}/";

        if ($last_page > 1) {
            $this->variables['paginate'] = [
                'first' => [
                    'number' => 1,
                    'url' => $this->getRelativeUrl()
                ],
                'previous' => [
                    'number' => $current_page > 1 ? $current_page - 1 : null,
                    'url' => $current_page == 2 ? $this->getRelativeUrl() : ($current_page > 1 ? $base . ($current_page - 1) : null)
                ],
                'current' => [
                    'number' => $current_page,
                    'url' => $base . $current_page
                ],
                'next' => [
                    'number' => $current_page < $last_page ? $current_page + 1 : null,
                    'url' => $current_page < $last_page ? $base . ($current_page + 1) : null
                ],
                'last' => [
                    'number' => $last_page,
                    'url' => $base . $last_page
                ]
            ];
        }

        return $this;
    }

    public function setPaginateLoop(array $items): self
    {
        $this->getVariables();
        $this->variables['paginate']['loop'] = [];

        foreach ($items as $item) {
            $this->variables['paginate']['loop'][] = $item->getTemplateVariables();
        }

        return $this;
    }
    
    public function getVariables(): array
    {
        if (!isset($this->variables)) {
            $this->variables = (array) $this->getSourceParsed()->getYAML();

            // Create url if no url is given
            if (!isset($this->variables['url'])) {
                $parts = explode('.', $this->getSourcePathRelative());
                array_pop($parts);

                $this->variables['url'] = implode('.', $parts);
            }

            // Create title if no title is given
            if(!isset($this->variables['title'])) {
                $parts = explode('.', $this->getSourcePathRelative());
                array_pop($parts);

                $this->variables['title'] = ucfirst(preg_replace('/[-_]/', '', implode('.', $parts)));
            }

            // Create date if no date is given
            if (!isset($this->variables['date'])) {
                $this->variables['date'] = filemtime($this->getSourcePathAbsolute());
            }

            $this->variables['source_path_relative'] = $this->getSourcePathRelative();
            $this->variables['source_path_absolute'] = $this->getSourcePathAbsolute();
        }

        return $this->variables;
    }

    public function getPaginate(): array
    {
        $variable = $this->getVariables();

        if (isset($variable['paginate'])) {
            $this->paginate = true;
        }

        if (isset($variable['paginate']['type'])) {
            $this->paginate_type = $variable['paginate']['type'];
        }

        if (isset($variable['paginate']['amount'])) {
            $this->paginate_amount = (int) $variable['paginate']['amount'];
        }

        if (isset($variable['paginate']['skip'])) {
            $this->paginate_skip = (int) $variable['paginate']['skip'];
        }

        if (isset($variable['paginate']['url'])) {
            $this->paginate_url = $variable['paginate']['url'];
        }

        if (isset($variable['paginate']['sort']) && isset($variable['paginate']['sort']['type'])) {
            $this->paginate_sort_type = $variable['paginate']['sort']['type'];
        }

        if (isset($variable['paginate']['sort']) && isset($variable['paginate']['sort']['asc'])) {
            $this->paginate_sort_asc = $variable['paginate']['sort']['asc'];
        }

        return [
            'paginate' => $this->paginate,
            'type' => $this->paginate_type,
            'amount' => $this->paginate_amount,
            'skip' => $this->paginate_skip,
            'url' => [
                // 'home' => self::getAbsoluteUrl(),
                // 'absolute' => self::getAbsoluteUrl($this->paginate_url),
                'relative' => $this->paginate_url,
            ],
            'sort' => [
                'type' => $this->paginate_sort_type,
                'asc' => $this->paginate_sort_asc
            ]
        ];
    }

    private function getHtml(): string
    {
        if (!isset($this->html)) {
            $this->html = $this->getSourceParsed()->getContent();
        }

        return $this->html;
    }

    public function getTemplateVariables(): array
    {
        return array_merge(
            $this->getVariables(),
            [
                'content' => $this->getHtml()
            ]
        );
    }

    public function paginatedBuild(): self
    {
        $files = File::getContent();

        $pages = [];

        foreach ($files as $file) {
            $page = Page::create($file);
            $pages[] = $page;
        }

        $paginate = $this->getPaginate();

        $children = [];

        foreach ($pages as $child) {
            if ($this === $child) {
                continue;
            }

            $path = $child->getPath();
            array_pop($path);

            if (implode('/', $path) === $paginate['type']) {
                $children[] = $child;
            }
        }

        usort($children, function (Page $a, Page $b) use ($paginate) {
            $paginate_type = $paginate['sort']['type'];
            $paginate_asc = $paginate['sort']['asc'];

            $a_vars = $a->getVariables();
            $b_vars = $b->getVariables();

            if ($paginate_asc) {
                return $a_vars[$paginate_type] > $b_vars[$paginate_type];
            }

            return $a_vars[$paginate_type] < $b_vars[$paginate_type];
        });

        $skip = $this->getSkip();

        $amount_pages = ceil((sizeof($children) - $skip) / $paginate['amount']);
        $amount_pages = max($amount_pages, 1);

        $page_url = $this->getRelativeUrl();

        for ($page_num = 1; $page_num <= $amount_pages; $page_num++) {
            $start = ($page_num - 1) * $paginate['amount'] + $skip;
            $end = $page_num * $paginate['amount'] + $skip;

            $paginate_loop = array_slice($children, $start, $end);

            $this->setPaginatePages($page_num, $amount_pages);

            if ($page_num === 1) {
                $this->setPaginateLoop($paginate_loop)->singleBuild();
            }

            if ($amount_pages > 1) {
                $this->setPaginateLoop($paginate_loop)->singleBuild("{$page_url}/{$paginate['url']}/{$page_num}");
            }
        }

        return $this;
    }

    public function singleBuild(?string $custom_url = null): self
    {
        $twig = new Environment($this->template_loader);
        $template = $twig->load($this->getTemplate());
        $content = $template->render($this->getTemplateVariables());

        $url = isset($custom_url) ? $custom_url : $this->getRelativeUrl();

        $dirs = explode('/', $url);

        $file_path = [__DIR__ . '/../' . Config::get('path.public')];

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

        return $this;
    }

    public function getIsBuilt(): bool
    {
        return $this->is_built;
    }

    public function setIsBuilt(bool $is_built): self
    {
        $this->is_built = $is_built;
        return $this;
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

    public function getSkip(): int
    {
        return $this->paginate_skip;
    }

    public function setSkip(int $skip): int
    {
        $this->paginate_skip = $skip;
        return $this->paginate_skip;
    }

    public function getAbsoluteUrl(): string
    {
        return implode('/', [
            rtrim(Config::get('url'), '/'),
            $this->getRelativeUrl()
        ]);
    }

    public function getRelativeUrl(): string
    {
        $variables = $this->getVariables();
        return $variables['url'];
    }

    public function getPath(): array
    {
        if (!isset($this->path)) {
            $this->path = explode('/', $this->getRelativeUrl());
        }

        return $this->path;
    }

    public function build(): self
    {
        $paginate = $this->getPaginate();

        $this->setIsBuilt(true);

        if($paginate['paginate']) {
            return $this->paginatedBuild();
        }

        return $this->singleBuild();
    }
}
