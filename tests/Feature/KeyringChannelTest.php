<?php

namespace Tests\Feature;

use App\Keyrings\KeyringPresence;
use App\Keyrings\KeyringType;
use App\Models\Project;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Pusher\Pusher;
use RuntimeException;
use Tests\TestCase;

class KeyringChannelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (glob(database_path('migrations/*.php')) as $path) {
            (require $path)->up();
        }
    }

    #[DataProvider('channelAccess')]
    public function test_presence_channel_membership_follows_resource_access(string $type, array $permissions, bool $enabled, bool $expected): void
    {
        $user = User::factory()->create(['enabled' => $enabled, 'name' => 'Ada Analist']);
        foreach (['samples.view', 'projects.view', 'reports.generate'] as $permission) {
            Gate::define($permission, fn () => in_array($permission, $permissions, true));
        }
        $sample = $this->sample();
        $typeId = $type === 'sample' ? $sample->id : (int) $sample->project;
        $authorize = Broadcast::getChannels()->get('keyrings.{type}.{typeId}');

        $member = $authorize($user, $type, $typeId);

        $this->assertSame($expected ? ['id' => $user->id, 'name' => 'Ada Analist', 'avatar_url' => null] : false, $member);
        $this->assertFalse($authorize($user, $type, 99999));
    }

    public static function channelAccess(): array
    {
        return [
            'sample via sample permission' => ['sample', ['samples.view'], true, true],
            'sample via project permission' => ['sample', ['projects.view'], true, true],
            'sample without permission' => ['sample', [], true, false],
            'project reader' => ['project', ['projects.view'], true, true],
            'project without permission' => ['project', ['samples.view'], true, false],
            'export reporter' => ['export', ['projects.view', 'reports.generate'], true, true],
            'export without report permission' => ['export', ['projects.view'], true, false],
            'disabled user' => ['sample', ['samples.view', 'projects.view'], false, false],
            'unknown type' => ['extern', ['projects.view', 'reports.generate'], true, false],
        ];
    }

    public function test_presence_reads_channel_members_from_reverb(): void
    {
        $this->configureReverb();
        $pusher = Mockery::mock(Pusher::class);
        $pusher->expects('get')->with('/channels/presence-keyrings.project.7/users', [], true)
            ->andReturn(['users' => [['id' => '5'], ['id' => 9]]]);
        $this->mock(BroadcastManager::class)->expects('pusher')
            ->withArgs(fn (array $config): bool => $config['client_options']['timeout'] === 1.5 && $config['options']['timeout'] === 1.5)
            ->andReturn($pusher);

        $presence = app(KeyringPresence::class);

        $this->assertTrue($presence->isPresent(KeyringType::Project, 7, 5));
    }

    public function test_presence_is_unknown_when_reverb_fails_or_is_not_configured(): void
    {
        config(['broadcasting.connections.reverb' => null]);
        $this->assertNull(app(KeyringPresence::class)->isPresent(KeyringType::Sample, 1, 1));

        $this->configureReverb();
        $pusher = Mockery::mock(Pusher::class);
        $pusher->expects('get')->andThrow(new RuntimeException('Connection refused'));
        $this->mock(BroadcastManager::class)->allows('pusher')->andReturn($pusher);

        $this->assertNull(app(KeyringPresence::class)->isPresent(KeyringType::Sample, 1, 1));
    }

    private function configureReverb(): void
    {
        config([
            'keyrings.presence_connection' => 'reverb',
            'keyrings.presence_timeout' => 1.5,
            'broadcasting.connections.reverb' => ['driver' => 'reverb', 'key' => 'key', 'secret' => 'secret', 'app_id' => 'app', 'options' => ['host' => 'reverb']],
        ]);
    }

    private function sample(): Sample
    {
        $project = Project::create(['client' => 1, 'subclient' => 0, 'project_name' => 'Project water', 'lock_pass' => 'private']);

        return Sample::create([
            'barcode' => '26091000', 'follow_no' => 1000, 'description' => 'Water sample',
            'sampling_method' => 1, 'date_registered' => '1788998400', 'registered_by' => 1,
            'client' => 1, 'subclient' => 0, 'project' => $project->id,
            'custom_fields' => '{}', 'predicted_end' => 1789171200, 'sample_innoculated' => '', 'analyses_data' => '[]',
        ]);
    }
}
