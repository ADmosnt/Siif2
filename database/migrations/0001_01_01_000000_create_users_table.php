<!-- databse/migrations/0001_01_01_000000_create_users_table.php -->
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    //migracion de la tabla personas
    public function up(): void
    {
        $tables = [
            't_personas' => function (Blueprint $table) {
                $table->string('idPersona', 11)->primary();
                $table->string('name', 20);
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password', 60);
                $table->string('remember_token', 100)->nullable();
                $table->timestamps();
            },
            'password_reset_tokens' => function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            },
            'sessions' => function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            },
        ];
    
        foreach ($tables as $tableName => $tableDefinition) {
            if (!Schema::hasTable($tableName)) {
                Schema::create($tableName, $tableDefinition);
            }
        }
    }


    public function down(): void
    {
        // Lista de tablas a eliminar
        $tables = ['t_personas', 'password_reset_tokens', 'sessions'];
    
        // Elimina cada tabla si existe
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::dropIfExists($table);
            }
        }
    }
};
