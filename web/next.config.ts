import type {NextConfig} from 'next';
import createNextIntlPlugin from 'next-intl/plugin';

const nextConfig: NextConfig = {
  watchOptions: {
    pollIntervalMs: 1000,
  },
  // Task 13 media-topology gate: the Next image optimizer resolves a
  // root-relative `next/image` `src` (e.g. Laravel's `/storage/...` public
  // media reference, unchanged from the Phase 4 API contract) by issuing an
  // internal self-fetch against this Next server's own listener. That
  // self-fetch cannot reach Laravel on its own, so this narrow, server-only
  // rewrite forwards it to the API over the existing internal Docker network
  // (`INTERNAL_API_ORIGIN`, already used for the six public content
  // fetches). This runs entirely inside the Next server process:
  // - the public API contract keeps emitting a plain root-relative
  //   `/storage/...` string; nothing here changes that;
  // - the destination origin is server-side configuration only — never a
  //   `NEXT_PUBLIC_*` variable, never present in rendered HTML or the
  //   client bundle;
  // - it does not publish `api:80` to the host — `web` already reaches
  //   `api:80` over the internal `front` network for content requests;
  // - it does not touch `images.remotePatterns` — the `src` stays
  //   root-relative, so remotePatterns (which governs absolute external
  //   URLs) is not involved;
  // - it is a path-for-path proxy limited to `/storage/*`, the exact prefix
  //   Phase 4 already owns; it adds no aggregation, no new endpoint, and no
  //   domain-copying view model, so it is not a BFF/Route Handler.
  // Real browser requests never hit this rule: Caddy already routes
  // `/storage/*` straight to `api:80` without touching `web`.
  async rewrites() {
    const internalApiOrigin = process.env.INTERNAL_API_ORIGIN;

    if (!internalApiOrigin) {
      return [];
    }

    return [
      {
        source: '/storage/:path*',
        destination: `${internalApiOrigin}/storage/:path*`,
      },
    ];
  },
};

const withNextIntl = createNextIntlPlugin('./src/i18n/request.ts');

export default withNextIntl(nextConfig);
