<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        // --- Superadmin: pengawas ekosistem, tidak memiliki tenant mana pun ---
        User::updateOrCreate(
            ['email' => 'admin@amikom.ac.id'],
            [
                'name' => 'Superadmin Amikom',
                'password' => bcrypt('password'),
                'role' => User::ROLE_SUPERADMIN,
            ]
        );

        // --- Tenant 1: HIMA Sistem Informasi ---
        $hmsi = Organization::updateOrCreate(
            ['slug' => 'hima-si'],
            [
                'name' => 'HIMA Sistem Informasi',
                'description' => 'Himpunan Mahasiswa Sistem Informasi Universitas AMIKOM Yogyakarta. Menyelenggarakan seminar, workshop, dan kompetisi teknologi.',
                'contact_email' => 'hima.si@amikom.ac.id',
                'contact_phone' => '081234567001',
                'is_active' => true,
            ]
        );

        $hmsiOwner = User::updateOrCreate(
            ['email' => 'panitia.si@amikom.ac.id'],
            [
                'name' => 'Ketua HIMA SI',
                'password' => bcrypt('password'),
                'role' => User::ROLE_ORGANIZER,
            ]
        );
        $hmsi->members()->syncWithoutDetaching([$hmsiOwner->id => ['role' => 'owner']]);

        // --- Tenant 2: UKM Musik ---
        $ukmMusik = Organization::updateOrCreate(
            ['slug' => 'ukm-musik'],
            [
                'name' => 'UKM Musik AMIKOM',
                'description' => 'Unit Kegiatan Mahasiswa bidang musik. Konser, festival, dan pentas seni kampus.',
                'contact_email' => 'ukm.musik@amikom.ac.id',
                'contact_phone' => '081234567002',
                'is_active' => true,
            ]
        );

        $musikOwner = User::updateOrCreate(
            ['email' => 'panitia.musik@amikom.ac.id'],
            [
                'name' => 'Ketua UKM Musik',
                'password' => bcrypt('password'),
                'role' => User::ROLE_ORGANIZER,
            ]
        );
        $ukmMusik->members()->syncWithoutDetaching([$musikOwner->id => ['role' => 'owner']]);

        // --- Akun pembeli biasa untuk demo review ---
        User::updateOrCreate(
            ['email' => 'peserta@amikom.ac.id'],
            [
                'name' => 'Peserta Demo',
                'password' => bcrypt('password'),
                'role' => User::ROLE_USER,
            ]
        );

        // --- Backfill: bagikan event lama ke tenant berdasarkan kategori ---
        Event::whereNull('organization_id')->each(function (Event $event) use ($hmsi, $ukmMusik) {
            $isEntertainment = str_contains(
                strtolower(optional($event->category)->name ?? ''),
                'entertai'
            );

            $event->organization_id = $isEntertainment ? $ukmMusik->id : $hmsi->id;
            $event->save();
        });
    }
}