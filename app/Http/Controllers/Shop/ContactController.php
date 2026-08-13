<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Services\CartService;
use App\Services\Mail\TemplateMailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function __construct(private TemplateMailer $mailer) {}

    public function show(CartService $cartService)
    {
        return view('shop.contact', [
            'cartCount' => $cartService->getCart()->itemCount(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $contactMessage = ContactMessage::create($data);

        $this->notifyAdmins($contactMessage);

        return back()->with('success', "Thanks for reaching out! We'll get back to you soon.");
    }

    /**
     * Alert the store owners about a new enquiry. The message is already saved and
     * visible in the admin, so a mail failure must not fail the visitor's request.
     */
    private function notifyAdmins(ContactMessage $message): void
    {
        $admins = $this->mailer->adminRecipients();
        if ($admins === []) {
            return;
        }

        try {
            $this->mailer->send('contact_message_admin', $admins, [
                'name' => $message->name,
                'email' => $message->email,
                'phone' => $message->phone ?: 'Not provided',
                // Visitor-supplied text goes into an HTML mail: escape it.
                'message' => nl2br(e($message->message)),
                'received_at' => $message->created_at->format('d M Y, g:i a'),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
