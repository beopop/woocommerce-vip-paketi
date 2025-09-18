<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get quiz questions from options
$questions = get_option('wvp_health_quiz_questions', array());

// Parse answers and intensity data - SIMPLIFIED SYSTEM
$answers = json_decode($result['answers'], true);
$intensity_data = json_decode($result['intensity_data'], true);

// Ensure we have arrays
if (!is_array($answers)) $answers = array();
if (!is_array($intensity_data)) $intensity_data = array();

// Calculate statistics
$total_questions = count($questions);
$answered_questions = count(array_filter($answers, function($answer) {
    return !empty($answer) && $answer !== 'Nema odgovora';
}));
$yes_answers = count(array_filter($answers, function($answer) {
    return $answer === 'Da';
}));
$completion_percentage = $total_questions > 0 ? round(($answered_questions / $total_questions) * 100) : 0;
?>

<div class="wrap">
    <h1>📊 Detaljan Izveštaj - <?php echo esc_html($result['first_name'] . ' ' . $result['last_name']); ?></h1>

    <a href="?page=wvp-health-quiz-results" class="button">⬅️ Nazad na listu</a>

    <!-- Quick Stats Dashboard -->
    <div class="wvp-stats-dashboard" style="margin: 20px 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <div class="wvp-stat-card">
            <div class="stat-value"><?php echo $completion_percentage; ?>%</div>
            <div class="stat-label">Popunjenost ankete</div>
            <div class="stat-bar">
                <div class="stat-progress" style="width: <?php echo $completion_percentage; ?>%"></div>
            </div>
        </div>
        <div class="wvp-stat-card">
            <div class="stat-value"><?php echo $answered_questions; ?>/<?php echo $total_questions; ?></div>
            <div class="stat-label">Odgovorena pitanja</div>
        </div>
        <div class="wvp-stat-card">
            <div class="stat-value"><?php echo $yes_answers; ?></div>
            <div class="stat-label">Pozitivni odgovori</div>
        </div>
        <div class="wvp-stat-card">
            <div class="stat-value"><?php echo !empty($result['ai_analysis']) ? '✅' : '⏳'; ?></div>
            <div class="stat-label">AI Analiza</div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">

        <!-- Osnovni podaci -->
        <div class="postbox">
            <div class="postbox-header"><h2>👤 Osnovni podaci</h2></div>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th>Ime i prezime:</th>
                        <td><strong><?php echo esc_html($result['first_name'] . ' ' . $result['last_name']); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td><?php echo esc_html($result['email']); ?></td>
                    </tr>
                    <tr>
                        <th>Telefon:</th>
                        <td><?php echo esc_html($result['phone']); ?></td>
                    </tr>
                    <tr>
                        <th>Godina rođenja:</th>
                        <td><?php echo esc_html($result['birth_year']); ?> (<?php echo date('Y') - $result['birth_year']; ?> godina)</td>
                    </tr>
                    <tr>
                        <th>Mesto:</th>
                        <td><?php echo esc_html($result['location']); ?></td>
                    </tr>
                    <tr>
                        <th>Zemlja:</th>
                        <td><?php echo esc_html($result['country']); ?></td>
                    </tr>
                    <tr>
                        <th>Datum unosa:</th>
                        <td><?php echo esc_html($result['created_at']); ?></td>
                    </tr>
                    <?php if (!empty($result['public_analysis_id'])): ?>
                    <tr>
                        <th>Javni ID analize:</th>
                        <td>
                            <code style="background: #f0f0f0; padding: 4px 8px; border-radius: 4px; font-family: monospace;">
                                <?php echo esc_html($result['public_analysis_id']); ?>
                            </code>
                            <br>
                            <small style="color: #666;">
                                URL za pristup:
                                <a href="<?php echo esc_url(home_url('/analiza-zdravstvenog-stanja/izvestaj/?id=' . $result['public_analysis_id'])); ?>" target="_blank">
                                    <?php echo esc_url(home_url('/analiza-zdravstvenog-stanja/izvestaj/?id=' . $result['public_analysis_id'])); ?>
                                </a>
                            </small>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- AI Analiza -->
        <div class="postbox">
            <div class="postbox-header"><h2>🤖 AI Analiza</h2></div>
            <div class="inside">
                <?php if (!empty($result['ai_analysis'])): ?>
                    <?php
                    $ai_analysis = maybe_unserialize($result['ai_analysis']);
                    if (is_array($ai_analysis)):
                    ?>
                        <div class="ai-analysis-content">
                            <?php foreach ($ai_analysis as $key => $value): ?>
                                <div class="ai-section">
                                    <h4><?php echo esc_html(ucfirst(str_replace('_', ' ', $key))); ?>:</h4>
                                    <p><?php echo esc_html($value); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p><?php echo esc_html($result['ai_analysis']); ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="notice notice-warning inline">
                        <p>AI analiza još nije generisana za ovaj izveštaj.</p>
                        <button type="button" class="button button-primary" id="generate-ai-report" data-result-id="<?php echo $result['id']; ?>">
                            🤖 Generiši AI Izveštaj
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Pitanja i odgovori -->
    <div class="postbox" style="margin-top: 20px;">
        <div class="postbox-header"><h2>❓ Pitanja i odgovori</h2></div>
        <div class="inside">
            <table class="wp-list-table widefat fixed striped wvp-questions-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 55%;">Pitanje</th>
                        <th style="width: 15%;">Odgovor</th>
                        <th style="width: 25%;">Intenzitet</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 0; $i < count($questions); $i++): ?>
                        <?php
                        $question = $questions[$i];

                        // Get answer - simple approach
                        $answer = 'Nema odgovora';
                        if (isset($answers[(string)$i])) {
                            $answer = $answers[(string)$i];
                        } elseif (isset($answers[$i])) {
                            $answer = $answers[$i];
                        }

                        // Get intensity
                        $intensity_value = '';
                        if (isset($intensity_data[(string)$i])) {
                            $intensity_value = $intensity_data[(string)$i];
                        } elseif (isset($intensity_data[$i])) {
                            $intensity_value = $intensity_data[$i];
                        }

                        // Determine row class for better visual indication
                        $row_class = '';
                        if ($answer === 'Da') {
                            $row_class = 'positive-answer';
                        } elseif ($answer === 'Ne') {
                            $row_class = 'negative-answer';
                        } else {
                            $row_class = 'no-answer';
                        }
                        ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td><strong><?php echo $i + 1; ?></strong></td>
                            <td><?php echo esc_html($question['text']); ?></td>
                            <td>
                                <span class="answer-badge <?php echo strtolower($answer); ?>">
                                    <?php echo esc_html($answer); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($answer === 'Da' && !empty($intensity_value)): ?>
                                    <?php
                                    // Show intensity level
                                    $intensity_text = $intensity_value;
                                    $intensity_index = intval($intensity_value) - 1;

                                    if (isset($question['intensity_levels']) && isset($question['intensity_levels'][$intensity_index])) {
                                        $intensity_text = $question['intensity_levels'][$intensity_index];
                                    }
                                    ?>
                                    <span class="intensity-badge level-<?php echo intval($intensity_value); ?>">
                                        <?php echo esc_html($intensity_text); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="no-intensity">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Preporučeni proizvodi -->
    <?php if (!empty($result['ai_recommended_products'])): ?>
        <div class="postbox" style="margin-top: 20px;">
            <div class="postbox-header"><h2>🛒 AI Preporučeni proizvodi</h2></div>
            <div class="inside">
                <?php
                $recommended_products = maybe_unserialize($result['ai_recommended_products']);
                if (is_array($recommended_products)):
                ?>
                    <div class="recommended-products">
                        <?php foreach ($recommended_products as $product_id): ?>
                            <?php $product = wc_get_product($product_id); ?>
                            <?php if ($product): ?>
                                <div class="product-recommendation">
                                    <h4><a href="<?php echo admin_url('post.php?post=' . $product_id . '&action=edit'); ?>" target="_blank">
                                        <?php echo esc_html($product->get_name()); ?>
                                    </a></h4>
                                    <p><?php echo esc_html($product->get_short_description()); ?></p>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
