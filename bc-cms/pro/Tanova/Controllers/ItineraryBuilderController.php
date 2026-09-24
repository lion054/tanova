<?php

namespace Pro\Tanova\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Support\FilterBar;
use App\Support\ListQuery;
use Pro\Tanova\Models\ItineraryDay;
use Pro\Tanova\Models\ItineraryTemplate;
use Pro\Tanova\Models\TanovaMeal;
use Pro\Tanova\Models\TanovaRestaurant;
use Pro\Tanova\Services\DocumentTextExtractor;
use Pro\Tanova\Services\ItineraryDocumentImporter;

/**
 * Tanova port, phase 3 — manual itinerary builder.
 *
 * The portal could already generate an itinerary with AI but never edit one. This
 * adds day-by-day authoring, with the source builder's validation surfaced as
 * warnings rather than hard errors so a draft can stay half-finished.
 */
class ItineraryBuilderController extends Controller
{
    public function index(Request $request)
    {
        $base = ItineraryTemplate::withCount('days');
        $everything = ItineraryTemplate::count();
        ListQuery::search($base, $request->query('s'), ['name', 'description']);
        $lengths = ['short' => __('1 to 3 days'), 'week' => __('4 to 7 days'), 'long' => __('8 days or more')];
        match ((string) $request->query('length')) {
            'short' => $base->where('total_days', '<=', 3),
            'week'  => $base->whereBetween('total_days', [4, 7]),
            'long'  => $base->where('total_days', '>=', 8),
            default => null,
        };
        ListQuery::sort($base, $request->query('sort'), ['newest' => ['id', 'desc'], 'name' => ['name', 'asc'], 'days' => ['total_days', 'desc']], 'newest');
        $fb = FilterBar::make($request)->search('s', __('Search itineraries'))->select('length', __('Length'), $lengths, __('Any length'))
            ->sort(['newest' => __('Newest first'), 'name' => __('Name A to Z'), 'days' => __('Longest first')], 'newest')->perPage()->noun(__('itineraries'))
            ->total((clone $base)->reorder()->count(), $everything)->toArray();
        $rows = $base->paginate(ListQuery::perPage($request))->withQueryString();

        return view('vendor.itineraries.index', [
            'rows'       => $rows,
            'fb'         => $fb,
            'page_title' => __('Itinerary Builder'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:191'],
            'description'  => ['nullable', 'string', 'max:5000'],
            'total_days'   => ['required', 'integer', 'min:1', 'max:365'],
            'total_nights' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        $data['total_nights'] = $data['total_nights'] ?? max(0, $data['total_days'] - 1);

        $template = ItineraryTemplate::create($data);

        // Seed empty days so the builder opens with something to fill in.
        for ($i = 1; $i <= $template->total_days; $i++) {
            ItineraryDay::create(['template_id' => $template->id, 'day_number' => $i]);
        }

        return redirect()->route('vendor.itineraries.edit', $template->id)
            ->with('success', __('Itinerary created — :n days ready to fill in.', ['n' => $template->total_days]));
    }

    public function edit(ItineraryTemplate $itinerary)
    {
        $itinerary->load('days');

        return view('vendor.itineraries.edit', [
            'template'    => $itinerary,
            'warnings'    => $itinerary->warnings(),
            'meals'       => TanovaMeal::published()->orderBy('name')->get(),
            'restaurants' => TanovaRestaurant::published()->orderBy('name')->get(),
            'page_title'  => $itinerary->name,
        ]);
    }

    public function update(Request $request, ItineraryTemplate $itinerary)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:191'],
            'description'  => ['nullable', 'string', 'max:5000'],
            'total_days'   => ['required', 'integer', 'min:1', 'max:365'],
            'total_nights' => ['nullable', 'integer', 'min:0', 'max:365'],
            'status'       => ['nullable', 'in:draft,publish'],
        ]);

        // Publishing is gated on the itinerary actually being complete — this is the
        // one place the warnings become an error, because a published itinerary with
        // empty days is customer-visible.
        if (($data['status'] ?? null) === 'publish') {
            $itinerary->load('days');
            if (!$itinerary->isPublishable()) {
                return back()->with('error', __('Fix the warnings before publishing.'));
            }
        }

        $itinerary->update($data);

        return redirect()->route('vendor.itineraries.edit', $itinerary->id)
            ->with('success', __('Itinerary updated.'));
    }

