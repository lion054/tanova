<?php

namespace Tests;

use App\User;
use Illuminate\Support\Facades\DB;
use Modules\Vendor\Models\VendorApiKey;

/**
 * Base for tests that call the vendor API over real HTTP, through the real middleware
 * (keys, scopes, rate limit, idempotency), against the portal's real table structure.
 *
 * The structure is copied once into a throwaway database (`tsoka_portal_api_test`) from the
 * development database, and again whenever the development structure changes. Each test runs
 * inside a transaction that is rolled back, so tests never see each other's rows.
 *
 * Every test gets two vendors, so isolation is checked side by side:
 *   $this->vendor / $this->key      the vendor under test and its secret test key (sk_test_)
 *   $this->pk                       its publishable test key (read only)
 *   $this->other / $this->otherKey  another vendor, whose data must never appear
 */
abstract class ApiTestCase extends TestCase
{
    private const DB = 'tsoka_portal_api_test';
    private static bool $ready = false;

    protected User $vendor;
    protected User $other;
    protected string $key;
    protected string $pk;
    protected string $otherKey;
    /** A live secret key for $this->vendor, who has an active plan: for tests where real e-mail, messages or state changes are the point. */
    protected string $liveKey;

    protected function setUp(): void
    {
        parent::setUp();

        $dev = config('database.connections.mysql');
        $test = $dev;
        $test['database'] = self::DB;
        $this->ensureSchema($dev['database']);
        config(['database.connections.mysql_api' => $test, 'database.default' => 'mysql_api']);
        DB::purge('mysql_api');
        DB::connection('mysql_api')->beginTransaction();

        $this->vendor = $this->makeVendor('Vendor One');
        $this->other = $this->makeVendor('Vendor Two');
        $this->key = VendorApiKey::generate($this->vendor, 'test', 100000, null, 'secret', 'test')->key;
        $this->pk = VendorApiKey::generate($this->vendor, 'test pk', 100000, null, 'publishable', 'test')->key;
        $this->otherKey = VendorApiKey::generate($this->other, 'other', 100000, null, 'secret', 'test')->key;
        $this->vendor->vendor_plan_id = 1;
        $this->vendor->vendor_plan_expires_at = now()->addYear();
        $this->vendor->save();
        $this->liveKey = VendorApiKey::generate($this->vendor, 'live', 100000, null, 'secret', 'live')->key;
    }

    protected function tearDown(): void
    {
        DB::connection('mysql_api')->rollBack();
        parent::tearDown();
    }

    protected function makeVendor(string $name): User
    {
        $u = new User();
        $u->name = $name;
        $u->first_name = explode(' ', $name)[0];
        $u->last_name = explode(' ', $name)[1] ?? '';
        $u->email = strtolower(str_replace(' ', '.', $name)) . '.' . uniqid() . '@example.test';
        $u->password = bcrypt('secret-pass-1');
        $u->status = 'publish';
        $u->email_verified_at = now();
        $u->save();

        return $u;
    }

    // ── Calling the API ───────────────────────────────────────────────────────

