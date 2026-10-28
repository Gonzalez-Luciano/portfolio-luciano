#!/usr/bin/env bash
# Functional smoke check for the portfolio production runtime.
#
# Verifies the HTTP contract the portfolio gateway is expected to serve. It is
# read-only: it never writes content, never mutates the database and never
# needs a credential.
#
# Usage:
#   infra/validation/smoke-production.sh [base-url]
#
# Default base URL is http://127.0.0.1:8000, the loopback entrypoint declared
# in compose.production.yaml. Operations runs the same script against the
# deployed origin after a deployment.
set -uo pipefail

BASE="${1:-http://127.0.0.1:8000}"
PASS=0
FAIL=0

red() { printf '\033[31m%s\033[0m\n' "$1"; }
green() { printf '\033[32m%s\033[0m\n' "$1"; }

# status <name> <path> <expected-code> [curl-args...]
status() {
    local name="$1" path="$2" expected="$3"
    shift 3
    local actual
    actual="$(curl -s -o /dev/null -w '%{http_code}' "$@" "${BASE}${path}")"
    if [ "$actual" = "$expected" ]; then
        green "PASS  ${name} (${path} -> ${actual})"
        PASS=$((PASS + 1))
    else
        red "FAIL  ${name} (${path} -> ${actual}, expected ${expected})"
        FAIL=$((FAIL + 1))
    fi
}

# status_any <name> <path> <acceptable-codes...>
# For endpoints whose code legitimately depends on whether content has been
# published yet.
status_any() {
    local name="$1" path="$2"
    shift 2
    local actual code
    actual="$(curl -s -o /dev/null -w '%{http_code}' "${BASE}${path}")"
    for code in "$@"; do
        if [ "$actual" = "$code" ]; then
            green "PASS  ${name} (${path} -> ${actual})"
            PASS=$((PASS + 1))
            return
        fi
    done
    red "FAIL  ${name} (${path} -> ${actual}, expected one of: $*)"
    FAIL=$((FAIL + 1))
}

# Substring matching is done with bash pattern matching rather than a pipe into
# `grep -q`: grep exits on its first match, which closes the pipe and makes the
# writing side die of SIGPIPE before the shell reads the exit status.
contains() { [[ "$1" == *"$2"* ]]; }

# body <name> <path> <expected-substring> [curl-args...]
body() {
    local name="$1" path="$2" needle="$3"
    shift 3
    local response
    response="$(curl -s "$@" "${BASE}${path}")"
    if contains "$response" "$needle"; then
        green "PASS  ${name}"
        PASS=$((PASS + 1))
    else
        red "FAIL  ${name} (${path} does not contain: ${needle})"
        FAIL=$((FAIL + 1))
    fi
}

# header <name> <path> <expected-header-substring>
header() {
    local name="$1" path="$2" needle="$3"
    local headers
    headers="$(curl -s -D - -o /dev/null "${BASE}${path}" | tr -d '\r')"
    if contains "${headers,,}" "${needle,,}"; then
        green "PASS  ${name}"
        PASS=$((PASS + 1))
    else
        red "FAIL  ${name} (${path} is missing header: ${needle})"
        FAIL=$((FAIL + 1))
    fi
}

# redirect <name> <path> <expected-code> <expected-location>
redirect() {
    local name="$1" path="$2" expected="$3" location="$4"
    local response code target
    response="$(curl -s -D - -o /dev/null "${BASE}${path}" | tr -d '\r')"
    code="$(printf '%s' "$response" | awk 'NR==1{print $2}')"
    target="$(printf '%s' "$response" | awk 'tolower($1)=="location:"{print $2}')"
    if [ "$code" = "$expected" ] && [ "$target" = "$location" ]; then
        green "PASS  ${name} (${path} -> ${code} ${target})"
        PASS=$((PASS + 1))
    else
        red "FAIL  ${name} (${path} -> ${code} ${target}, expected ${expected} ${location})"
        FAIL=$((FAIL + 1))
    fi
}

# laravel_reached <name> <method> <path>
# Proves a request was answered by Laravel and not by the static frontend
# fallback: the static frontend never sets a cookie, and its fallback is a 404.
# The concrete status (419 for an unauthenticated POST without a CSRF token) is
# reported as evidence but is not required.
laravel_reached() {
    local name="$1" method="$2" path="$3"
    local headers code
    headers="$(curl -s -X "$method" -D - -o /dev/null "${BASE}${path}" | tr -d '\r')"
    code="$(printf '%s' "$headers" | awk 'NR==1{print $2}')"
    if [ "$code" != "404" ] && contains "${headers,,}" "set-cookie:"; then
        green "PASS  ${name} (${method} ${path} -> ${code}, answered by Laravel)"
        PASS=$((PASS + 1))
    else
        red "FAIL  ${name} (${method} ${path} -> ${code}, not answered by Laravel)"
        FAIL=$((FAIL + 1))
    fi
}

