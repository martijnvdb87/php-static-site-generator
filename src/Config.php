<?php

namespace Martijnvdb\StaticSiteGenerator;

use Symfony\Component\Yaml\Yaml;

class Config
{
    private $path = __DIR__ . '/../config.yaml';
    private $exists = false;
    private $config = [];

    public function __construct()
    {
        if(!$this->exists && file_exists($this->path)) {
            $this->config = Yaml::parse(file_get_contents($this->path));
            $this->exists = true;
        }
    }

    public static function create(): self
    {
        $config = new self;
        return $config;
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    public function get(string $key): ?string
    {
        if(isset($this->config[$key])) {
            return $this->config[$key];
        }

        return null;
    }

    public function set(string $key, string $value): self
    {
        $this->config[$key] = $value;

        return $this;
    }

    public function build(): self
    {
        file_put_contents($this->path, Yaml::dump($this->config));

        return $this;
    }
}