<?php

namespace App\Http\Controllers\Ticket;

use App\Enums\TicketStatusEnum;
use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\StoreTicketReplyRequest;
use App\Models\Ticket;
use App\Models\TicketReply;
use HTMLPurifier;
use HTMLPurifier_Config;
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
        $purifierConfig = HTMLPurifier_Config::createDefault();
        $purifierConfig->set('HTML.Allowed', 'p,br,strong,em,u,s,ul,ol,li,a[href|target],blockquote,h1,h2,h3,h4,h5,h6,pre,code,span[style],img[src|alt|width|height]');
        $purifierConfig->set('HTML.TargetBlank', true);
        $purifierConfig->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);
        $purifierConfig->set('CSS.AllowedProperties', 'color,background-color,text-align,font-weight,font-style');
        $purifierConfig->set('AutoFormat.AutoParagraph', false);
        $purifierConfig->set('AutoFormat.RemoveEmpty', true);
        $purifier = new HTMLPurifier($purifierConfig);

        $cleanMessage = $purifier->purify($request->message);

        $reply = $ticket->replies()->create([
            'repliable_id' => \Auth::id(),
            'repliable_type' => \Auth::user()::class,
            'message' => $cleanMessage,
            'image' => $request->file('image') ? $request->file('image')->store('ticket_replies') : null,
        ]);

        $ticket->status = $request->status;
        $ticket->save();

        Toast::message('پاسخ شما با موفقیت ثبت شد.')->success()->notify();
        return redirect()->back();
    }
}
