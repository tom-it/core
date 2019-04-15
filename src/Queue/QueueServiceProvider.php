<?php

namespace Flarum\Queue;

use Flarum\Console\Event\Configuring;
use Flarum\Foundation\AbstractServiceProvider;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Queue\Connectors\SyncConnector;
use Illuminate\Queue\Console as Commands;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\Worker;

class QueueServiceProvider extends AbstractServiceProvider
{
    protected $commands = [
        Commands\FailedTableCommand::class,
        Commands\FlushFailedCommand::class,
        Commands\ForgetFailedCommand::class,
        Commands\ListenCommand::class,
        Commands\ListFailedCommand::class,
        Commands\RestartCommand::class,
        Commands\RetryCommand::class,
        Commands\TableCommand::class,
        Commands\WorkCommand::class,
    ];

    public function register()
    {
        $this->app->singleton('queue.connection', function ($app) {
            return $app['queue']->connection();
        });

        $this->app->singleton('queue', function($app) {
            $manager = new QueueManager($app);

            $manager->addConnector('sync', new SyncConnector);

            return $manager;
        });

        $this->app->singleton('queue.worker', function ($app) {
            return new Worker(
                $app['queue'], $app['events'], $app[ExceptionHandler::class]
            );
        });

        $this->registerCommands();
    }

    protected function registerCommands()
    {
        $this->app['events']->listen(Configuring::class, function (Configuring $event) {
            if (! in_array($event->app['config']->get('queue.default'), ['sync', 'null'])) {
                foreach ($this->commands as $command) {
                    $event->addCommand($command);
                }
            }
        });
    }
}