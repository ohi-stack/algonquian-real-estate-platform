#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGINS_DIR="${ROOT}/plugins"
RELEASES_DIR="${ROOT}/releases"

php "${ROOT}/build/validate-wordpress-plugins.php"

rm -rf "${RELEASES_DIR}"
mkdir -p "${RELEASES_DIR}"

find "${PLUGINS_DIR}" -mindepth 1 -maxdepth 1 -type d -print0 \
  | sort -z \
  | while IFS= read -r -d '' plugin_dir; do
      slug="$(basename "${plugin_dir}")"
      case "${slug}" in
        .*) continue ;;
      esac

      main_file="$(grep -ril --include='*.php' '^\s*Plugin Name:' "${plugin_dir}" | head -n 1 || true)"
      if [[ -z "${main_file}" ]]; then
        echo "Skipping ${slug}: no plugin header found" >&2
        exit 1
      fi

      version="$(grep -iE '^\s*Version:' "${main_file}" | head -n 1 | sed -E 's/^\s*Version:\s*//I' | tr -d '\r')"
      archive="${RELEASES_DIR}/${slug}-${version}.zip"

      (
        cd "${PLUGINS_DIR}"
        zip -qr "${archive}" "${slug}" \
          -x "${slug}/.git/*" \
          -x "${slug}/.github/*" \
          -x "${slug}/node_modules/*" \
          -x "${slug}/vendor/bin/*" \
          -x "${slug}/tests/*" \
          -x "${slug}/phpunit.xml*" \
          -x "${slug}/composer.lock" \
          -x "${slug}/package-lock.json"
      )

      echo "Created $(basename "${archive}")"
    done

(
  cd "${RELEASES_DIR}"
  sha256sum ./*.zip > SHA256SUMS.txt
)

echo "Release packages are available in ${RELEASES_DIR}"
