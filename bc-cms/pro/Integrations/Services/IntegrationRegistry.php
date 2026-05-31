<?php

namespace Pro\Integrations\Services;

class IntegrationRegistry
{
    public static function all(): array
    {
        return [
            'stay_os'       => static::stayOs(),
            'exp_os'        => static::expOs(),
            'trans_os'      => static::transOs(),
            'airline_os'    => static::airlineOs(),
            'sale_os'       => static::saleOs(),
            'payment'       => static::payments(),
            'communication' => static::communications(),
            'fiscal'        => static::fiscal(),
        ];
    }

    public static function category(string $key): array
    {
        return static::all()[$key] ?? [];
    }

    public static function find(string $slug): ?array
    {
        foreach (static::all() as $integrations) {
            foreach ($integrations as $item) {
                if ($item['slug'] === $slug) {
                    return $item;
                }
            }
        }
        return null;
    }

    // -------------------------------------------------------------------------

    private static function stayOs(): array
    {
        return [
            [
                'slug'         => 'airbnb',
                'name'         => 'Airbnb',
                'logo_domain'  => 'airbnb.com',
                'description'  => 'List properties on Airbnb and sync availability & rates directly from your PMS calendar.',
                'color'        => '#FF5A5F',
                'capabilities' => [
                    'Sync availability & blocked dates',
                    'Push rates and minimum stays',
                    'Receive new booking notifications',
                    'Auto-update listing calendars',
                ],
                'fields' => [
                    ['key' => 'api_key',    'label' => 'API Key',    'type' => 'password', 'required' => true,
                     'hint' => 'Found in your Airbnb Host Account → API Access.'],
                    ['key' => 'listing_id', 'label' => 'Listing ID', 'type' => 'text',     'required' => false,
                     'hint' => 'Optional: restrict sync to a specific listing.'],
                ],
            ],
            [
                'slug'         => 'booking_com',
                'name'         => 'Booking.com',
                'logo_domain'  => 'booking.com',
                'description'  => 'Connectivity partner API for Booking.com — sync rooms, rates and reservations.',
                'color'        => '#003580',
                'capabilities' => [
                    'Push rooms and rate plans',
                    'Sync real-time availability',
                    'Pull new reservations automatically',
                    'Close-out and min-stay rules',
                ],
                'fields' => [
                    ['key' => 'hotel_id',  'label' => 'Hotel ID',  'type' => 'text',     'required' => true],
                    ['key' => 'username',  'label' => 'Username',  'type' => 'text',     'required' => true],
                    ['key' => 'password',  'label' => 'Password',  'type' => 'password', 'required' => true],
                ],
            ],
            [
                'slug'         => 'expedia',
                'name'         => 'Expedia',
                'logo_domain'  => 'expedia.com',
                'description'  => 'Expedia Group connectivity — distribute your rooms across Expedia, Hotels.com & Vrbo.',
                'color'        => '#1E4784',
                'capabilities' => [
                    'Multi-brand reach (Expedia, Hotels.com, Vrbo)',
                    'Rate and availability sync',
                    'Reservation retrieval',
                    'Booking modification handling',
                ],
                'fields' => [
                    ['key' => 'hotel_id',  'label' => 'Hotel ID',    'type' => 'text',     'required' => true],
                    ['key' => 'client_id', 'label' => 'Client ID',   'type' => 'text',     'required' => true],
                    ['key' => 'secret',    'label' => 'API Secret',  'type' => 'password', 'required' => true],
                ],
            ],
        ];
    }

