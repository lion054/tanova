<?php

namespace Pro\Integrations\Services\Fiscal;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Booking\Models\Booking;
use Pro\Integrations\Models\Integration;

/**
 * Wraps the Fiscalize API (https://docs.fiscalize.co.zw/docs/intro).
 *
 * Handles: authentication (login + refresh), invoice submission,
 * credit note / debit note submission.
 *
 * Token lifecycle:
 *   - Access token: 24 h, cached under 'fiscalize_access_token'
 *   - Refresh token: 90 days, stored in the Integration credentials
 */
class FiscalizeService
{
    protected string $base;
    protected string $apiKey;
    protected string $apiSecret;

    const TOKEN_CACHE_KEY   = 'fiscalize_access_token';
    const TOKEN_TTL_SECONDS = 82800; // 23 h (before 24 h expiry)

    public function __construct()
    {
        $creds = Integration::forSlug('fiscalize')->credentials;

        $this->apiKey    = $creds['api_key']    ?? '';
        $this->apiSecret = $creds['api_secret'] ?? '';
        $this->base      = rtrim($creds['base_url'] ?? 'https://fiscalize.erpona.com:8090/api', '/');
    }

    // -------------------------------------------------------------------------
    // High-level: fiscalize a Booking
    // -------------------------------------------------------------------------

    /**
     * Submit a GoTrip booking as a fiscalized invoice to ZIMRA via Fiscalize.
     * Returns the Fiscalize response (with receiptNo, qrCode, signatures) or null.
     */
    public function fiscalizeBooking(Booking $booking): ?array
    {
        $customer = $booking->customer_name ?? 'Guest';
        $email    = $booking->customer_email ?? '';

        $payload = [
            'docNo'        => 'BK-' . $booking->id,
            'customerCode' => 'GUEST-' . ($booking->customer_id ?? $booking->id),
            'customerName' => $customer,
            'total'        => (float) $booking->total,
            'currency'     => strtoupper($booking->currency ?? 'USD'),
            'docDate'      => now()->toIso8601String(),
            'dueDate'      => now()->toIso8601String(),
            'notes'        => "Booking #{$booking->id} — {$booking->object_model}",
            'buyerData'    => [
                'registeredName' => $customer,
                'tradeName'      => $customer,
                'contacts'       => array_filter(['email' => $email]),
                'address'        => [
                    'province' => '',
                    'city'     => '',
                    'street'   => '',
                    'houseNo'  => '',
                ],
            ],
            'payments' => [
                [
                    'paymentType'   => $this->mapGatewayToPaymentType($booking->gateway),
                    'paymentAmount' => (float) $booking->total,
                ],
            ],
            'lines' => [
                [
                    'lineNo'             => 1,
                    'itemCode'           => strtoupper($booking->object_model ?? 'SVC'),
                    'itemName'           => $booking->getServiceName() ?? "Booking #{$booking->id}",
                    'itemHsCode'         => '',
                    'price'              => (float) $booking->total,
                    'isFinalPrice'       => true,
                    'quantity'           => 1,
                    'discountPercentage' => 0,
                    'lineTotal'          => (float) $booking->total,
                    'taxPercent'         => 15.0,
                    'taxCategory'        => 'Rated',
                ],
            ],
        ];

        return $this->submitInvoice($payload);
    }

    // -------------------------------------------------------------------------
    // API: Invoice
    // -------------------------------------------------------------------------

    public function submitInvoice(array $payload): ?array
    {
        return $this->post('/invoices/Devices', $payload);
    }

    public function resubmitInvoice(array $payload): ?array
    {
        return $this->post('/invoices/Devices/Resubmit', $payload);
    }

    // -------------------------------------------------------------------------
    // API: Credit / Debit Notes
    // -------------------------------------------------------------------------

    public function submitCreditNote(string $baseDocNo, array $payload): ?array
    {
        return $this->post('/creditnotes/Devices', array_merge($payload, [
            'baseDocNo'   => $baseDocNo,
            'isDebitNote' => false,
        ]));
    }

    public function submitDebitNote(string $baseDocNo, array $payload): ?array
    {
        return $this->post('/creditnotes/Devices', array_merge($payload, [
            'baseDocNo'   => $baseDocNo,
            'isDebitNote' => true,
        ]));
    }

    // -------------------------------------------------------------------------
    // Authentication
    // -------------------------------------------------------------------------

    public function getAccessToken(): ?string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, self::TOKEN_TTL_SECONDS, function () {
            return $this->login();
        });
    }

    protected function login(): ?string
    {
        try {
            $response = Http::post("{$this->base}/deviceauths/Login", [
                'apiKey'    => $this->apiKey,
                'apiSecret' => $this->apiSecret,
            ]);

            if ($response->failed()) {
                Log::error('Fiscalize login failed: ' . $response->body());
                return null;
            }

            $data = $response->json();
            $token = $data['token'] ?? null;

            // Persist the refresh token back into Integration credentials
            if (!empty($data['refreshToken'])) {
                $integration = Integration::forSlug('fiscalize');
                $creds = $integration->credentials;
                $creds['refresh_token'] = $data['refreshToken'];
                $integration->credentials = $creds;
                $integration->save();
            }

            return $token;
        } catch (\Throwable $e) {
            Log::error('Fiscalize login exception: ' . $e->getMessage());
            return null;
        }
    }

    protected function refreshToken(): ?string
    {
        $integration  = Integration::forSlug('fiscalize');
        $refreshToken = $integration->credential('refresh_token');

        if (!$refreshToken) {
            return $this->login();
        }

        try {
            $response = Http::post("{$this->base}/deviceauths/RefreshToken", [
                'refreshToken' => $refreshToken,
            ]);

            if ($response->failed()) {
                Cache::forget(self::TOKEN_CACHE_KEY);
                return $this->login();
            }

            $data  = $response->json();
            $token = $data['token'] ?? null;

            // Refresh token rotation — store the new one
            if (!empty($data['refreshToken'])) {
                $creds = $integration->credentials;
                $creds['refresh_token'] = $data['refreshToken'];
                $integration->credentials = $creds;
                $integration->save();
            }

            Cache::put(self::TOKEN_CACHE_KEY, $token, self::TOKEN_TTL_SECONDS);
            return $token;
        } catch (\Throwable $e) {
            Log::error('Fiscalize token refresh exception: ' . $e->getMessage());
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // HTTP primitive with auto-refresh on 401
    // -------------------------------------------------------------------------

    protected function post(string $path, array $body, bool $isRetry = false): ?array
    {
        $token = $this->getAccessToken();

        if (!$token) {
            Log::error("Fiscalize: no access token available for {$path}");
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->post("{$this->base}{$path}", $body);

            if ($response->status() === 401 && !$isRetry) {
                Cache::forget(self::TOKEN_CACHE_KEY);
                $newToken = $this->refreshToken();
                if ($newToken) {
                    return $this->post($path, $body, true);
                }
                return null;
            }

            if ($response->failed()) {
                Log::warning("Fiscalize error [{$path}]", [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error("Fiscalize exception [{$path}]: " . $e->getMessage());
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function mapGatewayToPaymentType(string $gateway = null): string
    {
        return match (strtolower($gateway ?? '')) {
            'stripe', 'paypal', 'payrexx', 'twocheckout', 'razorpay' => 'Card',
            'mpesa', 'ecocash', 'tigopesa', 'airtel'                 => 'MobileWallet',
            'flutterwave', 'paystack'                                  => 'Card',
            'bank_transfer'                                            => 'BankTransfer',
            default                                                    => 'Cash',
        };
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->apiSecret);
    }
}
