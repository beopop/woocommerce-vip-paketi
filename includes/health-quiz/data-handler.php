<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Debug: Log when data handler loads
error_log('WVP Health Quiz: data-handler.php loaded at ' . date('Y-m-d H:i:s'));

// Define table name for WVP Health Quiz
if (!defined('WVP_HEALTH_QUIZ_TABLE')) {
    define('WVP_HEALTH_QUIZ_TABLE', $GLOBALS['wpdb']->prefix . 'wvp_health_quiz_results');
}

// Old wvp_save_quiz function removed to prevent duplicate entries
// All functionality moved to wvp_save_answers which has proper UPDATE logic

// Generate unique public analysis ID
function wvp_generate_public_analysis_id() {
    global $wpdb;
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
    return $public_id;
}

error_log('WVP Health Quiz: Registering AJAX actions for wvp_save_answers');
add_action( 'wp_ajax_wvp_save_answers', 'wvp_save_answers' );
add_action( 'wp_ajax_nopriv_wvp_save_answers', 'wvp_save_answers' );

// NEW ROBUST SAVE SYSTEM
add_action( 'wp_ajax_wvp_save_answers_new', 'wvp_save_answers_new' );
add_action( 'wp_ajax_nopriv_wvp_save_answers_new', 'wvp_save_answers_new' );

