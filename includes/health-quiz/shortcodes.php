<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'wvp_health_quiz', 'wvp_health_quiz_shortcode' );
function wvp_health_quiz_shortcode($atts = array()) {
    // Check if we're on the completed quiz page
    $health_quiz_state = get_query_var('wvp_health_quiz', 'main');
    if ($health_quiz_state === 'completed') {
        return wvp_health_quiz_completed_page();
    }

    // Check current step for PHP navigation
    $url_step = get_query_var('wvp_quiz_step', '');
    $current_step = 1; // Default to step 1 (basic info)

    if ($url_step === 'report') {
        $current_step = 3; // Results page
    } elseif (is_numeric($url_step)) {
        $current_step = 2; // Questions page (pitanja1, pitanja2 etc all show step 2)
    }

    error_log('WVP Shortcode: Current step = ' . $current_step . ', URL step = ' . $url_step);

    // Show only the appropriate step content for PHP navigation
    if ($current_step === 3) {
        return wvp_generate_step_3(); // Results
    } elseif ($current_step === 2) {
        return wvp_generate_step_2(); // Questions
    }

    // Default: Show the complete original quiz with all steps (for JavaScript navigation)

    // Add immediate style to hide header except on first step
    echo '<style>
    .wvp-ai-health-header { display: none !important; }
    .wvp-ai-health-header.show-initial:not(.hidden) { display: grid !important; visibility: visible !important; }
    </style>';

    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        var header = document.querySelector(".wvp-ai-health-header");
        if (header && header.classList.contains("show-initial")) {
            // Show header only on initial load of first step
            var firstStep = document.querySelector(".wvp-health-step[data-step=\"1\"]");
            if (firstStep && firstStep.style.display !== "none") {
                header.style.display = "grid";
                header.style.visibility = "visible";
            }
        }

        // Hide header when any interaction happens
        document.addEventListener("click", function(e) {
            if (e.target.closest(".wvp-health-next")) {
                if (header) {
                    header.classList.remove("show-initial");
                    header.classList.add("hidden");
                    header.style.display = "none";
                }
            }
        });
    });
    </script>';

    // Continue with original shortcode code...
    $default_questions = array(
        array(
            'text'    => 'Da li imate probleme sa digestijom?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('Blago', 'Umerno', 'Jako')
        ),
        array(
            'text'    => 'Da li se osećate umorno tokom dana?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('Ponekad', 'Često', 'Stalno')
        ),
        array(
            'text'    => 'Da li imate probleme sa spavanjem?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('Retko', 'Nekoliko puta nedeljno', 'Svake noći')
        ),
    );

    $questions = get_option( 'wvp_health_quiz_questions', $default_questions );
    $debug_log = intval( get_option( 'wvp_health_quiz_debug_log', 0 ) );
    $per_page  = intval( get_option( 'wvp_health_quiz_questions_per_page', 3 ) );
    if ( $per_page < 1 ) $per_page = 1;
    $question_pages = array_chunk( $questions, $per_page );

    $universal_package = intval( get_option( 'wvp_health_quiz_universal_package', 0 ) );

    $product_ids = array();
    foreach ( $questions as $q ) {
        foreach ( array( $q['main'], $q['extra'], $q['package'] ) as $pid ) {
            if ( $pid ) {
                $product_ids[] = $pid;
            }
        }
    }
    if ( $universal_package ) {
        $product_ids[] = $universal_package;
    }
    $product_ids = array_unique( $product_ids );
    $product_data = array();
    foreach ( $product_ids as $pid ) {
        $prod = wc_get_product( $pid );
        if ( $prod ) {
            $img  = wp_get_attachment_image_url( $prod->get_image_id(), 'medium' );
            $price_html = $prod->get_price_html();
            $price_html = trim( $price_html );
            $product_data[ $pid ] = array(
                'img'   => $img ? $img : '',
                'price' => $price_html,
                'name'  => $prod->get_name(),
                'link'  => get_permalink( $pid ),
            );
        }
    }

    // Get current user data for auto-population
    $current_user = wp_get_current_user();
    $user_first_name = '';
    $user_last_name = '';
    $user_email = '';
    $user_phone = '';
    $user_location = '';
    $user_country = '';
    $user_birth_year = '';

    if ($current_user->ID) {
        $user_first_name = $current_user->first_name;
        $user_last_name = $current_user->last_name;
        $user_email = $current_user->user_email;
        $user_phone = get_user_meta($current_user->ID, 'billing_phone', true);
        $user_location = get_user_meta($current_user->ID, 'billing_city', true);
        $user_country = get_user_meta($current_user->ID, 'billing_country', true);
        $user_birth_year = get_user_meta($current_user->ID, 'wvp_birth_year', true);
    }

    $countries = array(
        'RS' => 'Srbija',
        'HU' => 'Mađarska'
    );

    ob_start();
    ?>
    <div class="wvp-single-package-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-10 col-xl-8 mx-auto">

                    <!-- Breadcrumbs -->
                    <nav class="wvp-breadcrumbs" aria-label="Breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="<?php echo home_url(); ?>">Početna</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Analiza zdravstvenog stanja</li>
                        </ol>
                    </nav>

                    <!-- AI Health Quiz Header -->
                    <article class="wvp-ai-health-header show-initial" itemscope itemtype="https://schema.org/MedicalEntity">
                        <div class="ai-header-content">
                            <div class="ai-branding">
                                <div class="ai-icon">🤖</div>
                                <span class="ai-badge">Sistemska Analiza</span>
                            </div>
                            <h1 class="ai-health-title" itemprop="name">
                                <span class="title-main">Personalizovana<br>Analiza Zdravlja</span>
                                <span class="title-subtitle">Preporuke • Prirodno • Personalizovano</span>
                            </h1>
                            <div class="ai-description" itemprop="description">
                                <p class="description-main">Naš sistem sakuplja informacije o vašem zdravstvenom stanju i daje Vam personalnu prirodnu analizu vašeg zdravlja i savetuje prirodne suplemente i doziranje.</p>
                                <p class="medical-disclaimer">⚠️ Napomena: Ove preporuke predstavljaju naše sugestije i ne mogu zameniti savete stručnog medicinskog lica ni terapijski tretman.</p>
                                <div class="ai-features">
                                    <div class="feature-item">
                                        <span class="feature-icon">📋</span>
                                        <span>Personalizovan izveštaj</span>
                                    </div>
                                    <div class="feature-item">
                                        <span class="feature-icon">🌿</span>
                                        <span>100% Prirodno</span>
                                    </div>
                                    <div class="feature-item">
                                        <span class="feature-icon">🔬</span>
                                        <span>Sistemska analiza</span>
                                    </div>
                                    <div class="feature-item">
                                        <span class="feature-icon">⚡</span>
                                        <span>Brzi rezultati</span>
                                    </div>
                                    <div class="feature-item">
                                        <span class="feature-icon">🩺</span>
                                        <span>Analiza celog stanja</span>
                                    </div>
                                    <div class="feature-item">
                                        <span class="feature-icon">🌾</span>
                                        <span>Preporuka prirodnih suplemenata</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>


                    <div id="wvp-health-quiz">
                        <?php $step = 1; ?>
                        <div class="wvp-health-step" data-step="<?php echo $step; ?>">
                            <div class="wvp-ai-form-section">
                                <div class="form-header-card">
                                    <div class="form-step-indicator">
                                        <span class="step-number">1</span>
                                        <span class="step-label">Osnovni podaci</span>
                                    </div>
                                    <h2 class="form-title">
                                        <span class="title-icon">📝</span>
                                        Hajde da personalizujemo preporuku
                                    </h2>
                                    <p class="form-description">Vaši podaci pomažu našem sistemu da vam obezbedi precizniju analizu i personalizovane preporuke koje odgovaraju baš vašim potrebama.</p>
                                    <div class="ai-processing-indicator">
                                        <div class="processing-dots">
                                            <span class="dot"></span>
                                            <span class="dot"></span>
                                            <span class="dot"></span>
                                        </div>
                                        <span class="processing-text">Pripremam personalizovanu analizu...</span>
                                    </div>
                                </div>
                <?php if ($current_user->ID): ?>
                <div class="wvp-health-user-notice">
                    <div class="user-welcome-icon">👋</div>
                    <div class="user-welcome-content">
                        <p><strong>Dobrodošli, <?php echo esc_html($user_first_name . ' ' . $user_last_name); ?>!</strong></p>
                        <p>Vaši podaci su automatski popunjeni. Možete ih izmeniti ako je potrebno.</p>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" id="wvp-main-form">
                    <input type="hidden" name="wvp_action" value="start_quiz">
                    <input type="hidden" name="wvp_nonce" value="<?php echo wp_create_nonce('wvp_quiz_navigation'); ?>">

                    <div class="ai-form-grid">
                        <div class="form-group">
                            <label class="form-label" for="wvp-first-name">
                                <span class="label-icon">👤</span>
                                <span class="label-text">Ime *</span>
                            </label>
                            <input type="text" id="wvp-first-name" name="first_name" class="form-input" value="<?php echo esc_attr($user_first_name); ?>" placeholder="Unesite vaše ime" required>
                            <span class="wvp-health-error" id="wvp-first-name-error"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="wvp-last-name">
                                <span class="label-icon">👤</span>
                                <span class="label-text">Prezime *</span>
                            </label>
                            <input type="text" id="wvp-last-name" name="last_name" class="form-input" value="<?php echo esc_attr($user_last_name); ?>" placeholder="Unesite vaše prezime" required>
                            <span class="wvp-health-error" id="wvp-last-name-error"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="wvp-email">
                                <span class="label-icon">📧</span>
                                <span class="label-text">Email *</span>
                            </label>
                            <input type="email" id="wvp-email" name="email" class="form-input" value="<?php echo esc_attr($user_email); ?>" placeholder="vase.ime@email.com" required>
                            <span class="wvp-health-error" id="wvp-email-error"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="wvp-phone">
                                <span class="label-icon">📱</span>
                                <span class="label-text">Telefon *</span>
                            </label>
                            <input type="tel" id="wvp-phone" name="phone" class="form-input" value="<?php echo esc_attr($user_phone); ?>" placeholder="06x xxx xxxx" pattern="[0-9]+" title="Samo brojevi" required>
                            <span class="wvp-health-error" id="wvp-phone-error"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="wvp-year">
                                <span class="label-icon">🎂</span>
                                <span class="label-text">Godina rođenja *</span>
                            </label>
                            <input type="number" id="wvp-year" name="birth_year" class="form-input" value="<?php echo esc_attr($user_birth_year); ?>" placeholder="1990" min="1930" max="2010" required>
                            <span class="wvp-health-error" id="wvp-year-error"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="wvp-location">
                                <span class="label-icon">🏙️</span>
                                <span class="label-text">Mesto stanovanja</span>
                            </label>
                            <input type="text" id="wvp-location" name="location" class="form-input" value="<?php echo esc_attr($user_location); ?>" placeholder="Beograd, Novi Sad...">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="wvp-country">
                                <span class="label-icon">🌍</span>
                                <span class="label-text">Zemlja *</span>
                            </label>
                            <select id="wvp-country" name="country" class="form-select" required>
                                <option value="">Izaberite zemlju</option>
                                <?php foreach ($countries as $code => $name): ?>
                                    <option value="<?php echo esc_attr($code); ?>" <?php selected($user_country, $code); ?>><?php echo esc_html($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="wvp-health-error" id="wvp-country-error"></span>
                        </div>
                    </div>

                    <div class="ai-form-actions">
                        <button class="ai-submit-btn" type="submit">
                            <div class="btn-content">
                                <span class="btn-icon">🤖</span>
                                <span class="btn-text">Započni AI analizu</span>
                                <span class="btn-arrow">→</span>
                            </div>
                            <div class="btn-shimmer"></div>
                        </button>
                        <p class="form-note">
                            <span class="note-icon">🔒</span>
                            Vaši podaci su sigurni i koriste se isključivo za personalizaciju preporuka
                        </p>
                    </div>
                </form>
                </div>
            </div>
        </div>
        <?php $q_index = 0; foreach ( $question_pages as $p_idx => $page ) : $step++; ?>
        <div class="wvp-health-step wvp-survey-step" data-step="<?php echo $step; ?>" style="display:none;">

            <!-- Survey Header -->
            <div class="wvp-survey-header">
                <h2 class="survey-title">Anketa – Simptomi i navike</h2>
                <div class="survey-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo (($step-1) / count($question_pages)) * 100; ?>%"></div>
                    </div>
                    <span class="progress-text">Korak <?php echo ($step-1); ?>/<?php echo count($question_pages); ?></span>
                </div>
                <p class="survey-tip">💡 <strong>Tip:</strong> Odgovori instinktivno, nema tačnih i netačnih.</p>
            </div>

            <!-- Survey Questions -->
            <div class="wvp-survey-content">
            <?php foreach ( $page as $q ) : ?>
                <div class="wvp-health-question-group" data-question="<?php echo $q_index; ?>">
                    <p><?php echo esc_html( $q['text'] ); ?></p>
                    <div class="wvp-health-answers">
                        <?php foreach ( $q['answers'] as $a_idx => $ans ) : ?>
                            <label class="wvp-health-answer">
                                <input type="radio" name="q<?php echo $q_index; ?>" class="wvp-health-question" data-index="<?php echo $a_idx; ?>" value="<?php echo esc_attr( $ans ); ?>" required>
                                <span><?php echo esc_html( $ans ); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <?php if ( isset($q['intensity_levels']) && !empty($q['intensity_levels']) && strtolower($q['answers'][0]) === 'da' ) : ?>
                    <div class="wvp-health-intensity-group" data-question="<?php echo $q_index; ?>" style="display:none;">
                        <p class="wvp-intensity-label"><?php echo esc_html(isset($q['intensity_text']) ? $q['intensity_text'] : 'Koliko intenzivno:'); ?></p>
                        <div class="wvp-health-intensity-answers">
                            <?php foreach ( $q['intensity_levels'] as $i_idx => $intensity ) : ?>
                                <label class="wvp-health-intensity">
                                    <input type="radio" name="q<?php echo $q_index; ?>_intensity" class="wvp-health-intensity-radio" data-intensity="<?php echo $i_idx + 1; ?>" value="<?php echo esc_attr( $intensity ); ?>">
                                    <span><?php echo esc_html( $intensity ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <span class="wvp-health-error"></span>
                </div>
            <?php $q_index++; endforeach; ?>
            </div>

            <!-- Survey Navigation -->
            <div class="survey-navigation">
                <button class="wvp-health-prev">Nazad</button>
                <button class="wvp-health-next">Dalje</button>
            </div>
        </div>
        <?php endforeach; $step++; ?>
        <div class="wvp-health-step wvp-results-step" data-step="<?php echo $step; ?>" style="display:none;">

            <!-- Body Map Results -->
            <div class="wvp-body-map-section">
                <div class="body-map-header">
                    <h2 class="results-title">Tvoje telo danas — vizuelni pregled</h2>
                    <p class="results-subtitle">Kliknite na regione za detaljne informacije</p>
                </div>

                <div class="body-map-container">
                    <div class="body-map-visual">
                        <svg viewBox="0 0 1024 1536" xmlns="http://www.w3.org/2000/svg" class="body-svg">
                            <defs>
                                <!-- Enhanced filter for clickable areas -->
                                <filter id="glow" x="-50%" y="-50%" width="200%" height="200%">
                                    <feGaussianBlur stdDeviation="8" result="coloredBlur"/>
                                    <feMerge>
                                        <feMergeNode in="coloredBlur"/>
                                        <feMergeNode in="SourceGraphic"/>
                                    </feMerge>
                                </filter>

                                <style><![CDATA[
                                    .clickable-organ { cursor:pointer; transition:all 0.3s ease; opacity: 0.05; stroke: none; stroke-width: 0; }
                                    .clickable-organ:hover { filter:url(#glow); opacity:0.6; transform:scale(1.02); stroke: #fff; stroke-width: 2; }
                                    .clickable-organ.active { filter:url(#glow); opacity:0.8; transform:scale(1.05); stroke: #fff; stroke-width: 3; }
                                ]]></style>
                            </defs>

                            <!-- Original SVG paths from the professional file -->
                            <path d="m0 0h1024v1536c-337.92 0-675.84 0-1024 0 0-506.88 0-1013.76 0-1536z" fill="#fefefe"/>
                            <path d="m0 0c.73266083.00087616 1.46532166.00175232 2.22018433.00265503 11.30149172.04460404 22.04173682.57446744 32.77981567 4.37234497.68255859.24137695 1.36511719.48275391 2.06835938.73144531 20.02449975 7.39065799 34.5254165 19.9666423 43.93164062 39.26855469 2.83548864 7.10511171 5.13428518 14.18019967 5.11352539 21.88208008-.00007553.94059768-.00015106 1.88119537-.00022888 2.85029602-.00516129 1.0022612-.01032257 2.0045224-.01564026 3.03715515-.00212242 1.55420906-.00212242 1.55420906-.00428772 3.13981628-.00558611 3.30108051-.01813611 6.60209255-.03086853 9.90315247-.00501679 2.24153548-.00957939 4.48307202-.01367188 6.72460938-.01101059 5.48765346-.02773494 10.97526715-.04882812 16.46289062 1.26070312-.03609375 2.52140625-.0721875 3.8203125-.109375 3.29505004-.09433681 4.08913624.05425684 7.0546875 1.859375 5.80655436 6.14811639 4.66291149 13.73010131 4.50390625 21.6953125-.59367798 12.41404064-5.36061109 26.69619848-14.19140625 35.6796875-1.0828125.928125-1.0828125.928125-2.1875 1.875-.33.33-.66.66-1 1-2.67058851.14115161-5.32432238.04247107-8 0-.14695312.825-.29390625 1.65-.4453125 2.5-2.54748511 13.11808425-6.74944778 30.69476028-16.5546875 40.5-1.009504 13.69713379-2.31012336 31.52379959 5.5625 43.5 17.20055359 19.56303926 57.98116611 26.48239571 82.5625 32.8125 26.35414934 6.80982266 48.80203186 16.98132064 63.875 40.6875 7.29821228 12.62313074 12.37009011 25.82783416 15.1875 40.125.23251465 1.16921997.23251465 1.16921997.46972656 2.36206055 2.88805171 15.83866701 3.52832771 31.69972968 3.57675171 47.75836181.09746338 17.89724702 1.37340048 35.14816702 4.25454712 52.80023194.27498238 1.68524987.54963567 3.37055347.82397461 5.0559082.20821632 1.2758773.20821632 1.2758773.42063904 2.57752991 1.89516106 11.88718229 3.14250272 23.85815299 4.45436096 35.82090759.12015472 1.09250061.24030945 2.18500122.36410522 3.31060791.23562986 2.14351338.47097946 4.28705759.70603943 6.43063355.4609137 4.19406189.92687867 8.38755748 1.39317322 12.58102416.23717108 2.15837705.469801 4.31725696.69836426 6.4765625 1.33439285 12.59204726 2.84564662 24.58585651 6.65081787 36.70117188.72862822 2.44378286 1.45647711 4.88779821 2.18359375 7.33203125.36786621 1.2161499.73573242 2.4322998 1.11474609 3.68530273 9.20131287 30.65460963 12.5313299 61.21466879 14.77148438 93.01708985.06056579.85912994.12113159 1.71825989.18353271 2.60342407.12268623 1.74035921.24512306 3.48073601.36730957 5.22113037 1.4964681 56.22168968 1.4964681 56.22168968 28.2543335 102.45352173 7.26157145 6.59163238 12.86911187 13.43848297 18.125 21.6875.37672852.58668457.75345703 1.17336914 1.14160156 1.77783203 1.51605476 2.36311003 3.02872924 4.72826652 4.53149414 7.09985352 5.34081156 8.37549775 11.32015201 16.25379458 18.0144043 23.59887695 1.910264 2.21727071 2.30384971 3.44385486 2.625 6.3984375-.3125 3.125-.3125 3.125-2.1875 5.4375-3.80523294 3.02180263-6.26102518 3.1507357-11.125 2.6875-8.74319386-3.31917545-16.67146422-10.41972814-22-18 0 2.84230648.77485584 5.46816594 1.4230957 8.22119141 2.47004308 10.49320325 4.7435873 21.02955847 6.91159058 31.5888977.31521306 1.52714355.63456308 3.05343981.95831299 4.57879639 4.85955065 22.95177963 4.85955065 22.95177963.83200073 29.7986145-2.53442424 2.16171479-3.61465139 2.7557946-6.9375 3.125-4.0675922-.39878355-5.31171572-1.43213644-8.1875-4.3125-1.05026283-2.61518436-1.78870874-4.91319153-2.4375-7.625-.2952356-1.14057861-.2952356-1.14057861-.59643555-2.30419922-.68283768-2.68425848-1.32813226-5.37551484-1.96606445-8.07080078-.26522461-1.11761719-.53044922-2.23523438-.80371094-3.38671875-1.77529785-7.52808383-3.50292544-15.06636914-5.19628906-22.61328125.12846313 1.06528931.12846313 1.06528931.25952148 2.15209961.39308731 3.26174874.78553717 6.52357388 1.17797852 9.78540039.13470703 1.11697266.26941406 2.23394531.40820312 3.38476562.26591011 2.21159384.52984166 4.42342651.79174805 6.63549805.21735726 1.82389821.43977684 3.64721951.67236328 5.46923828.73278446 5.96029143.94197323 11.88307335 1.00268555 17.88549805.02900391.97517578.05800781 1.95035156.08789062 2.95507812.06299127 7.0598676.06299127 7.0598676-2.20898437 10.35742188-3.363652 2.11052675-5.30382146 2.11230056-9.19140625 1.375-5.65695082-4.14928967-6.8787716-9.38977288-8-16-.39381973-2.9525022-.67501294-5.90826376-.9375-8.875-.94028653-9.78074658-2.43531608-19.43783173-4.0625-29.125.06058594.69335449.12117187 1.38670898.18359375 2.10107422 2.00590456 23.33407057 2.00590456 23.33407057 2.19140625 34.21142578.02578125.71462402.0515625 1.42924805.078125 2.16552734.01367201 3.51797848-.40520279 5.44393087-2.26171875 8.46728516-2.19140625 2.0546875-2.19140625 2.0546875-5.62890625 2.6171875-4.14691789-.65477651-5.73165558-1.48901177-8.5625-4.5625-1.88866012-4.64881061-2.54988381-9.28816545-3.0703125-14.2421875-.12893143-1.15255783-.12893143-1.15255783-.26046753-2.32839966-.27088346-2.43417211-.53268721-4.86922016-.79421997-7.30441284-.18299299-1.65892055-.36658065-3.31777561-.55078125-4.9765625-.44828904-4.04875409-.88844242-8.09831833-1.32421875-12.1484375-.0501123.70084717-.10022461 1.40169434-.15185547 2.1237793-.23271215 3.18857866-.47780134 6.37609009-.72314453 9.5637207-.11794922 1.65418945-.11794922 1.65418945-.23828125 3.34179688-.08378906 1.06669921-.16757812 2.13339843-.25390625 3.23242187-.07331543.97928467-.14663086 1.95856934-.22216797 2.9675293-.59589049 4.02066656-1.51792645 6.87803387-4.41064453 9.77075195-2.8125.5-2.8125.5-6 0-5.61256227-4.71965463-6.31202526-9.93840093-7-17-.10436224-2.38507959-.17180907-4.76994584-.23988342-7.15632629-.37625502-12.18141802-1.91835327-23.99725507-3.82875061-36.02116394-6.69267491-42.48440384-6.69267491-42.48440384-2.89620972-57.01000977 3.06775628-13.17288198.21991749-24.09188579-4.22265625-36.375-.54799028-1.56993424-1.09367069-3.14067638-1.63720703-4.7121582-1.13634983-3.27554694-2.28484432-6.54643747-3.44335938-9.81420899-1.67826734-4.74264922-3.31211192-9.49954503-4.93115234-14.26269531-.26126175-.76812515-.5225235-1.53625031-.79170227-2.32765198-.52901799-1.55551911-1.05795363-3.11106623-1.58680725-4.66664123-1.34327734-3.94530195-2.69482441-7.88775923-4.04727173-11.82992554-.27232956-.7939769-.54465912-1.5879538-.82524109-2.4059906-1.76117061-5.13088154-3.53078834-10.25879941-5.30374145-15.38562012-13.80944812-39.94941361-13.80944812-39.94941361-18.24601746-57.03260803-.22445801-.84256348-.44891602-1.68512695-.68017578-2.55322266-3.7125822-14.23926004-5.39579636-28.63806827-7.06851196-43.22253418-1.41553715-12.27746324-3.22532521-24.27773543-5.80087281-36.37316894-2.51919563-11.88136395-4.62471236-23.82277836-6.70043945-35.78857422-.1726236-.98672699-.34524719-1.97345398-.52310181-2.99008179-5.56210829-31.8147283-5.56210829-31.8147283-5.35189819-42.00991821.01804687-1.16402344.03609375-2.32804687.0546875-3.52734375.03480469-1.25490234.03480469-1.25490234.0703125-2.53515625-.66 0-1.32 0-2 0-.03955811.74991211-.07911621 1.49982422-.11987305 2.27246094-.5335391 8.38618936-1.77776831 16.53058218-3.29162597 24.77990722-3.0496265 16.71306725-5.37014355 33.45418867-7.19787598 50.34167481-.24175521 2.23170282-.49038037 4.4626876-.75 6.69238281-6.19988718 53.35408863 1.8473427 107.82319864 10.24490356 160.47587586 1.70414077 10.69176637 3.29910941 21.394653 4.78976441 32.11830139.28740367 2.05293736.58462209 4.10449545.88330078 6.15582275 1.4689926 10.37332565 2.37235231 20.78433062 3.24633789 31.22216797.25542324 3.04086816.51880366 6.08097317.78295898 9.12109375 2.02488383 23.62100857 2.60032446 47.1032792 2.59741211 70.80004883-.00008092 3.28911884.00682376 6.57809739.02050782 9.8671875.11970026 29.313367-1.25236342 58.1654711-4.95581055 87.27807617-.13038666 1.0408374-.26077332 2.0816748-.39511108 3.15405273-3.93219428 31.29614825-9.45924458 61.96739562-16.52261353 92.70895386-1.40867723 6.17556553-2.43619764 12.06826035-2.64868164 18.40261841-.04237793 1.24910156-.08475586 2.49820312-.12841797 3.78515625-.19357965 6.52651309-.35608651 13.05356035-.50189209 19.58129883-.05190167 2.18474509-.11302221 4.36903998-.17681885 6.5534668-.52339189 18.57487257.69809678 35.99355702 3.87744141 54.29492187 7.70357486 44.42793396 1.06061203 86.7830636-7.74316406 130.37402344-3.25678227 16.14102072-6.40375459 32.30458967-9.51934815 48.47338867-.45715185 2.36962628-.91724679 4.73866488-1.37762451 7.10766602-2.66782454 13.80052074-4.98165971 27.14572642-4.22412109 41.23339843.11692219 2.33740924.16765935 4.67089204.21191406 7.01074219.42320968 16.49678034 6.43408408 26.1709454 15.8984375 39.1953125.50160645.69528809 1.00321289 1.39057617 1.52001953 2.10693359 5.7170413 7.89478633 11.57158216 15.41863751 18.32373047 22.46337891 4.77457092 5.38004187 6.81194464 8.95101149 6.46875 16.09375-.429853 3.21315118-1.07873112 4.95805449-3.3125 7.3359375-3.4375 1.625-3.4375 1.625-7 3-.66.53625-1.32 1.0725-2 1.625-2 1.375-2 1.375-5.5625 1.875-3.6514458.53111939-4.15013463.88586815-6.4375 3.5-2.89228185.96201167-5.35193686 1.03410859-8.375.875-4.3042716-.07044634-6.81423531 1.09259216-10.625 3.125-3.74650174.45875531-5.34584705.44155324-8.5-1.6875-1.2375-.6496875-1.2375-.6496875-2.5-1.3125-2.00925805.98126556-4.00757511 1.9849911-6 3-6.93560693 1.29817253-11.96034185-.28798849-17.7421875-4.17578125-7.7786866-6.28485579-10.5491862-14.6445615-13.14453125-24.03125-1.96640106-6.9944139-4.51829057-12.3593745-8.5625-18.38671875-4.97749845-7.72327215-4.30634236-17.70592915-3.05078125-26.53125.86088185-6.67183431 1.02310644-13.10897646-.5-19.6875-1.9643641-8.95976143-1.48087088-17.65327506.5-26.5625 3.53155194-16.46375879.77087385-33.09623851-1.25646973-49.61853027-.24485683-2.01739939-.4809782-4.03566282-.71520996-6.05432129-2.8162805-24.07048384-7.59530332-47.56627959-13.01811218-71.17822266-2.16043994-9.45151861-2.74635762-18.41583824-2.76020813-28.08642578-.00233643-.74481628-.00467285-1.48963257-.00708008-2.25701904-.01318187-11.26600893.61793031-22.35129032 1.75708008-33.55548096 2.34344854-23.7502766 1.60554022-41.87348732-5.58154297-64.80957031-4.7456111-15.83888704-5.20801221-32.78832344-6.34423828-49.17871094-.98456895-13.83762924-2.69804205-27.40598054-4.95166016-41.09179687-2.28345044-13.91919755-4.4605763-27.85640684-6.62255859-41.79492188-.11835205-.76217834-.2367041-1.52435669-.35864258-2.30963135-4.18803396-27.05367791-7.32907806-54.11861218-8.29638672-81.49676513-.28495022-7.48415524-1.15867835-14.79943179-2.3449707-22.19360352-.89332031.50466797-.89332031.50466797-1.8046875 1.01953125-3.03073536 1.35358465-5.02385355.73886111-8.1953125-.01953125-.66-.33-1.32-.66-2-1-.77635749 3.55902837-1.23174153 7.028482-1.5090332 10.65942383-.08728363 1.11878036-.17456726 2.23756073-.26449585 3.39024353-.09149323 1.20693008-.18298645 2.41386017-.2772522 3.65736389-.20327148 2.59882871-.40698294 5.19762303-.61108398 7.79638672-.05261113.67425868-.10522225 1.34851736-.15942765 2.04320812-1.83854048 23.55823406-3.70844159 47.06641991-7.17870712 70.45337391-.11063782.75011353-.22127563 1.50022705-.33526611 2.27307129-2.29768851 15.56199298-4.6721915 31.11229528-7.35687256 46.6126709-2.92495767 16.91256943-5.14867504 33.94981756-5.74536133 51.11425781-.92085427 23.25276318-6.22991668 45.00225004-13.5625 67-.93496427 12.76585836.89195339 25.48022359 2.265625 38.1484375.82491711 7.61509302 1.52561482 15.19054929 1.734375 22.8515625.04084717 1.49611816.04084717 1.49611816.08251953 3.02246094.0803175 3.49254832.12846454 6.98429645.16748047 10.47753906.02151123 1.09199707.04302246 2.18399414.06518555 3.30908203.05506185 13.97739982-3.51457853 27.35804669-6.73233033 40.86895752-3.63574627 15.35026534-6.56551363 30.66670253-8.58285522 46.32196045-.14276367 1.09618652-.28552734 2.19237305-.43261719 3.32177734-4.57709691 32.38663265-4.57709691 32.38663265-1.24316406 64.61962891 1.77475972 8.03258303.35175062 15.63363114-.875 23.6640625-.79171027 5.98257585-.69623813 11.73087622.05078125 17.70703125 1.44481366 12.00470175 1.12410348 21.57617823-5.875 31.625-3.29961343 4.90445686-4.77986691 9.52919683-6.1875 15.1875-2.89021388 11.44792899-8.05206048 19.72001669-18.34375 26.046875-4.16352336 1.6467667-8.7007559 1.28785694-13.09375.828125-2-1-4-2-6-3-1.68208635.97383946-3.34738161 1.97695052-5 3-3.75377373.11491144-7.10846714-.63034003-10.5625-2.0625-2.53712742-1.15111164-2.53712742-1.15111164-5.875-.875-4.10639427-.072042-7.57367944-1.07367944-10.5625-4.0625-2.5427952-.49331124-5.0940669-.89978037-7.65234375-1.3046875-2.34765625-.6953125-2.34765625-.6953125-4.34765625-3.6953125-1.4540625-.7115625-1.4540625-.7115625-2.9375-1.4375-3.19552001-1.63036735-4.23099129-2.56500174-6.0625-5.5625-.91062232-4.15074358-.87828275-8.23012719 1.03125-12.078125 1.54240316-2.10795099 3.19079065-4.00799727 4.96875-5.921875 9.9319284-10.79808495 18.29933337-22.35345824 26.5-34.5.7566394-1.11181641.7566394-1.11181641 1.52856445-2.24609375 6.61898402-9.92791501 7.67912145-18.58626808 7.97143555-30.25390625.02723145-.86705566.05446289-1.73411133.08251953-2.62744141.47013641-16.3778967-1.66673804-31.44563854-4.89501953-47.44287109-5.96900119-29.73988089-11.74686708-59.51041401-16.75-89.4296875-.16971313-1.00647583-.33942627-2.01295166-.51428223-3.04992676-2.65557938-15.80664846-4.15849287-31.16379301-4.17321777-47.20007324-.00100708-.76900635-.00201416-1.5380127-.00305176-2.33032227.01371157-15.58763845 1.64057358-30.58336936 4.28356934-45.94482421 2.5743348-15.3895643 3.57347753-30.35381531 3.43603515-45.94873047-.02930021-3.61919178-.02046735-7.23693771-.00756835-10.85620117-.01492421-18.0699807-2.33107832-34.28079167-6.70898438-51.79492188-10.40221673-43.35278434-15.59176069-87.6834614-18.4675293-132.11376953-.17316001-2.66991445-.36635467-5.33525362-.57470703-8.00292969-.81953367-10.755376-.90764355-21.47783579-.89526367-32.25830078.00060425-.979758.0012085-1.95951599.00183105-2.96896362.07992092-48.27272046 4.55645104-96.1425914 11.85040284-143.83352661.81386053-5.33446834 1.60445108-10.67245507 2.39776611-16.01000977.2309436-1.54917114.2309436-1.54917114.46655273-3.12963867 3.09421974-20.88638422 6.02619557-41.805948 8.22094727-62.80786133.11545166-1.09747559.23090332-2.19495117.34985352-3.32568359 5.20277182-52.04349616-.54369048-102.72598574-9.89453125-153.94018555-.96471135-5.31732753-1.66109762-10.60654476-2.20532227-15.98413086-.06469482.58450928-.12938965 1.16901855-.19604492 1.77124023-3.85977662 34.43318315-9.3428204 68.71529629-16.56298828 102.60839844-1.74121577 8.18483973-2.78798151 16.29208138-3.6159668 24.62036133-4.46250432 43.59712844-20.6816327 85.11057956-35.09802246 126.21157837-4.26824443 12.17876899-8.44306381 24.38036498-12.40197754 36.66342163-.20923096.64661591-.41846191 1.29323181-.6340332 1.95944214-2.43433067 7.5407995-4.62989333 14.37729417-4.6784668 22.35305786-.01417969 1.04542969-.02835937 2.09085938-.04296875 3.16796875.18639288 2.9475348.58103583 5.40709569 1.22583008 8.26098633 1.03984524 5.10280596 1.34601054 9.96677591 1.30932617 15.16479492-.00387726.90214233-.00775452 1.80428467-.01174927 2.73376465-.14303089 9.18777178-1.34598748 18.15110867-2.74325561 27.22009277-.7327515 4.77057244-1.4143329 9.54826695-2.08874512 14.32739258-.13273315.94008911-.26546631 1.88017822-.40222168 2.84875488-1.28062048 9.29987001-2.01008606 18.58132437-2.47619629 27.95788574-.99500149 19.58550052-.99500149 19.58550052-5.58251953 26.13085938-2.87470493 1.43735246-4.81894588 1.38172649-8 1-3.75145751-3.10775723-4.34163041-6.1000227-4.828125-10.73828125-.15880343-2.19016397-.29886116-4.38175339-.421875-6.57421875-.07734375-1.12212891-.1546875-2.24425781-.234375-3.40039062-.18803223-2.76172334-.35931672-5.52343912-.515625-8.28710938-.08040527.79583496-.16081055 1.59166992-.24365234 2.41162109-.37213441 3.65498634-.75168926 7.30916677-1.13134766 10.96337891-.18949219 1.87751953-.18949219 1.87751953-.3828125 3.79296875-2.1307734 20.34888599-2.1307734 20.34888599-5.9921875 23.95703125-3.5748394 2.06240735-5.21203206 2.6650372-9.25 1.875-2.16015625-1.33203125-2.16015625-1.33203125-4-4-.53434773-3.75718117-.41939535-7.46352901-.3125-11.25.00966797-1.01191406.01933594-2.02382813.02929688-3.06640625.06384906-4.28066307.19435397-8.49017623.80273437-12.73046875.60802761-5.00263364.56372023-9.92103584.48046875-14.953125-.16604736 1.24088379-.33209473 2.48176758-.50317383 3.76025391-.62105863 4.6196704-1.25618908 9.23731642-1.89550781 13.85449218-.27433046 1.99557199-.54488266 3.99166726-.81152344 5.98828125-.38475431 2.87598472-.78322379 5.74979053-1.18432617 8.62353516-.11575882.88773102-.23151764 1.77546204-.3507843 2.69009399-.81546081 5.71593127-1.93787847 10.60105139-6.00468445 14.95834351-3.27780427 1.63890214-5.640416 1.576198-9.25 1.125-2.19775391-1.60375977-2.19775391-1.60375977-4-4-.43896484-3.12475586-.43896484-3.12475586-.3359375-6.77734375.02352036-.98636696.02352036-.98636696.04751587-1.99266052.22259657-7.28919997.90972124-14.54175306 1.66342163-21.79249573.18949219-1.94036133.18949219-1.94036133.3828125-3.91992188.856688-8.54546277 1.96037313-17.02617495 3.2421875-25.51757812-1.08476779 4.60399668-2.16913971 9.20808637-3.25317383 13.81225586-.3674091 1.56024583-.73491898 3.12046793-1.10253906 4.68066406-.35710898 1.51564262-.71401299 3.03133356-1.07070923 4.54707337-.35331596 1.50018563-.7074604 3.00017639-1.06243896 4.49996948-.8139435 3.44781177-1.60658967 6.89667654-2.3631897 10.35774231-.25378418 1.15781982-.50756836 2.31563965-.76904297 3.50854492-.21454834 1.00740234-.42909668 2.01480469-.65014648 3.05273438-1.12474109 3.92171028-2.24326943 5.402192-5.72875977 7.54101562-4.30274909.69151325-6.20945312.51557405-9.875-1.875-3.21569796-3.21569796-3.28593015-5.06706107-3.31982422-9.51293945.05762794-6.33253213 1.003519-12.22468517 2.31201172-18.4050293.2172171-1.07147781.4344342-2.14295563.65823364-3.24690247.68895448-3.38488409 1.39403013-6.76616925 2.09957886-10.14762878.46589836-2.27777934.93075073-4.55577288 1.39453125-6.83398438 1.84074086-9.00891496 3.71469551-18.00699475 5.73046875-26.97851562-.60585938.72445312-1.21171875 1.44890625-1.8359375 2.1953125-6.12491438 7.0672089-13.58946803 15.30849169-23.4765625 16.0546875-3.88405077-.26332548-5.63281404-.91406368-8.6875-3.25-1.48731668-2.97463337-1.4313052-4.67278843-1-8 1.69447694-2.91375002 3.83565517-5.42405968 6-8 7.53251335-9.09373941 14.09962859-18.69572125 20.3203125-28.72265625 6.10380804-9.80600949 12.90572718-17.99753005 21.703125-25.515625 17.03944984-15.18733572 18.22273526-35.81319449 20.5390625-57.19921875.22033913-1.99465115.44088099-3.98927991.66162109-5.98388672 1.71026669-15.5580643 3.22830302-31.10258722 4.24411011-46.7225647 2.25159202-34.59814872 5.88900506-68.15063075 17.49026489-101.11947631 4.44507049-13.04725236 5.65437713-26.97406825 7.06182862-40.60574341 1.77273943-17.09988084 3.81378739-34.15542687 6.10467529-51.19332886.18316269-1.36890884.18316269-1.36890884.37002563-2.76547241.35583626-2.65275309.71475712-5.3050648 1.07528687-7.95718384.11561279-.85463837.23122559-1.70927673.3503418-2.58981323.5789703-4.21786089 1.20027521-8.42230161 1.90625-12.62088013 2.02710712-12.19765606 2.64991664-24.0871747 2.7355957-36.44165039.33216152-37.17860385 2.19186235-79.51198504 28.4375-108.5625l2.046875-2.328125c15.2151208-16.32571356 36.94253648-22.27680451 57.77929688-27.82080078 11.62693637-3.11780771 23.04306129-6.69374162 34.42382812-10.60107422.76670013-.26256592 1.53340027-.52513184 2.32333374-.7956543 22.70645882-6.44459526 22.70645882-6.44459526 41.03604126-19.64575195.37640625-.78246094.7528125-1.56492187 1.140625-2.37109375.38671875-.76441406.7734375-1.52882813 1.171875-2.31640625 4.22573464-12.23319557 6.37085245-30.43854153 1.72265625-42.6953125-1.7230116-2.62787242-1.7230116-2.62787242-3.703125-4.984375-6.59294478-8.29092654-11.94140625-23.67864404-11.94140625-34.44140625-1.216875.0825-2.43375.165-3.6875.25-2.96986266-.03131665-4.24091502-.2125031-6.9375-1.625-5.67022547-6.26709131-8.86075762-13.37544209-11.6875-21.25-.38176392-1.05610474-.38176392-1.05610474-.77124023-2.13354492-3.24436666-9.47019248-4.93235102-21.19318132-1.72875977-30.80395508 2.12025838-2.85138196 3.71714145-4.64907061 6.8125-6.4375 2.0246582-.15869141 2.0246582-.15869141 4.17578125-.1015625 1.26199219.03351563 2.52398437.06703125 3.82421875.1015625-.02094727-1.31492432-.04189453-2.62984863-.06347656-3.98461914-.07321635-4.94812836-.11861584-9.8962104-.15625-14.84472656-.01993161-2.12866304-.04708697-4.25727267-.08203125-6.38574219-.20109559-12.57734625-.02320988-23.8631219 4.30175781-35.78491211.40001221-1.12039673.40001221-1.12039673.80810547-2.26342773 3.78241404-9.58304699 9.93452116-16.68408759 17.19189453-23.73657227.63035156-.61617187 1.26070313-1.23234375 1.91015625-1.8671875 7.20884201-6.57828136 15.53303974-10.50107603 24.58984375-13.8828125.7827832-.29382568 1.56556641-.58765137 2.37207031-.89038086 11.00364756-3.95751244 21.49687461-4.76847647 33.12792969-4.73461914z" fill="#fed8ac" transform="translate(511 21.625)"/>

                            <!-- Anatomski organi obeleže tačne delove tela -->

                            <!-- MOZAK -->
                            <ellipse class="clickable-organ" cx="512" cy="120" rx="28" ry="22" fill="#f4a6cd" stroke="#d63384" stroke-width="2"
                                     data-region="brain"
                                     data-title="Mozak"
                                     data-symptoms="Glavobolja, problemi sa memorijom, koncentracijom"
                                     data-causes="Stres, dehidracija, loš san"
                                     data-solutions="Kvalitetan san, meditacija, omega-3"/>

                            <!-- SRCE -->
                            <path class="clickable-organ" d="M512 320 C495 305, 475 305, 475 325 C475 345, 495 365, 512 380 C529 365, 549 345, 549 325 C549 305, 529 305, 512 320 Z"
                                  fill="#ff6b6b" stroke="#dc3545" stroke-width="2"
                                  data-region="heart"
                                  data-title="Srce"
                                  data-symptoms="Lupanje srca, kratko dah, bol u grudima"
                                  data-causes="Stres, loša ishrana, pušenje"
                                  data-solutions="Kardio vežbe, zdrava ishrana, prestanak pušenja"/>

                            <!-- PLUĆA -->
                            <ellipse class="clickable-organ" cx="475" cy="340" rx="22" ry="35" fill="#a8dadc" stroke="#457b9d" stroke-width="2"
                                     data-region="left-lung"
                                     data-title="Levo pluće"
                                     data-symptoms="Kratak dah, kašalj, bol pri disanju"
                                     data-causes="Pušenje, zagađenje vazduha, infekcije"
                                     data-solutions="Prestanak pušenja, duboko disanje, čist vazduh"/>

                            <ellipse class="clickable-organ" cx="549" cy="340" rx="22" ry="35" fill="#a8dadc" stroke="#457b9d" stroke-width="2"
                                     data-region="right-lung"
                                     data-title="Desno pluće"
                                     data-symptoms="Kratak dah, kašalj, bol pri disanju"
                                     data-causes="Pušenje, zagađenje vazduha, infekcije"
                                     data-solutions="Prestanak pušenja, duboko disanje, čist vazduh"/>

                            <!-- JETRA -->
                            <path class="clickable-organ" d="M540 420 Q570 415, 575 440 Q570 465, 540 460 Q525 455, 525 440 Q530 425, 540 420 Z"
                                  fill="#daa520" stroke="#b8860b" stroke-width="2"
                                  data-region="liver"
                                  data-title="Jetra"
                                  data-symptoms="Umor, žutilo, bol pod desnim rebrom"
                                  data-causes="Alkohol, masna hrana, virusi"
                                  data-solutions="Ograničiti alkohol, zdrava ishrana, detoksikacija"/>

                            <!-- STOMAK -->
                            <ellipse class="clickable-organ" cx="495" cy="460" rx="25" ry="18" fill="#ff9f43" stroke="#e55039" stroke-width="2"
                                     data-region="stomach"
                                     data-title="Stomak"
                                     data-symptoms="Bol u stomaku, nadutost, žgaravica"
                                     data-causes="Začinska hrana, stres, bakterije"
                                     data-solutions="Blaga hrana, probiotici, sporije jedenje"/>

                            <!-- BUBREZI -->
                            <ellipse class="clickable-organ" cx="470" cy="480" rx="12" ry="20" fill="#8e44ad" stroke="#6c3483" stroke-width="2"
                                     data-region="left-kidney"
                                     data-title="Levi bubreg"
                                     data-symptoms="Bol u leđima, problemi sa mokrenjem"
                                     data-causes="Dehidracija, infekcije, kamenci"
                                     data-solutions="Piti više vode, zdrava ishrana, lekarska kontrola"/>

                            <ellipse class="clickable-organ" cx="554" cy="480" rx="12" ry="20" fill="#8e44ad" stroke="#6c3483" stroke-width="2"
                                     data-region="right-kidney"
                                     data-title="Desni bubreg"
                                     data-symptoms="Bol u leđima, problemi sa mokrenjem"
                                     data-causes="Dehidracija, infekcije, kamenci"
                                     data-solutions="Piti više vode, zdrava ishrana, lekarska kontrola"/>

                            <!-- CREVA -->
                            <path class="clickable-organ" d="M485 520 Q520 515, 530 540 Q525 565, 495 570 Q475 565, 470 540 Q480 520, 485 520 Z"
                                  fill="#f39c12" stroke="#d68910" stroke-width="2"
                                  data-region="intestines"
                                  data-title="Creva"
                                  data-symptoms="Nadutost, dijareja, konstipacija"
                                  data-causes="Loša ishrana, stres, bakterijski disbalans"
                                  data-solutions="Probiotici, vlaknasta hrana, hidratacija"/>

                            <!-- MOKRAĆNA BEŠIKA -->
                            <ellipse class="clickable-organ" cx="512" cy="620" rx="18" ry="12" fill="#3498db" stroke="#2980b9" stroke-width="2"
                                     data-region="bladder"
                                     data-title="Mokraćna bešika"
                                     data-symptoms="Često mokrenje, pečenje, bol"
                                     data-causes="Infekcije, dehidracija, stres"
                                     data-solutions="Piti više vode, higijena, čaj od brusnice"/>


                        </svg>
                    </div>

                    <!-- AI Analysis Side Panel -->
                    <div class="ai-analysis-panel" id="ai-analysis-panel">
                        <div class="ai-panel-header">
                            <div class="ai-indicator">
                                <div class="ai-pulse"></div>
                                <span class="ai-label">🤖 AI Analiza</span>
                            </div>
                            <button class="close-panel" onclick="closeAnalysisPanel()">×</button>
                        </div>

                        <div class="ai-panel-content">
                            <h3 class="region-title" id="panel-region-title">Izaberite region za analizu</h3>

                            <div class="analysis-section" id="symptoms-section" style="display:none;">
                                <div class="section-header">
                                    <span class="section-icon">👁️</span>
                                    <h4>Šta vidimo</h4>
                                </div>
                                <p class="analysis-text" id="symptoms-text"></p>
                            </div>

                            <div class="analysis-section" id="causes-section" style="display:none;">
                                <div class="section-header">
                                    <span class="section-icon">🔍</span>
                                    <h4>Mogući uzrok</h4>
                                </div>
                                <p class="analysis-text" id="causes-text"></p>
                            </div>

                            <div class="analysis-section" id="solutions-section" style="display:none;">
                                <div class="section-header">
                                    <span class="section-icon">💡</span>
                                    <h4>Prva pomoć</h4>
                                </div>
                                <p class="analysis-text" id="solutions-text"></p>
                            </div>

                            <div class="ai-recommendation" id="ai-recommendation" style="display:none;">
                                <div class="recommendation-header">
                                    <span class="rec-icon">🎯</span>
                                    <h4>AI Preporuka</h4>
                                </div>
                                <button class="recommendation-btn" id="recommendation-btn">
                                    Preporučeni plan za 30 dana
                                    <span class="btn-arrow">→</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cause-Effect-Solution Section -->
                <div class="cause-effect-solution">
                    <h3>Uzrok – posledica – rešenje</h3>

                    <div class="solution-section">
                        <h4>Šta je verovatan uzrok?</h4>
                        <p>Kombinacija stresa i niske hidratacije utiče na varenje i imunitet.</p>
                    </div>

                    <div class="solution-section">
                        <h4>Šta radimo 30 dana?</h4>
                        <ul class="solution-list">
                            <li><strong>Jutro:</strong> reset metabolizma i podrška imunitetu</li>
                            <li><strong>Pre podne:</strong> blagi biljni tonik za varenje</li>
                            <li><strong>Popodne:</strong> mineralna podrška i smanjenje umora</li>
                        </ul>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="results-navigation">
                    <button class="wvp-health-prev">Nazad</button>
                    <button class="wvp-health-next">Vidi preporuke</button>
                </div>
            </div>
        </div>

        <!-- Product Recommendations Step -->
        <?php $step++; ?>
        <div class="wvp-health-step wvp-recommendations-step" data-step="<?php echo $step; ?>" style="display:none;">

            <!-- Personalized Recommendations -->
            <div class="wvp-recommendations-section">
                <div class="recommendations-header">
                    <h2 class="recommendations-title">Tvoj personalizovani paket</h2>
                    <p class="recommendations-subtitle">Preporučeni proizvodi na osnovu vaše analize</p>
                </div>

                <!-- Product Recommendations Grid -->
                <div class="product-recommendations">

                    <!-- Product 1 -->
                    <div class="product-card">
                        <div class="product-info">
                            <h3 class="product-name">Sok od zelenog žita</h3>
                            <p class="product-purpose">jutarnji detoks i imunitet</p>
                            <div class="product-dosage">
                                <strong>Doziranje:</strong> 100 ml ujutru, pre doručka (30 dana)
                            </div>
                            <div class="product-reason">
                                <strong>Zašto tebi:</strong> podrška energiji i čistini creva
                            </div>
                        </div>
                        <div class="product-actions">
                            <button class="product-details-btn">Detalji proizvoda</button>
                            <button class="add-to-cart-btn">Dodaj u korpu</button>
                        </div>
                    </div>

                    <!-- Product 2 -->
                    <div class="product-card">
                        <div class="product-info">
                            <h3 class="product-name">Sok od spelte</h3>
                            <p class="product-purpose">blaga vlaknasta podrška</p>
                            <div class="product-dosage">
                                <strong>Doziranje:</strong> 100 ml uz popodnevni obrok (30 dana)
                            </div>
                            <div class="product-reason">
                                <strong>Zašto tebi:</strong> ravnoteža varenja, osećaj lakoće
                            </div>
                        </div>
                        <div class="product-actions">
                            <button class="product-details-btn">Detalji proizvoda</button>
                            <button class="add-to-cart-btn">Dodaj u korpu</button>
                        </div>
                    </div>

                    <!-- Product 3 -->
                    <div class="product-card">
                        <div class="product-info">
                            <h3 class="product-name">Sok od mirođije</h3>
                            <p class="product-purpose">smiren stomak i gasovi</p>
                            <div class="product-dosage">
                                <strong>Doziranje:</strong> 50–100 ml po potrebi posle obroka
                            </div>
                            <div class="product-reason">
                                <strong>Zašto tebi:</strong> prirodna pomoć nadutosti
                            </div>
                        </div>
                        <div class="product-actions">
                            <button class="product-details-btn">Detalji proizvoda</button>
                            <button class="add-to-cart-btn">Dodaj u korpu</button>
                        </div>
                    </div>

                    <!-- Product 4 -->
                    <div class="product-card">
                        <div class="product-info">
                            <h3 class="product-name">Sok od ječma</h3>
                            <p class="product-purpose">minerali za fokus i oporavak</p>
                            <div class="product-dosage">
                                <strong>Doziranje:</strong> 100 ml kasno prepodne ili posle treninga
                            </div>
                            <div class="product-reason">
                                <strong>Zašto tebi:</strong> stabilna energija bez skokova
                            </div>
                        </div>
                        <div class="product-actions">
                            <button class="product-details-btn">Detalji proizvoda</button>
                            <button class="add-to-cart-btn">Dodaj u korpu</button>
                        </div>
                    </div>

                </div>

                <!-- Package Action -->
                <div class="package-action">
                    <button class="add-package-btn">Dodaj ceo paket u korpu</button>
                    <p class="package-note">Količine su predložene za 30 dana. Možeš izmeniti svaku stavku.</p>
                </div>

                <!-- Daily Plan Card -->
                <div class="daily-plan-card">
                    <h3>Tvoj dnevni ritam</h3>
                    <div class="daily-schedule">
                        <div class="schedule-item">
                            <span class="time">07:30</span>
                            <span class="product">Zeleno žito 100 ml</span>
                            <span class="note">(pre doručka)</span>
                        </div>
                        <div class="schedule-item">
                            <span class="time">11:00</span>
                            <span class="product">Ječam 100 ml</span>
                            <span class="note">(fokus)</span>
                        </div>
                        <div class="schedule-item">
                            <span class="time">15:00</span>
                            <span class="product">Spelta 100 ml</span>
                            <span class="note">(lako varenje)</span>
                        </div>
                        <div class="schedule-item">
                            <span class="time">Po potrebi</span>
                            <span class="product">Mirođija 50–100 ml</span>
                            <span class="note">(posle teškog obroka)</span>
                        </div>
                    </div>
                    <div class="plan-actions">
                        <button class="email-plan-btn">Pošalji sebi u email</button>
                        <button class="pdf-plan-btn">Preuzmi PDF plan</button>
                    </div>
                </div>

                <!-- Important Notice -->
                <div class="important-notice">
                    <h4>⚠️ Važno</h4>
                    <p>Ako si trudna, dojiš, ili imaš hroničnu terapiju — konsultuj lekara pre upotrebe.</p>
                    <p>Kontraindikacije su istaknute na stranici svakog proizvoda.</p>
                </div>

                <!-- CTA Section -->
                <div class="cta-section">
                    <h3>Spremno za start?</h3>
                    <p>Uzmi paket za prvih 14 dana i oseti promenu u ritmu i lakoći.</p>
                    <div class="cta-buttons">
                        <button class="cta-primary">Dodaj ceo paket</button>
                        <button class="cta-secondary">Napravi sopstveni paket</button>
                    </div>
                    <div class="social-proof">
                        <span class="rating">⭐⭐⭐⭐⭐ 4.9/5</span>
                        <span class="reviews">na osnovu 1.240 ocena korisnika</span>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="recommendations-navigation">
                    <button class="wvp-health-prev">Nazad</button>
                </div>
            </div>
        </div>
        <div id="wvp-health-debug-container" style="display:none;">
            <label><input type="checkbox" id="wvp-health-debug-toggle"> Prikaži log greške</label>
            <pre id="wvp-health-debug-log" style="display:none;"></pre>
        </div>
    </div>

    <style>
    .wvp-health-completion-step {
        text-align: center;
        padding: 60px 20px;
    }
    .wvp-completion-header h2 {
        color: #28a745;
        margin-bottom: 15px;
        font-size: 32px;
    }
    .wvp-completion-header p {
        font-size: 18px;
        color: #6c757d;
        margin-bottom: 40px;
    }
    .wvp-completion-loading {
        margin: 40px 0;
    }
    .wvp-completion-spinner {
        width: 60px;
        height: 60px;
        border: 6px solid #f3f3f3;
        border-top: 6px solid #28a745;
        border-radius: 50%;
        animation: completion-spin 1s linear infinite;
        margin: 0 auto 20px;
    }
    .wvp-completion-loading p {
        font-size: 16px;
        color: #28a745;
        font-weight: 500;
    }
    @keyframes completion-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    </style>

    <?php
    wp_enqueue_style( 'wvp-health-style', WVP_PLUGIN_URL . 'assets/css/health-quiz.css', array(), WVP_VERSION );
    wp_enqueue_script( 'wvp-health-script', WVP_PLUGIN_URL . 'assets/js/health-quiz.js', array('jquery'), WVP_VERSION, true );
    wp_enqueue_script( 'wvp-health-notify', WVP_PLUGIN_URL . 'assets/js/health-quiz-notify.js', array('jquery', 'wvp-health-script'), WVP_VERSION, true );
    $health_quiz_slug = get_option('wvp_health_quiz_url_slug', 'analiza-zdravstvenog-stanja');
    wp_localize_script( 'wvp-health-script', 'wvpHealthData', array(
        'ajaxurl'   => admin_url( 'admin-ajax.php' ),
        'nonce'     => wp_create_nonce( 'wvp_health_quiz_nonce' ),
        'checkout'  => wc_get_checkout_url(),
        'cart_url'  => wc_get_cart_url(),
        'base_url'  => home_url('/' . $health_quiz_slug),
        'products'  => $product_data,
        'questions' => $questions,
        'universal' => $universal_package,
        'debug'     => $debug_log,
        'initial_step' => $initial_step,
        'status_texts' => get_option( 'wvp_health_quiz_status_texts', array(
            'low'  => '',
            'mid'  => '',
            'high' => '',
        ) )
    ) );
    ?>

                </div> <!-- col-lg-10 col-xl-8 mx-auto -->
            </div> <!-- row -->
        </div> <!-- container-fluid -->
    </div> <!-- wvp-single-package-wrapper -->

    <style>
/* AI Health Quiz - Modern Design */
.wvp-single-package-wrapper {
    padding: 40px 0;
    background: #e9ecef;
    min-height: 70vh;
    width: 100%;
    margin: 0;
}

.wvp-single-package-wrapper::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="25" r="1" fill="rgba(255,255,255,0.05)"/><circle cx="25" cy="75" r="1" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
    pointer-events: none;
}

.wvp-single-package-wrapper .container-fluid {
    max-width: 1200px !important;
    margin: 0 auto !important;
    padding: 0 20px !important;
    position: relative;
    z-index: 1;
}

/* AI Header Styling */
.wvp-ai-health-header {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    padding: 40px;
    transition: all 0.5s ease;
    margin-bottom: 40px;
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 40px;
}

.wvp-ai-health-header.hidden {
    display: none;
}

/* Hide header when any step other than first is active */
.wvp-health-step[data-step]:not([data-step="1"]):not([style*="display: none"]) ~ * .wvp-ai-health-header,
.wvp-health-step[data-step]:not([data-step="1"])[style*="display: block"] ~ * .wvp-ai-health-header {
    display: none;
}

/* Alternative approach - hide header when survey or other steps are visible */
.wvp-survey-step:not([style*="display: none"]) ~ .wvp-ai-health-header,
.wvp-results-step:not([style*="display: none"]) ~ .wvp-ai-health-header,
.wvp-recommendations-step:not([style*="display: none"]) ~ .wvp-ai-health-header {
    display: none;
}

/* Hide header when quiz has started */
body.quiz-started .wvp-ai-health-header {
    display: none !important;
}

/* Hide header on survey steps */
.wvp-survey-step .wvp-ai-health-header,
.wvp-survey-step ~ .wvp-ai-health-header,
.wvp-survey-step + * .wvp-ai-health-header {
    display: none !important;
}

/* Force hide header when not on first step */
#wvp-health-quiz .wvp-ai-health-header {
    display: none !important;
}

/* Nuclear option - hide header completely after any interaction */
.wvp-ai-health-header:not(.show-initial) {
    display: none !important;
}

/* Hide header completely when quiz container exists */
.wvp-health-quiz .wvp-ai-health-header,
.wvp-survey-step ~ .wvp-ai-health-header,
body:has(.wvp-survey-step) .wvp-ai-health-header {
    display: none !important;
}

/* Show header only on very first load */
.wvp-ai-health-header {
    display: none !important;
}

.wvp-ai-health-header.show-initial:not(.hidden) {
    display: grid !important;
}
    align-items: center;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.ai-branding {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.ai-icon {
    font-size: 28px;
    animation: aiPulse 2s ease-in-out infinite;
}

.ai-badge {
    background: linear-gradient(135deg, #ff6b6b, #feca57);
    color: white;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
}

.ai-health-title {
    margin: 0;
    color: white;
}

.title-main {
    display: block;
    font-size: 42px;
    font-weight: 800;
    line-height: 1.1;
    margin-bottom: 8px;
    background: linear-gradient(135deg, #ffffff, #f8f9fa);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.title-subtitle {
    display: block;
    font-size: 16px;
    font-weight: 400;
    color: #1d2327;
    text-transform: uppercase;
    letter-spacing: 2px;
}

.description-main {
    color: rgba(0, 88, 64, 1);
    font-size: 18px;
    line-height: 1.6;
    margin-bottom: 20px;
}

.medical-disclaimer {
    color: #2c3e50;
    font-size: 16px;
    font-weight: bold;
    line-height: 1.4;
    margin-bottom: 30px;
    background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
    padding: 15px 20px;
    border-radius: 8px;
    border-left: 4px solid #FF8C00;
    box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3);
}

.ai-features {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: rgba(0, 88, 64, 1);
    font-weight: 500;
}

.feature-icon {
    font-size: 20px;
}

.ai-visual {
    position: relative;
}

.ai-pulse-animation {
    position: relative;
    width: 120px;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pulse-circle {
    position: absolute;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    animation: aiPulse 3s ease-in-out infinite;
}

.pulse-1 {
    width: 80px;
    height: 80px;
    animation-delay: 0s;
}

.pulse-2 {
    width: 100px;
    height: 100px;
    animation-delay: 0.5s;
}

.pulse-3 {
    width: 120px;
    height: 120px;
    animation-delay: 1s;
}

.ai-brain {
    font-size: 40px;
    z-index: 2;
    position: relative;
    animation: brainThink 4s ease-in-out infinite;
}

@keyframes aiPulse {
    0%, 100% {
        transform: scale(1);
        opacity: 0.7;
    }
    50% {
        transform: scale(1.1);
        opacity: 1;
    }
}

@keyframes brainThink {
    0%, 100% {
        transform: scale(1) rotate(0deg);
    }
    25% {
        transform: scale(1.1) rotate(-2deg);
    }
    75% {
        transform: scale(1.1) rotate(2deg);
    }
}

/* Responsive design - exactly like package page */
@media (max-width: 1200px) {
    .wvp-single-package-wrapper .container-fluid {
        max-width: 100% !important;
        padding: 0 30px !important;
    }

    .wvp-ai-health-header {
        grid-template-columns: 1fr;
        text-align: center;
        padding: 30px;
    }

    .ai-features {
        grid-template-columns: repeat(2, 1fr);
    }

    .title-main {
        font-size: 32px;
    }
}

@media (max-width: 768px) {
    .wvp-single-package-wrapper {
        padding: 20px 0 !important;
    }

    .wvp-single-package-wrapper .container-fluid {
        padding: 0 15px !important;
    }
}

/* Force bootstrap grid to work correctly */
.wvp-single-package-wrapper .col-lg-10.col-xl-8.mx-auto {
    width: auto !important;
    max-width: none !important;
    flex: 0 0 auto !important;
}

/* Ensure container takes full width */
.wvp-single-package-wrapper {
    width: 100% !important;
    max-width: none !important;
    box-sizing: border-box !important;
}

/* Hide sidebars only */
body .sidebar {
    display: none !important;
}

/* Additional specific selectors for WordPress themes */
body .primary {
    width: 100% !important;
    flex: 0 0 100% !important;
    max-width: 100% !important;
}

body #primary {
    width: 100% !important;
    flex: 0 0 100% !important;
    max-width: 100% !important;
}

body .entry-content {
    width: 100% !important;
    max-width: none !important;
}

body .site-main {
    width: 100% !important;
    max-width: none !important;
}

/* Enhanced Breadcrumbs */
.wvp-breadcrumbs {
    margin-bottom: 25px;
    padding: 20px 0;
}

.breadcrumb {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 12px 20px;
    margin: 0;
    list-style: none;
    display: flex;
    flex-wrap: wrap;
    font-size: 14px;
    border-radius: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border: 1px solid rgba(0, 88, 64, 0.1);
}

.breadcrumb-item {
    display: inline-flex;
    align-items: center;
    font-weight: 500;
}

.breadcrumb-item + .breadcrumb-item:before {
    content: "→";
    padding: 0 12px;
    color: rgba(0, 88, 64, 0.6);
    font-weight: 600;
}

.breadcrumb-item a {
    color: rgba(0, 88, 64, 0.8);
    text-decoration: none;
    padding: 4px 8px;
    border-radius: 15px;
    transition: all 0.3s ease;
}

.breadcrumb-item a:hover {
    background: rgba(0, 88, 64, 0.1);
    color: rgba(0, 88, 64, 1);
    text-decoration: none;
}

.breadcrumb-item.active {
    color: rgba(0, 88, 64, 1);
    font-weight: 600;
    background: rgba(0, 88, 64, 0.1);
    padding: 4px 12px;
    border-radius: 15px;
}

/* Header Section */
.wvp-health-quiz-header {
    background: #fff;
    padding: 40px;
    border-radius: 8px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.wvp-health-quiz-header::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    animation: float 20s ease-in-out infinite;
    pointer-events: none;
}

@keyframes float {
    0%, 100% { transform: translate(0, 0) rotate(0deg); }
    33% { transform: translate(30px, -30px) rotate(120deg); }
    66% { transform: translate(-20px, 20px) rotate(240deg); }
}

.health-quiz-title {
    font-size: 2.5em;
    color: #1d2327;
    margin-bottom: 20px;
    text-align: center;
    font-weight: 600;
}

.health-quiz-featured-image {
    margin: 30px 0;
    position: relative;
    z-index: 1;
}

.health-icons {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin: 20px 0;
}

.health-icon {
    font-size: 3em;
    padding: 15px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255,255,255,0.3);
    animation: bounce 2s ease-in-out infinite;
}

.health-icon:nth-child(2) { animation-delay: 0.2s; }
.health-icon:nth-child(3) { animation-delay: 0.4s; }
.health-icon:nth-child(4) { animation-delay: 0.6s; }

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.health-quiz-description {
    font-size: 1.1em;
    line-height: 1.6;
    margin-bottom: 25px;
    text-align: center;
    color: #495057;
}

.health-quiz-benefits {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.benefit-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px 20px;
    background: rgba(255,255,255,0.15);
    border-radius: 25px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    transition: all 0.3s ease;
}

.benefit-item:hover {
    background: rgba(255,255,255,0.25);
    transform: translateY(-2px);
}

.benefit-icon {
    font-size: 1.5em;
    flex-shrink: 0;
}

/* Form Section */
.wvp-health-form-section {
    background: #fff;
    padding: 40px;
    border-radius: 8px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.wvp-health-form-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="glow"><stop offset="0%" stop-color="white" stop-opacity="0.1"/><stop offset="100%" stop-color="white" stop-opacity="0"/></radialGradient></defs><circle cx="200" cy="200" r="300" fill="url(%23glow)"/><circle cx="800" cy="800" r="200" fill="url(%23glow)"/></svg>');
    pointer-events: none;
}

.form-container {
    position: relative;
    z-index: 1;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    padding: 30px;
}

.form-label {
    color: #fff;
    font-weight: 600;
    font-size: 1.1em;
    margin-bottom: 8px;
    display: block;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.form-control {
    background: rgba(255, 255, 255, 0.9);
    border: 2px solid transparent;
    border-radius: 12px;
    padding: 15px 20px;
    font-size: 1em;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(10px);
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
}

.form-control:focus {
    outline: none;
    background: rgba(255, 255, 255, 1);
    border-color: #005840;
    box-shadow: 0 0 0 4px rgba(0, 88, 64, 0.2), inset 0 2px 4px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}

.form-control::placeholder {
    color: #6c757d;
    opacity: 0.8;
}

.form-section-title {
    font-size: 2.2em;
    color: #fff;
    margin-bottom: 15px;
    text-align: center;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    background: linear-gradient(45deg, #fff, #e3f2fd);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.form-section-description {
    text-align: center;
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.1em;
    margin-bottom: 30px;
    line-height: 1.6;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}

/* Enhanced Button Styling */
.ai-submit-btn {
    background: linear-gradient(45deg, #005840 0%, #005840 50%, #005840 100%);
    border: none;
    border-radius: 50px;
    padding: 18px 40px;
    font-size: 1.2em;
    font-weight: 700;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 1px;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(0, 88, 64, 0.4);
    width: 100%;
    margin-top: 20px;
}

.ai-submit-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.6s;
}

.ai-submit-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 35px rgba(0, 88, 64, 0.6);
    background: linear-gradient(45deg, #2d5a3d 0%, #2d5a3d 50%, #2d5a3d 100%);
}

.ai-submit-btn:hover::before {
    left: 100%;
}

.ai-submit-btn:active {
    transform: translateY(-1px);
    box-shadow: 0 10px 20px rgba(0, 88, 64, 0.5);
}

/* Form Grid Layout */
.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

/* User Welcome Notice */
.wvp-health-user-notice {
    display: flex;
    align-items: center;
    gap: 15px;
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border: 2px solid #28a745;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
}

.user-welcome-icon {
    font-size: 2.5em;
    flex-shrink: 0;
}

.user-welcome-content p {
    margin: 5px 0;
    color: #155724;
}

.user-welcome-content strong {
    color: #0f4419;
}

/* AI Form Styling */
.wvp-ai-form-section {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 24px;
    padding: 0;
    margin-bottom: 30px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.form-header-card {
    background: #f8f9fa;
    padding: 40px;
    color: white;
    position: relative;
    overflow: hidden;
}

.form-header-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    animation: headerShimmer 6s ease-in-out infinite;
}

.form-step-indicator {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.step-number {
    width: 40px;
    height: 40px;
    background: rgba(0, 88, 64, 1);
    border: 2px solid #B8D9BC;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 18px;
    color: white;
}

.step-label {
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: rgba(0, 88, 64, 1) !important;
    font-weight: 600;
    background: transparent;
    padding: 4px 8px;
    border-radius: 4px;
}

.form-title {
    margin: 0 0 15px 0;
    font-size: 28px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 12px;
    color: rgba(0, 88, 64, 1);
}

.title-icon {
    font-size: 32px;
    color: rgba(0, 88, 64, 1);
}

.form-description {
    font-size: 16px;
    line-height: 1.6;
    margin-bottom: 25px;
    color: #2c3e50;
    font-weight: 500;
}

.ai-processing-indicator {
    display: flex;
    align-items: center;
    gap: 15px;
    opacity: 0.8;
}

.processing-dots {
    display: flex;
    gap: 4px;
}

.dot {
    width: 8px;
    height: 8px;
    background: rgba(255, 255, 255, 0.6);
    border-radius: 50%;
    animation: dotPulse 1.5s ease-in-out infinite;
}

.dot:nth-child(2) {
    animation-delay: 0.2s;
}

.dot:nth-child(3) {
    animation-delay: 0.4s;
}

.processing-text {
    font-size: 14px;
    font-style: italic;
    color: #2c3e50;
    font-weight: 500;
}

.ai-form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    padding: 40px;
    background: white;
}

.form-group {
    position: relative;
    margin-bottom: 25px;
}

@keyframes headerShimmer {
    0%, 100% {
        transform: translateX(-100%) translateY(-100%);
    }
    50% {
        transform: translateX(-50%) translateY(-50%);
    }
}

@keyframes dotPulse {
    0%, 100% {
        transform: scale(1);
        opacity: 0.6;
    }
    50% {
        transform: scale(1.4);
        opacity: 1;
    }
}

.form-label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2c3e50;
    font-size: 14px;
}

.label-icon {
    font-size: 1.2em;
    flex-shrink: 0;
}

.label-text {
    font-weight: 600;
}

.form-input, .form-select {
    display: block;
    width: 100%;
    padding: 18px 20px;
    border: 2px solid #e1e5e9;
    border-radius: 12px;
    font-size: 16px;
    line-height: 1.5;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    background: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    margin-top: 8px;
    position: relative;
    color: #2c3e50;
    min-height: 56px;
}

.form-input:hover, .form-select:hover {
    border-color: #667eea;
    box-shadow: 0 4px 16px rgba(102, 126, 234, 0.15);
    transform: translateY(-1px);
}

.form-input:focus, .form-select:focus {
    outline: none;
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    transform: translateY(-1px);
}

.form-input::placeholder {
    color: #a0a9b8;
    font-style: italic;
}

.form-select option {
    color: #2c3e50;
    background: white;
    padding: 10px;
}

.form-select option:first-child {
    color: #6c757d;
    font-style: italic;
}

.wvp-health-error {
    display: block;
    color: #e74c3c;
    font-size: 13px;
    margin-top: 5px;
    font-weight: 500;
}

/* AI Form Actions */
.ai-form-actions {
    text-align: center;
    padding: 40px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}

.ai-submit-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(45deg, #005840 0%, #005840 50%, #005840 100%);
    color: white;
    border: none;
    padding: 0;
    border-radius: 16px;
    font-size: 18px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 8px 32px rgba(0, 88, 64, 0.3);
    overflow: hidden;
    min-width: 280px;
    height: 64px;
}

.btn-content {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 32px;
    position: relative;
    z-index: 2;
}

.btn-icon {
    font-size: 24px;
    animation: aiPulse 2s ease-in-out infinite;
}

.btn-text {
    font-weight: 600;
    letter-spacing: 0.5px;
}

.btn-arrow {
    font-size: 20px;
    transition: transform 0.3s ease;
}

.btn-shimmer {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.6s ease;
}

.ai-submit-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 48px rgba(0, 88, 64, 0.4);
}

.ai-submit-btn:hover .btn-arrow {
    transform: translateX(4px);
}

.ai-submit-btn:hover .btn-shimmer {
    left: 100%;
}

.ai-submit-btn:active {
    transform: translateY(-1px);
}

.form-note {
    margin-top: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 14px;
    color: #6c757d;
    font-style: italic;
}

.note-icon {
    font-size: 16px;
    color: #28a745;
}

.wvp-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
}

.wvp-btn-primary:active {
    transform: translateY(0);
}

.btn-icon {
    font-size: 1.2em;
}

/* Responsive Design */
@media (max-width: 768px) {
    .wvp-health-quiz-wrapper {
        padding: 20px 0;
    }

    .wvp-health-quiz-header {
        padding: 30px 20px;
        margin-bottom: 20px;
    }

    .health-quiz-title {
        font-size: 2em;
    }

    .form-section-title {
        font-size: 1.5em;
    }

    .health-icons {
        gap: 10px;
    }

    .health-icon {
        font-size: 2em;
        padding: 10px;
    }

    .wvp-health-form-section {
        padding: 25px 20px;
    }

    .health-form-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .health-quiz-benefits {
        grid-template-columns: 1fr;
        gap: 15px;
    }
}

/* Force full width layout */
body.page #content {
    width: 100% !important;
    max-width: none !important;
}

body.page .main-page-wrapper {
    max-width: none !important;
}

body.page .site-content {
    max-width: none !important;
    width: 100% !important;
}

body.page .sidebar {
    display: none !important;
}

/* Comprehensive sidebar removal for all theme types */
body.page .sidebar,
body.page .widget-area,
body.page #secondary,
body.page .secondary,
body.page .aside,
body.page #aside,
body.page .sidebar-primary,
body.page .sidebar-secondary,
body.page .right-sidebar,
body.page .left-sidebar,
body.page .col-md-3,
body.page .col-lg-3,
body.page .col-xl-3,
body.page .widget_area {
    display: none !important;
    width: 0 !important;
    visibility: hidden !important;
}

/* Enhanced Typography */
.wvp-ai-health-header h1,
.wvp-health-form-section h2 {
    font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
    letter-spacing: -0.02em;
}

.ai-health-title .title-main {
    font-weight: 800;
    font-size: 2.8em;
    background: linear-gradient(135deg, #B8D9BC 0%, rgba(0, 88, 64, 1) 50%, #B8D9BC 100%);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-size: 200% 200%;
    animation: gradient-shift 4s ease-in-out infinite;
}

@keyframes gradient-shift {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

.title-subtitle {
    color: #adb5bd;
    font-weight: 500;
    font-size: 0.6em;
    margin-top: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
}

/* Enhanced AI Badge */
.ai-badge {
    background: linear-gradient(45deg, #B8D9BC, rgba(0, 88, 64, 1));
    color: #fff;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 0.9em;
    font-weight: 700;
    letter-spacing: 1px;
    box-shadow: 0 4px 15px rgba(184, 217, 188, 0.3);
    animation: pulse-glow 2s ease-in-out infinite;
}

@keyframes pulse-glow {
    0%, 100% { box-shadow: 0 4px 15px rgba(184, 217, 188, 0.3); }
    50% { box-shadow: 0 6px 20px rgba(184, 217, 188, 0.5); }
}

/* Overall Progress Indicator */
.overall-progress-indicator {
    margin-bottom: 40px;
    padding: 25px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.progress-steps {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    position: relative;
}

.progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    flex: 1;
    position: relative;
}

.step-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}

.progress-step.active .step-circle {
    background: linear-gradient(45deg, #005840, #667eea);
    border-color: #005840;
    box-shadow: 0 0 20px rgba(0, 88, 64, 0.5);
    transform: scale(1.1);
}

.progress-step.completed .step-circle {
    background: linear-gradient(45deg, #28a745, #20c997);
    border-color: #28a745;
}

.step-number {
    font-weight: 700;
    color: #fff;
    font-size: 1.2em;
    transition: opacity 0.3s;
}

.step-icon {
    position: absolute;
    font-size: 1.3em;
    opacity: 0;
    transition: opacity 0.3s;
}

.progress-step.active .step-number {
    opacity: 0;
}

.progress-step.active .step-icon {
    opacity: 1;
}

.step-label {
    font-size: 0.9em;
    color: rgba(255, 255, 255, 0.8);
    font-weight: 600;
    text-align: center;
    transition: color 0.3s;
}

.progress-step.active .step-label {
    color: #fff;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

.progress-line {
    position: relative;
    height: 4px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 2px;
    overflow: hidden;
}

.progress-line-fill {
    height: 100%;
    background: linear-gradient(90deg, #005840, #667eea);
    transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 2px;
    position: relative;
}

.progress-line-fill::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: progress-shimmer 2s ease-in-out infinite;
}

@keyframes progress-shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

/* Enhanced Survey Progress */
.survey-progress {
    background: rgba(255, 255, 255, 0.1);
    padding: 15px 20px;
    border-radius: 15px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.progress-bar {
    height: 8px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 10px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #005840, #28a745);
    transition: width 0.5s ease;
    border-radius: 4px;
    position: relative;
}

.progress-fill::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    animation: survey-progress-glow 1.5s ease-in-out infinite;
}

@keyframes survey-progress-glow {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.progress-text {
    color: rgba(255, 255, 255, 0.9);
    font-weight: 600;
    font-size: 0.95em;
}

/* Responsive Design */
@media (max-width: 768px) {
    .wvp-health-form-section {
        padding: 25px 20px;
        margin: 0 10px 20px;
    }

    .form-container {
        padding: 20px;
    }

    .ai-health-title .title-main {
        font-size: 2.2em;
    }

    .form-section-title {
        font-size: 1.8em;
    }

    .form-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .ai-submit-btn {
        padding: 16px 30px;
        font-size: 1.1em;
    }

    .health-quiz-benefits {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .benefit-item {
        padding: 12px 16px;
    }
}

@media (max-width: 480px) {
    .ai-health-title .title-main {
        font-size: 1.8em;
    }

    .health-icons {
        gap: 15px;
    }

    .health-icon {
        font-size: 2.5em;
        padding: 12px;
    }
}

/* Enhanced Body Map Styling */
.body-map-container {
    display: flex;
    gap: 30px;
    background: #fff;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}


.body-map-visual {
    flex: 1;
    max-width: 450px;
    background: #fff;
    border-radius: 8px;
    padding: 25px;
    box-shadow:
        0 8px 32px rgba(0,0,0,0.12),
        inset 0 2px 4px rgba(255,255,255,0.8);
    position: relative;
    overflow: visible;
    min-height: 500px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.body-map-visual::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="medical-grid" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M 20 0 L 0 0 0 20" fill="none" stroke="rgba(102,126,234,0.05)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23medical-grid)"/></svg>');
    pointer-events: none;
    opacity: 0.3;
}

.body-svg {
    width: 100%;
    height: auto;
    max-width: 400px;
    max-height: 600px;
    cursor: pointer;
}

.clickable-region {
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    transform-origin: center;
    stroke-dasharray: 0;
}

.clickable-region:hover {
    opacity: 0.7 !important;
    filter: url(#glow) drop-shadow(0 4px 8px rgba(0,0,0,0.2));
    transform: scale(1.08);
    stroke-dasharray: 5;
    animation: region-glow 1.5s ease-in-out infinite;
}

.clickable-region.active {
    opacity: 0.85 !important;
    filter: url(#glow) drop-shadow(0 6px 12px rgba(0,0,0,0.3));
    stroke-width: 4 !important;
    transform: scale(1.1);
    animation: region-active-pulse 2s ease-in-out infinite;
}

/* Specialized region styling */
.head-region:hover, .head-region.active {
    filter: url(#glow) drop-shadow(0 0 15px #ffc107);
}

.heart-region:hover, .heart-region.active {
    filter: url(#glow) drop-shadow(0 0 15px #dc3545);
    animation: heartbeat 1.2s ease-in-out infinite;
}

.lungs-region:hover, .lungs-region.active {
    filter: url(#glow) drop-shadow(0 0 15px #28a745);
    animation: breathing 2s ease-in-out infinite;
}

.liver-region:hover, .liver-region.active {
    filter: url(#glow) drop-shadow(0 0 15px #fd7e14);
}

.digestive-region:hover, .digestive-region.active {
    filter: url(#glow) drop-shadow(0 0 15px #dc3545);
}

.immune-region:hover, .immune-region.active {
    filter: url(#glow) drop-shadow(0 0 15px #28a745);
    animation: immune-boost 1.8s ease-in-out infinite;
}

.joints-region:hover, .joints-region.active {
    filter: url(#glow) drop-shadow(0 0 15px #6f42c1);
    animation: joint-flex 2.2s ease-in-out infinite;
}

.kidneys-region:hover, .kidneys-region.active {
    filter: url(#glow) drop-shadow(0 0 15px #6f42c1);
}

/* Enhanced Medical Animations */
@keyframes region-glow {
    0%, 100% {
        opacity: 0.7;
        stroke-dasharray: 5;
    }
    50% {
        opacity: 0.9;
        stroke-dasharray: 10;
    }
}

@keyframes region-active-pulse {
    0%, 100% {
        opacity: 0.85;
        transform: scale(1.1);
        stroke-width: 4;
    }
    50% {
        opacity: 1;
        transform: scale(1.15);
        stroke-width: 5;
    }
}

@keyframes heartbeat {
    0%, 100% {
        transform: scale(1.1);
        opacity: 0.85;
    }
    25% {
        transform: scale(1.2);
        opacity: 1;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.85;
    }
    75% {
        transform: scale(1.18);
        opacity: 0.95;
    }
}

@keyframes breathing {
    0%, 100% {
        transform: scale(1.08) scaleY(1);
        opacity: 0.7;
    }
    50% {
        transform: scale(1.12) scaleY(1.1);
        opacity: 0.9;
    }
}

@keyframes immune-boost {
    0%, 100% {
        transform: scale(1.08);
        opacity: 0.7;
    }
    33% {
        transform: scale(1.15);
        opacity: 0.9;
    }
    66% {
        transform: scale(1.1);
        opacity: 0.8;
    }
}

@keyframes joint-flex {
    0%, 100% {
        transform: scale(1.08) rotate(0deg);
        opacity: 0.7;
    }
    25% {
        transform: scale(1.12) rotate(2deg);
        opacity: 0.85;
    }
    50% {
        transform: scale(1.15) rotate(0deg);
        opacity: 0.9;
    }
    75% {
        transform: scale(1.12) rotate(-2deg);
        opacity: 0.85;
    }
}

.region-label-outside {
    font-size: 14px;
    font-weight: 700;
    fill: #2c3e50;
    pointer-events: none;
    text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
    font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
    letter-spacing: 0.5px;
}

/* Medical Grid Background */
.body-svg::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image:
        linear-gradient(rgba(102,126,234,0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(102,126,234,0.05) 1px, transparent 1px);
    background-size: 20px 20px;
    pointer-events: none;
}

/* Professional Medical Styling */
.body-map-visual {
    position: relative;
}

.body-map-visual::after {
    content: '';
    position: absolute;
    top: 10px;
    right: 10px;
    width: 8px;
    height: 8px;
    background: #28a745;
    border-radius: 50%;
    box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.3);
    animation: medical-status 2s ease-in-out infinite;
}

@keyframes medical-status {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.7;
        transform: scale(1.2);
    }
}

.energy-field {
    animation: energy-flow 3s ease-in-out infinite;
}

@keyframes energy-flow {
    0%, 100% { stroke-dashoffset: 0; }
    50% { stroke-dashoffset: 20; }
}

/* AI Analysis Side Panel */
.ai-analysis-panel {
    flex: 1;
    max-width: 350px;
    background: linear-gradient(135deg, #B8D9BC 0%, rgba(0, 88, 64, 1) 100%);
    border-radius: 8px;
    padding: 0;
    box-shadow: 0 15px 35px rgba(184, 217, 188, 0.3);
    overflow: hidden;
    position: relative;
    min-height: 500px;
}

.ai-panel-header {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    padding: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ai-indicator {
    display: flex;
    align-items: center;
    gap: 10px;
}

.ai-pulse {
    width: 12px;
    height: 12px;
    background: #005840;
    border-radius: 50%;
    animation: ai-pulse-animation 1.5s ease-in-out infinite;
}

@keyframes ai-pulse-animation {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
        box-shadow: 0 0 5px rgba(0, 88, 64, 0.5);
    }
    50% {
        opacity: 0.7;
        transform: scale(1.2);
        box-shadow: 0 0 15px rgba(0, 88, 64, 0.8);
    }
}

.ai-label {
    color: #fff;
    font-weight: 600;
    font-size: 1.1em;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}

.close-panel {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: #fff;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.2em;
    font-weight: bold;
    transition: all 0.3s ease;
}

.close-panel:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.ai-panel-content {
    padding: 25px;
    color: #fff;
}

.region-title {
    font-size: 1.5em;
    margin-bottom: 20px;
    color: #fff;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

.analysis-section {
    margin-bottom: 20px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 15px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    opacity: 0;
    transform: translateY(20px);
    animation: section-fade-in 0.5s ease forwards;
}

.analysis-section:nth-child(2) { animation-delay: 0.1s; }
.analysis-section:nth-child(3) { animation-delay: 0.2s; }
.analysis-section:nth-child(4) { animation-delay: 0.3s; }

@keyframes section-fade-in {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.section-icon {
    font-size: 1.3em;
}

.section-header h4 {
    margin: 0;
    color: #fff;
    font-size: 1.1em;
}

.analysis-text {
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.5;
    margin: 0;
}

.ai-recommendation {
    margin-top: 25px;
    text-align: center;
}

.recommendation-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-bottom: 15px;
}

.rec-icon {
    font-size: 1.4em;
}

.recommendation-header h4 {
    margin: 0;
    color: #fff;
}

.recommendation-btn {
    background: linear-gradient(45deg, #005840 0%, #005840 100%);
    border: none;
    border-radius: 25px;
    padding: 12px 25px;
    color: #fff;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
    justify-content: center;
    width: 100%;
    box-shadow: 0 5px 15px rgba(0, 88, 64, 0.3);
}

.recommendation-btn:hover {
    background: linear-gradient(45deg, #2d5a3d 0%, #2d5a3d 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 88, 64, 0.4);
}

.btn-arrow {
    transition: transform 0.3s ease;
}

.recommendation-btn:hover .btn-arrow {
    transform: translateX(3px);
}

/* Results Section Styling */
.body-map-header {
    text-align: center;
    margin-bottom: 30px;
}

.results-title {
    font-size: 2.2em;
    color: #1d2327;
    margin-bottom: 10px;
    font-weight: 700;
}

.results-subtitle {
    color: #6c757d;
    font-size: 1.1em;
    margin-bottom: 0;
}

/* Responsive Design for Body Map */
@media (max-width: 1024px) {
    .body-map-container {
        flex-direction: column;
        gap: 20px;
    }

    .ai-analysis-panel {
        max-width: none;
        min-height: auto;
    }
}

@media (max-width: 768px) {
    .body-map-container {
        padding: 20px;
        margin: 0 10px 30px;
    }

    .body-map-visual {
        padding: 15px;
    }

    .ai-panel-content {
        padding: 20px;
    }

    .results-title {
        font-size: 1.8em;
    }
}

/* Professional SVG Organ Analysis Panel */
.ai-analysis-panel {
    margin-top: 30px;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 0;
    box-shadow: 0 15px 35px rgba(102, 126, 234, 0.3);
    overflow: hidden;
    position: relative;
    display: none;
    opacity: 0;
    animation: panel-slide-in 0.5s ease forwards;
}

@keyframes panel-slide-in {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.ai-analysis-panel.show {
    display: block;
}

.analysis-title {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    padding: 20px;
    margin: 0;
    color: #fff;
    font-size: 1.4em;
    font-weight: 600;
    text-align: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}

.analysis-content {
    padding: 25px;
}

.organ-title {
    color: #fff;
    font-size: 1.3em;
    margin: 0 0 20px 0;
    text-align: center;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
    font-weight: 600;
}

.health-indicators {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.indicator-section {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 18px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    opacity: 0;
    transform: translateY(20px);
    animation: indicator-fade-in 0.5s ease forwards;
}

.indicator-section:nth-child(1) { animation-delay: 0.1s; }
.indicator-section:nth-child(2) { animation-delay: 0.2s; }
.indicator-section:nth-child(3) { animation-delay: 0.3s; }

@keyframes indicator-fade-in {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.indicator-section h5 {
    margin: 0 0 10px 0;
    color: #fff;
    font-size: 1.1em;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.indicator-section p {
    margin: 0;
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.6;
    font-size: 0.95em;
}

.symptoms-text,
.causes-text,
.solutions-text {
    background: rgba(255, 255, 255, 0.05);
    padding: 12px;
    border-radius: 8px;
    border-left: 3px solid #005840;
    font-style: italic;
}

/* Responsive adjustments for analysis panel */
@media (max-width: 768px) {
    .ai-analysis-panel {
        margin-top: 20px;
        border-radius: 15px;
    }

    .analysis-title {
        font-size: 1.2em;
        padding: 15px;
    }

    .analysis-content {
        padding: 20px;
    }

    .indicator-section {
        padding: 15px;
    }

    .organ-title {
        font-size: 1.1em;
    }
}

    </style>
    <?php
    return ob_get_clean();
}

/**
 * Health Quiz Completed Page with AI Analysis
 */
function wvp_health_quiz_completed_page() {
    ob_start();

    // Check if OpenAI is enabled
    $openai = new WVP_Health_Quiz_OpenAI();
    $openai_enabled = $openai->is_enabled();

    ?>
    <div class="wvp-single-package-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-10 col-xl-8 mx-auto">

                    <!-- Breadcrumbs -->
                    <nav class="wvp-breadcrumbs" aria-label="Breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="<?php echo home_url(); ?>">Početna</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="<?php echo home_url('/analiza-zdravstvenog-stanja'); ?>">Analiza zdravlja</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Rezultati</li>
                        </ol>
                    </nav>

                    <!-- Completed Quiz Header -->
                    <article class="wvp-health-completed-header" itemscope itemtype="https://schema.org/MedicalEntity">
                        <h1 class="completed-title" itemprop="name">🎉 Vaša AI Analiza Je Gotova</h1>
                        <div class="completed-featured-image">
                            <div class="completion-icons">
                                <span class="completion-icon">✅</span>
                                <span class="completion-icon">🧬</span>
                                <span class="completion-icon">📊</span>
                                <span class="completion-icon">💊</span>
                            </div>
                        </div>
                        <div class="completed-description" itemprop="description">
                            <p><strong>Čestitamo! Uspešno ste završili AI analizu zdravstvenog stanja</strong></p>
                            <p>Naša napredna AI tehnologija je analizirala vaše odgovore i pripremila personalizovane preporuke za poboljšanje vašeg zdravlja.</p>
                        </div>
                    </article>

                    <?php if ($openai_enabled): ?>
                    <div class="wvp-ai-analysis-section">
                        <h2 class="ai-section-title">🤖 AI Analiza Zdravstvenog Stanja</h2>

                        <div id="wvp-ai-status" class="wvp-ai-status">
                            <div class="wvp-loading">
                                <div class="wvp-spinner"></div>
                                <p>AI analizira vaše odgovore i priprema personalizovane preporuke...</p>
                            </div>
                        </div>

                        <div id="wvp-ai-results" class="wvp-ai-results" style="display:none;">

                            <!-- AI Score and Body Diagram Section -->
                            <div class="wvp-ai-overview-section">
                                <div class="ai-score-container">
                                    <h3 class="section-title">📊 Vaš Health Score</h3>
                                    <div class="wvp-ai-score-display">
                                        <div id="wvp-ai-score-circle" class="wvp-score-circle">
                                            <span id="wvp-ai-score-number">-</span>
                                        </div>
                                        <p id="wvp-ai-score-description">Analiziramo vaše odgovore...</p>
                                    </div>
                                </div>

                                <div class="body-diagram-container">
                                    <h3 class="section-title">🫀 Mapa Vašeg Tela</h3>
                                    <div class="body-diagram">
                                        <svg viewBox="0 0 200 400" class="human-body-svg">
                                            <!-- Head -->
                                            <circle cx="100" cy="40" r="25" class="body-part" id="head" data-area="head"/>
                                            <text x="100" y="45" text-anchor="middle" class="body-label">Glava</text>

                                            <!-- Neck -->
                                            <rect x="90" y="65" width="20" height="15" class="body-part" id="neck" data-area="neck"/>

                                            <!-- Chest -->
                                            <ellipse cx="100" cy="120" rx="45" ry="35" class="body-part" id="chest" data-area="chest"/>
                                            <text x="100" y="125" text-anchor="middle" class="body-label">Grudi</text>

                                            <!-- Stomach -->
                                            <ellipse cx="100" cy="180" rx="35" ry="25" class="body-part" id="stomach" data-area="stomach"/>
                                            <text x="100" y="185" text-anchor="middle" class="body-label">Stomak</text>

                                            <!-- Arms -->
                                            <ellipse cx="60" cy="110" rx="15" ry="40" class="body-part" id="left-arm" data-area="arms"/>
                                            <ellipse cx="140" cy="110" rx="15" ry="40" class="body-part" id="right-arm" data-area="arms"/>
                                            <text x="50" y="115" text-anchor="middle" class="body-label">Ruke</text>

                                            <!-- Legs -->
                                            <ellipse cx="85" cy="280" rx="15" ry="60" class="body-part" id="left-leg" data-area="legs"/>
                                            <ellipse cx="115" cy="280" rx="15" ry="60" class="body-part" id="right-leg" data-area="legs"/>
                                            <text x="100" y="285" text-anchor="middle" class="body-label">Noge</text>

                                            <!-- Joints -->
                                            <circle cx="85" cy="240" r="8" class="body-part joint" id="left-knee" data-area="joints"/>
                                            <circle cx="115" cy="240" r="8" class="body-part joint" id="right-knee" data-area="joints"/>
                                            <text x="100" y="235" text-anchor="middle" class="body-label small">Zglobovi</text>
                                        </svg>

                                        <div class="body-legend">
                                            <div class="legend-item">
                                                <span class="legend-color good"></span>
                                                <span>Dobro stanje</span>
                                            </div>
                                            <div class="legend-item">
                                                <span class="legend-color warning"></span>
                                                <span>Potrebna pažnja</span>
                                            </div>
                                            <div class="legend-item">
                                                <span class="legend-color danger"></span>
                                                <span>Problematično</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- AI Analysis Content -->
                            <div class="wvp-ai-analysis-content">
                                <div class="analysis-grid">
                                    <div class="wvp-ai-health-status analysis-card">
                                        <h3 class="card-title">🩺 Stanje Organizma</h3>
                                        <div id="wvp-ai-health-text" class="wvp-ai-text-content"></div>
                                    </div>

                                    <div class="wvp-ai-recommendations analysis-card">
                                        <h3 class="card-title">💡 AI Preporuke</h3>
                                        <div id="wvp-ai-recommendations-text" class="wvp-ai-text-content"></div>
                                    </div>
                                </div>

                                <!-- Package Selection Section -->
                                <div class="wvp-package-selection-section">
                                    <h3 class="section-title">📦 Izaberite Vaš Terapijski Paket</h3>
                                    <p class="section-description">AI je analizirao vaše stanje i preporučuje sledeće pakete. Izaberite onaj koji najbolje odgovara vašim potrebama:</p>

                                    <div class="package-options-grid">
                                        <div class="package-option" data-package-type="light">
                                            <div class="package-icon">🌱</div>
                                            <h4>Blagi Paket</h4>
                                            <p>Za preventivno delovanje i održavanje zdravlja</p>
                                            <ul class="package-features">
                                                <li>2-3 proizvoda</li>
                                                <li>Osnovni popust</li>
                                                <li>30-dnevna terapija</li>
                                            </ul>
                                        </div>

                                        <div class="package-option recommended" data-package-type="optimal">
                                            <div class="package-badge">🏆 AI Preporučuje</div>
                                            <div class="package-icon">⚡</div>
                                            <h4>Optimalni Paket</h4>
                                            <p>Za efikasno rešavanje zdravstvenih problema</p>
                                            <ul class="package-features">
                                                <li>4-5 proizvoda</li>
                                                <li>Pojačani popust</li>
                                                <li>60-dnevna terapija</li>
                                            </ul>
                                        </div>

                                        <div class="package-option" data-package-type="intensive">
                                            <div class="package-icon">🚀</div>
                                            <h4>Intenzivni Paket</h4>
                                            <p>Za ozbiljne probleme i brze rezultate</p>
                                            <ul class="package-features">
                                                <li>6+ proizvoda</li>
                                                <li>Maksimalni popust</li>
                                                <li>90-dnevna terapija</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- AI Generated Packages -->
                                <div class="wvp-ai-packages">
                                    <h3 class="section-title">🎯 Vaši Personalizovani Paketi</h3>
                                    <div id="wvp-ai-packages-list" class="wvp-packages-grid"></div>
                                </div>

                                <!-- Individual Products -->
                                <div class="wvp-ai-products">
                                    <h3 class="section-title">🛒 Pojedinačni Proizvodi</h3>
                                    <p class="section-description">Ili kombinujte proizvode prema vašim potrebama:</p>
                                    <div id="wvp-ai-products-list" class="wvp-products-grid"></div>
                                </div>

                                <!-- Usage Instructions -->
                                <div class="usage-instructions" id="usage-instructions" style="display:none;">
                                    <h3 class="section-title">📋 Način Upotrebe</h3>
                                    <div class="instructions-content">
                                        <div class="instruction-timeline">
                                            <div class="timeline-item">
                                                <div class="timeline-icon">🌅</div>
                                                <div class="timeline-content">
                                                    <h4>Jutro (08:00)</h4>
                                                    <div id="morning-instructions"></div>
                                                </div>
                                            </div>
                                            <div class="timeline-item">
                                                <div class="timeline-icon">☀️</div>
                                                <div class="timeline-content">
                                                    <h4>Podne (13:00)</h4>
                                                    <div id="afternoon-instructions"></div>
                                                </div>
                                            </div>
                                            <div class="timeline-item">
                                                <div class="timeline-icon">🌙</div>
                                                <div class="timeline-content">
                                                    <h4>Veče (20:00)</h4>
                                                    <div id="evening-instructions"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="important-notes">
                                            <h4>⚠️ Važne Napomene</h4>
                                            <ul id="important-notes-list">
                                                <li>Konzumirati sa dovoljno vode</li>
                                                <li>Uzimati redovno, u isto vreme</li>
                                                <li>Konsultovati se sa lekarom pri bilo kakvim neobičnim reakcijama</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="wvp-ai-error" class="wvp-ai-error" style="display:none;">
                            <h3>⚠️ Greška pri AI analizi</h3>
                            <p>Došlo je do greške prilikom analize. Molimo pokušajte ponovo kasnije.</p>
                            <button id="wvp-retry-ai" class="wvp-retry-button">Pokušaj ponovo</button>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="wvp-ai-disabled-notice">
                        <h2>ℹ️ AI Analiza nije dostupna</h2>
                        <p>AI analiza trenutno nije konfigurirana. Kontaktirajte administratora za više informacija.</p>
                    </div>
                    <?php endif; ?>

                    <div class="wvp-test-connection-section">
                        <h3>🔧 Debug & Test Opcije</h3>
                        <button id="wvp-test-openai" class="wvp-test-button">Testiraj OpenAI konekciju</button>
                        <button id="wvp-trigger-ai" class="wvp-test-button" style="margin-left: 10px;">Pokreni AI Analizu</button>
                        <div id="wvp-test-results" class="wvp-test-results"></div>
                    </div>

                    <div class="wvp-completed-actions">
                        <a href="<?php echo home_url(); ?>" class="wvp-btn wvp-btn-secondary">← Nazad na početnu</a>
                        <a href="<?php echo home_url('/analiza-zdravstvenog-stanja'); ?>" class="wvp-btn wvp-btn-primary">Ponovi quiz</a>
                    </div>

                </div> <!-- col-lg-10 col-xl-8 mx-auto -->
            </div> <!-- row -->
        </div> <!-- container-fluid -->
    </div> <!-- wvp-single-package-wrapper -->

    <style>
/* Use same wrapper styling for completed page */

/* Breadcrumbs */
.wvp-breadcrumbs {
    margin-bottom: 20px;
}

.breadcrumb {
    background: transparent;
    padding: 0;
    margin-bottom: 0;
    font-size: 14px;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    color: #6c757d;
}

.breadcrumb-item a {
    color: #007cba;
    text-decoration: none;
}

.breadcrumb-item a:hover {
    color: #0056b3;
    text-decoration: underline;
}

.breadcrumb-item.active {
    color: #6c757d;
}

/* Completed Header */
.wvp-health-completed-header {
    background: white;
    border-radius: 15px;
    padding: 40px;
    margin-bottom: 30px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    text-align: center;
    border: 1px solid #e9ecef;
}

.completed-title {
    font-size: 2.5em;
    font-weight: 700;
    margin-bottom: 25px;
    color: #2c3e50;
}

.completion-icons {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin: 20px 0;
}

.completion-icon {
    font-size: 2.5em;
    padding: 10px;
    background: linear-gradient(135deg, #007cba 0%, #0056b3 100%);
    border-radius: 50%;
    color: white;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: bounce 2s ease-in-out infinite;
}

.completion-icon:nth-child(2) { animation-delay: 0.2s; }
.completion-icon:nth-child(3) { animation-delay: 0.4s; }
.completion-icon:nth-child(4) { animation-delay: 0.6s; }

.completed-description {
    font-size: 1.1em;
    line-height: 1.6;
    margin-bottom: 20px;
    color: #495057;
}

/* AI Analysis Section */
.wvp-ai-analysis-section {
    background: white;
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
}

.ai-section-title {
    font-size: 2em;
    color: #2c3e50;
    text-align: center;
    margin-bottom: 25px;
    font-weight: 600;
}

/* AI Overview Section */
.wvp-ai-overview-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin-bottom: 30px;
}

.ai-score-container, .body-diagram-container {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 12px;
    border: 1px solid #e9ecef;
}

.section-title {
    font-size: 1.3em;
    color: #2c3e50;
    margin-bottom: 15px;
    text-align: center;
    font-weight: 600;
}

/* Body Diagram */
.body-diagram {
    text-align: center;
}

.human-body-svg {
    max-width: 200px;
    height: auto;
    margin: 0 auto 20px;
}

.body-part {
    fill: #e9ecef;
    stroke: #adb5bd;
    stroke-width: 2;
    cursor: pointer;
    transition: all 0.3s ease;
}

.body-part:hover {
    fill: #007cba;
    stroke: #0056b3;
}

.body-part.good { fill: #d4edda; stroke: #28a745; }
.body-part.warning { fill: #fff3cd; stroke: #ffc107; }
.body-part.danger { fill: #f8d7da; stroke: #dc3545; }

/* Weak points highlighting */
.body-part.weak-point {
    animation: pulse-weak 2s ease-in-out infinite;
    filter: drop-shadow(0 0 5px rgba(220, 53, 69, 0.8));
}

.body-part.weak-point[data-severity="high"] {
    fill: #f8d7da;
    stroke: #dc3545;
    stroke-width: 3;
    animation: pulse-severe 1.5s ease-in-out infinite;
}

.body-part.weak-point[data-severity="moderate"] {
    fill: #fff3cd;
    stroke: #ffc107;
    stroke-width: 2.5;
    animation: pulse-moderate 2s ease-in-out infinite;
}

.body-part.weak-point[data-severity="low"] {
    fill: #d1ecf1;
    stroke: #17a2b8;
    stroke-width: 2;
    animation: pulse-low 2.5s ease-in-out infinite;
}

@keyframes pulse-weak {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

@keyframes pulse-severe {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.8; transform: scale(1.05); }
}

@keyframes pulse-moderate {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.75; }
}

@keyframes pulse-low {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.85; }

.body-label {
    font-size: 12px;
    fill: #495057;
    pointer-events: none;
}

.body-label.small {
    font-size: 10px;
}

.body-legend {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 15px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.legend-color.good { background: #28a745; }
.legend-color.warning { background: #ffc107; }
.legend-color.danger { background: #dc3545; }

/* Analysis Grid */
.analysis-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.analysis-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    border: 1px solid #e9ecef;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

.card-title {
    color: #007cba;
    margin-bottom: 15px;
    font-size: 1.2em;
    font-weight: 600;
}

/* Package Selection */
.wvp-package-selection-section {
    margin-bottom: 30px;
    background: white;
    border-radius: 15px;
    padding: 30px;
    border: 1px solid #e9ecef;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.section-description {
    text-align: center;
    color: #6c757d;
    margin-bottom: 25px;
    font-size: 1em;
}

.package-options-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.package-option {
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 20px 15px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.package-option:hover {
    border-color: #007cba;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,123,186,0.2);
    background: white;
}

.package-option.recommended {
    border-color: #28a745;
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
}

.package-option.selected {
    border-color: #007cba;
    background: linear-gradient(135deg, #cce5ff 0%, #b3daff 100%);
}

.package-badge {
    position: absolute;
    top: -10px;
    left: 50%;
    transform: translateX(-50%);
    background: #28a745;
    color: white;
    padding: 5px 15px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
}

.package-icon {
    font-size: 3em;
    margin-bottom: 15px;
}

.package-option h4 {
    font-size: 1.4em;
    color: #2c3e50;
    margin-bottom: 10px;
    font-weight: 600;
}

.package-option p {
    color: #6c757d;
    margin-bottom: 15px;
    font-size: 14px;
}

.package-features {
    list-style: none;
    padding: 0;
    margin: 0;
}

.package-features li {
    padding: 5px 0;
    font-size: 13px;
    color: #495057;
    position: relative;
    padding-left: 20px;
}

.package-features li:before {
    content: '✓';
    position: absolute;
    left: 0;
    color: #28a745;
    font-weight: bold;
}

/* Usage Instructions */
.usage-instructions {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 15px;
    padding: 30px;
    margin-top: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.instruction-timeline {
    margin-bottom: 30px;
}

.timeline-item {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 25px;
}

.timeline-icon {
    font-size: 2em;
    flex-shrink: 0;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.8);
    border-radius: 50%;
    border: 2px solid #ffc107;
}

.timeline-content h4 {
    margin-bottom: 10px;
    color: #856404;
    font-size: 1.2em;
}

.important-notes {
    background: rgba(255,255,255,0.8);
    padding: 20px;
    border-radius: 10px;
    border-left: 5px solid #ffc107;
}

.important-notes h4 {
    color: #856404;
    margin-bottom: 15px;
}

.important-notes ul {
    margin: 0;
    padding-left: 20px;
}

.important-notes li {
    margin-bottom: 8px;
    color: #856404;
}

/* Responsive Design */
@media (max-width: 992px) {
    .wvp-ai-overview-section {
        grid-template-columns: 1fr;
        gap: 30px;
    }

    .analysis-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .package-options-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .timeline-item {
        flex-direction: column;
        text-align: center;
    }
}

@media (max-width: 768px) {

    .wvp-health-completed-header {
        padding: 25px 20px;
    }

    .completed-title {
        font-size: 2em;
    }

    .wvp-ai-analysis-section,
    .wvp-package-selection-section {
        padding: 20px 15px;
        margin-bottom: 20px;
    }

    .completion-icons {
        gap: 10px;
    }

    .completion-icon {
        font-size: 2em;
        width: 50px;
        height: 50px;
    }

    .wvp-ai-overview-section {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .package-options-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .package-option {
        padding: 20px;
    }

    .breadcrumb {
        font-size: 12px;
    }
}
        border: 1px solid #e9ecef;
    }

    .wvp-ai-status .wvp-loading {
        text-align: center;
        padding: 40px;
    }

    .wvp-spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #007cba;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .wvp-ai-score-display {
        text-align: center;
        margin: 20px 0;
    }

    .wvp-score-circle {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        font-weight: bold;
        color: white;
        margin-bottom: 15px;
        background: #6c757d;
    }

    .wvp-score-circle.excellent { background: #28a745; }
    .wvp-score-circle.good { background: #17a2b8; }
    .wvp-score-circle.warning { background: #ffc107; color: #212529; }
    .wvp-score-circle.danger { background: #dc3545; }

    .wvp-ai-text-content {
        background: white;
        padding: 20px;
        border-radius: 8px;
        border-left: 4px solid #007cba;
        margin: 10px 0;
    }

    .wvp-products-grid, .wvp-packages-grid, .wvp-ai-products-grid, .wvp-ai-packages-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }
    .wvp-ai-product-card, .wvp-ai-package-card {
        background: white;
        border: 2px solid #007cba;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .wvp-ai-package-card h4 {
        color: #007cba;
        margin-top: 0;
        margin-bottom: 15px;
    }
    .wvp-package-btn {
        display: inline-block;
        background: #28a745;
        color: white;
        padding: 12px 24px;
        text-decoration: none;
        border-radius: 6px;
        font-weight: bold;
        margin-top: 15px;
        transition: background 0.3s;
    }
    .wvp-package-btn:hover {
        background: #218838;
        color: white;
        text-decoration: none;
    }

    /* Product and package configuration styles */
    .wvp-product-image img {
        max-width: 100%;
        height: 150px;
        object-fit: cover;
        border-radius: 6px;
        margin-bottom: 15px;
    }

    .wvp-product-description {
        font-size: 14px;
        color: #666;
        margin: 10px 0;
    }

    .wvp-product-price {
        margin: 15px 0;
        font-weight: bold;
    }

    .wvp-regular-price {
        text-decoration: line-through;
        color: #999;
        margin-right: 10px;
    }

    .wvp-sale-price {
        color: #e74c3c;
        font-size: 18px;
    }

    .wvp-current-price {
        color: #2c3e50;
        font-size: 18px;
    }

    .wvp-add-to-cart-btn, .wvp-add-package-to-cart-btn {
        background: #007cba;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
        width: 100%;
        margin-top: 15px;
        transition: background 0.3s;
    }

    .wvp-add-to-cart-btn:hover, .wvp-add-package-to-cart-btn:hover:not(:disabled) {
        background: #005a87;
    }

    .wvp-add-package-to-cart-btn:disabled {
        background: #6c757d;
        cursor: not-allowed;
        opacity: 0.7;
    }

    .wvp-added-to-cart {
        background: #28a745 !important;
        cursor: default;
    }

    /* AI Auto-Selected Products Styles */
    .wvp-ai-selected-products {
        background: linear-gradient(135deg, #e8f5e8 0%, #f0f9ff 100%);
        border: 2px solid #28a745;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .wvp-selected-products-info {
        margin-bottom: 15px;
    }

    .wvp-ai-selection-note {
        margin: 0;
        color: #155724;
        font-weight: 600;
        font-size: 14px;
        text-align: center;
        background: rgba(40, 167, 69, 0.1);
        padding: 10px;
        border-radius: 6px;
        border-left: 4px solid #28a745;
    }

    .wvp-selected-products-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .wvp-selected-product-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background: rgba(255, 255, 255, 0.8);
        border: 1px solid rgba(40, 167, 69, 0.3);
        border-radius: 6px;
        transition: all 0.2s ease;
    }

    .wvp-selected-product-item:hover {
        background: #fff;
        box-shadow: 0 2px 8px rgba(40, 167, 69, 0.15);
    }

    .wvp-product-image-small {
        flex-shrink: 0;
    }

    .wvp-product-info {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .wvp-product-name {
        font-weight: 600;
        color: #1d2327;
        font-size: 14px;
        line-height: 1.3;
    }

    .wvp-product-price-display {
        color: #28a745;
        font-size: 13px;
        font-weight: 600;
    }

    .wvp-selected-indicator {
        flex-shrink: 0;
        font-size: 18px;
        color: #28a745;
    }

    /* Package configuration styles */
    .wvp-package-config {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 2px solid #e9ecef;
    }

    .wvp-package-config h5 {
        margin: 0 0 15px 0;
        color: #2c3e50;
    }

    .wvp-package-products {
        margin: 15px 0;
    }

    .wvp-package-product-option {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-bottom: 8px;
        cursor: pointer;
        transition: background 0.2s;
    }

    .wvp-package-product-option:hover {
        background: #f8f9fa;
    }

    .wvp-package-product-option input[type="checkbox"] {
        margin-right: 10px;
    }

    .wvp-product-name {
        flex: 1;
        font-weight: 500;
    }

    .wvp-product-price {
        font-weight: bold;
        color: #007cba;
    }

    .wvp-package-pricing {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 6px;
        margin: 15px 0;
    }

    .wvp-price-breakdown p {
        display: flex;
        justify-content: space-between;
        margin: 5px 0;
    }

    .wvp-regular-total {
        color: #666;
    }

    .wvp-discount-amount {
        color: #e74c3c;
    }

    .wvp-final-total {
        font-weight: bold;
        font-size: 18px;
        color: #2c3e50;
        border-top: 1px solid #ddd;
        padding-top: 8px;
    }

    .wvp-package-reason {
        font-style: italic;
        color: #666;
        background: #fff3cd;
        padding: 10px;
        border-radius: 4px;
        border-left: 4px solid #ffc107;
    }

    /* Cart message styles */
    .wvp-cart-message {
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-weight: 500;
    }

    .wvp-cart-message-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .wvp-cart-message-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .wvp-cart-message-warning {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }

    .wvp-ai-disabled-notice {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        margin-bottom: 30px;
    }

    .wvp-test-connection-section {
        background: #e9ecef;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
    }

    .wvp-test-button, .wvp-retry-button {
        background: #007cba;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
    }

    .wvp-test-button:hover, .wvp-retry-button:hover {
        background: #005a87;
    }

    .wvp-test-results {
        margin-top: 15px;
        padding: 10px;
        border-radius: 5px;
        display: none;
    }

    .wvp-test-results.success {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }

    .wvp-test-results.error {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }

    .wvp-completed-actions {
        text-align: center;
        margin-top: 30px;
    }

    .wvp-btn {
        display: inline-block;
        padding: 12px 24px;
        margin: 0 10px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: bold;
        transition: all 0.3s ease;
    }

    .wvp-btn-primary {
        background: #007cba;
        color: white;
    }

    .wvp-btn-secondary {
        background: #6c757d;
        color: white;
    }

    .wvp-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    .wvp-ai-error {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
    }

    .wvp-debug-error {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
        text-align: left;
    }

    .wvp-debug-error h3 {
        margin-top: 0;
        color: #856404;
    }
    </style>

    <script>
    // Health quiz JavaScript configuration
    window.wvpHealthQuizConfig = {};

    jQuery(document).ready(function($) {
        // Comprehensive sidebar removal
        $('.sidebar, .widget-area, #secondary, .secondary, .aside, #aside, .sidebar-primary, .sidebar-secondary, .right-sidebar, .left-sidebar, .col-md-3, .col-lg-3, .col-xl-3, .widget_area').hide();


        // Debug: Show what we have
        // Try to get Result ID from multiple sources
        let resultId = localStorage.getItem('wvp_health_quiz_result_id');

        // Also try to get from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const urlResultId = urlParams.get('result_id');

        if (!resultId && urlResultId) {
            resultId = urlResultId;
            // Save to localStorage for future use
            localStorage.setItem('wvp_health_quiz_result_id', resultId);
        }
        const openaiEnabled = <?php echo $openai_enabled ? 'true' : 'false'; ?>;

        console.log('Debug - Result ID from localStorage:', localStorage.getItem('wvp_health_quiz_result_id'));
        console.log('Debug - Result ID from URL:', urlResultId);
        console.log('Debug - Final Result ID:', resultId);
        console.log('Debug - OpenAI enabled:', openaiEnabled);

        // Add debug section to page
        if (!resultId) {
            $('#wvp-ai-status').html('<div class="wvp-debug-error"><h3>🐛 Debug Info</h3><p><strong>Problem:</strong> Nema result ID iz kviza. Možda kviz nije uspešno završen.</p><p><strong>Result ID iz localStorage:</strong> ' + (localStorage.getItem('wvp_health_quiz_result_id') || 'null') + '</p><p><strong>Result ID iz URL:</strong> ' + (urlResultId || 'null') + '</p><p><strong>Final Result ID:</strong> ' + (resultId || 'null') + '</p><p><strong>OpenAI enabled:</strong> ' + openaiEnabled + '</p></div>');
        } else if (!openaiEnabled) {
            $('#wvp-ai-status').html('<div class="wvp-debug-error"><h3>🐛 Debug Info</h3><p><strong>Problem:</strong> OpenAI nije omogućen u admin panelu.</p><p><strong>Result ID:</strong> ' + resultId + '</p><p><strong>OpenAI enabled:</strong> ' + openaiEnabled + '</p></div>');
        } else {
            // Start AI analysis immediately
            startAIAnalysis(resultId);
        }

        // Test OpenAI connection
        $('#wvp-test-openai').on('click', function() {
            testOpenAIConnection();
        });

        // Trigger AI analysis manually
        $('#wvp-trigger-ai').on('click', function() {
            if (resultId) {
                triggerAIAnalysis(resultId);
            } else {
                alert('Nema result ID za analizu!');
            }
        });

        // Retry AI analysis
        $('#wvp-retry-ai').on('click', function() {
            if (resultId) {
                $('#wvp-ai-error').hide();
                $('#wvp-ai-status').show();
                startAIAnalysis(resultId);
            }
        });

        function startAIAnalysis(resultId) {
            console.log('Starting AI analysis for result ID:', resultId);

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'wvp_get_ai_analysis',
                    result_id: resultId,
                    nonce: '<?php echo wp_create_nonce('wvp_ai_analysis_nonce'); ?>'
                },
                success: function(response) {
                    console.log('AI Analysis response:', response);

                    if (response.success) {
                        if (response.data.ai_analysis && !response.data.processing) {
                            console.log('AI analysis completed, displaying results');
                            displayAIResults(response.data);
                        } else {
                            console.log('AI analysis still processing, checking again in 5 seconds');
                            $('#wvp-ai-status .wvp-loading p').text('AI analizira vaše odgovore... Sačekajte još malo.');
                            setTimeout(function() {
                                startAIAnalysis(resultId);
                            }, 5000);
                        }
                    } else {
                        console.error('AI Analysis error:', response.data);
                        showAIError('Greška: ' + (response.data?.message || 'Nepoznata greška'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error);
                    showAIError('Greška pri komunikaciji sa serverom: ' + error);
                }
            });
        }

        function displayAIResults(data) {
            $('#wvp-ai-status').hide();

            if (data.ai_score) {
                updateInteractiveScoreDisplay(data.ai_score);
            }

            if (data.ai_analysis) {
                displayEnhancedAIAnalysis(data.ai_analysis);
            }

            // Display AI recommended products
            if (data.ai_recommended_products && data.ai_recommended_products.length > 0) {
                let productsHtml = '<div class="wvp-ai-products-grid">';
                $('#wvp-ai-products-list').html(productsHtml + '</div>');

                data.ai_recommended_products.forEach(function(productId) {
                    // Get product details via AJAX
                    getProductDetails(productId, function(product) {
                        let productCard = '<div class="wvp-ai-product-card">';
                        productCard += '<div class="wvp-product-image">';
                        if (product.image_url) {
                            productCard += '<img src="' + product.image_url + '" alt="' + product.name + '" style="width: 100%; height: 150px; object-fit: cover; border-radius: 6px;">';
                        } else {
                            productCard += '<div style="width: 100%; height: 150px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border-radius: 6px; color: #666;">Nema slike</div>';
                        }
                        productCard += '</div>';
                        productCard += '<h4>' + product.name + '</h4>';
                        if (product.short_description) {
                            productCard += '<p class="wvp-product-description">' + product.short_description + '</p>';
                        }
                        productCard += '<div class="wvp-product-price">';
                        if (product.sale_price && product.sale_price < product.regular_price) {
                            productCard += '<span class="wvp-regular-price">' + formatPrice(product.regular_price) + '</span>';
                            productCard += '<span class="wvp-sale-price">' + formatPrice(product.sale_price) + '</span>';
                        } else {
                            productCard += '<span class="wvp-current-price">' + formatPrice(product.price) + '</span>';
                        }
                        productCard += '</div>';
                        productCard += '<button class="wvp-add-to-cart-btn" data-product-id="' + productId + '">Dodaj u korpu</button>';
                        productCard += '</div>';

                        $('#wvp-ai-products-list .wvp-ai-products-grid').append(productCard);
                    });
                });
            }

            // Display AI recommended packages
            if (data.ai_recommended_packages && data.ai_recommended_packages.length > 0) {
                let packagesHtml = '<div class="wvp-ai-packages-grid">';
                data.ai_recommended_packages.forEach(function(packageRec) {
                    if (typeof packageRec === 'object' && packageRec.id) {
                        // Get package details via AJAX
                        getPackageDetails(packageRec.id, packageRec.size, function(packageData) {
                            let packageCard = '<div class="wvp-ai-package-card" data-package-id="' + packageRec.id + '" data-size="' + packageRec.size + '" data-discount="' + packageData.discount + '">';
                            packageCard += '<h4>' + packageData.name + '</h4>';
                            packageCard += '<p class="wvp-package-description">' + packageData.description + '</p>';
                            packageCard += '<div class="wvp-package-size-info">';
                            packageCard += '<p><strong>Preporučena veličina:</strong> ' + packageRec.size + ' stavki</p>';
                            if (packageRec.reason) {
                                packageCard += '<p class="wvp-package-reason"><strong>Razlog:</strong> ' + packageRec.reason + '</p>';
                            }
                            packageCard += '</div>';

                            // Package configuration section
                            packageCard += '<div class="wvp-package-config">';
                            packageCard += '<h5>AI Izabrani Proizvodi:</h5>';

                            // Auto-selected products display
                            if (packageData.products && packageData.products.length > 0) {
                                // Automatically select products based on package size
                                const selectedProducts = packageData.products.slice(0, packageRec.size);

                                packageCard += '<div class="wvp-ai-selected-products">';
                                packageCard += '<div class="wvp-selected-products-info">';
                                packageCard += '<p class="wvp-ai-selection-note">🤖 AI je automatski izabrao najbolje proizvode za vas:</p>';
                                packageCard += '</div>';

                                packageCard += '<div class="wvp-selected-products-list">';
                                selectedProducts.forEach(function(product, index) {
                                    packageCard += '<div class="wvp-selected-product-item">';
                                    packageCard += '<div class="wvp-product-image-small">';
                                    if (product.image_url) {
                                        packageCard += '<img src="' + product.image_url + '" alt="' + product.name + '" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">';
                                    } else {
                                        packageCard += '<div style="width: 40px; height: 40px; background: #f8f9fa; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #666;">📦</div>';
                                    }
                                    packageCard += '</div>';
                                    packageCard += '<div class="wvp-product-info">';
                                    packageCard += '<span class="wvp-product-name">' + product.name + '</span>';
                                    packageCard += '<span class="wvp-product-price-display">' + formatPrice(product.price) + '</span>';
                                    packageCard += '</div>';
                                    packageCard += '<div class="wvp-selected-indicator">✅</div>';
                                    // Store selected product ID for later use
                                    packageCard += '<input type="hidden" class="wvp-selected-product-id" data-package-id="' + packageRec.id + '" value="' + product.id + '">';
                                    packageCard += '</div>';
                                });
                                packageCard += '</div>';
                            }

                            // Pricing information
                            packageCard += '<div class="wvp-package-pricing">';
                            packageCard += '<div class="wvp-price-breakdown">';
                            packageCard += '<p class="wvp-regular-total">Regularna cena: <span id="regular-total-' + packageRec.id + '">-</span></p>';
                            packageCard += '<p class="wvp-discount-amount">Popust (' + (packageData.discount || 0) + '%): <span id="discount-amount-' + packageRec.id + '">-</span></p>';
                            packageCard += '<p class="wvp-final-total">Ukupno: <span id="final-total-' + packageRec.id + '">-</span></p>';
                            packageCard += '</div>';
                            packageCard += '</div>';

                            packageCard += '<button class="wvp-add-package-to-cart-btn" data-package-id="' + packageRec.id + '" data-size="' + packageRec.size + '">🛒 Kupi Ovaj Paket</button>';
                            packageCard += '</div>';
                            packageCard += '</div>';

                            $('#wvp-ai-packages-list .wvp-ai-packages-grid').append(packageCard);

                            // Calculate initial pricing for auto-selected products
                            calculateAutoSelectedPackagePrice(packageRec.id, selectedProducts);
                        });
                    }
                });
                packagesHtml += '</div>';
                $('#wvp-ai-packages-list').html(packagesHtml);
            }

            $('#wvp-ai-results').show();
        }

        function updateScoreDisplay(score) {
            const circle = $('#wvp-ai-score-circle');
            const number = $('#wvp-ai-score-number');
            const description = $('#wvp-ai-score-description');

            number.text(score);

            if (score >= 80) {
                circle.removeClass().addClass('wvp-score-circle excellent');
                description.text('Odličo zdravstveno stanje!');
            } else if (score >= 60) {
                circle.removeClass().addClass('wvp-score-circle good');
                description.text('Dobro zdravstveno stanje');
            } else if (score >= 40) {
                circle.removeClass().addClass('wvp-score-circle warning');
                description.text('Potrebna pažnja');
            } else {
                circle.removeClass().addClass('wvp-score-circle danger');
                description.text('Potrebna hitna pažnja');
            }
        }

        function showAIError(message) {
            $('#wvp-ai-status').hide();
            if (message) {
                $('#wvp-ai-error p').text(message);
            }
            $('#wvp-ai-error').show();
        }

        function testOpenAIConnection() {
            const button = $('#wvp-test-openai');
            const results = $('#wvp-test-results');

            button.prop('disabled', true).text('Testiramo...');
            results.hide();

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'wvp_test_openai_connection',
                    nonce: '<?php echo wp_create_nonce('wvp_test_openai_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        results.removeClass('error').addClass('success')
                               .html('<strong>✅ Uspešno!</strong> OpenAI konekcija radi ispravno.')
                               .show();
                    } else {
                        results.removeClass('success').addClass('error')
                               .html('<strong>❌ Greška!</strong> ' + (response.data.message || 'Nepoznata greška'))
                               .show();
                    }
                },
                error: function() {
                    results.removeClass('success').addClass('error')
                           .html('<strong>❌ Greška!</strong> Nisu mogli da se povežemo sa serverom.')
                           .show();
                },
                complete: function() {
                    button.prop('disabled', false).text('Testiraj OpenAI konekciju');
                }
            });
        }

        function triggerAIAnalysis(resultId) {
            const button = $('#wvp-trigger-ai');
            const results = $('#wvp-test-results');

            button.prop('disabled', true).text('Pokrećemo AI...');
            results.hide();

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'wvp_trigger_ai_analysis',
                    result_id: resultId,
                    nonce: '<?php echo wp_create_nonce('wvp_ai_analysis_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        results.removeClass('error').addClass('success')
                               .html('<strong>✅ Uspešno!</strong> AI analiza je pokrenuta. Rezultati će biti prikazani za koji minut.')
                               .show();

                        // Start checking for results
                        setTimeout(function() {
                            startAIAnalysis(resultId);
                        }, 3000);
                    } else {
                        results.removeClass('success').addClass('error')
                               .html('<strong>❌ Greška!</strong> ' + (response.data.message || 'Nepoznata greška'))
                               .show();
                    }
                },
                error: function() {
                    results.removeClass('success').addClass('error')
                           .html('<strong>❌ Greška!</strong> Nije moguće pokrenuti AI analizu.')
                           .show();
                },
                complete: function() {
                    button.prop('disabled', false).text('Pokreni AI Analizu');
                }
            });
        }

        // Helper function to get product details
        function getProductDetails(productId, callback) {
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'wvp_get_product_details',
                    product_id: productId,
                    nonce: '<?php echo wp_create_nonce('wvp_product_details_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success && response.data) {
                        callback(response.data);
                    } else {
                        console.error('Failed to get product details:', response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error getting product details:', error);
                }
            });
        }

        // Helper function to get package details
        function getPackageDetails(packageId, size, callback) {
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'wvp_get_package_details',
                    package_id: packageId,
                    size: size,
                    nonce: '<?php echo wp_create_nonce('wvp_package_details_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success && response.data) {
                        callback(response.data);
                    } else {
                        console.error('Failed to get package details:', response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error getting package details:', error);
                }
            });
        }

        // Function to calculate package price for auto-selected products
        function calculateAutoSelectedPackagePrice(packageId, selectedProducts) {
            let regularTotal = 0;
            let discountPercent = 0;

            // Calculate total from auto-selected products
            selectedProducts.forEach(function(product) {
                regularTotal += parseFloat(product.price);
            });

            // Get discount from package data
            const packageCard = $('.wvp-ai-package-card[data-package-id="' + packageId + '"]');
            discountPercent = parseFloat(packageCard.data('discount') || 0);

            const discountAmount = regularTotal * (discountPercent / 100);
            const finalTotal = regularTotal - discountAmount;

            $('#regular-total-' + packageId).text(formatPrice(regularTotal));
            $('#discount-amount-' + packageId).text('-' + formatPrice(discountAmount));
            $('#final-total-' + packageId).text(formatPrice(finalTotal));

            // Package is always ready since AI auto-selected products
            const addButton = packageCard.find('.wvp-add-package-to-cart-btn');
            addButton.prop('disabled', false);
        }

        // Event handlers for cart functionality
        $(document).on('click', '.wvp-add-to-cart-btn', function() {
            const productId = $(this).data('product-id');
            const button = $(this);

            button.prop('disabled', true).text('Dodajem...');

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'wvp_add_product_to_cart',
                    product_id: productId,
                    nonce: '<?php echo wp_create_nonce('wvp_add_to_cart_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        button.text('✅ Dodato!').removeClass('wvp-add-to-cart-btn').addClass('wvp-added-to-cart');

                        // Show success message
                        showCartMessage('Proizvod je uspešno dodat u korpu!', 'success');
                    } else {
                        button.prop('disabled', false).text('Dodaj u korpu');
                        showCartMessage('Greška pri dodavanju u korpu: ' + (response.data?.message || 'Nepoznata greška'), 'error');
                    }
                },
                error: function() {
                    button.prop('disabled', false).text('Dodaj u korpu');
                    showCartMessage('Greška pri dodavanju u korpu.', 'error');
                }
            });
        });

        $(document).on('click', '.wvp-add-package-to-cart-btn', function() {
            const packageId = $(this).data('package-id');
            const size = $(this).data('size');
            const button = $(this);

            // Get auto-selected products
            const selectedProducts = [];
            $('.wvp-selected-product-id[data-package-id="' + packageId + '"]').each(function() {
                const productId = $(this).val();
                if (productId) {
                    selectedProducts.push(productId);
                }
            });

            if (selectedProducts.length !== parseInt(size)) {
                showCartMessage('Greška: AI nije pravilno izabrao proizvode. Molimo pokušajte ponovo.', 'error');
                return;
            }

            button.prop('disabled', true).text('Dodajem u korpu...');

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'wvp_add_package_to_cart',
                    package_id: packageId,
                    size: size,
                    products: selectedProducts,
                    nonce: '<?php echo wp_create_nonce('wvp_add_to_cart_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        button.text('✅ Dodato u korpu!').removeClass('wvp-add-package-to-cart-btn').addClass('wvp-added-to-cart');
                        showCartMessage('🎉 AI paket je uspešno dodat u korpu!', 'success');
                    } else {
                        button.prop('disabled', false).text('🛒 Kupi Ovaj Paket');
                        showCartMessage('Greška pri dodavanju paketa: ' + (response.data?.message || 'Nepoznata greška'), 'error');
                    }
                },
                error: function() {
                    button.prop('disabled', false).text('🛒 Kupi Ovaj Paket');
                    showCartMessage('Greška pri dodavanju paketa.', 'error');
                }
            });
        });

        // No more manual quantity controls - AI auto-selects everything!

        // Price formatting helper function
        function formatPrice(price) {
            return parseFloat(price).toLocaleString('sr-RS', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }) + ' RSD';
        }

        function showCartMessage(message, type) {
            const messageHtml = '<div class="wvp-cart-message wvp-cart-message-' + type + '">' + message + '</div>';

            // Remove existing messages
            $('.wvp-cart-message').remove();

            // Add new message
            $('#wvp-ai-results').prepend(messageHtml);

            // Auto-hide after 5 seconds
            setTimeout(function() {
                $('.wvp-cart-message').fadeOut(500, function() {
                    $(this).remove();
                });
            }, 5000);
        }

        // Initialize Body Diagram functionality
        initializeBodyDiagram();

        // Initialize Package Selection functionality
        initializePackageSelection();

        // Initialize Usage Instructions functionality
        initializeUsageInstructions();

        function initializeBodyDiagram() {
            // Load AI analysis results to highlight weak points
            const aiResults = localStorage.getItem('wvp_health_quiz_ai_results');
            let weakPoints = [];

            if (aiResults) {
                try {
                    const results = JSON.parse(aiResults);
                    if (results.weak_points) {
                        weakPoints = results.weak_points;
                        highlightWeakPoints(weakPoints);
                    }
                } catch (e) {
                    console.log('No AI results available for body diagram');
                }
            }

            // Initialize body diagram interactions
            $('.body-part').on('click', function() {
                const area = $(this).data('area');
                showBodyPartInfo(area, weakPoints);
            });

            $('.body-part').on('mouseenter', function() {
                const area = $(this).data('area');
                const isWeakPoint = weakPoints.find(point => point.area === area);
                const title = isWeakPoint ?
                    `⚠️ ${area} - Slaba tačka (${isWeakPoint.severity === 'high' ? 'Visok rizik' : isWeakPoint.severity === 'moderate' ? 'Umeren rizik' : 'Nizak rizik'})` :
                    `Kliknite za više informacija o ${area}`;
                $(this).attr('title', title);
            });
        }

        function highlightWeakPoints(weakPoints) {
            // Remove existing highlights
            $('.body-part').removeClass('weak-point').removeAttr('data-severity');

            // Add highlights for weak points
            weakPoints.forEach(point => {
                const bodyPart = $(`.body-part[data-area="${point.area}"]`);
                if (bodyPart.length) {
                    bodyPart.addClass('weak-point');
                    bodyPart.attr('data-severity', point.severity || 'moderate');
                }
            });
        }

        function showBodyPartInfo(area, weakPoints = []) {
            const bodyPartInfo = {
                'head': {
                    title: 'Glava i mozak',
                    problems: ['Glavobolje', 'Migrene', 'Stres', 'Problemi sa koncentracijom'],
                    symptoms: ['Bol u glavi', 'Vrtoglavica', 'Zamor'],
                    recommendations: ['Omega-3 masne kiseline', 'Vitamin B kompleks', 'Magnezijum', 'Ginkgo biloba']
                },
                'neck': {
                    title: 'Vrat',
                    problems: ['Napetost u vratu', 'Krutost', 'Bol'],
                    symptoms: ['Bol u vratu', 'Ograničena pokretljivost'],
                    recommendations: ['Magnezijum', 'Kurkumin', 'Vitamin D', 'MSM']
                },
                'chest': {
                    title: 'Grudi i srce',
                    problems: ['Kardiovaskularne tegobe', 'Problemi sa disanjem', 'Napetost'],
                    symptoms: ['Bol u grudima', 'Otežano disanje', 'Lupanje srca'],
                    recommendations: ['Koenzim Q10', 'Hawthorne extract', 'Omega-3', 'Vitamin C']
                },
                'stomach': {
                    title: 'Stomak i digestija',
                    problems: ['Problemi sa digestijom', 'Nadutost', 'Gastritis'],
                    symptoms: ['Bol u stomaku', 'Nadutost', 'Mučnina'],
                    recommendations: ['Probiotici', 'Digestivni enzimi', 'Aloe vera', 'L-glutamin']
                },
                'arms': {
                    title: 'Ruke i ramena',
                    problems: ['Bol u ramenima', 'Napetost', 'Smanjenu snagu'],
                    symptoms: ['Bol u rukama', 'Utrnulost', 'Slabost'],
                    recommendations: ['Magnezijum', 'Vitamin D', 'Kolagen', 'B vitamini']
                },
                'legs': {
                    title: 'Noge i cirkulacija',
                    problems: ['Problemi sa cirkulacijom', 'Otok', 'Umor'],
                    symptoms: ['Bol u nogama', 'Otok', 'Teško kretanje'],
                    recommendations: ['Ginko biloba', 'Vitamin E', 'Crvena repa', 'Rutin']
                },
                'joints': {
                    title: 'Zglobovi',
                    problems: ['Artritis', 'Upala zglobova', 'Krutost'],
                    symptoms: ['Bol u zglobovima', 'Krutost', 'Otežano kretanje'],
                    recommendations: ['Glukozamin', 'Kondroitin', 'MSM', 'Kurkumin']
                }
            };

            const info = bodyPartInfo[area];
            if (info) {
                // Check if this body part is identified as weak point by AI
                const weakPoint = weakPoints.find(point => point.area === area);
                let aiAnalysisSection = '';
                let severityBadge = '';

                if (weakPoint) {
                    const severityColor = weakPoint.severity === 'high' ? '#dc3545' :
                                         weakPoint.severity === 'moderate' ? '#ffc107' : '#17a2b8';
                    const severityText = weakPoint.severity === 'high' ? 'Visok rizik' :
                                        weakPoint.severity === 'moderate' ? 'Umeren rizik' : 'Nizak rizik';

                    severityBadge = `<span class="severity-badge" style="background: ${severityColor}; color: white; padding: 5px 10px; border-radius: 15px; font-size: 12px; margin-left: 10px;">${severityText}</span>`;

                    if (weakPoint.description) {
                        aiAnalysisSection = `
                            <div class="info-section ai-analysis-section" style="background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); padding: 20px; border-radius: 10px; border-left: 5px solid ${severityColor}; margin-bottom: 20px;">
                                <h4 style="color: #856404; margin-bottom: 15px;">🤖 AI Analiza za ovaj deo tela:</h4>
                                <p style="color: #856404; font-weight: 500; margin: 0;">${weakPoint.description}</p>
                            </div>
                        `;
                    }
                }

                const modal = `
                    <div class="body-part-modal-overlay" onclick="closeBodyPartModal()">
                        <div class="body-part-modal" onclick="event.stopPropagation()">
                            <div class="modal-header">
                                <h3>${info.title}${severityBadge}</h3>
                                <button onclick="closeBodyPartModal()" class="close-btn">&times;</button>
                            </div>
                            <div class="modal-content">
                                ${aiAnalysisSection}
                                <div class="info-section">
                                    <h4>Mogući problemi:</h4>
                                    <ul>
                                        ${info.problems.map(problem => `<li>${problem}</li>`).join('')}
                                    </ul>
                                </div>
                                <div class="info-section">
                                    <h4>Simptomi na koje treba obratiti pažnju:</h4>
                                    <ul>
                                        ${info.symptoms.map(symptom => `<li>${symptom}</li>`).join('')}
                                    </ul>
                                </div>
                                <div class="info-section recommendations-section">
                                    <h4>Preporučeni suplementi:</h4>
                                    <div class="recommendations-grid">
                                        ${info.recommendations.map(rec => `
                                            <div class="recommendation-item">
                                                <span class="recommendation-icon">💊</span>
                                                <span class="recommendation-name">${rec}</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                $('body').append(modal);
            }
        }

        window.closeBodyPartModal = function() {
            $('.body-part-modal-overlay').remove();
        };

        function initializePackageSelection() {
            // Handle package option selection
            $('.package-option').on('click', function() {
                $('.package-option').removeClass('selected');
                $(this).addClass('selected');

                const packageType = $(this).data('package-type');
                filterAIPackagesByType(packageType);

                // Show usage instructions when a package is selected
                showUsageInstructions(packageType);

                // Update package selection description
                updatePackageSelectionDescription(packageType);
            });

            // Auto-select recommended package based on AI analysis
            const aiResults = localStorage.getItem('wvp_health_quiz_ai_results');
            if (aiResults) {
                try {
                    const results = JSON.parse(aiResults);
                    const recommendedType = determineRecommendedPackageType(results);
                    if (recommendedType) {
                        $(`.package-option[data-package-type="${recommendedType}"]`).addClass('recommended');
                        // Auto-click the recommended option after a short delay
                        setTimeout(() => {
                            $(`.package-option[data-package-type="${recommendedType}"]`).click();
                        }, 1000);
                    }
                } catch (e) {
                    console.log('Could not auto-select package type');
                }
            }
        }

        function determineRecommendedPackageType(aiResults) {
            const score = aiResults.skor || 50;
            const symptomCount = countSymptoms(aiResults);

            // Logic to determine package type based on AI analysis
            if (score < 40 || symptomCount >= 5) {
                return 'intensive';
            } else if (score < 70 || symptomCount >= 3) {
                return 'optimal';
            } else {
                return 'light';
            }
        }

        function countSymptoms(aiResults) {
            // Count various indicators of health issues
            let symptomCount = 0;

            if (aiResults.weak_points) {
                symptomCount += aiResults.weak_points.length;
            }

            // Add other symptom counting logic based on the quiz data
            const quizData = localStorage.getItem('wvp_health_quiz_data');
            if (quizData) {
                try {
                    const data = JSON.parse(quizData);
                    if (data.answers) {
                        // Count "Da" answers as symptoms
                        Object.values(data.answers).forEach(answer => {
                            if (answer.answer === 'Da') {
                                symptomCount++;
                            }
                        });
                    }
                } catch (e) {
                    console.log('Could not parse quiz data for symptom counting');
                }
            }

            return symptomCount;
        }

        function updatePackageSelectionDescription(packageType) {
            const descriptions = {
                'light': 'Blagi pristup je idealan za preventivnu negu i održavanje zdravlja. Manji broj proizvoda fokusiran na ključne potrebe.',
                'optimal': 'Optimalni pristup pruža balansiranu kombinaciju suplementa za poboljšanje opšteg zdravlja i energije.',
                'intensive': 'Intenzivni pristup je namenjen za ozbiljne zdravstvene probleme i zahteva sveobuhvatan tretman.'
            };

            const description = descriptions[packageType] || descriptions['optimal'];
            $('.section-description').text(description);
        }

        function filterAIPackagesByType(packageType) {
            // Filter AI generated packages based on selected type
            const packageSizeMap = {
                'light': [2, 3],
                'optimal': [4, 5],
                'intensive': [6, 7, 8]
            };

            const targetSizes = packageSizeMap[packageType] || [4, 5];

            $('.wvp-ai-package-card').each(function() {
                const packageSize = parseInt($(this).data('size') || 4);

                if (targetSizes.includes(packageSize)) {
                    $(this).show().addClass('highlight-package');
                } else {
                    $(this).hide().removeClass('highlight-package');
                }
            });
        }

        function initializeUsageInstructions() {
            // Monitor for cart additions to show usage instructions
            $(document).on('wvp_package_added_to_cart', function(e, packageData) {
                showUsageInstructions('optimal', packageData.products);
            });

            $(document).on('wvp_product_added_to_cart', function(e, productData) {
                showUsageInstructions('light', [productData]);
            });
        }

        function showUsageInstructions(packageType, products = []) {
            const instructionTemplates = {
                'light': {
                    morning: ['Zeleni sok (30ml) sa 150ml vode', 'Osnovni probiotik', 'Na prazan stomak 30min pre doručka'],
                    afternoon: ['Vitamin C iz prirodnih izvora', 'Uz ručak ako potrebno'],
                    evening: ['Magnezijum (prirodni)', 'Biljni čaj za opuštanje', 'Pre spavanja']
                },
                'optimal': {
                    morning: ['Zeleni sok (60ml) + probiotik', 'Spirulina ili chlorella', 'Na prazan stomak uz 200ml vode'],
                    afternoon: ['Adaptogeni za energiju', 'Vitamin D3 prirodni', 'Uz ručak'],
                    evening: ['Magnezijum bisglicinat', 'Omega-3 iz biljnih izvora', 'Pre spavanja (1-2h)']
                },
                'intensive': {
                    morning: ['Koncentrovani zeleni sok (90ml)', 'Probiotik kompleks', 'Chlorella/spirulina mix', 'Na prazan stomak uz 300ml vode'],
                    afternoon: ['Adaptogeni kompeks', 'Prirodni vitamin kompleks', 'Uz ručak bogat mastima'],
                    evening: ['Magnezijum + cink', 'Detoks čaj', 'Biljni antioksidanti', 'Pre spavanja (2h)']
                }
            };

            let personalizedInstructions = instructionTemplates[packageType] || instructionTemplates['optimal'];

            // Personalize instructions based on AI analysis and weak points
            const aiResults = localStorage.getItem('wvp_health_quiz_ai_results');
            if (aiResults) {
                try {
                    const results = JSON.parse(aiResults);
                    personalizedInstructions = personalizeInstructionsForUser(personalizedInstructions, results);
                } catch (e) {
                    console.log('Could not personalize instructions');
                }
            }

            $('#morning-instructions').html(createInstructionList(personalizedInstructions.morning));
            $('#afternoon-instructions').html(createInstructionList(personalizedInstructions.afternoon));
            $('#evening-instructions').html(createInstructionList(personalizedInstructions.evening));

            // Add specific product instructions if products are provided
            if (products.length > 0) {
                updateSpecificProductInstructions(products);
            }

            // Add personalized tips based on weak points
            addPersonalizedTips(packageType);

            $('#usage-instructions').slideDown();
        }

        function createInstructionList(instructions) {
            return '<ul class="instruction-list">' +
                instructions.map(item => `<li class="instruction-item">
                    <span class="instruction-icon">💊</span>
                    <span class="instruction-text">${item}</span>
                </li>`).join('') + '</ul>';
        }

        function personalizeInstructionsForUser(instructions, aiResults) {
            const personalizedInstructions = JSON.parse(JSON.stringify(instructions)); // Deep copy

            // Add specific supplements based on weak points
            if (aiResults.weak_points) {
                aiResults.weak_points.forEach(weakPoint => {
                    const supplement = getSupplementForWeakPoint(weakPoint.area, weakPoint.severity);
                    if (supplement.timing === 'morning') {
                        personalizedInstructions.morning.push(supplement.instruction);
                    } else if (supplement.timing === 'evening') {
                        personalizedInstructions.evening.push(supplement.instruction);
                    }
                });
            }

            return personalizedInstructions;
        }

        function getSupplementForWeakPoint(area, severity) {
            const supplementMap = {
                'stomach': {
                    instruction: 'Dodatni zeleni sok + probiotik za digestiju',
                    timing: 'morning'
                },
                'head': {
                    instruction: 'Ginkgo biloba + omega-3 iz algi za mozak',
                    timing: 'morning'
                },
                'joints': {
                    instruction: 'Kurkumin + biljni kolagen za zglobove',
                    timing: 'evening'
                },
                'chest': {
                    instruction: 'Hawthorne + prirodni vitamin E za srce',
                    timing: 'morning'
                },
                'legs': {
                    instruction: 'Ginkgo + vitamin E za cirkulaciju',
                    timing: 'afternoon'
                },
                'neck': {
                    instruction: 'Magnezijum + adaptogeni za mišićnu tenziju',
                    timing: 'evening'
                },
                'arms': {
                    instruction: 'B vitamin kompleks + magnezijum',
                    timing: 'morning'
                }
            };

            return supplementMap[area] || {
                instruction: 'Zeleni sok + prirodni multivitamin',
                timing: 'morning'
            };
        }

        function updateSpecificProductInstructions(products) {
            let specificInstructions = '<h5 style="color: #856404; margin-top: 20px;">🎯 Specifične instrukcije za vaše proizvode:</h5><ul class="specific-products-list">';

            products.forEach(product => {
                const instruction = getDetailedProductInstructions(product.name);
                specificInstructions += `<li class="product-instruction">
                    <strong>${product.name}</strong><br>
                    <span class="instruction-details">${instruction}</span>
                </li>`;
            });

            specificInstructions += '</ul>';
            $('#important-notes-list').html(specificInstructions);
        }

        function addPersonalizedTips(packageType) {
            const tips = {
                'light': [
                    'Pijte 2L čiste filtirane vode dnevno za podršku detoksikaciji',
                    'Uzimajte zelene sokove na prazan stomak za najbolji efekat',
                    'Fokusirajte se na organsku hranu bez pesticida',
                    'Dodajte svež limun u vodu za dodatnu alkalizaciju'
                ],
                'optimal': [
                    'Pijte 2.5L vode dnevno + zelenе čajeve za antioksidante',
                    'Vodite dnevnik energije i digestije za praćenje napretka',
                    'Kombinujte sa blagom fizičkom aktivnošću (joga, šetnja)',
                    'Izbegavajte procesiranu hranu tokom detoks perioda',
                    'Uključite fermentisane namirnice za probiotike'
                ],
                'intensive': [
                    'Pijte 3L vode + biljne čajeve za pojačanu detoksikaciju',
                    'Razgovarajte sa nutricionistom o holističkom pristupu',
                    'Vodite detaljan dnevnik ishrane i simptoma',
                    'Počnite postupno - prve 3 dana pola doze',
                    'Možda će se javiti blagi detoks simptomi (glavobolja, umor) - to je normalno',
                    'Izbegavajte kofein, alkohol i prerađenu hranu potpuno'
                ]
            };

            const tipsList = tips[packageType] || tips['optimal'];
            let tipsHtml = '<div class="personalized-tips"><h5 style="color: #856404;">💡 Personalizovani saveti:</h5><ul>';

            tipsList.forEach(tip => {
                tipsHtml += `<li class="tip-item"><span class="tip-icon">💡</span> ${tip}</li>`;
            });

            tipsHtml += '</ul></div>';
            $('.important-notes').append(tipsHtml);
        }

        function getDetailedProductInstructions(productName) {
            // Generate detailed instructions based on product name/type - focused on green juices and natural supplements
            const instructionMap = {
                'zeleni sok': 'Ujutru na prazan stomak, 30 minuta pre doručka. Razblažiti sa vodom ako je potrebno. Najbolji efekat za detoksikaciju.',
                'sok od žita': 'Na prazan stomak ujutru. Bogat hlorofilom - pomaže alkalizaciji organizma. Piti polako.',
                'sok od trave': 'Ujutru pre obroka. Snažan detoks efekat - početi sa malom dozom i postupno povećavati.',
                'chlorella': 'Sa doručkom, zapiti puno vode. Snažan detoksifikator - može izazvati blage detoks simptome na početku.',
                'spirulina': 'Ujutru na prazan stomak sa vodom ili u smoothie. Bogata proteinima - odličan za energiju.',
                'probiotik': 'Na prazan stomak ujutru, 30 minuta pre doručka. Čuvati u frižideru. Pomaže digestiji.',
                'vitamin c': 'Podeliti dozu tokom dana. Prirodni vitamin C bolje se apsorbuje uz bioflavonoide.',
                'vitamin d': 'Uz obrok koji sadrži masti. Najbolje ujutru za održavanje cirkadijanog ritma.',
                'omega': 'Uz obrok sa mastima. Biljni izvori (lan, čia) su lakši za digestiju.',
                'magnezijum': 'Pre spavanja za opuštanje. Prirodne forme (citrat, bisglicinat) su bolje.',
                'adaptogen': 'Ujutru ili pre podne. Pomaže u upravljanju stresom i povećanju energije.',
                'antoksidant': 'Sa obrokom za bolje usvajanje. Prirodni antioksidanti rade sinergijski.',
                'detoks': 'Sa puno vode tokom dana. Podržava prirodne detoks procese jetre i bubrega.',
                'enzim': 'Neposredno pre ili tokom obroka za poboljšanje digestije.',
                'kolagen': 'Na prazan stomak sa vitamin C za bolju sintezu. Biljni kolagen je vegansko rešenje.',
                'kurkumin': 'Uz masni obrok i crni biber za povećanu bioraspoloživost.',
                'ashwagandha': 'Ujutru ili uveče. Adaptogen koji pomaže u upravljanju stresom.',
                'ginseng': 'Ujutru za energiju. Ne uzimati uveče jer može uticati na san.'
            };

            // Try to match product name with instruction keywords
            for (const [keyword, instruction] of Object.entries(instructionMap)) {
                if (productName.toLowerCase().includes(keyword)) {
                    return instruction;
                }
            }

            return 'Pratiti uputstva na pakovanju. Uzimati redovno u isto vreme za najbolje rezultate.';
        }

        function getProductSpecificInstructions(productName) {
            // Shorter version for backward compatibility
            return getDetailedProductInstructions(productName);
        }

        function updateInteractiveScoreDisplay(score) {
            const scoreContainer = $('#wvp-ai-health-score');
            if (scoreContainer.length === 0) return;

            const scoreHtml = createInteractiveHealthScore(score);
            scoreContainer.html(scoreHtml);

            // Animate score bar
            setTimeout(() => {
                $('.wvp-score-fill').css('width', score + '%');
                animateScoreNumber(score);
            }, 500);
        }

        function createInteractiveHealthScore(score) {
            const scoreClass = score >= 70 ? 'good' : score >= 40 ? 'moderate' : 'poor';
            const scoreColor = score >= 70 ? '#28a745' : score >= 40 ? '#ffc107' : '#dc3545';
            const scoreText = score >= 70 ? 'Odličo zdravlje! 🎉' : score >= 40 ? 'Umereno zdravlje ⚖️' : 'Potrebno poboljšanje ⚠️';

            return `
                <div class="wvp-interactive-health-score">
                    <div class="score-header">
                        <h3 class="score-title">Vaš zdravstveni skor</h3>
                        <div class="score-circle" data-score="${score}">
                            <div class="score-number" style="color: ${scoreColor}">
                                <span class="animated-number">0</span>/100
                            </div>
                            <svg class="score-progress" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="45" fill="none" stroke="#e9ecef" stroke-width="6"/>
                                <circle cx="50" cy="50" r="45" fill="none" stroke="${scoreColor}" stroke-width="6"
                                        stroke-linecap="round" stroke-dasharray="283" stroke-dashoffset="283"
                                        class="score-progress-bar" style="transition: stroke-dashoffset 2s ease-in-out;"/>
                            </svg>
                        </div>
                    </div>
                    <div class="score-description ${scoreClass}">
                        <p class="score-status">${scoreText}</p>
                        <div class="score-interpretation">
                            ${getScoreInterpretation(score)}
                        </div>
                    </div>
                </div>
            `;
        }

        function animateScoreNumber(targetScore) {
            const numberElement = $('.animated-number');
            let currentScore = 0;
            const increment = targetScore / 50; // 50 steps animation

            const timer = setInterval(() => {
                currentScore += increment;
                if (currentScore >= targetScore) {
                    currentScore = targetScore;
                    clearInterval(timer);
                }
                numberElement.text(Math.round(currentScore));
            }, 40);

            // Animate circular progress
            const circumference = 2 * Math.PI * 45;
            const offset = circumference - (targetScore / 100) * circumference;
            $('.score-progress-bar').css('stroke-dashoffset', offset);
        }

        function getScoreInterpretation(score) {
            if (score >= 85) {
                return '<span class="interpretation-text">Izvrsno! Vaše zdravlje je u odličnom stanju. Nastavite sa postojećim navikama.</span>';
            } else if (score >= 70) {
                return '<span class="interpretation-text">Vrlo dobro! Mali dodaci mogu dodatno poboljšati vaše zdravlje.</span>';
            } else if (score >= 55) {
                return '<span class="interpretation-text">Prosečno. Postoji prostor za poboljšanje sa odgovarajućim suplementima.</span>';
            } else if (score >= 40) {
                return '<span class="interpretation-text">Ispod proseka. Preporučujemo fokus na ključne zdravstvene probleme.</span>';
            } else {
                return '<span class="interpretation-text">Potrebna je značajna pažnja. Savetujemo da se konsultujete sa lekarom.</span>';
            }
        }

        function displayEnhancedAIAnalysis(analysis) {
            if (analysis.stanje_organizma) {
                const healthAnalysisHtml = createExpandableSection(
                    'Analiza stanja organizma',
                    '🩺',
                    analysis.stanje_organizma,
                    'health-analysis'
                );
                $('#wvp-ai-health-text').html(healthAnalysisHtml);
            }

            if (analysis.preporuke) {
                const recommendationsHtml = createExpandableSection(
                    'Personalizovane preporuke',
                    '💡',
                    analysis.preporuke,
                    'recommendations'
                );
                $('#wvp-ai-recommendations-text').html(recommendationsHtml);
            }

            // Add interactive elements to sections
            initializeExpandableSections();
        }

        function createExpandableSection(title, icon, content, sectionId) {
            return `
                <div class="expandable-section" id="${sectionId}">
                    <div class="section-header" onclick="toggleSection('${sectionId}')">
                        <div class="section-title">
                            <span class="section-icon">${icon}</span>
                            <h4>${title}</h4>
                        </div>
                        <span class="expand-arrow">▼</span>
                    </div>
                    <div class="section-content">
                        <div class="content-text">${content}</div>
                        <div class="section-actions">
                            <button class="action-btn" onclick="shareSection('${sectionId}')">
                                📤 Podeli
                            </button>
                            <button class="action-btn" onclick="printSection('${sectionId}')">
                                🖨️ Štampaj
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        function initializeExpandableSections() {
            $('.expandable-section').each(function() {
                $(this).find('.section-content').slideUp(0);
            });

            // Auto-expand first section
            setTimeout(() => {
                $('.expandable-section').first().find('.section-header').click();
            }, 1000);
        }

        window.toggleSection = function(sectionId) {
            const section = $('#' + sectionId);
            const content = section.find('.section-content');
            const arrow = section.find('.expand-arrow');

            if (content.is(':visible')) {
                content.slideUp(300);
                arrow.text('▼');
                section.removeClass('expanded');
            } else {
                content.slideDown(300);
                arrow.text('▲');
                section.addClass('expanded');
            }
        };

        window.shareSection = function(sectionId) {
            const section = $('#' + sectionId);
            const title = section.find('h4').text();
            const content = section.find('.content-text').text();

            if (navigator.share) {
                navigator.share({
                    title: title,
                    text: content,
                    url: window.location.href
                });
            } else {
                // Fallback - copy to clipboard
                navigator.clipboard.writeText(content).then(() => {
                    alert('Sadržaj je kopiran u clipboard!');
                });
            }
        };

        window.printSection = function(sectionId) {
            const section = $('#' + sectionId);
            const title = section.find('h4').text();
            const content = section.find('.content-text').html();

            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head><title>${title}</title></head>
                    <body style="font-family: Arial, sans-serif; margin: 20px;">
                        <h2>${title}</h2>
                        <div>${content}</div>
                    </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        };

        // Update body diagram based on AI analysis
        function updateBodyDiagram(aiAnalysis) {
            // This function will be called when AI analysis is complete
            // to highlight problem areas on the body diagram

            if (!aiAnalysis.body_analysis) return;

            const bodyAnalysis = aiAnalysis.body_analysis;

            // Reset all body parts to neutral
            $('.body-part').removeClass('good warning danger');

            // Apply analysis results to body parts
            Object.keys(bodyAnalysis).forEach(area => {
                const status = bodyAnalysis[area].status;
                const $bodyPart = $(`.body-part[data-area="${area}"]`);

                if ($bodyPart.length) {
                    $bodyPart.addClass(status);
                }
            });
        }

        // Override displayAIResults to include new functionality
        const originalDisplayAIResults = window.displayAIResults || function() {};

        window.displayAIResults = function(data) {
            // Call original function first
            if (typeof originalDisplayAIResults === 'function') {
                originalDisplayAIResults(data);
            }

            // Add our new functionality
            if (data.ai_analysis) {
                updateBodyDiagram(data.ai_analysis);
            }

            // Trigger events for other components
            if (data.ai_recommended_packages && data.ai_recommended_packages.length > 0) {
                $(document).trigger('ai_packages_loaded', data.ai_recommended_packages);
            }
        };

        // Hide header when quiz starts - detect when first form is submitted
        $(document).on('click', '.wvp-health-next', function() {
            // Remove show-initial class to hide header permanently
            $('.wvp-ai-health-header').removeClass('show-initial').addClass('hidden');
            $('body').addClass('quiz-started');
        });

        // Monitor for any step changes and hide header if not on step 1
        setInterval(function() {
            var currentStep = $('.wvp-health-step:visible').data('step');
            if (currentStep && currentStep != 1) {
                $('.wvp-ai-health-header').addClass('hidden').hide();
                $('body').addClass('quiz-started');
            }
        }, 100);

        // Immediate check and hide on page load if we're past step 1
        setTimeout(function() {
            if ($('.wvp-survey-step:visible').length > 0 ||
                $('.wvp-health-step:visible').data('step') > 1) {
                $('.wvp-ai-health-header').removeClass('show-initial').addClass('hidden').hide();
                $('body').addClass('quiz-started');
            }
        }, 100);

        // Also hide header immediately on any form interaction
        $(document).on('focus', 'input, select, button', function() {
            if (!$(this).closest('.wvp-ai-health-header').length) {
                $('.wvp-ai-health-header').removeClass('show-initial').addClass('hidden');
                $('body').addClass('quiz-started');
            }
        });

        // Aggressive header hiding - check every 50ms
        setInterval(function() {
            // If we detect survey step or any step beyond 1, force hide header
            if ($('.wvp-survey-step').length > 0 || $('.wvp-health-step[data-step]').length > 1) {
                $('.wvp-ai-health-header').removeClass('show-initial').addClass('hidden').hide();
                $('body').addClass('quiz-started');
            }
        }, 50);

        // Hide header immediately if URL contains quiz parameters
        if (window.location.href.indexOf('analiza-zdravstvenog-stanja') !== -1) {
            setTimeout(function() {
                $('.wvp-ai-health-header').removeClass('show-initial').addClass('hidden').hide();
                $('body').addClass('quiz-started');
            }, 10);
        }
    });
    </script>

    <style>
    /* Body Part Modal Styles */
    .body-part-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.7);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .body-part-modal {
        background: white;
        border-radius: 12px;
        max-width: 600px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
        animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
        from { transform: translateY(-50px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border-bottom: 1px solid #eee;
        background: #f8f9fa;
        color: white;
        border-radius: 12px 12px 0 0;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 1.5em;
    }

    .close-btn {
        background: none;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background 0.2s;
    }

    .close-btn:hover {
        background: rgba(255,255,255,0.2);
    }

    .modal-content {
        padding: 20px;
    }

    .info-section {
        margin-bottom: 20px;
    }

    .info-section h4 {
        color: #2c3e50;
        margin-bottom: 10px;
        font-size: 1.1em;
    }

    .info-section ul {
        margin: 0;
        padding-left: 20px;
    }

    .info-section li {
        margin-bottom: 5px;
        color: #495057;
    }

    /* Recommendations Grid */
    .recommendations-section {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 20px;
        border-radius: 10px;
        border-left: 5px solid #28a745;
    }

    .recommendations-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 10px;
        margin-top: 15px;
    }

    .recommendation-item {
        background: white;
        padding: 12px 15px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .recommendation-item:hover {
        border-color: #28a745;
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(40,167,69,0.2);
    }

    .recommendation-icon {
        font-size: 1.2em;
        flex-shrink: 0;
    }

    .recommendation-name {
        font-weight: 500;
        color: #2c3e50;
        font-size: 14px;
    }

    /* Enhanced Usage Instructions */
    .instruction-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .instruction-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid rgba(255,193,7,0.2);
    }

    .instruction-item:last-child {
        border-bottom: none;
    }

    .instruction-icon {
        font-size: 1.2em;
        flex-shrink: 0;
    }

    .instruction-text {
        font-size: 14px;
        color: #856404;
        line-height: 1.4;
    }

    .specific-products-list {
        list-style: none;
        padding: 0;
        margin: 15px 0;
    }

    .product-instruction {
        background: rgba(255,255,255,0.6);
        padding: 15px;
        margin: 10px 0;
        border-radius: 8px;
        border-left: 4px solid #ffc107;
    }

    .instruction-details {
        font-size: 13px;
        color: #6c5a00;
        font-style: italic;
        margin-top: 5px;
        display: block;
    }

    .personalized-tips {
        background: rgba(255,255,255,0.8);
        padding: 20px;
        border-radius: 10px;
        margin-top: 20px;
        border: 2px solid #28a745;
    }

    .tip-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin: 10px 0;
        font-size: 14px;
        color: #155724;
    }

    .tip-icon {
        flex-shrink: 0;
        margin-top: 2px;
    }

    /* Package highlighting */
    .highlight-package {
        border-color: #28a745 !important;
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%) !important;
        box-shadow: 0 0 20px rgba(40,167,69,0.4) !important;
        transform: scale(1.05) !important;
        position: relative;
        z-index: 10;
    }

    .wvp-ai-package-card {
        transition: all 0.3s ease;
    }

    .wvp-ai-package-card:not(.highlight-package) {
        opacity: 0.5;
        transform: scale(0.95);
    }

    .highlight-package::before {
        content: "✓ Preporučeno za ovaj tip";
        position: absolute;
        top: -10px;
        left: 10px;
        background: #28a745;
        color: white;
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 600;
        z-index: 11;
    }

    /* Interactive Health Score Styling */
    .wvp-interactive-health-score {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-radius: 8px;
        padding: 30px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }

    .score-header {
        margin-bottom: 30px;
    }

    .score-title {
        font-size: 1.8em;
        color: #2c3e50;
        margin-bottom: 30px;
        font-weight: 600;
    }

    .score-circle {
        position: relative;
        width: 150px;
        height: 150px;
        margin: 0 auto;
    }

    .score-number {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 2.2em;
        font-weight: 800;
        z-index: 10;
    }

    .score-progress {
        width: 150px;
        height: 150px;
        transform: rotate(-90deg);
    }

    .score-description {
        margin-top: 20px;
    }

    .score-status {
        font-size: 1.3em;
        font-weight: 600;
        margin-bottom: 15px;
    }

    .score-description.good .score-status { color: #28a745; }
    .score-description.moderate .score-status { color: #ffc107; }
    .score-description.poor .score-status { color: #dc3545; }

    .interpretation-text {
        font-size: 1.1em;
        color: #6c757d;
        line-height: 1.6;
        font-style: italic;
    }

    /* Expandable Sections */
    .expandable-section {
        background: white;
        border-radius: 15px;
        margin-bottom: 20px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .expandable-section:hover {
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    .expandable-section.expanded {
        border-left: 5px solid #28a745;
    }

    .section-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 20px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s ease;
    }

    .section-header:hover {
        background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .section-icon {
        font-size: 1.5em;
    }

    .section-title h4 {
        margin: 0;
        color: #2c3e50;
        font-size: 1.3em;
        font-weight: 600;
    }

    .expand-arrow {
        font-size: 1.2em;
        color: #6c757d;
        transition: transform 0.3s ease;
    }

    .expandable-section.expanded .expand-arrow {
        transform: rotate(180deg);
    }

    .section-content {
        padding: 25px;
        border-top: 1px solid #e9ecef;
    }

    .content-text {
        font-size: 1.1em;
        line-height: 1.7;
        color: #495057;
        margin-bottom: 20px;
    }

    .section-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        padding-top: 15px;
        border-top: 1px solid #f1f3f4;
    }

    .action-btn {
        background: linear-gradient(135deg, #007cba 0%, #0056b3 100%);
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 8px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .action-btn:hover {
        background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,123,186,0.3);
    }

    /* Enhanced animations */
    @keyframes scoreAnimation {
        0% { opacity: 0; transform: scale(0.8); }
        50% { opacity: 0.7; transform: scale(1.05); }
        100% { opacity: 1; transform: scale(1); }
    }

    .wvp-interactive-health-score {
        animation: scoreAnimation 0.8s ease-out;
    }

    @keyframes sectionSlideIn {
        0% { opacity: 0; transform: translateX(-20px); }
        100% { opacity: 1; transform: translateX(0); }
    }

    .expandable-section {
        animation: sectionSlideIn 0.5s ease-out;
    }

    .expandable-section:nth-child(2) { animation-delay: 0.2s; }
    .expandable-section:nth-child(3) { animation-delay: 0.4s; }

    /* Responsive improvements */
    @media (max-width: 768px) {
        .wvp-interactive-health-score {
            padding: 20px;
        }

        .score-circle {
            width: 120px;
            height: 120px;
        }

        .score-progress {
            width: 120px;
            height: 120px;
        }

        .score-number {
            font-size: 1.8em;
        }

        .section-actions {
            flex-direction: column;
            gap: 8px;
        }

        .action-btn {
            width: 100%;
            justify-content: center;
        }
    }

    /* Hide sidebars for all pages */
    body.page .sidebar,
    body.single .sidebar {
        display: none !important;
    }

    body.page #content,
    body.single #content {
        width: 100% !important;
        max-width: none !important;
    }

    body.page .main-page-wrapper,
    body.single .main-page-wrapper {
        max-width: none !important;
    }

    body.page .site-content,
    body.single .site-content {
        max-width: none !important;
        width: 100% !important;
    }

    body.page .col-lg-9,
    body.single .col-lg-9 {
        flex: 0 0 100% !important;
        max-width: 100% !important;
    }

    body.page .content-area,
    body.single .content-area {
        width: 100% !important;
        flex: 0 0 100% !important;
        max-width: 100% !important;
    }

    /* Comprehensive sidebar removal for completed page */
    body.page .sidebar,
    body.single .sidebar,
    body.page .widget-area,
    body.single .widget-area,
    body.page #secondary,
    body.single #secondary,
    body.page .secondary,
    body.single .secondary,
    body.page .aside,
    body.single .aside,
    body.page #aside,
    body.single #aside,
    body.page .sidebar-primary,
    body.single .sidebar-primary,
    body.page .sidebar-secondary,
    body.single .sidebar-secondary,
    body.page .right-sidebar,
    body.single .right-sidebar,
    body.page .left-sidebar,
    body.single .left-sidebar,
    body.page .col-md-3,
    body.single .col-md-3,
    body.page .col-lg-3,
    body.single .col-lg-3,
    body.page .col-xl-3,
    body.single .col-xl-3,
    body.page .widget_area,
    body.single .widget_area {
        display: none !important;
        width: 0 !important;
        visibility: hidden !important;
    }

    </style>
    <?php

    return ob_get_clean();
}

// Step generation functions for PHP-only navigation
function wvp_generate_step_1() {
    ob_start();
    ?>
    <div id="wvp-health-quiz" style="background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <div class="wvp-ai-health-header">
            <div class="ai-health-title">
                <h1>
                    <span class="title-main">Analiza Zdravstvenog Stanja</span>
                    <span class="title-subtitle">AI-powered Natural Health Assessment</span>
                </h1>
            </div>
            <div class="health-subtitle">
                <p>Odgovorite na jednostavna pitanja i dobijte besplatnu analizu, personalizovane savete i prirodnu terapiju za rešavanje uzroka problema.</p>
            </div>
            <div class="health-icons">
                <div class="health-icon" title="Digestivno zdravlje">🦠</div>
                <div class="health-icon" title="Energija i vitalnost">⚡</div>
                <div class="health-icon" title="Imuni sistem">🛡️</div>
                <div class="health-icon" title="Zdravlje zglobova">🦴</div>
            </div>
        </div>

        <div class="wvp-health-form-section">
            <h2 class="form-section-title">Osnovne informacije</h2>

            <form method="post" action="">
                <?php wp_nonce_field('wvp_quiz_navigation', 'wvp_nonce'); ?>
                <input type="hidden" name="wvp_action" value="start_quiz">

                <div class="health-form-grid">
                    <div class="form-group">
                        <label for="first_name">Ime *</label>
                        <input type="text" id="first_name" name="first_name" required class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="last_name">Prezime *</label>
                        <input type="text" id="last_name" name="last_name" required class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="email">Email adresa *</label>
                        <input type="email" id="email" name="email" required class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="phone">Telefon *</label>
                        <input type="tel" id="phone" name="phone" required class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="birth_year">Godina rođenja *</label>
                        <select id="birth_year" name="birth_year" required class="form-control">
                            <option value="">Izaberite godinu</option>
                            <?php
                            $current_year = date('Y');
                            for ($year = $current_year; $year >= $current_year - 100; $year--) {
                                echo '<option value="' . $year . '">' . $year . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="location">Grad</label>
                        <input type="text" id="location" name="location" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="country">Zemlja *</label>
                        <select id="country" name="country" required class="form-control">
                            <option value="">Izaberite zemlju</option>
                            <option value="Srbija">Srbija</option>
                            <option value="Crna Gora">Crna Gora</option>
                            <option value="Bosna i Hercegovina">Bosna i Hercegovina</option>
                            <option value="Hrvatska">Hrvatska</option>
                            <option value="Slovenija">Slovenija</option>
                            <option value="Makedonija">Makedonija</option>
                            <option value="Njemačka">Njemačka</option>
                            <option value="Austrija">Austrija</option>
                            <option value="Švajcarska">Švajcarska</option>
                            <option value="Ostalo">Ostalo</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions" style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="wvp-health-next">Započni analizu →</button>
                </div>
            </form>
        </div>

        <div class="health-quiz-benefits">
            <div class="benefit-item">
                <div class="benefit-icon">🔬</div>
                <div class="benefit-text">
                    <h3>AI Analiza</h3>
                    <p>Napredna analiza na bazi vaših odgovora</p>
                </div>
            </div>
            <div class="benefit-item">
                <div class="benefit-icon">🌿</div>
                <div class="benefit-text">
                    <h3>Prirodne preporuke</h3>
                    <p>Personalizovane preporuke za prirodno lečenje</p>
                </div>
            </div>
            <div class="benefit-item">
                <div class="benefit-icon">📊</div>
                <div class="benefit-text">
                    <h3>Detaljni izveštaj</h3>
                    <p>Kompletna analiza vašeg zdravstvenog stanja</p>
                </div>
            </div>
        </div>
    </div>

    <style>
    .health-form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        font-weight: 600;
        margin-bottom: 8px;
        color: #2c3e50;
    }

    .form-control {
        padding: 12px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 16px;
        transition: border-color 0.3s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: #007cba;
        box-shadow: 0 0 0 3px rgba(0, 123, 186, 0.1);
    }

    .wvp-health-next {
        background: linear-gradient(135deg, #007cba 0%, #005177 100%);
        color: white;
        border: none;
        padding: 15px 40px;
        border-radius: 8px;
        font-size: 18px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .wvp-health-next:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 123, 186, 0.3);
    }

    .health-quiz-benefits {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 40px;
    }

    .benefit-item {
        display: flex;
        align-items: center;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #007cba;
    }

    .benefit-icon {
        font-size: 2em;
        margin-right: 15px;
    }

    .benefit-text h3 {
        margin: 0 0 5px 0;
        color: #2c3e50;
        font-size: 1.1em;
    }

    .benefit-text p {
        margin: 0;
        color: #6c757d;
        font-size: 0.9em;
    }

    .ai-health-title h1 {
        text-align: center;
        margin-bottom: 20px;
    }

    .title-main {
        display: block;
        font-size: 2.5em;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 10px;
    }

    .title-subtitle {
        display: block;
        font-size: 1em;
        font-weight: 400;
        color: #6c757d;
        font-style: italic;
    }

    .health-subtitle {
        text-align: center;
        margin-bottom: 30px;
    }

    .health-subtitle p {
        font-size: 1.1em;
        color: #495057;
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.6;
    }

    .health-icons {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-bottom: 40px;
    }

    .health-icon {
        font-size: 3em;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 50%;
        transition: transform 0.3s ease;
    }

    .health-icon:hover {
        transform: scale(1.1);
    }

    .form-section-title {
        font-size: 1.8em;
        color: #2c3e50;
        text-align: center;
        margin-bottom: 30px;
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .health-form-grid {
            grid-template-columns: 1fr;
        }

        .health-icons {
            gap: 10px;
        }

        .health-icon {
            font-size: 2em;
            padding: 10px;
        }

        .title-main {
            font-size: 2em;
        }
    }
    </style>
    <?php
    return ob_get_clean();
}

function wvp_generate_step_2() {
    // Get questions from options
    $default_questions = array(
        array(
            'text'    => 'Da li vam je ikada dijagnostifikovana autoimuna bolest (npr. Hashimoto, reumatoidni artritis, lupus)?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('Pre više godina', 'Pre godinu-dve', 'Nedavno dijagnostikovano'),
            'intensity_text' => 'Kada je dijagnostikovano:',
            'ai_daily_dose' => '2',
            'ai_monthly_box' => '2'
        ),
        array(
            'text'    => 'Imate li problema sa varenjem – nadutost, gasovi, zatvor ili iritabilno crevo?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('Povremeno', 'Često (2-3 puta nedeljno)', 'Svakodnevno'),
            'intensity_text' => 'Koliko često:',
            'ai_daily_dose' => '2',
            'ai_monthly_box' => '2'
        ),
        array(
            'text'    => 'Da li su vam lekari nekada rekli da imate povišen holesterol ili trigliceride?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('Blago povišen', 'Umerno povišen', 'Visok'),
            'intensity_text' => 'Nivo povišenosti:',
            'ai_daily_dose' => '2',
            'ai_monthly_box' => '2'
        ),
        array(
            'text'    => 'Da li osećate česte padove energije, hroničan umor ili iscrpljenost?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('Povremeno', 'Često (nekoliko puta nedeljno)', 'Konstantno'),
            'intensity_text' => 'Koliko često:',
            'ai_daily_dose' => '1 spelta + 1 ječam',
            'ai_monthly_box' => '1 spelta + 1 ječam'
        ),
        array(
            'text'    => 'Jeste li nekada imali anemiju ili niske vrednosti gvožđa u krvi?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('Pre više godina', 'U poslednje 2 godine', 'Trenutno imam'),
            'intensity_text' => 'Kada je dijagnostikovano:',
            'ai_daily_dose' => '2',
            'ai_monthly_box' => '2'
        ),
        array(
            'text'    => 'Imate li problema sa kostima i zglobovima?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('Blagi bolovi', 'Umerni bolovi', 'Jaki bolovi'),
            'intensity_text' => 'Intenzitet bola:',
            'ai_daily_dose' => '2',
            'ai_monthly_box' => '2'
        ),
        array(
            'text'    => 'Da li vam je dijagnostikovan povišen krvni pritisak ili problemi sa srcem i krvnim sudovima?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('Blago povišen', 'Umeren', 'Ozbiljan problem'),
            'intensity_text' => 'Ozbiljnost problema:',
            'ai_daily_dose' => '2',
            'ai_monthly_box' => '2'
        ),
        array(
            'text'    => 'Koliko često u toku godine imate prehlade, viruse ili infekcije?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('1-2 puta godišnje', '3-5 puta godišnje', 'Više od 6 puta godišnje'),
            'intensity_text' => 'Učestalost:',
            'ai_daily_dose' => '2',
            'ai_monthly_box' => '2'
        ),
        array(
            'text'    => 'Da li su vam dijagnostikovane ciste, miomi ili polipi?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('Manje promene', 'Umeren broj', 'Veliki broj ili veličina'),
            'intensity_text' => 'Veličina/broj:',
            'ai_daily_dose' => '3',
            'ai_monthly_box' => '3'
        ),
        array(
            'text'    => 'Da li imate dijabetes ili insulinsku rezistenciju?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('Predijabetest', 'Tip 2 dijabetes', 'Tip 1 dijabetes'),
            'intensity_text' => 'Tip dijabetesa:',
            'ai_daily_dose' => '1',
            'ai_monthly_box' => '1'
        ),
        array(
            'text'    => 'Imate li problema sa kožom – akne, ekcem, dermatitis, suvoća?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('Blagi problemi', 'Umeren intenzitet', 'Ozbiljni problemi'),
            'intensity_text' => 'Intenzitet problema:',
            'ai_daily_dose' => '2',
            'ai_monthly_box' => '2'
        ),
        array(
            'text'    => 'Da li ste u poslednje vreme primetili promene u apetitu ili telesnoj težini?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('Manje promene (1-3kg)', 'Umeren stepen (3-7kg)', 'Velike promene (više od 7kg)'),
            'intensity_text' => 'Stepen promene:',
            'ai_daily_dose' => '1 spelta + 1 ječam',
            'ai_monthly_box' => '1 spelta + 1 ječam'
        ),
        array(
            'text'    => 'Da li imate problem sa nesanicom, stresom ili nervozom?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('Povremeno', 'Često', 'Konstantno'),
            'intensity_text' => 'Učestalost:',
            'ai_daily_dose' => '2',
            'ai_monthly_box' => '2'
        ),
        array(
            'text'    => 'Da li spadate u grupu starijih od 60 godina i osećate da vam je potrebna dodatna vitalnost?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('60-65 godina', '65-75 godina', 'Preko 75 godina'),
            'intensity_text' => 'Uzrasna grupa:',
            'ai_daily_dose' => '1',
            'ai_monthly_box' => '1'
        ),
        array(
            'text'    => 'Imate li u porodici istoriju bolesti jetre ili bubrega (npr. masna jetra, kamen u bubregu)?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('Dalji srodnici', 'Bliži srodnici (roditelji, deca)', 'Više članova porodice'),
            'intensity_text' => 'Porodična istorija:',
            'ai_daily_dose' => '1 spelta + 1 ječam',
            'ai_monthly_box' => '1 spelta + 1 ječam'
        ),
        array(
            'text'    => 'Da li vam je ikada dijagnostifikovan kancer ili se trenutno lečite od njega?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('U remisiji', 'Trenutno na terapiji', 'Nedavno dijagnostikovan'),
            'intensity_text' => 'Status lečenja:',
            'ai_daily_dose' => '4',
            'ai_monthly_box' => '4'
        ),
        array(
            'text'    => 'Da li u vašoj porodici postoji genetska predispozicija za kancer (npr. rak dojke, debelog creva, prostate)?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('Dalji srodnici', 'Bliži srodnici', 'Više članova porodice'),
            'intensity_text' => 'Porodična istorija:',
            'ai_daily_dose' => '2',
            'ai_monthly_box' => '2'
        ),
        array(
            'text'    => 'Da li vam je ikada dijagnostikovana neka od bolesti jetre (masna jetra, hepatitis, ciroza)?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('Blaga masna jetra', 'Umeren problem', 'Ozbiljan problem'),
            'intensity_text' => 'Ozbiljnost:',
            'ai_daily_dose' => '1 spelta + 2 ječam',
            'ai_monthly_box' => '1 spelta + 2 ječam'
        ),
        array(
            'text'    => 'Da li imate problem sa pamćenjem, koncentracijom ili mentalnom jasnoćom?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('Povremeno', 'Često', 'Konstantno'),
            'intensity_text' => 'Učestalost:',
            'ai_daily_dose' => '2',
            'ai_monthly_box' => '2'
        ),
        array(
            'text'    => 'Da li osećate potrebu za detoksikacijom organizma (npr. nakon terapija, loše ishrane, alkohola)?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('Povremeno', 'Redovno', 'Hitno potrebno'),
            'intensity_text' => 'Potreba za detoks:',
            'ai_daily_dose' => '1 spelta + 1 ječam',
            'ai_monthly_box' => '1 spelta + 1 ječam'
        ),
        array(
            'text'    => 'Da li ste podložni alergijama (polenska, respiratorna, alergija na hranu)?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('Sezonske alergije', 'Višestruke alergije', 'Ozbiljne alergijske reakcije'),
            'intensity_text' => 'Tip alergije:',
            'ai_daily_dose' => '2',
            'ai_monthly_box' => '2'
        ),
        array(
            'text'    => 'Da li osećate iscrpljenost usled posla, treninga ili stresa i želeli biste prirodan način da povratite snagu?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('Blagu iscrpljenost', 'Umeren umor', 'Potpuna iscrpljenost'),
            'intensity_text' => 'Nivo iscrpljenosti:',
            'ai_daily_dose' => '1',
            'ai_monthly_box' => '2'
        ),
        array(
            'text'    => 'Da li želite da poboljšate imunitet svoje dece ili starijih članova porodice?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('Za decu', 'Za odrasle', 'Za starije osobe'),
            'intensity_text' => 'Za koga:',
            'ai_daily_dose' => '2',
            'ai_monthly_box' => '2'
        ),
        array(
            'text'    => 'Da li imate problema sa mentalnim zdravljem – depresijom, anksioznošću ili paničnim napadima?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('Blagi simptomi', 'Umeren intenzitet', 'Ozbiljni simptomi'),
            'intensity_text' => 'Intenzitet simptoma:',
            'ai_daily_dose' => '2',
            'ai_monthly_box' => '2'
        ),
        array(
            'text'    => 'Da li vam je ikada dijagnostifikovana kandida ili heliko bakterija?',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => '',
            'intensity_levels' => array('Pre više godina', 'U poslednje 2 godine', 'Nedavno/trenutno'),
            'intensity_text' => 'Kada je dijagnostikovano:',
            'ai_daily_dose' => '1',
            'ai_monthly_box' => '1'
        ),
    );

    $questions = get_option( 'wvp_health_quiz_questions', $default_questions );
    $per_page = intval( get_option( 'wvp_health_quiz_questions_per_page', 3 ) );
    if ( $per_page < 1 ) $per_page = 1;

    // Get current page from URL step
    $url_step = get_query_var('wvp_quiz_step', '1');
    $current_page = intval($url_step) - 1; // Convert to 0-based index for array access
    if ($current_page < 0) $current_page = 0;

    $question_pages = array_chunk( $questions, $per_page );
    $total_pages = count($question_pages);


    // Clamp display_page to valid range
    $display_page = max(1, min(intval($url_step), $total_pages));

    // Make sure we don't exceed available pages
    if ($current_page >= $total_pages) {
        $current_page = $total_pages - 1;
    }

    $current_questions = $question_pages[$current_page] ?? array();
    $is_last_page = ($current_page >= $total_pages - 1);
    $next_page = $current_page + 2; // Convert back to 1-based

    ob_start();
    ?>
    <div class="wvp-single-package-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-10 col-xl-8 mx-auto">

                    <!-- Breadcrumbs -->
                    <nav class="wvp-breadcrumbs" aria-label="Breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="<?php echo home_url(); ?>">Početna</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="<?php echo home_url('/analiza-zdravstvenog-stanja/'); ?>">Analiza zdravstvenog stanja</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Pitanja - Korak <?php echo $display_page; ?></li>
                        </ol>
                    </nav>

                    <div id="wvp-health-quiz">
                        <div class="wvp-ai-form-section">
                            <div class="form-header-card">
                                <div class="form-step-indicator">
                                    <span class="step-number"><?php echo $display_page; ?></span>
                                    <span class="step-label">od <?php echo $total_pages; ?> koraka</span>
                                </div>
                                <h2 class="form-title">
                                    Zdravstvena anketa - Pitanja
                                </h2>
                                <p class="form-description">Odgovorite na pitanja da bismo mogli da analiziramo vaše zdravstveno stanje i damo personalizovane preporuke.</p>
                                <div class="progress-container">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php
                                            // Calculate progress: we are on question pages (after form)
                                            // Form step = 1, Questions pages = 1 to $total_pages, Results = $total_pages + 1
                                            $total_quiz_steps = $total_pages + 2; // form + questions + results
                                            $current_quiz_step = $display_page + 1; // +1 because form was step 1
                                            $progress_percent = ($current_quiz_step / $total_quiz_steps) * 100;
                                            echo round($progress_percent);
                                        ?>%"></div>
                                    </div>
                                    <span class="progress-text"><?php
                                        // Calculate progress based on total quiz steps (including form step)
                                        $total_quiz_steps = $total_pages + 1; // +1 for initial form step
                                        $current_quiz_step = $display_page + 1; // +1 because form was step 1
                                        $progress_percent = min(100, ($current_quiz_step / $total_quiz_steps) * 100);
                                        echo round($progress_percent);
                                    ?>% završeno</span>
                                </div>
                            </div>

        <form method="post" action="" class="quiz-questions-form">
            <?php wp_nonce_field('wvp_quiz_navigation', 'wvp_nonce'); ?>
            <input type="hidden" name="wvp_action" value="<?php echo $is_last_page ? 'complete_quiz' : 'next_questions'; ?>">
            <input type="hidden" name="current_page" value="<?php echo $current_page; ?>">

            <?php if (!empty($current_questions)): ?>
                <?php foreach ($current_questions as $q_index => $question): ?>
                    <?php
                    $global_index = ($current_page * $per_page) + $q_index;
                    $question_id = 'question_' . $global_index;
                    ?>
                    <div class="ai-question-card" data-question="<?php echo $global_index; ?>">
                        <div class="question-header">
                            <h3 class="question-text"><?php echo esc_html($question['text']); ?></h3>
                        </div>

                        <div class="answer-options">
                            <?php foreach ($question['answers'] as $answer_index => $answer): ?>
                                <label class="answer-option">
                                    <input type="radio"
                                           name="answers[<?php echo $global_index; ?>]"
                                           value="<?php echo esc_attr($answer); ?>"
                                           id="<?php echo $question_id . '_' . $answer_index; ?>"
                                           onchange="toggleIntensity(<?php echo $global_index; ?>, '<?php echo esc_js($answer); ?>')">
                                    <span class="radio-custom"></span>
                                    <span class="answer-text"><?php echo esc_html($answer); ?></span>
                                    <span class="answer-checkmark">✓</span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="ai-intensity-section" id="intensity_<?php echo $global_index; ?>" style="display: none;">
                            <div class="intensity-header">
                                <label class="intensity-label">
                                    <?php echo esc_html($question['intensity_text'] ?? 'Koliko intenzivno:'); ?>
                                </label>
                            </div>
                            <div class="intensity-options">
                                <?php foreach ($question['intensity_levels'] as $level_index => $level): ?>
                                    <label class="intensity-option">
                                        <input type="radio"
                                               name="intensity[<?php echo $global_index; ?>]"
                                               value="<?php echo esc_attr($level); ?>"
                                               id="intensity_<?php echo $global_index . '_' . $level_index; ?>">
                                        <span class="intensity-custom"></span>
                                        <span class="intensity-text"><?php echo esc_html($level); ?></span>
                                        <span class="intensity-indicator"></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="ai-form-navigation">
                <?php if ($current_page > 0): ?>
                    <a href="<?php echo home_url('/analiza-zdravstvenog-stanja/pitanja' . $current_page . '/'); ?>"
                       class="ai-nav-btn ai-prev-btn">
                        <span class="nav-icon">←</span>
                        <span class="nav-text">Nazad</span>
                    </a>
                <?php endif; ?>

                <button type="submit" class="ai-nav-btn ai-next-btn" onclick="return validateForm()">
                    <span class="nav-text"><?php echo $is_last_page ? 'Završi anketu' : 'Sledeće pitanje'; ?></span>
                    <span class="nav-icon">→</span>
                </button>
            </div>
        </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    /* Modern Breadcrumbs Design */
    .wvp-breadcrumbs {
        margin-bottom: 30px;
        padding: 0;
    }

    .breadcrumb {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border: 1px solid rgba(0, 88, 64, 0.1);
        border-radius: 16px;
        padding: 20px 30px;
        margin: 0;
        box-shadow: 0 2px 12px rgba(0, 88, 64, 0.08);
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }

    .breadcrumb-item {
        display: inline-flex;
        align-items: center;
        font-weight: 500;
        font-size: 0.95em;
    }

    .breadcrumb-item + .breadcrumb-item:before {
        content: "→";
        padding: 0 12px;
        color: rgba(0, 88, 64, 0.6);
        font-weight: 600;
        font-size: 1.1em;
    }

    .breadcrumb-item a {
        color: rgba(0, 88, 64, 0.8);
        text-decoration: none;
        padding: 8px 12px;
        border-radius: 8px;
        transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
        font-weight: 500;
    }

    .breadcrumb-item a:hover {
        background: rgba(0, 88, 64, 0.1);
        color: rgba(0, 88, 64, 1);
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 88, 64, 0.2);
    }

    .breadcrumb-item.active {
        color: rgba(0, 88, 64, 1);
        font-weight: 600;
        background: rgba(0, 88, 64, 0.12);
        padding: 8px 12px;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0, 88, 64, 0.15);
    }

    /* Enhanced Form Header for Questions Page */
    .form-header-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border: 1px solid rgba(0, 88, 64, 0.1);
        border-radius: 20px;
        padding: 40px;
        margin-bottom: 30px;
        text-align: center;
        box-shadow: 0 4px 20px rgba(0, 88, 64, 0.08);
        position: relative;
        overflow: hidden;
    }

    .form-header-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #005840 0%, #007F5B 50%, #005840 100%);
    }

    .form-step-indicator {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        margin-bottom: 20px;
    }

    .step-number {
        background: linear-gradient(135deg, #005840 0%, #007F5B 100%);
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.2em;
        box-shadow: 0 4px 12px rgba(0, 88, 64, 0.3);
    }

    .step-label {
        color: rgba(0, 88, 64, 0.8);
        font-weight: 600;
        font-size: 1.1em;
        letter-spacing: 0.5px;
    }

    .form-title {
        margin: 0 0 15px 0;
        font-size: 2.2em;
        font-weight: 700;
        color: rgba(0, 88, 64, 1);
        text-shadow: 0 2px 4px rgba(0, 88, 64, 0.1);
        letter-spacing: -0.5px;
    }

    .form-description {
        color: #6c757d;
        font-size: 1.1em;
        line-height: 1.6;
        margin: 0 0 20px 0;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    /* Enhanced Progress Styling */
    .progress-container {
        margin: 30px 0;
        text-align: center;
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        padding: 25px;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 88, 64, 0.1);
        border: 1px solid rgba(0, 88, 64, 0.1);
    }

    .progress-bar {
        width: 100%;
        height: 16px;
        background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
        border-radius: 25px;
        overflow: hidden;
        box-shadow: inset 0 3px 6px rgba(0,0,0,0.15);
        margin-bottom: 15px;
        position: relative;
        border: 2px solid rgba(0, 88, 64, 0.1);
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(135deg, #005840 0%, #007F5B 50%, #005840 100%);
        transition: width 1s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        position: relative;
        border-radius: 25px;
        box-shadow: 0 2px 8px rgba(0, 88, 64, 0.3);
    }

    .progress-fill::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        animation: progressShimmer 3s infinite ease-in-out;
        border-radius: 25px;
    }

    @keyframes progressShimmer {
        0% { transform: translateX(-150%); opacity: 0; }
        50% { opacity: 1; }
        100% { transform: translateX(150%); opacity: 0; }
    }

    .progress-text {
        font-size: 1em;
        color: rgba(0, 88, 64, 0.8);
        font-weight: 700;
        text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        letter-spacing: 0.5px;
    }

    /* Beautiful Question Cards */
    .ai-question-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 88, 64, 0.1);
        transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .ai-question-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #005840 0%, #005840 50%, #005840 100%);
    }

    .ai-question-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 48px rgba(0, 88, 64, 0.15);
        border-color: rgba(0, 88, 64, 0.3);
    }

    .question-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 25px;
    }

    .question-icon {
        font-size: 1.5em;
        background: linear-gradient(135deg, #005840 0%, #005840 100%);
        padding: 10px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        filter: grayscale(100%);
        transition: all 0.3s ease;
    }

    .ai-question-card:hover .question-icon {
        filter: grayscale(0%);
        transform: scale(1.1);
    }

    .question-text {
        margin: 0;
        font-size: 1.3em;
        font-weight: 600;
        color: #2c3e50;
        line-height: 1.4;
        flex: 1;
    }

    .question-text {
        font-size: 1.3em;
        color: #2c3e50;
        margin-bottom: 20px;
        font-weight: 600;
    }

    /* Beautiful Answer Options */
    .answer-options {
        display: flex;
        flex-direction: row;
        gap: 15px;
        margin-bottom: 25px;
        justify-content: center;
        width: 100%;
    }

    .answer-option {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
        padding: 18px 20px;
        background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
        border: 2px solid #e9ecef;
        border-radius: 15px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
        position: relative;
        overflow: hidden;
        flex: 1;
        min-height: 60px;
        font-size: 1.1em;
        font-weight: 600;
    }

    .answer-option::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(0, 88, 64, 0.1), transparent);
        transition: left 0.5s;
    }

    .answer-option:hover {
        border-color: #005840;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 88, 64, 0.15);
    }

    .answer-option:hover::before {
        left: 100%;
    }

    .answer-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        cursor: pointer;
    }

    .radio-custom {
        width: 22px;
        height: 22px;
        border: 3px solid #dee2e6;
        border-radius: 50%;
        background: #fff;
        transition: all 0.3s ease;
        position: relative;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .answer-option input[type="radio"]:checked + .radio-custom {
        border-color: #005840;
        background: linear-gradient(135deg, #005840 0%, #005840 100%);
        transform: scale(1.1);
    }

    .answer-option input[type="radio"]:checked + .radio-custom::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 8px;
        height: 8px;
        background: white;
        border-radius: 50%;
        animation: radioScale 0.3s ease;
    }

    @keyframes radioScale {
        0% { transform: translate(-50%, -50%) scale(0); }
        100% { transform: translate(-50%, -50%) scale(1); }
    }

    .answer-text {
        font-size: 1.1em;
        color: #495057;
        font-weight: 500;
        flex: 1;
        transition: color 0.3s ease;
    }

    .answer-option input[type="radio"]:checked ~ .answer-text {
        color: #2c3e50;
        font-weight: 600;
    }

    .answer-checkmark {
        opacity: 0;
        color: #005840;
        font-size: 1.2em;
        font-weight: bold;
        transition: all 0.3s ease;
        transform: scale(0);
    }

    .answer-option input[type="radio"]:checked ~ .answer-checkmark {
        opacity: 1;
        transform: scale(1);
        animation: checkmarkBounce 0.4s ease;
    }

    @keyframes checkmarkBounce {
        0%, 20% { transform: scale(0); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }

    /* Beautiful Intensity Section */
    .ai-intensity-section {
        margin-top: 25px;
        padding: 25px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 15px;
        border-left: 4px solid #005840;
        animation: intensitySlideIn 0.5s ease;
    }

    @keyframes intensitySlideIn {
        0% { opacity: 0; transform: translateY(-10px); }
        100% { opacity: 1; transform: translateY(0); }
    }

    .intensity-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }

    .intensity-icon {
        font-size: 1.3em;
        background: linear-gradient(135deg, #005840 0%, #005840 100%);
        padding: 8px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .intensity-label {
        font-weight: 600;
        color: #2c3e50;
        font-size: 1.1em;
        margin: 0;
    }

    .intensity-options {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        justify-content: center;
    }

    .intensity-option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 18px;
        background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
        border: 2px solid #dee2e6;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
        position: relative;
        min-width: 120px;
        justify-content: center;
    }

    .intensity-option:hover {
        border-color: #005840;
        background: linear-gradient(135deg, #fff5f0 0%, #ffe8d6 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 88, 64, 0.2);
    }

    .intensity-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        cursor: pointer;
    }

    .intensity-custom {
        width: 18px;
        height: 18px;
        border: 2px solid #dee2e6;
        border-radius: 50%;
        background: #fff;
        transition: all 0.3s ease;
        position: relative;
        flex-shrink: 0;
    }

    .intensity-option input[type="radio"]:checked + .intensity-custom {
        border-color: #005840;
        background: linear-gradient(135deg, #005840 0%, #005840 100%);
        transform: scale(1.1);
    }

    .intensity-option input[type="radio"]:checked + .intensity-custom::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 6px;
        height: 6px;
        background: white;
        border-radius: 50%;
    }

    .intensity-text {
        font-size: 0.95em;
        color: #495057;
        font-weight: 500;
    }

    /* Beautiful Navigation */
    .ai-form-navigation {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 40px;
        padding: 30px 0;
        border-top: 1px solid rgba(0, 88, 64, 0.2);
        position: relative;
    }

    .ai-form-navigation::before {
        content: '';
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 3px;
        background: linear-gradient(135deg, #005840 0%, #005840 100%);
        border-radius: 2px;
    }

    .ai-nav-btn {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 16px 32px;
        border: none;
        border-radius: 50px;
        font-size: 1.1em;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
        position: relative;
        overflow: hidden;
        text-align: center;
        min-width: 140px;
    }

    .ai-prev-btn {
        background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
    }

    .ai-prev-btn:hover {
        background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
        color: white;
        text-decoration: none;
    }

    .ai-next-btn {
        background: linear-gradient(135deg, #005840 0%, #005840 50%, #005840 100%);
        color: white;
        box-shadow: 0 6px 20px rgba(0, 88, 64, 0.4);
    }

    .ai-next-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.6s;
    }

    .ai-next-btn:hover {
        background: linear-gradient(135deg, #2d5a3d 0%, #2d5a3d 50%, #2d5a3d 100%);
        transform: translateY(-3px);
        box-shadow: 0 12px 35px rgba(0, 88, 64, 0.5);
    }

    .ai-next-btn:hover::before {
        left: 100%;
    }

    .nav-icon {
        font-size: 1.2em;
        transition: transform 0.3s ease;
    }

    .ai-prev-btn:hover .nav-icon {
        transform: translateX(-3px);
    }

    .ai-next-btn:hover .nav-icon {
        transform: translateX(3px);
    }

    .nav-text {
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .answer-options {
            flex-direction: column;
            gap: 12px;
        }

        .answer-option {
            flex: none;
            width: 100%;
            min-height: 50px;
            font-size: 1em;
        }

        .intensity-options {
            flex-direction: column;
        }

        .intensity-option {
            min-width: auto;
            width: 100%;
        }

        .form-navigation {
            flex-direction: column;
            gap: 15px;
        }

        .wvp-health-prev,
        .wvp-health-next {
            width: 100%;
            text-align: center;
        }
    }
    </style>

    <script>
    // OPTIMIZED AUTO-SAVE SYSTEM FOR QUESTION PAGES
    let autoSaveTimeout;
    let lastSaveTime = 0;
    const AUTO_SAVE_DELAY = 500;
    const MIN_SAVE_INTERVAL = 2000;

    function toggleIntensity(questionIndex, answer) {
        const intensitySection = document.getElementById('intensity_' + questionIndex);
        if (intensitySection) {
            if (answer.toLowerCase() === 'da') {
                intensitySection.style.display = 'block';
            } else {
                intensitySection.style.display = 'none';
                // Clear intensity selection when hiding
                const intensityInputs = intensitySection.querySelectorAll('input[type="radio"]');
                intensityInputs.forEach(input => input.checked = false);
            }
        }

        // Trigger auto-save after intensity toggle
        triggerAutoSave();
    }

    function triggerAutoSave(immediate = false) {
        if (autoSaveTimeout) {
            clearTimeout(autoSaveTimeout);
        }

        const now = Date.now();
        if (immediate || (now - lastSaveTime) >= MIN_SAVE_INTERVAL) {
            autoSaveToDatabase();
            lastSaveTime = now;
        } else {
            autoSaveTimeout = setTimeout(() => {
                autoSaveToDatabase();
                lastSaveTime = Date.now();
            }, AUTO_SAVE_DELAY);
        }
    }

    function autoSaveToDatabase() {
        console.log('🔄 Auto-save: Starting save process...');

        // Get form data from question page
        const firstName = document.querySelector('input[name="first_name"]')?.value?.trim() || '';
        const lastName = document.querySelector('input[name="last_name"]')?.value?.trim() || '';
        const email = document.querySelector('input[name="email"]')?.value?.trim() || '';
        const phone = document.querySelector('input[name="phone"]')?.value?.trim() || '';
        const birthYear = document.querySelector('input[name="birth_year"]')?.value || '';
        const location = document.querySelector('input[name="location"]')?.value?.trim() || '';
        const country = document.querySelector('input[name="country"]')?.value?.trim() || '';

        // Get current answers from radio buttons (question page format)
        const currentAnswers = {};
        const currentIntensities = {};

        // Get answers from ai-question-card format
        document.querySelectorAll('.ai-question-card input[type="radio"]:checked').forEach(radio => {
            if (radio.name.includes('answers[')) {
                const match = radio.name.match(/answers\[(\d+)\]/);
                if (match) {
                    const questionIndex = match[1];
                    currentAnswers[questionIndex] = radio.value;
                    console.log('📻 Found answer:', questionIndex, '=', radio.value);
                }
            }
            if (radio.name.includes('intensity[')) {
                const match = radio.name.match(/intensity\[(\d+)\]/);
                if (match) {
                    const questionIndex = match[1];
                    currentIntensities[questionIndex] = radio.value;
                    console.log('📊 Found intensity:', questionIndex, '=', radio.value);
                }
            }
        });

        // Decide if we should save
        const hasFormData = firstName && lastName;
        const hasQuizData = Object.keys(currentAnswers).length > 0;

        if (!hasFormData && !hasQuizData) {
            console.log('⏭️ Auto-save: No data to save');
            return;
        }

        console.log('💾 Auto-save: Preparing data...', {
            formData: hasFormData,
            questionsAnswered: Object.keys(currentAnswers).length,
            intensitiesSet: Object.keys(currentIntensities).length,
            currentAnswers: currentAnswers,
            currentIntensities: currentIntensities
        });

        // Use the same format as main JavaScript
        const formData = new FormData();
        formData.append('action', 'bulletproof_save_answers');
        formData.append('nonce', '<?php echo wp_create_nonce('wvp_health_quiz_nonce'); ?>');
        formData.append('first_name', firstName);
        formData.append('last_name', lastName);
        formData.append('email', email);
        formData.append('phone', phone);
        formData.append('birth_year', birthYear);
        formData.append('location', location);
        formData.append('country', country);

        // Send answers and intensities in JSON format (same as main JS)
        formData.append('answers_data', JSON.stringify(currentAnswers));
        formData.append('intensities_data', JSON.stringify(currentIntensities));
        formData.append('auto_save', '1');

        // Get session ID from localStorage (if available)
        const sessionId = localStorage.getItem('wvp_health_quiz_session_id') || '';
        if (sessionId) {
            formData.append('session_id', sessionId);
        }

        showSaveIndicator('saving');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('✅ Auto-save: Success', data);

            // Store session_id and result_id for future requests
            if (data.data && data.data.session_id) {
                localStorage.setItem('wvp_health_quiz_session_id', data.data.session_id);
            }
            if (data.data && data.data.result_id) {
                localStorage.setItem('wvp_health_quiz_result_id', data.data.result_id);
            }

            showSaveIndicator('success');
        })
        .catch(error => {
            console.log('❌ Auto-save: Error', error);
            showSaveIndicator('error');
        });
    }

    function showSaveIndicator(status) {
        let indicator = document.querySelector('.wvp-save-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'wvp-save-indicator';
            indicator.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 10px 20px;
                border-radius: 8px;
                font-weight: bold;
                z-index: 10000;
                transition: all 0.3s ease;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            `;
            document.body.appendChild(indicator);
        }

        switch(status) {
            case 'saving':
                indicator.textContent = '💾 Čuva se...';
                indicator.style.background = '#ffc107';
                indicator.style.color = '#856404';
                break;
            case 'success':
                indicator.textContent = '✅ Sačuvano';
                indicator.style.background = '#28a745';
                indicator.style.color = 'white';
                setTimeout(() => {
                    indicator.style.opacity = '0';
                    setTimeout(() => {
                        indicator.style.opacity = '1';
                    }, 2000);
                }, 2000);
                break;
            case 'error':
                indicator.textContent = '❌ Greška';
                indicator.style.background = '#dc3545';
                indicator.style.color = 'white';
                break;
        }
    }

    function setupAutoSaveListeners() {
        console.log('🎯 Setting up auto-save listeners for question page...');

        // Listen for radio button changes (answers and intensity)
        document.addEventListener('change', function(e) {
            if (e.target.type === 'radio' && (
                e.target.name.includes('answers[') ||
                e.target.name.includes('intensity[')
            )) {
                console.log('📻 Radio button changed:', e.target.name, '=', e.target.value);
                triggerAutoSave();
            }
        });

        // Listen for text input changes
        document.addEventListener('input', function(e) {
            if (e.target.type === 'text' || e.target.type === 'email' || e.target.type === 'tel') {
                console.log('📝 Text input changed:', e.target.name);
                triggerAutoSave();
            }
        });

        console.log('✅ Auto-save listeners ready for question page');
    }

    function validateForm() {
        // Find all questions
        const questions = document.querySelectorAll('.ai-question-card');

        for (let question of questions) {
            const questionIndex = question.getAttribute('data-question');

            // Check if "Da" is selected
            const daSelected = question.querySelector('input[name="answers[' + questionIndex + ']"][value="Da"]:checked');

            if (daSelected) {
                // Check if intensity is selected
                const intensitySelected = question.querySelector('input[name="intensity[' + questionIndex + ']"]:checked');

                if (!intensitySelected) {
                    // Show error message
                    alert('Molimo vas da odgovorite na pitanje o intenzitetu za svaki "Da" odgovor.');

                    // Scroll to the question
                    question.scrollIntoView({ behavior: 'smooth', block: 'center' });

                    // Highlight the intensity section
                    const intensitySection = question.querySelector('.ai-intensity-section');
                    if (intensitySection) {
                        intensitySection.style.border = '2px solid #dc3545';
                        intensitySection.style.borderRadius = '8px';
                        intensitySection.style.padding = '10px';

                        // Remove highlight after 3 seconds
                        setTimeout(() => {
                            intensitySection.style.border = '';
                            intensitySection.style.borderRadius = '';
                            intensitySection.style.padding = '';
                        }, 3000);
                    }

                    return false; // Prevent form submission
                }
            }
        }

        return true; // Allow form submission
    }

    // Initialize session ID for consistency with main page
    function initializeQuestionPageSession() {
        let sessionId = localStorage.getItem('wvp_health_quiz_session_id');
        if (!sessionId) {
            // Generate new session ID (same format as main page)
            sessionId = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                const r = Math.random() * 16 | 0;
                const v = c == 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
            localStorage.setItem('wvp_health_quiz_session_id', sessionId);
            console.log('🔄 Question page: Generated new session ID:', sessionId);
        } else {
            console.log('🔄 Question page: Using existing session ID:', sessionId);
        }
        return sessionId;
    }

    // Initialize auto-save when page loads
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 Question page loaded - initializing auto-save...');

        // Initialize session before setting up listeners
        initializeQuestionPageSession();

        setupAutoSaveListeners();

        // Trigger initial save to establish session after a short delay
        setTimeout(() => {
            triggerAutoSave(true);
        }, 1500);
    });
    </script>
    <?php
    return ob_get_clean();
}

// Helper function to calculate health analysis
function wvp_calculate_health_analysis($questions, $answers, $intensities, $first_name, $age) {
    $problems = array();
    $da_count = 0;
    $total_intensity = 0;
    $max_possible_intensity = 0;

    // Analyze answers and build problems list
    foreach ($questions as $i => $question) {
        if (isset($answers[$i]) && $answers[$i] === 'Da') {
            $da_count++;
            $problem_text = $question['text'];

            // Add intensity info if available
            if (isset($intensities[$i]) && !empty($question['intensity_levels'])) {
                $intensity_idx = $intensities[$i] - 1;
                if (isset($question['intensity_levels'][$intensity_idx])) {
                    $intensity_level = $question['intensity_levels'][$intensity_idx];
                    $problem_text .= " (Intenzitet: " . $intensity_level . ")";
                    $total_intensity += $intensities[$i];
                }
            }
            $problems[] = $problem_text;
        }

        // Calculate max possible intensity for this question
        if (!empty($question['intensity_levels'])) {
            $max_possible_intensity += count($question['intensity_levels']);
        }
    }

    // Calculate health score (higher problems = lower score)
    $problem_ratio = count($questions) > 0 ? $da_count / count($questions) : 0;
    $intensity_ratio = $max_possible_intensity > 0 ? $total_intensity / $max_possible_intensity : 0;

    // Score calculation: start with 100, subtract for problems and intensity
    $score = 100 - ($problem_ratio * 50) - ($intensity_ratio * 30);
    $score = max(10, min(100, round($score))); // Keep between 10-100

    // Determine score color
    $score_color = '#dc3545'; // Red for poor
    if ($score >= 70) $score_color = '#28a745'; // Green for good
    elseif ($score >= 40) $score_color = '#ffc107'; // Yellow for moderate

    // Generate natural advice based on age and problems
    $advice = wvp_generate_natural_advice($problems, $age, $da_count);

    return array(
        'score' => $score,
        'score_color' => $score_color,
        'problems' => $problems,
        'natural_advice' => $advice,
        'da_count' => $da_count
    );
}

// Helper function to generate natural health advice
function wvp_generate_natural_advice($problems, $age, $problem_count) {
    $advice = array();

    // Age-specific advice
    if ($age < 30) {
        $advice[] = "U vašim godinama ({$age}) je važno uspostaviti zdrave navike koje će vam koristiti ceo život.";
    } elseif ($age > 50) {
        $advice[] = "Za vaš uzrast ({$age} godina) je ključno fokusirati se na održavanje vitalnosti i prevenciju bolesti.";
    } else {
        $advice[] = "U srednjim godinama ({$age}) je važno balansirati prevenciju sa aktivnim rešavanjem postojećih problema.";
    }

    // Problem-specific advice
    if ($problem_count >= 5) {
        $advice[] = "<strong>Opšti saveti za poboljšanje zdravlja:</strong>";
        $advice[] = "• Započnite dan sa čašom tople vode i limunom za detoksikaciju";
        $advice[] = "• Unesitte više zelenog povrća i sezonskog voća";
        $advice[] = "• Praktikujte duboko disanje 10 minuta dnevno";
        $advice[] = "• Spavajte 7-8 sati kvalitetnog sna";
        $advice[] = "• Ograničite prerađenu hranu i šećer";
    } elseif ($problem_count >= 2) {
        $advice[] = "<strong>Fokusirani saveti:</strong>";
        $advice[] = "• Dodajte više antioksidanasa u ishranu (borovnica, zeleni čaj)";
        $advice[] = "• Uvedite redovnu fizičku aktivnost (30 min dnevno)";
        $advice[] = "• Praktikujte tehnike upravljanja stresom";
    } else {
        $advice[] = "<strong>Saveti za održavanje dobrog zdravlja:</strong>";
        $advice[] = "• Nastavite sa zdravim navikama";
        $advice[] = "• Redovno proveravajte zdravlje";
        $advice[] = "• Održavajte balansiranu ishranu";
    }

    // General lifestyle advice
    $advice[] = "<strong>Preporučene prirodne navike:</strong>";
    $advice[] = "• Pijte najmanje 2L čiste vode dnevno";
    $advice[] = "• Izlazite na svež vazduh i sunce barem 20 minuta dnevno";
    $advice[] = "• Praktikujte zahvalnost i pozitivno razmišljanje";
    $advice[] = "• Ograničite vreme provedeno pred ekranima";

    return implode('<br>', $advice);
}

function wvp_generate_step_3() {
    // Check if we have the public analysis ID from URL
    $public_id = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';

    global $wpdb;
    $result = null;

    if (empty($public_id)) {
        // If no ID provided, try to find the most recent result or redirect appropriately

        // First, check if there's a result_id in URL parameters (from /zavrsena-anketa redirect)
        $url_result_id = isset($_GET['result_id']) ? intval($_GET['result_id']) : 0;

        if ($url_result_id > 0) {
            // Try to get public_analysis_id for this result
            $result_with_public_id = $wpdb->get_row($wpdb->prepare(
                "SELECT public_analysis_id FROM " . WVP_HEALTH_QUIZ_TABLE . " WHERE id = %d AND public_analysis_id IS NOT NULL AND public_analysis_id != ''",
                $url_result_id
            ));

            if ($result_with_public_id && $result_with_public_id->public_analysis_id) {
                // Redirect to the proper URL with public_analysis_id
                $redirect_url = home_url('/analiza-zdravstvenog-stanja/izvestaj/?id=' . $result_with_public_id->public_analysis_id);
                wp_redirect($redirect_url);
                exit;
            }
        }

        // Try to find the most recent completed analysis (fallback)
        $recent_result = $wpdb->get_row(
            "SELECT public_analysis_id FROM " . WVP_HEALTH_QUIZ_TABLE . "
             WHERE public_analysis_id IS NOT NULL AND public_analysis_id != ''
             AND ai_analysis IS NOT NULL AND ai_analysis != ''
             ORDER BY created_at DESC LIMIT 1"
        );

        if ($recent_result && $recent_result->public_analysis_id) {
            // Redirect to the most recent completed analysis
            $redirect_url = home_url('/analiza-zdravstvenog-stanja/izvestaj/?id=' . $recent_result->public_analysis_id);
            wp_redirect($redirect_url);
            exit;
        }

        // No results found - show JavaScript that will check localStorage and redirect
        $health_quiz_slug = get_option('wvp_health_quiz_url_slug', 'analiza-zdravstvenog-stanja');
        return '<div class="no-report-message" style="background: #fff; padding: 60px 40px; border-radius: 12px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.1); max-width: 600px; margin: 40px auto;">
            <div style="font-size: 48px; margin-bottom: 20px; color: #f39c12;">⏳</div>
            <h2 style="color: #2c3e50; margin-bottom: 20px; font-size: 28px;">Tražimo vaš izveštaj...</h2>
            <p style="color: #7f8c8d; margin-bottom: 30px; font-size: 16px; line-height: 1.6;">
                Molimo sačekajte dok proveravamo da li imate završenu analizu.
            </p>
            <div id="loading-spinner" style="margin: 20px 0;">
                <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            </div>
            <div id="redirect-options" style="display: none;">
                <p style="color: #e74c3c; margin-bottom: 20px;">Nema završene analize.</p>
                <a href="/' . $health_quiz_slug . '/" style="
                    display: inline-block;
                    background: linear-gradient(135deg, #3498db, #2980b9);
                    color: white;
                    padding: 15px 30px;
                    text-decoration: none;
                    border-radius: 8px;
                    font-weight: 600;
                    font-size: 16px;
                    transition: transform 0.2s;
                    box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
                " onmouseover="this.style.transform=\'translateY(-2px)\'" onmouseout="this.style.transform=\'translateY(0)\'">
                    🚀 Započni analizu zdravstvenog stanja
                </a>
            </div>
        </div>
        <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        </style>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Check localStorage for public_analysis_id
            const publicId = localStorage.getItem("wvp_health_quiz_public_id");
            const resultId = localStorage.getItem("wvp_health_quiz_result_id");

            console.log("Checking localStorage - Public ID:", publicId, "Result ID:", resultId);

            if (publicId) {
                // Redirect to proper URL with public_analysis_id
                window.location.href = "/' . $health_quiz_slug . '/izvestaj/?id=" + publicId;
            } else if (resultId) {
                // Redirect to /zavrsena-anketa to generate public_analysis_id
                window.location.href = "/' . $health_quiz_slug . '/zavrsena-anketa?result_id=" + resultId;
            } else {
                // No data found, show options
                setTimeout(function() {
                    document.getElementById("loading-spinner").style.display = "none";
                    document.getElementById("redirect-options").style.display = "block";
                }, 2000);
            }
        });
        </script>';
    }

    // Get analysis data
    $result = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM " . WVP_HEALTH_QUIZ_TABLE . " WHERE public_analysis_id = %s",
        $public_id
    ), ARRAY_A);

    if (!$result) {
        return '<div class="error-message" style="background: #fff; padding: 40px; border-radius: 8px; text-align: center; color: #dc3545;">Analiza nije pronađena. Molimo proverite link ili kontaktirajte podršku.</div>';
    }

    // Process data
    $first_name = $result['first_name'];
    $last_name = $result['last_name'];
    $birth_year = $result['birth_year'];
    $age = date('Y') - $birth_year;
    $answers = maybe_unserialize($result['answers']);
    $intensities = maybe_unserialize($result['intensity_data']);

    // Get questions
    $questions = get_option('wvp_health_quiz_questions', array());

    // Calculate health score and problems
    $analysis_data = wvp_calculate_health_analysis($questions, $answers, $intensities, $first_name, $age);

    ob_start();
    ?>
    <div id="wvp-health-quiz" style="background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px;">

        <!-- Progress Bar and Health Score -->
        <div class="health-score-section" style="text-align: center; margin-bottom: 40px;">
            <h2 style="color: #2c3e50; margin-bottom: 20px;">Analiza Zdravstvenog Stanja</h2>

            <div class="progress-container" style="margin: 30px 0;">
                <div class="progress-bar" style="width: 100%; height: 20px; background: #e9ecef; border-radius: 10px; overflow: hidden;">
                    <div class="progress-fill" style="width: <?php echo $analysis_data['score']; ?>%; height: 100%; background: <?php echo $analysis_data['score_color']; ?>; transition: width 0.5s ease;"></div>
                </div>
                <div class="progress-labels" style="display: flex; justify-content: space-between; margin-top: 10px; font-size: 12px; color: #6c757d;">
                    <span>Loše (0)</span>
                    <span>Dobro (50)</span>
                    <span>Odličo (100)</span>
                </div>
            </div>

            <div class="ai-score" style="font-size: 3em; font-weight: bold; color: <?php echo $analysis_data['score_color']; ?>; margin: 20px 0;">
                <?php echo $analysis_data['score']; ?> / 100
            </div>
            <p style="color: #6c757d; font-size: 1.1em;">AI Health Score</p>
        </div>

        <!-- Personal Greeting and Problems -->
        <div class="analysis-section" style="margin-bottom: 40px;">
            <h3 style="color: #2c3e50; margin-bottom: 20px;">Poštovana <?php echo esc_html($first_name); ?>,</h3>

            <?php if (!empty($analysis_data['problems'])): ?>
            <div class="problems-section" style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 20px; margin: 20px 0;">
                <h4 style="color: #856404; margin-bottom: 15px;">🚨 Vaši glavni zdravstveni problemi:</h4>
                <ul style="color: #856404; margin: 0; padding-left: 20px;">
                    <?php foreach ($analysis_data['problems'] as $problem): ?>
                    <li style="margin-bottom: 8px;"><?php echo esc_html($problem); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>

        <!-- Natural Health Advice -->
        <div class="advice-section" style="background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 8px; padding: 25px; margin: 30px 0;">
            <h4 style="color: #0c5460; margin-bottom: 20px;">🌿 Prirodni saveti za poboljšanje zdravlja:</h4>
            <div style="color: #0c5460; line-height: 1.6;">
                <?php echo $analysis_data['natural_advice']; ?>
            </div>
        </div>

        <!-- Footer Info -->
        <div class="footer-info" style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #dee2e6; color: #6c757d;">
            <p><strong>Napomena:</strong> Ovi saveti su zasnovani na AI analizi vaših odgovora i služe kao opšte smernice za poboljšanje zdravlja. Za ozbiljne zdravstvene probleme, molimo konsultujte se sa lekarom.</p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
