import createMiddleware from 'next-intl/middleware';
import {NextRequest, NextResponse} from 'next/server';
import {resolveLocale} from './i18n/resolve-locale';
import {localeCookieName, routing} from './i18n/routing';

const handleLocalizedRequest = createMiddleware({
  ...routing,
  localeCookie: false,
});

export function proxy(request: NextRequest): NextResponse {
  if (request.nextUrl.pathname === '/') {
    const locale = resolveLocale(
      request.cookies.get(localeCookieName)?.value,
      request.headers.get('accept-language'),
    );
    const redirectUrl = request.nextUrl.clone();
    redirectUrl.pathname = `/${locale}`;

    return NextResponse.redirect(redirectUrl);
  }

  return handleLocalizedRequest(request);
}

export const config = {
  matcher: ['/((?!api|admin|up|health|_next|.*\\..*).*)'],
};