# livewire_runtime: discovers the content-addressed Livewire prefix from the
# real login page instead of copying a hash, then checks the runtime script and
# the technical endpoints behind it.
livewire_runtime() {
    local html script prefix headers body code
    html="$(curl -s "${BASE}/admin/login")"
    script="$(printf '%s' "$html" | grep -oE '/livewire-[^/"]+/livewire(\.min)?\.js(\?[^"]*)?' | head -n 1)"
    script="${script//&amp;/&}"
    if [ -z "$script" ]; then
        red "FAIL  livewire script discovered (/admin/login has no Livewire script)"
        FAIL=$((FAIL + 1))
        return
    fi
    green "PASS  livewire script discovered (${script})"
    PASS=$((PASS + 1))

    headers="$(curl -s -D - -o /tmp/portfolio-smoke-livewire.js "${BASE}${script}" | tr -d '\r')"
    body="$(head -c 2048 /tmp/portfolio-smoke-livewire.js)"
    rm -f /tmp/portfolio-smoke-livewire.js
    code="$(printf '%s' "$headers" | awk 'NR==1{print $2}')"
    if [ "$code" = "200" ] && contains "${headers,,}" "javascript" && ! contains "$body" 'Página no encontrada' && ! contains "$body" '<html'; then
        green "PASS  livewire runtime script (${script} -> 200 javascript)"
        PASS=$((PASS + 1))
    else
        red "FAIL  livewire runtime script (${script} -> ${code}, expected 200 javascript, not the frontend)"
        FAIL=$((FAIL + 1))
    fi

    prefix="$(printf '%s' "$script" | sed -E 's#^(/livewire-[^/]+/).*#\1#')"
    laravel_reached "livewire update reaches Laravel"      POST "${prefix}update"
    laravel_reached "livewire upload reaches Laravel"      POST "${prefix}upload-file"
}

echo "Smoke checking ${BASE}"
echo

echo "-- gateway --"
status  "gateway health"            /__gateway/health         200

echo "-- localized shells (direct refresh) --"
status  "spanish shell"             /                         200
body    "spanish shell is es"       /                         '<html lang="es">'
body    "spanish canonical"         /                         '<link rel="canonical" href="https://lucianogonzalez.dev/">'
status  "english shell"             /en                       200
body    "english shell is en"       /en                       '<html lang="en">'
body    "english canonical"         /en                       '<link rel="canonical" href="https://lucianogonzalez.dev/en">'

echo "-- canonical redirects --"
redirect "es redirect"              /es                       308 /
redirect "es slash redirect"        /es/                      308 /
redirect "en slash redirect"        /en/                      308 /en

echo "-- SEO files --"
status  "sitemap"                   /sitemap.xml              200
body    "sitemap urls"              /sitemap.xml              '<loc>https://lucianogonzalez.dev/en</loc>'
status  "robots"                    /robots.txt               200
body    "robots sitemap"            /robots.txt               'Sitemap: https://lucianogonzalez.dev/sitemap.xml'

echo "-- favicon --"
status  "favicon.ico"               /favicon.ico              200
status  "favicon 32"                /favicon-32x32.png        200
status  "favicon 16"                /favicon-16x16.png        200
status  "apple touch icon"          /apple-touch-icon.png     200

echo "-- not found --"
status  "unknown page"              /this-page-does-not-exist 404
body    "unknown page body"         /this-page-does-not-exist 'Página no encontrada'
header  "unknown page noindex"      /this-page-does-not-exist 'X-Robots-Tag: noindex, nofollow'
status  "missing asset"             /assets/does-not-exist.js 404
status  "missing media"             /media/does-not-exist.mp4 404
status  "development path is gone"  /src/main.tsx             404

echo "-- runtime configuration --"
# Present or absent, this endpoint is never cacheable. A 404 is the intentional
# analytics-OFF state, not a failure.
header  "runtime-config no-store"   /runtime-config.json      'Cache-Control: no-store'
header  "runtime-config noindex"    /runtime-config.json      'X-Robots-Tag: noindex, nofollow'

echo "-- api --"
status  "api version"               /api/v1                   200
body    "api version payload"       /api/v1                   '"version":"v1"'
header  "api noindex"               /api/v1                   'X-Robots-Tag: noindex, nofollow'
# A freshly deployed site has nothing published yet, so the localized public
# endpoints legitimately answer 404 with the documented error envelope until an
# administrator publishes content. Both states satisfy the contract.
status_any "api spanish profile"    /api/v1/es/profile        200 404
status_any "api english profile"    /api/v1/en/profile        200 404
status_any "api spanish site"       /api/v1/es/site           200 404
status_any "api english site"       /api/v1/en/site           200 404
status_any "api projects"           /api/v1/es/projects       200 404
status_any "api technologies"       /api/v1/es/technologies   200 404
status_any "api experiences"        /api/v1/es/experiences    200 404
status_any "api work cases"         /api/v1/es/work-cases     200 404
body    "api error envelope"        /api/v1/es/nope           '"code":"not_found"'
status  "api unsupported locale"    /api/v1/fr/profile        404
status  "api unknown resource"      /api/v1/es/nope           404
status  "laravel health"            /up                       200

echo "-- managed media and CV downloads --"
# These follow publication state: 200 once an administrator has published the
# document, 404 while it is still a draft. A draft must never leak, so any
# other status is a failure.
status_any "spanish CV"             /cv/luciano-gonzalez-es.pdf 200 404
status_any "english CV"             /cv/luciano-gonzalez-en.pdf 200 404
status  "unknown managed media"     /storage/does-not-exist.png 404

echo "-- administration --"
# /admin must answer and must not serve the panel to an anonymous visitor.
status  "admin requires auth"       /admin                    302
header  "admin noindex"             /admin                    'X-Robots-Tag: noindex, nofollow'
status  "admin login page"          /admin/login              200

echo "-- livewire runtime --"
livewire_runtime

echo "-- scroll media --"
status  "scroll video v4"           /media/scroll/ink-tree-network-v4.mp4         200
status  "scroll poster v4"          /media/scroll/ink-tree-network-v4-poster.webp 200

echo
echo "passed: ${PASS}  failed: ${FAIL}"
[ "$FAIL" -eq 0 ]