    protected function api(string $method, string $path, array $body = [], ?string $key = null, array $headers = [])
    {
        $key ??= $this->key;
        $headers += ['Authorization' => 'Bearer ' . $key, 'Accept' => 'application/json'];
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true) && !isset($headers['Idempotency-Key'])) {
            $headers['Idempotency-Key'] = 'test-' . uniqid('', true);
        }

        return $this->json($method, '/api/v' . $path, $body, $headers);
    }

    protected function apiGet(string $path, array $query = [], ?string $key = null)
    {
        return $this->api('GET', $path . ($query ? '?' . http_build_query($query) : ''), [], $key);
    }

    // ── The throwaway database ────────────────────────────────────────────────

    /**
     * The response has the shape the documentation promises for [$op] ("GET /waitlist/{id}"): every documented field is
     * there, with a type that fits (null only where the docs say it may be). Extra fields are fine.
     */
    protected function assertMatchesDocs(\Illuminate\Testing\TestResponse $r, string $op, ?int $status = null): void
    {
        $doc = \Modules\Api\Docs\Doc::ops()[$op] ?? null;
        $this->assertNotNull($doc, "No documentation for {$op}");
        $status ??= (int) array_key_first(array_filter($doc->returns, fn ($_, $k) => $k < 300, ARRAY_FILTER_USE_BOTH));
        $schema = $doc->returns[$status]['schema'] ?? null;
        $this->assertNotNull($schema, "{$op} documents no {$status} body");
        $r->assertStatus($status);
        $problems = [];
        $this->walkDocs($schema, $r->json(), '$', $problems);
        $this->assertSame([], $problems, "{$op} does not match its documentation:\n" . implode("\n", $problems));
    }

    private function walkDocs(array $schema, mixed $value, string $at, array &$problems): void
    {
        $schemas = \Modules\Api\Docs\Doc::schemas() + ['PageMeta' => \Modules\Api\Docs\S::obj(['page' => ['type' => 'integer'], 'per_page' => ['type' => 'integer'], 'total' => ['type' => 'integer'], 'last_page' => ['type' => 'integer']], ['page', 'per_page', 'total', 'last_page'])];
        while (isset($schema['$ref']) || isset($schema['allOf']) || isset($schema['oneOf'])) {
            if (isset($schema['$ref'])) {
                $schema = $schemas[substr($schema['$ref'], strlen('#/components/schemas/'))] ?? [];
            } elseif (isset($schema['allOf'])) {
                $m = ['type' => 'object', 'properties' => []];
                foreach ($schema['allOf'] as $part) {
                    while (isset($part['$ref'])) { $part = $schemas[substr($part['$ref'], strlen('#/components/schemas/'))] ?? []; }
                    $m['properties'] += $part['properties'] ?? [];
                }
                $schema = $m;
            } else {
                if ($value === null) { return; }
                $schema = array_values(array_filter($schema['oneOf'], fn ($x) => ($x['type'] ?? null) !== 'null'))[0] ?? [];
            }
        }
        $types = (array) ($schema['type'] ?? 'object');
        if ($value === null) {
            if (!in_array('null', $types, true)) { $problems[] = "{$at} is null but is documented as non-null"; }
            return;
        }
        $t = array_values(array_diff($types, ['null']))[0] ?? 'object';
        $ok = match ($t) {
            'integer' => is_int($value), 'number' => is_int($value) || is_float($value), 'string' => is_string($value), 'boolean' => is_bool($value),
            'array' => is_array($value) && array_is_list($value), 'object' => is_array($value) && ($value === [] || !array_is_list($value)),
            default => true,
        };
        if (!$ok) { $problems[] = "{$at} is " . get_debug_type($value) . ", documented as {$t}"; return; }
        if ($t === 'object') {
            foreach (($schema['properties'] ?? []) as $k => $p) {
                if (!array_key_exists($k, $value)) { $problems[] = "{$at}.{$k} is documented but missing"; continue; }
                $this->walkDocs($p, $value[$k], "{$at}.{$k}", $problems);
            }
        } elseif ($t === 'array' && isset($schema['items']) && $value) {
            $this->walkDocs($schema['items'], $value[0], "{$at}[0]", $problems);
        }
    }

    private function ensureSchema(string $devDb): void
    {
        if (self::$ready) {
            return;
        }
        $pdo = DB::connection('mysql')->getPdo();
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . self::DB . '` CHARACTER SET utf8mb4');

        // GROUP_CONCAT stops at 1 KB by default, which would hide a new column from the comparison.
        DB::connection('mysql')->statement('SET SESSION group_concat_max_len = 1073741824');
        $sig = fn (string $db) => (string) DB::connection('mysql')->selectOne(
            'SELECT MD5(GROUP_CONCAT(CONCAT(c.table_name, ".", c.column_name, ":", c.column_type) ORDER BY c.table_name, c.column_name)) AS s
             FROM information_schema.columns c JOIN information_schema.tables t ON t.table_schema = c.table_schema AND t.table_name = c.table_name
             WHERE c.table_schema = ? AND t.table_type = "BASE TABLE"', [$db])->s;

        if ($sig($devDb) !== $sig(self::DB)) {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            foreach (DB::connection('mysql')->select('SHOW FULL TABLES FROM `' . $devDb . '` WHERE Table_type = "BASE TABLE"') as $row) {
                $table = array_values((array) $row)[0];
                $create = DB::connection('mysql')->selectOne('SHOW CREATE TABLE `' . $devDb . '`.`' . $table . '`');
                $sql = ((array) $create)['Create Table'];
                $pdo->exec('DROP TABLE IF EXISTS `' . self::DB . '`.`' . $table . '`');
                $pdo->exec(preg_replace('/^CREATE TABLE `' . preg_quote($table, '/') . '`/', 'CREATE TABLE `' . self::DB . '`.`' . $table . '`', $sql));
            }
            // Reference data the portal cannot run without (roles, settings, languages).
            foreach (['core_roles', 'core_role_permissions', 'core_permissions', 'core_settings', 'core_languages', 'core_translations'] as $t) {
                if (DB::connection('mysql')->getSchemaBuilder()->hasTable($t)) {
                    $pdo->exec('INSERT INTO `' . self::DB . '`.`' . $t . '` SELECT * FROM `' . $devDb . '`.`' . $t . '`');
                }
            }
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }
        self::$ready = true;
    }
}
