<?php

namespace Tests;

use App\Support\Campus;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Pengaturan kampus disimpan statis selama satu proses; setiap tes mulai dari database baru.
        Campus::flush();
    }
}
