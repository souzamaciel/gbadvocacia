<?php

namespace App\Mail;

use App\Models\Suggestion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SuggestionReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Suggestion $suggestion
    ) {}

    public function envelope(): Envelope
    {
        $type = Suggestion::TYPES[$this->suggestion->type] ?? 'Sugestão';

        return new Envelope(
            replyTo: $this->suggestion->user?->email ? [new Address($this->suggestion->user->email, $this->suggestion->user_name)] : [],
            subject: "[Sugestão #{$this->suggestion->id}] {$type}: {$this->suggestion->title}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.suggestion-received');
    }

    /**
     * The screenshots sent with the suggestion.
     *
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return array_map(
            fn (array $file): Attachment => Attachment::fromStorageDisk('local', $file['path'])->as($file['name']),
            $this->suggestion->attachments ?? [],
        );
    }
}
