<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Pro\Tanova\Services\RestaurantDetails;

class RestaurantDetailsTest extends TestCase
{
    public function test_reads_cuisine_price_hours_and_url_from_a_full_description(): void
    {
        $d = RestaurantDetails::parse(
            'Japanese cuisine. Prices range from 15-30 USD per dish. | URL: https://www.sushiart.ae/ | Hours: 12:00:00-23:00:00 | Offered by: Multiple locations'
        );

        $this->assertSame('Japanese', $d['cuisine']);
        $this->assertSame('15-30 USD per dish', $d['price_text']);
        $this->assertSame(22.5, $d['price_estimate']);
        $this->assertSame('12:00', $d['opens']);
        $this->assertSame('23:00', $d['closes']);
        $this->assertSame('https://www.sushiart.ae/', $d['url']);
        $this->assertSame('Multiple locations', $d['offered_by']);
    }

    public function test_a_price_only_description_leaves_no_summary(): void
    {
        $d = RestaurantDetails::parse('$7 per person | Hours: 10:00-20:00 | Offered by: Hotspot Incubator');

        $this->assertSame('$7 per person', $d['price_text']);
        $this->assertSame(7.0, $d['price_estimate']);
        $this->assertSame('', $d['summary']);
    }

    public function test_prices_in_other_currencies_are_shown_but_not_estimated_in_dollars(): void
    {
        $d = RestaurantDetails::parse(
            'A fine dining restaurant. The average meal cost is ZAR 1800 per person. | Hours: 6:30 PM-9:30 PM'
        );

        $this->assertSame('ZAR 1800 per person', $d['price_text']);
        $this->assertNull($d['price_estimate']);
        $this->assertSame('A fine dining restaurant.', $d['summary']);
        $this->assertSame('18:30', $d['opens']);
        $this->assertSame('21:30', $d['closes']);
    }

    public function test_reads_four_digit_hours_and_a_price_with_a_leading_currency_word(): void
    {
        $d = RestaurantDetails::parse('USD 20 per person - A restaurant with a view of the gorge. | Hours: 0900-2100');

        $this->assertSame('USD 20 per person', $d['price_text']);
        $this->assertSame(20.0, $d['price_estimate']);
        $this->assertSame('09:00', $d['opens']);
        $this->assertSame('21:00', $d['closes']);
        $this->assertStringContainsString('view of the gorge', $d['summary']);
    }

    public function test_a_stored_cuisine_wins_over_one_guessed_from_the_text(): void
    {
        $d = RestaurantDetails::parse('Chinese cuisine.', 'Szechuan');

        $this->assertSame('Szechuan', $d['cuisine']);
    }

    public function test_missing_pieces_come_back_as_null_not_errors(): void
    {
        $d = RestaurantDetails::parse('Savor exquisite dishes in a charming atmosphere.');

        $this->assertNull($d['cuisine']);
        $this->assertNull($d['price_text']);
        $this->assertNull($d['opens']);
        $this->assertNull($d['url']);
    }
}
