<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceProvider;
use Illuminate\Support\Facades\DB;

class KahramanmarasServicesSeeder extends Seeder
{
    public function run()
    {
        // Kahramanmaraş koordinat aralıkları
        $kahramanmarasCoords = [
            'lat_min' => 37.5500,
            'lat_max' => 37.6000,
            'lng_min' => 36.9000,
            'lng_max' => 36.9500
        ];

        // Servis türü mapping
        $serviceTypeMapping = [
            'plumbing' => 1,
            'electrical' => 2,
            'cleaning' => 3,
            'appliance' => 4,
            'computer' => 5,
            'phone' => 6,
            'other' => 7
        ];
        
        // Kahramanmaraş'a özgü servis sağlayıcıları
        $services = [
            [
                'name' => 'Maraş Tesisatçı Ahmet Usta',
                'service_type' => 'plumbing',
                'district' => 'Dulkadiroğlu',
                'description' => 'Kahramanmaraş\'ta 15 yıllık deneyimle tesisat işleri. Su kaçağı, petek temizliği, klozet montajı.',
                'price' => '₺150-300',
                'phone' => '0344 555 0101'
            ],
            [
                'name' => 'Elektrik Ustası Mehmet Bey',
                'service_type' => 'electrical',
                'district' => 'Onikişubat',
                'description' => 'Elektrik tesisatı, anahtar priz montajı, elektrik panosu bakımı. 7/24 hizmet.',
                'price' => '₺100-250',
                'phone' => '0344 555 0102'
            ],
            [
                'name' => 'Maraş Temizlik Hizmetleri',
                'service_type' => 'cleaning',
                'district' => 'Dulkadiroğlu',
                'description' => 'Ev, ofis, cam temizliği. Halı yıkama ve koltuk temizliği hizmeti.',
                'price' => '₺80-200',
                'phone' => '0344 555 0103'
            ],
            [
                'name' => 'Beyaz Eşya Tamir Merkezi',
                'service_type' => 'appliance',
                'district' => 'Onikişubat',
                'description' => 'Buzdolabı, çamaşır makinesi, bulaşık makinesi tamiri. Garantili hizmet.',
                'price' => '₺120-400',
                'phone' => '0344 555 0104'
            ],
            [
                'name' => 'Bilgisayar Doktoru Servis',
                'service_type' => 'computer',
                'district' => 'Dulkadiroğlu',
                'description' => 'Bilgisayar tamiri, format, virüs temizliği, donanım değişimi.',
                'price' => '₺100-350',
                'phone' => '0344 555 0105'
            ],
            [
                'name' => 'Cep Telefonu Hastanesi',
                'service_type' => 'phone',
                'district' => 'Onikişubat',
                'description' => 'Telefon ekran değişimi, batarya değişimi, yazılım güncelleme.',
                'price' => '₺80-300',
                'phone' => '0344 555 0106'
            ],
            [
                'name' => 'Usta Ali Genel Tamir',
                'service_type' => 'other',
                'district' => 'Dulkadiroğlu',
                'description' => 'Mobilya montajı, kapı pencere tamiri, genel ev tamirhanesi.',
                'price' => '₺70-200',
                'phone' => '0344 555 0107'
            ],
            [
                'name' => 'Maraş Su Tesisatçısı',
                'service_type' => 'plumbing',
                'district' => 'Onikişubat',
                'description' => 'Banyo mutfak tesisatı, şofben montajı, lavabo musluk tamiri.',
                'price' => '₺120-280',
                'phone' => '0344 555 0108'
            ],
            [
                'name' => 'Voltaj Elektrik Servisi',
                'service_type' => 'electrical',
                'district' => 'Dulkadiroğlu',
                'description' => 'Ev elektrik tesisatı, LED aydınlatma, elektrik panosu yenileme.',
                'price' => '₺90-220',
                'phone' => '0344 555 0109'
            ],
            [
                'name' => 'Temiz Ev Temizlik',
                'service_type' => 'cleaning',
                'district' => 'Onikişubat',
                'description' => 'Derin temizlik, taşınma temizliği, inşaat sonrası temizlik.',
                'price' => '₺100-250',
                'phone' => '0344 555 0110'
            ],
            [
                'name' => 'Arçelik Yetkili Servis',
                'service_type' => 'appliance',
                'district' => 'Dulkadiroğlu',
                'description' => 'Arçelik marka beyaz eşya tamiri. Orijinal yedek parça garantisi.',
                'price' => '₺150-500',
                'phone' => '0344 555 0111'
            ],
            [
                'name' => 'Tekno Bilgisayar Servisi',
                'service_type' => 'computer',
                'district' => 'Onikişubat',
                'description' => 'Laptop tamiri, masaüstü bilgisayar kurulumu, ağ kurulumu.',
                'price' => '₺120-400',
                'phone' => '0344 555 0112'
            ],
            [
                'name' => 'Akıllı Telefon Tamirhanesi',
                'service_type' => 'phone',
                'district' => 'Dulkadiroğlu',
                'description' => 'iPhone, Samsung, Xiaomi tamiri. Hızlı ve güvenilir hizmet.',
                'price' => '₺100-350',
                'phone' => '0344 555 0113'
            ],
            [
                'name' => 'Marangoz Hasan Usta',
                'service_type' => 'other',
                'district' => 'Onikişubat',
                'description' => 'Dolap montajı, mutfak dolapları, özel ahşap işleri.',
                'price' => '₺150-400',
                'phone' => '0344 555 0114'
            ],
            [
                'name' => 'Kalorifer Tesisatçısı',
                'service_type' => 'plumbing',
                'district' => 'Dulkadiroğlu',
                'description' => 'Kalorifer sistemi kurulumu, kombi bakımı, petek temizliği.',
                'price' => '₺200-450',
                'phone' => '0344 555 0115'
            ],
            [
                'name' => 'Işık Elektrik Hizmetleri',
                'service_type' => 'electrical',
                'district' => 'Onikişubat',
                'description' => 'Avize montajı, spot aydınlatma, elektrik arıza giderme.',
                'price' => '₺80-200',
                'phone' => '0344 555 0116'
            ],
            [
                'name' => 'Profesyonel Temizlik',
                'service_type' => 'cleaning',
                'district' => 'Dulkadiroğlu',
                'description' => 'Ofis temizliği, apartman temizliği, cam silme hizmetleri.',
                'price' => '₺120-300',
                'phone' => '0344 555 0117'
            ],
            [
                'name' => 'Bosch Yetkili Servisi',
                'service_type' => 'appliance',
                'district' => 'Onikişubat',
                'description' => 'Bosch marka beyaz eşya tamiri ve bakımı. Uzman teknisyen.',
                'price' => '₺140-480',
                'phone' => '0344 555 0118'
            ],
            [
                'name' => 'Bilişim Teknolojileri',
                'service_type' => 'computer',
                'district' => 'Dulkadiroğlu',
                'description' => 'Sunucu kurulumu, network çözümleri, güvenlik kameraları.',
                'price' => '₺200-600',
                'phone' => '0344 555 0119'
            ],
            [
                'name' => 'Mobil Tamir Servisi',
                'service_type' => 'phone',
                'district' => 'Onikişubat',
                'description' => 'Evde telefon tamiri, tablet tamiri, aksesuar satışı.',
                'price' => '₺90-280',
                'phone' => '0344 555 0120'
            ]
        ];

        foreach ($services as $index => $serviceData) {
            // Önce User oluştur
            $user = \App\Models\User::create([
                'name' => $serviceData['name'],
                'email' => 'service' . ($index + 201) . '@kahramanmaras.com',
                'password' => bcrypt('password123'),
                'phone' => $serviceData['phone'],
                'role' => 'service',
                'email_verified_at' => now()
            ]);

            // Rastgele koordinat üret (Kahramanmaraş sınırları içinde)
            $latitude = $kahramanmarasCoords['lat_min'] + 
                       (($kahramanmarasCoords['lat_max'] - $kahramanmarasCoords['lat_min']) * rand(0, 1000) / 1000);
            $longitude = $kahramanmarasCoords['lng_min'] + 
                        (($kahramanmarasCoords['lng_max'] - $kahramanmarasCoords['lng_min']) * rand(0, 1000) / 1000);

            // Rastgele rating üret (3.5 - 5.0 arası)
            $rating = 3.5 + (rand(0, 150) / 100);

            // ServiceProvider oluştur
            ServiceProvider::create([
                'user_id' => $user->id,
                'company_name' => $serviceData['name'],
                'service_type' => $serviceData['service_type'],
                'city' => 'Kahramanmaraş',
                'district' => $serviceData['district'],
                'address' => $serviceData['district'] . ' Mahallesi, Kahramanmaraş',
                'latitude' => $latitude,
                'longitude' => $longitude,
                'description' => $serviceData['description'],
                'rating' => round($rating, 1),
                'total_reviews' => rand(5, 50),
                'working_hours' => '08:00-18:00',
                'status' => 'active',
                'is_verified' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $this->command->info('Kahramanmaraş için 20 servis sağlayıcı eklendi!');
    }
}
