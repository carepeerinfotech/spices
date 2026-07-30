<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Services\Mail\TemplateMailer;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function index()
    {
        return view('admin.email-templates.index', [
            'templates' => EmailTemplate::orderBy('name')->get(),
        ]);
    }

    public function edit(EmailTemplate $email_template)
    {
        return view('admin.email-templates.form', ['template' => $email_template]);
    }

    public function update(Request $request, EmailTemplate $email_template)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $email_template->update([
            ...$data,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template updated.',
            'redirect' => route('admin.email-templates.index'),
        ]);
    }

    public function testSend(Request $request, EmailTemplate $email_template, TemplateMailer $mailer)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $placeholders = [];
        foreach (($email_template->placeholders ?? []) as $key) {
            $placeholders[$key] = 'Sample '.$key;
        }

        $mailer->send($email_template->slug, $data['email'], $placeholders);

        return response()->json(['success' => true, 'message' => 'Test email queued/sent.']);
    }
}
