<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSuggestionRequest;
use App\Mail\SuggestionReceived;
use App\Models\Suggestion;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SuggestionController extends Controller
{
    /**
     * Saves the suggestion and e-mails it to the system owner after the response, so the
     * person does not wait for the e-mail server.
     */
    public function store(StoreSuggestionRequest $request): JsonResponse
    {
        $suggestion = Suggestion::create([
            'user_id' => $request->user()->id,
            'user_name' => $request->user()->name,
            ...$request->safe()->only(['type', 'area', 'impact', 'title', 'description', 'page_title']),
            'page_url' => Str::startsWith((string) $request->input('page_url'), url('/')) ? $request->input('page_url') : null,
        ]);

        $attachments = [];
        foreach ($request->file('images', []) as $index => $image) {
            $name = ($index + 1).'-'.Str::slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) ?: 'print').'.'.$image->extension();
            $attachments[] = ['path' => $image->storeAs("sugestoes/{$suggestion->id}", $name, 'local'), 'name' => $name];
        }
        $suggestion->update(['attachments' => $attachments ?: null]);

        app(ActivityLogger::class)->log('suggestion.sent', 'Enviou uma sugestão: '.(Suggestion::TYPES[$suggestion->type] ?? $suggestion->type).'.', $suggestion);

        $recipient = config('services.sugestoes.email');
        if ($recipient) {
            app()->terminating(function () use ($suggestion, $recipient): void {
                try {
                    Mail::to($recipient)->send(new SuggestionReceived($suggestion));
                    $suggestion->update(['emailed_at' => now()]);
                } catch (Throwable $e) {
                    report($e);
                }
            });
        }

        return response()->json(['message' => 'Sugestão enviada. Obrigado por ajudar a melhorar o sistema!'], 201);
    }
}
