<?php

namespace Modules\Vendor\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Modules\Vendor\Services\ServiceTranslations as ST;

/**
 * Translations written before the dashboard could tell "the same on purpose" from "not done" (a hotel's name reads the same in French)
 * left those pieces looking unfinished. In a row that was really translated (at least one piece differs from the original), a SHORT piece
 * that reads the same is a name or a loan word, so it is marked done. A long piece that reads the same is left as still to translate.
 */
class BackfillTranslationMarks extends Command
{
    protected $signature = 'languages:backfill-marks {--dry-run}';
    protected $description = 'Mark short same-as-original pieces of translated services as done';

    public function handle(): int
    {
        $default = (string) setting_item('site_locale');
        $total = 0;
        foreach (ST::types() as $type => $t) {
            $modelClass = $t['class'];
            $transClass = (new $modelClass())->getTranslationModelName();
            $cols = Schema::getColumnListing((new $transClass())->getTable());
            $fields = array_values(array_intersect($t['fields'], $cols));
            $transClass::where('locale', '!=', $default)->orderBy('id')->chunkById(200, function ($rows) use ($type, $modelClass, $fields, &$total) {
                $models = $modelClass::whereIn('id', $rows->pluck('origin_id'))->get()->keyBy('id');
                foreach ($rows as $row) {
                    $m = $models->get($row->origin_id);
                    if (!$m) {
                        continue;
                    }
                    $prog = ST::progress($m, $row, $fields);
                    if (array_sum($prog['fields']) == 0) {
                        continue;   // nothing was really translated in this row
                    }
                    $marks = [];
                    foreach ($fields as $f) {
                        if (ST::kind($f) === 'list') {
                            foreach (ST::listRows($m, $row, $f) as $e) {
                                if (!$e['done'] && $this->short($e['src']) && $this->hasText($row, $f, $e['path'])) {
                                    $marks[] = $f . ':' . $e['path'];
                                }
                            }
                        } else {
                            $src = ST::kind($f) === 'html' ? ST::toText($m->getAttribute($f)) : trim((string) $m->getAttribute($f));
                            $tr = ST::kind($f) === 'html' ? ST::toText($row->getAttribute($f)) : trim((string) $row->getAttribute($f));
                            if ($src !== '' && $tr === $src && $this->short($src)) {
                                $marks[] = $f;
                            }
                        }
                    }
                    if ($marks && !$this->option('dry-run')) {
                        foreach ($marks as $piece) {
                            \Illuminate\Support\Facades\DB::table('vendor_translation_marks')->updateOrInsert(['kind' => $type, 'origin_id' => $row->origin_id, 'locale' => $row->locale, 'piece' => $piece], ['updated_at' => now(), 'created_at' => now()]);
                        }
                    }
                    $total += count($marks);
                }
            });
        }
        $this->info(($this->option('dry-run') ? 'Would mark ' : 'Marked ') . $total . ' piece(s).');

        return self::SUCCESS;
    }

    private function short(string $s): bool
    {
        return mb_strlen($s) <= 80 && !str_contains($s, "\n");
    }

    private function hasText($row, string $field, string $path): bool
    {
        foreach (ST::entries($field, $row->getAttribute($field)) as $e) {
            if ($e['path'] === $path) {
                return true;
            }
        }

        return false;
    }
}
