<?php

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('can render user resource list and view pages', function () {
    $admin = User::factory()->create([
        'name' => 'Admin User',
        'email' => 'admin@admin.com',
        'phone' => '01000000001',
        'role' => UserRole::Admin,
    ]);

    $teacher = User::factory()->create([
        'name' => 'Fatma Ahmed',
        'email' => 'fatma@example.com',
        'phone' => '01012345678',
        'role' => UserRole::Teacher,
    ]);

    $response = $this->actingAs($admin)->get(UserResource::getUrl('index'));
    $response->assertSuccessful();
    $response->assertSee('Fatma Ahmed');
    $response->assertSee('admin@admin.com');

    $viewResponse = $this->actingAs($admin)->get(UserResource::getUrl('view', ['record' => $teacher]));
    $viewResponse->assertSuccessful();
    $viewResponse->assertSee('Fatma Ahmed');
    $viewResponse->assertSee('01012345678');
});

test('can create a teacher user via resource form', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Aya Mansour',
            'email' => 'aya@example.com',
            'phone' => '01123456789',
            'role' => UserRole::Teacher->value,
            'password' => 'secret123',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'aya@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Aya Mansour')
        ->and($user->phone)->toBe('01123456789')
        ->and($user->role)->toBe(UserRole::Teacher)
        ->and(Hash::check('secret123', $user->password))->toBeTrue();
});

test('can update a teacher user without changing password if left empty', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $teacher = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
        'phone' => '01011112222',
        'role' => UserRole::Teacher,
        'password' => 'initial_password',
    ]);

    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $teacher->id])
        ->fillForm([
            'name' => 'Updated Name',
            'phone' => '01099998888',
            'password' => '',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $teacher->refresh();
    expect($teacher->name)->toBe('Updated Name')
        ->and($teacher->phone)->toBe('01099998888')
        ->and(Hash::check('initial_password', $teacher->password))->toBeTrue();
});
