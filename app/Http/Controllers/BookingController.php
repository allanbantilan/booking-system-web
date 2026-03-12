<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Booking::query()
            ->with(['category:id,name,color,badge_label', 'media'])
            ->orderBy('event_date');

        if ($request->filled('categoryId') && $request->string('categoryId')->value() !== 'all') {
            $query->where('category_id', $request->string('categoryId')->value());
        }

        if ($request->filled('minPrice')) {
            $query->where('price', '>=', (float) $request->input('minPrice'));
        }

        if ($request->filled('maxPrice')) {
            $query->where('price', '<=', (float) $request->input('maxPrice'));
        }

        $bookings = $query
            ->paginate(8)
            ->withQueryString();

        $bookings->setCollection(
            $bookings->getCollection()->map(fn (Booking $booking) => $this->serializeBooking($booking))
        );

        $categories = Category::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Bookings', [
            'bookings' => $bookings,
            'categories' => $categories,
            'filters' => [
                'categoryId' => $request->input('categoryId', 'all'),
                'minPrice' => $request->input('minPrice', ''),
                'maxPrice' => $request->input('maxPrice', ''),
            ],
        ]);
    }

    public function show(Booking $booking): Response
    {
        $booking->load(['category:id,name,color,badge_label', 'creator:id,name', 'media']);

        return Inertia::render('BookingShow', [
            'booking' => $this->serializeBooking($booking, withCreator: true),
        ]);
    }

    private function serializeBooking(Booking $booking, bool $withCreator = false): array
    {
        return [
            'id' => $booking->id,
            'title' => $booking->title,
            'description' => $booking->description,
            'location' => $booking->location,
            'event_date' => $booking->event_date,
            'capacity' => $booking->capacity,
            'price' => $booking->price,
            'discount_percentage' => $booking->discount_percentage,
            'availability_label' => $booking->availability_label,
            'quantity_label' => $booking->quantity_label,
            'meta_line' => $booking->meta_line,
            'amenities' => $booking->amenities,
            'image_urls' => $booking->image_urls,
            'category' => $booking->category
                ? [
                    'id' => $booking->category->id,
                    'name' => $booking->category->name,
                    'color' => $booking->category->color,
                    'badge_label' => $booking->category->badge_label,
                ]
                : null,
            'creator' => $withCreator && $booking->creator
                ? [
                    'id' => $booking->creator->id,
                    'name' => $booking->creator->name,
                ]
                : null,
        ];
    }
}
