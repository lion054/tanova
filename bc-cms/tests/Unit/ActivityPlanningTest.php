<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Pro\Tanova\Services\ActivityPlanning;

class ActivityPlanningTest extends TestCase
{
    public function test_subtype_follows_the_length_of_the_trip(): void
    {
        $this->assertSame('package', ActivityPlanning::subtype(true, 24));
        $this->assertSame('day_trip', ActivityPlanning::subtype(false, 5));
        $this->assertSame('day_trip', ActivityPlanning::subtype(false, 10));
        $this->assertSame('activity', ActivityPlanning::subtype(false, 4));
        $this->assertSame('activity', ActivityPlanning::subtype(false, 24));
    }

    public function test_the_slot_is_the_one_the_portal_recorded(): void
    {
        $this->assertSame('morning', ActivityPlanning::slot(1));
        $this->assertSame('afternoon', ActivityPlanning::slot(2));
        $this->assertSame('evening', ActivityPlanning::slot(3));
    }

    public function test_an_unrecorded_or_unknown_slot_is_null_not_a_guess(): void
    {
        foreach ([null, 0, 4, 5, 9] as $code) {
            $this->assertNull(ActivityPlanning::slot($code), (string) $code);
        }
    }

    public function test_a_stored_start_wins_and_is_tidied(): void
    {
        $this->assertSame('08:30', ActivityPlanning::start('08:30', 2));
        $this->assertSame('06:30', ActivityPlanning::start('6:30', null));
    }

    public function test_the_usual_start_for_the_slot_otherwise(): void
    {
        $this->assertSame('08:00', ActivityPlanning::start(null, 1));
        $this->assertSame('13:00', ActivityPlanning::start(null, 2));
        $this->assertSame('19:00', ActivityPlanning::start(null, 3));
    }

    public function test_no_start_when_nothing_is_recorded(): void
    {
        $this->assertNull(ActivityPlanning::start(null, null));
        $this->assertNull(ActivityPlanning::start('', 4));
        $this->assertNull(ActivityPlanning::start('soon', 0));
    }

    public function test_minutes(): void
    {
        $this->assertSame(8 * 60 + 30, ActivityPlanning::minutes('08:30'));
        $this->assertNull(ActivityPlanning::minutes(null));
    }

    public function test_thrill_is_only_what_was_recorded(): void
    {
        $this->assertSame('thrill', ActivityPlanning::thrill('thrill'));
        $this->assertSame('easy', ActivityPlanning::thrill('easy'));
        $this->assertNull(ActivityPlanning::thrill(null));
        $this->assertNull(ActivityPlanning::thrill('extreme'));
    }
}
