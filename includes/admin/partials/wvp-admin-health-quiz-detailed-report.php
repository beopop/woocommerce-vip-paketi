<?php
if (!defined('ABSPATH')) {
    exit;
}

// $result variable is passed from the parent file
$questions = get_option('wvp_health_quiz_questions', array());

// Debug raw data
echo '<!-- Debug Raw Data:
Answers field: ' . esc_html($result['answers']) . '
Intensity field: ' . esc_html($result['intensity_data']) . '
-->';

// Parse answers and intensity data - NEW SYSTEM uses JSON, fallback to old formats
$answers = json_decode($result['answers'], true);
if (!$answers) {
    $answers = maybe_unserialize($result['answers']);
}

$intensity_data = json_decode($result['intensity_data'], true);
if (!$intensity_data) {
    $intensity_data = maybe_unserialize($result['intensity_data']);
}

// NEW SYSTEM: Check if answers are stored as object with question indices as keys
if (is_array($answers) && !empty($answers)) {
    // Check if this is the new format (object with question indices as keys)
    $first_key = array_key_first($answers);
    if (is_string($first_key) || is_numeric($first_key)) {
        // This is the new format - answers are indexed by question number
        $is_new_format = true;
    } else {
        // This is the old format - answers are sequential array
        $is_new_format = false;
    }
} else {
    $is_new_format = false;
}

// Fallback to empty arrays if both decode methods failed
if (!is_array($answers)) {
    $answers = array();
}
if (!is_array($intensity_data)) {
    $intensity_data = array();
}

// Debug processed data
echo '<!-- Debug Processed Data:
Answers array: ' . print_r($answers, true) . '
Answers array keys: ' . print_r(array_keys($answers), true) . '
Answers array type: ' . gettype($answers) . '
Intensity array: ' . print_r($intensity_data, true) . '
Intensity array keys: ' . print_r(array_keys($intensity_data), true) . '
Questions count: ' . count($questions) . '
-->';
?>

