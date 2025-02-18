<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\Request;

class TicketReplyController extends Controller
{
    public function index(Ticket $ticket)
    {
        $ticketReplies = TicketReply::where('ticket_id',$ticket->id)->get();

    }
}
