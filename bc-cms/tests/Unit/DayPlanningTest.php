<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Pro\Tanova\Services\DayPlanning;

/**
 * The day planner works from a catalogue handed to it, so these need no
 * database. Times are minutes after midnight in the place's clock.
 */
class DayPlanningTest extends TestCase
{
    private function activity(string $id, string $name, array $over = []): array
    {
        return $over + [
            'id' => $id, 'name' => $name, 'type' => 'Sight Seeing', 'description' => '',
            'price' => 50, 'duration_hours' => 2, 'slot' => 'morning', 'start_minutes' => 9 * 60,
            'min_age' => null, 'min_pax' => 1, 'max_pax' => 20, 'thrill' => 'easy',
            'lat' => -17.92, 'lng' => 25.85,
        ];
    }

    private function restaurant(int $id, string $name, array $over = []): array
    {
        return $over + ['id' => $id, 'name' => $name, 'opens' => '10:00', 'closes' => '22:00', 'price_estimate' => 20.0, 'is_partner' => false];
    }

    private function planner(array $activities, array $restaurants = []): DayPlanning
    {
        return new DayPlanning(7, fn () => [
            'location' => ['id' => 6, 'name' => 'Victoria Falls', 'lat' => -17.92, 'lng' => 25.85],
            'activities' => $activities,
            'restaurants' => $restaurants,
        ]);
    }

    private function request(array $over = []): array
    {
        return $over + [
            'location_id' => 6, 'when' => 'rest', 'now_minutes' => 8 * 60,
            'party' => [['age' => 35], ['age' => 34]],
            'weather' => ['fit' => 'outdoor', 'sunset_minutes' => 18 * 60],
        ];
    }

    private function ids(array $option): array
    {
        return array_column($option['stops'], 'service_id');
    }

    public function test_nothing_starts_before_now_and_time_to_get_ready(): void
    {
        $out = $this->planner([$this->activity('activity-1', 'A')])->plan($this->request(['now_minutes' => 10 * 60 + 10]));
        // 10:10 + 30 minutes to get ready, on the half hour: 11:00.
        $this->assertSame(11 * 60, $out['window']['start']);
        $this->assertSame(22 * 60, $out['window']['end']);
    }

    public function test_tomorrow_starts_at_eight_whatever_the_time_now(): void
    {
        $out = $this->planner([$this->activity('activity-1', 'A')])->plan($this->request(['tomorrow' => true, 'now_minutes' => 21 * 60]));
        $this->assertSame(8 * 60, $out['window']['start']);
    }

    public function test_too_little_of_the_day_left_plans_nothing(): void
    {
        $out = $this->planner([$this->activity('activity-1', 'A')])->plan($this->request(['now_minutes' => 20 * 60 + 30]));
        $this->assertNull($out['window']);
        $this->assertSame([], $out['options']);
    }

    public function test_an_outdoor_activity_has_to_finish_before_sunset_less_a_margin(): void
    {
        $late = $this->activity('activity-1', 'Late', ['start_minutes' => 16 * 60 + 30, 'duration_hours' => 2, 'slot' => 'afternoon']);
        $out = $this->planner([$late])->plan($this->request(['when' => 'afternoon']));
        // Sunset 18:00, margin 30: it cannot run 16:30-18:30, and it fits nowhere earlier only if the window says so;
        // it must not be placed to end after 17:30.
        foreach ($out['options'] as $o) {
            foreach ($o['stops'] as $s) {
                $this->assertLessThanOrEqual(17 * 60 + 30, $s['end_minutes']);
            }
        }
    }

    public function test_an_evening_activity_may_run_after_dark(): void
    {
        $show = $this->activity('activity-2', 'Boma dinner show', ['slot' => 'evening', 'start_minutes' => 19 * 60, 'duration_hours' => 2]);
        $out = $this->planner([$show])->plan($this->request(['when' => 'evening']));
        $this->assertSame(['activity-2'], $this->ids($out['options'][0]));
        $this->assertSame(19 * 60, $out['options'][0]['stops'][0]['start_minutes']);
    }

