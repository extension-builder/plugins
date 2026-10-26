#!/usr/bin/env bash
set -euo pipefail

plugin_root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
test_project="${JCB_TEST_PROJECT:-$plugin_root/.runtime/jcb/libraries/vendor_jcb/tests}"

if [[ ! -f "$test_project/vendor/bin/phpunit" ]]; then
  printf 'Prepare the JCB test runtime described in tests/README.md, or set JCB_TEST_PROJECT.\n' >&2
  exit 1
fi

export JCB_TEST_PROJECT="$test_project"
exec php -d memory_limit=1G "$test_project/vendor/bin/phpunit" --configuration "$plugin_root/phpunit.xml.dist" "$@"
