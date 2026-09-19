import { ChevronLeft, ChevronRight, Maximize2, X } from 'lucide-react'
import { useRef, useState } from 'react'
import type { UiCopy } from '@/content'
import type { ProjectImage } from '@/lib/api'
import { thumbnailSlots, wrapIndex } from '@/lib/gallery'

type Props = { ui: UiCopy; title: string; images: ProjectImage[] }

export function ProjectGallery({ ui, title, images }: Props) {
  const [selected, setSelected] = useState(0)
  const [viewerIndex, setViewerIndex] = useState(0)
  const dialogRef = useRef<HTMLDialogElement>(null)
  const triggerRef = useRef<HTMLElement | null>(null)
  const copy = ui.projects.gallery
  const { visible, overflow } = thumbnailSlots(images.length)
  const main = images[wrapIndex(selected, images.length)]
  const current = images[wrapIndex(viewerIndex, images.length)]

  const openViewer = (index: number, trigger: HTMLElement) => {
    triggerRef.current = trigger
    setViewerIndex(index)
    dialogRef.current?.showModal()
  }

  const step = (delta: number) => setViewerIndex((index) => wrapIndex(index + delta, images.length))

  return (
    <div>
      <div className="overflow-hidden rounded-2xl border border-line bg-surface">
        <img
          src={main.url}
          alt={main.alt}
          width={1600}
          height={1000}
          loading="lazy"
          decoding="async"
          className="aspect-[16/10] w-full object-cover"
        />
      </div>

      {visible > 0 && (
        // Four tiles on desktop; a horizontal carousel on narrow screens.
        <div className="mt-3 flex gap-2 overflow-x-auto pb-1 lg:grid lg:grid-cols-4 lg:overflow-visible lg:pb-0">
          {images.slice(0, visible).map((image, index) => (
            <button
              key={image.url}
              type="button"
              onClick={() => setSelected(index)}
              aria-current={index === selected ? 'true' : undefined}
              aria-label={copy.image(index + 1, images.length)}
              className={`aspect-[16/10] w-28 shrink-0 overflow-hidden rounded-lg border-2 lg:w-auto ${index === selected ? 'border-accent' : 'border-line'}`}
            >
              <img src={image.url} alt="" width={160} height={100} loading="lazy" decoding="async" className="h-full w-full object-cover" />
            </button>
          ))}

          {overflow > 0 && (
            <button
              type="button"
              onClick={(event) => openViewer(visible, event.currentTarget)}
              aria-label={`${copy.more(overflow)} · ${copy.viewFull}`}
              className="flex aspect-[16/10] w-28 shrink-0 items-center justify-center rounded-lg border-2 border-line font-mono text-sm lg:w-auto"
            >
              {copy.more(overflow)}
            </button>
          )}
        </div>
      )}

      <button
        type="button"
        onClick={(event) => openViewer(selected, event.currentTarget)}
        className="mt-2 inline-flex min-h-11 items-center gap-2 text-sm font-semibold"
      >
        <Maximize2 size={16} aria-hidden="true" />
        {copy.viewFull}
      </button>

      <dialog
        ref={dialogRef}
        aria-label={title}
        onClose={() => triggerRef.current?.focus()}
        onKeyDown={(event) => {
          if (event.key === 'ArrowLeft') step(-1)
          if (event.key === 'ArrowRight') step(1)
        }}
        className="m-auto max-h-[92vh] w-[min(92vw,1400px)] max-w-none overflow-hidden rounded-2xl bg-canvas p-0 text-ink backdrop:bg-scene-veil/80"
      >
        <div className="flex items-center justify-between gap-4 border-b border-line px-4 py-2">
          <p className="label text-muted" aria-live="polite">
            {copy.image(wrapIndex(viewerIndex, images.length) + 1, images.length)}
          </p>
          <button type="button" onClick={() => dialogRef.current?.close()} aria-label={copy.close} className="flex h-11 w-11 items-center justify-center rounded-full">
            <X size={18} aria-hidden="true" />
          </button>
        </div>

        <img src={current.url} alt={current.alt} loading="lazy" decoding="async" className="max-h-[75vh] w-full object-contain" />

        {images.length > 1 && (
          <div className="flex justify-between px-4 py-2">
            <button type="button" onClick={() => step(-1)} aria-label={copy.previous} className="flex h-11 w-11 items-center justify-center rounded-full border border-line">
              <ChevronLeft size={18} aria-hidden="true" />
            </button>
            <button type="button" onClick={() => step(1)} aria-label={copy.next} className="flex h-11 w-11 items-center justify-center rounded-full border border-line">
              <ChevronRight size={18} aria-hidden="true" />
            </button>
          </div>
        )}
      </dialog>
    </div>
  )
}
