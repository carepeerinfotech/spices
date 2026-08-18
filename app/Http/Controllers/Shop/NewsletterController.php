<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Services\Mail\TemplateMailer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function __construct(private TemplateMailer $mailer) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $subscriber = NewsletterSubscriber::firstOrCreate(['email' => $data['email']]);

        if ($subscriber->wasRecentlyCreated) {
            $this->notifyAdmins($subscriber);
        }

        return response()->json([
            'success' => true,
            'message' => 'Thanks for subscribing! Watch your inbox for your welcome discount.',
        ]);
    }

    /**
     * Tell the store owners about a new subscriber. The row is already saved, so a
     * mail failure must not turn a successful signup into an error for the visitor.
     */
    private function notifyAdmins(NewsletterSubscriber $subscriber): void
    {
        $admins = $this->mailer->adminRecipients();
        if ($admins === []) {
            return;
        }

        try {
            $this->mailer->send('newsletter_signup_admin', $admins, [
                'email' => e($subscriber->email),
                'subscribed_at' => $subscriber->created_at->format('d M Y, g:i a'),
                'total_subscribers' => (string) NewsletterSubscriber::count(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
