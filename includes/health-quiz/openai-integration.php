<?php
if (!defined('ABSPATH')) {
    exit;
}

class WVP_Health_Quiz_OpenAI {

    private $api_key;
    private $api_url = 'https://api.openai.com/v1/chat/completions';

    public function __construct() {
        $this->api_key = get_option('wvp_health_quiz_openai_api_key', '');
    }

    /**
     * Check if OpenAI is enabled and API key is set
     */
    public function is_enabled() {
        return get_option('wvp_health_quiz_openai_enabled', 0) && !empty($this->api_key);
    }

    /**
     * Analyze health quiz answers using OpenAI
     * @param array $questions - Array of questions
     * @param array $answers - Array of user answers
     * @param array $intensities - Array of intensity levels
     * @param array $user_data - User information
     * @return array|WP_Error - AI analysis result or error
     */
    public function analyze_health_quiz($questions, $answers, $intensities = array(), $user_data = array()) {
        if (!$this->is_enabled()) {
            return new WP_Error('openai_disabled', 'OpenAI integration is not enabled or API key is missing');
        }

        // Get available products for recommendations
        $products = $this->get_available_products();
        $package_logic = get_option('wvp_health_quiz_package_logic', 'most_symptoms');

        // Build the prompt for OpenAI
        $prompt = $this->build_analysis_prompt($questions, $answers, $intensities, $user_data, $products, $package_logic);

        // Make API call to OpenAI
        $response = $this->make_openai_request($prompt);

        if (is_wp_error($response)) {
            // Check if it's a timeout error and try fallback
            if ($response->get_error_code() === 'openai_timeout') {
                return $this->generate_fallback_analysis($questions, $answers, $intensities, $user_data, $products);
            }
            return $response;
        }

        // Parse and structure the response
        return $this->parse_openai_response($response);
    }

