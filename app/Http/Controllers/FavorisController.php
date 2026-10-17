<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class FavorisController extends Controller
{
    public function index(): View
    {
        return view('favoris.index');
    }
}
