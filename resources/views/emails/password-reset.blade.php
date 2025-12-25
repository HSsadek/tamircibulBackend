@component('mail::message')
# Şifre Sıfırlama

Merhaba{{ $userName ? ' ' . $userName : '' }},

TamirciBul hesabınız için şifre sıfırlama talebinde bulundunuz. Aşağıdaki butona tıklayarak yeni şifrenizi belirleyebilirsiniz.

@component('mail::button', ['url' => $resetUrl, 'color' => 'primary'])
Şifremi Sıfırla
@endcomponent

**Önemli Bilgiler:**
- Bu link 1 saat süreyle geçerlidir
- Eğer şifre sıfırlama talebinde bulunmadıysanız, bu e-postayı görmezden gelebilirsiniz
- Güvenliğiniz için linki kimseyle paylaşmayın

Herhangi bir sorunuz varsa bizimle iletişime geçebilirsiniz.

Teşekkürler,<br>
{{ config('app.name') }} Ekibi

---
Eğer "Şifremi Sıfırla" butonuna tıklayamıyorsanız, aşağıdaki linki kopyalayıp tarayıcınıza yapıştırın:
{{ $resetUrl }}
@endcomponent