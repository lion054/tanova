<?php

namespace Modules\Api\Docs;

/** Query / path parameter helpers. */
class P
{
    public static function make(string $name, string $type, string $desc, array $o = []): array
    {
        return ['name' => $name, 'type' => $type, 'desc' => $desc] + $o;
    }

    public static function str(string $name, string $desc, array $o = []): array { return self::make($name, 'string', $desc, $o); }
    public static function int(string $name, string $desc, array $o = []): array { return self::make($name, 'integer', $desc, $o); }
    public static function bool(string $name, string $desc, array $o = []): array { return self::make($name, 'boolean', $desc, $o); }
    public static function date(string $name, string $desc, array $o = []): array { return self::make($name, 'string', $desc, ['format' => 'date', 'example' => '2026-11-01'] + $o); }

    /** @param string[] $values */
    public static function enum(string $name, array $values, string $desc, array $o = []): array { return self::make($name, 'string', $desc, ['enum' => $values] + $o); }

    public static function q(string $searches): array
    {
        return self::str('q', "Search. Every word must match somewhere in: {$searches}. Order does not matter.", ['example' => 'vic falls']);
    }

    /** @param array<string,string> $values key => what it means */
    public static function sort(array $values, string $default): array
    {
        $list = implode(', ', array_map(fn ($k, $v) => "`{$k}` ({$v})", array_keys($values), $values));

        return self::enum('sort', array_keys($values), "Order of the results: {$list}.", ['default' => $default]);
    }

    public static function page(): array { return self::int('page', 'Page number, starting at 1.', ['default' => 1, 'min' => 1]); }
    public static function perPage(): array { return self::int('per_page', 'Rows per page: 10, 25, 50 or 100.', ['default' => 25, 'enum' => [10, 25, 50, 100]]); }

    /** The three every list takes. */
    public static function paging(): array { return [self::page(), self::perPage()]; }
}
