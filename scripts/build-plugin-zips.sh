#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGINS_DIR="${ROOT_DIR}/plugins"
DIST_DIR="${ROOT_DIR}/dist"

mkdir -p "${DIST_DIR}"

if ! command -v zip >/dev/null 2>&1; then
  echo "Error: zip is required but is not installed." >&2
  exit 1
fi

if [ ! -d "${PLUGINS_DIR}" ]; then
  echo "Error: plugins directory not found: ${PLUGINS_DIR}" >&2
  exit 1
fi

build_plugin_zip() {
  local plugin_path="$1"
  local plugin_slug
  plugin_slug="$(basename "${plugin_path}")"
  local zip_path="${DIST_DIR}/${plugin_slug}-1.0.0.zip"

  if [ ! -f "${plugin_path}/${plugin_slug}.php" ]; then
    echo "Skipping ${plugin_slug}: missing ${plugin_slug}.php"
    return 0
  fi

  echo "Building ${zip_path}"
  rm -f "${zip_path}"

  (
    cd "${plugin_path}/.."
    zip -rq "${zip_path}" "${plugin_slug}" \
      -x "${plugin_slug}/.git/*" \
      -x "${plugin_slug}/.github/*" \
      -x "${plugin_slug}/node_modules/*" \
      -x "${plugin_slug}/vendor/*" \
      -x "${plugin_slug}/tests/*" \
      -x "${plugin_slug}/.DS_Store" \
      -x "${plugin_slug}/package-lock.json" \
      -x "${plugin_slug}/composer.lock" \
      -x "${plugin_slug}/phpunit.xml" \
      -x "${plugin_slug}/phpunit.xml.dist"
  )
}

for plugin_path in "${PLUGINS_DIR}"/*; do
  [ -d "${plugin_path}" ] || continue
  build_plugin_zip "${plugin_path}"
done

echo "Plugin ZIP generation complete. Output: ${DIST_DIR}"