// BULLETPROOF SAVE SYSTEM
add_action( 'wp_ajax_bulletproof_save_answers', 'bulletproof_save_answers' );
add_action( 'wp_ajax_nopriv_bulletproof_save_answers', 'bulletproof_save_answers' );
function wvp_save_answers() {

    // Debug logging - log to a file that's easily accessible
    $log_file = WP_CONTENT_DIR . '/wvp_debug.log';
    $log_message = date('[Y-m-d H:i:s] ') . 'WVP Health Quiz: wvp_save_answers called' . PHP_EOL;
    $log_message .= 'POST data: ' . print_r($_POST, true) . PHP_EOL;
    file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);

    // Also log to WordPress error log
    error_log('WVP Health Quiz: wvp_save_answers called - POST data: ' . print_r($_POST, true));

    // Debug nonce verification
    error_log('WVP Health Quiz: About to verify nonce: ' . $_POST['nonce']);

    // Manual nonce verification instead of check_ajax_referer
    $nonce_valid = wp_verify_nonce($_POST['nonce'], 'wvp_health_quiz_nonce');
    if (!$nonce_valid) {
        error_log('WVP Health Quiz: Nonce verification FAILED - nonce invalid');
        wp_send_json_error(array('message' => 'Nonce verification failed'));
        return;
    }

    error_log('WVP Health Quiz: Nonce verification PASSED');

    global $wpdb;
    $debug = intval( get_option( 'wvp_health_quiz_debug_log', 0 ) );
    $first_name = sanitize_text_field( $_POST['first_name'] );
    $last_name  = sanitize_text_field( $_POST['last_name'] );
    $email = sanitize_email( $_POST['email'] );
    $is_auto_save = isset($_POST['auto_save']) && $_POST['auto_save'] == '1';

    // For auto-save, allow partial data. For final save, require complete data.
    if ( !$is_auto_save && ! is_email( $email ) ) {
        wp_send_json_error( array( 'message' => 'Neispravan email.' ) );
    }
    $phone = preg_replace( '/[^0-9]/', '', $_POST['phone'] );
    if ( !$is_auto_save && $phone === '' ) {
        wp_send_json_error( array( 'message' => 'Neispravan telefon.' ) );
    }
    $birth_year = intval( $_POST['birth_year'] );
    $location = sanitize_text_field( $_POST['location'] );
    $country = sanitize_text_field( $_POST['country'] );
    // FIXED: Handle both old and new formats
    $answers = array();
    $intensities = array();

    // New simple format (preferred)
    if (isset($_POST['answers_simple'])) {
        $answers_simple = json_decode(stripslashes($_POST['answers_simple']), true);
        if (is_array($answers_simple)) {
            $answers = $answers_simple;
        }
        error_log('WVP Health Quiz: Using NEW SIMPLE format - answers: ' . print_r($answers, true));
    }
    // Old format (fallback)
    elseif (isset($_POST['answers'])) {
        $answers = array_map('sanitize_text_field', $_POST['answers']);
        error_log('WVP Health Quiz: Using OLD format - answers: ' . print_r($answers, true));
    }

    // New simple intensities format
    if (isset($_POST['intensities_simple'])) {
        $intensities_simple = json_decode(stripslashes($_POST['intensities_simple']), true);
        if (is_array($intensities_simple)) {
            $intensities = $intensities_simple;
        }
        error_log('WVP Health Quiz: Using NEW SIMPLE format - intensities: ' . print_r($intensities, true));
    }
    // Old intensities format
    elseif (isset($_POST['intensities'])) {
        $intensities = json_decode(stripslashes($_POST['intensities']), true);
        if (!is_array($intensities)) $intensities = array();
        error_log('WVP Health Quiz: Using OLD format - intensities: ' . print_r($intensities, true));
    }
    if ( ! is_array( $intensities ) ) {
        $intensities = array();
    }
    $user_id = get_current_user_id();

    // Check if this is an auto-save call and if record already exists
    $is_auto_save = isset($_POST['auto_save']) && $_POST['auto_save'] == '1';
    $existing_record = null;
    $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';

    if ($is_auto_save) {
        // Priority 1: Try to find by session_id if provided
        if (!empty($session_id)) {
            $existing_record = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . WVP_HEALTH_QUIZ_TABLE . " WHERE session_id = %s ORDER BY created_at DESC LIMIT 1",
                $session_id
            ));
        }

        // Priority 2: Try to find by email but only recent ones (last 2 hours to prevent conflicts) - only if email is valid
        if (!$existing_record && is_email($email)) {
            $two_hours_ago = date('Y-m-d H:i:s', strtotime('-2 hours'));
            $existing_record = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . WVP_HEALTH_QUIZ_TABLE . " WHERE email = %s AND created_at > %s ORDER BY created_at DESC LIMIT 1",
                $email,
                $two_hours_ago
            ));
        }

            // Priority 3: For auto-save with partial data, try to find by first name if email is not valid
        if (!$existing_record && !is_email($email) && !empty($first_name)) {
            $two_hours_ago = date('Y-m-d H:i:s', strtotime('-2 hours'));
            $existing_record = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . WVP_HEALTH_QUIZ_TABLE . " WHERE first_name = %s AND created_at > %s ORDER BY created_at DESC LIMIT 1",
                $first_name,
                $two_hours_ago
            ));
        }

        // Priority 4: For quiz answers without basic info, look for a recent record from same session
        if (!$existing_record && empty($first_name) && !is_email($email) && !empty($answers)) {
            $one_hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));
            $existing_record = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . WVP_HEALTH_QUIZ_TABLE . " WHERE session_id = %s AND created_at > %s ORDER BY created_at DESC LIMIT 1",
                $session_id,
                $one_hour_ago
            ));
        }
    }

    if ($existing_record && $is_auto_save) {
        // Update existing record
        $update_data = [
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'phone'      => $phone,
            'birth_year' => $birth_year,
            'location'   => $location,
            'country'    => $country,
            'answers'    => json_encode( $answers ),
            'intensity_data' => json_encode( $intensities ),
            'user_id'    => $user_id
        ];

        // Update email only if it's valid (don't overwrite with empty string)
        if (is_email($email)) {
            $update_data['email'] = $email;
        }

        // Add session_id if provided and not already set
        if (!empty($session_id) && empty($existing_record->session_id)) {
            $update_data['session_id'] = $session_id;
        }

        $updated = $wpdb->update(
            WVP_HEALTH_QUIZ_TABLE,
            $update_data,
            ['id' => $existing_record->id]
        );

        if ($updated !== false) {
            error_log('WVP Health Quiz: Successfully updated existing record ID: ' . $existing_record->id);
            wp_send_json_success( array( 'result_id' => $existing_record->id, 'action' => 'updated' ) );
        } else {
            error_log('WVP Health Quiz: Failed to update record: ' . $wpdb->last_error);
            wp_send_json_error( array( 'message' => 'Greška pri ažuriranju.' ) );
        }
        return; // Exit here for auto-save updates
    } else if ($is_auto_save && (empty($first_name) && !is_email($email) && !empty($answers))) {
        // Special case: auto-save with quiz answers but no basic info
        // Create a placeholder record that can be updated later with basic info
        error_log('WVP Health Quiz: Creating placeholder record for quiz answers only');

        $public_analysis_id = wvp_generate_public_analysis_id();
        if (empty($session_id)) {
            $session_id = wp_generate_uuid4();
        }

        // Create minimal record with just answers
        $insert_data = [
            'first_name' => '',
            'last_name'  => '',
            'email'      => '',
            'phone'      => '',
            'birth_year' => 0,
            'location'   => '',
            'country'    => '',
            'answers'    => json_encode( $answers ),
            'intensity_data' => json_encode( $intensities ),
            'ai_analysis' => '',
            'ai_recommended_products' => '',
            'ai_recommended_packages' => '',
            'ai_score' => 0,
            'product_id' => 0,
            'order_id'   => 0,
            'user_id'    => $user_id,
            'public_analysis_id' => $public_analysis_id,
            'session_id' => $session_id,
            'created_at' => current_time( 'mysql' )
        ];

        $inserted = $wpdb->insert( WVP_HEALTH_QUIZ_TABLE, $insert_data );

        if ( false === $inserted ) {
            error_log( 'WVP Health Quiz placeholder insert error: ' . $wpdb->last_error );
            wp_send_json_error( array( 'message' => 'Greška pri snimanju odgovora.' ) );
        }

        $result_id = $wpdb->insert_id;
        error_log('WVP Health Quiz: Successfully created placeholder record with ID: ' . $result_id);

        wp_send_json_success( array(
            'result_id' => $result_id,
            'public_analysis_id' => $public_analysis_id,
            'session_id' => $session_id,
            'action' => 'placeholder_created'
        ) );
        return;
    } else {
        // Generate public analysis ID and session ID if not provided
        $public_analysis_id = wvp_generate_public_analysis_id();
        if (empty($session_id)) {
            $session_id = wp_generate_uuid4();
        }

        // Insert new record
        $insert_data = [
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => $email,
            'phone'      => $phone,
            'birth_year' => $birth_year,
            'location'   => $location,
            'country'    => $country,
            'answers'    => json_encode( $answers ),
            'intensity_data' => json_encode( $intensities ),
            'ai_analysis' => '',
            'ai_recommended_products' => '',
            'ai_recommended_packages' => '',
            'ai_score' => 0,
            'product_id' => 0,
            'order_id'   => 0,
            'user_id'    => $user_id,
            'public_analysis_id' => $public_analysis_id,
            'session_id' => $session_id,
            'created_at' => current_time( 'mysql' )
        ];

        $inserted = $wpdb->insert( WVP_HEALTH_QUIZ_TABLE, $insert_data );

    if ( false === $inserted ) {
        error_log( 'WVP Health Quiz insert error: ' . $wpdb->last_error );
        $resp = array( 'message' => 'Greška pri snimanju.' );
        if ( $debug && $wpdb->last_error ) {
            $resp['log'] = $wpdb->last_error;
        }
        wp_send_json_error( $resp );
    }

        $result_id = $wpdb->insert_id;
        error_log('WVP Health Quiz: Successfully inserted with ID: ' . $result_id);
    } // End of else block for new record insertion

    // Trigger AI analysis if enabled (for both new and updated records)
    if (!$is_auto_save) { // Only trigger AI for final submissions, not auto-saves
        $current_result_id = $existing_record ? $existing_record->id : $result_id;
        $openai = new WVP_Health_Quiz_OpenAI();
        if ( $openai->is_enabled() ) {
            // Try direct execution first, fallback to async
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                // Direct execution for development
                wvp_execute_ai_analysis( $current_result_id );
            } else {
                // Async execution for production
                wvp_process_ai_analysis_async( $current_result_id );
            }
        }
    }

    $final_result_id = $existing_record ? $existing_record->id : $result_id;

    // Get public_analysis_id for response
    $final_public_id = '';
    if ($existing_record) {
        $final_public_id = $existing_record->public_analysis_id;
        // Generate one if missing
        if (empty($final_public_id)) {
            $final_public_id = wvp_generate_public_analysis_id();
            $wpdb->update(
                WVP_HEALTH_QUIZ_TABLE,
                ['public_analysis_id' => $final_public_id],
                ['id' => $existing_record->id]
            );
        }
    } else {
        $final_public_id = $public_analysis_id;
    }

    // Get session_id for response
    $final_session_id = '';
    if ($existing_record) {
        $final_session_id = $existing_record->session_id;
    } else {
        $final_session_id = $session_id;
    }

    wp_send_json_success( array(
        'result_id' => $final_result_id,
        'public_analysis_id' => $final_public_id,
        'session_id' => $final_session_id,
        'action' => $existing_record ? 'updated' : 'created'
    ) );
}

