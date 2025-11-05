<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $databasePath = __DIR__.'/../database/database.sqlite';
        if (env('DB_DATABASE') !== ':memory:' && !file_exists($databasePath)) {
            touch($databasePath);
        }

        return $app;
    }
}
