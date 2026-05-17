<?php

namespace OperationGpt;

use Illuminate\Support\ServiceProvider;

class OperationGptServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/Config/config.php', 'operation-gpt');
        $this->mergeConfigFrom(__DIR__.'/Config/prompts.php', 'operation-gpt-prompts');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/Config/config.php' => config_path('operation-gpt.php'),
            __DIR__.'/Config/prompts.php' => config_path('operation-gpt-prompts.php'),
        ], 'config');

        $this->loadRoutesFrom(__DIR__.'/Routes/api.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'operation-gpt');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/operation-gpt'),
        ], 'views');

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/operation-gpt'),
        ], 'public');
    }
}