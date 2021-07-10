<?php

namespace Martijnvdb\StaticSiteGenerator;

use Martijnvdb\StaticSiteGenerator\Page;
use Martijnvdb\StaticSiteGenerator\Config;

class File
{
    public static function getContent(): array
    {
        $files = self::getFilesFromPath(__DIR__ . '/../' . Config::get('path.content'), false, true);

        $files_variables = [];

        foreach($files as $file) {
            $variables = Page::create($file)->getVariables();

            $files_variables[] = $variables;
        }

        $dates = array_column($files_variables, 'date');

        array_multisort($files_variables, SORT_DESC, $dates);

        return array_column($files_variables, 'source_path_relative');
    }

    private static function getFilesFromPath($path, $show_dirs = false, $hide_hidden = false, $orignal_path = null): array
    {
        $path = substr($path, -1) === '/' ? $path : $path . '/';

        if (empty($orignal_path)) {
            $orignal_path = $path;
        }

        $files_list = [];

        $files = scandir($path);

        foreach ($files as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }

            if (is_dir("{$path}{$file}")) {
                if ($hide_hidden && in_array(substr($file, 0, 1), ['_', '.'])) {
                    continue;
                }

                $files_list = array_merge($files_list, self::getFilesFromPath("{$path}{$file}/", $show_dirs, $hide_hidden, $orignal_path));

                if ($show_dirs) {
                    $files_list[] = substr("{$path}{$file}", strlen($orignal_path));
                }
            } else {
                $files_list[] = substr("{$path}{$file}", strlen($orignal_path));
            }
        }

        return $files_list;
    }

    public static function resetPublicDir(): void
    {
        if (!file_exists(Config::get('path.public'))) {
            mkdir(Config::get('path.public'));
        }

        $files = self::getFilesFromPath(Config::get('path.public'), true);

        foreach ($files as $file) {
            if (is_dir(Config::get('path.public') . '/' . $file)) {
                rmdir(Config::get('path.public') . '/' . $file);
            } else {
                unlink(Config::get('path.public') . '/' . $file);
            }
        }
    }
}
