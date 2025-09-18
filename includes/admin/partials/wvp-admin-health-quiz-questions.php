<?php
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['wvp_health_quiz_save_questions'])) {
    check_admin_referer('wvp_health_quiz_save_questions');
    $questions = array();
    if (isset($_POST['question_text'])) {
        foreach ($_POST['question_text'] as $i => $qt) {
            $text = sanitize_text_field($qt);

            // Process intensity levels for "Da" answers
            $intensity_levels = array();
            if (isset($_POST['question_intensity_levels'][$i])) {
                $intensity_input = sanitize_text_field($_POST['question_intensity_levels'][$i]);
                if ($intensity_input !== '') {
                    $levels = array_map('trim', explode(',', $intensity_input));
                    $intensity_levels = array_map('sanitize_text_field', $levels);
                }
            }

            // Process intensity text for each question
            $intensity_text = 'Koliko intenzivno:'; // Default value
            if (isset($_POST['question_intensity_text'][$i])) {
                $intensity_text_input = sanitize_text_field($_POST['question_intensity_text'][$i]);
                if ($intensity_text_input !== '') {
                    $intensity_text = $intensity_text_input;
                }
            }

            // Process AI dosage recommendations
            $ai_daily_dose = '';
            if (isset($_POST['question_ai_daily_dose'][$i])) {
                $ai_daily_dose = sanitize_text_field($_POST['question_ai_daily_dose'][$i]);
            }

            $ai_monthly_box = '';
            if (isset($_POST['question_ai_monthly_box'][$i])) {
                $ai_monthly_box = sanitize_text_field($_POST['question_ai_monthly_box'][$i]);
            }

            // Process recommended products for "Da" answers
            $recommended_products = array();
            if (isset($_POST['question_recommended_products'][$i]) && is_array($_POST['question_recommended_products'][$i])) {
                $recommended_products = array_map('intval', $_POST['question_recommended_products'][$i]);
                // Limit to maximum 2 products as requested
                $recommended_products = array_slice($recommended_products, 0, 2);
            }

            if ($text !== '') {
                $questions[] = array(
                    'text'    => $text,
                    'answers' => array('Da', 'Ne'), // Fixed answers
                    'main'    => 0,
                    'extra'   => 0,
                    'package' => 0,
                    'note'    => '',
                    'intensity_levels' => $intensity_levels,
                    'intensity_text' => $intensity_text,
                    'ai_daily_dose' => $ai_daily_dose,
                    'ai_monthly_box' => $ai_monthly_box,
                    'recommended_products' => $recommended_products,
                );
            }
        }
    }
    update_option('wvp_health_quiz_questions', $questions);
    $max_q = count($questions);

    $per_page = max(1, intval($_POST['questions_per_page']));
    update_option('wvp_health_quiz_questions_per_page', $per_page);

    $debug_log = isset($_POST['wvp_health_quiz_debug_log']) ? 1 : 0;
    update_option('wvp_health_quiz_debug_log', $debug_log);


    echo '<div class="updated"><p>Sačuvano.</p></div>';
}

// Handle reset to defaults
if (isset($_POST['wvp_health_quiz_reset_questions'])) {
    check_admin_referer('wvp_health_quiz_reset_questions');
    delete_option('wvp_health_quiz_questions');
    echo '<div class="updated"><p>Pitanja su resetovana na nove default vrednosti.</p></div>';
}

