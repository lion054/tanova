<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add geolocation columns to locations table
        if (Schema::hasTable('locations')) {
            Schema::table('locations', function (Blueprint $table) {
                if (!Schema::hasColumn('locations', 'latitude')) {
                    $table->decimal('latitude', 10, 8)->nullable()->after('country');
                }
                if (!Schema::hasColumn('locations', 'longitude')) {
                    $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
                }
            });

            Schema::table('locations', function (Blueprint $table) {
                $table->index(['latitude', 'longitude']);
            });
        }

        // Add location_id to tours table
        if (Schema::hasTable('bravo_tours')) {
            Schema::table('bravo_tours', function (Blueprint $table) {
                if (!Schema::hasColumn('bravo_tours', 'location_id')) {
                    $table->unsignedBigInteger('location_id')->nullable()->after('user_id');
                    $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
                }
            });
        }

        // Add location_id to hotels table
        if (Schema::hasTable('bravo_hotels')) {
            Schema::table('bravo_hotels', function (Blueprint $table) {
                if (!Schema::hasColumn('bravo_hotels', 'location_id')) {
                    $table->unsignedBigInteger('location_id')->nullable()->after('user_id');
                    $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
                }
            });
        }
    }

    public function down(): void
    {
        // Drop foreign keys first
        if (Schema::hasTable('bravo_tours')) {
            Schema::table('bravo_tours', function (Blueprint $table) {
                $table->dropForeignIdFor('locations', 'location_id');
            });
        }

        if (Schema::hasTable('bravo_hotels')) {
            Schema::table('bravo_hotels', function (Blueprint $table) {
                $table->dropForeignIdFor('locations', 'location_id');
            });
        }

        // Drop columns
        if (Schema::hasTable('locations')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->dropIndex(['latitude', 'longitude']);
                $table->dropColumn(['latitude', 'longitude']);
            });
        }

        if (Schema::hasTable('bravo_tours')) {
            Schema::table('bravo_tours', function (Blueprint $table) {
                $table->dropColumn('location_id');
            });
        }

        if (Schema::hasTable('bravo_hotels')) {
            Schema::table('bravo_hotels', function (Blueprint $table) {
                $table->dropColumn('location_id');
            });
        }
    }
};
