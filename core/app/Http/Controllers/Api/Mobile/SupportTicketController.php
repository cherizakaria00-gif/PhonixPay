<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Constants\Status;
use App\Http\Resources\Mobile\SupportMessageResource;
use App\Http\Resources\Mobile\SupportTicketResource;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SupportTicketController extends ApiMobileController
{
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $tickets = SupportTicket::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->paginate((int) $request->input('per_page', 10));

        return $this->ok([
            'data' => SupportTicketResource::collection($tickets->getCollection())->resolve(),
            'meta' => $this->meta($tickets),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'priority' => 'required|integer|in:1,2,3',
            'message' => 'required|string',
        ]);

        $user = $request->user();

        $ticket = new SupportTicket();
        $ticket->user_id = $user->id;
        $ticket->ticket = (string) random_int(100000, 999999);
        $ticket->name = $user->fullname;
        $ticket->email = $user->email;
        $ticket->subject = (string) $request->subject;
        $ticket->last_reply = Carbon::now();
        $ticket->status = Status::TICKET_OPEN;
        $ticket->priority = (int) $request->priority;
        $ticket->save();

        $message = new SupportMessage();
        $message->support_ticket_id = $ticket->id;
        $message->message = (string) $request->message;
        $message->save();

        return $this->ok([
            'success' => true,
            'message' => 'Ticket opened successfully',
        ], 201);
    }

    public function show(Request $request, string $ticket)
    {
        $ticketRecord = SupportTicket::query()
            ->where('user_id', $request->user()->id)
            ->where(function ($query) use ($ticket) {
                if (is_numeric($ticket)) {
                    $query->where('id', (int) $ticket);
                }

                $query->orWhere('ticket', $ticket);
            })
            ->first();

        if (!$ticketRecord) {
            return response()->json([
                'message' => 'Ticket not found.',
                'errors' => [
                    'ticket' => ['Ticket not found.'],
                ],
            ], 404);
        }

        $messages = SupportMessage::query()
            ->where('support_ticket_id', $ticketRecord->id)
            ->with('admin')
            ->orderBy('id')
            ->get();

        return $this->ok([
            'ticket' => (new SupportTicketResource($ticketRecord))->resolve(),
            'messages' => SupportMessageResource::collection($messages)->resolve(),
        ]);
    }

    public function reply(Request $request, int $id)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $ticket = SupportTicket::query()->where('user_id', $request->user()->id)->findOrFail($id);

        if ((int) $ticket->status === Status::TICKET_CLOSE) {
            return $this->ok([
                'success' => false,
                'message' => 'Ticket is already closed.',
            ], 422);
        }

        $message = new SupportMessage();
        $message->support_ticket_id = $ticket->id;
        $message->message = (string) $request->message;
        $message->save();

        $ticket->status = Status::TICKET_REPLY;
        $ticket->last_reply = Carbon::now();
        $ticket->save();

        return $this->ok([
            'success' => true,
            'message' => 'Support ticket replied successfully',
        ]);
    }

    public function close(Request $request, int $id)
    {
        $ticket = SupportTicket::query()->where('user_id', $request->user()->id)->findOrFail($id);
        $ticket->status = Status::TICKET_CLOSE;
        $ticket->save();

        return $this->ok([
            'success' => true,
            'message' => 'Support ticket closed successfully',
        ]);
    }
}
