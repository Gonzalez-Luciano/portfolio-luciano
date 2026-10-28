<?php

namespace Tests\Unit\Support;

use App\Support\PersistentMediaWriterGuard;
use App\Support\PersistentMediaWriterRejected;
use PHPUnit\Framework\TestCase;

final class PersistentMediaWriterGuardTest extends TestCase
{
    private const ROOTS = ['/media/private', '/media/public'];

    private const MESSAGE = 'This command writes persistent media and must be executed as the runtime user (www-data). Run it with `docker compose exec -T -u www-data api php artisan portfolio:example`.';

    public function test_the_runtime_user_owning_both_media_roots_is_accepted(): void
    {
        $this->guard(euid: 33, owners: ['/media/private' => 33, '/media/public' => 33])->assertRuntimeUser('portfolio:example');

        $this->addToAssertionCount(1);
    }

    public function test_root_is_rejected_even_though_it_could_write(): void
    {
        $this->assertRejected($this->guard(euid: 0, owners: ['/media/private' => 0, '/media/public' => 0]));
    }

    public function test_a_non_root_user_other_than_www_data_is_rejected(): void
    {
        $this->assertRejected($this->guard(euid: 1000, owners: ['/media/private' => 1000, '/media/public' => 1000]));
    }

    public function test_www_data_is_rejected_when_a_media_root_is_owned_by_root(): void
    {
        $this->assertRejected($this->guard(euid: 33, owners: ['/media/private' => 0, '/media/public' => 33]));
    }

    public function test_a_missing_media_root_is_rejected(): void
    {
        $this->assertRejected($this->guard(euid: 33, owners: ['/media/private' => 33]));
    }

    public function test_a_container_without_the_runtime_account_is_rejected(): void
    {
        $guard = new PersistentMediaWriterGuard(
            effectiveUid: static fn (): int => 33,
            uidOfUser: static fn (string $name): ?int => null,
            ownerOf: static fn (string $path): ?int => 33,
            roots: self::ROOTS,
        );

        $this->assertRejected($guard);
    }

    /** @param array<string, int> $owners */
    private function guard(int $euid, array $owners): PersistentMediaWriterGuard
    {
        return new PersistentMediaWriterGuard(
            effectiveUid: static fn (): int => $euid,
            uidOfUser: static fn (string $name): ?int => $name === 'www-data' ? 33 : null,
            ownerOf: static fn (string $path): ?int => $owners[$path] ?? null,
            roots: self::ROOTS,
        );
    }

    private function assertRejected(PersistentMediaWriterGuard $guard): void
    {
        try {
            $guard->assertRuntimeUser('portfolio:example');
            $this->fail('The guard must reject this process.');
        } catch (PersistentMediaWriterRejected $exception) {
            $this->assertSame(self::MESSAGE, $exception->getMessage());
        }
    }
}
