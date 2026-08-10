<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use RuntimeException;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $this->forceTestSafeDatabase($app);

        return $app;
    }

    /**
     * SAFETY GUARD — DO NOT REMOVE.
     *
     * admin-panel and api-service share one real MySQL database, and Feature
     * tests use RefreshDatabase (migrate:fresh) which DROPS ALL TABLES.
     *
     * When `php artisan config:cache` has been run, the cached
     * bootstrap/cache/config.php silently overrides every env var in
     * phpunit.xml — including DB_CONNECTION/DB_DATABASE — pointing tests at
     * the real MySQL database. This hook runs right after the app boots and
     * BEFORE RefreshDatabase migrates, forcing test-safe drivers no matter
     * what the cached config says. Explicit-connection migrations (e.g.
     * api_system_db) are covered by rewriting every non-sqlite connection.
     */
    private function forceTestSafeDatabase(Application $app): void
    {
        $config = $app['config'];

        $config->set('database.default', 'sqlite');
        $config->set('database.connections.sqlite.database', ':memory:');

        foreach (array_keys($config->get('database.connections', [])) as $name) {
            if ($config->get("database.connections.{$name}.driver") !== 'sqlite') {
                $config->set("database.connections.{$name}", [
                    'driver'                  => 'sqlite',
                    'database'                => ':memory:',
                    'prefix'                  => '',
                    'foreign_key_constraints' => true,
                ]);
            }
        }

        $config->set('cache.default', 'array');
        $config->set('queue.default', 'sync');
        $config->set('session.driver', 'array');
        $config->set('mail.default', 'array');

        if ($config->get('database.default') !== 'sqlite'
            || $config->get('database.connections.sqlite.database') !== ':memory:') {
            throw new RuntimeException(
                'Tests must run on sqlite :memory: — aborting to protect the shared MySQL database.',
            );
        }
    }
}
