<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * RefreshDatabase and DatabaseMigrations run `migrate:fresh` on the configured database, which here is the development database
     * with the developer's real data in it. A test that uses them only runs against a database whose name ends in `_test`; anywhere
     * else it is skipped, so a full run can never wipe local data. (ApiTestCase does not need this: it works in its own copy.)
     */
    protected function setUpTraits()
    {
        $uses = class_uses_recursive(static::class);
        if (isset($uses[RefreshDatabase::class]) || isset($uses[DatabaseMigrations::class])) {
            $db = (string) config('database.connections.' . config('database.default') . '.database');
            if (!str_ends_with($db, '_test')) {
                $this->markTestSkipped("Skipped: this test rebuilds the database and '{$db}' is not a *_test database.");
            }
        }

        return parent::setUpTraits();
    }
}
