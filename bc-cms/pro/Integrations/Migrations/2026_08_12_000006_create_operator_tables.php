<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tanova port, phase 6 — supplier / operator directory.
 *
 * Ported from the source's `operators`, `operator_routes`, `operator_schedules`,
 * `operator_fares` and `operator_sync_logs`.
 *
 * SCOPE NOTE: the plan flagged this as overlapping two things that already exist —
 * bc_vendor_api_keys (keys the vendor ISSUES to others) and pro/Integrations
 * (WetuService, inbound feeds). This is deliberately the third, distinct thing:
 * suppliers the vendor BUYS FROM — a bus company, a charter airline, a lodge — with
 * the routes and fares they sell. It lives under pro/Integrations so the sync
 * plumbing sits beside the existing connector code rather than in a new silo.
 *
 * The five child tables are collapsed to three: routes and schedules are one table
 * (a schedule without a route is meaningless) and availability folds into fares.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_operators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();

            $table->string('name');
            $table->string('type', 20)->default('transport'); // transport | air | lodging | dining | activity
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();

            // Commercial terms
            $table->decimal('commission_rate', 5, 2)->nullable();  // percent we earn
            $table->string('payment_terms', 60)->nullable();       // e.g. "net 30"
            $table->string('currency', 8)->default('USD');

            // Connection state. Credentials are NOT stored here — see the note in
            // the controller; this records only whether a connection exists.
            $table->string('connection_status', 20)->default('manual'); // manual | connected | error
            $table->timestamp('last_synced_at')->nullable();

            $table->text('notes')->nullable();
            $table->string('status', 12)->default('active');       // active | inactive
            $table->timestamps();

            $table->index(['vendor_id', 'type']);
            $table->index(['vendor_id', 'status']);
        });

        Schema::create('bc_operator_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained('bc_operators')->cascadeOnDelete();

            $table->string('origin');
            $table->string('destination');
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('vehicle_type', 60)->nullable();

            // Schedule, folded in — a departure without a route has no meaning.
            $table->string('days_of_week', 20)->nullable();        // e.g. "1,2,3,4,5"
            $table->time('departure_time')->nullable();
            $table->time('arrival_time')->nullable();

            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['vendor_id', 'operator_id']);
        });

        Schema::create('bc_operator_fares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained('bc_operators')->cascadeOnDelete();
            $table->unsignedBigInteger('route_id')->nullable();

            $table->string('fare_class', 40)->default('standard');
            $table->decimal('nett_price', 12, 2)->default(0);      // what we pay
            $table->decimal('sell_price', 12, 2)->nullable();      // what we charge
            $table->unsignedSmallInteger('available_seats')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'operator_id']);
        });

        Schema::create('bc_operator_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained('bc_operators')->cascadeOnDelete();

            $table->string('status', 20);                          // ok | error
            $table->unsignedInteger('records')->default(0);
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'operator_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_operator_sync_logs');
        Schema::dropIfExists('bc_operator_fares');
        Schema::dropIfExists('bc_operator_routes');
        Schema::dropIfExists('bc_operators');
    }
};
