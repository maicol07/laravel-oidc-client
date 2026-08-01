<?php

namespace Maicol07\OIDCClient\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Schema;
use Maicol07\OIDCClient\Auth\OIDCUserProvider;
use Maicol07\OIDCClient\Models\OidcAuthMapping;
use Maicol07\OIDCClient\Models\Traits\LogsInWithOidc;
use Maicol07\OIDCClient\OIDCServiceProvider;
use Maicol07\OpenIDConnect\UserInfo;
use Orchestra\Testbench\TestCase;

final class OidcAccountTakeoverTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [OIDCServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('oidc.link_by_verified_email', false);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', static function (Blueprint $t): void {
            $t->id();
            $t->string('email')->unique();
            $t->timestamp('email_verified_at')->nullable();
            $t->string('password')->nullable();
            $t->string('first_name')->nullable();
            $t->string('last_name')->nullable();
            $t->timestamps();
        });

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    private function userInfo(array $claims): UserInfo
    {
        return new UserInfo(collect(array_merge([
            'sub' => 'sub-default',
            'email' => null,
            'email_verified' => false,
            'id_token' => 'dummy.jwt.token',
            'given_name' => 'Test',
            'family_name' => 'User',
        ], $claims)));
    }

    public function test_unverified_email_does_not_take_over_existing_account(): void
    {
        $victim = User::create([
            'email' => 'victim@example.com',
            'password' => bcrypt('victim-local-password'),
        ]);

        $resolved = null;
        try {
            $resolved = (new OIDCUserProvider)->retrieveByInfo('https://idp.example', $this->userInfo([
                'sub' => 'attacker-sub-999',
                'email' => 'victim@example.com',
                'email_verified' => false,
            ]));
        } catch (UniqueConstraintViolationException) {
            // Acceptable post-fix outcome: with a unique email column the login
            // is rejected rather than silently adopting the victim's account.
        }

        $this->assertNotSame(
            $victim->id,
            $resolved?->id,
            'ACCOUNT TAKEOVER: OIDC login was matched to an existing account via an unverified email claim.'
        );
    }

    public function test_returning_user_resolves_by_issuer_sub(): void
    {
        $alice = User::create(['email' => 'alice@corp.example']);

        $mapping = OidcAuthMapping::firstOrNew(['sub' => 'alice-sub', 'issuer' => 'https://idp.example']);
        $mapping->user()->associate($alice);
        $mapping->save();

        $resolved = (new OIDCUserProvider)->retrieveByInfo('https://idp.example', $this->userInfo([
            'sub' => 'alice-sub',
            'email' => 'alice.renamed@corp.example',
            'email_verified' => true,
        ]));

        $this->assertSame($alice->id, $resolved->id, 'Returning (issuer, sub) must map to the original account.');
    }

    public function test_link_by_verified_email_links_when_enabled_and_verified(): void
    {
        config(['oidc.link_by_verified_email' => true]);

        $existing = User::create(['email' => 'user@example.com']);

        $resolved = (new OIDCUserProvider)->retrieveByInfo('https://idp.example', $this->userInfo([
            'sub' => 'new-sub-123',
            'email' => 'user@example.com',
            'email_verified' => true,
        ]));

        $this->assertSame($existing->id, $resolved->id, 'Should link to existing user when link_by_verified_email is true and email_verified is true.');
    }

    public function test_link_by_verified_email_does_not_link_when_enabled_but_unverified(): void
    {
        config(['oidc.link_by_verified_email' => true]);

        $existing = User::create(['email' => 'unverified-victim@example.com']);

        $resolved = null;
        try {
            $resolved = (new OIDCUserProvider)->retrieveByInfo('https://idp.example', $this->userInfo([
                'sub' => 'attacker-sub-456',
                'email' => 'unverified-victim@example.com',
                'email_verified' => false,
            ]));
        } catch (UniqueConstraintViolationException) {
        }

        $this->assertNotSame(
            $existing->id,
            $resolved?->id,
            'Should NOT link to existing user when email is unverified even if link_by_verified_email is true.'
        );
    }
}

class User extends Authenticatable
{
    use LogsInWithOidc;

    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = true;

    public function mapOIDCUserinfo(string $issuer, UserInfo $user_info, OidcAuthMapping $mapping): void
    {
        $this->fill([
            'first_name' => $user_info->given_name,
            'last_name' => $user_info->family_name,
        ]);
    }
}
