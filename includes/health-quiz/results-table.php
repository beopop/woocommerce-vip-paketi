<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WVP_Health_Quiz_Results_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct(
            array(
                'singular' => 'wvp_health_quiz_result',
                'plural'   => 'wvp_health_quiz_results',
                'ajax'     => false,
            )
        );
    }

    public function get_columns() {
        return array(
            'cb'         => '<input type="checkbox" />',
            'id'         => 'ID',
            'first_name' => 'Ime',
            'last_name'  => 'Prezime',
            'email'      => 'Email',
            'phone'      => 'Telefon',
            'birth_year' => 'Godina',
            'location'   => 'Mesto',
            'country'    => 'Zemlja',
            'ai_score'   => 'AI Skor',
            'product_id' => 'Proizvod',
            'created_at' => 'Datum',
            'actions'    => 'Akcije',
        );
    }

    public function get_sortable_columns() {
        return array(
            'id'         => array( 'id', false ),
            'first_name' => array( 'first_name', false ),
            'last_name'  => array( 'last_name', false ),
            'email'      => array( 'email', false ),
            'phone'      => array( 'phone', false ),
            'birth_year' => array( 'birth_year', false ),
            'location'   => array( 'location', false ),
            'country'    => array( 'country', false ),
            'ai_score'   => array( 'ai_score', false ),
            'created_at' => array( 'created_at', true ),
            'order_id'   => array( 'order_id', false ),
            'user_id'    => array( 'user_id', false ),
        );
    }

    public function get_bulk_actions() {
        return array(
            'delete' => 'Obriši',
        );
    }

    public function no_items() {
        echo 'Nema rezultata.';
    }

    public function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="wvp_health_quiz_selected[]" value="%d" />', $item['id'] );
    }

    public function column_actions( $item ) {
        $view_url = add_query_arg( array(
            'page' => 'wvp-health-quiz-results',
            'action' => 'view',
            'id' => $item['id'],
        ), 'admin.php' );

        $delete_url = wp_nonce_url( add_query_arg( array(
            'page'   => 'wvp-health-quiz-results',
            'action' => 'delete',
            'id'     => $item['id'],
        ), 'admin.php' ), 'bulk-' . $this->_args['plural'] );

        return sprintf(
            '<a href="%s" class="button button-small">📊 Detaljan izveštaj</a> ' .
            '<a href="%s" onclick="return confirm(\'Obrisati ovaj unos?\');" class="button button-small">🗑️ Obriši</a>',
            esc_url( $view_url ),
            esc_url( $delete_url )
        );
    }

    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'product_id':
                return esc_html( $item['product_title'] );
            case 'order_id':
                if ( ! empty( $item['order_id'] ) ) {
                    $url = admin_url( 'post.php?post=' . intval( $item['order_id'] ) . '&action=edit' );
                    return '<a href="' . esc_url( $url ) . '">#' . intval( $item['order_id'] ) . '</a>';
                }
                return '';
            case 'user_id':
                if ( ! empty( $item['user_id'] ) ) {
                    $user = get_user_by( 'id', intval( $item['user_id'] ) );
                    if ( $user ) {
                        $url = admin_url( 'user-edit.php?user_id=' . intval( $item['user_id'] ) );
                        return '<a href="' . esc_url( $url ) . '">' . esc_html( $user->display_name ) . '</a>';
                    }
                }
                return '';
            case 'country':
                $countries = array( 'RS' => 'Srbija', 'HU' => 'Mađarska' );
                $country_code = $item['country'];
                return isset( $countries[ $country_code ] ) ? esc_html( $countries[ $country_code ] ) : esc_html( $country_code );
            case 'ai_score':
                $score = intval( $item['ai_score'] );
                if ( $score > 0 ) {
                    $color = $score >= 70 ? 'green' : ( $score >= 40 ? 'orange' : 'red' );
                    return '<span style="color: ' . $color . '; font-weight: bold;">' . $score . '</span>';
                }
                return '<span style="color: #999;">-</span>';
            case 'ai_analysis':
                $analysis = maybe_unserialize( $item['ai_analysis'] );
                if ( ! empty( $analysis ) && is_array( $analysis ) ) {
                    $stanje = isset( $analysis['stanje_organizma'] ) ? $analysis['stanje_organizma'] : '';
                    $preporuke = isset( $analysis['preporuke'] ) ? $analysis['preporuke'] : '';

                    if ( $stanje || $preporuke ) {
                        $content = '';
                        if ( $stanje ) $content .= '<strong>Stanje:</strong> ' . wp_trim_words( $stanje, 15 ) . '<br>';
                        if ( $preporuke ) $content .= '<strong>Preporuke:</strong> ' . wp_trim_words( $preporuke, 15 );

                        return '<div style="max-width: 300px; font-size: 12px;">' . $content . '</div>';
                    }
                }
                return '<span style="color: #999;">Nema AI analize</span>';
            default:
                return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '';
        }
    }

    public function prepare_items() {
        global $wpdb;

        $per_page = $this->get_items_per_page( 'wvp_health_quiz_results_per_page', 20 );
        $search   = isset( $_REQUEST['s'] ) ? trim( wp_unslash( $_REQUEST['s'] ) ) : '';
        $orderby  = ! empty( $_REQUEST['orderby'] ) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'created_at';
        $order    = ( ! empty( $_REQUEST['order'] ) && strtolower( $_REQUEST['order'] ) === 'asc' ) ? 'ASC' : 'DESC';

        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );

        if ( ! array_key_exists( $orderby, $sortable ) ) {
            $orderby = 'created_at';
        }

        $where  = '';
        if ( $search !== '' ) {
            $like  = '%' . $wpdb->esc_like( $search ) . '%';
            $where = "WHERE first_name LIKE %s OR last_name LIKE %s OR email LIKE %s";
        }

        if ( $where ) {
            $total_items = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM " . WVP_HEALTH_QUIZ_TABLE . " $where",
                    $like,
                    $like,
                    $like
                )
            );
        } else {
            $total_items = $wpdb->get_var( "SELECT COUNT(*) FROM " . WVP_HEALTH_QUIZ_TABLE );
        }

        $current_page = $this->get_pagenum();
        $offset = ( $current_page - 1 ) * $per_page;

        if ( $where ) {
            $sql = $wpdb->prepare(
                "SELECT * FROM " . WVP_HEALTH_QUIZ_TABLE . " $where ORDER BY $orderby $order LIMIT %d OFFSET %d",
                $like,
                $like,
                $like,
                $per_page,
                $offset
            );
        } else {
            $sql = $wpdb->prepare( "SELECT * FROM " . WVP_HEALTH_QUIZ_TABLE . " ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $offset );
        }

        $items = $wpdb->get_results( $sql, ARRAY_A );
        foreach ( $items as &$it ) {
            $it['product_title'] = $it['product_id'] ? get_the_title( $it['product_id'] ) : '';
        }

        $this->items = $items;

        $this->set_pagination_args( array(
            'total_items' => (int) $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ) );
    }

    public function process_bulk_action() {
        if ( 'delete' === $this->current_action() ) {
            check_admin_referer( 'bulk-' . $this->_args['plural'] );
            global $wpdb;
            $ids = array();
            if ( ! empty( $_REQUEST['wvp_health_quiz_selected'] ) ) {
                $ids = array_map( 'intval', (array) $_REQUEST['wvp_health_quiz_selected'] );
            } elseif ( ! empty( $_GET['id'] ) ) {
                $ids[] = intval( $_GET['id'] );
            }
            foreach ( $ids as $id ) {
                if ( $id > 0 ) {
                    $wpdb->delete( WVP_HEALTH_QUIZ_TABLE, array( 'id' => $id ) );
                }
            }
        }
    }
}