<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Keep feature tests focused on business logic instead of CSRF token wiring.
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }
}
