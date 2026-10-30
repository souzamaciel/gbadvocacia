@php

    $rows = [
        'Tipo' => \App\Models\Suggestion::TYPES[$suggestion->type] ?? $suggestion->type,
        'Área' => \App\Models\Suggestion::AREAS[$suggestion->area] ?? $suggestion->area,
        \App\Models\Suggestion::IMPACTS[$suggestion->type]['question'] ?? 'Impacto' => $suggestion->impactLabel(),
        'Enviada por' => $suggestion->user_name.($suggestion->user?->email ? ' ('.$suggestion->user->email.')' : ''),
        'Data' => $suggestion->created_at->format('d/m/Y').' às '.$suggestion->created_at->format('H:i'),
        'Tela de origem' => $suggestion->page_title ?: '—',
    ];
@endphp
<!doctype html>
<html lang="pt-BR">
<body style="margin:0;padding:24px;background:#f3f4f8;font-family:Arial,Helvetica,sans-serif;color:#1d2240;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden;">
        <tr>
            <td style="background:#232a54;color:#ffffff;padding:18px 24px;">
                <div style="font-size:12px;color:#d8a764;text-transform:uppercase;letter-spacing:1px;">Sistema de petições · GB Advocacia</div>
                <div style="font-size:18px;font-weight:bold;margin-top:4px;">Sugestão #{{ $suggestion->id }}: {{ $suggestion->title }}</div>
            </td>
        </tr>
        <tr>
            <td style="padding:20px 24px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                    @foreach ($rows as $label => $value)
                        <tr>
                            <td style="padding:6px 0;color:#555b6e;width:200px;vertical-align:top;">{{ $label }}</td>
                            <td style="padding:6px 0;font-weight:bold;">{{ $value }}</td>
                        </tr>
                    @endforeach
                </table>

                <div style="margin-top:18px;padding:14px 16px;background:#f6eee4;border-left:4px solid #c49a6c;border-radius:4px;font-size:14px;line-height:1.55;white-space:pre-line;">{{ $suggestion->description }}</div>

                @if ($suggestion->page_url)
                    <p style="font-size:13px;color:#555b6e;margin:16px 0 0;">Endereço da tela: <a href="{{ $suggestion->page_url }}" style="color:#232a54;">{{ $suggestion->page_url }}</a></p>
                @endif
                @if (! empty($suggestion->attachments))
                    <p style="font-size:13px;color:#555b6e;margin:16px 0 8px;">{{ count($suggestion->attachments) === 1 ? 'Print enviado (também em anexo):' : count($suggestion->attachments).' prints enviados (também em anexo):' }}</p>
                    @foreach ($suggestion->attachments as $file)
                        @php($path = \Illuminate\Support\Facades\Storage::disk('local')->path($file['path']))
                        @if (is_file($path))
                            <img src="{{ isset($message) ? $message->embed($path) : 'data:'.mime_content_type($path).';base64,'.base64_encode(file_get_contents($path)) }}"
                                 alt="{{ $file['name'] }}" width="592" style="display:block;width:100%;max-width:592px;height:auto;border:1px solid #d5d8e0;border-radius:4px;margin:0 0 10px;">
                        @endif
                    @endforeach
                @endif
                <p style="font-size:12px;color:#888888;margin:20px 0 0;">Para responder a quem enviou, basta responder este e-mail.</p>
            </td>
        </tr>
    </table>
</body>
</html>
