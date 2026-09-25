<?php

namespace Pro\Integrations\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Pro\Integrations\Models\Integration;
use Pro\Integrations\Models\IntegrationRoomChannel;
use Pro\Integrations\Services\IntegrationRegistry;
use Pro\Integrations\Services\IntegrationsNav;
use Pro\Integrations\Services\Wetu\WetuService;
use Pro\Tanova\Models\TanovaTrip;

class IntegrationsAdminController extends Controller
{

    /** Main hub — all OS categories as cards */
    public function hub()
    {
        $nav          = IntegrationsNav::current();
        $all          = IntegrationRegistry::all();
        $integrations = Integration::forVendor()->pluck('status', 'slug');

        $categories = [
            'stay_os'       => ['label' => 'Stay OS',        'icon' => 'ion ion-ios-bed',         'color' => '#1a73e8', 'desc' => 'Hotels, lodges & PMS channels'],
            'exp_os'        => ['label' => 'Exp OS',         'icon' => 'ion ion-ios-compass',     'color' => '#B8722E', 'desc' => 'Experiences & activity booking'],
            'trans_os'      => ['label' => 'Trans OS',       'icon' => 'ion ion-ios-car',         'color' => '#2E7D32', 'desc' => 'Ground transfers & fleet'],
            'airline_os'    => ['label' => 'Airline OS',     'icon' => 'ion ion-ios-airplane',    'color' => '#7B1FA2', 'desc' => 'Airline PSS & GDS connectivity'],
            'sale_os'       => ['label' => 'Sale OS',        'icon' => 'ion ion-ios-briefcase',   'color' => '#00838F', 'desc' => 'Travel agents, DMCs & CRM'],
            'payment'       => ['label' => 'Payments',       'icon' => 'ion ion-ios-card',        'color' => '#E65100', 'desc' => 'Payment gateways & mobile money'],
            'communication' => ['label' => 'Communications', 'icon' => 'ion ion-ios-chatboxes',   'color' => '#37474F', 'desc' => 'Email, SMS, WhatsApp & AI'],
            'fiscal'        => ['label' => 'Fiscal & Tax',   'icon' => 'ion ion-ios-receipt',     'color' => '#1B5E20', 'desc' => 'ZIMRA, TRA, KRA, SARS fiscal compliance'],
        ];

        // Count connected integrations per category
        $counts = [];
        foreach ($all as $cat => $items) {
            $counts[$cat] = collect($items)
                ->filter(fn($i) => ($integrations[$i['slug']] ?? 'disconnected') === 'connected')
                ->count();
        }

        return view('Integrations::admin.hub', compact('categories', 'all', 'integrations', 'counts', 'nav'));
    }

    /** Category page — shows all integrations in that OS */
    public function category(string $cat)
    {
        $nav   = IntegrationsNav::current();
        $items = IntegrationRegistry::category($cat);
        $slugs = array_column($items, 'slug');

        $saved        = $slugs ? Integration::forVendor()->whereIn('slug', $slugs)->get()->keyBy('slug') : collect();

        $rentals      = $this->getRentals();
        $roomChannels = $slugs ? IntegrationRoomChannel::whereIn('integration_slug', $slugs)->get()
            ->groupBy('integration_slug') : collect();

        $catMeta = [
            'stay_os'       => ['label' => 'Stay OS',       'icon' => 'ion ion-ios-bed',      'color' => '#1a73e8'],
            'exp_os'        => ['label' => 'Exp OS',        'icon' => 'ion ion-ios-compass',  'color' => '#B8722E'],
            'trans_os'      => ['label' => 'Trans OS',      'icon' => 'ion ion-ios-car',      'color' => '#2E7D32'],
            'airline_os'    => ['label' => 'Airline OS',    'icon' => 'ion ion-ios-airplane', 'color' => '#7B1FA2'],
            'sale_os'       => ['label' => 'Sale OS',       'icon' => 'ion ion-ios-briefcase','color' => '#00838F'],
            'payment'       => ['label' => 'Payments',      'icon' => 'ion ion-ios-card',     'color' => '#E65100'],
            'communication' => ['label' => 'Communications','icon' => 'ion ion-ios-chatboxes','color' => '#37474F'],
            'fiscal'        => ['label' => 'Fiscal & Tax',  'icon' => 'ion ion-ios-receipt',  'color' => '#1B5E20'],
        ][$cat] ?? ['label' => ucfirst($cat), 'icon' => 'ion ion-ios-apps', 'color' => '#333'];

        return view('Integrations::admin.category', compact(
            'cat', 'items', 'saved', 'rentals', 'roomChannels', 'catMeta', 'nav'
        ));
    }

    /** Save / connect an integration */
    public function connect(Request $request, string $slug)
    {
        $definition = IntegrationRegistry::find($slug);
        if (!$definition) {
            abort(404);
        }

        // Build validation rules from registry field definitions
        $rules = [];
        foreach ($definition['fields'] ?? [] as $field) {
            if (!empty($field['required'])) {
                $rules[$field['key']] = 'required|string';
            } else {
                $rules[$field['key']] = 'nullable|string';
            }
        }

        $validated = $request->validate($rules);

        $integration = Integration::firstOrNew(['slug' => $slug, 'author_id' => Auth::id()]);
        $integration->author_id   = Auth::id();
        $integration->category    = $definition['category'] ?? $this->inferCategory($slug);
        $integration->credentials = array_filter($validated);
        $integration->status      = Integration::STATUS_CONNECTED;
        $integration->create_user = $integration->create_user ?? Auth::id();
        $integration->update_user = Auth::id();
        $integration->save();

        return back()->with('success', "{$definition['name']} connected successfully.");
    }

