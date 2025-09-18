<?php
if (!defined('ABSPATH')) {
    exit;
}

// Start session for quiz data
if (!session_id()) {
    session_start();
}

// Handle PHP form navigation BEFORE any output
if ($_POST && isset($_POST['wvp_action'])) {
    error_log('WVP Health Quiz Template: POST action detected: ' . $_POST['wvp_action']);
    error_log('WVP Health Quiz Template: POST data keys: ' . implode(', ', array_keys($_POST)));
    error_log('WVP Health Quiz Template: wvp_nonce value: ' . ($_POST['wvp_nonce'] ?? 'NOT SET'));

    if (isset($_POST['wvp_nonce']) && wp_verify_nonce($_POST['wvp_nonce'], 'wvp_quiz_navigation')) {
        error_log('WVP Health Quiz Template: Nonce verified successfully');

        switch ($_POST['wvp_action']) {
            case 'start_quiz':
                // Validate form data
                $first_name = sanitize_text_field($_POST['first_name'] ?? '');
                $last_name = sanitize_text_field($_POST['last_name'] ?? '');
                $email = sanitize_email($_POST['email'] ?? '');
                $phone = sanitize_text_field($_POST['phone'] ?? '');
                $birth_year = intval($_POST['birth_year'] ?? 0);
                $location = sanitize_text_field($_POST['location'] ?? '');
                $country = sanitize_text_field($_POST['country'] ?? '');

                error_log('WVP Health Quiz Template: Form data: ' . json_encode([
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'phone' => $phone,
                    'birth_year' => $birth_year,
                    'country' => $country
                ]));

                if ($first_name && $last_name && $email && $phone && $birth_year && $country) {
                    // Save basic data to session or database here if needed
                    $_SESSION['wvp_quiz_data'] = [
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $email,
                        'phone' => $phone,
                        'birth_year' => $birth_year,
                        'location' => $location,
                        'country' => $country
                    ];

                    error_log('WVP Health Quiz Template: Validation passed, redirecting to pitanja1');
                    wp_redirect(home_url('/analiza-zdravstvenog-stanja/pitanja1/'));
                    exit;
                } else {
                    error_log('WVP Health Quiz Template: Validation failed');
                }
                break;

            case 'next_questions':
                // Handle question navigation
                $current_page = intval($_POST['current_page'] ?? 0);
                $answers = $_POST['answers'] ?? array();
                $intensity = $_POST['intensity'] ?? array();

                // Save current answers to session
                if (!isset($_SESSION['wvp_quiz_answers'])) {
                    $_SESSION['wvp_quiz_answers'] = array();
                }
                if (!isset($_SESSION['wvp_quiz_intensity'])) {
                    $_SESSION['wvp_quiz_intensity'] = array();
                }

                $_SESSION['wvp_quiz_answers'] = array_merge($_SESSION['wvp_quiz_answers'], $answers);
                $_SESSION['wvp_quiz_intensity'] = array_merge($_SESSION['wvp_quiz_intensity'], $intensity);

                // Calculate next page
                $next_page = $current_page + 2; // Convert from 0-based to 1-based and increment

                error_log('WVP Health Quiz Template: Next questions, redirecting to pitanja' . $next_page);
                wp_redirect(home_url('/analiza-zdravstvenog-stanja/pitanja' . $next_page . '/'));
                exit;
                break;

            case 'complete_quiz':
                // Handle quiz completion
                $answers = $_POST['answers'] ?? array();
                $intensity = $_POST['intensity'] ?? array();

                // Save final answers to session
                if (!isset($_SESSION['wvp_quiz_answers'])) {
                    $_SESSION['wvp_quiz_answers'] = array();
                }
                if (!isset($_SESSION['wvp_quiz_intensity'])) {
                    $_SESSION['wvp_quiz_intensity'] = array();
                }

                $_SESSION['wvp_quiz_answers'] = array_merge($_SESSION['wvp_quiz_answers'], $answers);
                $_SESSION['wvp_quiz_intensity'] = array_merge($_SESSION['wvp_quiz_intensity'], $intensity);

                // Save complete data to database
                global $wpdb;
                $quiz_data = $_SESSION['wvp_quiz_data'] ?? array();

                if (!empty($quiz_data)) {
                    // Generate unique public analysis ID
                    do {
                        $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
                        $public_id = '';
                        for ($i = 0; $i < 8; $i++) {
                            $public_id .= $characters[rand(0, strlen($characters) - 1)];
                        }
                        $exists = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM " . WVP_HEALTH_QUIZ_TABLE . " WHERE public_analysis_id = %s",
                            $public_id
                        ));
                    } while ($exists > 0);

                    $result = $wpdb->insert(
                        WVP_HEALTH_QUIZ_TABLE,
                        array(
                            'first_name' => $quiz_data['first_name'],
                            'last_name' => $quiz_data['last_name'],
                            'email' => $quiz_data['email'],
                            'phone' => $quiz_data['phone'],
                            'birth_year' => $quiz_data['birth_year'],
                            'location' => $quiz_data['location'],
                            'country' => $quiz_data['country'],
                            'answers' => json_encode($_SESSION['wvp_quiz_answers']),
                            'intensity_data' => json_encode($_SESSION['wvp_quiz_intensity']),
                            'public_analysis_id' => $public_id,
                            'product_id' => 0,
                            'created_at' => current_time('mysql')
                        )
                    );

                    if ($result) {
                        $result_id = $wpdb->insert_id;
                        setcookie('wvp_result_id', $result_id, time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
                        error_log('WVP Health Quiz Template: Quiz completed, saved with ID: ' . $result_id);
                    }
                }

                error_log('WVP Health Quiz Template: Quiz completed, redirecting to zavrsena-anketa for AI processing');

                if ($result && $result_id) {
                    // Redirect to completed page which will generate AI analysis
                    wp_redirect(home_url('/analiza-zdravstvenog-stanja/zavrsena-anketa?result_id=' . $result_id));
                } else {
                    // Fallback to izvestaj if no result_id
                    wp_redirect(home_url('/analiza-zdravstvenog-stanja/izvestaj/'));
                }
                exit;
                break;
        }
    } else {
        if (!isset($_POST['wvp_nonce'])) {
            error_log('WVP Health Quiz Template: Nonce verification failed - nonce field missing');
        } else {
            error_log('WVP Health Quiz Template: Nonce verification failed - invalid nonce');
        }
    }
}

