<?php

use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use function Pest\Livewire\livewire;

beforeEach(function () {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);

    $this->admin = User::factory()->create([
        'password' => Hash::make('original-password'),
    ]);
    $this->admin->assignRole('admin');

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->admin);
});

test('updating a phone number does not replace the password or log out the admin', function () {
    // Prime AuthenticateSession with the current password hash, as a real
    // browser session would be before the edit request.
    $this->get('/admin')->assertSuccessful();

    $originalPasswordHash = $this->admin->password;

    livewire(EditUser::class, [
        'record' => $this->admin->getRouteKey(),
    ])
        ->assertSchemaStateSet([
            'password' => null,
        ])
        ->fillForm([
            'phone' => '+15551234567',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->admin->refresh();

    expect($this->admin->phone)->toBe('+15551234567')
        ->and($this->admin->password)->toBe($originalPasswordHash)
        ->and(auth()->id())->toBe($this->admin->getKey());

    $this->get('/admin')->assertSuccessful();
});

test('entering a new password still updates it securely', function () {
    $originalPasswordHash = $this->admin->password;

    livewire(EditUser::class, [
        'record' => $this->admin->getRouteKey(),
    ])
        ->fillForm([
            'password' => 'updated-password',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->admin->refresh();

    expect($this->admin->password)->not->toBe($originalPasswordHash)
        ->and(Hash::check('updated-password', $this->admin->password))->toBeTrue();
});
