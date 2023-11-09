<?php

namespace App\Console\Commands;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Console\Command;

class EventRepeater extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:repeat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for repeated events and re-schedule them';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today()->toDateString();
        $events = Event::all();
        foreach ($events as $key) {
            if($key->repeat_at !== null && $today >= $key->end_date) {
                $start_date = $key->start_date;
                $end_date = $key->end_date;
                if($key->repeat_at === 'daily') {
                    $key->start_date = \Carbon\Carbon::parse($start_date)->addDay()->toDateString();
                    $key->end_date = \Carbon\Carbon::parse($end_date)->addDay()->toDateString();
                }if($key->repeat_at === 'weekly') {
                    $key->start_date = \Carbon\Carbon::parse($start_date)->addDays(7)->toDateString();
                    $key->end_date = \Carbon\Carbon::parse($end_date)->addDays(7)->toDateString();
                }elseif ($key->repeat_at === 'monthly') {
                    $key->start_date = \Carbon\Carbon::parse($start_date)->addMonth()->toDateString();
                    $key->end_date = \Carbon\Carbon::parse($end_date)->addMonth()->toDateString();
                }
                $key->update();
            }
        }
        $this->info('Events repeated successfully!');
    }
}