// Get current data
$default_questions = array(
    array(
        'text'    => 'Da li vam je ikada dijagnostifikovana autoimuna bolest (npr. Hashimoto, reumatoidni artritis, lupus)?',
        'answers' => array('Da', 'Ne'),
        'main'    => 0,
        'extra'   => 0,
        'package' => 0,
        'note'    => '',
        'intensity_levels' => array('Pre više godina', 'Pre godinu-dve', 'Nedavno dijagnostikovano'),
        'intensity_text' => 'Kada je dijagnostikovano:',
        'ai_daily_dose' => '2 kockice/tablete',
        'ai_monthly_box' => '2 kutije'
    ),
    array(
        'text'    => 'Imate li problema sa varenjem – nadutost, gasovi, zatvor ili iritabilno crevo?',
        'answers' => array('Da', 'Ne'),
        'main'    => 0,
        'extra'   => 0,
        'package' => 0,
        'note'    => '',
        'intensity_levels' => array('Povremeno', 'Često (2-3 puta nedeljno)', 'Svakodnevno'),
        'intensity_text' => 'Koliko često:',
        'ai_daily_dose' => '2 kockice',
        'ai_monthly_box' => '2 kutije'
    ),
    array(
        'text'    => 'Da li su vam lekari nekada rekli da imate povišen holesterol ili trigliceride?',
        'answers' => array('Da', 'Ne'),
        'main'    => 0,
        'extra'   => 0,
        'package' => 0,
        'note'    => '',
        'intensity_levels' => array('Blago povišen', 'Umerno povišen', 'Visok'),
        'intensity_text' => 'Nivo povišenosti:',
        'ai_daily_dose' => '2 kockice',
        'ai_monthly_box' => '2 kutije'
    ),
    array(
        'text'    => 'Da li osećate česte padove energije, hroničan umor ili iscrpljenost?',
        'answers' => array('Da', 'Ne'),
        'main'    => 0,
        'extra'   => 0,
        'package' => 0,
        'note'    => '',
        'intensity_levels' => array('Povremeno', 'Često (nekoliko puta nedeljno)', 'Konstantno'),
        'intensity_text' => 'Koliko često:',
        'ai_daily_dose' => '1 spelta + 1 ječam',
        'ai_monthly_box' => '1 kutija spelte i 1 kutija ječma'
    ),
    array(
        'text'    => 'Jeste li nekada imali anemiju ili niske vrednosti gvožđa u krvi?',
        'answers' => array('Da', 'Ne'),
        'main'    => 0,
        'extra'   => 0,
        'package' => 0,
        'note'    => '',
        'intensity_levels' => array('Pre više godina', 'U poslednje 2 godine', 'Trenutno imam'),
        'intensity_text' => 'Kada je dijagnostikovano:',
        'ai_daily_dose' => '2 kockice',
        'ai_monthly_box' => '2 kutije'
    ),
    array(
        'text'    => 'Imate li problema sa kostima i zglobovima?',
        'answers' => array('Da', 'Ne'),
        'main'    => 0,
        'extra'   => 0,
        'package' => 0,
        'note'    => '',
        'intensity_levels' => array('Blagi bolovi', 'Umerni bolovi', 'Jaki bolovi'),
        'intensity_text' => 'Intenzitet bola:',
        'ai_daily_dose' => '2 kockice',
        'ai_monthly_box' => '2 kutije'
    ),
    array(
        'text'    => 'Da li vam je dijagnostikovan povišen krvni pritisak ili problemi sa srcem i krvnim sudovima?',
        'answers' => array('Da', 'Ne'),
        'main'    => 0,
        'extra'   => 0,
        'package' => 0,
        'note'    => '',
        'intensity_levels' => array('Blago povišen', 'Umeren', 'Ozbiljan problem'),
        'intensity_text' => 'Ozbiljnost problema:',
        'ai_daily_dose' => '2 kockice',
        'ai_monthly_box' => '2 kutije'
    ),
    array(
        'text'    => 'Koliko često u toku godine imate prehlade, viruse ili infekcije?',
        'answers' => array('Da', 'Ne'),
        'main'    => 0,
        'extra'   => 0,
        'package' => 0,
        'note'    => '',
        'intensity_levels' => array('1-2 puta godišnje', '3-5 puta godišnje', 'Više od 6 puta godišnje'),
        'intensity_text' => 'Učestalost:',
        'ai_daily_dose' => '2 kockice',
        'ai_monthly_box' => '2 kutije'
    ),
    array(
        'text'    => 'Da li su vam dijagnostikovane ciste, miomi ili polipi?',
        'answers' => array('Da', 'Ne'),
        'main'    => 0,
        'extra'   => 0,
        'package' => 0,
        'note'    => '',
        'intensity_levels' => array('Manje promene', 'Umeren broj', 'Veliki broj ili veličina'),
        'intensity_text' => 'Veličina/broj:',
        'ai_daily_dose' => '3 kockice',
        'ai_monthly_box' => '3 kutije'
    ),
    array(
        'text'    => 'Da li imate dijabetes ili insulinsku rezistenciju?',
        'answers' => array('Da', 'Ne'),
        'main'    => 0,
        'extra'   => 0,
        'package' => 0,
        'note'    => '',
        'intensity_levels' => array('Predijabetest', 'Tip 2 dijabetes', 'Tip 1 dijabetes'),
        'intensity_text' => 'Tip dijabetesa:',
        'ai_daily_dose' => '1 kockica',
        'ai_monthly_box' => '1 kutija'
    ),
    array(
        'text'    => 'Imate li problema sa kožom – akne, ekcem, dermatitis, suvoća?',
        'answers' => array('Da', 'Ne'),
        'main'    => 0,
        'extra'   => 0,
        'package' => 0,
        'note'    => '',
        'intensity_levels' => array('Blagi problemi', 'Umeren intenzitet', 'Ozbiljni problemi'),
        'intensity_text' => 'Intenzitet problema:',
        'ai_daily_dose' => '2 kockice',
        'ai_monthly_box' => '2 kutije'
    ),
    array(
        'text'    => 'Da li ste u poslednje vreme primetili promene u apetitu ili telesnoj težini?',
        'answers' => array('Da', 'Ne'),
        'main'    => 0,
        'extra'   => 0,
        'package' => 0,
        'note'    => '',
        'intensity_levels' => array('Manje promene (1-3kg)', 'Umeren stepen (3-7kg)', 'Velike promene (više od 7kg)'),
        'intensity_text' => 'Stepen promene:',
        'ai_daily_dose' => '1 spelta + 1 ječam',
        'ai_monthly_box' => '1 kutija spelte i 1 kutija ječma'
    ),
    array(
        'text'    => 'Da li imate problem sa nesanicom, stresom ili nervozom?',
        'answers' => array('Da', 'Ne'),
        'main'    => 0,
        'extra'   => 0,
        'package' => 0,
        'note'    => '',
        'intensity_levels' => array('Povremeno', 'Često', 'Konstantno'),
        'intensity_text' => 'Učestalost:',
        'ai_daily_dose' => '2 kockice',
        'ai_monthly_box' => '2 kutije'
    ),
    array(
        'text'    => 'Da li spadate u grupu starijih od 60 godina i osećate da vam je potrebna dodatna vitalnost?',
        'answers' => array('Da', 'Ne'),
        'main'    => 0,
        'extra'   => 0,
        'package' => 0,
        'note'    => '',
        'intensity_levels' => array('60-65 godina', '65-75 godina', 'Preko 75 godina'),
        'intensity_text' => 'Uzrasna grupa:',
        'ai_daily_dose' => '1 kockica',
        'ai_monthly_box' => '1 kutija'
    ),
    array(
        'text'    => 'Imate li u porodici istoriju bolesti jetre ili bubrega (npr. masna jetra, kamen u bubregu)?',
        'answers' => array('Da', 'Ne'),
        'main'    => 0,
        'extra'   => 0,
        'package' => 0,
        'note'    => '',
        'intensity_levels' => array('Dalji srodnici', 'Bliži srodnici (roditelji, deca)', 'Više članova porodice'),
        'intensity_text' => 'Porodična istorija:',
        'ai_daily_dose' => '1 spelta + 1 ječam',
        'ai_monthly_box' => '1 kutija spelte i 1 kutija ječma'
    ),
    array(
        'text'    => 'Da li vam je ikada dijagnostifikovan kancer ili se trenutno lečite od njega?',
        'answers' => array('Da', 'Ne'),
        'main'    => 0,
        'extra'   => 0,
        'package' => 0,
        'note'    => '',
        'intensity_levels' => array('U remisiji', 'Trenutno na terapiji', 'Nedavno dijagnostikovan'),
        'intensity_text' => 'Status lečenja:',
        'ai_daily_dose' => '4 kockice',
        'ai_monthly_box' => '4 kutije'
    ),
    array(
        'text'    => 'Da li u vašoj porodici postoji genetska predispozicija za kancer (npr. rak dojke, debelog creva, prostate)?',
        'answers' => array('Da', 'Ne'),
        'main'    => 0,
        'extra'   => 0,
        'package' => 0,
        'note'    => '',
        'intensity_levels' => array('Dalji srodnici', 'Bliži srodnici', 'Više članova porodice'),
        'intensity_text' => 'Porodična istorija:',
        'ai_daily_dose' => '2 kockice',
        'ai_monthly_box' => '2 kutije'
    ),
    array(
        'text'    => 'Da li vam je ikada dijagnostikovana neka od bolesti jetre (masna jetra, hepatitis, ciroza)?',
        'answers' => array('Da', 'Ne'),
        'main'    => 0,
        'extra'   => 0,
        'package' => 0,
        'note'    => '',
        'intensity_levels' => array('Blaga masna jetra', 'Umeren problem', 'Ozbiljan problem'),
        'intensity_text' => 'Ozbiljnost:',
        'ai_daily_dose' => '1 spelta + 2 ječam',
        'ai_monthly_box' => '1 kutija spelte i 2 kutije ječma'
    ),
    array(
        'text'    => 'Da li imate problem sa pamćenjem, koncentracijom ili mentalnom jasnoćom?',
        'answers' => array('Da', 'Ne'),
        'main'    => 0,
        'extra'   => 0,
        'package' => 0,
        'note'    => '',
        'intensity_levels' => array('Povremeno', 'Često', 'Konstantno'),
        'intensity_text' => 'Učestalost:',
        'ai_daily_dose' => '2 kockice',
        'ai_monthly_box' => '2 kutije'
    ),
    array(
        'text'    => 'Da li osećate potrebu za detoksikacijom organizma (npr. nakon terapija, loše ishrane, alkohola)?',
        'answers' => array('Da', 'Ne'),
        'main'    => 0,
        'extra'   => 0,
        'package' => 0,
        'note'    => '',
        'intensity_levels' => array('Povremeno', 'Redovno', 'Hitno potrebno'),
        'intensity_text' => 'Potreba za detoks:',
        'ai_daily_dose' => '1 spelta + 1 ječam',
        'ai_monthly_box' => '1 kutija spelte i 1 kutija ječma'
    ),
    array(
        'text'    => 'Da li ste podložni alergijama (polenska, respiratorna, alergija na hranu)?',
        'answers' => array('Da', 'Ne'),
        'main'    => 0,
        'extra'   => 0,
        'package' => 0,
        'note'    => '',
        'intensity_levels' => array('Sezonske alergije', 'Višestruke alergije', 'Ozbiljne alergijske reakcije'),
        'intensity_text' => 'Tip alergije:',
        'ai_daily_dose' => '2 kockice',
        'ai_monthly_box' => '2 kutije'
    ),
    array(
        'text'    => 'Da li osećate iscrpljenost usled posla, treninga ili stresa i želeli biste prirodan način da povratite snagu?',
        'answers' => array('Da', 'Ne'),
        'main'    => 0,
        'extra'   => 0,
        'package' => 0,
        'note'    => '',
        'intensity_levels' => array('Blagu iscrpljenost', 'Umeren umor', 'Potpuna iscrpljenost'),
        'intensity_text' => 'Nivo iscrpljenosti:',
        'ai_daily_dose' => '1 kockica',
        'ai_monthly_box' => '2 kutije'
    ),
    array(
        'text'    => 'Da li želite da poboljšate imunitet svoje dece ili starijih članova porodice?',
        'answers' => array('Da', 'Ne'),
        'main'    => 0,
        'extra'   => 0,
        'package' => 0,
        'note'    => '',
        'intensity_levels' => array('Za decu', 'Za odrasle', 'Za starije osobe'),
        'intensity_text' => 'Za koga:',
        'ai_daily_dose' => '2 kockice',
        'ai_monthly_box' => '2 kutije'
    ),
    array(
        'text'    => 'Da li imate problema sa mentalnim zdravljem – depresijom, anksioznošću ili paničnim napadima?',
        'answers' => array('Da', 'Ne'),
        'main'    => 0,
        'extra'   => 0,
        'package' => 0,
        'note'    => '',
        'intensity_levels' => array('Blagi simptomi', 'Umeren intenzitet', 'Ozbiljni simptomi'),
        'intensity_text' => 'Intenzitet simptoma:',
        'ai_daily_dose' => '2 kockice',
        'ai_monthly_box' => '2 kutije'
    ),
    array(
        'text'    => 'Da li vam je ikada dijagnostifikovana kandida ili heliko bakterija?',
        'answers' => array('Da', 'Ne'),
        'main'    => 0,
        'extra'   => 0,
        'package' => 0,
        'note'    => '',
        'intensity_levels' => array('Pre više godina', 'U poslednje 2 godine', 'Nedavno/trenutno'),
        'intensity_text' => 'Kada je dijagnostikovano:',
        'ai_daily_dose' => '1 kockica',
        'ai_monthly_box' => '1 kutija'
    ),
);
$questions = get_option('wvp_health_quiz_questions', $default_questions);
$debug_log = intval(get_option('wvp_health_quiz_debug_log', 0));
$per_page  = intval(get_option('wvp_health_quiz_questions_per_page', 3));
$max_q = count($questions);
?>

