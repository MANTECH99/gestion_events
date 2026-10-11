<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /** Liste des utilisateurs rattachés à l'organisateur. */
    public function index(Request $request): View
    {
        $user = $request->user();

        $users = User::query()
            ->with('organizer')
            ->when(! $user->isAdmin(), fn ($q) => $q->where('organizer_id', $user->id))
            ->when($user->isOrganizer(), fn ($q) => $q->whereIn('role', [User::ROLE_AGENT, User::ROLE_USER]))
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')->value()))
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
            ->orderBy('last_name')
            ->paginate(15)
            ->withQueryString();

        return view('organizer.users.index', [
            'users' => $users,
        ]);
    }

    /** Création d'un point de vente par l'organisateur. */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'username' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[\pL\pN _-]+$/u', 'unique:users,username'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'phone' => ['nullable', 'string', 'max:30'],
            'point_of_sale' => ['nullable', 'string', 'max:120'],
        ], [], [
            'first_name' => 'prénom',
            'last_name' => 'nom',
            'email' => 'email',
            'password' => 'mot de passe',
            'point_of_sale' => 'point de vente',
        ]);

        User::create([
            ...$data,
            'role' => User::ROLE_USER,
            'organizer_id' => $user->isAdmin() ? ($request->input('organizer_id') ?: $user->id) : $user->id,
        ]);

        return back()->with('success', 'Point de vente créé. Vous pouvez maintenant lui attribuer du stock.');
    }

    /** Nomme (ou retire) un utilisateur comme agent. */
    public function toggleAgent(Request $request, User $target): RedirectResponse
    {
        $this->authorizeTarget($request, $target);

        if ($target->isAdmin()) {
            return back()->with('error', 'Un administrateur ne peut pas être rétrogradé.');
        }

        $isAgent = $target->isAgent();

        if ($isAgent) {
            $target->update(['role' => User::ROLE_USER]);
            $message = "{$target->full_name} n'est plus agent.";
        } else {
            $target->update([
                'role' => User::ROLE_AGENT,
                'organizer_id' => $target->organizer_id ?: $request->user()->id,
            ]);
            $message = "{$target->full_name} est désormais agent de contrôle.";
        }

        return back()->with('success', $message);
    }

    public function toggleActive(Request $request, User $target): RedirectResponse
    {
        $this->authorizeTarget($request, $target);

        if ($target->id === $request->user()->id) {
            return back()->with('error', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        $target->update(['is_active' => ! $target->is_active]);

        return back()->with('success', $target->is_active ? 'Compte réactivé.' : 'Compte désactivé.');
    }

    public function destroy(Request $request, User $target): RedirectResponse
    {
        $this->authorizeTarget($request, $target);

        if ($target->id === $request->user()->id) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        if ($target->posSales()->exists() || $target->stockAllocations()->exists()) {
            return back()->with('error', 'Ce compte possède des ventes : désactivez-le plutôt.');
        }

        $target->delete();

        return back()->with('success', 'Utilisateur supprimé.');
    }

    private function authorizeTarget(Request $request, User $target): void
    {
        $user = $request->user();

        abort_if($target->id === $user->id && $target->isAdmin(), 403);

        abort_unless($user->isAdmin() || $target->organizer_id === $user->id, 403);
    }
}