add_action( 'wp_ajax_wvp_set_product', 'wvp_set_product' );
add_action( 'wp_ajax_nopriv_wvp_set_product', 'wvp_set_product' );
function wvp_set_product() {
    check_ajax_referer( 'wvp_health_quiz_nonce', 'nonce' );
    global $wpdb;
    $debug = intval( get_option( 'wvp_health_quiz_debug_log', 0 ) );
    $id = intval( $_POST['result_id'] );
    $product_id = intval( $_POST['product'] );
    if ( $id > 0 ) {
        $updated = $wpdb->update( WVP_HEALTH_QUIZ_TABLE, [ 'product_id' => $product_id ], [ 'id' => $id ] );
        if ( false === $updated ) {
            error_log( 'WVP Health Quiz update error: ' . $wpdb->last_error );
            $resp = array();
            if ( $debug && $wpdb->last_error ) {
                $resp['log'] = $wpdb->last_error;
            }
            wp_send_json_error( $resp );
        }
        setcookie( 'wvp_result_id', $id, time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
        wp_send_json_success();
    }
    wp_send_json_error();
}

/**
 * Process AI analysis asynchronously
 * @param int $result_id
 */
function wvp_process_ai_analysis_async( $result_id ) {
    // Schedule background processing using WordPress cron
    wp_schedule_single_event( time(), 'wvp_process_ai_analysis', array( $result_id ) );
}

/**
 * Process AI analysis - triggered by cron
 * @param int $result_id
 */
add_action( 'wvp_process_ai_analysis', 'wvp_execute_ai_analysis' );
function wvp_execute_ai_analysis( $result_id ) {
    global $wpdb;

    // Get the quiz result
    $result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . WVP_HEALTH_QUIZ_TABLE . " WHERE id = %d", $result_id ) );
    if ( ! $result ) {
        return;
    }

    // Get questions for analysis
    $questions = get_option( 'wvp_health_quiz_questions', array() );
    $answers = maybe_unserialize( $result->answers );
    $intensities = maybe_unserialize( $result->intensity_data );

    $user_data = array(
        'first_name' => $result->first_name,
        'last_name' => $result->last_name,
        'birth_year' => $result->birth_year,
        'country' => $result->country,
        'location' => $result->location
    );

    // Perform AI analysis
    $openai = new WVP_Health_Quiz_OpenAI();
    $analysis_result = $openai->analyze_health_quiz( $questions, $answers, $intensities, $user_data );

    if ( is_wp_error( $analysis_result ) ) {
        // Log error but don't fail
        error_log( 'WVP Health Quiz AI analysis error: ' . $analysis_result->get_error_message() );
        return;
    }

    // Update the result with AI analysis
    $wpdb->update(
        WVP_HEALTH_QUIZ_TABLE,
        array(
            'ai_analysis' => maybe_serialize( array(
                'stanje_organizma' => $analysis_result['stanje_organizma'],
                'preporuke' => $analysis_result['preporuke']
            ) ),
            'ai_recommended_products' => maybe_serialize( $analysis_result['proizvodi'] ),
            'ai_recommended_packages' => maybe_serialize( $analysis_result['paketi'] ),
            'ai_score' => $analysis_result['skor']
        ),
        array( 'id' => $result_id )
    );
}

/**
 * AJAX handler for testing database connection
 */
