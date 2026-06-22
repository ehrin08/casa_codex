<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\CustomerReview;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerReviewController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'sentiment' => ['nullable', Rule::in(CustomerReview::SENTIMENTS)],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'service_id' => ['nullable', 'integer', Rule::exists('services', 'id')],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $query = CustomerReview::query()
            ->when($filters['sentiment'] ?? null, fn ($query, $sentiment) => $query->where('sentiment_label', $sentiment))
            ->when($filters['rating'] ?? null, fn ($query, $rating) => $query->where('rating', $rating))
            ->when($filters['service_id'] ?? null, fn ($query, $serviceId) => $query->where('service_id', $serviceId))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('reviewed_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('reviewed_at', '<=', $date));

        $sentimentCounts = (clone $query)
            ->selectRaw('sentiment_label, COUNT(*) as aggregate')
            ->groupBy('sentiment_label')
            ->pluck('aggregate', 'sentiment_label');

        $summary = [
            'total' => (clone $query)->count(),
            'average_rating' => round((float) ((clone $query)->avg('rating') ?? 0), 2),
            'positive' => (int) $sentimentCounts->get(CustomerReview::SENTIMENT_POSITIVE, 0),
            'neutral' => (int) $sentimentCounts->get(CustomerReview::SENTIMENT_NEUTRAL, 0),
            'negative' => (int) $sentimentCounts->get(CustomerReview::SENTIMENT_NEGATIVE, 0),
        ];

        $reviews = $query
            ->with(['customerProfile', 'appointment', 'service'])
            ->orderByDesc('reviewed_at')
            ->paginate(20)
            ->withQueryString();

        $services = Service::query()->orderBy('name')->get(['id', 'name']);

        return view('management.reviews.index', compact('reviews', 'summary', 'filters', 'services'));
    }

    public function show(CustomerReview $review): View
    {
        $review->load(['customerProfile', 'appointment.therapistProfile', 'service']);

        return view('management.reviews.show', compact('review'));
    }
}
