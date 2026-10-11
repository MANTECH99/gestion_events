<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Les comptes créés par l'inscription (sans organisateur rattaché) sont des
     * clients, et non des points de vente. On leur attribue leur propre rôle
     * pour ne plus les confondre avec les comptes de vente physique.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'user')
            ->whereNull('organizer_id')
            ->update(['role' => 'client']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'client')
            ->update(['role' => 'user']);
    }
};