add_action( 'wp_ajax_wvp_test_db', 'wvp_test_db' );
add_action( 'wp_ajax_nopriv_wvp_test_db', 'wvp_test_db' );
function wvp_test_db() {
    check_ajax_referer( 'wvp_health_quiz_nonce', 'nonce' );
    global $wpdb;

    $results = array();

    // Test 1: Check if table exists
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", WVP_HEALTH_QUIZ_TABLE));
    $results['table_exists'] = ($table_exists == WVP_HEALTH_QUIZ_TABLE);
    $results['table_name'] = WVP_HEALTH_QUIZ_TABLE;

    if ($results['table_exists']) {
        // Test 2: Get table structure
        $columns = $wpdb->get_results("SHOW COLUMNS FROM `" . WVP_HEALTH_QUIZ_TABLE . "`");
        $results['columns'] = array_map(function($col) { return $col->Field; }, $columns);

        // Test 3: Count records
        $count = $wpdb->get_var("SELECT COUNT(*) FROM `" . WVP_HEALTH_QUIZ_TABLE . "`");
        $results['record_count'] = intval($count);

        // Test 4: Get recent records
        $recent = $wpdb->get_results("SELECT id, first_name, last_name, email, created_at FROM `" . WVP_HEALTH_QUIZ_TABLE . "` ORDER BY created_at DESC LIMIT 5");
        $results['recent_records'] = $recent;
    }

    // Test 5: Check WordPress functions
    $results['wp_functions'] = array(
        'wp_create_nonce' => function_exists('wp_create_nonce'),
        'wp_verify_nonce' => function_exists('wp_verify_nonce'),
        'admin_url' => function_exists('admin_url'),
        'current_user_can' => function_exists('current_user_can')
    );

    wp_send_json_success($results);
}

/**
 * AJAX handler to get AI analysis results
 */
add_action( 'wp_ajax_wvp_get_ai_analysis', 'wvp_get_ai_analysis' );
add_action( 'wp_ajax_nopriv_wvp_get_ai_analysis', 'wvp_get_ai_analysis' );
function wvp_get_ai_analysis() {
    check_ajax_referer( 'wvp_ai_analysis_nonce', 'nonce' );

    $result_id = intval( $_POST['result_id'] );
    if ( $result_id <= 0 ) {
        wp_send_json_error( array( 'message' => 'Invalid result ID' ) );
    }

    global $wpdb;
    $result = $wpdb->get_row( $wpdb->prepare(
        "SELECT ai_analysis, ai_recommended_products, ai_recommended_packages, ai_score FROM " . WVP_HEALTH_QUIZ_TABLE . " WHERE id = %d",
        $result_id
    ) );

    if ( ! $result ) {
        wp_send_json_error( array( 'message' => 'Result not found' ) );
    }

    // Check if AI analysis is completed
    $ai_analysis = maybe_unserialize( $result->ai_analysis );
    if ( empty( $ai_analysis ) || ! is_array( $ai_analysis ) ) {
        // Check if OpenAI is enabled
        $openai = new WVP_Health_Quiz_OpenAI();
        if ( ! $openai->is_enabled() ) {
            wp_send_json_error( array( 'message' => 'OpenAI integracija nije omogućena. Proverite podešavanja u admin panelu.' ) );
        }

        // Automatically trigger AI analysis generation if not exists
        error_log('WVP: No AI analysis found for result ID ' . $result_id . ', triggering automatic generation...');

        // Trigger background AI analysis generation
        $wvp_core = new WVP_Core();

        // Create fake POST data for the AI generation function
        $_POST_backup = $_POST;
        $_POST['result_id'] = $result_id;
        $_POST['nonce'] = wp_create_nonce('wvp_generate_frontend_ai_report');

        // Temporarily change the nonce check to allow our auto-generated nonce
        $_POST['auto_generated'] = true;

        // Check if WVP_Core class and method exist before attempting to call
        if (!class_exists('WVP_Core')) {
            error_log('WVP: WVP_Core class not found - cannot generate AI analysis automatically');
            wp_send_json_error( array( 'message' => 'AI analiza nije dostupna - nedostaje WVP_Core klasa.' ) );
            return;
        }

        if (!method_exists($wvp_core, 'ajax_generate_frontend_ai_report')) {
            error_log('WVP: ajax_generate_frontend_ai_report method not found in WVP_Core');
            wp_send_json_error( array( 'message' => 'AI analiza nije dostupna - nedostaje metoda za generisanje.' ) );
            return;
        }

        try {
            // Set up error handler to catch fatal errors
            set_error_handler(function($severity, $message, $file, $line) {
                throw new ErrorException($message, 0, $severity, $file, $line);
            });

            // Call the AI generation function directly
            ob_start();
            $wvp_core->ajax_generate_frontend_ai_report();
            $ai_generation_output = ob_get_clean();

            // Restore error handler
            restore_error_handler();

            error_log('WVP: AI generation triggered successfully, output length: ' . strlen($ai_generation_output));

        } catch (Exception $e) {
            restore_error_handler();
            ob_end_clean();
            error_log('WVP: Error during automatic AI generation: ' . $e->getMessage());
            error_log('WVP: Error file: ' . $e->getFile() . ' line: ' . $e->getLine());

            // Return error instead of trying to continue
            wp_send_json_error( array( 'message' => 'Greška pri automatskom generisanju AI analize: ' . $e->getMessage() ) );
            return;
        } catch (Throwable $e) {
            restore_error_handler();
            ob_end_clean();
            error_log('WVP: Fatal error during automatic AI generation: ' . $e->getMessage());
            error_log('WVP: Fatal error file: ' . $e->getFile() . ' line: ' . $e->getLine());

            // Return error instead of trying to continue
            wp_send_json_error( array( 'message' => 'Fatalna greška pri AI analizi: ' . $e->getMessage() ) );
            return;
        }

        // Restore original POST data
        $_POST = $_POST_backup;

        wp_send_json_success( array( 'ai_analysis' => null, 'processing' => true, 'message' => 'AI analiza se automatski generiše... Sačekajte nekoliko sekundi.' ) );
    }

    $response_data = array(
        'ai_analysis' => $ai_analysis,
        'ai_score' => intval( $result->ai_score ),
        'ai_recommended_products' => maybe_unserialize( $result->ai_recommended_products ),
        'ai_recommended_packages' => maybe_unserialize( $result->ai_recommended_packages ),
        'processing' => false
    );

    wp_send_json_success( $response_data );
}

/**
 * AJAX handler to test OpenAI connection
 */
