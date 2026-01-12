<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\ServiceProvider;
use App\Models\Customer;
use App\Models\ServiceRequest;
use App\Models\Review;
use App\Models\Message;

class ComprehensiveSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Disable foreign key checks
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Clear existing data
        Message::truncate();
        Review::truncate();
        ServiceRequest::truncate();
        ServiceProvider::truncate();
        Customer::truncate();
        User::where('email', '!=', 'admin@tamircibul.com')->delete();
        
        // Re-enable foreign key checks
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create admin user
        $admin = User::firstOrCreate([
            'email' => 'admin@tamircibul.com'
        ], [
            'name' => 'Admin User',
            'password' => Hash::make('admin123'),
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);

        // Create customers
        $customers = [
            [
                'name' => 'Ahmet Müşteri',
                'email' => 'customer@example.com',
                'phone' => '05551234567',
                'city' => 'İstanbul',
                'district' => 'Kadıköy',
                'address' => 'Kadıköy Mahallesi, Bağdat Caddesi No:123'
            ],
            [
                'name' => 'Ayşe Yılmaz',
                'email' => 'ayse@example.com',
                'phone' => '05552345678',
                'city' => 'İstanbul',
                'district' => 'Üsküdar',
                'address' => 'Üsküdar Merkez, Çamlıca Sokak No:45'
            ],
            [
                'name' => 'Mehmet Kaya',
                'email' => 'mehmet@example.com',
                'phone' => '05553456789',
                'city' => 'Ankara',
                'district' => 'Çankaya',
                'address' => 'Çankaya Mahallesi, Atatürk Bulvarı No:67'
            ],
            [
                'name' => 'Fatma Demir',
                'email' => 'fatma@example.com',
                'phone' => '05554567890',
                'city' => 'İzmir',
                'district' => 'Konak',
                'address' => 'Konak Merkez, Kordon Boyu No:89'
            ],
            [
                'name' => 'Ali Özkan',
                'email' => 'ali@example.com',
                'phone' => '05555678901',
                'city' => 'İstanbul',
                'district' => 'Beşiktaş',
                'address' => 'Beşiktaş Merkez, Barbaros Bulvarı No:12'
            ]
        ];

        foreach ($customers as $customerData) {
            $user = User::create([
                'name' => $customerData['name'],
                'email' => $customerData['email'],
                'phone' => $customerData['phone'],
                'password' => Hash::make('password123'),
                'role' => User::ROLE_CUSTOMER,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
            ]);

            Customer::create([
                'user_id' => $user->id,
                'city' => $customerData['city'],
                'district' => $customerData['district'],
                'address' => $customerData['address'],
                'latitude' => $this->getRandomLatitude($customerData['city']),
                'longitude' => $this->getRandomLongitude($customerData['city']),
            ]);
        }

        // Create service providers - Generate for all Turkish cities
        $turkishCities = [
            'İstanbul' => ['Kadıköy', 'Beşiktaş', 'Şişli', 'Beyoğlu', 'Fatih', 'Üsküdar', 'Bakırköy', 'Zeytinburnu'],
            'Ankara' => ['Çankaya', 'Keçiören', 'Yenimahalle', 'Mamak', 'Sincan', 'Etimesgut', 'Altındağ', 'Gölbaşı'],
            'İzmir' => ['Konak', 'Karşıyaka', 'Bornova', 'Bayraklı', 'Buca', 'Çiğli', 'Gaziemir', 'Balçova'],
            'Bursa' => ['Osmangazi', 'Nilüfer', 'Yıldırım', 'Mudanya', 'Gemlik', 'İnegöl', 'Orhangazi', 'Kestel'],
            'Antalya' => ['Muratpaşa', 'Kepez', 'Konyaaltı', 'Aksu', 'Döşemealtı', 'Alanya', 'Manavgat', 'Serik'],
            'Adana' => ['Seyhan', 'Yüreğir', 'Çukurova', 'Sarıçam', 'Karaisalı', 'Pozantı', 'Kozan', 'Ceyhan'],
            'Konya' => ['Meram', 'Karatay', 'Selçuklu', 'Ereğli', 'Akşehir', 'Beyşehir', 'Cihanbeyli', 'Kulu'],
            'Gaziantep' => ['Şahinbey', 'Şehitkamil', 'Oğuzeli', 'Nizip', 'Nurdağı', 'Araban', 'Yavuzeli', 'Karkamış'],
            'Kayseri' => ['Melikgazi', 'Kocasinan', 'Talas', 'İncesu', 'Develi', 'Yahyalı', 'Tomarza', 'Sarıoğlan'],
            'Eskişehir' => ['Odunpazarı', 'Tepebaşı', 'Çifteler', 'Mahmudiye', 'Mihalıççık', 'Sarıcakaya', 'Seyitgazi', 'Sivrihisar'],
            'Elazığ' => ['Merkez', 'Ağın', 'Alacakaya', 'Arıcak', 'Baskil', 'Karakoçan', 'Keban', 'Kovancılar']
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
                'Profesyonel tesisatçı hizmeti. Su kaçağı, tıkanıklık açma, musluk tamiri.',
                'Uzman tesisatçı. Kombi bakımı, kalorifer tamiri, su tesisatı.',
                'Sıhhi tesisat uzmanı. Banyo tadilat, mutfak tesisatı, su kaçağı tamiri.',
                'Tesisatçı ustası. Petek temizliği, boru değişimi, sıcak su tamiri.',
                'Deneyimli tesisatçı. Klozet tamiri, lavabo montajı, duş kabini kurulumu.'
            ],
            'electrical' => [
                'Elektrik arıza ve montaj hizmetleri. Sigorta değişimi, kablo çekimi.',
                'Uzman elektrikçi. Aydınlatma sistemleri, priz montajı, elektrik panosu.',
                'Elektrik tamircisi. Kısa devre tamiri, elektrik tesisatı, LED montajı.',
                'Elektrikçi ustası. Ev elektriği, ofis elektriği, güvenlik sistemleri.',
                'Deneyimli elektrikçi. Jeneratör bakımı, elektrik ölçümü, kablo tamiri.'
            ],
            'cleaning' => [
                'Ev ve ofis temizlik hizmetleri. Derin temizlik, cam silme.',
                'Profesyonel temizlik. Halı yıkama, koltuk temizliği, ev temizliği.',
                'Temizlik uzmanı. Ofis temizliği, cam temizliği, zemin cilalama.',
                'Temizlik hizmetleri. Taşınma temizliği, inşaat sonrası temizlik.',
                'Ev temizlik servisi. Günlük temizlik, haftalık temizlik, aylık temizlik.'
            ],
            'appliance' => [
                'Beyaz eşya tamiri. Buzdolabı, çamaşır makinesi, bulaşık makinesi.',
                'Beyaz eşya servisi. Fırın tamiri, mikrodalga tamiri, aspiratör.',
                'Beyaz eşya uzmanı. Kurutma makinesi, derin dondurucu, su sebili.',
                'Beyaz eşya tamircisi. Ocak tamiri, davlumbaz, ankastre fırın.',
                'Beyaz eşya servisi. Klima tamiri, şofben, kombi bakımı.'
            ],
            'computer' => [
                'Bilgisayar tamiri ve yazılım desteği. Laptop tamiri, veri kurtarma.',
                'Bilgisayar uzmanı. Donanım tamiri, yazılım kurulumu, virüs temizliği.',
                'Bilgisayar servisi. Ekran tamiri, klavye değişimi, RAM yükseltme.',
                'Bilgisayar tamircisi. Anakart tamiri, güç kaynağı, soğutma sistemi.',
                'Bilgisayar teknisyeni. Ağ kurulumu, güvenlik yazılımı, sistem optimizasyonu.'
            ],
            'phone' => [
                'Cep telefonu tamiri. Ekran değişimi, batarya değişimi, su hasarı.',
                'Telefon servisi. iPhone tamiri, Samsung tamiri, Huawei tamiri.',
                'Telefon tamircisi. Tablet tamiri, şarj soketi, hoparlör tamiri.',
                'Mobil cihaz tamiri. Kamera tamiri, tuş takımı, yazılım güncelleme.',
                'Telefon uzmanı. Ekran koruyucu, kılıf montajı, veri aktarımı.'
            ],
            'other' => [
                'Genel tamir ve montaj hizmetleri. Mobilya montajı, tadilat işleri.',
                'Usta hizmetleri. Kapı tamiri, pencere tamiri, dolap montajı.',
                'Genel tamirci. Musluk tamiri, kilit değişimi, menteşe tamiri.',
                'Montaj uzmanı. TV montajı, raf montajı, ayna asma.',
                'Tadilat ustası. Boya badana, fayans döşeme, laminat parke.'
            ]
        ];

        $serviceProviders = [];
        
        // Her şehir için 20 servis sağlayıcı oluştur
        foreach ($turkishCities as $city => $districts) {
            for ($i = 0; $i < 20; $i++) {
                $serviceType = $serviceTypes[array_rand($serviceTypes)];
                $firstName = $firstNames[array_rand($firstNames)];
                $lastName = $lastNames[array_rand($lastNames)];
                $companyName = $firstName . ' ' . $serviceNames[$serviceType][array_rand($serviceNames[$serviceType])];
                $district = $districts[array_rand($districts)];
                
                $serviceProviders[] = [
                    'name' => $firstName . ' ' . $lastName,
                    'email' => strtolower($firstName . '.' . $lastName . $i . '.' . strtolower($city)) . '@example.com',
                    'phone' => '0555' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                    'company_name' => $companyName,
                    'service_type' => $serviceType,
                    'description' => $descriptions[$serviceType][array_rand($descriptions[$serviceType])],
                    'city' => $city,
                    'district' => $district,
                    'address' => $district . ' Mahallesi ' . rand(1, 999) . '. Sokak No:' . rand(1, 200),
                    'working_hours' => rand(7, 9) . ':00-' . rand(17, 19) . ':00',
                    'rating' => round(rand(35, 50) / 10, 1), // 3.5 - 5.0 arası
                    'total_reviews' => rand(10, 200),
                    'total_jobs' => rand(5, 150),
                ];
            }
        }

        foreach ($serviceProviders as $providerData) {
            $user = User::create([
                'name' => $providerData['name'],
                'email' => $providerData['email'],
                'phone' => $providerData['phone'],
                'password' => Hash::make('password123'),
                'role' => User::ROLE_SERVICE,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
            ]);

            ServiceProvider::create([
                'user_id' => $user->id,
                'company_name' => $providerData['company_name'],
                'service_type' => $providerData['service_type'],
                'description' => $providerData['description'],
                'city' => $providerData['city'],
                'district' => $providerData['district'],
                'address' => $providerData['address'],
                'latitude' => $this->getRandomLatitude($providerData['city']),
                'longitude' => $this->getRandomLongitude($providerData['city']),
                'working_hours' => $providerData['working_hours'],
                'rating' => $providerData['rating'],
                'total_reviews' => $providerData['total_reviews'],
                'total_jobs' => $providerData['total_jobs'],
                'is_verified' => true,
                'status' => ServiceProvider::STATUS_ACTIVE,
            ]);
        }

        // Create fixed test service provider accounts
        $testProviders = [
            [
                'name' => 'Fatma Elektrik',
                'email' => 'fatma.elektrik@example.com',
                'phone' => '05551234501',
                'company_name' => 'Fatma Elektrik Servisi',
                'service_type' => 'electrical',
                'description' => 'Profesyonel elektrik tamiri ve montaj hizmetleri.',
                'city' => 'İstanbul',
                'district' => 'Kadıköy',
                'address' => 'Kadıköy Merkez, Test Sokak No:1',
                'working_hours' => '08:00-18:00',
                'rating' => 4.8,
                'total_reviews' => 50,
                'total_jobs' => 75,
            ],
            [
                'name' => 'Hasan Temizlik',
                'email' => 'hasan.temizlik@example.com',
                'phone' => '05551234502',
                'company_name' => 'Hasan Temizlik Hizmetleri',
                'service_type' => 'cleaning',
                'description' => 'Ev ve ofis temizlik hizmetleri.',
                'city' => 'İstanbul',
                'district' => 'Beşiktaş',
                'address' => 'Beşiktaş Merkez, Test Sokak No:2',
                'working_hours' => '09:00-17:00',
                'rating' => 4.5,
                'total_reviews' => 30,
                'total_jobs' => 45,
            ],
            [
                'name' => 'Ali Tesisat',
                'email' => 'service@example.com',
                'phone' => '05551234503',
                'company_name' => 'Ali Tesisatçılık',
                'service_type' => 'plumbing',
                'description' => 'Tesisat tamiri ve montaj hizmetleri.',
                'city' => 'İstanbul',
                'district' => 'Şişli',
                'address' => 'Şişli Merkez, Test Sokak No:3',
                'working_hours' => '08:00-19:00',
                'rating' => 4.9,
                'total_reviews' => 100,
                'total_jobs' => 120,
            ]
        ];

        foreach ($testProviders as $providerData) {
            $user = User::create([
                'name' => $providerData['name'],
                'email' => $providerData['email'],
                'phone' => $providerData['phone'],
                'password' => Hash::make('password123'),
                'role' => User::ROLE_SERVICE,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
            ]);

            ServiceProvider::create([
                'user_id' => $user->id,
                'company_name' => $providerData['company_name'],
                'service_type' => $providerData['service_type'],
                'description' => $providerData['description'],
                'city' => $providerData['city'],
                'district' => $providerData['district'],
                'address' => $providerData['address'],
                'latitude' => $this->getRandomLatitude($providerData['city']),
                'longitude' => $this->getRandomLongitude($providerData['city']),
                'working_hours' => $providerData['working_hours'],
                'rating' => $providerData['rating'],
                'total_reviews' => $providerData['total_reviews'],
                'total_jobs' => $providerData['total_jobs'],
                'is_verified' => true,
                'status' => ServiceProvider::STATUS_ACTIVE,
            ]);
        }

        // Create some pending service providers
        $pendingProviders = [
            [
                'name' => 'Yeni Tamirci',
                'email' => 'yeni@example.com',
                'phone' => '05551122334',
                'company_name' => 'Yeni Tamir Servisi',
                'service_type' => 'electrical',
                'description' => 'Yeni başlayan elektrikçi. Uygun fiyat.',
                'city' => 'İstanbul',
                'district' => 'Bakırköy',
            ],
            [
                'name' => 'Bekleyen Servis',
                'email' => 'bekleyen@example.com',
                'phone' => '05552233445',
                'company_name' => 'Bekleyen Temizlik',
                'service_type' => 'cleaning',
                'description' => 'Onay bekleyen temizlik servisi.',
                'city' => 'Ankara',
                'district' => 'Mamak',
            ]
        ];

        foreach ($pendingProviders as $providerData) {
            $user = User::create([
                'name' => $providerData['name'],
                'email' => $providerData['email'],
                'phone' => $providerData['phone'],
                'password' => Hash::make('password123'),
                'role' => User::ROLE_SERVICE,
                'status' => User::STATUS_PENDING,
            ]);

            ServiceProvider::create([
                'user_id' => $user->id,
                'company_name' => $providerData['company_name'],
                'service_type' => $providerData['service_type'],
                'description' => $providerData['description'],
                'city' => $providerData['city'],
                'district' => $providerData['district'],
                'status' => ServiceProvider::STATUS_PENDING,
            ]);
        }

        // Create service requests
        $this->createServiceRequests();

        // Create reviews
        $this->createReviews();

        $this->command->info('Comprehensive database seeded successfully!');
        $this->command->info('=== Test Accounts ===');
        $this->command->info('Admin: admin@tamircibul.com / admin123');
        $this->command->info('Customer: customer@example.com / password123');
        $this->command->info('Service Provider: service@example.com / password123');
        $this->command->info('=== Additional Accounts ===');
        $this->command->info('Customer: ayse@example.com / password123');
        $this->command->info('Service Provider: fatma.elektrik@example.com / password123');
        $this->command->info('Service Provider: hasan.temizlik@example.com / password123');
    }

    private function createServiceRequests()
    {
        $customers = User::where('role', User::ROLE_CUSTOMER)->get();
        $serviceProviders = User::where('role', User::ROLE_SERVICE)->where('status', User::STATUS_ACTIVE)->get();

        $requests = [
            [
                'title' => 'Mutfak Lavabo Tıkanıklığı',
                'description' => 'Mutfak lavabom tıkandı, su akmuyor. Acil çözüm gerekiyor.',
                'service_type' => 'plumbing',
                'city' => 'İstanbul',
                'district' => 'Kadıköy',
                'address' => 'Kadıköy Merkez, Ev Sokak No:1',
                'budget_min' => 100,
                'budget_max' => 200,
                'priority' => 'high',
                'status' => 'pending'
            ],
            [
                'title' => 'Elektrik Kesintisi Sorunu',
                'description' => 'Evde elektrik gidiyor geliyor. Sigorta sürekli atıyor.',
                'service_type' => 'electrical',
                'city' => 'İstanbul',
                'district' => 'Beşiktaş',
                'address' => 'Beşiktaş Merkez, Elektrik Sokak No:5',
                'budget_min' => 150,
                'budget_max' => 300,
                'priority' => 'urgent',
                'status' => 'accepted'
            ],
            [
                'title' => 'Genel Ev Temizliği',
                'description' => 'Haftalık genel ev temizliği hizmeti istiyorum.',
                'service_type' => 'cleaning',
                'city' => 'Ankara',
                'district' => 'Çankaya',
                'address' => 'Çankaya Merkez, Temiz Sokak No:3',
                'budget_min' => 200,
                'budget_max' => 400,
                'priority' => 'medium',
                'status' => 'completed'
            ],
            [
                'title' => 'Buzdolabı Çalışmıyor',
                'description' => 'Buzdolabım soğutmuyor. Motor sesi geliyor ama soğuk yapmıyor.',
                'service_type' => 'appliance',
                'city' => 'İstanbul',
                'district' => 'Kadıköy',
                'address' => 'Kadıköy Merkez, Soğuk Sokak No:7',
                'budget_min' => 200,
                'budget_max' => 500,
                'priority' => 'high',
                'status' => 'in_progress'
            ],
            [
                'title' => 'Laptop Yavaş Çalışıyor',
                'description' => 'Laptopum çok yavaş açılıyor ve donuyor. Temizlik ve format gerekebilir.',
                'service_type' => 'computer',
                'city' => 'İzmir',
                'district' => 'Konak',
                'address' => 'Konak Merkez, Bilgisayar Caddesi No:9',
                'budget_min' => 100,
                'budget_max' => 250,
                'priority' => 'medium',
                'status' => 'pending'
            ],
            [
                'title' => 'Telefon Ekranı Kırık',
                'description' => 'iPhone ekranım kırıldı. Dokunmatik çalışıyor ama görüntü bozuk.',
                'service_type' => 'phone',
                'city' => 'İstanbul',
                'district' => 'Şişli',
                'address' => 'Şişli Merkez, Telefon Sokak No:11',
                'budget_min' => 300,
                'budget_max' => 600,
                'priority' => 'medium',
                'status' => 'completed'
            ]
        ];

        foreach ($requests as $index => $requestData) {
            $customer = $customers->random();
            $serviceProvider = null;
            
            if (in_array($requestData['status'], ['accepted', 'in_progress', 'completed'])) {
                $serviceProvider = $serviceProviders->random();
            }

            ServiceRequest::create([
                'customer_id' => $customer->id,
                'service_provider_id' => $serviceProvider?->id,
                'service_type' => $requestData['service_type'],
                'title' => $requestData['title'],
                'description' => $requestData['description'],
                'address' => $requestData['address'],
                'city' => $requestData['city'],
                'district' => $requestData['district'],
                'latitude' => $this->getRandomLatitude($requestData['city']),
                'longitude' => $this->getRandomLongitude($requestData['city']),
                'budget_min' => $requestData['budget_min'],
                'budget_max' => $requestData['budget_max'],
                'priority' => $requestData['priority'],
                'status' => $requestData['status'],
                'completed_at' => $requestData['status'] === 'completed' ? now()->subDays(rand(1, 30)) : null,
                'created_at' => now()->subDays(rand(1, 60)),
            ]);
        }
    }

    private function createReviews()
    {
        $completedRequests = ServiceRequest::where('status', 'completed')->get();
        
        $reviewTexts = [
            'Çok memnun kaldım. Hızlı ve kaliteli hizmet.',
            'Profesyonel yaklaşım. Kesinlikle tavsiye ederim.',
            'Zamanında geldi, işini titizlikle yaptı.',
            'Uygun fiyat, kaliteli hizmet. Teşekkürler.',
            'Sorunumu çok hızlı çözdü. Mükemmel!',
            'Güler yüzlü ve işinin ehli. 5 yıldız hak ediyor.',
            'Temiz çalışma, güvenilir servis.',
            'Beklentilerimi aştı. Tekrar tercih edeceğim.'
        ];

        foreach ($completedRequests as $request) {
            if ($request->service_provider_id && rand(1, 10) > 3) { // %70 chance of review
                Review::create([
                    'service_request_id' => $request->id,
                    'customer_id' => $request->customer_id,
                    'service_provider_id' => $request->service_provider_id,
                    'rating' => rand(4, 5),
                    'comment' => $reviewTexts[array_rand($reviewTexts)],
                    'is_verified' => true,
                    'created_at' => $request->completed_at->addHours(rand(1, 48)),
                ]);
            }
        }
    }

    private function getRandomLatitude($city)
    {
        $coordinates = [
            'İstanbul' => ['min' => 40.9, 'max' => 41.2],
            'Ankara' => ['min' => 39.8, 'max' => 40.0],
            'İzmir' => ['min' => 38.3, 'max' => 38.5],
            'Bursa' => ['min' => 40.1, 'max' => 40.3],
            'Antalya' => ['min' => 36.8, 'max' => 37.0],
            'Adana' => ['min' => 36.9, 'max' => 37.1],
            'Konya' => ['min' => 37.8, 'max' => 38.0],
            'Gaziantep' => ['min' => 37.0, 'max' => 37.2],
            'Kayseri' => ['min' => 38.7, 'max' => 38.9],
            'Eskişehir' => ['min' => 39.7, 'max' => 39.9],
            'Elazığ' => ['min' => 38.6, 'max' => 38.8],
        ];

        $range = $coordinates[$city] ?? $coordinates['İstanbul'];
        return $range['min'] + (($range['max'] - $range['min']) * rand(0, 1000) / 1000);
    }

    private function getRandomLongitude($city)
    {
        $coordinates = [
            'İstanbul' => ['min' => 28.8, 'max' => 29.2],
            'Ankara' => ['min' => 32.7, 'max' => 32.9],
            'İzmir' => ['min' => 27.0, 'max' => 27.3],
            'Bursa' => ['min' => 29.0, 'max' => 29.2],
            'Antalya' => ['min' => 30.6, 'max' => 30.8],
            'Adana' => ['min' => 35.2, 'max' => 35.4],
            'Konya' => ['min' => 32.4, 'max' => 32.6],
            'Gaziantep' => ['min' => 37.3, 'max' => 37.5],
            'Kayseri' => ['min' => 35.4, 'max' => 35.6],
            'Eskişehir' => ['min' => 30.5, 'max' => 30.7],
            'Elazığ' => ['min' => 39.1, 'max' => 39.4],
        ];

        $range = $coordinates[$city] ?? $coordinates['İstanbul'];
        return $range['min'] + (($range['max'] - $range['min']) * rand(0, 1000) / 1000);
    }
}
