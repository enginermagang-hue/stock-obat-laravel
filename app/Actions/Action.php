<?php

namespace App\Actions;

/**
 * Basis untuk seluruh Action class.
 *
 * Livewire memanggil Action langsung (in-process); API Controller memanggil
 * Action yang sama. Otorisasi divalidasi ulang di sini, bukan hanya di UI.
 */
abstract class Action
{
    abstract public function handle(): mixed;
}
