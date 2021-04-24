<?php

namespace Martijnvdb\StaticSiteGenerator;

class Files {
    public static function getContent($path = null)
    {
        $content_path = __DIR__ . '/../content/';

        if(empty($path)) {
            $path = $content_path;
        }

        $content = [];

        $files = scandir($path);
        
        foreach($files as $file) {
            if(in_array($file, ['.', '..'])) {
                continue;
            }

            if(is_dir("{$path}{$file}")) {
                if(in_array(substr($file, 0, 1), ['_', '.', '@'])) {
                    continue;
                }

                $content = array_merge($content, self::getContent("{$path}{$file}/"));
                
            } else {
                $content[] = substr("{$path}{$file}", strlen($content_path));
            }

        }

        return $content;
    }
}