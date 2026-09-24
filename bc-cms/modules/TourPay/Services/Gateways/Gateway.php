<?php

namespace Modules\TourPay\Services\Gateways;

use Modules\TourPay\Models\Invoice;

/**
 * A payment gateway, used with THE VENDOR'S OWN credentials, so the money goes to their account and never through
 * the platform's. Each gateway starts a payment (returning where to send the guest) and later confirms it by asking
 * the gateway, never by trusting what the guest's browser says.
 */
abstract class Gateway
{
    public const LABELS = [
        'stripe' => 'Card (Stripe)', 'paypal' => 'PayPal', 'paystack' => 'Card / mobile money (Paystack)',
        'paynow' => 'EcoCash, OneMoney, cards (Paynow, Zimbabwe)', 'pesapal' => 'M-Pesa, Airtel, MTN, cards (Pesapal)', 'selcom' => 'Mobile money and cards (Selcom, Tanzania)',
    ];

    /** The fields a vendor must fill in before a gateway counts as set up. */
    public const REQUIRED = [
        'stripe' => ['secret_key'], 'paypal' => ['client_id', 'client_secret'], 'paystack' => ['secret_key'],
        'paynow' => ['integration_id', 'integration_key'], 'pesapal' => ['consumer_key', 'consumer_secret'], 'selcom' => ['vendor', 'api_key', 'api_secret'],
    ];

    /** Fields that are keys, never shown again once saved. */
    public const SECRET_FIELDS = ['secret_key', 'client_secret', 'integration_key', 'consumer_secret', 'api_key', 'api_secret'];

    /** Which currencies each gateway takes, so a guest is never offered one that will refuse the invoice. Null: any the account supports. */
    public const CURRENCIES = ['paynow' => ['USD', 'ZWG', 'ZWL'], 'selcom' => ['TZS', 'USD'], 'pesapal' => ['KES', 'UGX', 'TZS', 'RWF', 'USD', 'ZMW', 'MWK', 'ZAR', 'NGN', 'GHS']];

    public static function supports(string $name, string $currency): bool
    {
        $list = self::CURRENCIES[$name] ?? null;

        return $list === null || in_array(strtoupper($currency), $list, true);
    }

    /** Currencies where the smallest unit is the whole unit (no cents). */
    private const ZERO_DECIMAL = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];

    public function __construct(protected array $config) {}

    public static function make(string $name, array $config): self
    {
        return match ($name) {
            'stripe'   => new StripeGateway($config),
            'paypal'   => new PayPalGateway($config),
            'paystack' => new PaystackGateway($config),
            'paynow'   => new PaynowGateway($config),
            'pesapal'  => new PesapalGateway($config),
            'selcom'   => new SelcomGateway($config),
            default    => throw new GatewayException('unknown_gateway', 'That payment method is not available.'),
        };
    }

    /**
     * One-off set-up a gateway needs before it can start payments (Pesapal registers where it should tell us about a payment).
     * Returns settings to keep with the vendor's saved keys.
     * @return array<string,mixed>
     */
    public function prepare(string $notifyUrl): array
    {
        return [];
    }

    /** Where this gateway's own copy of a payment can be found again from what the guest's browser or the gateway sends us. */
    abstract public function referenceFrom(array $query): ?string;

    /** Ask the gateway to take [$amount] for the invoice. @return array{url:string,reference:string} where to send the guest, and the gateway's id for it */
    abstract public function start(Invoice $invoice, float $amount, string $returnUrl, string $cancelUrl): array;

    /** Ask the gateway what became of [$reference]. @return array{paid:bool,amount:float,currency:string,method:string,failed:bool} */
    abstract public function check(string $reference): array;

    /** Checks the keys work, without moving money. Throws GatewayException with a plain reason when they do not. */
    abstract public function test(): void;

    /** The smallest unit for a currency: 12.50 USD is 1250, 500 UGX is 500. */
    public static function minorUnits(float $amount, string $currency): int
    {
        return in_array(strtoupper($currency), self::ZERO_DECIMAL, true) ? (int) round($amount) : (int) round($amount * 100);
    }

    public static function fromMinor(int|float $minor, string $currency): float
    {
        return in_array(strtoupper($currency), self::ZERO_DECIMAL, true) ? (float) $minor : round($minor / 100, 2);
    }

    protected function fail(string $code, string $message): never
    {
        throw new GatewayException($code, $message);
    }
}
