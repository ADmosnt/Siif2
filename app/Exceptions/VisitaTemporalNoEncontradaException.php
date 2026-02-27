<?php

namespace App\Exceptions;

use Exception;

class VisitaTemporalNoEncontradaException extends Exception
{
    protected $message = 'La visita temporal ya fue procesada o no se encuentra.';
    // ID de la visita temporal para el log
    public function __construct($idTmpVisita = null, $code = 400, ?Exception $previous = null) {
        $message = $idTmpVisita ? "La visita temporal ID {$idTmpVisita} ya fue procesada o no se encuentra." : $this->message;
        parent::__construct($message, $code, $previous);
    }
}