<div class="wrap">
    <h1>📊 Detaljan Izveštaj - <?php echo esc_html($result['first_name'] . ' ' . $result['last_name']); ?></h1>

    <a href="?page=wvp-health-quiz-results" class="button">⬅️ Nazad na listu</a>

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
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 50%;">Pitanje</th>
                        <th style="width: 15%;">Odgovor</th>
                        <th style="width: 30%;">Dodatni odgovori</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Debug: Show data summary
                    echo '<!-- Debug: Questions count: ' . count($questions) . ', Answers count: ' . count($answers) . ', Intensity count: ' . count($intensity_data) . ' -->';
                    ?>
                    <?php for ($i = 0; $i < count($questions); $i++): ?>
                        <?php $question = $questions[$i]; ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo esc_html($question['text']); ?></td>
                            <td>
                                <?php
                                $answer = 'Nema odgovora';

                                // BULLETPROOF UNIVERSAL APPROACH - try all possible keys and formats
                                if (isset($answers[(string)$i])) {
                                    $answer = $answers[(string)$i];
                                } elseif (isset($answers[$i])) {
                                    $answer_data = $answers[$i];
                                    if (is_string($answer_data)) {
                                        $answer = $answer_data;
                                    } elseif (is_array($answer_data)) {
                                        $answer = reset($answer_data);
                                    }
                                } elseif (isset($answers['q'.$i])) {
                                    $answer = $answers['q'.$i];
                                }

                                // NEW: Try parsing as raw JSON if we still don't have an answer
                                if ($answer === 'Nema odgovora' && is_string($result['answers'])) {
                                    $json_answers = json_decode($result['answers'], true);
                                    if (is_array($json_answers)) {
                                        if (isset($json_answers[(string)$i])) {
                                            $answer = $json_answers[(string)$i];
                                        } elseif (isset($json_answers[$i])) {
                                            $answer = $json_answers[$i];
                                        }
                                    }
                                }

                                // Debug info for first few questions
                                if ($i < 3) {
                                    echo '<!-- Debug Q' . ($i+1) . ': Format = ' . ($is_new_format ? 'NEW' : 'OLD') . ', Answer = ' . var_export($answer, true) . ' -->';
                                }

                                echo '<span class="answer-badge ' . ($answer === 'Da' ? 'yes' : 'no') . '">' . esc_html($answer) . '</span>';
                                ?>
                            </td>
                            <td>
                                <?php
                                $has_sub_data = false;

                                // First check if sub-questions are in the answer data itself
                                if ($answer === 'Da' && is_array($answer_data)) {
                                    // Look for sub-questions in answer data
                                    foreach ($answer_data as $key => $value) {
                                        if ($key !== 'main' && is_string($key) && !is_numeric($key)) {
                                            if (!$has_sub_data) {
                                                echo '<div class="sub-questions">';
                                                $has_sub_data = true;
                                            }
                                            echo '<div class="sub-qa">';
                                            echo '<strong>' . esc_html($key) . ':</strong> ';
                                            echo '<span class="sub-answer">' . esc_html($value) . '</span>';
                                            echo '</div>';
                                        }
                                    }
                                    if ($has_sub_data) {
                                        echo '</div>';
                                    }
                                }

                                // If no sub-data found in answers, check intensity data
                                $intensity_value = null;

                                // BULLETPROOF UNIVERSAL APPROACH for intensities too
                                if (isset($intensity_data[(string)$i])) {
                                    $intensity_value = $intensity_data[(string)$i];
                                } elseif (isset($intensity_data[$i])) {
                                    $intensity_value = $intensity_data[$i];
                                } elseif (isset($intensity_data['q'.$i])) {
                                    $intensity_value = $intensity_data['q'.$i];
                                }

                                // NEW: Try parsing intensities as raw JSON if we still don't have a value
                                if (empty($intensity_value) && is_string($result['intensity_data'])) {
                                    $json_intensities = json_decode($result['intensity_data'], true);
                                    if (is_array($json_intensities)) {
                                        if (isset($json_intensities[(string)$i])) {
                                            $intensity_value = $json_intensities[(string)$i];
                                        } elseif (isset($json_intensities[$i])) {
                                            $intensity_value = $json_intensities[$i];
                                        }
                                    }
                                }

                                if (!$has_sub_data && $answer === 'Da' && !empty($intensity_value)) {
                                    $additional_data = $intensity_value;

                                    // Debug: Show what we have for this question
                                    if ($i < 3) {
                                        echo '<!-- Debug intensity data for Q' . ($i+1) . ': ' . var_export($additional_data, true) . ' -->';
                                    }

                                    // Check if this is sub-questions data (object/array) or just intensity (number/string)
                                    if (is_array($additional_data) || is_object($additional_data)) {
                                        // This contains sub-questions and answers
                                        echo '<div class="sub-questions">';
                                        foreach ($additional_data as $sub_question => $sub_answer) {
                                            if (is_string($sub_question) && !is_numeric($sub_question)) {
                                                echo '<div class="sub-qa">';
                                                echo '<strong>' . esc_html($sub_question) . ':</strong> ';
                                                echo '<span class="sub-answer">' . esc_html($sub_answer) . '</span>';
                                                echo '</div>';
                                            }
                                        }
                                        echo '</div>';
                                        $has_sub_data = true;
                                    } else {
                                        // This is just intensity level - try to get text from question definition
                                        $intensity_index = intval($additional_data) - 1;
                                        $intensity_text = $additional_data;

                                        if (isset($question['intensity_levels']) && isset($question['intensity_levels'][$intensity_index])) {
                                            $intensity_text = $question['intensity_levels'][$intensity_index];
                                        }

                                        echo '<span class="intensity-level">' . esc_html($intensity_text) . '</span>';
                                        $has_sub_data = true;
                                    }
                                }

                                // If still no data found, show N/A
                                if (!$has_sub_data) {
                                    echo '<span style="color: #999;">N/A</span>';
                                }
                                ?>
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
.answer-badge {
    padding: 3px 8px;
    border-radius: 12px;
    color: white;
    font-weight: bold;
    font-size: 11px;
}
.answer-badge.yes {
    background-color: #dc3545;
}
.answer-badge.no {
    background-color: #28a745;
}
.intensity-level {
    background-color: #007cba;
    color: white;
    padding: 2px 6px;
    border-radius: 8px;
    font-size: 11px;
}
.ai-section {
    margin-bottom: 15px;
    padding: 10px;
    background: #f8f9fa;
    border-left: 4px solid #007cba;
}
.ai-section h4 {
    margin: 0 0 5px 0;
    color: #007cba;
}
.product-recommendation {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 10px;
    background: #fff;
}
.product-recommendation h4 {
    margin: 0 0 5px 0;
}
.product-recommendation h4 a {
    text-decoration: none;
    color: #007cba;
}

.sub-questions {
    font-size: 12px;
}

.sub-qa {
    margin-bottom: 5px;
    padding: 3px 0;
    border-bottom: 1px dotted #ddd;
}

.sub-qa:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.sub-qa strong {
    color: #0073aa;
    font-weight: 500;
}

.sub-answer {
    color: #666;
    margin-left: 5px;
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