/* Stats Dashboard */
.wvp-stat-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}
.wvp-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}
.stat-value {
    font-size: 28px;
    font-weight: bold;
    color: #0073aa;
    margin-bottom: 5px;
}
.stat-label {
    font-size: 13px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.stat-bar {
    height: 4px;
    background: #f0f0f0;
    border-radius: 2px;
    margin-top: 10px;
    overflow: hidden;
}
.stat-progress {
    height: 100%;
    background: linear-gradient(90deg, #0073aa, #005177);
    transition: width 0.8s ease;
}

/* Question Table */
.wvp-questions-table tr.positive-answer {
    background-color: #fff5f5;
}
.wvp-questions-table tr.negative-answer {
    background-color: #f0f9ff;
}
.wvp-questions-table tr.no-answer {
    background-color: #f9f9f9;
}

/* Answer Badges */
.answer-badge {
    padding: 4px 10px;
    border-radius: 15px;
    color: white;
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    display: inline-block;
}
.answer-badge.da {
    background: linear-gradient(135deg, #dc3545, #c82333);
    box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
}
.answer-badge.ne {
    background: linear-gradient(135deg, #28a745, #218838);
    box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
}
.answer-badge.nema {
    background: linear-gradient(135deg, #6c757d, #5a6268);
    box-shadow: 0 2px 4px rgba(108, 117, 125, 0.3);
}

/* Intensity Badges */
.intensity-badge {
    padding: 3px 8px;
    border-radius: 12px;
    color: white;
    font-weight: 500;
    font-size: 10px;
    display: inline-block;
}
.intensity-badge.level-1 { background: #17a2b8; }
.intensity-badge.level-2 { background: #ffc107; color: #333; }
.intensity-badge.level-3 { background: #fd7e14; }
.intensity-badge.level-4 { background: #dc3545; }
.intensity-badge.level-5 { background: #6f42c1; }

.no-intensity {
    color: #999;
    font-style: italic;
}

/* AI Section */
.ai-section {
    margin-bottom: 15px;
    padding: 15px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-left: 4px solid #0073aa;
    border-radius: 0 8px 8px 0;
}
.ai-section h4 {
    margin: 0 0 8px 0;
    color: #0073aa;
    font-weight: 600;
}

/* Product Recommendations */
.product-recommendation {
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 10px;
    background: linear-gradient(135deg, #fff, #fafafa);
    transition: all 0.2s ease;
}
.product-recommendation:hover {
    border-color: #0073aa;
    transform: translateX(5px);
}
.product-recommendation h4 {
    margin: 0 0 8px 0;
}
.product-recommendation h4 a {
    text-decoration: none;
    color: #0073aa;
    font-weight: 600;
}
.product-recommendation h4 a:hover {
    color: #005177;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#generate-ai-report').on('click', function() {
        var button = $(this);
        var resultId = button.data('result-id');

        button.prop('disabled', true).text('🔄 Generiše se...');

        // AJAX call to generate AI report
        $.post(ajaxurl, {
            action: 'wvp_generate_ai_report',
            result_id: resultId,
            nonce: '<?php echo wp_create_nonce('wvp_generate_ai_report'); ?>'
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Greška pri generisanju izveštaja: ' + response.data);
                button.prop('disabled', false).text('🤖 Generiši AI Izveštaj');
            }
        });
    });
});
</script>