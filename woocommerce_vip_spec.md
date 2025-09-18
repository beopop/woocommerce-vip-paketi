# WooCommerce VIP & Paketi - Tehnička Specifikacija i Progress Tracker

## 🎯 PROJEKTNI CILJEVI

Kreiranje WooCommerce plugina koji implementira:
1. **VIP članstvo** sa dinamičkim cenama i VIP kodovima
2. **Variabilne pakete** sa popustima za regularne i VIP korisnike
3. **Kompatibilnost sa Woodmart temom**
4. **🇷🇸 OBAVEZNO: Kompletan prevod na srpski jezik** - svi tekstovi, poruke, labels i interface elementi moraju biti na srpskom jeziku

---

## 📋 PROGRESS TRACKER

### ✅ Završeno
- [ ] **Setup projekta** - kreiranje osnovne strukture plugina
- [ ] **Database setup** - kreiranje tabela i meta polja
- [ ] **VIP role sistem** - implementacija korisničkih uloga

### 🔄 U toku
- [ ] **Trenutni task:** [Označiti šta se trenutno radi]

### ⏳ Sledeće na redu
- [ ] **Lista narednih taskova**

### 📝 Poslednja sesija
**Datum:** [YYYY-MM-DD HH:MM]
**Status:** [Kratko objašnjenje gde se stalo]
**Sledeći korak:** [Šta treba uraditi dalje]

---

## 🏗️ ARHITEKTURA PLUGINA

### Struktura direktorijuma
```
woocommerce-vip-paketi/
├── woocommerce-vip-paketi.php (glavni fajl)
├── includes/
│   ├── class-wvp-activator.php
│   ├── class-wvp-deactivator.php
│   ├── class-wvp-loader.php
│   ├── class-wvp-core.php
│   ├── admin/
│   │   ├── class-wvp-admin.php
│   │   ├── class-wvp-admin-vip-codes.php
│   │   ├── class-wvp-admin-packages.php
│   │   └── partials/ (admin templates)
│   ├── public/
│   │   ├── class-wvp-public.php
│   │   ├── class-wvp-pricing.php
│   │   ├── class-wvp-checkout.php
│   │   └── partials/ (frontend templates)
│   ├── database/
│   │   └── class-wvp-database.php
│   └── integrations/
│       └── class-wvp-woodmart.php
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
└── documentation/
    ├── user-manual.md
    ├── developer-docs.md
    └── changelog.md
```

---

## 📊 DATABASE SCHEMA

### wp_wvp_codes tabela
```sql
CREATE TABLE wp_wvp_codes (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    code varchar(50) NOT NULL UNIQUE,
    email varchar(100) DEFAULT NULL,
    domain varchar(100) DEFAULT NULL,
    max_uses int(11) DEFAULT 1,
    current_uses int(11) DEFAULT 0,
    expires_at datetime DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status enum('active','inactive','expired','used') DEFAULT 'active',
    PRIMARY KEY (id),
    KEY idx_code (code),
    KEY idx_email (email),
    KEY idx_status (status)
);
```

### Meta polja za proizvode
- `_wvp_vip_price` - VIP cena
- `_wvp_enable_vip_pricing` - da li je VIP pricing omogućen
- `_wvp_package_allowed` - da li je proizvod dozvoljen u paketima
- `_wvp_package_config` - konfiguracija paketa (JSON)

---

## 🎨 I. VIP ČLANSTVO - DETALJNE SPECIFIKACIJE

### 1.1 Uloge i kompatibilnost

#### Kompatibilnost sa postojećim pluginovima:
- **WooCommerce Memberships** - provera aktivnog članstva
- **WooCommerce Subscriptions** - provera aktivne pretplate
- **Woodmart tema** - prilagođavanje prikaza cena

#### VIP status logika:
```php
function is_user_vip($user_id = null) {
    // 1. Proveri WooCommerce Memberships
    // 2. Proveri WooCommerce Subscriptions  
    // 3. Proveri custom VIP role
    // 4. Proveri aktivne VIP kodove
}
```

### 1.2 Prikaz i obračun cena

#### Za VIP korisnike:
- Regularna cena se **potpuno zamenjuje** VIP cenom
- Prikaz samo VIP cene na svim mestima
- Obračun u korpi/checkout-u koristi VIP cenu

#### Za ne-VIP korisnike:
- Prikaz regularne cene + VIP cena (označena)
- Obračun koristi regularnu cenu
- VIP cena služi kao "teaser"

