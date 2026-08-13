'use client';

import {useState} from 'react';
import {requestApi} from '@/lib/api/client';

type VersionData = {
  status: 'ok';
  version: 'v1';
};

type ApiStatusState = 'idle' | 'checking' | 'available' | 'unavailable';

export function ApiStatus() {
  const [state, setState] = useState<ApiStatusState>('idle');
  const [version, setVersion] = useState<string | null>(null);

  async function checkApi() {
    setState('checking');

    try {
      const response = await requestApi<VersionData>('/v1');
      setVersion(response.data.version);
      setState('available');
    } catch {
      setVersion(null);
      setState('unavailable');
    }
  }

  return (
    <section aria-label="API status">
      <button type="button" disabled={state === 'checking'} onClick={checkApi}>
        Check API
      </button>
      <p aria-live="polite" role="status">
        {state === 'checking' && 'Checking API...'}
        {state === 'available' && `ok/${version}`}
        {state === 'unavailable' && 'API unavailable.'}
      </p>
    </section>
  );
}
