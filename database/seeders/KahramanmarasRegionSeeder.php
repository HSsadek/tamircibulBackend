<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ServiceProvider;

class KahramanmarasRegionSeeder extends Seeder
{
    public function run()
    {
        // Kahramanmaraş ve çevre şehirler - gerçek mesafeler
        $cities = [
            // Kahramanmaraş merkez - 0-5km
            'Kahramanmaraş' => [
                'districts' => ['Dulkadiroğlu', 'Onikişubat'],
                'lat_range' => [37.5650, 37.5850], // Merkez
                'lng_range' => [36.9200, 36.9500],
                'service_count' => 15
            ],
            // Yakın şehirler - 50-100km
            'Gaziantep' => [
                'districts' => ['Şahinbey', 'Şehitkamil'],
                'lat_range' => [37.0500, 37.1000],
                'lng_range' => [37.3500, 37.4000],
                'service_count' => 10
            ],
            'Kayseri' => [
                'districts' => ['Melikgazi', 'Kocasinan'],
                'lat_range' => [38.7000, 38.7500],
                'lng_range' => [35.4500, 35.5000],
                'service_count' => 10
            ],
            // Orta mesafe - 150-200km
            'Adana' => [
                'districts' => ['Seyhan', 'Yüreğir'],
                'lat_range' => [36.9800, 37.0200],
                'lng_range' => [35.3000, 35.3500],
                'service_count' => 8
            ],
            'Konya' => [
                'districts' => ['Meram', 'Karatay'],
                'lat_range' => [37.8500, 37.9000],
                'lng_range' => [32.4500, 32.5000],
                'service_count' => 8
            ],
            // Uzak şehirler - 300km+
            'Ankara' => [
                'districts' => ['Çankaya', 'Keçiören'],
                'lat_range' => [39.9000, 39.9500],
                'lng_range' => [32.8000, 32.9000],
                'service_count' => 5
            ],
            'İstanbul' => [
                'districts' => ['Kadıköy', 'Beşiktaş'],
                'lat_range' => [41.0000, 41.1000],
                'lng_range' => [28.9000, 29.1000],
                'service_count' => 5
            ]
        ];

        $serviceTypes = ['plumbing', 'electrical', 'cleaning', 'appliance', 'computer', 'phone', 'other'];
        
        $serviceNames = [
            'plumbing' => ['Tesisatçı', 'Su Tesisatı', 'Kalorifer Servisi', 'Kombi Tamiri'],
            'electrical' => ['Elektrikçi', 'Elektrik Servisi', 'Aydınlatma', 'Elektrik Tamiri'],
            'cleaning' => ['Temizlik', 'Ev Temizliği', 'Ofis Temizliği', 'Derin Temizlik'],
            'appliance' => ['Beyaz Eşya', 'Buzdolabı Tamiri', 'Çamaşır Makinesi', 'Fırın Tamiri'],
            'computer' => ['Bilgisayar Tamiri', 'Laptop Servisi', 'Yazılım Desteği', 'Donanım Tamiri'],
            'phone' => ['Telefon Tamiri', 'Ekran Değişimi', 'Batarya Değişimi', 'Yazılım Güncelleme'],
            'other' => ['Genel Tamir', 'Montaj Hizmetleri', 'Tadilat', 'Bakım Onarım']
        ];

        $firstNames = ['Ahmet', 'Mehmet', 'Ali', 'Hasan', 'İbrahim', 'Mustafa', 'Ömer', 'Fatma', 'Ayşe', 'Zeynep'];
        $lastNames = ['Yılmaz', 'Kaya', 'Demir', 'Şahin', 'Çelik', 'Yıldız', 'Özkan', 'Arslan', 'Doğan', 'Kurt'];

        $serviceId = 1;

        foreach ($cities as $cityName => $cityData) {
            for ($i = 0; $i < $cityData['service_count']; $i++) {
                $serviceType = $serviceTypes[array_rand($serviceTypes)];
                $firstName = $firstNames[array_rand($firstNames)];
                $lastName = $lastNames[array_rand($lastNames)];
                $serviceName = $serviceNames[$serviceType][array_rand($serviceNames[$serviceType])];
                $district = $cityData['districts'][array_rand($cityData['districts'])];

                // Rastgele koordinat üret
                $latitude = $cityData['lat_range'][0] + 
                           (($cityData['lat_range'][1] - $cityData['lat_range'][0]) * rand(0, 1000) / 1000);
                $longitude = $cityData['lng_range'][0] + 
                            (($cityData['lng_range'][1] - $cityData['lng_range'][0]) * rand(0, 1000) / 1000);

                // User oluştur
                $user = User::create([
                    'name' => $firstName . ' ' . $lastName,
                    'email' => 'service' . $serviceId . '@' . strtolower($cityName) . '.com',
                    'password' => bcrypt('password123'),
                    'phone' => '0' . rand(500, 599) . ' ' . rand(100, 999) . ' ' . rand(1000, 9999),
                    'role' => 'service',
                    'email_verified_at' => now()
                ]);

                // ServiceProvider oluştur
                ServiceProvider::create([
                    'user_id' => $user->id,
                    'company_name' => $firstName . ' ' . $serviceName,
                    'service_type' => $serviceType,
                    'city' => $cityName,
                    'district' => $district,
                    'address' => $district . ' Mahallesi, ' . $cityName,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'description' => $cityName . ' bölgesinde profesyonel ' . $serviceName . ' hizmeti.',
                    'rating' => 3.5 + (rand(0, 150) / 100),
                    'total_reviews' => rand(5, 50),
                    'working_hours' => '08:00-18:00',
                    'status' => 'active',
                    'is_verified' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $serviceId++;
            }
        }

        $this->command->info('Kahramanmaraş bölgesi için ' . ($serviceId - 1) . ' servis sağlayıcı eklendi!');
        $this->command->info('Mesafe tabanlı test senaryosu hazır.');
    }
}
