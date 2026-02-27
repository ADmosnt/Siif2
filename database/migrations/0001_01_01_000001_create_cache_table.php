<!-- databse/migrations/0001_01_01_000001_create_cache_table.php -->
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'cache' => function (Blueprint $table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration');
            },
            'cache_locks' => function (Blueprint $table) {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration');
            },
        ];

        foreach ($tables as $tableName => $tableDefinition) {
            if (!Schema::hasTable($tableName)) {
                Schema::create($tableName, $tableDefinition);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['cache', 'cache_locks'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::dropIfExists($tableName);
            }
        }
    }
};