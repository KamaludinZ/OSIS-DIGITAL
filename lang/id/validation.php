<?php

return [
    'required' => ':attribute wajib diisi.',
    'email' => ':attribute harus berupa alamat email yang benar.',
    'string' => ':attribute harus berupa teks.',
    'min' => ['string' => ':attribute minimal :min karakter.', 'numeric' => ':attribute minimal :min.', 'file' => ':attribute minimal :min kilobita.'],
    'max' => ['string' => ':attribute maksimal :max karakter.', 'numeric' => ':attribute maksimal :max.', 'file' => 'Ukuran :attribute maksimal :max kilobita.'],
    'image' => ':attribute harus berupa gambar.',
    'mimes' => ':attribute harus berformat: :values.',
    'url' => ':attribute harus berupa tautan yang benar.',
    'in' => ':attribute yang dipilih tidak valid.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Kata sandi salah.',
    'unique' => ':attribute sudah dipakai.',
    'attributes' => [
        'email' => 'Email',
        'password' => 'Kata sandi',
        'judul' => 'Judul',
        'tautan' => 'Tautan konten',
        'platform' => 'Jenis media sosial',
        'orientasi' => 'Orientasi',
        'thumbnail' => 'Thumbnail',
    ],
];
