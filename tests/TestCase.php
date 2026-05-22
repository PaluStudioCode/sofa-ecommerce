<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config([
            'services.midtrans.driver' => 'fake',
            'services.midtrans.client_key' => null,
            'services.midtrans.server_key' => null,
            'services.fonnte.driver' => 'fake',
        ]);
    }
}
