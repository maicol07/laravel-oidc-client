<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The column was created with foreignIdFor() and never constrained, so a
     * removed user leaves its mappings behind. Combined with the unique index
     * on (issuer, sub) that is not merely untidy: the stale row keeps the pair
     * taken, so the same person can never sign up again.
     */
    public function up(): void
    {
        $users = $this->userModel();

        // The constraint cannot be created while rows point at users that are
        // already gone, and those rows are exactly what this migration exists
        // to stop accumulating.
        $orphans = DB::table('oidc_auth_mappings')
            ->whereNotIn(
                'user_id',
                DB::table($users->getTable())->select($users->getKeyName())
            )
            ->delete();

        if ($orphans > 0) {
            Log::warning('Dropped OIDC auth mappings left behind by removed users | ', [
                'count' => $orphans,
            ]);
        }

        Schema::table('oidc_auth_mappings', function (Blueprint $table) use ($users) {
            $table->foreign('user_id')
                ->references($users->getKeyName())
                ->on($users->getTable())
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('oidc_auth_mappings', static function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
    }

    private function userModel(): Model
    {
        $model = config('auth.providers.users.model');

        return new $model;
    }
};
