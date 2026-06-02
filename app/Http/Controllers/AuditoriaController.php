<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

final class AuditoriaController
{
    public function index(): View
    {
        return view('auditoria.index');
    }
}

