#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${1:-${SMOKE_BASE_URL:-https://apaind.mycafe24.com}}"

# Keep checks simple and fast for shared-hosting deployments.
PATHS=(
  "/"
  "/login"
  "/community"
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

echo "Smoke checks passed"