    /**
     * Build the prompt for OpenAI analysis
     */
    private function build_analysis_prompt($questions, $answers, $intensities, $user_data, $products, $logic) {
        $age = !empty($user_data['birth_year']) ? (date('Y') - $user_data['birth_year']) : 'nepoznat';
        $first_name = isset($user_data['first_name']) ? $user_data['first_name'] : 'korisnik';
        $last_name = isset($user_data['last_name']) ? $user_data['last_name'] : '';
        $full_name = trim($first_name . ' ' . $last_name);

        $prompt = "Ti si renomirani ekspert za holistička zdravstvena rešenja i prirodni wellness sa 20+ godina iskustva. ";
        $prompt .= "Specijalizovan si za personalizovane analize zdravstvenih upitnika, kreirajući detaljne preporuke ";
        $prompt .= "zasnovane na individualnim potrebama, godinama i specifičnim zdravstvenim problemima.\n\n";

        $prompt .= "PROFIL KORISNIKA:\n";
        $prompt .= "📋 Ime: {$full_name}\n";
        $prompt .= "🎂 Godine: {$age} (rođen/a " . $user_data['birth_year'] . ")\n";
        $prompt .= "🌍 Lokacija: {$user_data['location']}, {$user_data['country']}\n\n";

        // Enhanced age-based personalization
        if ($age !== 'nepoznat') {
            $prompt .= "PERSONALIZACIJA PO GODINAMA:\n";
            if ($age > 50) {
                $prompt .= "🎯 ZRELA OSOBA ({$age} godina): Fokus na održavanje vitalnosti, podršku zglobovima i kostima, ";
                $prompt .= "kardiovaskularno zdravlje, kognitivne funkcije i metaboličku podršku. ";
                $prompt .= "Veći značaj antioksidantima, omega kiselinama i specifičnim vitaminima za zrele godine.\n\n";
            } elseif ($age < 30) {
                $prompt .= "🎯 MLADA OSOBA ({$age} godina): Fokus na prevenciju, jačanje imuniteta, optimizaciju energije ";
                $prompt .= "i uspostavljanje zdravih navika za dugoročnu vitalnost. Detoksikacija, balansiranje hormona i podrška nervnom sistemu.\n\n";
            } else {
                $prompt .= "🎯 SREDNJA GODINA ({$age} godina): Balansiran pristup između prevencije i aktivnog rešavanja problema. ";
                $prompt .= "Upravljanje stresom, održavanje energije, podrška metabolizmu i priprema za zdravo starenje.\n\n";
            }
        }

        $prompt .= "PITANJA I ODGOVORI SA DOZIRANJEM:\n";
        foreach ($questions as $i => $question) {
            $answer = isset($answers[$i]) ? $answers[$i] : 'Nema odgovora';
            $intensity = '';
            if (isset($intensities[$i]) && $answer === 'Da') {
                $intensity_level = isset($question['intensity_levels'][intval($intensities[$i]) - 1]) ?
                    $question['intensity_levels'][intval($intensities[$i]) - 1] : '';
                $intensity = " (Intenzitet: {$intensity_level})";
            }

            $dosage_info = '';
            if ($answer === 'Da' && (!empty($question['ai_daily_dose']) || !empty($question['ai_monthly_box']))) {
                $dosage_info = "\n   Preporučena doza: ";
                if (!empty($question['ai_daily_dose'])) {
                    $dosage_info .= "Dnevno: {$question['ai_daily_dose']}";
                }
                if (!empty($question['ai_monthly_box'])) {
                    $dosage_info .= (!empty($question['ai_daily_dose']) ? ", " : "") . "Mesečno: {$question['ai_monthly_box']}";
                }
            }

            // Add recommended products info for "Da" answers
            $recommended_products_info = '';
            if ($answer === 'Da' && !empty($question['recommended_products']) && is_array($question['recommended_products'])) {
                $recommended_products_info = "\n   🎯 FOKUS PROIZVODI za ovaj problem: ";
                $recommended_product_names = array();
                foreach ($question['recommended_products'] as $product_id) {
                    foreach ($products as $product) {
                        if ($product['id'] == $product_id) {
                            $recommended_product_names[] = $product['name'];
                            break;
                        }
                    }
                }
                if (!empty($recommended_product_names)) {
                    $recommended_products_info .= implode(', ', $recommended_product_names);
                    $recommended_products_info .= " (PRIORITETNO za preporuke)";
                }
            }

            $prompt .= ($i + 1) . ". {$question['text']}\n   Odgovor: {$answer}{$intensity}{$dosage_info}{$recommended_products_info}\n\n";
        }

        $prompt .= "DOSTUPNI PROIZVODI (Čitaj OPISE PROIZVODA iz dozvoljenih proizvoda):\n";
        foreach ($products as $product) {
            $prompt .= "- {$product['name']}: {$product['description']}\n";
            $prompt .= "  VAŽNO: Koristi ovaj opis za objašnjenje zašto je proizvod dobar za korisnike probleme.\n";
        }

        $prompt .= "\nSPECIJALIZACIJA - KATEGORIJE PROIZVODA:\n";
        $prompt .= "1. ZELENI SOKOVI - detoksikacija, alkalizacija organizma, povećanje energije\n";
        $prompt .= "2. PROBIOTICI - poboljšanje digestije, jačanje imuniteta\n";
        $prompt .= "3. VITAMINI I MINERALI - opšte zdravlje, nadoknada nedostajućih nutričenata\n";
        $prompt .= "4. ADAPTOGENI - upravljanje stresom, povećanje energije i izdržljivosti\n";
        $prompt .= "5. ANTIOKSIDANTI - anti-aging efekti, zaštita od slobodnih radikala\n";
        $prompt .= "6. OMEGA MASNE KISELINE - zdravlje srca i krvnih sudova, funkcija mozga\n";
        $prompt .= "7. DIGESTIVNI ENZIMI - poboljšanje varenja i apsorpcije hrane\n";
        $prompt .= "8. BILJNI EKSTRAKTI - ciljanо rešavanje specifičnih zdravstvenih problema\n\n";

        // Get discount settings
        $discounts = $this->get_package_discounts();
        $vip_discount = $this->get_vip_additional_discount();

        $prompt .= "\nSISTEM KREIRANJA CUSTOM PAKETA:\n";
        $prompt .= "AI treba da kreira 3 CUSTOM PAKETA kombinacijom odabranih proizvoda sa automatskim popustima:\n\n";
        $prompt .= "PRAVILA ZA PAKETE:\n";
        $prompt .= "- 2 proizvoda: -{$discounts[2]}% popust\n";
        $prompt .= "- 3 proizvoda: -{$discounts[3]}% popust\n";
        $prompt .= "- 4 proizvoda: -{$discounts[4]}% popust\n";
        $prompt .= "- 6 proizvoda: -{$discounts[6]}% popust\n";
        $prompt .= "- VIP korisnici dobijaju dodatnih -{$vip_discount}% popust na sve pakete\n\n";
        $prompt .= "STRUKTURA PAKETA:\n";
        $prompt .= "- Paket 1: 2 proizvoda (starter paket)\n";
        $prompt .= "- Paket 2: 3-4 proizvoda (optimalni paket)\n";
        $prompt .= "- Paket 3: 4-6 proizvoda (premium paket)\n\n";

        // Count symptoms for package size recommendation
        $symptom_count = 0;
        $high_intensity_count = 0;

        foreach ($answers as $i => $answer) {
            if ($answer === 'Da') {
                $symptom_count++;

                // Check intensity level
                if (isset($intensities[$i])) {
                    $question = $questions[$i];
                    $max_intensity = isset($question['intensity_levels']) ? count($question['intensity_levels']) : 3;

                    // If intensity is in top third, count as high intensity
                    if ($intensities[$i] >= ceil($max_intensity * 0.67)) {
                        $high_intensity_count++;
                    }
                }
            }
        }

        $prompt .= "\nSTATISTIKE ODGOVORA:\n";
        $prompt .= "- Ukupno 'Da' odgovora (simptoma): {$symptom_count}\n";
        $prompt .= "- Visok intenzitet simptoma: {$high_intensity_count}\n";
        $prompt .= "- Godine korisnika: {$age}\n\n";

        // Add dosage calculation guidelines based on actual question data
        $prompt .= "DOZIRANJE I MESEČNE KUTIJE (Na osnovu preporučenih doza iz pitanja):\n";
        $prompt .= "- Koristi preporučene doze iz pitanja gde su specificirane\n";
        $prompt .= "- Za pitanja bez specifične doze: 1 kutija mesečno po proizvodu\n";
        $prompt .= "- Za intenzivne simptome: povećaj dozu prema preporuci iz pitanja\n";
        $prompt .= "- Za osobe starije od 50 godina: dodatne 0.2-0.5 kutije ako nije drugačije specificirano\n";
        $prompt .= "- 🎯 PRIORITET: Koristi FOKUS PROIZVODE označene kao 'PRIORITETNO' za pitanja sa 'Da' odgovorima\n";
        $prompt .= "- Prioritizuj proizvode koji odgovaraju na najveći broj simptoma sa 'Da' odgovorima\n";
        $prompt .= "- Kada postoje fokus proizvodi za problem, OBAVEZNO ih uključi u preporuke\n";
        $prompt .= "- Maksimalno 8 kutija mesečno po paketu\n";
        $prompt .= "- U objašnjenju navedi specifične doze iz preporučenih pitanja\n\n";

        $prompt .= "LOGIKA PREPORUČIVANJA: ";
        switch ($logic) {
            case 'most_symptoms':
                $prompt .= "Fokusiraj se na najveći broj simptoma. Preporuči pakete veličine na osnovu broja simptoma: 1-2 simptoma = 2 stavke, 3-4 simptoma = 4 stavke, 5+ simptoma = 6 stavki.";
                break;
            case 'severity_based':
                $prompt .= "Fokusiraj se na težinu simptoma i intenzitete. Ako ima mnogo visokih intenziteta, preporuči veće pakete.";
                break;
            case 'balanced':
                $prompt .= "Balansiran pristup između broja i težine simptoma. Uzmi u obzir i broj simptoma i njihove intenzitete.";
                break;
        }

        $prompt .= "\n\nKreiraj personalizovan zdravstveni izveštaj za {$full_name} ({$age} god):\n\n";

        $prompt .= "STRUKTURA:\n";
        $prompt .= "1. UVOD: Oslovi '{$first_name} {$last_name}', spomeni {$age} godina kao ključni faktor\n";
        $prompt .= "2. ANALIZA: Objasni glavne probleme i uzroke za {$age}-godišnjaka\n";
        $prompt .= "3. PREPORUKE: 3-4 konkretna saveta za uzrast {$age} godina\n";
        $prompt .= "4. PAKETI: 3 custom paketa sa dozama iz pitanja ili 1-2 kutije mesečno\n";
        $prompt .= "5. ZAKLJUČAK: Skor + motivacija\n\n";

        // Get custom AI restrictions and recommendations from admin settings
        $ai_restrictions = get_option('wvp_ai_restrictions', "STRIKTNE ZABRANE:\n1. NIKAD ne davaš medicinske dijagnoze\n2. NIKAD ne preporučuješ lekove ili farmaceutske proizvode\n3. NIKAD ne zamenjuješ lekarski pregled\n4. UVEK naglašavaš da su tvoje preporuke za prirodno zdravlje i wellness\n5. NIKAD ne garantuješ lečenje bolesti\n6. FOKUSIRAŠ se isključivo na prirodne proizvode i suplemente\n7. UVEK preporučuješ konsultaciju sa lekarom za ozbiljne zdravstvene probleme");

        $ai_recommendation_prompt = get_option('wvp_ai_recommendation_prompt', "PRIORITETI U PREPORUKAMA:\n- Za digestivne probleme: zeleni sokovi + probiotici\n- Za nizak imunitet: vitamin C + adaptogeni + probiotici\n- Za umor/nizak energiju: zeleni sokovi + B vitamini + adaptogeni\n- Za stres: adaptogeni + magnezijum + omega masne kiseline\n- Za anti-aging: antioksidanti + kolagen + omega masne kiseline");

        $prompt .= "ZABRANE I OGRANIČENJA:\n";
        $prompt .= $ai_restrictions . "\n\n";

        $prompt .= "PREPORUKE I PRIORITETI:\n";
        $prompt .= $ai_recommendation_prompt . "\n\n";
        // Get dosage priorities from admin settings
        $ai_dosage_priorities = get_option('wvp_ai_dosage_priorities', "DOZNI PRIORITETI:\n- Visok intenzitet simptoma: x1.5 preporučena doza\n- Godine 50+: +0.5 kutija mesečno\n- Kombinacija problema: saberi sve doze\n- Maksimum: 8 kutija po paketu\n- Minimum: 1 kutija po proizvodu");

        $prompt .= "PAKETI: Koristi IDs iz liste, 2-6 stavki, max 8 kutija mesečno. Format: {\"id\": X, \"size\": Y, \"monthly_boxes\": Z, \"reason\": \"objašnjenje\", \"product_explanations\": \"opis\"}\n\n";

        $prompt .= "SPECIFIČNI DOZNI PRIORITETI:\n";
        $prompt .= $ai_dosage_priorities . "\n\n";
        $prompt .= "Takođe, na osnovu odgovora, identifikuj slabe tačke na telu korisnika. Koristi sledeće oblasti: head, neck, chest, stomach, arms, legs, joints.\n";
        $prompt .= "Odredi nivo rizika za svaku oblast: low, moderate, high.\n\n";
        $prompt .= "Odgovori na srpskom jeziku u sledećem JSON formatu:\n";
        $prompt .= '{\n';
        $prompt .= '  "uvod": "Poštovani ' . $first_name . ' ' . $last_name . ', na osnovu detaljne analize vaših odgovora i uzimajući u obzir vaše ' . $age . ' godine života...",\n';
        $prompt .= '  "stanje_organizma": "Detaljno objašnjenje zdravstvenog stanja sa specifičnim uzrocima problema za ' . $age . '-godišnjaka...",\n';
        $prompt .= '  "preporuke": "Konkretni saveti za životne navike i wellness rešenja prilagođene vašim ' . $age . ' godinama i trenutnim potrebama...",\n';
        $prompt .= '  "lifestyle_recommendations": ["Savet 1", "Savet 2", "Savet 3"],\n';
        $prompt .= '  "proizvodi": [1,2,3],\n';
        $prompt .= '  "paketi": [{\n';
        $prompt .= '    "products": [product_id1, product_id2],\n';
        $prompt .= '    "size": 2,\n';
        $prompt .= '    "discount": ' . $discounts[2] . ',\n';
        $prompt .= '    "vip_discount": ' . $vip_discount . ',\n';
        $prompt .= '    "monthly_boxes": 1,\n';
        $prompt .= '    "reason": "Starter paket sa osnovnim proizvodima za...",\n';
        $prompt .= '    "product_explanations": "Objašnjenje zašto su odabrani ovi proizvodi"\n';
        $prompt .= '  }, {\n';
        $prompt .= '    "products": [product_id1, product_id2, product_id3],\n';
        $prompt .= '    "size": 3,\n';
        $prompt .= '    "discount": ' . $discounts[3] . ',\n';
        $prompt .= '    "vip_discount": ' . $vip_discount . ',\n';
        $prompt .= '    "monthly_boxes": 2,\n';
        $prompt .= '    "reason": "Optimalni paket sa kombinacijom proizvoda za...",\n';
        $prompt .= '    "product_explanations": "Objašnjenje zašto su odabrani ovi proizvodi"\n';
        $prompt .= '  }, {\n';
        $prompt .= '    "products": [product_id1, product_id2, product_id3, product_id4],\n';
        $prompt .= '    "size": 4,\n';
        $prompt .= '    "discount": ' . $discounts[4] . ',\n';
        $prompt .= '    "vip_discount": ' . $vip_discount . ',\n';
        $prompt .= '    "monthly_boxes": 3,\n';
        $prompt .= '    "reason": "Premium paket sa kompletnom kombinacijom za...",\n';
        $prompt .= '    "product_explanations": "Objašnjenje zašto su odabrani ovi proizvodi"\n';
        $prompt .= '  }],\n';
        $prompt .= '  "weak_points": [{"area": "stomach", "severity": "moderate", "description": "Opis problema"}],\n';
        $prompt .= '  "zaključak": "Personalizovan zaključak za ' . $first_name . '-a koji summira ključne preporuke i motiviše na akciju, uzimajući u obzir ' . $age . ' godina i specifične potrebe",\n';
        $prompt .= '  "skor": 75\n';
        $prompt .= '}';

        return $prompt;
    }

