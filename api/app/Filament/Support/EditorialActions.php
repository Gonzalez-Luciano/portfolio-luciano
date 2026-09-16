<?php

namespace App\Filament\Support;

use App\Domain\Content\Actions\HideContent;
use App\Domain\Content\Actions\PublishContent;
use App\Domain\Content\Actions\ReturnContentToDraft;
use App\Domain\Content\Actions\ShowContent;
use App\Domain\Publishing\PublicationValidationException;
use App\Enums\PublicationStatus;
use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

/**
 * Filament-UI glue for the four content transition buttons (Publish, Show,
 * Hide, Return to draft). Every method only calls the matching domain action
 * and translates its result/exception into a Filament notification; no
 * publication or transition rule is reimplemented here.
 */
final class EditorialActions
{
    /**
     * @param  Closure(): Model  $record
     * @param  (Closure(Model): void)|null  $afterSuccess
     */
    public static function publish(Closure $record, ?Closure $afterSuccess = null): Action
    {
        return Action::make('publish')
            ->label('Publish')
            ->color('success')
            ->visible(fn (): bool => $record()->status === PublicationStatus::Draft)
            ->action(function () use ($record, $afterSuccess): void {
                self::run('Publish', fn (): Model => app(PublishContent::class)($record()), $afterSuccess);
            });
    }

    /**
     * @param  Closure(): Model  $record
     * @param  (Closure(Model): void)|null  $afterSuccess
     */
    public static function show(Closure $record, ?Closure $afterSuccess = null): Action
    {
        return Action::make('show')
            ->label('Show')
            ->color('success')
            ->visible(fn (): bool => $record()->status === PublicationStatus::Published && ! $record()->is_visible)
            ->action(function () use ($record, $afterSuccess): void {
                self::run('Show', fn (): Model => app(ShowContent::class)($record()), $afterSuccess);
            });
    }

    /**
     * @param  Closure(): Model  $record
     * @param  (Closure(Model): void)|null  $afterSuccess
     */
    public static function hide(Closure $record, ?Closure $afterSuccess = null): Action
    {
        return Action::make('hide')
            ->label('Hide')
            ->color('warning')
            ->visible(fn (): bool => $record()->status === PublicationStatus::Published && $record()->is_visible)
            ->action(function () use ($record, $afterSuccess): void {
                self::run('Hide', fn (): Model => app(HideContent::class)($record()), $afterSuccess);
            });
    }

    /**
     * @param  Closure(): Model  $record
     * @param  (Closure(Model): void)|null  $afterSuccess
     */
    public static function returnToDraft(Closure $record, ?Closure $afterSuccess = null): Action
    {
        return Action::make('return_to_draft')
            ->label('Return to draft')
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('This removes the record from the portfolio and returns it to draft.')
            ->visible(fn (): bool => $record()->status === PublicationStatus::Published)
            ->action(function () use ($record, $afterSuccess): void {
                self::run('Return to draft', fn (): Model => app(ReturnContentToDraft::class)($record()), $afterSuccess);
            });
    }

    /**
     * @param  Closure(): Model  $operation
     * @param  (Closure(Model): void)|null  $afterSuccess
     */
    private static function run(string $label, Closure $operation, ?Closure $afterSuccess): void
    {
        try {
            $updated = $operation();
        } catch (PublicationValidationException $exception) {
            self::notifyIssues($label, $exception);

            return;
        } catch (\Throwable $exception) {
            Notification::make()
                ->danger()
                ->title("{$label} failed")
                ->body($exception->getMessage())
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title("{$label} succeeded")
            ->send();

        if ($afterSuccess !== null) {
            $afterSuccess($updated);
        }
    }

    private static function notifyIssues(string $label, PublicationValidationException $exception): void
    {
        Notification::make()
            ->danger()
            ->title("{$label} failed")
            ->body(self::formatIssues($exception))
            ->send();
    }

    private static function formatIssues(PublicationValidationException $exception): string
    {
        return collect($exception->issues())
            ->map(fn ($issue): string => "[{$issue->code}] {$issue->path}: {$issue->message}")
            ->implode("\n");
    }

    /**
     * Shared UI-glue for owned-asset actions (Replace/Remove) invoked
     * outside the four transition buttons above. Every owned-asset call
     * site in the admin (EditProfile, EditProject, EditTechnology,
     * EditCvDocument) catches its `ReplaceOwnedAsset`/`RemoveOwnedAsset`
     * call and routes the failure through this single method instead of
     * reimplementing the issue-list/sanitized-message distinction that
     * `run()` above already establishes for the four transition actions.
     */
    public static function notifyAssetFailure(string $label, \Throwable $exception): void
    {
        if ($exception instanceof PublicationValidationException) {
            self::notifyIssues($label, $exception);

            return;
        }

        Notification::make()
            ->danger()
            ->title("{$label} failed")
            ->body($exception->getMessage())
            ->send();
    }

    /**
     * Same failure-formatting as `notifyAssetFailure()`, but for a call site
     * where the record's own fields have already been committed and only a
     * later, separate asset-owning step (a project's screenshot gallery)
     * failed. The panel runs without a database transaction, so that field
     * commit is real and permanent; the notification makes that partial
     * success explicit instead of implying nothing was saved.
     */
    public static function notifyPartialAssetFailure(string $label, \Throwable $exception): void
    {
        $detail = $exception instanceof PublicationValidationException
            ? self::formatIssues($exception)
            : $exception->getMessage();

        Notification::make()
            ->danger()
            ->title("{$label} partially failed")
            ->body("The other changes were saved. The screenshots were not:\n{$detail}")
            ->send();
    }
}
