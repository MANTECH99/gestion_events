<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ArtistController extends Controller
{
    public function index(): View
    {
        return view('organizer.artists.index', [
            'artists' => Artist::withCount('events')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('organizer.artists.form', ['artist' => new Artist()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $artist = Artist::create($data);

        if ($request->hasFile('image')) {
            $this->storeImage($request, $artist);
        }

        return redirect()->route('organizer.artists.index')->with('success', "Artiste « {$artist->name} » créé.");
    }

    public function edit(Artist $artist): View
    {
        return view('organizer.artists.form', ['artist' => $artist]);
    }

    public function update(Request $request, Artist $artist): RedirectResponse
    {
        $data = $this->validated($request);

        $artist->update($data);

        if ($request->hasFile('image')) {
            $this->storeImage($request, $artist);
        }

        return redirect()->route('organizer.artists.index')->with('success', 'Artiste mis à jour.');
    }

    public function destroy(Artist $artist): RedirectResponse
    {
        if ($artist->image_path) {
            Storage::disk('public')->delete($artist->image_path);
        }

        $artist->delete();

        return redirect()->route('organizer.artists.index')
            ->with('success', 'Artiste supprimé — ses événements restent en ligne sans artiste.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'title' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [], [
            'name' => 'nom',
            'title' => 'titre / rôle',
            'bio' => 'biographie',
            'image' => 'photo',
        ]);
    }

    private function storeImage(Request $request, Artist $artist): void
    {
        $path = $request->file('image')->store('artists', 'public');

        if ($artist->image_path) {
            Storage::disk('public')->delete($artist->image_path);
        }

        $artist->update(['image_path' => $path]);
    }
}
