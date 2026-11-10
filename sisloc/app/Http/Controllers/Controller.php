<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as ControladorBase;

abstract class Controller extends ControladorBase
{
    use AuthorizesRequests;
}