    public function test_whole_party_has_to_be_old_enough_and_the_right_size(): void
    {
        $rafting = $this->activity('activity-3', 'Rafting', ['min_age' => 14]);
        $out = $this->planner([$rafting])->plan($this->request(['party' => [['age' => 35], ['age' => 8]]]));
        $this->assertSame([], $out['options']);

        $big = $this->activity('activity-4', 'Small boat', ['max_pax' => 2]);
        $out = $this->planner([$big])->plan($this->request(['party' => [['age' => 30], ['age' => 30], ['age' => 30]]]));
        $this->assertSame([], $out['options']);
    }

    public function test_an_occasion_that_caps_thrill_takes_only_what_is_known_to_fit(): void
    {
        $unknown = $this->activity('activity-5', 'Sunset cruise', ['thrill' => null, 'slot' => 'evening', 'start_minutes' => 17 * 60]);
        $easy = $this->activity('activity-6', 'Dhow cruise', ['thrill' => 'easy', 'slot' => 'evening', 'start_minutes' => 18 * 60]);
        $out = $this->planner([$unknown, $easy])->plan($this->request(['occasion' => 'dinner_date', 'now_minutes' => 9 * 60]));
        $all = array_merge(...array_map(fn ($o) => $this->ids($o), $out['options']));
        $this->assertContains('activity-6', $all);
        $this->assertNotContains('activity-5', $all, 'an unknown thrill level is never taken to be easy');
    }

    public function test_a_dinner_date_can_be_just_dinner_and_each_option_eats_somewhere_else(): void
    {
        $out = $this->planner([], [
            $this->restaurant(1, 'Alpha'), $this->restaurant(2, 'Bravo'), $this->restaurant(3, 'Charlie'),
        ])->plan($this->request(['occasion' => 'dinner_date', 'now_minutes' => 9 * 60]));

        $this->assertCount(3, $out['options']);
        $first = array_map(fn ($o) => $this->ids($o)[0], $out['options']);
        $this->assertCount(3, array_unique($first));
        foreach ($out['options'] as $o) {
            $this->assertSame('meal', $o['stops'][0]['kind']);
            $this->assertSame('dinner', $o['stops'][0]['meal']);
            $this->assertSame(19 * 60, $o['stops'][0]['start_minutes']);
            $this->assertSame(0.0, (float) $o['total'], 'meals are paid at the table');
        }
    }

    public function test_a_meal_needs_a_restaurant_that_is_open_for_it(): void
    {
        $closed = $this->restaurant(1, 'Lunch only', ['opens' => '10:00', 'closes' => '15:00']);
        $out = $this->planner([], [$closed])->plan($this->request(['occasion' => 'dinner_date', 'now_minutes' => 9 * 60]));
        $this->assertSame([], $out['options']);
    }

    public function test_a_restaurant_with_no_hours_counts_as_open(): void
    {
        $unknown = $this->restaurant(1, 'Somewhere', ['opens' => null, 'closes' => null]);
        $out = $this->planner([], [$unknown])->plan($this->request(['occasion' => 'dinner_date', 'now_minutes' => 9 * 60]));
        $this->assertSame(['restaurant:1'], $this->ids($out['options'][0]));
    }

    public function test_hours_past_midnight_are_handled(): void
    {
        $bar = $this->restaurant(1, 'Late bar', ['opens' => '18:00', 'closes' => '01:00']);
        $out = $this->planner([], [$bar])->plan($this->request(['occasion' => 'dinner_date', 'now_minutes' => 9 * 60]));
        $this->assertSame(['restaurant:1'], $this->ids($out['options'][0]));
    }

