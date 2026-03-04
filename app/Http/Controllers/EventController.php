<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    public function index(): JsonResponse
    {
        $events = Event::query()
            ->orderBy('event_date')
            ->get();

        return response()->json([
            'message' => 'Event listing scaffold is ready. Add your manual filtering/pagination logic as needed.',
            'data' => $events,
        ]);
    }
}