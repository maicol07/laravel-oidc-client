<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Safe cleanup: remove exact duplicate rows for the same (user_id, issuer, sub)
        $sameUserDuplicates = DB::table('oidc_auth_mappings')
            ->select('user_id', 'issuer', 'sub', DB::raw('MAX(id) as max_id'))
            ->groupBy('user_id', 'issuer', 'sub')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($sameUserDuplicates as $duplicate) {
            DB::table('oidc_auth_mappings')
                ->where('user_id', $duplicate->user_id)
                ->where('issuer', $duplicate->issuer)
                ->where('sub', $duplicate->sub)
                ->where('id', '<', $duplicate->max_id)
                ->delete();
        }

        // 2. Safety check: detect if different user_ids share the same (issuer, sub)
        $conflicts = DB::table('oidc_auth_mappings')
            ->select('issuer', 'sub', DB::raw('COUNT(DISTINCT user_id) as user_count'))
            ->groupBy('issuer', 'sub')
            ->havingRaw('COUNT(DISTINCT user_id) > 1')
            ->get();

        if ($conflicts->isNotEmpty()) {
            throw new RuntimeException(
                'Cannot add unique constraint to oidc_auth_mappings: conflicting user_id assignments exist for the same (issuer, sub) pair. Please resolve these conflicts manually before running the migration.'
            );
        }

        // 3. Add unique constraint on (issuer, sub)
        Schema::table('oidc_auth_mappings', static function (Blueprint $table) {
            $table->unique(['issuer', 'sub'], 'oidc_auth_mappings_issuer_sub_unique');
        });
    }

    public function down(): void
    {
        Schema::table('oidc_auth_mappings', static function (Blueprint $table) {
            $table->dropUnique('oidc_auth_mappings_issuer_sub_unique');
        });
    }
};
