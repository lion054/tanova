<?php

namespace Tests\Feature;

use App\Support\Health;
use Illuminate\Support\Facades\Artisan;
use Tests\ApiTestCase;

class HealthAndExportTest extends ApiTestCase
{
    public function test_health_needs_a_scheduler_heartbeat_and_says_nothing_secret(): void
    {
        \Cache::forget(Health::HEARTBEAT);
        $r = $this->getJson('/health');
        $r->assertStatus(503)->assertJsonPath('checks.database.ok', true)->assertJsonPath('checks.scheduler.ok', false);

        Health::beat();
        $r = $this->getJson('/health');
        $r->assertStatus(200)->assertJsonPath('ok', true);
        $this->assertStringNotContainsString(base_path(), $r->getContent());
    }

    public function test_tenant_export_holds_only_that_business_and_no_secrets(): void
    {
        $dir = sys_get_temp_dir() . '/tx-' . uniqid();
        $this->assertSame(0, Artisan::call('tenant:export', ['vendor_id' => $this->vendor->id, '--path' => $dir]));
        $zip = new \ZipArchive();
        $zip->open(glob($dir . '/*.zip')[0]);

        $manifest = json_decode($zip->getFromName('manifest.json'), true);
        $this->assertSame($this->vendor->id, $manifest['vendor_id']);
        $users = $zip->getFromName('users.jsonl');
        $this->assertStringNotContainsString('"password"', $users);
        $this->assertStringNotContainsString($this->other->email, $users);
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (in_array($name, ['manifest.json', 'users.jsonl'], true)) { continue; }
            foreach (array_filter(explode("\n", $zip->getFromIndex($i))) as $line) {
                $this->assertSame($this->vendor->id, json_decode($line, true)['vendor_id'], "foreign row in {$name}");
            }
        }
        $this->assertSame(1, Artisan::call('tenant:export', ['vendor_id' => 99999999, '--path' => $dir]));
    }
}
