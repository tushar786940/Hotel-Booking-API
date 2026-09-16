<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Step 1: Create Roles ───
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $ownerRole = Role::create(['name' => 'hotel-owner', 'guard_name' => 'web']);
        $guestRole = Role::create(['name' => 'guest', 'guard_name' => 'web']);

        // ─── Step 2: Create Users ───
        $admin = User::create([
            'name'     => 'Admin User',
            'email'    => 'admin@hotel.com',
            'password' => 'password',
            'phone'    => '+1234567890',
        ]);
        $admin->assignRole('admin');

        $owner = User::create([
            'name'     => 'Hotel Owner',
            'email'    => 'owner@hotel.com',
            'password' => 'password',
            'phone'    => '+1234567891',
        ]);
        $owner->assignRole('hotel-owner');

        $guest = User::create([
            'name'     => 'John Guest',
            'email'    => 'guest@hotel.com',
            'password' => 'password',
            'phone'    => '+1234567892',
        ]);
        $guest->assignRole('guest');

        // ─── Step 3: Create Hotels with Room Types and Rooms ───
        $hotelsData = [
            [
                'name'        => 'Grand Palace Hotel',
                'description' => 'Experience luxury in the heart of New York City. Our 5-star hotel offers unmatched elegance and comfort.',
                'address'     => '123 Broadway, Manhattan',
                'city'        => 'New York',
                'state'       => 'NY',
                'country'     => 'USA',
                'zip_code'    => '10001',
                'latitude'    => 40.7128,
                'longitude'   => -74.0060,
                'star_rating' => 5,
                'amenities'   => ['wifi', 'pool', 'spa', 'gym', 'restaurant', 'bar', 'valet_parking'],
                'room_types'  => [
                    ['name' => 'Standard',  'price' => 199.99,  'capacity' => 2, 'total' => 15, 'amenities' => ['wifi', 'tv', 'ac', 'minibar']],
                    ['name' => 'Deluxe',    'price' => 299.99,  'capacity' => 3, 'total' => 10, 'amenities' => ['wifi', 'tv', 'ac', 'minibar', 'balcony', 'city_view']],
                    ['name' => 'Suite',     'price' => 499.99,  'capacity' => 4, 'total' => 5,  'amenities' => ['wifi', 'tv', 'ac', 'minibar', 'balcony', 'jacuzzi', 'living_room']],
                    ['name' => 'Penthouse', 'price' => 999.99,  'capacity' => 6, 'total' => 2,  'amenities' => ['wifi', 'tv', 'ac', 'minibar', 'terrace', 'jacuzzi', 'butler_service']],
                ],
            ],
            [
                'name'        => 'Seaside Resort & Spa',
                'description' => 'A beautiful beachfront resort in Miami with stunning ocean views and world-class amenities.',
                'address'     => '456 Ocean Drive',
                'city'        => 'Miami',
                'state'       => 'FL',
                'country'     => 'USA',
                'zip_code'    => '33139',
                'latitude'    => 25.7617,
                'longitude'   => -80.1918,
                'star_rating' => 4,
                'amenities'   => ['wifi', 'pool', 'beach_access', 'spa', 'restaurant', 'water_sports'],
                'room_types'  => [
                    ['name' => 'Garden View', 'price' => 149.99, 'capacity' => 2, 'total' => 20, 'amenities' => ['wifi', 'tv', 'ac']],
                    ['name' => 'Ocean View',  'price' => 229.99, 'capacity' => 2, 'total' => 15, 'amenities' => ['wifi', 'tv', 'ac', 'balcony', 'ocean_view']],
                    ['name' => 'Beach Suite', 'price' => 399.99, 'capacity' => 4, 'total' => 5,  'amenities' => ['wifi', 'tv', 'ac', 'private_beach', 'jacuzzi']],
                ],
            ],
            [
                'name'        => 'Mountain Lodge',
                'description' => 'A cozy mountain retreat in Denver, perfect for nature lovers and adventure seekers.',
                'address'     => '789 Mountain Road',
                'city'        => 'Denver',
                'state'       => 'CO',
                'country'     => 'USA',
                'zip_code'    => '80201',
                'latitude'    => 39.7392,
                'longitude'   => -104.9903,
                'star_rating' => 3,
                'amenities'   => ['wifi', 'parking', 'fireplace', 'hiking_trails', 'ski_access'],
                'room_types'  => [
                    ['name' => 'Standard Cabin', 'price' => 89.99,  'capacity' => 2, 'total' => 12, 'amenities' => ['wifi', 'fireplace', 'mountain_view']],
                    ['name' => 'Family Cabin',   'price' => 159.99, 'capacity' => 5, 'total' => 8,  'amenities' => ['wifi', 'fireplace', 'kitchen', 'mountain_view']],
                    ['name' => 'Luxury Lodge',   'price' => 249.99, 'capacity' => 4, 'total' => 4,  'amenities' => ['wifi', 'fireplace', 'hot_tub', 'kitchen', 'panoramic_view']],
                ],
            ],
        ];

        foreach ($hotelsData as $hotelData) {
            $roomTypesData = $hotelData['room_types'];
            unset($hotelData['room_types']);

            $hotel = Hotel::create(array_merge($hotelData, [
                'user_id'   => $owner->id,
                'is_active' => true,
            ]));

            foreach ($roomTypesData as $rtData) {
                $roomType = RoomType::create([
                    'hotel_id'        => $hotel->id,
                    'name'            => $rtData['name'],
                    'description'     => "{$rtData['name']} room at {$hotel->name}",
                    'price_per_night' => $rtData['price'],
                    'capacity'        => $rtData['capacity'],
                    'total_rooms'     => $rtData['total'],
                    'amenities'       => $rtData['amenities'],
                ]);

                $prefix = strtoupper(substr($rtData['name'], 0, 1));

                for ($i = 1; $i <= $rtData['total']; $i++) {
                    Room::create([
                        'room_type_id' => $roomType->id,
                        'room_number'  => $prefix . str_pad($i, 3, '0', STR_PAD_LEFT),
                        'floor'        => (int) ceil($i / 4),
                        'status'       => 'available',
                        'is_available' => true,
                    ]);
                }
            }

            $this->command->info("✅ Created hotel: {$hotel->name} with rooms");
        }

        $this->command->info('');
        $this->command->info('🎉 Seeding complete!');
        $this->command->info('───────────────────────');
        $this->command->info('Admin:  admin@hotel.com / password');
        $this->command->info('Owner:  owner@hotel.com / password');
        $this->command->info('Guest:  guest@hotel.com / password');
    }
}
