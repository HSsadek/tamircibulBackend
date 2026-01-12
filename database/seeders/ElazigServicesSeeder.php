<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\ServiceProvider;

class ElazigServicesSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Elazığ koordinatları
        $elazigLat = 38.6748;
        $elazigLng = 39.2264;
        
        // Elazığ ilçeleri
        $districts = [
            'Merkez', 'Ağın', 'Alacakaya', 'Arıcak', 'Baskil', 'Karakoçan', 
            'Keban', 'Kovancılar', 'Maden', 'Palu', 'Sivrice'
        ];

        $serviceTypes = ['plumbing', 'electrical', 'cleaning', 'appliance', 'computer', 'phone', 'other'];
        
        $serviceNames = [
            'plumbing' => ['Tesisatçılık', 'Su Tesisatı', 'Sıhhi Tesisat', 'Kalorifer Servisi', 'Kombi Servisi'],
            'electrical' => ['Elektrik Servisi', 'Elektrikçi', 'Elektrik Tamiri', 'Aydınlatma', 'Elektrik Montajı'],
            'cleaning' => ['Temizlik Hizmetleri', 'Ev Temizliği', 'Ofis Temizliği', 'Derin Temizlik', 'Cam Temizliği'],
            'appliance' => ['Beyaz Eşya Servisi', 'Buzdolabı Tamiri', 'Çamaşır Makinesi', 'Bulaşık Makinesi', 'Fırın Tamiri'],
            'computer' => ['Bilgisayar Tamiri', 'Laptop Servisi', 'Yazılım Desteği', 'Donanım Tamiri', 'Veri Kurtarma'],
            'phone' => ['Telefon Tamiri', 'Cep Telefonu', 'Tablet Tamiri', 'Ekran Değişimi', 'Batarya Değişimi'],
            'other' => ['Genel Tamir', 'Usta Hizmetleri', 'Tadilat', 'Montaj Hizmetleri', 'Bakım Onarım']
        ];

        $firstNames = ['Ahmet', 'Mehmet', 'Mustafa', 'Ali', 'Hasan', 'Hüseyin', 'İbrahim', 'İsmail', 'Ömer', 'Osman',
                      'Fatma', 'Ayşe', 'Emine', 'Hatice', 'Zeynep', 'Elif', 'Meryem', 'Şule', 'Seda', 'Burcu'];
        $lastNames = ['Yılmaz', 'Kaya', 'Demir', 'Şahin', 'Çelik', 'Yıldız', 'Yıldırım', 'Öztürk', 'Aydin', 'Özdemir',
                     'Arslan', 'Doğan', 'Kılıç', 'Aslan', 'Çetin', 'Kara', 'Koç', 'Kurt', 'Özkan', 'Şimşek'];

        $descriptions = [
            'plumbing' => [
                'Elazığ\'da profesyonel tesisatçı hizmeti. Su kaçağı, tıkanıklık açma, musluk tamiri.',
                'Uzman tesisatçı. Kombi bakımı, kalorifer tamiri, su tesisatı.',
                'Sıhhi tesisat uzmanı. Banyo tadilat, mutfak tesisatı, su kaçağı tamiri.',
                'Tesisatçı ustası. Petek temizliği, boru değişimi, sıcak su tamiri.',
                'Deneyimli tesisatçı. Klozet tamiri, lavabo montajı, duş kabini kurulumu.'
            ],
            'electrical' => [
                'Elazığ elektrik arıza ve montaj hizmetleri. Sigorta değişimi, kablo çekimi.',
                'Uzman elektrikçi. Aydınlatma sistemleri, priz montajı, elektrik panosu.',
                'Elektrik tamircisi. Kısa devre tamiri, elektrik tesisatı, LED montajı.',
                'Elektrikçi ustası. Ev elektriği, ofis elektriği, güvenlik sistemleri.',
                'Deneyimli elektrikçi. Jeneratör bakımı, elektrik ölçümü, kablo tamiri.'
            ],
            'cleaning' => [
                'Elazığ ev ve ofis temizlik hizmetleri. Derin temizlik, cam silme.',
                'Profesyonel temizlik. Halı yıkama, koltuk temizliği, ev temizliği.',
                'Temizlik uzmanı. Ofis temizliği, cam temizliği, zemin cilalama.',
                'Temizlik hizmetleri. Taşınma temizliği, inşaat sonrası temizlik.',
                'Ev temizlik servisi. Günlük temizlik, haftalık temizlik, aylık temizlik.'
            ],
            'appliance' => [
                'Elazığ beyaz eşya tamiri. Buzdolabı, çamaşır makinesi, bulaşık makinesi.',
                'Beyaz eşya servisi. Fırın tamiri, mikrodalga tamiri, aspiratör.',
                'Beyaz eşya uzmanı. Kurutma makinesi, derin dondurucu, su sebili.',
                'Beyaz eşya tamircisi. Ocak tamiri, davlumbaz, ankastre fırın.',
                'Beyaz eşya servisi. Klima tamiri, şofben, kombi bakımı.'
            ],
            'computer' => [
                'Elazığ bilgisayar tamiri ve yazılım desteği. Laptop tamiri, veri kurtarma.',
                'Bilgisayar uzmanı. Donanım tamiri, yazılım kurulumu, virüs temizliği.',
                'Bilgisayar servisi. Ekran tamiri, klavye değişimi, RAM yükseltme.',
                'Bilgisayar tamircisi. Anakart tamiri, güç kaynağı, soğutma sistemi.',
                'Bilgisayar teknisyeni. Ağ kurulumu, güvenlik yazılımı, sistem optimizasyonu.'
            ],
            'phone' => [
                'Elazığ cep telefonu tamiri. Ekran değişimi, batarya değişimi, su hasarı.',
                'Telefon servisi. iPhone tamiri, Samsung tamiri, Huawei tamiri.',
                'Telefon tamircisi. Tablet tamiri, şarj soketi, hoparlör tamiri.',
                'Mobil cihaz tamiri. Kamera tamiri, tuş takımı, yazılım güncelleme.',
                'Telefon uzmanı. Ekran koruyucu, kılıf montajı, veri aktarımı.'
            ],
            'other' => [
                'Elazığ genel tamir ve montaj hizmetleri. Mobilya montajı, tadilat işleri.',
                'Usta hizmetleri. Kapı tamiri, pencere tamiri, dolap montajı.',
                'Genel tamirci. Musluk tamiri, kilit değişimi, menteşe tamiri.',
                'Montaj uzmanı. TV montajı, raf montajı, ayna asma.',
                'Tadilat ustası. Boya badana, fayans döşeme, laminat parke.'
            ]
        ];

        // Elazığ için 30 servis sağlayıcı oluştur
        for ($i = 0; $i < 30; $i++) {
            $serviceType = $serviceTypes[array_rand($serviceTypes)];
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $companyName = $firstName . ' ' . $serviceNames[$serviceType][array_rand($serviceNames[$serviceType])];
            $district = $districts[array_rand($districts)];
            
            // Elazığ merkez etrafında rastgele koordinatlar oluştur
            $latOffset = (rand(-100, 100) / 1000); // ±0.1 derece
            $lngOffset = (rand(-100, 100) / 1000); // ±0.1 derece
            $latitude = $elazigLat + $latOffset;
            $longitude = $elazigLng + $lngOffset;
            
            $user = User::create([
                'name' => $firstName . ' ' . $lastName,
                'email' => strtolower($firstName . '.' . $lastName . $i . '.elazig') . '@example.com',
                'phone' => '0424' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                'password' => Hash::make('password123'),
                'role' => User::ROLE_SERVICE,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
            ]);

            ServiceProvider::create([
                'user_id' => $user->id,
                'company_name' => $companyName,
                'service_type' => $serviceType,
                'description' => $descriptions[$serviceType][array_rand($descriptions[$serviceType])],
                'city' => 'Elazığ',
                'district' => $district,
                'address' => $district . ' Mahallesi ' . rand(1, 999) . '. Sokak No:' . rand(1, 200),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'working_hours' => rand(7, 9) . ':00-' . rand(17, 19) . ':00',
                'rating' => round(rand(35, 50) / 10, 1), // 3.5 - 5.0 arası
                'total_reviews' => rand(5, 100),
                'total_jobs' => rand(3, 80),
                'is_verified' => true,
                'status' => ServiceProvider::STATUS_ACTIVE,
            ]);
        }

        $this->command->info('Elazığ servisleri başarıyla eklendi!');
        $this->command->info('30 adet Elazığ servis sağlayıcısı oluşturuldu.');
    }
}