#### Lokacije prikaza:
- Shop stranica (grid/list)
- Single product stranica
- Quick view (Woodmart specifično)
- Cart stranica
- Checkout stranica
- Order stranica
- Email notifikacije

### 1.3 VIP kod na Checkout-u

#### UI elementi:
```html
<div class="wvp-checkout-vip-section">
    <h3>Već ste VIP korisnik? 
        <span class="wvp-tooltip" data-tip="Unesite VIP kod za pristup posebnim cenama">❓</span>
    </h3>
    <div class="wvp-code-input-wrapper">
        <input type="text" id="wvp_code" placeholder="Unesite VIP kod">
        <button type="button" id="wvp_verify_code">Potvrdi</button>
    </div>
    <div id="wvp_code_messages"></div>
</div>
```

#### Logički tokovi:

**Gost korisnik + validan kod:**
1. Validacija koda
2. Kreiranje novog naloga sa email-om iz koda (ili unešenim)
3. Dodela VIP statusa
4. Automatski login
5. Refresh cena u checkout-u
6. Email notifikacija

**Gost + postojeći email:**
1. Validacija koda
2. Pronalaženje postojećeg naloga
3. Dodela VIP statusa postojećem nalogu
4. Automatski login
5. Refresh cena
6. Email notifikacija

**Ulogovan korisnik:**
1. Validacija koda
2. Dodela VIP statusa trenutnom nalogu
3. Refresh cena
4. Email notifikacija

#### AJAX endpoint:
```php
wp_ajax_wvp_verify_code
wp_ajax_nopriv_wvp_verify_code
```

### 1.4 Admin podešavanja - VIP

#### Tabs struktura:
1. **General Settings**
   - Enable/Disable VIP pricing
   - VIP role configuration
   - Integration settings (Memberships, Subscriptions)

2. **Price Display**
   - VIP price label
   - Non-VIP user display format
   - CSS styling options
   - Position settings

3. **VIP Codes**
   - CRUD interface
   - Bulk import/export (CSV)
   - Usage analytics

4. **Email Notifications**
   - Templates za različite događaje
   - Enable/disable opcije
   - Recipient settings

---

## 📦 II. PAKETI - DETALJNE SPECIFIKACIJE

### 2.1 Globalna selekcija proizvoda

#### Admin interface:
```php
// Settings stranica sa listom svih simple proizvoda
// Checkbox za svaki proizvod
// Bulk actions: Enable/Disable for packages
// Search i filter opcije
```

#### Database storage:
```php
// wp_options: wvp_package_allowed_products = array of product IDs
update_option('wvp_package_allowed_products', $product_ids);
```

### 2.2 Kreiranje variabilnog paketa

#### Custom Post Type: `wvp_package`
```php
register_post_type('wvp_package', [
    'public' => true,
    'show_in_menu' => false, // Dodaje se u plugin menu
    'supports' => ['title', 'editor', 'thumbnail']
]);
```

#### Meta polja paketa:
- `_wvp_allowed_products` - Array dozvoljenih product IDs
- `_wvp_min_items` - Minimalno artikala
- `_wvp_max_items` - Maksimalno artikala  
- `_wvp_package_sizes` - Array veličina (2,3,4,5,6...)
- `_wvp_regular_discounts` - Array popusta za regularne korisnike
- `_wvp_vip_discounts` - Array popusta za VIP korisnike
- `_wvp_allow_coupons` - Da li su kuponi dozvoljeni

#### Admin metabox-ovi:
1. **Package Configuration**
2. **Allowed Products** 
3. **Discount Rules**
4. **Display Settings**

### 2.3 Dinamički obračun cena

#### Frontend workflow:
1. Korisnik bira veličinu paketa (varijacija)
2. Prikazuje se lista dozvoljenih proizvoda
3. Korisnik bira artikle (do maksimuma)
4. Real-time obračun cene sa popustima
5. Add to cart sa finalnom cenom

#### Cena kalkulacija:
```php
function calculate_package_price($package_id, $selected_products, $package_size, $user_id) {
    $base_price = array_sum(get_product_prices($selected_products));
    $regular_discount = get_regular_discount($package_id, $package_size);
    $vip_discount = is_user_vip($user_id) ? get_vip_discount($package_id, $package_size) : 0;
    
    // Primeni popuste
    $final_price = $base_price * (1 - $regular_discount/100) * (1 - $vip_discount/100);
    
    return $final_price;
}
```

### 2.4 Tabela popusta

