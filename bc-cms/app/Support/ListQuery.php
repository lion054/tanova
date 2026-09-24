<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Safe searching, sorting and paging for portal lists, so every page does it the same way.
 *
 * Search: the words typed are split, and every word must match at least one of the given
 * columns (so "vic falls swing" finds a tour with all three words, in any order). Wildcard
 * characters the user types are escaped, so "50%" searches for "50%". A plain number also
 * matches the row's id.
 *
 * Sort and per-page only ever accept values from a list the page declares, so nothing the
 * user puts in the address can reach the query.
 */
class ListQuery
{
    public const MAX_TERM = 100;
    public const MAX_WORDS = 6;

    /** Escapes % _ and \ so they are searched for, not treated as wildcards. */
    public static function like(string $word): string
    {
        return '%' . addcslashes($word, '\\%_') . '%';
    }

    /** The words of a search, cleaned and limited. */
    public static function words(?string $term): array
    {
        $term = trim(mb_substr((string) $term, 0, self::MAX_TERM));
        if ($term === '') {
            return [];
        }

        return array_slice(preg_split('/\s+/u', $term), 0, self::MAX_WORDS);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     * @param string[] $columns  columns to search (may be "table.column")
     * @param string|null $idColumn  when set, a purely numeric word also matches this column exactly
     */
    public static function search($query, ?string $term, array $columns, ?string $idColumn = null)
    {
        foreach (self::words($term) as $word) {
            $query->where(function ($q) use ($word, $columns, $idColumn) {
                foreach ($columns as $col) {
                    $q->orWhere($col, 'like', self::like($word));
                }
                if ($idColumn !== null && ctype_digit($word)) {
                    $q->orWhere($idColumn, (int) $word);
                }
            });
        }

        return $query;
    }

    /**
     * Orders by one of the declared choices and returns the key used.
     *
     * @param array<string,array{0:string,1?:string}> $map key => [column, direction]; the direction defaults to asc
     */
    public static function sort($query, ?string $key, array $map, string $default): string
    {
        $key = isset($map[(string) $key]) ? (string) $key : $default;
        [$column, $dir] = $map[$key] + [1 => 'asc'];
        $query->orderBy($column, strtolower($dir) === 'desc' ? 'desc' : 'asc');
        // A stable order for rows that tie, so paging never repeats or skips a row.
        if ($column !== 'id' && !str_ends_with($column, '.id')) {
            $query->orderBy(str_contains($column, '.') ? explode('.', $column)[0] . '.id' : 'id', 'desc');
        }

        return $key;
    }

    /** A valid Y-m-d date, or '' (so a mistyped or hostile value is simply ignored). */
    public static function date($v): string
    {
        $v = trim((string) $v);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            return '';
        }
        try {
            // Carbon rolls 2026-13-45 over into 2027-02-14; only a date that reads back the same is real.
            $d = \Carbon\Carbon::createFromFormat('!Y-m-d', $v);

            return $d && $d->format('Y-m-d') === $v ? $v : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    public static function perPage(Request $request, array $allowed = [20, 50, 100], ?int $default = null): int
    {
        $default ??= $allowed[0];
        $n = (int) $request->query('per_page', $default);

        return in_array($n, $allowed, true) ? $n : $default;
    }
}
