<?php

namespace Martijnvdb\StaticSiteGenerator;

use Martijnvdb\StaticSiteGenerator\Page;

class Html
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
        $files = File::getContent();
        
        foreach ($files as $file) {
            $page = Page::create($file);

            print_r($page->getVariables());
        }

        return $this;
    }
}
