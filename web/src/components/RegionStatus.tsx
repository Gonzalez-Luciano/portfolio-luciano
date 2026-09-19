import type { UiCopy } from '@/content'

export function RegionStatus({ ui, status, onRetry }: { ui: UiCopy; status: 'loading' | 'error'; onRetry: () => void }) {
  if (status === 'loading') {
    return (
      <p role="status" className="label text-muted">
        {ui.loading}
      </p>
    )
  }

  return (
    <div role="alert" className="flex flex-col items-start gap-4 rounded-2xl border border-line bg-surface p-6">
      <p className="text-muted">{ui.regionFailure}</p>
      <button type="button" onClick={onRetry} className="min-h-11 rounded-full border border-line px-5 text-sm font-semibold">
        {ui.retry}
      </button>
    </div>
  )
}
