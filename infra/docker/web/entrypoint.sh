#!/bin/sh
# Materializes the optional public runtime configuration consumed by
# GET /runtime-config.json.
#
# The same image digest must be reconfigurable without a rebuild, tag or
# release, so these values are read from the environment at container start and
# never baked into the Vite build. They are public values, not secrets.
#
# Analytics is unambiguously OFF unless BOTH values are present and valid:
# a partial or malformed configuration removes the file, which the static
# runtime then serves as an intentional 404 with Cache-Control: no-store.
set -eu

target="/srv/www/runtime-config.json"
tracker_url="${UMAMI_TRACKER_URL:-}"
website_id="${UMAMI_WEBSITE_ID:-}"

nil_uuid="00000000-0000-0000-0000-000000000000"
uuid_pattern='^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$'
tracker_pattern='^https?://[!-~]+$'

analytics_off() {
    rm -f "$target"
    echo "portfolio-web: analytics OFF ($1)" >&2
}

if [ -z "$tracker_url" ] || [ -z "$website_id" ]; then
    analytics_off "runtime configuration not supplied"
elif ! printf '%s' "$tracker_url" | grep -Eq "$tracker_pattern"; then
    analytics_off "UMAMI_TRACKER_URL is not an http(s) URL"
elif printf '%s' "$tracker_url" | grep -q '["\\]'; then
    analytics_off "UMAMI_TRACKER_URL contains an unsupported character"
elif ! printf '%s' "$website_id" | grep -Eq "$uuid_pattern"; then
    analytics_off "UMAMI_WEBSITE_ID is not a UUID"
elif [ "$website_id" = "$nil_uuid" ]; then
    analytics_off "UMAMI_WEBSITE_ID is the nil UUID"
else
    # Written to a sibling temporary file and renamed so a reader never
    # observes a partially written configuration.
    temporary="${target}.tmp"
    printf '{"umami":{"trackerUrl":"%s","websiteId":"%s"}}\n' "$tracker_url" "$website_id" >"$temporary"
    mv "$temporary" "$target"
    echo "portfolio-web: analytics ENABLED" >&2
fi

exec "$@"
