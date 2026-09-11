import {afterEach, describe, expect, it, vi} from 'vitest';
import {cleanup, render, screen} from '@testing-library/react';

vi.mock('next/navigation', () => ({
  useRouter: () => ({refresh: vi.fn()}),
}));

import {
  NeutralEmptyState,
  RegionalFailure,
  StructuralFailure,
} from './content-state';

const retry = {label: 'Retry', pendingLabel: 'Retrying…'} as const;

function assertNoLiveRegion(container: HTMLElement) {
  expect(container.querySelector('[role="alert"]')).toBeNull();
  expect(container.querySelector('[role="status"]')).toBeNull();
  expect(container.querySelector('[aria-live]')).toBeNull();
}

describe('StructuralFailure', () => {
  afterEach(cleanup);

  it('renders the exact structural-failure copy inside a semantic region with Retry', () => {
    const {container} = render(
      <StructuralFailure
        message="We couldn't load the portfolio content."
        retry={retry}
      />,
    );

    expect(
      screen.getByText("We couldn't load the portfolio content."),
    ).toBeInTheDocument();
    expect(container.querySelector('section')).not.toBeNull();
    expect(
      screen.getByRole('heading', {
        name: "We couldn't load the portfolio content.",
      }),
    ).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'Retry'})).toBeInTheDocument();
    assertNoLiveRegion(container);
  });
});

describe('RegionalFailure', () => {
  afterEach(cleanup);

  it('renders the exact regional-failure copy and Retry with no heading of its own', () => {
    const {container} = render(
      <RegionalFailure
        message="We couldn't load this section."
        retry={retry}
      />,
    );

    expect(
      screen.getByText("We couldn't load this section."),
    ).toBeInTheDocument();
    expect(container.querySelector('section')).not.toBeNull();
    expect(screen.getByRole('button', {name: 'Retry'})).toBeInTheDocument();
    assertNoLiveRegion(container);

    // The host section always supplies the granular anchor + <h3> heading
    // (spec §15.2 / §20). RegionalFailure must not emit a competing heading.
    expect(container.querySelector('h1,h2,h3,h4,h5,h6')).toBeNull();
    expect(container.querySelector('.content-state__message')).not.toBeNull();
  });
});

describe('NeutralEmptyState', () => {
  afterEach(cleanup);

  it('renders the exact neutral copy with no Retry control and no live region', () => {
    const {container} = render(
      <NeutralEmptyState message="Content is not available at the moment." />,
    );

    expect(
      screen.getByText('Content is not available at the moment.'),
    ).toBeInTheDocument();
    expect(screen.queryByRole('button')).toBeNull();
    assertNoLiveRegion(container);
  });
});
