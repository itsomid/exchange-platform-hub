<?php

namespace App\Http\Controllers\Ticket;

use App\Enums\TicketStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::all();
        return view('dashboard.ticket.index', compact('tickets'));
    }

    public function create()
    {
        return view('tickets.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'priority' => 'required|in:low,medium,high',
            'message' => 'required|string',
            'image' => 'nullable|image|max:2048',
        ]);

        $ticket = auth()->user()->tickets()->create([
            'ticket_number' => Ticket::generateTicketNumber(),
            'subject' => $data['subject'],
            'priority' => $data['priority'],
        ]);

        $replyData = [
            'message' => $data['message'],
            'repliable_id' => auth()->id(),
            'repliable_type' => get_class(auth()->user()),
        ];

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('public/ticket_replies');
            $replyData['image'] = \Storage::url($path);
        }

        $ticket->replies()->create($replyData);

        return redirect()->route('tickets.show', $ticket);
    }

    public function show(Ticket $ticket)
    {
        abort_unless($ticket->user_id === auth()->id(), 403);
        return view('tickets.show', compact('ticket'));
    }

    public function update(Request $request, Ticket $ticket)
    {
        abort_unless($ticket->user_id === auth()->id(), 403);

        $ticket->update($request->validate([
            'status' => 'required|in:open,closed,resolved,reopened',
            'priority' => 'required|in:low,medium,high'
        ]));

        return redirect()->back();
    }

    public function destroy(Ticket $ticket)
    {
        abort_unless($ticket->user_id === auth()->id(), 403);
        $ticket->delete();
        return redirect()->route('tickets.index');
    }
}
