<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $users = DB::table('users')->orderBy('id')->lockForUpdate()->get();
            $usernames = DB::table('profiles')->pluck('username', 'user_id');
            $aliases = [];
            $owners = [];

            foreach ($users as $user) {
                $login = Str::lower($user->email);
                $owners[$login][] = $user->id;
            }

            foreach ($users as $user) {
                $alias = trim((string) $user->alias);
                $alias = Str::lower($alias !== '' ? $alias : trim((string) ($usernames[$user->id] ?? '')));
                $aliases[$user->id] = $alias !== '' ? $alias : null;
                if ($alias !== '') {
                    $owners[$alias][] = $user->id;
                }
            }

            foreach ($owners as $ids) {
                if (count(array_unique($ids)) > 1) {
                    throw new RuntimeException('Conflicting login identifiers for user IDs '.implode(', ', array_unique($ids)).'. Resolve these before migrating usernames.');
                }
            }

            foreach ($aliases as $id => $alias) {
                DB::table('users')->where('id', $id)->update(['alias' => $alias]);
            }

            Schema::table('profiles', fn (Blueprint $table) => $table->dropColumn('username'));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new RuntimeException('Username consolidation is irreversible. Restore a database backup to recover discarded usernames.');
    }
};
