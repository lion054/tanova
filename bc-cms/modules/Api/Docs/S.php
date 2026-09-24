<?php

namespace Modules\Api\Docs;

/**
 * Schema helpers: short ways to describe a JSON shape (OpenAPI 3.1 / JSON Schema).
 *   S::obj(['id' => S::int('The id', 12), 'name' => S::str('Display name', 'Ann Ray')], ['id'])
 * Every property takes a description and an example, and the examples in the docs are built from them,
 * so the description of a field is written once.
 */
class S
{
    private static function base(string|array $type, string $d, mixed $ex, array $o): array
    {
        $s = ['type' => $type];
        if ($d !== '') {
            $s['description'] = $d;
        }
        if ($ex !== null) {
            $s['example'] = $ex;
        }

        return $s + $o;
    }

    public static function str(string $d = '', mixed $ex = null, array $o = []): array { return self::base('string', $d, $ex ?? 'text', $o); }
    public static function int(string $d = '', mixed $ex = null, array $o = []): array { return self::base('integer', $d, $ex ?? 1, $o); }
    public static function num(string $d = '', mixed $ex = null, array $o = []): array { return self::base('number', $d, $ex ?? 10.5, $o); }
    public static function bool(string $d = '', mixed $ex = true, array $o = []): array { return self::base('boolean', $d, $ex, $o); }
    public static function date(string $d = '', mixed $ex = '2026-11-10'): array { return self::base('string', $d, $ex, ['format' => 'date']); }
    public static function dt(string $d = '', mixed $ex = '2026-11-10T09:00:00+00:00'): array { return self::base('string', $d, $ex, ['format' => 'date-time']); }
    public static function email(string $d = 'E-mail address', mixed $ex = 'ann@example.com'): array { return self::base('string', $d, $ex, ['format' => 'email']); }
    public static function url(string $d = '', mixed $ex = 'https://example.com'): array { return self::base('string', $d, $ex, ['format' => 'uri']); }
    public static function money(string $d = 'Amount', mixed $ex = 250.0): array { return self::base('number', $d, $ex, ['format' => 'double']); }

    /** @param string[] $values */
    public static function enum(array $values, string $d = '', mixed $ex = null): array
    {
        return self::base('string', $d, $ex ?? $values[0], ['enum' => array_values($values)]);
    }

    /** Anything that may also be null. */
    public static function nullable(array $schema): array
    {
        // A reference or a composition has no `type` of its own: allow either it or null.
        if (!isset($schema['type'])) {
            return ['oneOf' => [$schema, ['type' => 'null']]];
        }
        $schema['type'] = array_values(array_unique(array_merge((array) $schema['type'], ['null'])));
        if (isset($schema['example']) && $schema['example'] === null) {
            unset($schema['example']);
        }

        return $schema;
    }

    public static function obj(array $props, array $required = [], string $d = ''): array
    {
        $s = ['type' => 'object', 'properties' => $props];
        if ($required) {
            $s['required'] = array_values($required);
        }
        if ($d !== '') {
            $s['description'] = $d;
        }

        return $s;
    }

    public static function arr(array $items, string $d = ''): array
    {
        return ['type' => 'array', 'items' => $items] + ($d !== '' ? ['description' => $d] : []);
    }

    public static function ref(string $name): array
    {
        return ['$ref' => "#/components/schemas/$name"];
    }

    /** { data: [Item...], meta: {page, per_page, total, last_page} } */
    public static function page(string $item): array
    {
        return self::obj(['data' => self::arr(self::ref($item)), 'meta' => self::ref('PageMeta')], ['data', 'meta']);
    }

    /** { data: Item } */
    public static function one(string $item): array
    {
        return self::obj(['data' => self::ref($item)], ['data']);
    }

    public static function many(string $item): array
    {
        return self::obj(['data' => self::arr(self::ref($item))], ['data']);
    }

    /** An example value for a schema, built from the examples written on its fields. */
    public static function example(array $s, array $components = []): mixed
    {
        if (isset($s['$ref'])) {
            $name = substr($s['$ref'], strlen('#/components/schemas/'));

            return isset($components[$name]) ? self::example($components[$name], $components) : new \stdClass();
        }
        if (array_key_exists('example', $s)) {
            return $s['example'];
        }
        $type = (array) ($s['type'] ?? 'object');
        $type = array_values(array_diff($type, ['null'])) ?: ['null'];
        switch ($type[0]) {
            case 'object':
                $o = [];
                foreach (($s['properties'] ?? []) as $k => $p) {
                    $o[$k] = self::example($p, $components);
                }

                return $o ?: new \stdClass();
            case 'array':
                return [self::example($s['items'] ?? ['type' => 'string'], $components)];
            case 'string':
                return isset($s['enum']) ? $s['enum'][0] : 'text';
            case 'integer':
                return 1;
            case 'number':
                return 10.5;
            case 'boolean':
                return true;
            default:
                return null;
        }
    }
}
