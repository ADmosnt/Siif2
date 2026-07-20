<?php
// database/migrations/2025_07_01_000001_create_push_subscriptions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    Schema::create('push_subscriptions', function (Blueprint $table) {
        $table->charset = 'utf8';
        $table->collation = 'utf8_unicode_ci';
        $table->increments('id');
        
        // BORRA O COMENTA ESTA LÍNEA:
        // $table->morphs('subscribable'); 
        
        // AGREGA ESTAS LÍNEAS EN SU LUGAR:
        $table->string('subscribable_type');
        $table->string('subscribable_id');
        $table->index(['subscribable_type', 'subscribable_id']);

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
