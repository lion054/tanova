<?php
namespace Themes\GoTrip;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class UpdaterProvider extends ServiceProvider
{

    public function boot(){
        try {
            if (file_exists(storage_path().'/installed') and !app()->runningInConsole()) {
                $this->runUpdateTo100();
                $this->runUpdateTo120();
            }
        } catch (\Throwable $e) {
            // Cache directory may not be ready; will succeed on next request
        }
    }

    public function runUpdateTo100(){
        $version = '1.0.0';
        if (version_compare(setting_item('GoTrip_update_to_100'), $version, '>=')) return;

        Artisan::call('migrate', [
            '--force' => true,
        ]);

        Schema::table('bc_locations', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_locations', 'general_info')) {
                $table->text('general_info')->nullable();
            }
        });
        Schema::table('bc_location_translations', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_location_translations', 'general_info')) {
                $table->text('general_info')->nullable();
            }
        });
        setting_update_item('GoTrip_update_to_100',$version);
        Artisan::call('view:clear');
        Artisan::call('cache:clear');
    }
    public function runUpdateTo120(){
        $version = '1.0.0';
        if (version_compare(setting_item('GoTrip_update_to_120'), $version, '>=')) return;
        Artisan::call('migrate', [
            '--force' => true,
        ]);
        setting_update_item('GoTrip_update_to_120',$version);
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
    }
}
