<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UsersSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\StoreTestCase;

class UsersSeederTest extends StoreTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['seeders.user.email' => 'user@phoneshop.com', 'seeders.user.password' => null]);
    }

    public function test_seeder_creates_one_verified_customer_without_sending_email()
    {
        $this->seed(UsersSeeder::class);
        $user = User::where('email', 'user@phoneshop.com')->firstOrFail();

        $this->assertSame('TDH Customer', $user->name);
        $this->assertSame('user', $user->role);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertNotSame('12345678', $user->password);
        $this->assertTrue(Hash::check('12345678', $user->password));
        $this->assertSame(1, User::where('email', $user->email)->count());
        Notification::assertNothingSent();
        Http::assertNothingSent();
    }

    public function test_seeded_customer_can_login_and_access_customer_pages_but_not_admin()
    {
        $this->seed(UsersSeeder::class);
        $user = User::where('email', 'user@phoneshop.com')->firstOrFail();
        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => '12345678'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->get(route('profile.edit'))->assertOk();
        $this->get(route('orders.index'))->assertOk();
        $this->get(route('admin.dashboard'))->assertRedirect('/')->assertSessionHas('error');
        Notification::assertNothingSent();
        Http::assertNothingSent();
    }

    public function test_repeated_seeding_does_not_duplicate_or_reset_an_existing_account()
    {
        $this->seed(UsersSeeder::class);
        $user = User::where('email', 'user@phoneshop.com')->firstOrFail();
        $user->forceFill([
            'name' => 'Existing Customer', 'password' => Hash::make('ChangedPassword2026!'),
            'email_verified_at' => null, 'phone' => '0901234567',
        ])->save();
        $attributes = $user->fresh()->getAttributes();
        config(['seeders.user.password' => 'DifferentPassword2026!']);

        $this->seed(UsersSeeder::class);

        $this->assertSame($attributes, $user->fresh()->getAttributes());
        $this->assertSame(1, User::where('email', $user->email)->count());
        Notification::assertNothingSent();
    }

    public function test_seeder_never_changes_an_existing_admin_with_the_configured_email()
    {
        config(['seeders.user.email' => $this->admin->email]);
        $attributes = $this->admin->fresh()->getAttributes();
        $count = User::count();

        $this->seed(UsersSeeder::class);

        $this->assertSame($attributes, $this->admin->fresh()->getAttributes());
        $this->assertSame('admin', $this->admin->fresh()->role);
        $this->assertSame($count, User::count());
    }

    public function test_private_email_and_password_are_supported_in_production()
    {
        $this->app['env'] = 'production';
        config(['seeders.user.email' => 'demo@tdh.test', 'seeders.user.password' => 'PrivatePassword2026!']);

        $this->artisan('db:seed', ['--class' => UsersSeeder::class, '--database' => 'sqlite', '--force' => true])->assertExitCode(0);

        $user = User::where('email', 'demo@tdh.test')->firstOrFail();
        $this->assertSame('user', $user->role);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertTrue(Hash::check('PrivatePassword2026!', $user->password));
        Notification::assertNothingSent();
        Http::assertNothingSent();
    }

    /** @dataProvider unsafeProductionPasswords */
    public function test_production_requires_a_non_default_password($password)
    {
        $this->app['env'] = 'production';
        config(['seeders.user.password' => $password]);

        try {
            $this->artisan('db:seed', ['--class' => UsersSeeder::class, '--database' => 'sqlite', '--force' => true])->run();
            $this->fail('An unsafe production password must not create an account.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('SEED_USER_PASSWORD', $exception->getMessage());
        }
        $this->assertDatabaseMissing('users', ['email' => 'user@phoneshop.com']);
    }

    public static function unsafeProductionPasswords(): array
    {
        return ['missing' => [null], 'demo default' => ['12345678'], 'too short' => ['short']];
    }

    public function test_invalid_seed_email_is_rejected_before_creating_a_user()
    {
        config(['seeders.user.email' => 'invalid-address']);
        $count = User::count();
        try {
            $this->seed(UsersSeeder::class);
            $this->fail('An invalid email address must not create an account.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('SEED_USER_EMAIL', $exception->getMessage());
        }
        $this->assertSame($count, User::count());
    }
}
