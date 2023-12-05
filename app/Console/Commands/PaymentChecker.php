<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PaymentChecker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:payment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for payment status and cancels all bookings not paid for after 72 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today()->toDateString();
        $bookings = Booking::where('accepted', true)
        ->where('paid', false)
        ->get();
        foreach ($bookings as $key) {
            $today_date = Carbon::parse($today);
            $date_accepted = Carbon::parse($key->updated_at);
            $difference = $today_date->diffInHours($date_accepted);
            if($difference > 72) {
                $key->accepted = false;
                $key->guide_delete = true;
                $key->update();
                $event = Event::where('id', $key->event_id)->first();
                if (!$event->repeat_at) {
                    $event->limit_count = DB::raw('limit_count - '.$key->quantity);
                    $event->update();
                }
            }
        }
        $this->info('Payment status checked successfully!');
    }
}
