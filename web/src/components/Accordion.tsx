import { Minus, Plus } from 'lucide-react'
import { useId, useState, type ReactNode } from 'react'

/** Independent disclosure: several can be open at once. */
export function Accordion({ title, children }: { title: string; children: ReactNode }) {
  const [open, setOpen] = useState(false)
  const panelId = useId()

  return (
    <div className="border-t border-line last:border-b">
      <h4>
        <button
          type="button"
          aria-expanded={open}
          aria-controls={panelId}
          onClick={() => setOpen((value) => !value)}
          className="flex min-h-11 w-full items-center justify-between gap-6 py-4 text-left font-semibold aria-expanded:text-accent"
        >
          <span>{title}</span>
          {open ? <Minus size={18} aria-hidden="true" className="shrink-0 text-accent" /> : <Plus size={18} aria-hidden="true" className="shrink-0 text-accent" />}
        </button>
      </h4>
      <div id={panelId} hidden={!open} className="pb-8">
        {children}
      </div>
    </div>
  )
}
