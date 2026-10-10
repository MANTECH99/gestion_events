<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PwaController extends Controller
{
    public function manifest(): Response
    {
        return response()
            ->view('pwa.manifest', [], 200, ['Content-Type' => 'application/manifest+json'])
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function serviceWorker(Request $request): Response
    {
        $version = config('app.asset_version', '1');

        return response()
            ->view('pwa.sw', ['version' => $version], 200, ['Content-Type' => 'application/javascript'])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    public function offline(): View
    {
        return view('pwa.offline');
    }
}