add_action( 'wp_ajax_wvp_test_openai_connection', 'wvp_test_openai_connection' );
add_action( 'wp_ajax_nopriv_wvp_test_openai_connection', 'wvp_test_openai_connection' );
function wvp_test_openai_connection() {
    check_ajax_referer( 'wvp_test_openai_nonce', 'nonce' );

    $openai = new WVP_Health_Quiz_OpenAI();

    if ( ! $openai->is_enabled() ) {
        wp_send_json_error( array( 'message' => 'OpenAI integracija nije omogućena ili API ključ nije postavljen.' ) );
    }

    // Test with a simple prompt
    $test_questions = array(
        array(
            'text' => 'Test pitanje',
            'answers' => array( 'Da', 'Ne' ),
            'intensity_levels' => array( 'Blago', 'Umerno', 'Jako' )
        )
    );

    $test_answers = array( 'Ne' );
    $test_intensities = array();
    $test_user_data = array(
        'first_name' => 'Test',
        'last_name' => 'User',
        'birth_year' => 1990,
        'country' => 'RS',
        'location' => 'Test'
    );

    $test_result = $openai->analyze_health_quiz( $test_questions, $test_answers, $test_intensities, $test_user_data );

    if ( is_wp_error( $test_result ) ) {
        wp_send_json_error( array( 'message' => $test_result->get_error_message() ) );
    }

    wp_send_json_success( array( 'message' => 'OpenAI konekcija radi ispravno!', 'test_result' => $test_result ) );
}

/**
 * AJAX handler to manually trigger AI analysis for a result
 */
add_action( 'wp_ajax_wvp_trigger_ai_analysis', 'wvp_trigger_ai_analysis_manual' );
add_action( 'wp_ajax_nopriv_wvp_trigger_ai_analysis', 'wvp_trigger_ai_analysis_manual' );
function wvp_trigger_ai_analysis_manual() {
    check_ajax_referer( 'wvp_ai_analysis_nonce', 'nonce' );

    $result_id = intval( $_POST['result_id'] );
    if ( $result_id <= 0 ) {
        wp_send_json_error( array( 'message' => 'Invalid result ID' ) );
    }

    // Execute AI analysis directly
    wvp_execute_ai_analysis( $result_id );

    wp_send_json_success( array( 'message' => 'AI analiza je pokrenuta!' ) );
}

/**
 * AJAX handler to get product details
 */
add_action( 'wp_ajax_wvp_get_product_details', 'wvp_get_product_details' );
add_action( 'wp_ajax_nopriv_wvp_get_product_details', 'wvp_get_product_details' );
function wvp_get_product_details() {
    check_ajax_referer( 'wvp_product_details_nonce', 'nonce' );

    $product_id = intval( $_POST['product_id'] );
    if ( $product_id <= 0 ) {
        wp_send_json_error( array( 'message' => 'Invalid product ID' ) );
    }

    $product = wc_get_product( $product_id );
    if ( ! $product ) {
        wp_send_json_error( array( 'message' => 'Product not found' ) );
    }

    $product_data = array(
        'id' => $product_id,
        'name' => $product->get_name(),
        'description' => $product->get_description(),
        'short_description' => $product->get_short_description(),
        'image' => wp_get_attachment_image_url( $product->get_image_id(), 'medium' ),
        'image_url' => wp_get_attachment_image_url( $product->get_image_id(), 'medium' ),
        'regular_price' => $product->get_regular_price(),
        'sale_price' => $product->get_sale_price(),
        'price' => $product->get_price(),
        'regular_price_formatted' => wc_price( $product->get_regular_price() ),
        'sale_price_formatted' => $product->get_sale_price() ? wc_price( $product->get_sale_price() ) : '',
        'price_formatted' => wc_price( $product->get_price() )
    );

    wp_send_json_success( $product_data );
}

/**
 * AJAX handler to get package details
 */
add_action( 'wp_ajax_wvp_get_package_details', 'wvp_get_package_details' );
add_action( 'wp_ajax_nopriv_wvp_get_package_details', 'wvp_get_package_details' );
function wvp_get_package_details() {
    check_ajax_referer( 'wvp_package_details_nonce', 'nonce' );

    $package_id = intval( $_POST['package_id'] );
    $size = intval( $_POST['size'] );

    if ( $package_id <= 0 ) {
        wp_send_json_error( array( 'message' => 'Invalid package ID' ) );
    }

    $package = get_post( $package_id );
    if ( ! $package || $package->post_type !== 'wvp_package' ) {
        wp_send_json_error( array( 'message' => 'Package not found' ) );
    }

    // Get package meta data
    $package_sizes = get_post_meta( $package_id, '_wvp_package_sizes', true ) ?: array();
    $regular_discounts = get_post_meta( $package_id, '_wvp_regular_discounts', true ) ?: array();
    $allowed_products = get_post_meta( $package_id, '_wvp_allowed_products', true ) ?: array();

    // Get discount for the requested size
    $discount = isset( $regular_discounts[$size] ) ? $regular_discounts[$size] : 0;

    // Get product details
    $products = array();
    foreach ( $allowed_products as $product_id ) {
        $product = wc_get_product( $product_id );
        if ( $product ) {
            $products[] = array(
                'id' => $product_id,
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'price_formatted' => wc_price( $product->get_price() ),
                'image_url' => wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ),
                'short_description' => $product->get_short_description()
            );
        }
    }

    $package_data = array(
        'id' => $package_id,
        'name' => $package->post_title,
        'description' => wp_trim_words( $package->post_content, 30 ),
        'sizes' => $package_sizes,
        'discount' => $discount,
        'products' => $products
    );

    wp_send_json_success( $package_data );
}

/**
 * AJAX handler to add product to cart
 */