    /**
     * Make request to OpenAI API
     */
    private function make_openai_request($prompt) {
        $headers = array(
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json',
        );

        // Get AI settings from admin
        $ai_model = get_option('wvp_ai_model', 'gpt-4');
        $ai_temperature = floatval(get_option('wvp_ai_temperature', '0.7'));
        $ai_max_tokens = intval(get_option('wvp_ai_max_tokens', '2000'));
        $ai_system_prompt = get_option('wvp_ai_system_prompt', 'Ti si stručnjak za prirodno zdravlje i wellness koji se specijalizuje za zelene sokove, detoksikaciju, probiotike i holistički pristup zdravlju. Analiziraš zdravstvene upitnike i preporučuješ prirodne proizvode i pakete zasnovane na biljnim sastojcima, vitaminima, mineralima i suplementima za poboljšanje vitalnosti.');

        $body = array(
            'model' => $ai_model,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => $ai_system_prompt
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => $ai_max_tokens,
            'temperature' => $ai_temperature
        );

        // Get timeout from admin settings - increase default for complex AI responses
        $ai_timeout = intval(get_option('wvp_ai_timeout', '60'));

        $response = wp_remote_post($this->api_url, array(
            'headers' => $headers,
            'body' => json_encode($body),
            'timeout' => $ai_timeout
        ));

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();

            // Handle timeout errors specifically
            if (strpos($error_message, 'Operation timed out') !== false || strpos($error_message, 'cURL error 28') !== false) {
                return new WP_Error('openai_timeout', 'AI analiza je predugo trajala. Molimo pokušajte ponovo za nekoliko trenutaka. Ako se problem nastavi, povećaćemo vreme obrade.');
            }

            // Handle other connection errors
            if (strpos($error_message, 'cURL error') !== false) {
                return new WP_Error('openai_connection', 'Problemi sa povezivanjem na AI servis. Molimo proverite internetsku vezu i pokušajte ponovo.');
            }

            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            return new WP_Error('openai_api_error', 'OpenAI API error: ' . $response_code . ' - ' . $response_body);
        }

