<?php
/**
 * Plugin Name: Options FIPE
 * Plugin URI: https://optionstech.com
 * Description: Adiciona uma aba com a tabela fipe direto no WooCommerce.
 * Version: 1.1.0
 * Author: Manoel de Souza
 * Author URI: https://optionstech.com
 * Text Domain: options-fipe
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Adiciona os estilos necessários
function fipe_enqueue_styles($hook)
{
    if ('post.php' === $hook || 'post-new.php' === $hook) {
        wp_enqueue_style('fipe-style', plugin_dir_url(__FILE__) . 'fipe-style.css', array(), '1.0.0');
    }
}
add_action('admin_enqueue_scripts', 'fipe_enqueue_styles');

// Adiciona uma nova aba em Dados do Produto
function fipe_add_tab($tabs)
{
    $tabs['fipe_tab'] = array(
        'label' => __('Tabela FIPE', 'fipe-to-woocommerce'),
        'target' => 'fipe_product_data',
        'class' => array('show_if_simple', 'show_if_variable'),
        'priority' => 40, // Define a posição da aba
    );
    return $tabs;
}
add_filter('woocommerce_product_data_tabs', 'fipe_add_tab');

// Adiciona o conteúdo da aba Tabela FIPE
function fipe_tab_content()
{
    ?>
    <div id="fipe_product_data" class="panel woocommerce_options_panel">
        <div class="options_group">
            <p><?php _e('Busque informações de veículos direto da Tabela FIPE.', 'fipe-to-woocommerce'); ?></p>

            <div class="options-list">
                <label for="fipe_brand"><?php _e('Marca', 'fipe-to-woocommerce'); ?></label>
                <select id="fipe_brand" class="fipe-select" name="fipe_brand"></select>

            </div>
            <div class="options-list">
                <label for="fipe_model"><?php _e('Modelo', 'fipe-to-woocommerce'); ?></label>
                <select id="fipe_model" class="fipe-select" name="fipe_model" disabled></select>

            </div>
            <div class="options-list">
                <label for="fipe_year"><?php _e('Ano', 'fipe-to-woocommerce'); ?></label>
                <select id="fipe_year" class="fipe-select" name="fipe_year" disabled></select>
            </div>

            <div id="fipe_result">
                <h4><?php _e('Resultado:', 'fipe-to-woocommerce'); ?></h4>
                <pre id="fipe_output"></pre>
            </div>
        </div>
    </div>
    <?php
}
add_action('woocommerce_product_data_panels', 'fipe_tab_content');

// Adiciona os scripts e estilos necessários
function fipe_enqueue_scripts($hook)
{
    if ('post.php' === $hook || 'post-new.php' === $hook) {
        wp_enqueue_script('fipe-script', plugin_dir_url(__FILE__) . 'fipe-script.js', array('jquery'), '1.0.0', true);
    }
}
add_action('admin_enqueue_scripts', 'fipe_enqueue_scripts');

// Cria um endpoint para chamar a API da FIPE
function fipe_handle_ajax_request()
{
    $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
    if ($url) {
        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'Erro ao buscar dados da API.'));
        }
        wp_send_json_success(wp_remote_retrieve_body($response));
    }
    wp_send_json_error(array('message' => 'URL inválida.'));
}
add_action('wp_ajax_fipe_api_request', 'fipe_handle_ajax_request');


// Criar a Tabela no Banco de Dados
register_activation_hook(__FILE__, 'create_fipe_table');
function create_fipe_table()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'fipe_wc'; // Nome da tabela
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        fipe_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        fipe_product_id BIGINT(20) UNSIGNED NOT NULL,
        fipe_api_url TEXT NOT NULL,
        PRIMARY KEY (fipe_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Executa o dbDelta e registra logs
    $result = dbDelta($sql);

    // Verifica se a tabela foi criada
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        error_log("Tabela $table_name não foi criada. Erro: " . $wpdb->last_error);
    } else {
        error_log("Tabela $table_name criada com sucesso.");
    }
}

// inserir ou atualizar banco
add_action('wp_ajax_save_fipe_data', 'save_fipe_data');
function save_fipe_data() {
    global $wpdb;

    // Verifica a segurança da requisição
    if (!check_ajax_referer('save_fipe_nonce', 'security', false)) {
        wp_send_json_error(['message' => __('Falha de segurança: nonce inválido.', 'options-fipe')]);
        return;
    }

    $post_id = intval($_POST['post_id']);
    $data = isset($_POST['data']) ? $_POST['data'] : null;

    // Certifique-se de que $data é um array válido
    if (!is_array($data)) {
        wp_send_json_error(['message' => __('Dados inválidos.', 'options-fipe')]);
        return;
    }

    // Log para depuração
    error_log('Dados recebidos no AJAX: ' . print_r($data, true));

    if ($post_id && $data) {
        // Salvar os dados nos metadados do produto
        update_post_meta($post_id, '_fipe_price', sanitize_text_field($data['price']));
        update_post_meta($post_id, '_fipe_reference_month', sanitize_text_field($data['referenceMonth']));
        update_post_meta($post_id, '_brand_id', sanitize_text_field($data['brand_id']));
        update_post_meta($post_id, '_model_id', sanitize_text_field($data['model_id']));
        update_post_meta($post_id, '_year_id', sanitize_text_field($data['year_id']));

        // Montar a URL da API
        $brand_id = sanitize_text_field($data['brand_id']);
        $model_id = sanitize_text_field($data['model_id']);
        $year_id = sanitize_text_field($data['year_id']);
        $api_url = "https://fipe.parallelum.com.br/api/v2/cars/brands/$brand_id/models/$model_id/years/$year_id";

        // Inserir ou atualizar na tabela personalizada
        $table_name = $wpdb->prefix . 'fipe_wc';
        $exists = $wpdb->get_var($wpdb->prepare("SELECT fipe_id FROM $table_name WHERE fipe_product_id = %d", $post_id));

        if ($exists) {
            $wpdb->update(
                $table_name,
                ['fipe_api_url' => $api_url],
                ['fipe_product_id' => $post_id],
                ['%s'],
                ['%d']
            );
        } else {
            $wpdb->insert(
                $table_name,
                ['fipe_product_id' => $post_id, 'fipe_api_url' => $api_url],
                ['%d', '%s']
            );
        }

        wp_send_json_success(['message' => 'Dados salvos com sucesso!', 'fipe_api_url' => $api_url]);
    } else {
        wp_send_json_error(['message' => __('Erro ao salvar os dados.', 'options-fipe')]);
    }
}


add_action('admin_enqueue_scripts', 'enqueue_fipe_save_script');
function enqueue_fipe_save_script($hook)
{
    if ('post.php' === $hook || 'post-new.php' === $hook) {
        wp_enqueue_script('save-fipe-script', plugin_dir_url(__FILE__) . 'save-fipe.js', ['jquery'], '1.0.0', true);
        wp_localize_script('save-fipe-script', 'fipe_save_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('save_fipe_nonce'),
        ]);
    }
}


// Listar os Dados da Tabela
add_action('admin_menu', 'add_fipe_menu_page');

function add_fipe_menu_page()
{
    add_menu_page(
        'Tabela FIPE',
        'Tabela FIPE',
        'manage_options',
        'fipe_table',
        'render_fipe_table_page',
        'dashicons-list-view',
        20
    );
}

function render_fipe_table_page()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'fipe_wc';
    $results = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);

    // Log dos resultados obtidos
    error_log('Registros obtidos da tabela ' . $table_name . ': ' . print_r($results, true));

    echo '<div class="wrap"><h1>Tabela FIPE</h1>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>Produto</th><th>URL da API</th></tr></thead><tbody>';

    foreach ($results as $row) {
        $product_title = get_the_title($row['fipe_product_id']);
        echo '<tr>';
        echo '<td>' . $row['fipe_id'] . '</td>';
        echo '<td>' . esc_html($product_title) . ' (ID: ' . $row['fipe_product_id'] . ')</td>';
        echo '<td><a href="' . esc_url($row['fipe_api_url']) . '" target="_blank">' . esc_url($row['fipe_api_url']) . '</a></td>';
        echo '</tr>';
    }

    echo '</tbody></table></div>';
}

// Shortcode da valor fipe
function url_fipe_price($atts) {
    global $wpdb;

    // Obter o ID do produto dos atributos ou do contexto atual
    $atts = shortcode_atts(
        ['product_id' => get_the_ID()], // Pega o ID do produto atual por padrão
        $atts,
        'tabelafipe'
    );

    $product_id = intval($atts['product_id']);

    // Verificar se o ID do produto é válido
    if (!$product_id) {
        return '<p>Erro: ID do produto inválido.</p>';
    }

    // Nome da tabela personalizada
    $table_name = $wpdb->prefix . 'fipe_wc';

    // Buscar a URL correspondente ao ID do produto
    $url_data = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT fipe_api_url FROM $table_name WHERE fipe_product_id = %d",
            $product_id
        )
    );

    // Verificar se foi encontrada uma URL correspondente
    if (!$url_data) {
        return '<p>Nenhuma URL FIPE encontrada para este produto.</p>';
    }

    // Fazer a requisição à URL
    $response = wp_remote_get($url_data->fipe_api_url);

    // Verificar se houve erro na requisição
    if (is_wp_error($response)) {
        return '<p>Erro ao buscar os dados da FIPE.</p>';
    }

    // Decodificar o JSON retornado
    $data = json_decode(wp_remote_retrieve_body($response), true);

    // Verificar se o campo "price" existe no JSON
    if (isset($data['price'])) {
        $price = $data['price'];
        return '<h4>Valor na Tabela FIPE:</h4><p>' . esc_html($price) . '</p>';
    } else {
        return '<p>O valor "price" não foi encontrado nos dados da FIPE.</p>';
    }
}
add_shortcode('tabelafipe', 'url_fipe_price');





register_deactivation_hook(__FILE__, 'remove_fipe_table_on_deactivation');

function remove_fipe_table_on_deactivation()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'fipe_wc';

    // Remove a tabela
    $wpdb->query("DROP TABLE IF EXISTS $table_name");

    // Opcional: Log para verificar remoção
    error_log("Tabela $table_name removida ao desativar o plugin.");
}

register_uninstall_hook(__FILE__, 'remove_fipe_table_on_uninstall');

function remove_fipe_table_on_uninstall()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'fipe_wc';

    // Remove a tabela
    $wpdb->query("DROP TABLE IF EXISTS $table_name");

    // Opcional: Log para verificar remoção
    error_log("Tabela $table_name removida ao excluir o plugin.");
}