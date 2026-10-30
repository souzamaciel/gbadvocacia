<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Records who did what, when and from where (IP, approximate location, browser and
 * device). The location is looked up after the response, so it never slows the system.
 */
class ActivityLogger
{
    /**
     * Event keys by area, with the label shown on the logs screen.
     */
    public const EVENTS = [
        'Acesso' => [
            'auth.login' => 'Entrada no sistema',
            'auth.failed' => 'Tentativa de entrada recusada',
            'auth.blocked' => 'Entrada de usuário desativado',
            'auth.logout' => 'Saída do sistema',
            'profile.updated' => 'Perfil alterado',
            'profile.password' => 'Senha alterada pelo próprio usuário',
        ],
        'Atendimentos' => [
            'appointment.created' => 'Atendimento criado',
            'appointment.updated' => 'Rascunho de atendimento salvo',
            'appointment.concluded' => 'Atendimento concluído',
            'appointment.imported' => 'Ficha importada do Word',
            'appointment.printed' => 'Ficha impressa',
            'appointment.deleted' => 'Atendimento excluído',
        ],
        'Petições' => [
            'petition.created' => 'Petição criada',
            'petition.updated' => 'Tópicos da petição alterados',
            'petition.generated' => 'Word da petição gerado',
            'petition.deleted' => 'Petição excluída',
        ],
        'Sugestões' => [
            'suggestion.sent' => 'Sugestão enviada',
        ],
        'Usuários e perfis' => [
            'user.created' => 'Usuário cadastrado',
            'user.updated' => 'Usuário alterado',
            'user.password_reset' => 'Senha provisória definida',
            'user.activated' => 'Usuário reativado',
            'user.deactivated' => 'Usuário desativado',
            'role.created' => 'Perfil criado',
            'role.updated' => 'Perfil alterado',
            'role.deleted' => 'Perfil excluído',
        ],
        'Biblioteca e ficha' => [
            'topic.created' => 'Tópico criado',
            'topic.updated' => 'Tópico alterado',
            'topic.deleted' => 'Tópico excluído',
            'group.created' => 'Grupo criado',
            'group.updated' => 'Grupo alterado',
            'group.deleted' => 'Grupo excluído',
            'form.changed' => 'Ficha de atendimento alterada',
        ],
    ];

    public function __construct(
        protected Request $request
    ) {}

    /**
     * @param  array<string, mixed>  $properties  extra details shown on the logs screen
     */
    public function log(string $event, string $description, ?Model $subject = null, array $properties = [], ?User $user = null): ActivityLog
    {
        $user ??= $this->request->user();
        $agent = UserAgent::parse($this->request->userAgent());
        $ip = $this->request->ip();

        $log = ActivityLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'event' => $event,
            'description' => mb_substr($description, 0, 500),
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'subject_label' => $subject ? mb_substr($this->subjectLabel($subject), 0, 255) : null,
            'properties' => $properties === [] ? null : $properties,
            'ip' => $ip,
            'location' => $this->isPrivate($ip) ? 'Rede local' : null,
            'user_agent' => $this->request->userAgent(),
            ...$agent,
        ]);

        if ($log->location === null && $ip !== null) {
            app()->terminating(fn () => $this->locate($log));
        }

        return $log;
    }

    public static function label(string $event): string
    {
        foreach (self::EVENTS as $events) {
            if (isset($events[$event])) {
                return $events[$event];
            }
        }

        return $event;
    }

    /**
     * Approximate location of the IP (city, state, country), cached for 30 days per IP.
     * Uses DB-IP's free lookup; when it is unavailable the location stays blank.
     */
    public function locate(ActivityLog $log): void
    {
        if ($log->location !== null || ! config('services.geoip.enabled', true)) {
            return;
        }

        try {
            $location = Cache::remember('geoip:'.$log->ip, now()->addDays(30), function () use ($log): string {
                $data = Http::timeout(4)->acceptJson()->get('https://api.db-ip.com/v2/free/'.$log->ip)->throw()->json();

                return implode(', ', array_filter([
                    $data['city'] ?? null,
                    $data['stateProv'] ?? null,
                    $this->countryName($data['countryCode'] ?? null, $data['countryName'] ?? null),
                ])) ?: 'Não identificada';
            });

            $log->update(['location' => $location]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Country name in Portuguese (the lookup answers in English).
     */
    private function countryName(?string $code, ?string $name): ?string
    {
        if ($code === 'BR') {
            return 'Brasil';
        }

        if ($code !== null && class_exists(\Locale::class)) {
            return \Locale::getDisplayRegion('-'.$code, 'pt_BR') ?: $name;
        }

        return $name;
    }

    private function subjectLabel(Model $subject): string
    {
        return (string) match (true) {
            method_exists($subject, 'displayName') => $subject->displayName(),
            isset($subject->name) => $subject->name,
            isset($subject->title) => $subject->title,
            isset($subject->label) => $subject->label,
            default => '#'.$subject->getKey(),
        };
    }

    private function isPrivate(?string $ip): bool
    {
        return $ip === null || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
