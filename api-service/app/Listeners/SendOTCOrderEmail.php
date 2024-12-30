<?php

namespace App\Listeners;

use App\Events\OTCOrderCreated;
use App\Mail\OTCCreateNotifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendOTCOrderEmail implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OTCOrderCreated $event): void
    {
        $currencySymbol = $event->OTCOrder->market->base_currency;
        Mail::to($event->OTCOrder->user->email)->send(
            new OTCCreateNotifyEmail($currencySymbol, $event->OTCOrder->quantity, $event->OTCOrder->type->value)
        );
    }
}
