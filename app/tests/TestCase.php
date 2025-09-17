<?php

namespace Tests;

use Laravel\Lumen\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        $databasePath = __DIR__.'/../database/database.sqlite';
        if (!file_exists($databasePath)) {
            touch($databasePath);
        }

        return require __DIR__.'/../bootstrap/app.php';
    }
}
