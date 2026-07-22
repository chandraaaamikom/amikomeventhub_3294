<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckinController extends Controller
{
    public function index(Request $request)
    {
        $organization = $request->attributes->get('organization');

        return view('organizer.checkin.index', compact('organization'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $organization = $request->attributes->get('organization');

        $ticket = Ticket::with(['event', 'transaction.user'])
            ->where('code', $request->code)
            ->first();

        if (! $ticket) {
            return response()->json([
                'success' => false,
                'status'  => 'invalid',
                'message' => 'Tiket tidak valid atau tidak terdaftar di sistem.',
            ], 404);
        }

        // Pagar tenant: tiket harus milik event organisasi ini.
        if (! $ticket->event || $ticket->event->organization_id !== $organization->id) {
            return response()->json([
                'success' => false,
                'status'  => 'wrong_event',
                'message' => 'Tiket ini bukan untuk event organisasi Anda.',
            ], 403);
        }

        // Cegah double entry.
        if ($ticket->checked_in_at) {
            return response()->json([
                'success'  => false,
                'status'   => 'already',
                'message'  => 'Tiket sudah digunakan pada ' . $ticket->checked_in_at->format('d M Y, H:i') . '.',
                'attendee' => $ticket->attendee_name ?? optional($ticket->transaction->user)->name ?? 'Peserta',
                'event'    => $ticket->event->title,
            ], 422);
        }

        $ticket->update([
            'checked_in_at' => now(),
            'checked_in_by' => Auth::id(),
        ]);

        return response()->json([
            'success'  => true,
            'status'   => 'ok',
            'message'  => 'Check-in berhasil.',
            'attendee' => $ticket->attendee_name ?? optional($ticket->transaction->user)->name ?? 'Peserta',
            'event'    => $ticket->event->title,
        ], 200);
    }
}