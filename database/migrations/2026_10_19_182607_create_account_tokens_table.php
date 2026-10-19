<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('purpose', 32);
            $table->string('email');
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('groups')->cascadeOnDelete();
            $table->foreignId('patrol_id')->nullable()->constrained('patrols')->cascadeOnDelete();
            $table->string('role', 32)->nullable();
            // SHA-256 of the random token. The plaintext token is never stored.
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['email', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_tokens');
    }
};