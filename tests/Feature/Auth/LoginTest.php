<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Hash;

test('guests can view the login page', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee(__('common.login_title'));
});

test('the login page is not reachable once signed in', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('login'))
        ->assertRedirect(route('dashboard'));
});

test('users can authenticate with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'ahmed@xtreme.local',
        'password' => Hash::make('correct-horse-battery'),
    ]);

    $this->post(route('login'), [
        'email' => 'ahmed@xtreme.local',
        'password' => 'correct-horse-battery',
    ])->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($user->id);
});

test('authentication fails with a wrong password', function () {
    User::factory()->create([
        'email' => 'ahmed@xtreme.local',
        'password' => Hash::make('correct-horse-battery'),
    ]);

    $this->from(route('login'))
        ->post(route('login'), [
            'email' => 'ahmed@xtreme.local',
            'password' => 'wrong-password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

test('email and password are required', function () {
    $this->from(route('login'))
        ->post(route('login'), [])
        ->assertSessionHasErrors(['email', 'password']);
});

test('too many attempts lock the form even for the right password', function () {
    User::factory()->create([
        'email' => 'ahmed@xtreme.local',
        'password' => Hash::make('correct-horse-battery'),
    ]);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login'), [
            'email' => 'ahmed@xtreme.local',
            'password' => 'wrong-password',
        ]);
    }

    // The sixth attempt is rejected even though the password is correct.
    $this->from(route('login'))
        ->post(route('login'), [
            'email' => 'ahmed@xtreme.local',
            'password' => 'correct-horse-battery',
        ])
        ->assertSessionHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

test('users can sign out', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    expect(auth()->check())->toBeFalse();
});

test('guests are sent to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('the root url leads to the dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect('/dashboard');
});

test('self-registration is not available', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();
});

test('the seeded administrator can sign in', function () {
    $this->seed(AdminUserSeeder::class);

    $this->post(route('login'), [
        'email' => AdminUserSeeder::EMAIL,
        'password' => AdminUserSeeder::PASSWORD,
    ])->assertRedirect(route('dashboard'));

    expect(auth()->user()->email)->toBe(AdminUserSeeder::EMAIL);
});
