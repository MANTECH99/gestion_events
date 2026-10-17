<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class ProfilController extends Controller
{
    public function show(): View
    {
        return view('profil.show', ['user' => auth()->user()]);
    }
}
