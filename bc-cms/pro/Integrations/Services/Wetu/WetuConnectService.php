<?php

namespace Pro\Integrations\Services\Wetu;

use Illuminate\Support\Facades\Http;
use Pro\Integrations\Models\Integration;

/**
 * Wetu Connect API — pushes bookings/quotes from Tsoka into Wetu.
 *
 * This is the "incoming" side from Wetu's perspective: Tsoka is the TOS
 * that sends data to Wetu so agents can see it in their itinerary builder.
 *
 * Registration: https://forms.office.com/r/GrhzwMwhdi
 * Docs: https://trip-solutions.helpscoutdocs.com/article/833-connect-api-incoming
 */
class WetuConnectService
{
    const BASE = 'https://wetu.com/Connect/API';

    public function __construct(protected string $connectKey) {}

    public static function fromIntegration(): ?self
    {
        $i   = Integration::forSlug('wetu');
        $key = $i->credential('connect_key');
        return $key ? new self($key) : null;
    }

    // ── Bookings endpoint ─────────────────────────────────────────────────

    /** Push (create or update) a booking in Wetu. Returns ['ok', 'status', 'body']. */
    public function pushBooking(array $payload): array
    {
        try {
            $r = Http::timeout(20)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(self::BASE . "/Bookings/{$this->connectKey}", $payload);

            return [
                'ok'     => $r->successful(),
                'status' => $r->status(),
                'body'   => $r->json() ?? $r->body(),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => 0, 'body' => $e->getMessage()];
        }
    }

    /** Retrieve a booking from Wetu by reference. */
    public function getBooking(string $reference): ?array
    {
        try {
            $r = Http::timeout(10)->get(self::BASE . "/Bookings/{$this->connectKey}", [
                'reference' => $reference,
            ]);
            return $r->successful() ? $r->json() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    // ── Mappings endpoint (read-only) ─────────────────────────────────────

    /** Get supplier/service mappings configured for this Connect key. */
    public function getMappings(): array
    {
        try {
            $r = Http::timeout(10)->get(self::BASE . "/Mappings/{$this->connectKey}");
            return $r->successful() ? ($r->json() ?? []) : [];
        } catch (\Throwable) {
            return [];
        }
    }

    // ── Locations endpoint ────────────────────────────────────────────────

    public function getLocations(): array
    {
        try {
            $r = Http::timeout(10)->get(self::BASE . "/Locations/{$this->connectKey}");
            return $r->successful() ? ($r->json() ?? []) : [];
        } catch (\Throwable) {
            return [];
        }
    }

    // ── Payload builder ───────────────────────────────────────────────────

    /**
     * Build a Wetu Connect-compatible booking payload from a flat Tsoka booking array.
     *
     * Wetu Connect schema fields:
     *   Name, Reference, TravelDate, BookingStatus, ConsultantEmail, Language,
     *   Travellers[], Services[]
     *
     * BookingStatus values: AwaitingQuotation, Quoted, Booked, Confirmed, Invoiced,
     *   PaymentDue, Paid, Travelled, Cancelled, Provisional
     */
    public static function buildPayload(array $booking): array
    {
        $travellers = array_map(fn($t) => array_filter([
            'Type'  => $t['type']  ?? 'Adult',
            'Title' => $t['title'] ?? 'Mr',
            'Name'  => $t['name']  ?? '',
            'Age'   => isset($t['age']) ? (int) $t['age'] : null,
        ]), $booking['travellers'] ?? []);

        // At least one traveller required
        if (empty($travellers)) {
            $travellers = [['Type' => 'Adult', 'Title' => 'Mr', 'Name' => $booking['name'] ?? 'Guest']];
        }

        $services = [];
        foreach ($booking['services'] ?? [] as $svc) {
            $service = array_filter([
                'SystemId'       => (string) ($svc['id'] ?? ''),
                'Name'           => $svc['name']      ?? '',
                'Date'           => $svc['date']      ?? null,
                'EndDate'        => $svc['end_date']  ?? null,
                'StartTime'      => $svc['start_time']?? null,
                'EndTime'        => $svc['end_time']  ?? null,
                'Adults'         => (int) ($svc['adults']   ?? 1),
                'Children'       => (int) ($svc['children'] ?? 0),
                'Rooms'          => isset($svc['rooms'])    ? (int) $svc['rooms'] : null,
                'RoomName'       => $svc['room_name']       ?? null,
                'Included'       => $svc['included']        ?? true,
                'Note'           => $svc['notes']           ?? null,
                'ExpertTips'     => $svc['expert_tips']     ?? null,
                'ReferenceCodes' => !empty($svc['reference']) ? [$svc['reference']] : [],
                'Sequence'       => isset($svc['sequence'])  ? (int) $svc['sequence'] : null,
                'Agency'         => $svc['agency']           ?? null,
                'Vehicle'        => $svc['vehicle']          ?? null,
            ]);
            $services[] = $service;
        }

        return array_filter([
            'Name'            => $booking['name'],
            'Reference'       => $booking['reference'],
            'TravelDate'      => $booking['travel_date']      ?? null,
            'BookingStatus'   => $booking['status']           ?? 'Booked',
            'ConsultantEmail' => $booking['consultant_email'] ?? null,
            'Language'        => $booking['language']         ?? 'en',
            'Introduction'    => $booking['introduction']     ?? null,
            'Price'           => isset($booking['price'])     ? (string) $booking['price'] : null,
            'Included'        => $booking['included']         ?? null,
            'Excluded'        => $booking['excluded']         ?? null,
            'Travellers'      => $travellers,
            'Services'        => $services,
        ]);
    }
}