    private static function expOs(): array
    {
        return [
            [
                'slug'         => 'wetu',
                'name'         => 'Wetu',
                'logo_domain'  => 'wetu.com',
                'description'  => 'Africa-first itinerary & distribution platform. Pull tour packages, read booking status, import trips to Tanova, push confirmed bookings back to Wetu.',
                'color'        => '#2E7D32',
                'panel'        => 'wetu',
                'capabilities' => [
                    'Pull itinerary inventory (tours & packages)',
                    'Read booking status (Quoted → Booked → Paid → Travelled)',
                    'Import itineraries directly into Tanova',
                    'Push confirmed bookings to Wetu via Connect API',
                ],
                'fields'       => [
                    ['key' => 'username',    'label' => 'Wetu Username',    'type' => 'text',     'required' => true,
                     'hint' => 'Your Wetu login email address.'],
                    ['key' => 'password',    'label' => 'Wetu Password',    'type' => 'password', 'required' => true],
                    ['key' => 'app_key',     'label' => 'App Key',          'type' => 'password', 'required' => false,
                     'hint' => 'Required for GetContent calls. Find it in Wetu → Account → API.'],
                    ['key' => 'connect_key', 'label' => 'Connect API Key',  'type' => 'password', 'required' => false,
                     'hint' => 'Enables pushing bookings to Wetu. Register at wetu.com/Connect.'],
                ],
            ],
            [
                'slug'         => 'getyourguide',
                'name'         => 'GetYourGuide',
                'logo_domain'  => 'getyourguide.com',
                'description'  => 'List experiences and activities on GetYourGuide — receive bookings and sync availability.',
                'color'        => '#FF6C00',
                'capabilities' => [
                    'Publish tours & activities',
                    'Sync availability in real time',
                    'Receive booking notifications',
                    'Manage capacity and cutoff times',
                ],
                'fields' => [
                    ['key' => 'api_key',    'label' => 'API Key',    'type' => 'password', 'required' => true],
                    ['key' => 'supplier_id','label' => 'Supplier ID','type' => 'text',     'required' => true],
                ],
            ],
            [
                'slug'         => 'viator',
                'name'         => 'Viator',
                'logo_domain'  => 'viator.com',
                'description'  => 'Tripadvisor\'s experiences platform — distribute tours to millions of Tripadvisor travellers.',
                'color'        => '#34C6CD',
                'capabilities' => [
                    'Distribute to Tripadvisor ecosystem',
                    'Push products and pricing',
                    'Sync availability windows',
                    'Receive instant booking notifications',
                ],
                'fields' => [
                    ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true,
                     'hint' => 'Available in your Viator Supplier Dashboard → API Settings.'],
                ],
            ],
        ];
    }

    private static function transOs(): array
    {
        return [
            [
                'slug'         => 'openstreetmap',
                'name'         => 'OpenStreetMap',
                'logo_domain'  => 'openstreetmap.org',
                'description'  => 'Free and open mapping — routing, geocoding and tile layers via OpenStreetMap + Nominatim. No API key required.',
                'color'        => '#7EBC6F',
                'capabilities' => [
                    'Interactive maps with Leaflet.js',
                    'Geocoding and reverse geocoding via Nominatim',
                    'Route calculation via OSRM / Valhalla',
                    'No usage limits, no billing, fully open-source',
                ],
                'fields'       => [
                    ['key' => 'tile_url', 'label' => 'Custom Tile URL', 'type' => 'text', 'required' => false,
                     'default' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                     'hint'    => 'Leave blank to use the default OSM tile server.'],
                    ['key' => 'nominatim_url', 'label' => 'Nominatim Endpoint', 'type' => 'text', 'required' => false,
                     'default' => 'https://nominatim.openstreetmap.org',
                     'hint'    => 'Override if you self-host Nominatim.'],
                ],
            ],
            [
                'slug'         => 'google_maps',
                'name'         => 'Google Maps',
                'logo_domain'  => 'google.com',
                'description'  => 'Google Maps Platform — Places autocomplete, Directions API, and embeddable maps.',
                'color'        => '#4285F4',
                'capabilities' => [
                    'Places autocomplete for destination search',
                    'Directions & distance matrix',
                    'Embedded map widgets',
                    'Geocoding with high accuracy',
                ],
                'fields' => [
                    ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true,
                     'hint' => 'Enable Maps JavaScript API, Places API, and Directions API in Google Cloud Console.'],
                ],
            ],
        ];
    }

    private static function airlineOs(): array
    {
        return [
            [
                'slug'         => 'amadeus',
                'name'         => 'Amadeus',
                'logo_domain'  => 'amadeus.com',
                'description'  => 'Amadeus Travel APIs — flight search, booking, seat selection and fare rules for any GDS-connected airline.',
                'color'        => '#00539F',
                'capabilities' => [
                    'Multi-airline flight search (Low Fare Search)',
                    'Seat selection and ancillaries',
                    'PNR create, retrieve and cancel',
                    'Real-time fare quotes and ticketing',
                ],
                'fields' => [
                    ['key' => 'client_id',     'label' => 'Client ID',     'type' => 'text',     'required' => true],
                    ['key' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password', 'required' => true],
                    ['key' => 'environment',   'label' => 'Environment',   'type' => 'select',   'required' => true,
                     'options' => ['test' => 'Test / Sandbox', 'production' => 'Production']],
                ],
            ],
            [
                'slug'         => 'sabre',
                'name'         => 'Sabre',
                'logo_domain'  => 'sabre.com',
                'description'  => 'Sabre GDS connectivity for flight availability, pricing, booking and ticketing.',
                'color'        => '#E31837',
                'capabilities' => [
                    'BFM (Best Fare Matcher) searches',
                    'PNR management and ticketing',
                    'Air shopping and repricing',
                    'Queue management',
                ],
                'fields' => [
                    ['key' => 'client_id',     'label' => 'Client ID',     'type' => 'text',     'required' => true],
                    ['key' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password', 'required' => true],
                    ['key' => 'pcc',           'label' => 'PCC',           'type' => 'text',     'required' => true,
                     'hint' => 'Your Pseudo City Code (3-4 chars) from your Sabre agreement.'],
                ],
            ],
        ];
    }

    private static function saleOs(): array
    {
        return [
            [
                'slug'         => 'hubspot',
                'name'         => 'HubSpot',
                'logo_domain'  => 'hubspot.com',
                'description'  => 'Push leads and booking contacts to HubSpot CRM — sync deals, contacts and activities automatically.',
                'color'        => '#FF7A59',
                'capabilities' => [
                    'Create and update CRM contacts',
                    'Push confirmed bookings as deals',
                    'Sync deal stages with booking status',
                    'Activity logging (emails, notes)',
                ],
                'fields' => [
                    ['key' => 'access_token', 'label' => 'Private App Token', 'type' => 'password', 'required' => true,
                     'hint' => 'Create a Private App in HubSpot → Settings → Integrations → Private Apps.'],
                    ['key' => 'portal_id',    'label' => 'Portal ID',         'type' => 'text',     'required' => false],
                ],
            ],
            [
                'slug'         => 'pipedrive',
                'name'         => 'Pipedrive',
                'logo_domain'  => 'pipedrive.com',
                'description'  => 'Sales pipeline CRM for travel agents — auto-create deals from Tanova enquiries.',
                'color'        => '#28A745',
                'capabilities' => [
                    'Auto-create deals from trip enquiries',
                    'Sync contact records',
                    'Update deal stage on booking',
                    'Activity timeline',
                ],
                'fields' => [
                    ['key' => 'api_token', 'label' => 'API Token', 'type' => 'password', 'required' => true,
                     'hint' => 'Found in Pipedrive → Personal Preferences → API.'],
                ],
            ],
            [
                'slug'         => 'mailchimp_sales',
                'name'         => 'Mailchimp',
                'logo_domain'  => 'mailchimp.com',
                'description'  => 'Add booking guests to targeted Mailchimp audiences for post-trip follow-up campaigns.',
                'color'        => '#FFE01B',
                'capabilities' => [
                    'Sync guest emails to audiences',
                    'Tag contacts by trip type',
                    'Trigger automations on booking',
                    'Segment by destination',
                ],
                'fields' => [
                    ['key' => 'api_key',    'label' => 'API Key',       'type' => 'password', 'required' => true],
                    ['key' => 'server',     'label' => 'Server Prefix', 'type' => 'text',     'required' => true,
                     'hint' => 'The prefix in your API key after the dash, e.g. "us6".'],
                    ['key' => 'list_id',    'label' => 'Audience ID',   'type' => 'text',     'required' => false],
                ],
            ],
        ];
    }

    private static function payments(): array
    {
        return [
            [
                'slug'        => 'stripe',
                'name'        => 'Stripe',
                'logo_domain' => 'stripe.com',
                'description' => 'International card payments — Visa, Mastercard, Amex.',
                'color'       => '#635BFF',
                'fields'      => [
                    ['key' => 'public_key',    'label' => 'Publishable Key', 'type' => 'text',     'required' => true],
                    ['key' => 'secret_key',    'label' => 'Secret Key',      'type' => 'password', 'required' => true],
                    ['key' => 'webhook_secret','label' => 'Webhook Secret',  'type' => 'password', 'required' => false],
                ],
            ],
            [
                'slug'        => 'paypal',
                'name'        => 'PayPal',
                'logo_domain' => 'paypal.com',
                'description' => 'International PayPal checkout and express payments.',
                'color'       => '#003087',
                'fields'      => [
                    ['key' => 'client_id',     'label' => 'Client ID',     'type' => 'text',     'required' => true],
                    ['key' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password', 'required' => true],
                    ['key' => 'mode',          'label' => 'Mode',          'type' => 'select',   'required' => true,
                     'options' => ['sandbox' => 'Sandbox', 'live' => 'Live']],
                ],
            ],
            [
                'slug'        => 'flutterwave',
                'name'        => 'Flutterwave',
                'logo_domain' => 'flutterwave.com',
                'description' => 'Africa-first payment gateway — card, mobile money, bank transfer across 30+ African countries.',
                'color'       => '#F5A623',
                'capabilities' => [
                    'Cards, mobile money, USSD',
                    'Multi-currency (NGN, KES, ZAR, ZWL...)',
                    'Instant bank transfers',
                    'Recurring billing support',
                ],
                'fields' => [
                    ['key' => 'public_key',    'label' => 'Public Key',    'type' => 'text',     'required' => true],
                    ['key' => 'secret_key',    'label' => 'Secret Key',    'type' => 'password', 'required' => true],
                    ['key' => 'encryption_key','label' => 'Encryption Key','type' => 'password', 'required' => false],
                ],
            ],
            [
                'slug'        => 'paystack',
                'name'        => 'Paystack',
                'logo_domain' => 'paystack.com',
                'description' => 'Accept payments from any device, globally — built for African businesses.',
                'color'       => '#00C3F7',
                'fields' => [
                    ['key' => 'public_key', 'label' => 'Public Key',  'type' => 'text',     'required' => true],
                    ['key' => 'secret_key', 'label' => 'Secret Key',  'type' => 'password', 'required' => true],
                ],
            ],
            [
                'slug'        => 'mpesa',
                'name'        => 'M-Pesa',
                'logo_domain' => 'safaricom.co.ke',
                'description' => 'Safaricom M-Pesa Daraja API — accept Lipa Na M-Pesa and B2C payments in Kenya.',
                'color'       => '#00A94F',
                'capabilities' => [
                    'STK Push (Lipa Na M-Pesa)',
                    'C2B paybill integration',
                    'B2C payouts',
                    'Transaction status queries',
                ],
                'fields' => [
                    ['key' => 'consumer_key',    'label' => 'Consumer Key',    'type' => 'text',     'required' => true],
                    ['key' => 'consumer_secret', 'label' => 'Consumer Secret', 'type' => 'password', 'required' => true],
                    ['key' => 'shortcode',       'label' => 'Paybill / Shortcode', 'type' => 'text', 'required' => true],
                    ['key' => 'passkey',         'label' => 'Lipa Na M-Pesa Passkey', 'type' => 'password', 'required' => false],
                ],
            ],
            [
                'slug'        => 'ecocash',
                'name'        => 'EcoCash',
                'logo_domain' => 'econet.co.zw',
                'description' => 'Zimbabwe\'s leading mobile money platform via Econet EcoCash API.',
                'color'       => '#E2231A',
                'fields' => [
                    ['key' => 'merchant_code', 'label' => 'Merchant Code', 'type' => 'text',     'required' => true],
                    ['key' => 'api_key',       'label' => 'API Key',       'type' => 'password', 'required' => true],
                    ['key' => 'api_secret',    'label' => 'API Secret',    'type' => 'password', 'required' => true],
                ],
            ],
        ];
    }

    private static function communications(): array
    {
        return [
            [
                'slug'        => 'whatsapp_cloud',
                'name'        => 'WhatsApp Cloud',
                'logo_domain' => 'whatsapp.com',
                'description' => 'Meta WhatsApp Cloud API for guest messaging at scale.',
                'color'       => '#25D366',
                'fields'      => [
                    ['key' => 'phone_number_id', 'label' => 'Phone Number ID',     'type' => 'text',     'required' => true],
                    ['key' => 'access_token',    'label' => 'Access Token',         'type' => 'password', 'required' => true],
                    ['key' => 'verify_token',    'label' => 'Webhook Verify Token', 'type' => 'text',     'required' => true],
                ],
            ],
            [
                'slug'        => 'twilio',
                'name'        => 'Twilio',
                'logo_domain' => 'twilio.com',
                'description' => 'SMS, voice and WhatsApp messaging via Twilio — global reach with local numbers.',
                'color'       => '#F22F46',
                'capabilities' => [
                    'SMS booking confirmations',
                    'WhatsApp messaging',
                    'Voice call notifications',
                    'Phone number verification',
                ],
                'fields' => [
                    ['key' => 'account_sid', 'label' => 'Account SID',   'type' => 'text',     'required' => true],
                    ['key' => 'auth_token',  'label' => 'Auth Token',     'type' => 'password', 'required' => true],
                    ['key' => 'from_number', 'label' => 'From Number',    'type' => 'text',     'required' => true,
                     'hint' => 'Your Twilio number in E.164 format, e.g. +12125551234'],
                ],
            ],
            [
                'slug'        => 'sendgrid',
                'name'        => 'SendGrid',
                'logo_domain' => 'sendgrid.com',
                'description' => 'Transactional email delivery — booking confirmations, invoices and notifications.',
                'color'       => '#1A82E2',
                'fields' => [
                    ['key' => 'api_key',    'label' => 'API Key',       'type' => 'password', 'required' => true],
                    ['key' => 'from_email', 'label' => 'From Email',    'type' => 'text',     'required' => true],
                    ['key' => 'from_name',  'label' => 'Sender Name',   'type' => 'text',     'required' => false],
                ],
            ],
            [
                'slug'        => 'mailgun',
                'name'        => 'Mailgun',
                'logo_domain' => 'mailgun.com',
                'description' => 'Reliable transactional email API with built-in analytics and logs.',
                'color'       => '#E94E39',
                'fields' => [
                    ['key' => 'api_key',    'label' => 'API Key',    'type' => 'password', 'required' => true],
                    ['key' => 'domain',     'label' => 'Domain',     'type' => 'text',     'required' => true,
                     'hint' => 'Your verified Mailgun sending domain.'],
                    ['key' => 'from_email', 'label' => 'From Email', 'type' => 'text',     'required' => true],
                ],
            ],
            [
                'slug'        => 'claude_ai',
                'name'        => 'Claude AI',
                'logo_domain' => 'anthropic.com',
                'description' => 'Anthropic Claude — powers Tanova trip planning, Concierge AI replies and smart email drafting.',
                'color'       => '#D97706',
                'required'    => true,
                'capabilities' => [
                    'Tanova AI trip generation',
                    'Concierge smart reply drafting',
                    'Email content generation',
                    'Itinerary summarisation',
                ],
                'fields' => [
                    ['key' => 'api_key', 'label' => 'Anthropic API Key', 'type' => 'password', 'required' => true,
                     'hint' => 'Get your key at console.anthropic.com — starts with sk-ant-...'],
                    ['key' => 'model',   'label' => 'Model',              'type' => 'select',   'required' => false,
                     'options' => [
                         'claude-sonnet-4-6'         => 'Claude Sonnet 4.6 (Recommended)',
                         'claude-opus-4-7'           => 'Claude Opus 4.7 (Most capable)',
                         'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5 (Fastest)',
                     ]],
                ],
            ],
        ];
    }

    private static function fiscal(): array
    {
        return [
            [
                'slug'        => 'fiscalize',
                'name'        => 'Fiscalize',
                'logo_domain' => 'fiscalize.co.zw',
                'description' => 'ZIMRA-certified fiscal device middleware for Zimbabwe.',
                'color'       => '#1B5E20',
                'fields'      => [
                    ['key' => 'api_key',    'label' => 'API Key',     'type' => 'password', 'required' => true,
                     'hint' => 'Generated in your Fiscalize dashboard after ZIMRA device approval.'],
                    ['key' => 'api_secret', 'label' => 'API Secret',  'type' => 'password', 'required' => true],
                    ['key' => 'base_url',   'label' => 'API Base URL','type' => 'text',     'required' => false,
                     'default' => 'https://fiscalize.erpona.com:8090/api'],
                ],
            ],
            [
                'slug'        => 'kra_kenya',
                'name'        => 'KRA iTax',
                'logo_domain' => 'kra.go.ke',
                'description' => 'Kenya Revenue Authority eTIMS integration for electronic tax invoice management.',
                'color'       => '#006600',
                'capabilities' => [
                    'Submit eTIMS tax invoices',
                    'Credit note issuance',
                    'Real-time KRA acknowledgement',
                    'VAT return support',
                ],
                'fields' => [
                    ['key' => 'tin',         'label' => 'KRA PIN / TIN', 'type' => 'text',     'required' => true],
                    ['key' => 'username',    'label' => 'iTax Username', 'type' => 'text',     'required' => true],
                    ['key' => 'password',    'label' => 'iTax Password', 'type' => 'password', 'required' => true],
                ],
            ],
            [
                'slug'        => 'tra_tanzania',
                'name'        => 'TRA Tanzania',
                'logo_domain' => 'tra.go.tz',
                'description' => 'Tanzania Revenue Authority EFD integration for electronic fiscal receipts.',
                'color'       => '#006B3F',
                'fields' => [
                    ['key' => 'tin',      'label' => 'TIN',        'type' => 'text',     'required' => true],
                    ['key' => 'username', 'label' => 'Username',   'type' => 'text',     'required' => true],
                    ['key' => 'password', 'label' => 'Password',   'type' => 'password', 'required' => true],
                ],
            ],
        ];
    }
}