#### Prikaz na package stranici:
```html
<table class="wvp-discount-table">
    <thead>
        <tr>
            <th>Veličina paketa</th>
            <th>Regularni popust</th>
            <th class="vip-column">VIP popust</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>2 artikla</td>
            <td>5%</td>
            <td class="vip-highlight">10%</td>
        </tr>
        <!-- ... -->
    </tbody>
</table>
```

#### Conditional prikaz:
- Ne-VIP korisnici: VIP kolona blurred/marked kao "Samo za VIP članove"
- VIP korisnici: VIP kolona highlighted

---

## 🔧 III. WOODMART TEMA INTEGRACIJA

### 3.1 Hook prioriteti
```php
// Cene - visoki prioritet da override-uje temu
add_filter('woocommerce_get_price_html', 'wvp_modify_price_html', 20, 2);
add_filter('woocommerce_product_get_price', 'wvp_get_vip_price', 20, 2);

// Woodmart specifični hookovi
add_filter('woodmart_product_price', 'wvp_woodmart_price', 10, 2);
add_filter('woodmart_quick_view_price', 'wvp_woodmart_quick_view_price', 10, 2);
```

### 3.2 AJAX kompatibilnost
```javascript
// Intercept Woodmart AJAX add to cart
$(document).on('added_to_cart', function(event, fragments, cart_hash, $button) {
    // Update VIP pricing display
    wvp_refresh_vip_prices();
});
```

### 3.3 CSS Override-i
```css
/* Woodmart tema specifični stilovi */
.woodmart-product-grid .wvp-vip-price { /* ... */ }
.woodmart-quick-view .wvp-vip-price { /* ... */ }
.woodmart-sticky-atc .wvp-vip-price { /* ... */ }
```

---

## 🚀 IV. REDOSLED RAZVOJA (MILESTONES)

### Milestone 1: VIP Osnova (Dani 1-3)
**Cilj:** Funkcionalni VIP sistem sa osnovnim prikazom cena

**Taskovi:**
- [ ] **1.1** Setup osnovne strukture plugina
- [ ] **1.2** Kreiranje database tabela i instalacija
- [ ] **1.3** Implementacija VIP role sistema
- [ ] **1.4** Osnovna admin stranica za VIP podešavanja
- [ ] **1.5** Price hooks za VIP/ne-VIP prikaz
- [ ] **1.6** Osnovna kompatibilnost sa Woodmart temom

**Definition of Done:**
- VIP korisnici vide VIP cene umesto regularnih
- Ne-VIP korisnici vide obe cene
- Admin može da podešava VIP pricing
- Radi na Woodmart temi (shop, single product)

### Milestone 2: VIP Kodovi na Checkout-u (Dani 4-6)  
**Cilj:** Kompletan VIP kod sistem na checkout stranici

**Taskovi:**
- [ ] **2.1** Admin CRUD interface za VIP kodove
- [ ] **2.2** Checkout UI komponente (input, dugme, messages)
- [ ] **2.3** AJAX validation endpoint
- [ ] **2.4** Auto-registracija gost korisnika
- [ ] **2.5** Auto-login postojećih korisnika
- [ ] **2.6** Email notifikacije sistem
- [ ] **2.7** Error handling i user feedback

**Definition of Done:**
- Svi described tokovi za VIP kodove rade
- Email notifikacije se šalju
- Cene se automatski ažuriraju nakon koda
- Proper error handling za sve edge case-ove

### Milestone 3: Paketi - Osnova (Dani 7-9)
**Cilj:** Osnovni paket sistem sa admin interface-om

**Taskovi:**
- [ ] **3.1** Globalna selekcija proizvoda u admin-u
- [ ] **3.2** Custom post type za pakete
- [ ] **3.3** Admin metabox-ovi za paket konfiguraciju
- [ ] **3.4** Validacija paket konfiguracije
- [ ] **3.5** Database struktura za pakete

**Definition of Done:**
- Admin može da kreira pakete sa osnovnim podešavanjima
- Globalna lista proizvoda radi
- Paket konfiguracija se čuva i validira

### Milestone 4: Paketi - Frontend (Dani 10-12)
**Cilj:** Frontend prikaz paketa sa dinamičkim cenama

**Taskovi:**
- [ ] **4.1** Frontend template za paket stranicu
- [ ] **4.2** JavaScript za izbor proizvoda
- [ ] **4.3** Real-time kalkulacija cena
- [ ] **4.4** Tabela popusta sa conditional prikazom
- [ ] **4.5** Add to cart funkcionalnost
- [ ] **4.6** Woodmart kompatibilnost za pakete

**Definition of Done:**
- Korisnici mogu da biraju pakete i proizvode
- Cene se kalkuliraju u real-time-u
- VIP/ne-VIP razlike su vidljive
- Add to cart radi ispravno

