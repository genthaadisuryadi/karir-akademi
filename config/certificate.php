<?php

/*
|--------------------------------------------------------------------------
| Sertifikat
|--------------------------------------------------------------------------
|
| Posisi nama, nomor, tanggal, dan QR Code SAMA untuk semua kelas, sehingga desainer
| cukup mengikuti koordinat di bawah ini saat membuat template PNG.
|
| Semua koordinat berupa PECAHAN (0-1) dari lebar/tinggi halaman, dihitung dari kiri-atas,
| jadi tidak bergantung pada resolusi PNG (asal rasio halaman depan dan belakang konsisten).
| Untuk teks, "x" adalah titik tengah teks (rata tengah) dan "y" adalah garis dasar teks.
| "size" adalah ukuran huruf sebagai pecahan dari LEBAR halaman.
|
*/

return [
    // Alamat dasar yang dimuat di QR Code: {base_url}/sertifikat/{nomor-sertifikat}
    'base_url' => env('CERTIFICATE_BASE_URL', 'http://karirakademi.id'),

    // Lebar halaman PDF dalam poin (A4 landscape = 841.89). Tinggi mengikuti rasio PNG.
    'page_width' => 841.89,

    // Syarat berkas template yang diunggah admin.
    'template' => [
        'min_width' => 1000,
        'min_height' => 700,
        'max_kb' => 8192,
    ],

    'layout' => [
        'name' => [
            'x' => 0.50, 'y' => 0.52, 'size' => 0.036, 'max_width' => 0.70,
            'color' => '0.07 0.15 0.30', 'bold' => true,
        ],
        'number' => [
            'x' => 0.50, 'y' => 0.285, 'size' => 0.0125,
            'color' => '0.25 0.29 0.35', 'bold' => false,
        ],
        'date' => [
            'x' => 0.30, 'y' => 0.865, 'size' => 0.0165,
            'color' => '0.07 0.15 0.30', 'bold' => false,
        ],
        'qr' => [
            // Titik kiri-atas QR dan ukuran sisi (pecahan dari lebar halaman), sudah termasuk bidang putih.
            'x' => 0.80, 'y' => 0.70, 'size' => 0.115,
        ],
    ],
];
