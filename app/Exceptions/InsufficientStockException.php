<?php

namespace App\Exceptions;

use Exception;

/**
 * Levée lorsqu'un point de vente tente de vendre plus de tickets
 * que le stock physique restant.
 */
class InsufficientStockException extends Exception
{
    public function __construct(public readonly int $remaining)
    {
        parent::__construct("Stock insuffisant : {$remaining} ticket(s) restant(s).");
    }
}
