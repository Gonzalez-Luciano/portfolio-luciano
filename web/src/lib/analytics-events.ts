export const ANALYTICS_EVENTS = {
  cv: 'cv-download',
  github: 'github-click',
  linkedin: 'linkedin-click',
  email: 'email-click',
} as const

export type AnalyticsEventName = (typeof ANALYTICS_EVENTS)[keyof typeof ANALYTICS_EVENTS]
