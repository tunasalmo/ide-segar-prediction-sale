/** Format nilai Rupiah: 352107238 -> "Rp 352.107.238" */
export function rupiah(value, decimals = 0) {
    if (value === null || value === undefined || Number.isNaN(Number(value))) return '—';
    const n = Number(value);
    return (
        'Rp ' +
        n.toLocaleString('id-ID', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        })
    );
}

/** Format angka dengan pemisah ribuan Indonesia. */
export function num(value, decimals = 0) {
    if (value === null || value === undefined || Number.isNaN(Number(value))) return '—';
    return Number(value).toLocaleString('id-ID', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
}

/** Persentase: 12.34 -> "+12,3%" atau "−3,2%"; null -> "—" */
export function pct(value, decimals = 1) {
    if (value === null || value === undefined || Number.isNaN(Number(value))) return '—';
    const n = Number(value);
    const sign = n > 0 ? '+' : n < 0 ? '−' : '';
    return sign + Math.abs(n).toLocaleString('id-ID', { maximumFractionDigits: decimals }) + '%';
}

export function pctPlain(value, decimals = 1) {
    if (value === null || value === undefined || Number.isNaN(Number(value))) return '—';
    return Number(value).toLocaleString('id-ID', { maximumFractionDigits: decimals }) + '%';
}
