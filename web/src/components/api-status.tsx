'use client';

import {useState} from 'react';
import {useTranslations} from 'next-intl';
import {requestApi} from '@/lib/api/client';

type VersionData = {
  status: 'ok';
  version: 'v1';
};

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function isVersionData(value: unknown): value is VersionData {
  return isRecord(value) && value.status === 'ok' && value.version === 'v1';
}

type ApiStatusState = 'idle' | 'checking' | 'available' | 'unavailable';

export function ApiStatus() {
  const t = useTranslations('Foundation');
  const [state, setState] = useState<ApiStatusState>('idle');
  const [version, setVersion] = useState<string | null>(null);

  async function checkApi() {
    setState('checking');

    try {
      const response = await requestApi<VersionData>('/v1', {
        validateData: isVersionData,
      });
      setVersion(response.data.version);
      setState('available');
    } catch {
      setVersion(null);
      setState('unavailable');
    }
  }

  return (
    <section aria-label={t('apiStatusLabel')}>
      <button type="button" disabled={state === 'checking'} onClick={checkApi}>
        {t('checkApi')}
      </button>
      <p aria-live="polite" role="status">
        {state === 'checking' && t('checkingApi')}
        {state === 'available' && t('apiAvailable', {version: version ?? ''})}
        {state === 'unavailable' && t('apiUnavailable')}
      </p>
    </section>
  );
}
