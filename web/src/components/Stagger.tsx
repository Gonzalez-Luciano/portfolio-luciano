import { useEffect, useState, type ReactNode } from 'react'

const ENTRANCE_EASE = 'cubic-bezier(0.16,1,0.3,1)'

/** Child entrance of a beat once its opacity passes STAGGER_THRESHOLD (reduced motion removes the transition in CSS). */
export function Stagger({ visible, delay, className, children }: { visible: boolean; delay: number; className?: string; children: ReactNode }) {
  const [ready, setReady] = useState(false)

  useEffect(() => {
    let second = 0
    const first = requestAnimationFrame(() => {
      second = requestAnimationFrame(() => setReady(true))
    })
    return () => {
      cancelAnimationFrame(first)
      cancelAnimationFrame(second)
    }
  }, [])

  const shown = ready && visible

  return (
    <div
      className={className}
      style={{
        opacity: shown ? 1 : 0,
        transform: shown ? 'translateY(0)' : 'translateY(24px)',
        transition: `opacity 0.8s ${ENTRANCE_EASE} ${delay}ms, transform 0.8s ${ENTRANCE_EASE} ${delay}ms`,
      }}
    >
      {children}
    </div>
  )
}
