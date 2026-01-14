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
        // Create pivot table for multiple technicians per ticket
        Schema::create('ticket_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // This is the technician
            $table->timestamps();
        });

        // Migrate existing single technician data to the pivot table
        $tickets = \DB::table('tickets')->whereNotNull('technician_id')->get();
        foreach ($tickets as $ticket) {
            \DB::table('ticket_user')->insert([
                'ticket_id' => $ticket->id,
                'user_id' => $ticket->technician_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Make technician_id nullable (or we could drop it, but keeping it nullable is safer for now to avoid breaking legacy code immediately if something was missed)
        // Actually, let's drop it to force usage of the new relationship
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['technician_id']);
            $table->dropColumn('technician_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('technician_id')->nullable()->constrained('users')->onDelete('set null');
        });

        // Restore data (take the first technician found)
        $pivots = \DB::table('ticket_user')->get();
        foreach ($pivots as $pivot) {
            \DB::table('tickets')->where('id', $pivot->ticket_id)->update(['technician_id' => $pivot->user_id]);
        }

        Schema::dropIfExists('ticket_user');
    }
};