<div class="wrap">
    <h1><?php _e('Health Quiz Pitanja', 'woocommerce-vip-paketi'); ?></h1>

    <div class="notice notice-info">
        <p><strong><?php _e('Kako koristiti:', 'woocommerce-vip-paketi'); ?></strong></p>
        <?php
        $health_quiz_slug = get_option('wvp_health_quiz_url_slug', 'analiza-zdravstvenog-stanja');
        $health_quiz_url = home_url('/' . $health_quiz_slug);
        ?>
        <p><?php printf(__('Health Quiz je dostupan na: <a href="%s" target="_blank">%s</a>', 'woocommerce-vip-paketi'), esc_url($health_quiz_url), esc_html($health_quiz_url)); ?></p>
        <p><?php _e('URL se može promeniti u glavnim podešavanjima VIP Paketi plugina.', 'woocommerce-vip-paketi'); ?></p>
        <p><em><?php _e('Takođe možete koristiti shortcode [wvp_health_quiz] na bilo kojoj stranici.', 'woocommerce-vip-paketi'); ?></em></p>
    </div>

    <div class="notice notice-warning">
        <p><strong>🤖 <?php _e('AI Integracija:', 'woocommerce-vip-paketi'); ?></strong></p>
        <p><?php _e('OpenAI podešavanja i AI analiza su prebačeni na dedikovan meni.', 'woocommerce-vip-paketi'); ?>
           <a href="<?php echo admin_url('admin.php?page=wvp-ai-integration'); ?>" class="button button-secondary" style="margin-left: 10px;"><?php _e('🔗 Otvori AI Integraciju', 'woocommerce-vip-paketi'); ?></a>
        </p>
    </div>

    <form method="post">
        <?php wp_nonce_field('wvp_health_quiz_save_questions'); ?>
        <table class="form-table" id="wvp-health-quiz-questions-table">
            <tbody id="wvp-health-quiz-questions-body">
            <?php for ($i = 0; $i < $max_q; $i++) :
                $q = isset($questions[$i]) ? $questions[$i] : array('text' => '', 'intensity_levels' => array(), 'intensity_text' => 'Koliko intenzivno:', 'ai_daily_dose' => '', 'ai_monthly_box' => '');
                $intensity_levels = isset($q['intensity_levels']) ? implode(',', $q['intensity_levels']) : '';
                $intensity_text = isset($q['intensity_text']) ? $q['intensity_text'] : 'Koliko intenzivno:';
                $ai_daily_dose = isset($q['ai_daily_dose']) ? $q['ai_daily_dose'] : '';
                $ai_monthly_box = isset($q['ai_monthly_box']) ? $q['ai_monthly_box'] : '';
            ?>
            <tr>
                <th><?php printf(__('Pitanje %d', 'woocommerce-vip-paketi'), $i + 1); ?></th>
                <td>
                    <input type="text" name="question_text[<?php echo $i; ?>]" value="<?php echo esc_attr($q['text']); ?>" class="regular-text" placeholder="Unesite tekst pitanja" />
                    <br/>
                    <small><?php _e('Intenziteti za "Da" odgovor (zarezom odvojeni, npr: Blago,Umerno,Jako)', 'woocommerce-vip-paketi'); ?></small><br/>
                    <input type="text" name="question_intensity_levels[<?php echo $i; ?>]" value="<?php echo esc_attr($intensity_levels); ?>" class="regular-text" placeholder="Blago,Umerno,Jako" />
                    <br/>
                    <small><?php _e('Tekst za intenzitet (npr: "Koliko intenzivno:", "Koliko često:", "Kako se osećate:")', 'woocommerce-vip-paketi'); ?></small><br/>
                    <input type="text" name="question_intensity_text[<?php echo $i; ?>]" value="<?php echo esc_attr($intensity_text); ?>" class="regular-text" placeholder="Koliko intenzivno:" />
                    <br/>
                    <small><?php _e('AI preporuka - dnevna doza (npr: "2-3 kockice dnevno", "1 tableta ujutru")', 'woocommerce-vip-paketi'); ?></small><br/>
                    <input type="text" name="question_ai_daily_dose[<?php echo $i; ?>]" value="<?php echo esc_attr($ai_daily_dose); ?>" class="regular-text" placeholder="2-3 kockice dnevno" />
                    <br/>
                    <small><?php _e('AI preporuka - mesečna kutija (npr: "1 kutija od 60 tableta", "2 kutije po 30 kockica")', 'woocommerce-vip-paketi'); ?></small><br/>
                    <input type="text" name="question_ai_monthly_box[<?php echo $i; ?>]" value="<?php echo esc_attr($ai_monthly_box); ?>" class="regular-text" placeholder="1 kutija od 60 tableta" />
                    <br/>
                    <small><?php _e('Preporučeni proizvodi za "DA" odgovor (maksimalno 2):', 'woocommerce-vip-paketi'); ?></small><br/>
                    <?php
                    // Get recommended products for this question
                    $recommended_products = isset($q['recommended_products']) ? $q['recommended_products'] : array();

                    // Get available products from WooCommerce
                    $available_products = get_posts(array(
                        'post_type' => 'product',
                        'post_status' => 'publish',
                        'numberposts' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC'
                    ));

                    if (!empty($available_products)) {
                        echo '<div style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 8px; background: #f9f9f9;">';
                        foreach ($available_products as $product) {
                            $product_obj = wc_get_product($product->ID);
                            if ($product_obj && $product_obj->get_status() === 'publish') {
                                $checked = in_array($product->ID, $recommended_products) ? 'checked' : '';
                                echo '<label style="display: block; margin: 2px 0;">';
                                echo '<input type="checkbox" name="question_recommended_products[' . $i . '][]" value="' . $product->ID . '" ' . $checked . ' class="recommended-product-checkbox" data-question="' . $i . '" /> ';
                                echo esc_html($product->post_title);
                                if ($product_obj->get_regular_price()) {
                                    echo ' (' . wc_price($product_obj->get_regular_price()) . ')';
                                }
                                echo '</label>';
                            }
                        }
                        echo '</div>';
                        echo '<small style="color: #666;">Maksimalno 2 proizvoda po pitanju</small>';
                    } else {
                        echo '<p style="color: #999;">Nema dostupnih proizvoda. Prvo kreirajte proizvode u WooCommerce.</p>';
                    }
                    ?>
                </td>
            </tr>
            <?php endfor; ?>
            </tbody>
            <tbody>
        <tr>
            <th><?php _e('Broj pitanja po stranici', 'woocommerce-vip-paketi'); ?></th>
            <td><input type="number" name="questions_per_page" value="<?php echo esc_attr($per_page); ?>" min="1" class="small-text" /></td>
        </tr>
            <tr>
                <th><?php _e('Prikaži log grešaka', 'woocommerce-vip-paketi'); ?></th>
                <td>
                    <label><input type="checkbox" name="wvp_health_quiz_debug_log" value="1" <?php checked($debug_log); ?> /> <?php _e('Omogući prikaz loga', 'woocommerce-vip-paketi'); ?></label>
                </td>
            </tr>
            </tbody>
        </table>
        <p><button type="button" id="wvp-health-quiz-add-question" class="button"><?php _e('Dodaj novo pitanje', 'woocommerce-vip-paketi'); ?></button></p>
        <p>
            <input type="submit" name="wvp_health_quiz_save_questions" class="button-primary" value="<?php _e('Sačuvaj', 'woocommerce-vip-paketi'); ?>">
        </p>
    </form>

    <form method="post" style="margin-top: 20px;">
        <?php wp_nonce_field('wvp_health_quiz_reset_questions'); ?>
        <div class="notice notice-warning">
            <p><strong><?php _e('Reset na nova pitanja:', 'woocommerce-vip-paketi'); ?></strong></p>
            <p><?php _e('Ova opcija će obrisati sva trenutna pitanja i učitati nova 25 medicinskih pitanja sa dozama i intenzitetom.', 'woocommerce-vip-paketi'); ?></p>
            <p>
                <input type="submit" name="wvp_health_quiz_reset_questions" class="button button-secondary"
                       value="<?php _e('Resetuj na nova pitanja', 'woocommerce-vip-paketi'); ?>"
                       onclick="return confirm('<?php esc_attr_e('Ovo će obrisati sva postojeća pitanja i učitati nova. Da li ste sigurni?', 'woocommerce-vip-paketi'); ?>')">
            </p>
        </div>
    </form>
    <script>
    jQuery(document).ready(function($){
        $('#wvp-health-quiz-add-question').on('click', function(){
            var index = $('#wvp-health-quiz-questions-body tr').length;
            var row = '<tr>'+
                      '<th>Pitanje '+(index+1)+'</th>'+
                      '<td>'+
                      '<input type="text" name="question_text['+index+']" class="regular-text" placeholder="Unesite tekst pitanja" />'+
                      '<br/><small>Intenziteti za "Da" odgovor (zarezom odvojeni, npr: Blago,Umerno,Jako)</small><br/>'+
                      '<input type="text" name="question_intensity_levels['+index+']" class="regular-text" placeholder="Blago,Umerno,Jako" />'+
                      '<br/><small>Tekst za intenzitet (npr: "Koliko intenzivno:", "Koliko često:", "Kako se osećate:")</small><br/>'+
                      '<input type="text" name="question_intensity_text['+index+']" class="regular-text" placeholder="Koliko intenzivno:" />'+
                      '<br/><small>AI preporuka - dnevna doza (npr: "2-3 kockice dnevno", "1 tableta ujutru")</small><br/>'+
                      '<input type="text" name="question_ai_daily_dose['+index+']" class="regular-text" placeholder="2-3 kockice dnevno" />'+
                      '<br/><small>AI preporuka - mesečna kutija (npr: "1 kutija od 60 tableta", "2 kutije po 30 kockica")</small><br/>'+
                      '<input type="text" name="question_ai_monthly_box['+index+']" class="regular-text" placeholder="1 kutija od 60 tableta" />'+
                      '</td>'+
                      '</tr>';
            $('#wvp-health-quiz-questions-body').append(row);
        });

        // Limit product selection to maximum 2 per question
        $(document).on('change', '.recommended-product-checkbox', function(){
            var questionIndex = $(this).data('question');
            var checkedBoxes = $('input[name="question_recommended_products[' + questionIndex + '][]"]:checked');

            if (checkedBoxes.length > 2) {
                $(this).prop('checked', false);
                alert('Maksimalno možete odabrati 2 proizvoda po pitanju.');
            }
        });

    });
    </script>

</div>