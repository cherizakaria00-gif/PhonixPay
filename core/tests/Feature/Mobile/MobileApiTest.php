<?php

namespace Tests\Feature\Mobile;

use App\Constants\Status;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareDatabase();
    }

    public function test_login_and_me_returns_token_and_user_shape(): void
    {
        $user = $this->createUser();

        $login = $this->postJson('/api/auth/login', [
            'username' => $user->email,
            'password' => 'secret123',
        ]);

        $login->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'username', 'balance', 'currency', 'ev', 'sv', 'tv', 'status'],
            ]);

        $token = (string) $login->json('token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_authorization_status_endpoint_returns_email_verification_state(): void
    {
        $user = $this->createUser(['ev' => Status::UNVERIFIED]);
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/authorization/status')
            ->assertOk()
            ->assertJsonPath('type', 'email')
            ->assertJsonStructure([
                'type',
                'message',
                'retry_after',
                'user' => ['email', 'mobile', 'email_masked', 'mobile_masked', 'ev', 'sv', 'tv', 'status'],
            ]);
    }

    public function test_notification_poll_returns_expected_payload(): void
    {
        $user = $this->createUser();
        $token = $user->createToken('test')->plainTextToken;

        NotificationLog::query()->create([
            'user_id' => $user->id,
            'subject' => 'New update',
            'message' => 'Hello merchant',
            'user_read' => Status::NO,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/notifications/poll')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('unread_count', 1)
            ->assertJsonStructure([
                'status',
                'unread_count',
                'notifications' => [[
                    'id', 'subject', 'message', 'time', 'unread', 'url',
                ]],
            ]);
    }

    public function test_support_ticket_create_and_list(): void
    {
        $user = $this->createUser();
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/support/tickets', [
                'subject' => 'API issue',
                'priority' => 2,
                'message' => 'Cannot connect from mobile app.',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $list = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/support/tickets');

        $list->assertOk()
            ->assertJsonStructure([
                'data' => [[
                    'id', 'ticket', 'subject', 'priority', 'status', 'status_text', 'created_at', 'last_reply',
                ]],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $ticketId = (int) $list->json('data.0.id');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/support/tickets/' . $ticketId)
            ->assertOk()
            ->assertJsonStructure([
                'ticket' => ['id', 'ticket', 'subject', 'priority', 'status', 'status_text', 'created_at', 'last_reply'],
                'messages' => [['id', 'is_admin', 'admin_name', 'message', 'created_at']],
            ]);
    }

    private function prepareDatabase(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ([
            'user_logins',
            'personal_access_tokens',
            'support_messages',
            'support_tickets',
            'notification_logs',
            'general_settings',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('name')->nullable();
            $table->string('username')->nullable()->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->decimal('balance', 28, 8)->default(0);
            $table->string('mobile')->nullable();
            $table->string('dial_code')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('address')->nullable();
            $table->string('country')->nullable();
            $table->string('country_name')->nullable();
            $table->unsignedTinyInteger('ev')->default(1);
            $table->unsignedTinyInteger('sv')->default(1);
            $table->unsignedTinyInteger('tv')->default(1);
            $table->unsignedTinyInteger('ts')->default(0);
            $table->unsignedTinyInteger('status')->default(1);
            $table->string('ver_code')->nullable();
            $table->timestamp('ver_code_send_at')->nullable();
            $table->string('remember_token')->nullable();
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('general_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name')->default('FlujiPay');
            $table->string('cur_text')->default('USD');
            $table->string('cur_sym')->default('$');
            $table->unsignedTinyInteger('currency_format')->default(2);
            $table->unsignedTinyInteger('registration')->default(1);
            $table->unsignedTinyInteger('secure_password')->default(0);
            $table->unsignedTinyInteger('kv')->default(0);
            $table->unsignedTinyInteger('ev')->default(0);
            $table->unsignedTinyInteger('sv')->default(0);
            $table->string('api_prefix')->default('live');
            $table->string('api_test_prefix')->default('test');
            $table->timestamps();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('subject')->nullable();
            $table->text('message')->nullable();
            $table->unsignedTinyInteger('user_read')->default(0);
            $table->timestamps();
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->default(0);
            $table->string('ticket');
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('subject');
            $table->unsignedTinyInteger('priority')->default(1);
            $table->unsignedTinyInteger('status')->default(0);
            $table->timestamp('last_reply')->nullable();
            $table->timestamps();
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('support_ticket_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->longText('message');
            $table->timestamps();
        });

        Schema::create('user_logins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('user_ip')->nullable();
            $table->string('country')->nullable();
            $table->string('country_code')->nullable();
            $table->string('city')->nullable();
            $table->string('longitude')->nullable();
            $table->string('latitude')->nullable();
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->timestamps();
        });

        \DB::table('general_settings')->insert([
            'site_name' => 'FlujiPay',
            'cur_text' => 'USD',
            'cur_sym' => '$',
            'currency_format' => 2,
            'registration' => 1,
            'secure_password' => 0,
            'kv' => 0,
            'ev' => 0,
            'sv' => 0,
            'api_prefix' => 'live',
            'api_test_prefix' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUser(array $attributes = []): User
    {
        $defaults = [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'name' => 'John Doe',
            'username' => 'john_' . random_int(1000, 9999),
            'email' => 'john' . random_int(1000, 9999) . '@example.com',
            'password' => Hash::make('secret123'),
            'balance' => 120.50,
            'mobile' => '1234567890',
            'dial_code' => '+1',
            'ev' => Status::VERIFIED,
            'sv' => Status::VERIFIED,
            'tv' => Status::VERIFIED,
            'ts' => Status::DISABLE,
            'status' => Status::USER_ACTIVE,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return User::query()->create(array_merge($defaults, $attributes));
    }
}
