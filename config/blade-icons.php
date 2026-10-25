<?php

// Pengaturan paket blade-ui-kit/blade-icons (dipasang bersama blade-heroicons).
//
// Komponen bawaan paket (<x-icon>, <x-heroicon-o-...>) dimatikan karena aplikasi
// memakai komponennya sendiri, <x-ikon>, yang memanggil helper svg() langsung.
// Tanpa ini, <x-icon> milik paket bisa mengambil alih tampilan lama dan
// memunculkan error "Svg by name ... from set "default" not found".

return [
    'sets' => [],
    'class' => '',
    'attributes' => [],
    'fallback' => '',
    'components' => [
        'disabled' => true,
        'default' => 'icon',
    ],
];
