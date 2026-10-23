<?php

namespace App\Support;

use Closure;

/**
 * Refuses a console process that would create persistent media the web
 * runtime cannot use.
 *
 * `docker compose exec` runs as root by default. The local disk creates
 * private directories as 0700 and files as 0600 owned by the creating
 * process, so anything written by root is unreadable and unwritable for
 * Apache/PHP (www-data). The check is identity-based on purpose:
 * is_writable() is true for root and would not detect the problem.
 *
 * Every identity is resolved inside the container (the www-data account and
 * the owner of the media roots); nothing depends on a host UID.
 */
final class PersistentMediaWriterGuard
{
    public const RUNTIME_USER = 'www-data';

    private readonly Closure $effectiveUid;

    private readonly Closure $uidOfUser;

    private readonly Closure $ownerOf;

    /** @var list<string>|null */
    private readonly ?array $roots;

    /** @param list<string>|null $roots */
    public function __construct(?Closure $effectiveUid = null, ?Closure $uidOfUser = null, ?Closure $ownerOf = null, ?array $roots = null)
    {
        $this->effectiveUid = $effectiveUid ?? static fn (): int => posix_geteuid();
        $this->uidOfUser = $uidOfUser ?? static function (string $name): ?int {
            $entry = posix_getpwnam($name);

            return $entry === false ? null : (int) $entry['uid'];
        };
        $this->ownerOf = $ownerOf ?? static function (string $path): ?int {
            if (! is_dir($path)) {
                return null;
            }
            $owner = fileowner($path);

            return $owner === false ? null : $owner;
        };
        $this->roots = $roots;
    }

    public function assertRuntimeUser(string $command): void
    {
        $euid = ($this->effectiveUid)();
        $runtimeUid = ($this->uidOfUser)(self::RUNTIME_USER);

        $accepted = $euid !== 0 && $runtimeUid !== null && $euid === $runtimeUid;
        foreach ($this->roots() as $root) {
            $accepted = $accepted && ($this->ownerOf)($root) === $euid;
        }

        if (! $accepted) {
            throw new PersistentMediaWriterRejected(
                'This command writes persistent media and must be executed as the runtime user ('.self::RUNTIME_USER.'). '
                .'Run it with `docker compose exec -T -u '.self::RUNTIME_USER." api php artisan {$command}`."
            );
        }
    }

    /** @return list<string> */
    private function roots(): array
    {
        return $this->roots ?? [
            (string) config('filesystems.disks.local.root'),
            (string) config('filesystems.disks.public.root'),
        ];
    }
}
