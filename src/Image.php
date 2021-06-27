<?php

namespace Martijnvdb\StaticSiteGenerator;

use Martijnvdb\ImageResize\ImageResize;

class Image
{
    private static $images = [];
    private static $target_sizes = [240, 480, 960, 1920];

    private $source_path;
    private $source_width;
    private $source_height;
    private $source_extension;

    private static $public_path = __DIR__ . '/../public';

    private $id;
    private $alt;
    private $url;

    public function __construct(string $source_path, ?string $alt = null)
    {
        $this->source_path = __DIR__ . '/../' . $source_path;

        $this->id = md5($this->source_path);
        $this->alt = $alt;

        $pathinfo = pathinfo($this->source_path);
        $this->source_extension = $pathinfo['extension'];

        list($this->source_width, $this->source_height) = getimagesize($this->source_path);
    }

    public static function create(string $source_path, ?string $alt = null): self
    {
        if(isset(self::$images[$source_path])) {
            return self::$images[$source_path];
        }

        $image = new self($source_path, $alt);
        self::$images[$source_path] = $image;

        return $image;
    }

    public static function getTargetSizes(): array
    {
        return Config::get('images.sizes') ?? [];
    }

    public function getHtml(): string
    {
        $output = '';

        $sizes = Image::getTargetSizes();

        if(empty($sizes)) {
            return $output;
        }
        
        $output .= '<picture>';
        
        foreach($sizes as $width) {
            if($this->source_width < $width) {
                break;
            }

            $uri = Config::get('url') . "/assets/images/{$this->id}-{$width}w.{$this->source_extension}";
            $output .= "<source srcset='$uri' media='(max-width: {$width}px)'>";
        }

        $output .= '<img srcset="' . $uri . '" alt="' . $this->alt . '">';
        $output .= '</picture>';

        return $output;
    }

    public function build(): self
    {
        foreach(Image::getTargetSizes() as $width) {
            if($this->source_width < $width) {
                break;
            }

            $export_path = self::$public_path . "/assets/images/{$this->id}-{$width}w.{$this->source_extension}";
            ImageResize::get($this->source_path)->setWidth($width)->export($export_path);
        }

        return $this;
    }

    public static function all(): array
    {
        return self::$images;
    }
}
