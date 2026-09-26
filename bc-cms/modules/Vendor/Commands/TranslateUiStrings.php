<?php

namespace Modules\Vendor\Commands;

use Illuminate\Console\Command;
use Modules\Language\Models\Language;
use Modules\Vendor\Services\AiTranslator;
use Modules\Vendor\Services\UiStrings;

/** Has AI write the interface words of the public service page in each active language that lacks them. Safe to repeat: it only adds. */
class TranslateUiStrings extends Command
{
    protected $signature = 'languages:translate-ui {locale?* : only these languages} {--dry-run : list what is missing, write nothing}';
    protected $description = 'Translate the public page interface into the active languages';

    public function handle(): int
    {
        $default = (string) setting_item('site_locale');
        $langs = collect(Language::getActive())->reject(fn ($l) => $l->locale === $default)->unique('locale');
        if ($only = (array) $this->argument('locale')) {
            $langs = $langs->whereIn('locale', $only);
        }
        if (!AiTranslator::configured() && !$this->option('dry-run')) {
            $this->error('DEEPSEEK_API_KEY is not set.');

            return self::FAILURE;
        }
        foreach ($langs as $l) {
            $missing = count(UiStrings::missing($l->locale));
            if ($this->option('dry-run')) {
                $this->line("{$l->name} ({$l->locale}): {$missing} missing");
                continue;
            }
            try {
                $n = $missing ? UiStrings::fill($l->locale, $l->name) : 0;
                $this->info("{$l->name} ({$l->locale}): {$n} of {$missing} added");
            } catch (\Throwable $e) {
                $this->warn("{$l->name}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