    public function destroy(ItineraryTemplate $itinerary)
    {
        $itinerary->delete();   // days cascade

        return redirect()->route('vendor.itineraries.index')
            ->with('success', __('Itinerary deleted.'));
    }

    /**
     * Import an itinerary from a Word/PDF/text document.
     *
     * Always produces a DRAFT — extraction is best-effort (see
     * DocumentTextExtractor), so a person confirms it before it goes live.
     */
    public function import(
        Request $request,
        DocumentTextExtractor $extractor,
        ItineraryDocumentImporter $importer
    ) {
        $request->validate([
            'document' => ['required', 'file', 'max:10240', 'mimes:pdf,docx,txt,md'],
            'name'     => ['nullable', 'string', 'max:191'],
        ], [
            'document.mimes' => __('Upload a PDF, Word (.docx), or text file.'),
            'document.max'   => __('That file is larger than 10 MB.'),
        ]);

        $file = $request->file('document');
        $name = $request->input('name') ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        try {
            $text = $extractor->extract($file->getRealPath(), $file->getClientOriginalExtension());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        if (trim($text) === '') {
            return back()->with('error', __('That document appears to be empty.'));
        }

        $result = $importer->import($text, $name);

        $message = $result['unmatched']
            ? __('Imported, but no "Day 1", "Day 2"… headings were found — the text is all in day 1 for you to split up.')
            : __('Imported :n days. Check each one before publishing.', ['n' => $result['days']]);

        return redirect()->route('vendor.itineraries.edit', $result['template']->id)
            ->with('success', $message);
    }

    /** Save one day. Kept separate so a long itinerary saves a day at a time. */
    public function saveDay(Request $request, ItineraryTemplate $itinerary, ItineraryDay $day)
    {
        abort_unless($day->template_id === $itinerary->id, 404);

        $data = $request->validate([
            'title'            => ['nullable', 'string', 'max:191'],
            'description'      => ['nullable', 'string', 'max:5000'],
            'location'         => ['nullable', 'string', 'max:191'],
            'restaurant_id'    => ['nullable', 'integer'],
            'meal_ids'         => ['nullable', 'array'],
            'meal_ids.*'       => ['integer'],
            'notes'            => ['nullable', 'string', 'max:2000'],
        ]);

        // Only keep references the vendor actually owns — the scope does the check.
        if (!empty($data['meal_ids'])) {
            $data['meal_ids'] = TanovaMeal::whereIn('id', $data['meal_ids'])->pluck('id')->all();
        }

        if (!empty($data['restaurant_id']) && !TanovaRestaurant::whereKey($data['restaurant_id'])->exists()) {
            $data['restaurant_id'] = null;
        }

        $day->update($data);

        return back()->with('success', __('Day :n saved.', ['n' => $day->day_number]));
    }

    /** Append a day at the end and widen the template to match. */
    public function addDay(ItineraryTemplate $itinerary)
    {
        $next = (int) $itinerary->days()->max('day_number') + 1;

        ItineraryDay::create(['template_id' => $itinerary->id, 'day_number' => $next]);
        $itinerary->update(['total_days' => max($itinerary->total_days, $next)]);

        return back()->with('success', __('Day :n added.', ['n' => $next]));
    }

    /** Delete a day and close the gap so numbering stays contiguous. */
    public function deleteDay(ItineraryTemplate $itinerary, ItineraryDay $day)
    {
        abort_unless($day->template_id === $itinerary->id, 404);

        $removed = $day->day_number;
        $day->delete();

        $itinerary->days()->where('day_number', '>', $removed)->decrement('day_number');
        $itinerary->update(['total_days' => max(1, $itinerary->days()->count())]);

        return back()->with('success', __('Day :n removed.', ['n' => $removed]));
    }
}
