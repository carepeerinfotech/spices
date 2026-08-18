<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterSubscriberController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('q'));

        $subscribers = NewsletterSubscriber::query()
            ->when($search !== '', fn ($query) => $query->where('email', 'like', '%'.$search.'%'))
            // Signups within the same second would otherwise paginate unpredictably.
            ->latest()
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.newsletter-subscribers.index', [
            'subscribers' => $subscribers,
            'search' => $search,
            'total' => NewsletterSubscriber::count(),
        ]);
    }

    /**
     * Stream the list as CSV so it can be pasted into a mailing tool.
     */
    public function export(): StreamedResponse
    {
        $filename = 'newsletter-subscribers-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Email', 'Subscribed at']);

            NewsletterSubscriber::orderBy('id')->chunk(500, function ($chunk) use ($handle) {
                foreach ($chunk as $subscriber) {
                    fputcsv($handle, [$subscriber->email, $subscriber->created_at?->toDateTimeString()]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function destroy(NewsletterSubscriber $newsletter_subscriber)
    {
        $newsletter_subscriber->delete();

        return response()->json(['success' => true, 'message' => 'Subscriber removed.']);
    }
}
