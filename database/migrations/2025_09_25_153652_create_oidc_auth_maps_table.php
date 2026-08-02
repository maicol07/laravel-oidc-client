<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oidc_auth_mappings', static function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(config('auth.providers.users.model'));
            $table->string('sub');
            $table->string('issuer');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oidc_auth_mappings');
    }
};
