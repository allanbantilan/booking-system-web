<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(): Response
    {
        $events = Event::query()
            ->orderBy('event_date')
            ->get();

        return Inertia::render('Events', [
            'events' => $events,
        ]);
    }
}
