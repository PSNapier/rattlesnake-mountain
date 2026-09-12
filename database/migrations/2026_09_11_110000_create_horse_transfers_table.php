<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horse_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('horse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('acted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');

            // One pending transfer per horse, enforced by the database rather than by a
            // service check, so two admins cannot both approve a horse in the same second.
            // MySQL has no partial indexes, so this generated column carries the horse id
            // only while the row is pending and is NULL otherwise. Repeated NULLs are legal
            // under a unique index, so resolved rows never collide. It is VIRTUAL rather
            // than STORED because MySQL forbids an ON DELETE CASCADE foreign key on the
            // base column of a stored generated column, and `horse_id` is both.
            $table->unsignedBigInteger('pending_horse_id')
                ->nullable()
                ->virtualAs("CASE WHEN status = 'pending' THEN horse_id ELSE NULL END");

            $table->text('notes')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique('pending_horse_id');
            $table->index(['status', 'created_at']);
        });

        $now = now();

        DB::table('role_capabilities')->updateOrInsert(
            ['role' => 'admin', 'capability' => 'horses'],
            ['created_at' => $now, 'updated_at' => $now],
        );
    }

    public function down(): void
    {
        DB::table('role_capabilities')->where('capability', 'horses')->delete();

        Schema::dropIfExists('horse_transfers');
    }
};