    public function test_partners_come_first_and_a_budget_prefers_what_it_can_pay_for(): void
    {
        $out = $this->planner([], [
            $this->restaurant(1, 'Aardvark', ['price_estimate' => 90.0]),
            $this->restaurant(2, 'Zebra', ['price_estimate' => 15.0, 'is_partner' => true]),
        ])->plan($this->request(['occasion' => 'dinner_date', 'now_minutes' => 9 * 60, 'budget' => 100]));
        // A budget of 100 for two: 40% is 40, for one meal is 20 each; Aardvark at 90 is dropped.
        $this->assertSame(['restaurant:2'], $this->ids($out['options'][0]));
    }

    public function test_options_differ_from_each_other(): void
    {
        $out = $this->planner([
            $this->activity('activity-1', 'Falls tour', ['slot' => 'morning', 'start_minutes' => 8 * 60, 'type' => 'Sight Seeing']),
            $this->activity('activity-2', 'Zip line', ['thrill' => 'thrill', 'slot' => 'morning', 'start_minutes' => 9 * 60, 'type' => 'Adventure']),
            $this->activity('activity-3', 'Museum', ['thrill' => 'easy', 'slot' => 'afternoon', 'start_minutes' => 14 * 60, 'type' => 'Cultural']),
            $this->activity('activity-4', 'Garden walk', ['thrill' => 'easy', 'slot' => 'afternoon', 'start_minutes' => 15 * 60, 'type' => 'Nature']),
        ])->plan($this->request());

        $keys = array_map(function ($o) {
            $ids = $this->ids($o);
            sort($ids);
            return implode('|', $ids);
        }, $out['options']);
        $this->assertNotEmpty($keys);
        $this->assertSame($keys, array_values(array_unique($keys)));
        $this->assertLessThanOrEqual(3, count($out['options']));
        foreach ($out['options'] as $o) {
            $this->assertLessThanOrEqual(DayPlanning::MAX_STOPS, count($o['stops']));
        }
    }

    public function test_the_easiest_option_never_includes_anything_not_known_to_be_easy(): void
    {
        $out = $this->planner([
            $this->activity('activity-1', 'Known easy', ['thrill' => 'easy']),
            $this->activity('activity-2', 'Unknown', ['thrill' => null, 'start_minutes' => 13 * 60, 'slot' => 'afternoon']),
        ])->plan($this->request());
        foreach ($out['options'] as $o) {
            if ($o['style'] === 'easiest') {
                $this->assertNotContains('activity-2', $this->ids($o));
            }
        }
    }

    public function test_stops_do_not_overlap_and_allow_for_travel(): void
    {
        $out = $this->planner([
            $this->activity('activity-1', 'A', ['start_minutes' => 9 * 60, 'duration_hours' => 3]),
            $this->activity('activity-2', 'B', ['start_minutes' => 9 * 60, 'duration_hours' => 3]),
            $this->activity('activity-3', 'C', ['start_minutes' => 9 * 60, 'duration_hours' => 3]),
        ])->plan($this->request());
        foreach ($out['options'] as $o) {
            $stops = $o['stops'];
            for ($i = 1; $i < count($stops); $i++) {
                $this->assertGreaterThan($stops[$i - 1]['end_minutes'], $stops[$i]['start_minutes']);
            }
        }
    }

    public function test_over_budget_the_dearest_stop_goes_but_one_always_stays(): void
    {
        $out = $this->planner([
            $this->activity('activity-1', 'Cheap', ['price' => 10, 'start_minutes' => 9 * 60]),
            $this->activity('activity-2', 'Dear', ['price' => 400, 'start_minutes' => 13 * 60, 'slot' => 'afternoon']),
        ])->plan($this->request(['budget' => 60]));
        foreach ($out['options'] as $o) {
            $this->assertNotEmpty($o['stops']);
            $this->assertNotContains('activity-2', $this->ids($o));
            $this->assertLessThanOrEqual(60, $o['total']);
        }
    }

    public function test_children_pay_by_age(): void
    {
        $out = $this->planner([$this->activity('activity-1', 'Tour', ['price' => 100])])
            ->plan($this->request(['party' => [['age' => 40], ['age' => 9], ['age' => 2]]]));
        // One adult, a half-price child, an infant who is free: 1.5 adult prices.
        $this->assertSame(150.0, (float) $out['options'][0]['total']);
    }

