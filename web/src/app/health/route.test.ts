import {describe, expect, it} from 'vitest';
import {GET} from './route';

describe('GET /health', () => {
  it('returns the frontend-owned health response', async () => {
    const response = GET();
    await expect(response.json()).resolves.toEqual({status: 'ok'});
    expect(response.status).toBe(200);
  });
});
