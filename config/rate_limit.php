<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Batas jumlah request per menit. Semua nilai dapat diatur lewat .env tanpa
    | mengubah kode. Limit "pos" dihitung per user (bukan per IP) sehingga
    | pengguna yang berbagi IP kantor tidak saling mengurangi kuota.
    |
    */

    // Request umum aplikasi (per user, atau per IP bila belum login).
    'pos_per_minute' => (int) env('RATE_LIMIT_POS_PER_MINUTE', 240),

    // Percobaan login (per IP) — perlindungan brute force.
    'login_per_minute' => (int) env('RATE_LIMIT_LOGIN_PER_MINUTE', 10),

];
