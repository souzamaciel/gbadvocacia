<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Petition;
use App\Models\Topic;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\UserAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private const CHROME_WINDOWS = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36';

    public function test_logins_and_refused_attempts_are_recorded_with_ip_and_browser(): void
    {
        $user = User::factory()->create(['email' => 'carla@escritorio.test', 'name' => 'Carla']);

        $this->withHeader('User-Agent', self::CHROME_WINDOWS)->post(route('login'), ['email' => 'carla@escritorio.test', 'password' => 'errada']);
        $this->withHeader('User-Agent', self::CHROME_WINDOWS)->post(route('login'), ['email' => 'carla@escritorio.test', 'password' => 'password']);

        $failed = ActivityLog::query()->where('event', 'auth.failed')->sole();
        $this->assertNull($failed->user_id);
        $this->assertSame(['E-mail informado' => 'carla@escritorio.test'], $failed->properties);

        $login = ActivityLog::query()->where('event', 'auth.login')->sole();
        $this->assertSame($user->id, $login->user_id);
        $this->assertSame('Carla', $login->user_name);
        $this->assertSame('127.0.0.1', $login->ip);
        $this->assertSame('Rede local', $login->location);
        $this->assertSame(['Chrome 141', 'Windows', 'Computador'], [$login->browser, $login->platform, $login->device]);
    }

    public function test_creating_and_deleting_a_petition_is_recorded_with_its_name(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create();

        $this->actingAs($user)->post(route('petition.store'), ['name' => 'Petição — Maria', 'topics' => [$topic->id]]);
        $petition = Petition::query()->sole();
        $this->actingAs($user)->delete(route('petition.destroy', $petition));

        $this->assertSame(['petition.created', 'petition.deleted'], ActivityLog::query()->orderBy('id')->pluck('event')->all());
        $this->assertSame('Petição — Maria', ActivityLog::query()->where('event', 'petition.deleted')->value('subject_label'));
    }

    public function test_appointment_draft_is_recorded(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('appointment.draft.store'), ['cliente_nome' => 'Maria Ferreira'])->assertOk();

        $log = ActivityLog::query()->sole();
        $this->assertSame('appointment.created', $log->event);
        $this->assertSame('Maria Ferreira', $log->subject_label);
    }

    public function test_public_ip_gets_an_approximate_location_after_the_response(): void
    {
        Http::fake(['api.db-ip.com/*' => Http::response(['city' => 'Ipatinga', 'stateProv' => 'Minas Gerais', 'countryCode' => 'BR', 'countryName' => 'Brazil'])]);
        $user = User::factory()->create();

        $this->actingAs($user)->withServerVariables(['REMOTE_ADDR' => '177.10.20.30'])->post(route('logout'));

        $log = ActivityLog::query()->sole();
        $this->assertSame('177.10.20.30', $log->ip);
        $this->assertSame('Ipatinga, Minas Gerais, Brasil', $log->location);
        Http::assertSentCount(1);
    }

    public function test_location_lookup_failure_keeps_the_record(): void
    {
        Http::fake(['api.db-ip.com/*' => Http::response('erro', 500)]);
        $user = User::factory()->create();

        $this->actingAs($user)->withServerVariables(['REMOTE_ADDR' => '177.10.20.31'])->post(route('logout'));

        $this->assertNull(ActivityLog::query()->sole()->location);
    }

    public function test_logs_screen_filters_and_removes_records_older_than_six_months(): void
    {
        $super = User::factory()->superAdmin()->create(['name' => 'Superadmin']);
        ActivityLog::query()->create(['event' => 'auth.login', 'description' => 'Entrou no sistema.', 'user_name' => 'Antigo'])->forceFill(['created_at' => now()->subMonths(7)])->save();
        ActivityLog::query()->create(['event' => 'topic.deleted', 'description' => 'Excluiu o tópico da Biblioteca.', 'user_name' => 'Carla', 'subject_label' => 'HORAS EXTRAS']);
        ActivityLog::query()->create(['event' => 'auth.login', 'description' => 'Entrou no sistema.', 'user_name' => 'João']);

        $this->actingAs($super)->get(route('admin.logs.index', ['area' => 'Biblioteca e ficha']))
            ->assertOk()
            ->assertSee('Tópico excluído')
            ->assertSee('HORAS EXTRAS')
            ->assertDontSee('João');

        $this->assertFalse(ActivityLog::query()->where('user_name', 'Antigo')->exists());
    }

    public function test_user_agent_is_read_for_phones_and_tablets(): void
    {
        $this->assertSame(
            ['browser' => 'Safari 18', 'platform' => 'iOS', 'device' => 'Celular'],
            UserAgent::parse('Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1'),
        );
        $this->assertSame('Tablet', UserAgent::parse('Mozilla/5.0 (Linux; Android 14; SM-X200) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0 Safari/537.36')['device']);
        $this->assertSame('Entrada no sistema', ActivityLogger::label('auth.login'));
    }
}
