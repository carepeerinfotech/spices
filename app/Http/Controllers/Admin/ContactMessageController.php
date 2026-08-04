<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;

class ContactMessageController extends Controller
{
    public function index()
    {
        $messages = ContactMessage::latest()->paginate(15);

        return view('admin.contact-messages.index', compact('messages'));
    }

    public function show(ContactMessage $contact_message)
    {
        if (! $contact_message->read_at) {
            $contact_message->update(['read_at' => now()]);
        }

        return view('admin.contact-messages.show', ['message' => $contact_message]);
    }

    public function destroy(ContactMessage $contact_message)
    {
        $contact_message->delete();

        return response()->json(['success' => true, 'message' => 'Message deleted successfully.']);
    }
}
