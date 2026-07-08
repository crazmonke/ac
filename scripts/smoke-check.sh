#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${1:-${SMOKE_BASE_URL:-https://apaind.mycafe24.com}}"

# Keep checks simple and fast for shared-hosting deployments.
PATHS=(
  "/"
  "/login"
  "/community"
  "/register"
)

echo "Smoke check base URL: ${BASE_URL}"

for path in "${PATHS[@]}"; do
  url="${BASE_URL}${path}"
  code="$(curl -sS -L -o /dev/null -w '%{http_code}' --max-time 20 "${url}")"
  case "${code}" in
    2*|3*)
      echo "PASS ${path} -> ${code}"
      ;;
    *)
      echo "FAIL ${path} -> ${code}" >&2
      exit 1
      ;;
  esac
done

search_url="${BASE_URL}/apartments/search"
search_query="모아미래도"
search_payload="$(curl -sS --get --max-time 20 --data-urlencode "q=${search_query}" "${search_url}")"

if printf '%s' "${search_payload}" | grep -q '"data"\s*:\s*\[' && ! printf '%s' "${search_payload}" | grep -q '"data"\s*:\s*\[\s*\]'; then
  echo "PASS /apartments/search?q=${search_query} -> non-empty data"
else
  echo "FAIL /apartments/search?q=${search_query} -> empty or invalid payload" >&2
  echo "Payload: ${search_payload}" >&2
  exit 1
fi

echo "Smoke checks passed"
