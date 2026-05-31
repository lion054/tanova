<?php

namespace Pro\Tanova\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Pro\Tanova\Services\TanovaEngine;
use Pro\Tanova\Models\TanovaTrip;

class GenerateTripJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tripId;
    protected $destination;
    protected $startDate;
    protected $endDate;
    protected $guests;
    protected $budget;
    protected $vendorId;

    public function __construct(
        $tripId,
        $destination,
        $startDate,
        $endDate,
        $guests,
        $budget,
        $vendorId
    ) {
        $this->tripId = $tripId;
        $this->destination = $destination;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->guests = $guests;
        $this->budget = $budget;
        $this->vendorId = $vendorId;

        $this->onQueue('trips');
        $this->timeout = 300; // 5 minutes max
    }

    public function handle()
    {
        try {
            $engine = new TanovaEngine();
            $result = $engine->generate(
                destination: $this->destination,
                startDate: $this->startDate,
                endDate: $this->endDate,
                guests: $this->guests,
                budget: $this->budget,
                vendorId: $this->vendorId
            );

            if ($result) {
                $trip = TanovaTrip::find($this->tripId);
                $trip->update([
                    'itinerary' => json_encode($result['packages']),
                    'daily_weather' => json_encode($result['weather']),
                    'status' => 'generated',
                ]);

                // Cache the result
                cache()->remember(
                    "trip:{$this->tripId}",
                    now()->addHours(24),
                    fn() => $trip
                );
            }
        } catch (\Exception $e) {
            $trip = TanovaTrip::find($this->tripId);
            $trip->update(['status' => 'failed', 'error_message' => $e->getMessage()]);

            \Log::error('Trip generation failed', [
                'trip_id' => $this->tripId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $exception)
    {
        $trip = TanovaTrip::find($this->tripId);
        $trip->update([
            'status' => 'failed',
            'error_message' => 'Job processing failed: ' . $exception->getMessage(),
        ]);

        \Log::error('Trip generation job failed', [
            'trip_id' => $this->tripId,
            'exception' => $exception,
        ]);
    }
}
