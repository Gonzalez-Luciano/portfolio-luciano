import {afterEach, describe, expect, it, vi} from 'vitest';
import {act, cleanup, render} from '@testing-library/react';
import {FragmentFocusManager} from './fragment-focus-manager';

function stubTarget(focus = vi.fn()) {
  vi.spyOn(document, 'getElementById').mockReturnValue({
    focus,
  } as unknown as HTMLElement);

  return focus;
}

describe('FragmentFocusManager', () => {
  afterEach(() => {
    cleanup();
    window.location.hash = '';
    vi.restoreAllMocks();
  });

  it('renders no visible UI', () => {
    const {container} = render(<FragmentFocusManager />);

    expect(container).toBeEmptyDOMElement();
  });

  it('focuses an allowlisted existing target on initial mount, without scrolling', () => {
    window.location.hash = '#work';
    const focus = stubTarget();

    render(<FragmentFocusManager />);

    expect(focus).toHaveBeenCalledWith({preventScroll: true});
    expect(focus).toHaveBeenCalledTimes(1);
  });

  it('focuses an allowlisted existing target on hashchange', () => {
    window.location.hash = '';
    const focus = stubTarget();

    render(<FragmentFocusManager />);
    expect(focus).not.toHaveBeenCalled();

    act(() => {
      window.location.hash = '#technologies';
      window.dispatchEvent(new Event('hashchange'));
    });

    expect(focus).toHaveBeenCalledWith({preventScroll: true});
  });

  it('does nothing for #top', () => {
    window.location.hash = '#top';
    const focus = stubTarget();

    render(<FragmentFocusManager />);

    expect(focus).not.toHaveBeenCalled();
  });

  it('does nothing for an unknown hash', () => {
    window.location.hash = '#not-a-section';
    const focus = stubTarget();

    render(<FragmentFocusManager />);

    expect(focus).not.toHaveBeenCalled();
  });

  it('does nothing when the allowlisted target is missing', () => {
    window.location.hash = '#experience';
    const getElementById = vi
      .spyOn(document, 'getElementById')
      .mockReturnValue(null);

    expect(() => render(<FragmentFocusManager />)).not.toThrow();
    expect(getElementById).toHaveBeenCalledWith('experience');
  });

  it('never invokes a scroll API', () => {
    const scrollTo = vi.fn();
    vi.stubGlobal('scrollTo', scrollTo);
    const scrollIntoView = vi.fn();
    window.location.hash = '#about';
    vi.spyOn(document, 'getElementById').mockReturnValue({
      focus: vi.fn(),
      scrollIntoView,
    } as unknown as HTMLElement);

    render(<FragmentFocusManager />);

    expect(scrollTo).not.toHaveBeenCalled();
    expect(scrollIntoView).not.toHaveBeenCalled();
    vi.unstubAllGlobals();
  });
});
