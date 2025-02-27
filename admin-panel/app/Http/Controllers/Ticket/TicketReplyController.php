<?php

namespace App\Http\Controllers\Ticket;

use App\Enums\TicketStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\StoreTicketReplyRequest;
use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\Request;

class TicketReplyController extends Controller
{
    public function index(Ticket $ticket)
    {

        $ticketReplies = TicketReply::where('ticket_id', $ticket->id)->orderBy('created_at','DESC')->get();

        return view('dashboard.ticket.ticket-view', [
            'ticket' => $ticket,
            'ticketReplies' => $ticketReplies
        ]);

    }

    public function store(StoreTicketReplyRequest $request,Ticket $ticket)
    {

//        return $request;
        $reply = $ticket->replies()->create([
            'repliable_id' => \Auth::id(),
            'repliable_type' => \Auth::user()::class,
            'message' => $request->message,
            'image' => $request->file('image') ? $request->file('image')->store('ticket_replies') : null,
        ]);

        $ticket->status = $request->status;
        $ticket->save();
        $ticketReplies = TicketReply::where('ticket_id', $ticket->id)->orderBy('created_at','DESC')->get();

        return view('dashboard.ticket.ticket-view',[
            'ticket' => $ticket,
            'ticketReplies' => $ticketReplies
        ]);
    }
}