add_action( 'wp_ajax_wvp_add_product_to_cart', 'wvp_add_product_to_cart' );
add_action( 'wp_ajax_nopriv_wvp_add_product_to_cart', 'wvp_add_product_to_cart' );
function wvp_add_product_to_cart() {
    check_ajax_referer( 'wvp_add_to_cart_nonce', 'nonce' );

    $product_id = intval( $_POST['product_id'] );
    if ( $product_id <= 0 ) {
        wp_send_json_error( array( 'message' => 'Invalid product ID' ) );
    }

    $product = wc_get_product( $product_id );
    if ( ! $product ) {
        wp_send_json_error( array( 'message' => 'Product not found' ) );
    }

    $result = WC()->cart->add_to_cart( $product_id, 1 );

    if ( $result ) {
        wp_send_json_success( array(
            'message' => 'Product added to cart successfully',
            'cart_count' => WC()->cart->get_cart_contents_count()
        ) );
    } else {
        wp_send_json_error( array( 'message' => 'Failed to add product to cart' ) );
    }
}

/**
 * AJAX handler to add package to cart
 */
add_action( 'wp_ajax_wvp_add_package_to_cart', 'wvp_add_package_to_cart' );
add_action( 'wp_ajax_nopriv_wvp_add_package_to_cart', 'wvp_add_package_to_cart' );
function wvp_add_package_to_cart() {
    check_ajax_referer( 'wvp_add_to_cart_nonce', 'nonce' );

    $package_id = intval( $_POST['package_id'] );
    $size = intval( $_POST['size'] );
    $products = isset( $_POST['products'] ) ? array_map( 'intval', $_POST['products'] ) : array();

    if ( $package_id <= 0 || empty( $products ) ) {
        wp_send_json_error( array( 'message' => 'Invalid package data' ) );
    }

    if ( count( $products ) !== $size ) {
        wp_send_json_error( array( 'message' => 'Product count does not match package size' ) );
    }

    // Get package discount
    $regular_discounts = get_post_meta( $package_id, '_wvp_regular_discounts', true ) ?: array();
    $discount = isset( $regular_discounts[$size] ) ? $regular_discounts[$size] : 0;

    $added_products = array();
    $total_regular_price = 0;

    // Add each product to cart with package metadata
    foreach ( $products as $product_id ) {
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            continue;
        }

        $cart_item_data = array(
            'wvp_package_id' => $package_id,
            'wvp_package_size' => $size,
            'wvp_package_discount' => $discount
        );

        $cart_item_key = WC()->cart->add_to_cart( $product_id, 1, 0, array(), $cart_item_data );

        if ( $cart_item_key ) {
            $added_products[] = $product_id;
            $total_regular_price += $product->get_price();
        }
    }

    if ( empty( $added_products ) ) {
        wp_send_json_error( array( 'message' => 'Failed to add any products to cart' ) );
    }

    $discount_amount = $total_regular_price * ( $discount / 100 );
    $final_price = $total_regular_price - $discount_amount;

    wp_send_json_success( array(
        'message' => 'Package added to cart successfully',
        'added_products' => $added_products,
        'total_regular_price' => $total_regular_price,
        'discount_amount' => $discount_amount,
        'final_price' => $final_price,
        'cart_count' => WC()->cart->get_cart_contents_count()
    ) );
}

/**
 * NEW ROBUST ANSWER SAVING SYSTEM
 * Completely rewritten for reliability
 */
