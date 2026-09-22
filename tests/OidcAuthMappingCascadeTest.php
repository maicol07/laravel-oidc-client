<?php

namespace Maicol07\OIDCClient\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Maicol07\OIDCClient\Auth\OIDCUserProvider;
use Maicol07\OIDCClient\Models\OidcAuthMapping;
use Maicol07\OIDCClient\OIDCServiceProvider;
use Maicol07\OpenIDConnect\UserInfo;
use Orchestra\Testbench\TestCase;

final class OidcAuthMappingCascadeTest extends TestCase
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
            'foreign_key_constraints' => true,
        ]);
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

    public function test_removing_a_user_removes_its_auth_mappings(): void
    {
        $user = User::create(['email' => 'leaver@corp.example']);

        $mapping = OidcAuthMapping::firstOrNew(['sub' => 'leaver-sub', 'issuer' => 'https://idp.example']);
        $mapping->user()->associate($user);
        $mapping->save();

        $user->delete();

        $this->assertSame(
            0,
            OidcAuthMapping::query()->count(),
            'A removed user must not leave its auth mappings behind.'
        );
    }

    public function test_a_returning_person_can_sign_up_again_after_deleting_their_account(): void
    {
        // The pair the identity provider will keep sending for this person.
        $issuer = 'https://idp.example';
        $sub = 'returning-sub';

        $first = (new OIDCUserProvider)->retrieveByInfo($issuer, $this->userInfo($sub, 'comeback@corp.example'));
        $first->delete();

        $second = (new OIDCUserProvider)->retrieveByInfo($issuer, $this->userInfo($sub, 'comeback@corp.example'));

        $this->assertNotNull($second);
        $this->assertNotSame($first->id, $second->id);
    }

    private function userInfo(string $sub, string $email): UserInfo
    {
        return new UserInfo(collect([
            'sub' => $sub,
            'email' => $email,
            'email_verified' => true,
            'id_token' => 'dummy.jwt.token',
            'given_name' => 'Test',
            'family_name' => 'User',
        ]));
    }
}
