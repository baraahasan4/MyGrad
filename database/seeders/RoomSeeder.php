<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\RoomImage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $rooms = [
            [
                'data' => ['number' => '101', 'status' => 'available', 'room_type_id' => 1],
                'images' => [
                    ['path' => 'images/rooms/1748266365_68346d7d52f14.png', 'type' => 'normal'],
                    ['path' => 'images/rooms/1748266526_68346e1e31b87.jpg', 'type' => 'panorama'],
                ],
            ],
            [
                'data' => ['number' => '102', 'status' => 'available', 'room_type_id' => 1],
                'images' => [
                    ['path' => 'images/rooms/1748266811_68346f3b1c8d7.png', 'type' => 'normal'],
                    ['path' => 'images/rooms/1748266873_68346f798308f.jpg', 'type' => 'panorama'],
                ],
            ],
            [
                'data' => ['number' => '103', 'status' => 'available', 'room_type_id' => 1],
                'images' => [
                    ['path' => 'images/rooms/1748268585_6834762994b06.jpg', 'type' => 'normal'],
                    ['path' => 'images/rooms/1748268363_6834754bdd321.png', 'type' => 'panorama'],
                ],
            ],
            [
                'data' => ['number' => '201', 'status' => 'available', 'room_type_id' => 2],
                'images' => [
                    ['path' => 'images/rooms/1748269129_68347849162d9.png', 'type' => 'normal'],
                    ['path' => 'images/rooms/1748269184_6834788007080.png', 'type' => 'normal'],
                    ['path' => 'images/rooms/1748269237_683478b52a6a9.jpg', 'type' => 'panorama'],
                ],
            ],
            [
                'data' => ['number' => '202', 'status' => 'available', 'room_type_id' => 2],
                'images' => [
                    ['path' => 'images/rooms/1748269532_683479dc6cdda.png', 'type' => 'normal'],
                    ['path' => 'images/rooms/1748269577_68347a0988a9f.png', 'type' => 'normal'],
                    ['path' => 'images/rooms/1748269471_6834799f3b428.jpg', 'type' => 'panorama'],
                ],
            ],
            [
                'data' => ['number' => '203', 'status' => 'available', 'room_type_id' => 2],
                'images' => [
                    ['path' => 'images/rooms/1748269691_68347a7b874ec.png', 'type' => 'normal'],
                    ['path' => 'images/rooms/1748269717_68347a9571d83.png', 'type' => 'normal'],
                    ['path' => 'images/rooms/1748269658_68347a5a748a1.jpg', 'type' => 'panorama'],
                ],
            ],
            [
                'data' => ['number' => '301', 'status' => 'available', 'room_type_id' => 3],
                'images' => [
                    ['path' => 'images/rooms/1748283390_6834affe7c057.png', 'type' => 'normal'],
                    ['path' => 'images/rooms/1748283432_6834b0289551a.png', 'type' => 'panorama'],
                ],
            ],
            [
                'data' => ['number' => '302', 'status' => 'available', 'room_type_id' => 3],
                'images'=> [
                    ['path' => 'images/rooms/1748287775_6834c11f56a83.png', 'type' => 'normal'],
                    ['path' => 'images/rooms/1748287828_6834c1547b8b6.png', 'type' => 'normal'],
                    ['path' => 'images/rooms/1748287865_6834c1795a216.png', 'type' => 'panorama'],
                ],
            ],
            [
                'data' => ['number' => '401', 'status' => 'available', 'room_type_id' => 4],
                'images'=> [
                    ['path' => 'images/rooms/1748367593_6835f8e9025ce.png', 'type' => 'normal'],
                    ['path' => 'images/rooms/1748367634_6835f91230a6a.png', 'type' => 'normal'],
                    ['path' => 'images/rooms/1748367713_6835f961d516a.png', 'type' => 'normal'],
                    ['path' => 'images/rooms/1748367798_6835f9b688095.jpg', 'type' => 'panorama'],
                ],
            ],
            [
                'data' => ['number' => '402', 'status' => 'available', 'room_type_id' => 4],
                'images'=> [
                    ['path' => 'images/rooms/1748368070_6835fac603740.png', 'type' => 'normal'],
                    ['path' => 'images/rooms/1748368099_6835fae37d3c4.png', 'type' => 'normal'],
                    ['path' => 'images/rooms/1748368156_6835fb1c677f6.jpg', 'type' => 'panorama'],
                ],
            ],
            [
                'data' => ['number' => '501', 'status' => 'available', 'room_type_id' => 5],
                'images'=> [
                    ['path' => 'images/rooms/1748368378_6835fbfab6d4c.png', 'type' => 'normal'],
                    ['path' => 'images/rooms/1748368418_6835fc22e2b14.png', 'type' => 'normal'],
                    ['path' => 'images/rooms/1748368475_6835fc5bea8c9.jpg', 'type' => 'panorama'],
                ],
            ],
            [
                'data' => ['number' => '502', 'status' => 'available', 'room_type_id' => 5],
                'images'=> [
                    ['path' => 'images/rooms/1748372546_68360c42802f4.jpg', 'type' => 'normal'],
                    ['path' => 'images/rooms/1748372500_68360c147cf48.png', 'type' => 'panorama'],
                ],
            ],
            [
                'data' => ['number' => '601', 'status' => 'available', 'room_type_id' => 6],
                'images'=> [
                    ['path' => 'images/rooms/1748372885_68360d954f10e.png', 'type' => 'normal'],
                    ['path' => 'images/rooms/1748372987_68360dfb42f88.jpg', 'type' => 'panorama'],
                ],
            ],
            [
                'data' => ['number' => '602', 'status' => 'available', 'room_type_id' => 6],
                'images'=> [
                    ['path' => 'images/rooms/1748373096_68360e680c025.png', 'type' => 'normal'],
                    ['path' => 'images/rooms/1748373189_68360ec5a175b.jpg', 'type' => 'panorama'],
                ],
            ],
        ];

        foreach ($rooms as $roomEntry) {
            $room = Room::create($roomEntry['data']);

            if (!empty($roomEntry['images'])) {
                foreach ($roomEntry['images'] as $image) {
                    RoomImage::create([
                        'room_id' => $room->id,
                        'image_path' => $image['path'],
                        'image_type' => $image['type'],
                    ]);
                }
            }
        }
    }
}
