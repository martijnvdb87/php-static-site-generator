<?php

namespace Martijnvdb\StaticSiteGenerator;

class Files {
    public static function getContent($path = null)
    {
        return self::getFilesFromPath(__DIR__ . '/../content/', false, true);
    }

    private static function getFilesFromPath($path, $show_dirs = false, $hide_hidden = false, $orignal_path = null)
    {
        if(empty($orignal_path)) {
            $orignal_path = $path;
        }

        $files_list = [];

        $files = scandir($path);
        
        foreach($files as $file) {
            if(in_array($file, ['.', '..'])) {
                continue;
            }

            if(is_dir("{$path}{$file}")) {
                if($hide_hidden && substr($file, 0, 1) === '_') {
                    continue;
                }

                $files_list = array_merge($files_list, self::getFilesFromPath("{$path}{$file}/", $show_dirs, $hide_hidden, $orignal_path));

                if($show_dirs) {
                    $files_list[] = substr("{$path}{$file}", strlen($orignal_path));
                }
                
            } else {
                $files_list[] = substr("{$path}{$file}", strlen($orignal_path));
            }

        }

        return $files_list;
    }

    public static function deleteContent()
    {

    }
}