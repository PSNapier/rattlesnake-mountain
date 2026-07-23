<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breeding_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('horse_id')->constrained('horses')->cascadeOnDelete();
            $table->unsignedTinyInteger('sequence');
            $table->foreignId('holder_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('available');
            $table->unsignedBigInteger('reserved_for_request_id')->nullable()->index();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->unique(['horse_id', 'sequence']);
            $table->index(['holder_id', 'status']);
            $table->index(['horse_id', 'status']);
        });

        $slotCount = (int) config('breeding.slots_per_horse', 10);
        $now = now();

        DB::table('horses')
            ->where('state', 'public')
            ->orderBy('id')
            ->chunkById(100, function ($horses) use ($slotCount, $now): void {
                $rows = [];
                foreach ($horses as $horse) {
                    for ($sequence = 1; $sequence <= $slotCount; $sequence++) {
                        $rows[] = [
                            'horse_id' => $horse->id,
                            'sequence' => $sequence,
                            'holder_id' => $horse->owner_id,
                            'status' => 'available',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if ($rows !== []) {
                    DB::table('breeding_slots')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('breeding_slots');
    }
};
