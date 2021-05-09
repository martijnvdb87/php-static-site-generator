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
        foreach (File::getContent() as $file) {
            Page::create($file)->build();
        }

        return $this;
    }
}