    /** Disconnect / remove an integration */
    public function disconnect(string $slug)
    {
        Integration::forVendor()->where('slug', $slug)->update([
            'status'      => Integration::STATUS_DISCONNECTED,
            'credentials' => null,
            'last_error'  => null,
        ]);

        return back()->with('success', ucfirst(str_replace('_', ' ', $slug)) . ' disconnected.');
    }

    /** Test an existing connection (quick ping) */
    public function test(string $slug)
    {
        $integration = Integration::forSlug($slug, Auth::id());

        if ($slug === 'wetu') {
            $svc = WetuService::fromIntegration();
            $ok  = $svc && $svc->ping();
        } else {
            // Generic: just confirm credentials are stored
            $ok = $integration->isConnected() && !empty($integration->credentials);
        }

        $integration->update([
            'status'           => $ok ? Integration::STATUS_CONNECTED : Integration::STATUS_ERROR,
            'last_verified_at' => now(),
            'last_error'       => $ok ? null : 'Connection test failed — check credentials.',
        ]);

        $msg = $ok ? 'Connection verified.' : 'Test failed — check credentials and try again.';
        return back()->with($ok ? 'success' : 'error', $msg);
    }

    // -------------------------------------------------------------------------
    // Legals
    // -------------------------------------------------------------------------

    public function legals()
    {
        $docs = $this->legalDocsMeta();
        return view('Integrations::admin.legals.index', compact('docs'));
    }

    public function legalEdit(string $doc)
    {
        $meta = $this->legalDocsMeta()[$doc] ?? null;
        if (!$meta) {
            abort(404);
        }

        $content = setting_item("legal_{$doc}_content", '');
        $updatedAt = setting_item("legal_{$doc}_updated_at");

        return view('Integrations::admin.legals.edit', compact('doc', 'meta', 'content', 'updatedAt'));
    }

    public function legalSave(Request $request, string $doc)
    {
        $meta = $this->legalDocsMeta()[$doc] ?? null;
        if (!$meta) {
            abort(404);
        }

        $request->validate(['content' => 'required|string']);

        \Modules\Core\Models\Settings::store("legal_{$doc}_content", $request->content);
        \Modules\Core\Models\Settings::store("legal_{$doc}_updated_at", now()->toDateTimeString());

        return back()->with('success', "{$meta['title']} saved.");
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function getRentals(): array
    {
        return [];
    }

    protected function legalDocsMeta(): array
    {
        return [
            'tos'     => ['title' => 'Terms of Service',          'icon' => 'ion ion-ios-paper',     'desc' => 'User terms governing use of the platform'],
            'privacy' => ['title' => 'Privacy Policy',            'icon' => 'ion ion-ios-eye-off',   'desc' => 'How personal data is collected and used'],
            'dpa'     => ['title' => 'Data Processing Agreement', 'icon' => 'ion ion-ios-lock',      'desc' => 'GDPR-compliant DPA for B2B partners'],
            'cookie'  => ['title' => 'Cookie Policy',             'icon' => 'ion ion-ios-browsers',  'desc' => 'Cookies and tracking disclosure'],
            'refund'  => ['title' => 'Cancellation & Refund Policy','icon'=>'ion ion-ios-return-left','desc' => 'Guest cancellation and refund terms'],
            'vendor'  => ['title' => 'Vendor / Host Agreement',   'icon' => 'ion ion-ios-people',    'desc' => 'Terms for property owners and operators'],
        ];
    }

    protected function inferCategory(string $slug): string
    {
        foreach (IntegrationRegistry::all() as $cat => $items) {
            foreach ($items as $item) {
                if ($item['slug'] === $slug) {
                    return $cat;
                }
            }
        }
        return 'unknown';
    }

    // -------------------------------------------------------------------------
    // Wetu-specific actions
    // -------------------------------------------------------------------------

    /** Show Wetu itinerary list pulled from the operator's account. */
    public function wetuItineraries(Request $request)
    {
        $svc = WetuService::fromIntegration();
        if (!$svc) {
            return redirect(IntegrationsNav::current()->category('exp_os'))
                ->withErrors(['wetu' => 'Connect Wetu first.']);
        }

        $filters = $request->only(['search', 'type', 'booking_status', 'page_start']);
        $data    = $svc->listItineraries($filters);

        return view('Integrations::admin.wetu.itineraries', [
            'nav'           => IntegrationsNav::current(),
            'itineraries'   => $data['itineraries'],
            'total'         => $data['total'],
            'filters'       => $filters,
            'connectOk'     => Integration::forSlug('wetu')->credential('connect_key') !== null,
        ]);
    }

    /** Force-refresh Wetu itinerary cache. */
    public function wetuSync()
    {
        $svc = WetuService::fromIntegration();
        if (!$svc) {
            return back()->withErrors(['wetu' => 'Wetu not connected.']);
        }

        $data = $svc->listItineraries(['bust' => true]);
        $count = count($data['itineraries']);

        return back()->with('success', "Synced {$count} itineraries from Wetu.");
    }

    /** Import a single Wetu itinerary into Tanova as a trip. */
    public function wetuImport(string $identifier)
    {
        $svc = WetuService::fromIntegration();
        if (!$svc) {
            return back()->withErrors(['wetu' => 'Wetu not connected.']);
        }

        $raw = $svc->getItinerary($identifier);
        if (!$raw) {
            return back()->withErrors(['wetu' => "Could not load itinerary {$identifier} from Wetu."]);
        }

        $payload = WetuService::toTanovaPayload($raw);
        $payload['user_id']     = auth()->id();
        $payload['create_user'] = auth()->id();

        $trip = TanovaTrip::create($payload);

        return redirect()->route('admin.tanova.show', $trip)
            ->with('success', "Wetu itinerary imported as Tanova trip #{$trip->id}.");
    }
}
