<?php
// database/migrations/2025_07_01_000001_create_push_subscriptions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('subscribable_id');
            $table->string('subscribable_type');
            $table->string('endpoint', 500)->unique();
            $table->string('public_key')->nullable();
            $table->string('auth_token')->nullable();
            $table->string('content_encoding')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