        $data = json_decode($response_body, true);

        if (!isset($data['choices'][0]['message']['content'])) {
            return new WP_Error('openai_response_error', 'Invalid OpenAI response format');
        }

        return $data['choices'][0]['message']['content'];
    }

    /**
     * Parse OpenAI response
     */
    private function parse_openai_response($response) {
        // Try to extract JSON from response
        $json_start = strpos($response, '{');
        $json_end = strrpos($response, '}');

        if ($json_start !== false && $json_end !== false) {
            $json_string = substr($response, $json_start, $json_end - $json_start + 1);
            $parsed = json_decode($json_string, true);

            if ($parsed) {
                return array(
                    'uvod' => isset($parsed['uvod']) ? $parsed['uvod'] : '',
                    'stanje_organizma' => isset($parsed['stanje_organizma']) ? $parsed['stanje_organizma'] : '',
                    'preporuke' => isset($parsed['preporuke']) ? $parsed['preporuke'] : '',
                    'lifestyle_recommendations' => isset($parsed['lifestyle_recommendations']) ? $parsed['lifestyle_recommendations'] : array(),
                    'proizvodi' => isset($parsed['proizvodi']) ? $parsed['proizvodi'] : array(),
                    'paketi' => isset($parsed['paketi']) ? $parsed['paketi'] : array(),
                    'weak_points' => isset($parsed['weak_points']) ? $parsed['weak_points'] : array(),
                    'zaključak' => isset($parsed['zaključak']) ? $parsed['zaključak'] : '',
                    'skor' => isset($parsed['skor']) ? intval($parsed['skor']) : 50,
                    'raw_response' => $response
                );
            }
        }

        // Fallback if JSON parsing fails
        return array(
            'stanje_organizma' => 'Analiza je obavljena, ali format odgovora nije mogao biti parsiran.',
            'preporuke' => 'Molimo kontaktirajte nas za detaljnije preporuke.',
            'proizvodi' => array(),
            'paketi' => array(),
            'weak_points' => array(),
            'skor' => 50,
            'raw_response' => $response
        );
    }

    /**
     * Get available products for recommendations
     */
    private function get_available_products() {
        // Get allowed products from AI integration settings
        $allowed_product_ids = get_option('wvp_ai_allowed_products', array());

        if (empty($allowed_product_ids)) {
            return array();
        }

        $result = array();

        foreach ($allowed_product_ids as $product_id) {
            $product = wc_get_product($product_id);

            if (!$product || $product->get_status() !== 'publish') {
                continue;
            }

            // Get AI characteristics for this product
            $characteristics = get_option('wvp_ai_product_characteristics_' . $product_id, '');

            $result[] = array(
                'id' => $product_id,
                'name' => $product->get_name(),
                'description' => $characteristics ?: $product->get_short_description(),
                'price' => $product->get_regular_price(),
                'sku' => $product->get_sku()
            );
        }

        return $result;
    }

    /**
     * Get discount percentages for package sizes
     */
    private function get_package_discounts() {
        return array(
            2 => get_option('wvp_ai_discount_2_products', 10),
            3 => get_option('wvp_ai_discount_3_products', 12),
            4 => get_option('wvp_ai_discount_4_products', 16),
            6 => get_option('wvp_ai_discount_6_products', 20)
        );
    }

    /**
     * Get VIP additional discount
     */
    private function get_vip_additional_discount() {
        return get_option('wvp_ai_vip_additional_discount', 10);
    }

    /**
     * Generate fallback analysis when OpenAI times out
     */
    private function generate_fallback_analysis($questions, $answers, $intensities, $user_data, $products) {
        $first_name = $user_data['first_name'] ?? '';
        $last_name = $user_data['last_name'] ?? '';
        $age = isset($user_data['birth_year']) ? (date('Y') - $user_data['birth_year']) : 0;

        // Count symptoms and calculate severity
        $positive_symptoms = 0;
        $total_intensity = 0;
        $problem_areas = array();

        for ($i = 0; $i < count($answers); $i++) {
            if (isset($answers[$i]) && strtolower($answers[$i]) === 'da') {
                $positive_symptoms++;
                $intensity = isset($intensities[$i]) ? intval($intensities[$i]) : 0;
                $total_intensity += $intensity;

                if (isset($questions[$i]['text'])) {
                    $problem_areas[] = $questions[$i]['text'];
                }
            }
        }

        // Generate simplified analysis
        $fallback_analysis = array(
            'uvod' => "Poštovani {$first_name} {$last_name}, na osnovu vaših odgovora i uzimajući u obzir vaše {$age} godine, prikazujemo vam osnovnu analizu vašeg zdravstvenog stanja.",
            'stanje_organizma' => "Identifikovano je {$positive_symptoms} područja koja zahtevaju pažnju. S obzirom na vaše godine ({$age}), preporučujemo fokus na prirodnu podršku organizma kroz kvalitetne suplemente i promenu životnih navika.",
            'preporuke' => "Za vaš uzrast preporučujemo redovnu fizičku aktivnost, balansiranu ishranu bogatu vitaminima i mineralima, te kvalitetan odmor. Prirodni suplementi mogu biti odličan dodatak vašoj wellness rutini.",
            'lifestyle_recommendations' => array(
                "Konzumirajte više zelenog povrća i voća",
                "Održavajte redovnu fizičku aktivnost",
                "Osigurajte kvalitetan san od 7-8 sati",
                "Smanjite stres kroz meditaciju ili joga"
            ),
            'proizvodi' => array_slice(array_column($products, 'id'), 0, 3),
            'paketi' => array(
                array(
                    'products' => array_slice(array_column($products, 'id'), 0, 2),
                    'size' => 2,
                    'discount' => $this->get_package_discounts()[2],
                    'vip_discount' => $this->get_vip_additional_discount(),
                    'monthly_boxes' => 1,
                    'reason' => 'Osnivni starter paket za opšte zdravlje',
                    'product_explanations' => 'Kombinacija ključnih suplemenata za podršku organizma'
                ),
                array(
                    'products' => array_slice(array_column($products, 'id'), 0, 3),
                    'size' => 3,
                    'discount' => $this->get_package_discounts()[3],
                    'vip_discount' => $this->get_vip_additional_discount(),
                    'monthly_boxes' => 2,
                    'reason' => 'Optimalni paket za poboljšanje zdravlja',
                    'product_explanations' => 'Proširena kombinacija proizvoda za bolje rezultate'
                )
            ),
            'weak_points' => array(
                array(
                    'area' => 'stomach',
                    'severity' => 'moderate',
                    'description' => 'Područje koje zahteva pažnju'
                )
            ),
            'zaključak' => "Vaš rezultat pokazuje potrebu za poboljšanjem određenih aspekata zdravlja. Prirodni pristupi i kvalitetni suplementi mogu značajno doprineti vašem blagostanju.",
            'skor' => max(30, 100 - ($positive_symptoms * 10) - $total_intensity)
        );

        return $fallback_analysis;
    }
}