    public function test_an_unknown_place_plans_nothing(): void
    {
        $none = new DayPlanning(7, fn () => null);
        $this->assertSame(['window' => null, 'options' => []], $none->plan($this->request()));
        $this->assertSame(['trips' => [], 'restaurants' => []], $none->dayTrips($this->request()));
    }

    // Day trips -------------------------------------------------------------

    public function test_day_trips_are_between_five_hours_and_a_day(): void
    {
        $found = $this->planner([
            $this->activity('activity-1', 'Short', ['duration_hours' => 2]),
            $this->activity('day_trip-2', 'Chobe', ['duration_hours' => 8]),
            $this->activity('activity-3', 'Overnight', ['duration_hours' => 24]),
        ])->dayTrips($this->request(['budget' => 1000]));
        $this->assertSame(['day_trip-2'], $found['trips']);
    }

    public function test_day_trips_respect_the_window_and_the_budget(): void
    {
        $p = $this->planner([
            $this->activity('day_trip-1', 'Half day', ['duration_hours' => 5, 'price' => 100]),
            $this->activity('day_trip-2', 'Full day', ['duration_hours' => 10, 'price' => 100]),
            $this->activity('day_trip-3', 'Dear', ['duration_hours' => 6, 'price' => 900]),
        ]);
        // A morning window of six hours takes the half day, not the ten-hour trip.
        $morning = $p->dayTrips($this->request(['budget' => 1000, 'start' => '07:00', 'end' => '13:00']));
        $this->assertSame(['day_trip-1'], $morning['trips']);
        // Budget is for the whole party: two people at 900 is 1800.
        $all = $p->dayTrips($this->request(['budget' => 1000, 'start' => '07:00', 'end' => '19:00']));
        $this->assertSame(['day_trip-1', 'day_trip-2'], $all['trips']);
    }

    public function test_an_occasion_puts_what_suits_it_first_and_lists_where_to_eat(): void
    {
        $found = $this->planner([
            $this->activity('day_trip-1', 'Quarry tour', ['duration_hours' => 6, 'type' => 'Industrial']),
            $this->activity('day_trip-2', 'Elephant and zoo day', ['duration_hours' => 6, 'type' => 'Wildlife']),
        ], [
            $this->restaurant(1, 'Zulu Bistro'),
            $this->restaurant(2, 'Alpha Grill'),
            $this->restaurant(3, 'Partner Table', ['is_partner' => true]),
            $this->restaurant(4, 'Breakfast only', ['opens' => '06:00', 'closes' => '11:00']),
        ])->dayTrips($this->request(['occasion' => 'family_day']));

        $this->assertSame(['day_trip-2', 'day_trip-1'], $found['trips']);
        // Lunch is family day's meal: those open at 12:30, partners first, then by name.
        $this->assertSame(['restaurant:3', 'restaurant:2', 'restaurant:1'], $found['restaurants']);
    }

    public function test_a_family_day_takes_only_trips_known_to_be_easy(): void
    {
        $found = $this->planner([
            $this->activity('day_trip-1', 'Unknown', ['duration_hours' => 6, 'thrill' => null]),
            $this->activity('day_trip-2', 'Easy', ['duration_hours' => 6, 'thrill' => 'easy']),
        ])->dayTrips($this->request(['occasion' => 'family_day']));
        $this->assertSame(['day_trip-2'], $found['trips']);
    }

    public function test_it_is_deterministic(): void
    {
        $p = $this->planner([
            $this->activity('activity-1', 'A'), $this->activity('activity-2', 'B', ['start_minutes' => 13 * 60, 'slot' => 'afternoon']),
        ], [$this->restaurant(1, 'R')]);
        $this->assertSame($p->plan($this->request()), $p->plan($this->request()));
    }
}
