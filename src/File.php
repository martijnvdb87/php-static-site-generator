<?php

namespace Martijnvdb\StaticSiteGenerator;

class File {
    private static $content_path = __DIR__ . '/../content';
    private static $public_path = __DIR__ . '/../public';

    public static function getContent($path = null)
    {
        return self::getFilesFromPath(self::$content_path, false, true);
    }

    private static function getFilesFromPath($path, $show_dirs = false, $hide_hidden = false, $orignal_path = null)
    {
        $path = substr($path, -1) === '/' ? $path : $path . '/';

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
                if($hide_hidden && in_array(substr($file, 0, 1), ['_', '.'])) {
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

    public static function resetPublicDir()
    {
        if(!file_exists(self::$public_path)) {
            mkdir(self::$public_path);
        }

        $files = self::getFilesFromPath(self::$public_path, true);

        foreach($files as $file) {
            if(is_dir(self::$public_path . '/' . $file)) {
                rmdir(self::$public_path . '/' . $file);

            } else {
                unlink(self::$public_path . '/' . $file);
            }
        }
    }
}