// Get WordPress header
get_header();

// Get current health quiz state
$health_quiz_state = get_query_var('wvp_health_quiz', 'main');

// Determine which step to show based on URL
$url_step = get_query_var('wvp_quiz_step', '');
$current_step = 1; // Default to step 1 (basic info)

if ($url_step === 'report') {
    $current_step = 3; // Results page
} elseif (is_numeric($url_step)) {
    $current_step = 2; // Questions page (pitanja1, pitanja2 etc all show step 2)
}

error_log('WVP Template: Current step determined as: ' . $current_step . ' based on URL step: ' . $url_step);

// Set up page data
$page_title = 'Analiza Zdravstvenog Stanja';
$page_description = 'Odgovorite na jednostavna pitanja i dobijte besplatnu analizu, personalizovane savete i prirodnu terapiju za rešavanje uzroka problema.';

// Set page title for SEO
add_filter('wp_title', function($title) use ($page_title) {
    return $page_title . ' | ' . get_bloginfo('name');
});

add_filter('document_title_parts', function($title) use ($page_title) {
    $title['title'] = $page_title;
    return $title;
});

// Add meta description
add_action('wp_head', function() use ($page_description) {
    echo '<meta name="description" content="' . esc_attr($page_description) . '">' . "\n";
});

?>

<div class="wvp-health-quiz-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-10 col-xl-8 mx-auto">
                <?php
                // Output the health quiz content
                echo wvp_health_quiz_shortcode($current_step);
                ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Force full width layout without sidebars */
body.page .wvp-health-quiz-wrapper {
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

body.page .col-lg-9 {
    flex: 0 0 100% !important;
    max-width: 100% !important;
}

.wvp-health-quiz-wrapper {
    padding: 40px 0;
    background: #f8f9fa;
    min-height: 70vh;
    width: 100%;
    margin: 0;
}

.wvp-health-quiz-wrapper .container-fluid {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 15px;
}

/* Make sure the quiz styling works on the dedicated page */
#wvp-health-quiz {
    background: #fff;
    padding: 40px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

/* Ensure proper spacing and typography */
.wvp-health-quiz-wrapper h1,
.wvp-health-quiz-wrapper h2,
.wvp-health-quiz-wrapper h3 {
    margin-top: 0;
    color: #1d2327;
}

.wvp-health-quiz-wrapper h1 {
    font-size: 2.5em;
    text-align: center;
    font-weight: 600;
    margin-bottom: 20px;
}

.wvp-health-quiz-wrapper h2 {
    font-size: 2em;
    text-align: center;
    margin-bottom: 15px;
}

.wvp-health-quiz-wrapper h3 {
    font-size: 1.5em;
    margin-bottom: 10px;
}

/* Woodmart theme compatibility */
body.page .woodmart-dark {
    background: #f8f9fa !important;
}

body.page .container {
    max-width: 1200px !important;
}

/* Responsive design */
@media (max-width: 1200px) {
    .wvp-health-quiz-wrapper .container-fluid {
        max-width: 100%;
        padding: 0 30px;
    }
}

@media (max-width: 768px) {
    .wvp-health-quiz-wrapper {
        padding: 20px 0;
    }

    .wvp-health-quiz-wrapper .container-fluid {
        padding: 0 15px;
    }

    #wvp-health-quiz {
        padding: 30px 20px;
        margin-bottom: 20px;
    }

    .wvp-health-quiz-wrapper h1 {
        font-size: 2em;
    }

    .wvp-health-quiz-wrapper h2 {
        font-size: 1.5em;
    }
}

/* Additional Woodmart overrides */
body.page .main-page-wrapper .container {
    max-width: none !important;
}

body.page .site-content .container {
    max-width: none !important;
}
</style>

<?php
get_footer();
?>