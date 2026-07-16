<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('role_capabilities', function (Blueprint $table) {
            $table->id();
            $table->string('role');
            $table->string('capability');
            $table->timestamps();

            $table->unique(['role', 'capability']);
        });

        $now = now();
        $rows = [];

        foreach (Role::cases() as $role) {
            foreach ($role->defaultCapabilities() as $capability) {
                $rows[] = [
                    'role' => $role->value,
                    'capability' => $capability,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows !== []) {
            DB::table('role_capabilities')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_capabilities');
    }
};
