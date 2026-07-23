<?php

use App\Jobs\ProvisionRepository;
use App\Models\Repository;
use App\Models\RepositoryMember;
use App\Models\User;
use App\Services\GitService;
use App\Services\RepositoryAuthorizationService;
use App\Services\SshKeyService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Role;

function makeRepository(User $owner, array $attributes = []): Repository
{
    return Repository::query()->create([
        'user_id' => $owner->id,
        'name' => 'Secure repository',
        'slug' => 'secure-repository',
        'description' => null,
        'visibility' => 'private',
        'storage_uuid' => (string) Str::uuid(),
        'state' => 'ready',
        'default_branch' => 'main',
        ...$attributes,
    ]);
}

test('repository authorization is deny by default and follows the role matrix', function () {
    $owner = User::factory()->create();
    $reader = User::factory()->create();
    $writer = User::factory()->create();
    $outsider = User::factory()->create();
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(Role::findOrCreate('super-admin', 'web'));
    $repository = makeRepository($owner);

    RepositoryMember::query()->create([
        'repository_id' => $repository->id,
        'user_id' => $reader->id,
        'role' => 'read',
    ]);
    RepositoryMember::query()->create([
        'repository_id' => $repository->id,
        'user_id' => $writer->id,
        'role' => 'write',
    ]);

    $authorization = app(RepositoryAuthorizationService::class);

    expect($authorization->capabilities(null, $repository))
        ->toMatchArray(['read' => false, 'write' => false, 'admin' => false, 'delete' => false])
        ->and($authorization->capabilities($reader, $repository))
        ->toMatchArray(['read' => true, 'write' => false, 'admin' => false, 'delete' => false])
        ->and($authorization->capabilities($writer, $repository))
        ->toMatchArray(['read' => true, 'write' => true, 'admin' => false, 'delete' => false])
        ->and($authorization->capabilities($owner, $repository))
        ->toMatchArray(['read' => true, 'write' => true, 'admin' => true, 'delete' => true])
        ->and($authorization->capabilities($superAdmin, $repository))
        ->toMatchArray(['read' => true, 'write' => true, 'admin' => true, 'delete' => true])
        ->and($authorization->allows($outsider, $repository, 'unknown-operation'))->toBeFalse();
});

test('repository API never exposes internal storage paths', function () {
    $owner = User::factory()->create();
    $repository = makeRepository($owner);

    $this->actingAs($owner)
        ->getJson("/api/v1/repositories/{$repository->id}")
        ->assertOk()
        ->assertJsonMissingPath('repository.storage_uuid')
        ->assertJsonMissingPath('repository.disk_path')
        ->assertJsonPath('repository.namespace', $owner->username)
        ->assertJsonPath('repository.capabilities.admin', true);
});

test('repository provisioning is queued with an opaque storage identifier', function () {
    Queue::fake();
    $owner = User::factory()->create();

    $response = $this->actingAs($owner)->postJson('/api/v1/repositories', [
        'name' => 'Queued repository',
        'slug' => 'queued-repository',
        'visibility' => 'private',
        'default_branch' => 'main',
    ]);

    $response
        ->assertAccepted()
        ->assertJsonPath('repository.state', 'provisioning')
        ->assertJsonMissingPath('repository.storage_uuid')
        ->assertJsonMissingPath('repository.disk_path');

    Queue::assertPushed(ProvisionRepository::class);
});

test('git storage rejects traversal and non uuid identifiers', function () {
    $root = storage_path('framework/testing/codeforge-repositories');
    $service = new GitService($root);

    expect(fn () => $service->repositoryPath('../outside'))->toThrow(RuntimeException::class)
        ->and(fn () => $service->repositoryPath('00000000-0000-0000-0000-000000000000'))
        ->toThrow(RuntimeException::class);
});

test('ssh keys are fingerprinted and private key material is rejected', function () {
    $service = app(SshKeyService::class);
    $encoded = base64_encode(str_repeat('a', 32));
    $metadata = $service->inspectPublicKey("ssh-ed25519 {$encoded} workstation");

    expect($metadata['algorithm'])->toBe('ssh-ed25519')
        ->and($metadata['fingerprint'])->toStartWith('SHA256:')
        ->and(fn () => $service->inspectPublicKey('-----BEGIN OPENSSH PRIVATE KEY-----'))
        ->toThrow(RuntimeException::class);
});

test('personal access tokens are hashed, scoped and shown only at creation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/tokens', [
        'name' => 'Git client',
        'abilities' => ['repo:write'],
        'expires_at' => now()->addMonth()->toIso8601String(),
    ]);

    $plainTextToken = $response->json('token');

    $response
        ->assertCreated()
        ->assertJsonPath('metadata.abilities', ['repo:write', 'repo:read']);

    expect($plainTextToken)->toBeString()
        ->and(PersonalAccessToken::query()->firstOrFail()->token)->not->toBe($plainTextToken);

    $this->actingAs($user)
        ->getJson('/api/v1/tokens')
        ->assertOk()
        ->assertJsonMissing(['token' => $plainTextToken]);
});
