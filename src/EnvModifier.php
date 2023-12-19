<?php

namespace JobMetric\EnvModifier;

use Illuminate\Contracts\Foundation\Application;
use JobMetric\EnvModifier\Exceptions\EnvFileNotFoundException;

class EnvModifier
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * The env file path.
     */
    private string $filePath;

    /**
     * Create a new EnvModifier instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->filePath = base_path('.env');
    }

    /**
     * set env file path
     *
     * @param string $path
     *
     * @return static
     * @throws EnvFileNotFoundException
     */
    public function setPath(string $path): static
    {
        if (!file_exists($path)) {
            throw new EnvFileNotFoundException($path);
        }

        $this->filePath = $path;

        return $this;
    }

    /**
     * get single env data
     *
     * @param string $key
     *
     * @return string
     * @throws EnvFileNotFoundException
     */
    private function getKey(string $key): string
    {
        $contentFile = $this->getContent();

        preg_match("/^{$key}=(.*)$/m", $contentFile, $matches);

        return trim($matches[1] ?? '');
    }

    /**
     * get data in env data
     *
     * @param $keys
     *
     * @return array
     */
    public function get(...$keys): array
    {
        $data = [];
        foreach ($keys as $key) {
            $data = array_merge($data, collect($key)->flatMap(function ($item) {
                return [$item => $this->getKey($item)];
            })->toArray());
        }

        return $data;
    }

    /**
     * set single env data
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     * @throws EnvFileNotFoundException
     */
    private function setKey(string $key, mixed $value): void
    {
        $contentFile = $this->getContent();

        if (preg_match("/^{$key}=/m", $contentFile)) {
            $contentFile = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $contentFile);
        } else {
            $contentFile .= PHP_EOL . "{$key}={$value}";
        }

        file_put_contents($this->filePath, $contentFile);
    }

    /**
     * set list of data in env data
     *
     * @param array $data
     *
     * @return static
     * @throws EnvFileNotFoundException
     */
    public function set(array $data): static
    {
        collect($data)->each(function ($value, $key) {
            $this->setKey($key, $value);
        });

        return $this;
    }

    /**
     * delete env data
     *
     * @param string $key
     *
     * @return void
     * @throws EnvFileNotFoundException
     */
    private function deleteKey(string $key): void
    {
        $contentFile = $this->getContent();

        $contentFile = preg_replace("/^{$key}=.*/m", '', $contentFile);

        file_put_contents($this->filePath, $contentFile);
    }

    /**
     * get list of data in env data
     *
     * @param $keys
     *
     * @return static
     * @throws EnvFileNotFoundException
     */
    public function delete(...$keys): static
    {
        foreach ($keys as $key) {
            collect($key)->each(function ($item) {
                $this->deleteKey($item);
            });
        }

        return $this;
    }

    /**
     * has key in env data
     *
     * @param string $key
     *
     * @return bool
     * @throws EnvFileNotFoundException
     */
    public function has(string $key): bool
    {
        $contentFile = $this->getContent();

        return preg_match("/^{$key}=(.*)$/m", $contentFile);
    }

    /**
     * get env content
     *
     * @return string
     * @throws EnvFileNotFoundException
     */
    private function getContent(): string
    {
        if (!file_exists($this->filePath)) {
            throw new EnvFileNotFoundException($this->filePath);
        }

        return file_get_contents($this->filePath);
    }
}
