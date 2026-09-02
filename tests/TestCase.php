<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // Aplikasi hanya punya satu database, jadi test berjalan di dalam
    // transaksi dan di-rollback — bukan menghapus isi database.
    use DatabaseTransactions;
}
