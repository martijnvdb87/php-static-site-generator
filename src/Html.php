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
        $GLOBALS['progress_percentage_html'] = 0.75 / count($files);
        
        foreach (File::getContent() as $file) {
            Page::create($file)->build();
            
            $GLOBALS['progress_current'] += $GLOBALS['progress_percentage_html'];
            $GLOBALS['progress']->set($GLOBALS['progress_current']);
        }

        return $this;
    }
}
