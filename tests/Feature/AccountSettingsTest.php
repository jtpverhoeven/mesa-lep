<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_username_consolidation_keeps_aliases_and_backfills_only_empty_aliases(): void
    {
        Schema::table('profiles', fn (Blueprint $table) => $table->string('username')->nullable()->unique());
        $existing = $this->userWithProfile();
        $existing->update(['alias' => 'chosen']);
        $empty = $this->userWithProfile();
        DB::table('profiles')->where('user_id', $existing->id)->update(['username' => 'discarded']);
        DB::table('profiles')->where('user_id', $empty->id)->update(['username' => 'OLD-LOGIN']);

        $migration = require database_path('migrations/2026_10_20_155336_consolidate_profile_usernames_into_user_aliases.php');
        $migration->up();

        $this->assertSame('chosen', $existing->fresh()->alias);
        $this->assertSame('old-login', $empty->fresh()->alias);
        $this->assertFalse(Schema::hasColumn('profiles', 'username'));
        $this->postJson('/login', ['login' => 'discarded', 'password' => 'password'])->assertUnprocessable();
        $this->post('/login', ['login' => 'OLD-LOGIN', 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($empty);
    }

    public function test_username_migration_aborts_without_data_loss_for_conflicting_logins(): void
    {
        Schema::table('profiles', fn (Blueprint $table) => $table->string('username')->nullable()->unique());
        $existing = $this->userWithProfile();
        $existing->update(['alias' => 'taken']);
        $empty = $this->userWithProfile();
        DB::table('profiles')->where('user_id', $empty->id)->update(['username' => 'TAKEN']);
        $migration = require database_path('migrations/2026_10_20_155336_consolidate_profile_usernames_into_user_aliases.php');

        try {
            $migration->up();
            $this->fail('Conflicting login identifiers must stop migration.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Conflicting login identifiers', $exception->getMessage());
        }

        $this->assertNull($empty->fresh()->alias);
        $this->assertTrue(Schema::hasColumn('profiles', 'username'));
        $this->assertDatabaseHas('profiles', ['user_id' => $empty->id, 'username' => 'TAKEN']);
    }

    #[DataProvider('accountEndpoints')]
    public function test_guests_cannot_access_account_endpoints(string $method, string $path): void
    {
        $this->json($method, '/account/settings'.$path)->assertUnauthorized();
    }

    public static function accountEndpoints(): array
    {
        return [
            'settings' => ['GET', ''],
            'profile' => ['PUT', '/profile'],
            'alias' => ['PUT', '/alias'],
            'password' => ['PUT', '/password'],
            'view avatar' => ['GET', '/images/avatar'],
            'upload avatar' => ['POST', '/images/avatar'],
            'delete avatar' => ['DELETE', '/images/avatar'],
            'view signature' => ['GET', '/images/nl'],
            'upload signature' => ['POST', '/images/en'],
            'delete signature' => ['DELETE', '/images/en'],
        ];
    }

    public function test_settings_are_available_without_administrative_permissions_and_escape_names(): void
    {
        $user = User::factory()->create(['enabled' => true, 'name' => '<script>alert("name")</script>']);

        $this->actingAs($user)->get(route('account.settings'))
            ->assertOk()->assertSee('Accountinstellingen')->assertDontSee('<aside', false)
            ->assertViewHas('account', fn (array $account): bool => $account['email'] === $user->email
                && ! isset($account['password']) && $account['images'] === ['avatar' => null, 'nl' => null, 'en' => null])
            ->assertDontSee($user->name, false);

        $this->assertDatabaseCount('profiles', 0);
    }

    public function test_profile_updates_only_the_authenticated_user_and_preserves_account_settings(): void
    {
        $user = $this->userWithProfile();
        $other = $this->userWithProfile();
        $password = $user->password;

        $this->actingAs($user)->putJson(route('account.profile.update'), [
            ...$this->profileData(), 'user_id' => $other->id, 'enabled' => false,
            'roles' => ['administrator'], 'password' => 'injected', 'avatar_path' => 'other.png',
        ])->assertOk()->assertJsonPath('name', 'Ada Lovelace');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Ada Lovelace', 'email' => $user->email, 'password' => $password, 'enabled' => true]);
        $this->assertDatabaseHas('profiles', [...$this->profileData(), 'user_id' => $user->id, 'locale' => 'en', 'avatar_path' => null]);
        $this->assertSame($other->name, $other->fresh()->name);
        $this->assertFalse($user->fresh()->hasRole('administrator'));
    }

    public function test_a_missing_profile_is_created_when_personal_details_are_saved(): void
    {
        $user = User::factory()->create(['enabled' => true]);

        $this->actingAs($user)->putJson(route('account.profile.update'), $this->profileData())->assertOk();

        $this->assertDatabaseHas('profiles', ['user_id' => $user->id, 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
    }

    #[DataProvider('invalidProfileFields')]
    public function test_invalid_profile_updates_return_422_without_changing_data(string $field, mixed $value): void
    {
        $user = $this->userWithProfile();

        $this->actingAs($user)->putJson(route('account.profile.update'), array_replace($this->profileData(), [$field => $value]))
            ->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertSame($user->name, $user->fresh()->name);
        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertSame('Original', $user->profile->fresh()->first_name);
    }

    public static function invalidProfileFields(): array
    {
        return [
            'email is immutable' => ['email', 'changed@example.com'],
            'username is immutable' => ['username', 'changed'],
            'required first name' => ['first_name', ''],
            'required last name' => ['last_name', ''],
            'title length' => ['title', str_repeat('t', 51)],
            'name length' => ['first_name', str_repeat('n', 121)],
            'gender length' => ['gender', str_repeat('g', 21)],
            'job length' => ['job_title', str_repeat('j', 151)],
            'phone length' => ['phone_number', str_repeat('1', 51)],
        ];
    }

    public function test_alias_is_normalized_and_can_be_removed_without_changing_email(): void
    {
        $user = $this->userWithProfile();

        $this->actingAs($user)->putJson(route('account.alias.update'), ['alias' => '  Ada.L  '])
            ->assertOk()->assertJsonPath('alias', 'ada.l');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'alias' => 'ada.l']);

        $this->putJson(route('account.alias.update'), ['alias' => ''])->assertOk()->assertJsonPath('alias', null);
        $this->assertNull($user->fresh()->alias);
        $this->assertSame($user->email, $user->fresh()->email);
    }

    #[DataProvider('loginIdentifiers')]
    public function test_alias_cannot_collide_with_another_users_login_case_insensitively(string $identifier): void
    {
        $owner = $this->userWithProfile();
        $owner->update(['alias' => 'operator']);
        $user = User::factory()->create(['enabled' => true]);
        $value = $owner->{$identifier};

        $this->actingAs($user)->putJson(route('account.alias.update'), ['alias' => strtoupper($value)])
            ->assertUnprocessable()->assertJsonValidationErrors(['alias' => 'Deze inlognaam is al in gebruik.']);

        $this->assertNull($user->fresh()->alias);
    }

    public static function loginIdentifiers(): array
    {
        return ['alias' => ['alias'], 'email' => ['email']];
    }

    public function test_own_alias_can_be_kept_and_malformed_alias_is_rejected(): void
    {
        $user = User::factory()->create(['enabled' => true, 'alias' => 'operator']);

        $this->actingAs($user)->putJson(route('account.alias.update'), ['alias' => 'OPERATOR'])->assertOk();
        $this->putJson(route('account.alias.update'), ['alias' => ['invalid']])->assertUnprocessable()->assertJsonValidationErrors('alias');

        $this->assertSame('operator', $user->fresh()->alias);
    }

    #[DataProvider('loginIdentifiers')]
    public function test_users_can_log_in_with_alias_or_email(string $identifier): void
    {
        $user = $this->userWithProfile();
        $user->update(['alias' => 'operator']);
        $value = $user->{$identifier};

        $this->post('/login', ['login' => strtoupper($value), 'password' => 'password'])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_disabled_accounts_cannot_log_in_using_an_alias(): void
    {
        User::factory()->create(['alias' => 'disabled', 'enabled' => false]);

        $this->postJson('/login', ['login' => 'disabled', 'password' => 'password'])
            ->assertUnprocessable()->assertJsonValidationErrors('login');

        $this->assertGuest();
    }

    public function test_ambiguous_legacy_login_identifiers_do_not_select_an_arbitrary_account(): void
    {
        User::factory()->create(['alias' => 'collision@example.com']);
        User::factory()->create(['email' => 'collision@example.com']);

        $this->postJson('/login', ['login' => 'collision@example.com', 'password' => 'password'])
            ->assertUnprocessable()->assertJsonValidationErrors('login');

        $this->assertGuest();
    }

    public function test_disabled_accounts_cannot_update_profile_information(): void
    {
        $user = User::factory()->create(['enabled' => false]);

        $this->actingAs($user)->putJson(route('account.profile.update'), $this->profileData())
            ->assertRedirect(route('login'));

        $this->assertSame($user->name, $user->fresh()->name);
        $this->assertDatabaseCount('profiles', 0);
        $this->assertGuest();
    }

    #[DataProvider('administrativeLoginFields')]
    public function test_administration_cannot_assign_an_existing_alias_as_a_login(string $field): void
    {
        User::factory()->create(['alias' => 'reserved@example.com']);
        $admin = $this->userWithProfile();
        $admin->assignRole(Role::findOrCreate('administrator', 'web'));
        $data = [...$this->profileData(), 'alias' => 'new-user', 'email' => 'new@example.com', 'locale' => 'nl', 'enabled' => true, 'password' => 'new-password', 'password_confirmation' => 'new-password', $field => 'RESERVED@example.com'];

        $this->actingAs($admin)->postJson(route('users.store'), $data)
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->putJson(route('users.update', $admin), $data)
            ->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('users', 2);
        $this->assertSame($admin->email, $admin->fresh()->email);
    }

    public static function administrativeLoginFields(): array
    {
        return ['alias' => ['alias'], 'email' => ['email']];
    }

    public function test_administrative_user_creation_and_updates_still_accept_available_logins(): void
    {
        $admin = $this->userWithProfile();
        $admin->assignRole(Role::findOrCreate('administrator', 'web'));
        $data = [...$this->profileData(), 'alias' => '  NEW-USER  ', 'email' => 'new@example.com', 'locale' => 'nl', 'enabled' => true, 'password' => 'new-password', 'password_confirmation' => 'new-password'];

        $this->actingAs($admin)->post(route('users.store'), $data)->assertRedirect()->assertSessionHasNoErrors();
        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertSame('new-user', $user->alias);
        $this->put(route('users.update', $user), [...$data, 'alias' => ' NEW-ALIAS ', 'job_title' => 'Hoofdanalist'])
            ->assertRedirect(route('users.edit', $user))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('profiles', ['user_id' => $user->id, 'job_title' => 'Hoofdanalist']);
        $this->assertSame('new-alias', $user->fresh()->alias);
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_password_changes_require_the_current_password_and_use_hashing(): void
    {
        $user = User::factory()->create(['enabled' => true]);

        $this->actingAs($user)->putJson(route('account.password.update'), [
            'current_password' => 'password', 'password' => 'new-secret-password', 'password_confirmation' => 'new-secret-password',
        ])->assertOk()->assertJsonPath('message', 'Wachtwoord gewijzigd.');

        $this->assertTrue(Hash::check('new-secret-password', $user->fresh()->password));
        $this->assertFalse(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_attempts_are_throttled_without_blocking_image_uploads(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['enabled' => true]);
        $this->actingAs($user);
        $payload = ['current_password' => 'wrong', 'password' => 'new-secret-password', 'password_confirmation' => 'new-secret-password'];

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->putJson(route('account.password.update'), $payload)->assertUnprocessable();
        }
        $this->putJson(route('account.password.update'), $payload)->assertTooManyRequests();
        $this->postJson(route('account.images.store', 'nl'), ['image' => UploadedFile::fake()->image('signature.png')])->assertOk();

        $this->assertSame($user->password, $user->fresh()->password);
        Storage::disk('local')->assertExists("signatures/{$user->id}.png");
    }

    #[DataProvider('invalidPasswords')]
    public function test_invalid_password_changes_return_422_and_keep_the_original_password(array $overrides, string $field): void
    {
        $user = User::factory()->create(['enabled' => true]);

        $this->actingAs($user)->putJson(route('account.password.update'), array_replace([
            'current_password' => 'password', 'password' => 'new-secret-password', 'password_confirmation' => 'new-secret-password',
        ], $overrides))->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertSame($user->password, $user->fresh()->password);
    }

    public static function invalidPasswords(): array
    {
        return [
            'wrong current password' => [['current_password' => 'incorrect'], 'current_password'],
            'confirmation mismatch' => [['password_confirmation' => 'different'], 'password'],
            'weak password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
        ];
    }

    public function test_avatars_are_normalized_to_256_pixel_pngs_and_replacements_remove_the_old_file(): void
    {
        Storage::fake('local');
        $user = $this->userWithProfile();
        Storage::disk('local')->put('avatars/old.png', 'old avatar');
        $user->profile->update(['avatar_path' => 'avatars/old.png']);

        $this->actingAs($user)->postJson(route('account.images.store', 'avatar'), [
            'image' => UploadedFile::fake()->image('photo.jpg', 1200, 600),
        ])->assertOk()->assertJsonPath('url', route('account.images.show', 'avatar'));

        $path = $user->profile->fresh()->avatar_path;
        Storage::disk('local')->assertExists($path);
        Storage::disk('local')->assertMissing('avatars/old.png');
        $this->assertSame([256, 256, IMAGETYPE_PNG], array_slice(getimagesizefromstring(Storage::disk('local')->get($path)), 0, 3));
        $this->get(route('account.images.show', 'avatar'))->assertOk()
            ->assertHeader('Content-Type', 'image/png')->assertHeaderContains('Cache-Control', 'private')
            ->assertHeaderContains('Cache-Control', 'no-store')
            ->assertStreamedContent(Storage::disk('local')->get($path));
    }

    public function test_avatar_can_be_uploaded_before_a_profile_exists_and_deleted(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['enabled' => true]);

        $this->actingAs($user)->postJson(route('account.images.store', 'avatar'), ['image' => UploadedFile::fake()->image('photo.png')])->assertOk();
        $path = $user->fresh()->profile->avatar_path;
        $this->deleteJson(route('account.images.destroy', 'avatar'))->assertOk();

        Storage::disk('local')->assertMissing($path);
        $this->assertNull($user->fresh()->profile->avatar_path);
        $this->get(route('account.images.show', 'avatar'))->assertNotFound();
    }

    #[DataProvider('signatureLanguages')]
    public function test_signatures_keep_legacy_language_filenames_and_do_not_replace_the_other_language(string $language, string $suffix, string $otherSuffix): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['enabled' => true]);
        $path = "signatures/{$user->id}{$suffix}.png";
        $other = "signatures/{$user->id}{$otherSuffix}.png";
        Storage::disk('local')->put($path, 'old');
        Storage::disk('local')->put($other, 'other language');

        $this->actingAs($user)->postJson(route('account.images.store', $language), [
            'image' => UploadedFile::fake()->image('signature.jpg', 2000, 1000),
        ])->assertOk();

        $this->assertSame([1200, 600, IMAGETYPE_PNG], array_slice(getimagesizefromstring(Storage::disk('local')->get($path)), 0, 3));
        $this->get(route('account.images.show', $language))->assertOk()->assertStreamedContent(Storage::disk('local')->get($path));
        $this->assertSame('other language', Storage::disk('local')->get($other));
        $this->deleteJson(route('account.images.destroy', $language))->assertOk();
        Storage::disk('local')->assertMissing($path);
        Storage::disk('local')->assertExists($other);
    }

    public static function signatureLanguages(): array
    {
        return ['Dutch' => ['nl', '', 'En'], 'English' => ['en', 'En', '']];
    }

    public function test_images_cannot_be_read_or_removed_by_another_account(): void
    {
        Storage::fake('local');
        $owner = $this->userWithProfile();
        $owner->profile->update(['avatar_path' => 'avatars/private.png']);
        Storage::disk('local')->put('avatars/private.png', 'avatar');
        Storage::disk('local')->put("signatures/{$owner->id}.png", 'signature');
        $other = User::factory()->create(['enabled' => true]);

        $this->actingAs($other)->get(route('account.images.show', 'avatar').'?user_id='.$owner->id)->assertNotFound();
        $this->get(route('account.images.show', 'nl').'?user_id='.$owner->id)->assertNotFound();
        $this->deleteJson(route('account.images.destroy', 'nl'), ['user_id' => $owner->id])->assertOk();

        Storage::disk('local')->assertExists(['avatars/private.png', "signatures/{$owner->id}.png"]);
    }

    #[DataProvider('invalidImages')]
    public function test_invalid_uploads_return_422_without_replacing_existing_images(string $kind, string $invalid): void
    {
        Storage::fake('local');
        $user = $this->userWithProfile();
        $user->profile->update(['avatar_path' => 'avatars/original.png']);
        $path = $kind === 'avatar' ? 'avatars/original.png' : "signatures/{$user->id}.png";
        Storage::disk('local')->put($path, 'original');
        $file = match ($invalid) {
            'svg' => UploadedFile::fake()->createWithContent('image.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            'spoofed' => UploadedFile::fake()->createWithContent('image.png', '<?php echo "not an image";'),
            'oversized' => UploadedFile::fake()->image('image.png')->size(5121),
            'dimensions' => UploadedFile::fake()->image('image.png', 4097, 1),
            'corrupt' => UploadedFile::fake()->createWithContent('image.png', substr(UploadedFile::fake()->image('image.png')->getContent(), 0, 30)),
        };

        $this->actingAs($user)->postJson(route('account.images.store', $kind), ['image' => $file])
            ->assertUnprocessable()->assertJsonValidationErrors('image');

        $this->assertSame('original', Storage::disk('local')->get($path));
        $this->assertSame('avatars/original.png', $user->profile->fresh()->avatar_path);
    }

    public static function invalidImages(): array
    {
        return [
            'SVG avatar' => ['avatar', 'svg'], 'spoofed avatar' => ['avatar', 'spoofed'],
            'large avatar' => ['avatar', 'oversized'], 'wide avatar' => ['avatar', 'dimensions'],
            'corrupt avatar' => ['avatar', 'corrupt'], 'SVG signature' => ['nl', 'svg'],
        ];
    }

    public function test_failed_storage_writes_are_reported_without_changing_the_avatar_path(): void
    {
        Exceptions::fake();
        $user = $this->userWithProfile();
        $user->profile->update(['avatar_path' => 'avatars/original.png']);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);

        $this->actingAs($user)->postJson(route('account.images.store', 'avatar'), ['image' => UploadedFile::fake()->image('image.png')])
            ->assertInternalServerError();

        $this->assertSame('avatars/original.png', $user->profile->fresh()->avatar_path);
        Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'Account image could not be saved.');
    }

    public function test_unknown_image_kinds_are_not_routable(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => true]))->postJson('/account/settings/images/fr')->assertNotFound();
    }

    private function userWithProfile(): User
    {
        $user = User::factory()->create(['enabled' => true]);
        $user->profile()->create(['first_name' => 'Original', 'last_name' => 'Name', 'locale' => 'en']);

        return $user;
    }

    private function profileData(): array
    {
        return ['title' => 'Dr.', 'first_name' => 'Ada', 'last_name' => 'Lovelace', 'gender' => 'F', 'job_title' => 'Analist', 'phone_number' => '+31 20 123 4567'];
    }
}