function wvp_save_answers_new() {
    error_log('WVP Health Quiz NEW: wvp_save_answers_new called');

    // Verify nonce
    $nonce_valid = wp_verify_nonce($_POST['nonce'] ?? '', 'wvp_health_quiz_nonce');
    if (!$nonce_valid) {
        error_log('WVP Health Quiz NEW: Nonce verification FAILED');
        wp_send_json_error(array('message' => 'Nonce verification failed'));
        return;
    }

    global $wpdb;

    // Get basic data
    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name = sanitize_text_field($_POST['last_name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
    $birth_year = intval($_POST['birth_year'] ?? 0);
    $location = sanitize_text_field($_POST['location'] ?? '');
    $country = sanitize_text_field($_POST['country'] ?? '');

    // Get answers and intensities as JSON
    $answers_json = $_POST['answers_json'] ?? '{}';
    $intensities_json = $_POST['intensities_json'] ?? '{}';
    $session_id = sanitize_text_field($_POST['session_id'] ?? '');
    $result_id = intval($_POST['result_id'] ?? 0);

    error_log('WVP Health Quiz NEW: Received data - Answers: ' . $answers_json . ', Intensities: ' . $intensities_json);

    // Try to find existing record
    $existing_record = null;

    if ($result_id > 0) {
        // Try by result ID first
        $existing_record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . WVP_HEALTH_QUIZ_TABLE . " WHERE id = %d",
            $result_id
        ));
    }

    if (!$existing_record && !empty($session_id)) {
        // Try by session ID
        $existing_record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . WVP_HEALTH_QUIZ_TABLE . " WHERE session_id = %s ORDER BY created_at DESC LIMIT 1",
            $session_id
        ));
    }

    if (!$existing_record && !empty($email) && is_email($email)) {
        // Try by email (recent records only)
        $two_hours_ago = date('Y-m-d H:i:s', strtotime('-2 hours'));
        $existing_record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . WVP_HEALTH_QUIZ_TABLE . " WHERE email = %s AND created_at > %s ORDER BY created_at DESC LIMIT 1",
            $email,
            $two_hours_ago
        ));
    }

    if ($existing_record) {
        // UPDATE existing record
        error_log('WVP Health Quiz NEW: Updating existing record ID: ' . $existing_record->id);

        $update_data = array(
            'answers' => $answers_json,
            'intensity_data' => $intensities_json,
        );

        // Only update basic info if provided
        if (!empty($first_name)) $update_data['first_name'] = $first_name;
        if (!empty($last_name)) $update_data['last_name'] = $last_name;
        if (!empty($email) && is_email($email)) $update_data['email'] = $email;
        if (!empty($phone)) $update_data['phone'] = $phone;
        if ($birth_year > 0) $update_data['birth_year'] = $birth_year;
        if (!empty($location)) $update_data['location'] = $location;
        if (!empty($country)) $update_data['country'] = $country;
        if (!empty($session_id) && empty($existing_record->session_id)) $update_data['session_id'] = $session_id;

        $updated = $wpdb->update(
            WVP_HEALTH_QUIZ_TABLE,
            $update_data,
            array('id' => $existing_record->id)
        );

        if ($updated !== false) {
            error_log('WVP Health Quiz NEW: Successfully updated record ID: ' . $existing_record->id);
            wp_send_json_success(array(
                'result_id' => $existing_record->id,
                'action' => 'updated'
            ));
        } else {
            error_log('WVP Health Quiz NEW: Failed to update record: ' . $wpdb->last_error);
            wp_send_json_error(array('message' => 'Failed to update record'));
        }

    } else {
        // CREATE new record
        error_log('WVP Health Quiz NEW: Creating new record');

        if (empty($session_id)) {
            $session_id = wp_generate_uuid4();
        }

        $public_analysis_id = wvp_generate_public_analysis_id();

        $insert_data = array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'birth_year' => $birth_year,
            'location' => $location,
            'country' => $country,
            'answers' => $answers_json,
            'intensity_data' => $intensities_json,
            'session_id' => $session_id,
            'public_analysis_id' => $public_analysis_id,
            'ai_analysis' => '',
            'ai_recommended_products' => '',
            'ai_recommended_packages' => '',
            'ai_score' => 0,
            'product_id' => 0,
            'order_id' => 0,
            'user_id' => get_current_user_id(),
            'created_at' => current_time('mysql')
        );

        $inserted = $wpdb->insert(WVP_HEALTH_QUIZ_TABLE, $insert_data);

        if ($inserted !== false) {
            $new_result_id = $wpdb->insert_id;
            error_log('WVP Health Quiz NEW: Successfully created record ID: ' . $new_result_id);
            wp_send_json_success(array(
                'result_id' => $new_result_id,
                'public_analysis_id' => $public_analysis_id,
                'session_id' => $session_id,
                'action' => 'created'
            ));
        } else {
            error_log('WVP Health Quiz NEW: Failed to create record: ' . $wpdb->last_error);
            wp_send_json_error(array('message' => 'Failed to create record'));
        }
    }
}

/**
 * BULLETPROOF ANSWER SAVING SYSTEM
 * 100% reliable - designed to never fail
 */
