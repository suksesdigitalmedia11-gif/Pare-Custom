#!/usr/bin/env bash
#
# Menjalankan test suite PareCustom POS dengan aman.
#
# Kenapa skrip ini ada:
#   1. Test memakai SQLite in-memory (RefreshDatabase menghapus seluruh tabel).
#      Pengaman di tests/TestCase.php memaksa koneksi sqlite, jadi database
#      produksi tidak akan tersentuh.
#   2. Bila `php artisan config:cache` sedang aktif, seluruh nilai di
#      phpunit.xml DIABAIKAN. Config cache harus dibersihkan dulu.
#   3. Config cache WAJIB dipulihkan setelah selesai — termasuk saat test
#      gagal — supaya situs tetap cepat.
#
# Pemakaian:
#   ./scripts/run-tests.sh                 # semua test
#   ./scripts/run-tests.sh --filter=Login  # filter PHPUnit
#
set -uo pipefail

cd "$(dirname "$0")/.." || exit 1

RESTORE_CACHE=0
if [ -f bootstrap/cache/config.php ]; then
    RESTORE_CACHE=1
    php artisan config:clear >/dev/null 2>&1
fi

restore_cache() {
    if [ "$RESTORE_CACHE" -eq 1 ]; then
        php artisan config:cache >/dev/null 2>&1
    fi
}
trap restore_cache EXIT

php artisan test "$@"
