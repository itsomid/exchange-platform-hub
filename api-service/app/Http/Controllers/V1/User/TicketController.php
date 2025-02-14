<?php

namespace App\Http\Controllers\V1\User;

use App\Enums\TicketTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\ReplyTicketRequest;
use App\Http\Requests\V1\User\StoreTicketRequest;
use App\Http\Resources\V1\User\TicketReplyResource;
use App\Http\Resources\V1\User\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/tickets",
     *     summary="Create a new ticket",
     *     tags={"Tickets"},
     *     security={{ "bearerAuth":{} }},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/StoreTicketRequest")
     *     ),
     *
     *     @OA\Response(response=201, description="Ticket created successfully", @OA\JsonContent(ref="#/components/schemas/TicketResource")),
     *     @OA\Response(response=400, description="Invalid request data"),
     *     @OA\Response(response=401, description="Unauthorized"),
     * )
     */
    public function store(StoreTicketRequest $request)
    {
        $validateData = $request->validated();

        $imagePath = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('ticket_replies', 'public');
            $imagePath = 'storage/'.$path;
        }

        // Ensure ticketable_id and ticketable_type are nullable
        $ticketableId = $validateData['ticketable_id'] ?? null;
        $ticketableType = isset($validateData['ticketable_type'])
            ? TicketTypeEnum::getTypeClass($validateData['ticketable_type'])
            : null;


        $ticket = Ticket::query()->create([
            'ticket_number' => Ticket::generateTicketNumber(),
            'user_id' => Auth::id(),
            'subject' => $validateData['subject'],
            'priority' => $validateData['priority'],
            'ticketable_id' => $ticketableId,
            'ticketable_type' => $ticketableType,
        ]);

        $ticket->replies()->create([
            'repliable_id' => auth()->id(),
            'repliable_type' => User::class,
            'message' => $validateData['message'],
            'is_private' => false,
            'image' => $imagePath,
        ]);

        $ticket = $ticket->load('replies');

        return response()->json(new TicketResource($ticket), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/tickets",
     *     summary="Get list of user tickets",
     *     tags={"Tickets"},
     *     security={{ "bearerAuth":{} }},
     *
     *     @OA\Response(response=200, description="List of tickets", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/TicketResource"))),
     *     @OA\Response(response=401, description="Unauthorized"),
     * )
     */
    public function index()
    {
        $tickets = Ticket::query()->where('user_id', auth()->id())->latest()->get();

        return TicketResource::collection($tickets);
    }

    /**
     * @OA\Get(
     *     path="/api/tickets/{ticket}",
     *     summary="Get a single ticket",
     *     tags={"Tickets"},
     *     security={{ "bearerAuth":{} }},
     *
     *     @OA\Parameter(
     *         name="ticket",
     *         in="path",
     *         required=true,
     *         description="ID of the ticket",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(response=200, description="Ticket details", @OA\JsonContent(ref="#/components/schemas/TicketResource")),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Ticket not found"),
     * )
     */
    public function show(Ticket $ticket)
    {
        if ($ticket->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        // Update `is_seen` to true for all unread replies
        $ticket->replies()->where('is_seen', false)->update(['is_seen' => true]);

        return new TicketResource($ticket->load('replies'));
    }

    /**
     * @OA\Post(
     *     path="/api/tickets/{ticket}/reply",
     *     summary="Reply to a ticket",
     *     tags={"Tickets"},
     *     security={{ "bearerAuth":{} }},
     *
     *     @OA\Parameter(
     *         name="ticket",
     *         in="path",
     *         required=true,
     *         description="ID of the ticket",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/ReplyTicketRequest")
     *     ),
     *
     *     @OA\Response(response=200, description="Reply added successfully", @OA\JsonContent(ref="#/components/schemas/TicketReplyResource")),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Ticket not found"),
     * )
     */
    public function reply(ReplyTicketRequest $request, Ticket $ticket)
    {
        if ($ticket->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $imagePath = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('ticket_replies', 'public');
            $imagePath = 'storage/'.$path;
        }

        $validateData = $request->validated();

        $reply = $ticket->replies()->create([
            'repliable_id' => auth()->id(),
            'repliable_type' => User::class,
            'message' => $validateData['message'],
            'is_private' => false,
            'image' => $imagePath,
        ]);

        return new TicketReplyResource($reply);
    }
}
