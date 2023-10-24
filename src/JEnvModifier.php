<?php

namespace JobMetric\EnvModifier;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;

class JEnvModifier
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
    private string $envPath;

    /**
     * Create a new EnvModifier instance.
     *
     * @param Application $app
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->envPath = base_path('.env');
    }

    /**
     * set env file path
     *
     * @param string $path
     *
     * @return void
     */
    public function setPath(string $path): void
    {
        $this->envPath = $path;
    }

    /**
     * set env data
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $envFile = file_get_contents($this->envPath);

        if (preg_match("/\n${key}=.*$/", $envFile)) {
            $envFile = preg_replace("/\n${key}=.*$/", "\n${key}=${value}", $envFile);
        } else {
            $envFile .= "\n${key}=${value}";
        }

        file_put_contents($this->envPath, $envFile);
    }

    /**
     * delete env data
     *
     * @param string $key
     *
     * @return void
     */
    public function delete(string $key): void
    {
        $envFile = file_get_contents($this->envPath);
        $envFile = preg_replace("/\n${key}=.*$/", '', $envFile);
        file_put_contents($this->envPath, $envFile);
    }
}