function bulletproof_save_answers() {
    // Immediately log that we were called
    error_log('BULLETPROOF: Function called at ' . date('Y-m-d H:i:s'));
    error_log('BULLETPROOF: POST data received: ' . print_r($_POST, true));

    // Verify nonce - but be more lenient
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wvp_health_quiz_nonce')) {
        error_log('BULLETPROOF: Nonce verification failed');
        wp_send_json_error(array('message' => 'Security check failed'));
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'wvp_health_quiz_results';

    // Get all data with strict validation
    $session_id = sanitize_text_field($_POST['session_id'] ?? '');
    $result_id = intval($_POST['result_id'] ?? 0);

    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name = sanitize_text_field($_POST['last_name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
    $birth_year = intval($_POST['birth_year'] ?? 0);
    $location = sanitize_text_field($_POST['location'] ?? '');
    $country = sanitize_text_field($_POST['country'] ?? '');

    // Get answers data - this is the crucial part
    $answers_raw = $_POST['answers_data'] ?? '{}';
    $intensities_raw = $_POST['intensities_data'] ?? '{}';

    error_log('BULLETPROOF: Raw answers data: ' . $answers_raw);
    error_log('BULLETPROOF: Raw intensities data: ' . $intensities_raw);

    // Parse JSON data
    $answers = json_decode($answers_raw, true);
    $intensities = json_decode($intensities_raw, true);

    if (!is_array($answers)) $answers = array();
    if (!is_array($intensities)) $intensities = array();

    error_log('BULLETPROOF: Parsed answers: ' . print_r($answers, true));
    error_log('BULLETPROOF: Parsed intensities: ' . print_r($intensities, true));

    // SMART RECORD LOOKUP - Find existing record using multiple strategies
    $existing_record = null;

    // Priority 1: Search by result_id if provided
    if ($result_id > 0) {
        $existing_record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $result_id
        ));
        error_log('BULLETPROOF: Search by result_id ' . $result_id . ': ' . ($existing_record ? 'FOUND' : 'NOT FOUND'));
    }

    // Priority 2: Search by session_id (most reliable for auto-save)
    if (!$existing_record && !empty($session_id)) {
        $existing_record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE session_id = %s ORDER BY created_at DESC LIMIT 1",
            $session_id
        ));
        error_log('BULLETPROOF: Search by session_id ' . $session_id . ': ' . ($existing_record ? 'FOUND' : 'NOT FOUND'));
    }

    // Priority 3: Search by email (for form data saves)
    if (!$existing_record && !empty($email) && is_email($email)) {
        $two_hours_ago = date('Y-m-d H:i:s', strtotime('-2 hours'));
        $existing_record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE email = %s AND created_at > %s ORDER BY created_at DESC LIMIT 1",
            $email,
            $two_hours_ago
        ));
        error_log('BULLETPROOF: Search by email ' . $email . ': ' . ($existing_record ? 'FOUND' : 'NOT FOUND'));
    }

    // Priority 4: For quiz-only data (answers without form), find any recent record from same session
    if (!$existing_record && empty($first_name) && !is_email($email) && (!empty($answers) || !empty($intensities))) {
        $one_hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));
        if (!empty($session_id)) {
            $existing_record = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE session_id = %s AND created_at > %s ORDER BY created_at DESC LIMIT 1",
                $session_id,
                $one_hour_ago
            ));
            error_log('BULLETPROOF: Search by session_id for quiz-only data: ' . ($existing_record ? 'FOUND' : 'NOT FOUND'));
        }
    }

    if ($existing_record) {
        // UPDATE existing record
        error_log('BULLETPROOF: Updating existing record ID: ' . $existing_record->id);

        $update_data = array();

        // MERGE answers and intensities instead of overwriting
        if (!empty($answers_raw) && $answers_raw !== '{}') {
            // Get existing answers from database with robust parsing
            $existing_answers_raw = $existing_record->answers;
            $existing_answers = json_decode($existing_answers_raw, true);

            // Handle escaped JSON if needed
            if (!is_array($existing_answers) && !empty($existing_answers_raw) && strpos($existing_answers_raw, '\\"') !== false) {
                $unescaped = stripslashes($existing_answers_raw);
                $existing_answers = json_decode($unescaped, true);
            }

            if (!is_array($existing_answers)) $existing_answers = array();

            // Merge new answers with existing ones (new answers override old ones for same keys)
            $merged_answers = array_merge($existing_answers, $answers);
            $update_data['answers'] = json_encode($merged_answers);

            error_log('BULLETPROOF: Merging answers - Existing: ' . print_r($existing_answers, true) . ' New: ' . print_r($answers, true) . ' Merged: ' . print_r($merged_answers, true));
        }
        if (!empty($intensities_raw) && $intensities_raw !== '{}') {
            // Get existing intensities from database with robust parsing
            $existing_intensities_raw = $existing_record->intensity_data;
            $existing_intensities = json_decode($existing_intensities_raw, true);

            // Handle escaped JSON if needed
            if (!is_array($existing_intensities) && !empty($existing_intensities_raw) && strpos($existing_intensities_raw, '\\"') !== false) {
                $unescaped = stripslashes($existing_intensities_raw);
                $existing_intensities = json_decode($unescaped, true);
            }

            if (!is_array($existing_intensities)) $existing_intensities = array();

            // Merge new intensities with existing ones (new intensities override old ones for same keys)
            $merged_intensities = array_merge($existing_intensities, $intensities);
            $update_data['intensity_data'] = json_encode($merged_intensities);

            error_log('BULLETPROOF: Merging intensities - Existing: ' . print_r($existing_intensities, true) . ' New: ' . print_r($intensities, true) . ' Merged: ' . print_r($merged_intensities, true));
        }

        // Update basic info only if provided and not empty
        if (!empty($first_name)) $update_data['first_name'] = $first_name;
        if (!empty($last_name)) $update_data['last_name'] = $last_name;
        if (!empty($email) && is_email($email)) $update_data['email'] = $email;
        if (!empty($phone)) $update_data['phone'] = $phone;
        if ($birth_year > 0) $update_data['birth_year'] = $birth_year;
        if (!empty($location)) $update_data['location'] = $location;
        if (!empty($country)) $update_data['country'] = $country;

        // Ensure we have at least some data to update
        if (empty($update_data)) {
            error_log('BULLETPROOF: No data to update, skipping...');
            wp_send_json_success(array(
                'result_id' => $existing_record->id,
                'action' => 'no_changes',
                'message' => 'No data to update'
            ));
            return;
        }

        $updated = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $existing_record->id)
        );

        if ($updated !== false) {
            error_log('BULLETPROOF: Successfully updated record ID: ' . $existing_record->id);
            wp_send_json_success(array(
                'result_id' => $existing_record->id,
                'action' => 'updated',
                'answers_count' => count($answers),
                'intensities_count' => count($intensities)
            ));
        } else {
            error_log('BULLETPROOF: Failed to update record: ' . $wpdb->last_error);
            wp_send_json_error(array('message' => 'Update failed: ' . $wpdb->last_error));
        }

    } else {
        // CREATE new record
        error_log('BULLETPROOF: Creating new record');

        if (empty($session_id)) {
            $session_id = 'bp_' . time() . '_' . wp_generate_password(8, false);
        }

        $public_analysis_id = wvp_generate_public_analysis_id();

        $insert_data = array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'birth_year' => $birth_year ?: 1990,
            'location' => $location,
            'country' => $country,
            'answers' => $answers_raw,  // Store raw JSON
            'intensity_data' => $intensities_raw,  // Store raw JSON
            'session_id' => $session_id,
            'public_analysis_id' => $public_analysis_id,
            'ai_analysis' => '',
            'ai_recommended_products' => '',
            'ai_recommended_packages' => '',
            'ai_score' => 0,
            'product_id' => 0,
            'order_id' => 0,
            'user_id' => get_current_user_id(),
            'created_at' => current_time('mysql')
        );

        error_log('BULLETPROOF: About to insert: ' . print_r($insert_data, true));

        $inserted = $wpdb->insert($table_name, $insert_data);

        if ($inserted !== false) {
            $new_result_id = $wpdb->insert_id;
            error_log('BULLETPROOF: Successfully created record ID: ' . $new_result_id);
            wp_send_json_success(array(
                'result_id' => $new_result_id,
                'public_analysis_id' => $public_analysis_id,
                'session_id' => $session_id,
                'action' => 'created',
                'answers_count' => count($answers),
                'intensities_count' => count($intensities)
            ));
        } else {
            error_log('BULLETPROOF: Failed to create record: ' . $wpdb->last_error);
            wp_send_json_error(array('message' => 'Insert failed: ' . $wpdb->last_error));
        }
    }
}