# WooCommerce VIP Paketi - WordPress Plugin

🇷🇸 **Srpski WordPress Plugin za VIP pakete sa Health Quiz sistemom**

## 📋 Opis

WooCommerce VIP Paketi je kompletan WordPress plugin koji omogućava kreiranje VIP paketa sa integrovanim health quiz sistemom i AI analizom.

## ✨ Ključne Funkcionalnosti

### 🎯 Health Quiz Sistem
- **Auto-save funkcionalnost** - Automatsko čuvanje odgovora tokom popunjavanja
- **Multi-step navigacija** - Postupno vođenje kroz pitanja
- **AI integracija** - Automatska analiza rezultata preko OpenAI
- **Personalizovane preporuke** - Preporučeni proizvodi na osnovu odgovora
- **Admin panel** - Kompletno upravljanje rezultatima i pitanjima

### 🛒 VIP Paketi
- **Kreiranje paketa** - Kombinovanje proizvoda sa popustima
- **Fleksibilni popusti** - Različiti popusti za različite veličine paketa
- **WooCommerce integracija** - Potpuna kompatibilnost sa WooCommerce
- **Automatska cena** - Automatsko izračunavanje cena sa popustima

### 🎨 Admin Panel
- **Upravljanje pitanjima** - Dodavanje/editovanje health quiz pitanja
- **Rezultati anketa** - Pregled i analiza korisničkih odgovora
- **AI integracija** - Konfiguracija OpenAI API-ja
- **Export funkcionalnost** - Izvoz rezultata u CSV format

## 🔧 Tehničke Specifikacije

### Sistem Zahtevi
- WordPress 5.0+
- WooCommerce 3.0+
- PHP 7.4+
- MySQL 5.6+

### Fajl Struktura
```
woocommerce-vip-paketi/
├── 📄 woocommerce-vip-paketi.php (Glavni plugin fajl)
├── 📁 includes/ (Core funkcionalnost)
│   ├── 📁 health-quiz/ (Health quiz sistem)
│   ├── 📁 admin/ (Admin paneli)
│   ├── 📁 public/ (Frontend funkcije)
│   └── 📁 database/ (Baza podataka)
├── 📁 assets/ (CSS, JS, slike)
├── 📁 languages/ (Prevodi)
└── 📄 Dokumentacija
```

## 🚀 Instalacija

1. **Download** plugin fajlove
2. **Upload** u `/wp-content/plugins/woocommerce-vip-paketi/`
3. **Aktiviraj** plugin kroz WordPress admin
4. **Konfiguriši** podešavanja u WP Admin → VIP Paketi

## ⚙️ Konfiguracija

### Health Quiz Setup
1. Idite na **VIP Paketi → Health Quiz → Pitanja**
2. Dodajte pitanja sa opcijama odgovora
3. Konfiguriši AI integraciju u podešavanjima
4. Postavite shortcode `[wvp_health_quiz]` na željenu stranicu

### VIP Paketi Setup
1. **VIP Paketi → Paketi** - Kreirajte nove pakete
2. Dodajte proizvode u paket
3. Postavite popuste za različite veličine
4. Konfiguriši prikaz na frontend-u

## 🔗 Shortcodes

- `[wvp_health_quiz]` - Prikazuje health quiz
- `[wvp_packages]` - Lista dostupnih paketa
- `[wvp_vip_status]` - Status korisnika (VIP/Regular)

## 🛠️ Recent Updates

### Version 1.0 - Health Quiz Fixes
- ✅ **Fixed auto-save** - Odgovori se čuvaju automatski
- ✅ **Unified save system** - Jedan sistem čuvanja umesto tri
- ✅ **Improved admin panel** - Bolje čitanje podataka
- ✅ **Better session management** - Poboljšano praćenje sesije
- ✅ **Enhanced error handling** - Naprednije rukovanje greškama

## 👥 Podrška

Za podršku i pitanja:
- **Email**: beohosting.com@gmail.com
- **GitHub Issues**: [Prijavite problem](https://github.com/beopop/woocommerce-vip-paketi/issues)

## 📄 Licenca

GNU General Public License v2.0

---

**🚀 Plugin kreiran uz pomoć Claude Code AI asistenta**