import {afterEach, describe, expect, it, vi} from 'vitest';
import {cleanup, fireEvent, render, screen} from '@testing-library/react';

const {refresh} = vi.hoisted(() => ({refresh: vi.fn()}));

vi.mock('next/navigation', () => ({
  useRouter: () => ({refresh}),
}));

import {RetryButton} from './retry-button';

describe('RetryButton', () => {
  afterEach(() => {
    cleanup();
    refresh.mockClear();
    vi.useRealTimers();
  });

  it('renders its localized label', () => {
    render(<RetryButton label="Retry" />);

    expect(screen.getByRole('button', {name: 'Retry'})).toBeInTheDocument();
  });

  it('refreshes the current route exactly once per activation', () => {
    render(<RetryButton label="Retry" />);

    fireEvent.click(screen.getByRole('button', {name: 'Retry'}));

    expect(refresh).toHaveBeenCalledTimes(1);
  });

  it('does not poll, auto-retry, or schedule timers', () => {
    vi.useFakeTimers();
    render(<RetryButton label="Retry" pendingLabel="Retrying…" />);

    fireEvent.click(screen.getByRole('button', {name: /Retry/}));
    vi.advanceTimersByTime(120_000);

    expect(refresh).toHaveBeenCalledTimes(1);
  });

  it('is not disabled before it is activated', () => {
    render(<RetryButton label="Retry" pendingLabel="Retrying…" />);

    expect(screen.getByRole('button', {name: 'Retry'})).not.toBeDisabled();
  });
});
