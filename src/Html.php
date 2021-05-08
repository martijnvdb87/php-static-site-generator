<?php

namespace Martijnvdb\StaticSiteGenerator;

use Martijnvdb\StaticSiteGenerator\Page;

class Html
{
    public function __construct()
    {
        foreach (File::getContent() as $file) {
            Page::create($file)->build();
        }
    }

    public static function build(): self
    {
        $html = new self;
        return $html;
    }
}