### Milestone 5: Finalizacija i QA (Dani 13-15)
**Cilj:** Poliranje, testiranje i dokumentacija

**Taskovi:**
- [ ] **5.1** CSS polishing za sve komponente
- [ ] **5.2** JavaScript optimization i debugging
- [ ] **5.3** Kompletno testiranje sa kuponima
- [ ] **5.4** Cross-browser testing
- [ ] **5.5** User i developer dokumentacija
- [ ] **5.6** Performance optimization

**Definition of Done:**
- Svi bug-ovi su rešeni
- Dokumentacija je kompletna
- Plugin je spreman za production

---

## 📋 KONTROLNE TAČKE

### Pre svakog rada - Checklist:
- [ ] Pročitao kompletnu specifikaciju
- [ ] Proverio progress tracker
- [ ] Identifikovao trenutni milestone
- [ ] Označio sledeći task za rad

### Tokom rada - Obaveze:
- [ ] Ažuriraj progress tracker posle svakog task-a
- [ ] Dokumentuj sve značajne promene
- [ ] Piši clean, komentarisan kod
- [ ] Testiraj svaku funkcionalnost pre prelaska na sledeću

### Posle svakog rada - Checklist:
- [ ] Ažuriraj "Poslednja sesija" sekciju
- [ ] Commituj sve promene u git
- [ ] Ažuriraj dokumentaciju ako je potrebno
- [ ] Označio sledeći task koji treba uraditi

---

## 📝 DOKUMENTACIJA LOG

### Development Log
**Format:** [YYYY-MM-DD HH:MM] - [Task] - [Status] - [Opis]

```
[2024-XX-XX XX:XX] - Setup projekta - Započeto - Kreirana osnovna struktura plugina
[2024-XX-XX XX:XX] - Database setup - Završeno - Kreirana wp_wvp_codes tabela
```

### Bug Tracker
**Format:** [Severity] - [Opis] - [Status] - [Rešenje]

```
[High] - VIP cene se ne prikazuju u Woodmart quick view - Otvoreno - Needs investigation
[Medium] - Email template nije responsive - Zatvoreno - Dodati media queries
```

### Feature Requests / Improvements
```
- Dodati bulk export/import za VIP kodove
- Implementirati analytics dashboard za pakete
- Dodati integration sa WooCommerce Points and Rewards
```

---

## 🔗 LINKS & RESURSI

### WooCommerce Dokumentacija:
- [Hooks Reference](https://woocommerce.github.io/code-reference/hooks/hooks.html)
- [Product Data](https://github.com/woocommerce/woocommerce/wiki/Product-Data-Schema)
- [Cart/Checkout](https://woocommerce.github.io/code-reference/classes/WC-Cart.html)

### Woodmart Dokumentacija:
- [Developer Docs](https://woodmart.xtemos.com/docs/)
- [Child Theme Guide](https://woodmart.xtemos.com/docs/child-theme/)

### WordPress Best Practices:
- [Plugin Development](https://developer.wordpress.org/plugins/)
- [Database API](https://developer.wordpress.org/apis/database/)
- [Security](https://developer.wordpress.org/apis/security/)

---

## ⚙️ KONFIGURACIJA ENVIRONMENT-a

### Potrebni plugini za testing:
- WooCommerce (latest)
- WooCommerce Memberships (optional, za testing)
- WooCommerce Subscriptions (optional, za testing)
- Woodmart tema

### Development setup:
```bash
# Clone repository
git clone [repo-url] woocommerce-vip-paketi

# Install dependencies (ako koristimo composer)
composer install

# Setup local database
wp db create wvp_testing
```

### Testing checklist:
- [ ] WordPress multisite compatibility
- [ ] PHP 7.4+ compatibility  
- [ ] WooCommerce versions 6.0+
- [ ] Različiti payment gateway-i
- [ ] Različiti shipping metodi
- [ ] Mobile responsiveness

---

**📍 NAPOMENA ZA CLAUDE CODE:**

**Svaki put kada počinješ rad:**
1. Otvori ovaj fajl PRIMEIRO
2. Pročitaj kompletnu specifikaciju  
3. Proveri Progress Tracker da vidiš gde si stao
4. Nastavi rad od tačno označenog mesta
5. Ažuriraj progress posle svakog task-a

**Nikad ne radi delimično - uvek završi kompletan task pre prelaska na sledeći!**

**Ovaj fajl je SINGLE SOURCE OF TRUTH za ceo projekat!**