<?php
/**
 * Plugin Name: BannerCast
 * Description: BannerCast – Broadcast beautiful announcement bars with per-message styles, scroll settings, display rules and shortcodes.
 * Version:     1.0.0
 * Author:      Silpa TA
 * License:     GPL-2.0+
 * Text Domain: bannercast
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'BC_VERSION', '1.0.0' );
define( 'BC_DIR', plugin_dir_path( __FILE__ ) );
define( 'BC_URL', plugin_dir_url(  __FILE__ ) );
define( 'BC_CPT', 'bc_message' );

/* ═══════════════════════════════════════════════
   ACTIVATION
═══════════════════════════════════════════════ */
register_activation_hook( __FILE__, function() {
    bc_register_cpt();
    flush_rewrite_rules();
});

/* ═══════════════════════════════════════════════
   CUSTOM POST TYPE
═══════════════════════════════════════════════ */
add_action( 'init', 'bc_register_cpt' );
function bc_register_cpt() {
    register_post_type( BC_CPT, [
        'labels'              => [
            'name'          => __( 'BannerCast Messages', 'bannercast' ),
            'singular_name' => __( 'BannerCast Message',  'bannercast' ),
        ],
        'public'              => false,
        'show_ui'             => false,
        'show_in_menu'        => false,
        'supports'            => [ 'title' ],
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
    ]);
}

/* ═══════════════════════════════════════════════
   TOP-LEVEL ADMIN MENU
═══════════════════════════════════════════════ */
add_action( 'admin_menu', 'bc_admin_menu' );
function bc_admin_menu() {
    add_menu_page(
        __( 'BannerCast', 'bannercast' ),
        __( 'BannerCast', 'bannercast' ),
        'manage_options',
        'bannercast',
        'bc_page_list',
        'dashicons-megaphone',
        25
    );
    add_submenu_page(
        'bannercast',
        __( 'All Messages', 'bannercast' ),
        __( 'All Messages', 'bannercast' ),
        'manage_options',
        'bannercast',
        'bc_page_list'
    );
    add_submenu_page(
        'bannercast',
        __( 'Add New Message', 'bannercast' ),
        __( 'Add New', 'bannercast' ),
        'manage_options',
        'bannercast-new',
        'bc_page_edit'
    );
}

/* ═══════════════════════════════════════════════
   ASSETS
═══════════════════════════════════════════════ */
add_action( 'wp_enqueue_scripts',    'bc_frontend_assets' );
add_action( 'admin_enqueue_scripts', 'bc_admin_assets' );

function bc_frontend_assets() {
    wp_enqueue_style(  'bannercast', BC_URL . 'assets/notice-bar.css', [], BC_VERSION );
    wp_enqueue_script( 'bannercast', BC_URL . 'assets/notice-bar.js',  ['jquery'], BC_VERSION, true );
}

function bc_admin_assets( $hook ) {
    $screen = get_current_screen();
    if ( ! $screen || strpos( $screen->id, 'bannercast' ) === false ) return;
    wp_enqueue_media();
    wp_enqueue_style(  'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );
    wp_enqueue_style(  'bc-admin', BC_URL . 'assets/admin.css', [], BC_VERSION );
    wp_enqueue_script( 'bc-admin', BC_URL . 'assets/admin.js', ['jquery','wp-color-picker','jquery-ui-sortable'], BC_VERSION, true );
    wp_localize_script( 'bc-admin', 'BC_ADMIN', [
        'nonce'        => wp_create_nonce( 'bc_nonce' ),
        'ajaxurl'      => admin_url( 'admin-ajax.php' ),
        'media_title'  => __( 'Choose Background Image', 'bannercast' ),
        'media_button' => __( 'Use this image', 'bannercast' ),
        'confirm_del'  => __( 'Delete this message?', 'bannercast' ),
    ]);
}

/* ═══════════════════════════════════════════════
   MESSAGE META DEFAULTS
═══════════════════════════════════════════════ */
function bc_meta_defaults() {
    return [
        // Content
        'text'           => '',
        'enabled'        => 1,
        // Display
        'display_on'     => 'all',       // all | selected | shortcode_only
        'selected_posts' => [],
        'position'       => 'top',       // top | bottom
        // Scroll
        'scroll_enabled' => 1,
        'scroll_speed'   => 50,
        // Style
        'bg_color'       => '#1a73e8',
        'bg_image'       => '',
        'bg_image_id'    => '',
        'font_color'     => '#ffffff',
        'font_size'      => '15',
        'font_family'    => 'inherit',
        'font_weight'    => 'normal',
        'bar_height'     => '44',
        'padding_x'      => '20',
        'border_color'   => '',
        'border_width'   => '0',
        'custom_css'     => '',
        // Interaction
        'close_button'   => 1,
        'link_url'       => '',
        'link_target'    => '_self',
        // Order
        'sort_order'     => 0,
    ];
}

function bc_get_meta( $post_id ) {
    $saved = get_post_meta( $post_id, '_bc_meta', true );
    return wp_parse_args( is_array($saved) ? $saved : [], bc_meta_defaults() );
}

/* ═══════════════════════════════════════════════
   AJAX: SAVE MESSAGE
═══════════════════════════════════════════════ */
add_action( 'wp_ajax_bc_save_message', 'bc_ajax_save_message' );
function bc_ajax_save_message() {
    check_ajax_referer( 'bc_nonce', 'nonce' );
    if ( ! current_user_can('manage_options') ) wp_die('Forbidden');

    $data    = $_POST['data'] ?? [];
    $post_id = intval( $data['post_id'] ?? 0 );
    $title   = sanitize_text_field( $data['title'] ?? 'BannerCast Message' );

    if ( $post_id ) {
        wp_update_post([ 'ID' => $post_id, 'post_title' => $title, 'post_status' => 'publish', 'post_type' => BC_CPT ]);
    } else {
        $post_id = wp_insert_post([ 'post_title' => $title, 'post_status' => 'publish', 'post_type' => BC_CPT ]);
    }

    if ( is_wp_error($post_id) ) {
        wp_send_json_error('Could not save post');
    }

    $meta = [
        'text'           => wp_kses_post( $data['text'] ?? '' ),
        'enabled'        => isset($data['enabled']) ? 1 : 0,
        'display_on'     => in_array($data['display_on']??'', ['all','selected','shortcode_only']) ? $data['display_on'] : 'all',
        'selected_posts' => array_map('intval', (array)($data['selected_posts'] ?? [])),
        'position'       => in_array($data['position']??'', ['top','bottom']) ? $data['position'] : 'top',
        'scroll_enabled' => isset($data['scroll_enabled']) ? 1 : 0,
        'scroll_speed'   => absint($data['scroll_speed'] ?? 50),
        'bg_color'       => sanitize_hex_color($data['bg_color'] ?? '#1a73e8') ?: '#1a73e8',
        'bg_image'       => esc_url_raw($data['bg_image'] ?? ''),
        'bg_image_id'    => absint($data['bg_image_id'] ?? 0),
        'font_color'     => sanitize_hex_color($data['font_color'] ?? '#ffffff') ?: '#ffffff',
        'font_size'      => absint($data['font_size'] ?? 15),
        'font_family'    => sanitize_text_field($data['font_family'] ?? 'inherit'),
        'font_weight'    => in_array($data['font_weight']??'', ['normal','bold','600']) ? $data['font_weight'] : 'normal',
        'bar_height'     => absint($data['bar_height'] ?? 44),
        'padding_x'      => absint($data['padding_x'] ?? 20),
        'border_color'   => sanitize_hex_color($data['border_color'] ?? '') ?: '',
        'border_width'   => absint($data['border_width'] ?? 0),
        'custom_css'     => sanitize_text_field($data['custom_css'] ?? ''),
        'close_button'   => isset($data['close_button']) ? 1 : 0,
        'link_url'       => esc_url_raw($data['link_url'] ?? ''),
        'link_target'    => ($data['link_target']??'_self') === '_blank' ? '_blank' : '_self',
        'sort_order'     => absint($data['sort_order'] ?? 0),
    ];

    update_post_meta( $post_id, '_bc_meta', $meta );

    wp_send_json_success([
        'post_id'   => $post_id,
        'shortcode' => '[bannercast id="' . $post_id . '"]',
        'message'   => __('Saved!', 'bannercast'),
    ]);
}

/* ═══════════════════════════════════════════════
   AJAX: DELETE MESSAGE
═══════════════════════════════════════════════ */
add_action( 'wp_ajax_bc_delete_message', 'bc_ajax_delete_message' );
function bc_ajax_delete_message() {
    check_ajax_referer( 'bc_nonce', 'nonce' );
    if ( ! current_user_can('manage_options') ) wp_die('Forbidden');
    $id = intval($_POST['post_id'] ?? 0);
    if ( $id && get_post_type($id) === BC_CPT ) {
        wp_delete_post($id, true);
        wp_send_json_success();
    }
    wp_send_json_error('Invalid');
}

/* ═══════════════════════════════════════════════
   AJAX: TOGGLE ENABLED
═══════════════════════════════════════════════ */
add_action( 'wp_ajax_bc_toggle_message', 'bc_ajax_toggle_message' );
function bc_ajax_toggle_message() {
    check_ajax_referer( 'bc_nonce', 'nonce' );
    if ( ! current_user_can('manage_options') ) wp_die('Forbidden');
    $id   = intval($_POST['post_id'] ?? 0);
    $meta = bc_get_meta($id);
    $meta['enabled'] = $meta['enabled'] ? 0 : 1;
    update_post_meta($id, '_bc_meta', $meta);
    wp_send_json_success(['enabled' => $meta['enabled']]);
}

/* ═══════════════════════════════════════════════
   FRONTEND RENDER
═══════════════════════════════════════════════ */
add_action( 'wp_body_open', 'bc_render_auto', 5 );
add_action( 'wp_footer',    'bc_render_auto', 5 );

function bc_render_auto() {
    global $post;
    $messages = bc_get_active_messages();
    if ( empty($messages) ) return;

    $doing_footer = did_action('wp_body_open') > 0 && current_filter() === 'wp_footer';

    foreach ( $messages as $msg ) {
        $meta = bc_get_meta($msg->ID);

        // Skip shortcode-only
        if ( $meta['display_on'] === 'shortcode_only' ) continue;

        // Position filter
        if ( current_filter() === 'wp_body_open'  && $meta['position'] !== 'top' )    continue;
        if ( current_filter() === 'wp_footer'     && $meta['position'] !== 'bottom' ) continue;

        // Page targeting
        if ( $meta['display_on'] === 'selected' ) {
            if ( ! $post || ! in_array($post->ID, (array)$meta['selected_posts']) ) continue;
        }

        echo bc_build_html( $msg->ID, $meta );
    }
}

function bc_get_active_messages() {
    return get_posts([
        'post_type'      => BC_CPT,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_key'       => '_bc_meta',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
    ]);
}

function bc_build_html( $id, $meta, $shortcode = false ) {
    if ( empty($meta['enabled']) && ! $shortcode ) return '';
    $text = trim($meta['text'] ?? '');
    if ( $text === '' ) return '';

    /* ── Inline styles ── */
    $bar_style  = "min-height:{$meta['bar_height']}px;";
    $bar_style .= "background-color:{$meta['bg_color']};";
    $bar_style .= "padding:0 {$meta['padding_x']}px;";
    if ( ! empty($meta['bg_image']) ) {
        $bar_style .= "background-image:url('{$meta['bg_image']}');background-size:cover;background-position:center;";
    }
    if ( ! empty($meta['border_width']) && $meta['border_width'] > 0 ) {
        $side = $meta['position'] === 'bottom' ? 'top' : 'bottom';
        $bar_style .= "border-{$side}:{$meta['border_width']}px solid {$meta['border_color']};";
    }
    if ( ! empty($meta['custom_css']) ) {
        $bar_style .= esc_attr($meta['custom_css']);
    }

    $txt_style  = "color:{$meta['font_color']};";
    $txt_style .= "font-size:{$meta['font_size']}px;";
    $txt_style .= "font-weight:{$meta['font_weight']};";
    if ( $meta['font_family'] !== 'inherit' ) {
        $txt_style .= "font-family:{$meta['font_family']};";
    }

    /* ── Position class ── */
    $pos_class  = $shortcode ? 'nb-inline' : 'nb-' . esc_attr($meta['position']);
    $scr_class  = ! empty($meta['scroll_enabled']) ? 'nb-scroll' : 'nb-static';

    /* ── Content ── */
    $content_html = wp_kses_post($text);

    if ( ! empty($meta['scroll_enabled']) ) {
        $inner = '<span class="nb-message-item">' . $content_html . '</span>';
        // duplicate for seamless loop
        $body = '<div class="nb-ticker" style="' . esc_attr($txt_style) . '">'
              . '<div class="nb-ticker-inner" data-speed="' . esc_attr($meta['scroll_speed']) . '">'
              . $inner . $inner
              . '</div></div>';
    } else {
        $body = '<div class="nb-static-text" style="' . esc_attr($txt_style) . '">' . $content_html . '</div>';
    }

    /* ── Link wrap ── */
    if ( ! empty($meta['link_url']) ) {
        $body = '<a href="' . esc_url($meta['link_url']) . '" target="' . esc_attr($meta['link_target']) . '" class="nb-link">' . $body . '</a>';
    }

    /* ── Close button ── */
    $close = ! empty($meta['close_button'])
        ? '<button class="nb-close" aria-label="Close" style="color:' . esc_attr($meta['font_color']) . '">&times;</button>'
        : '';

    return '<div id="nb-bar-' . $id . '" class="notice-bar ' . $pos_class . ' ' . $scr_class . '" '
         . 'style="' . esc_attr($bar_style) . '" '
         . 'data-id="' . $id . '" role="banner" aria-live="polite">'
         . $body . $close
         . '</div>';
}

/* ═══════════════════════════════════════════════
   SHORTCODE  [bannercast id="X"]
═══════════════════════════════════════════════ */
add_shortcode( 'bannercast', 'bc_shortcode' );
function bc_shortcode( $atts ) {
    $atts = shortcode_atts(['id' => 0], $atts, 'bannercast');
    $id   = intval($atts['id']);
    if ( ! $id || get_post_type($id) !== BC_CPT ) return '';
    $meta = bc_get_meta($id);
    return bc_build_html($id, $meta, true);
}

/* ═══════════════════════════════════════════════
   ADMIN PAGE: MESSAGE LIST
═══════════════════════════════════════════════ */
function bc_page_list() {
    $messages = get_posts([
        'post_type'      => BC_CPT,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);
    ?>
    <div class="wrap nb-wrap">
        <div class="nb-page-header">
            <h1><span class="dashicons dashicons-megaphone"></span> <?php _e('BannerCast Messages', 'bannercast'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=notice-bar-new'); ?>" class="page-title-action nb-btn-primary">
                + <?php _e('Add New Message', 'bannercast'); ?>
            </a>
        </div>

        <?php if ( empty($messages) ) : ?>
        <div class="nb-empty-state">
            <span class="dashicons dashicons-megaphone"></span>
            <h2><?php _e('No messages yet', 'bannercast'); ?></h2>
            <p><?php _e('Create your first notice bar message to get started.', 'bannercast'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=notice-bar-new'); ?>" class="button button-primary button-large">
                <?php _e('Create First Message', 'bannercast'); ?>
            </a>
        </div>
        <?php else : ?>

        <div class="nb-message-grid">
            <?php foreach ( $messages as $msg ) :
                $meta = bc_get_meta($msg->ID);
                $sc   = '[bannercast id="' . $msg->ID . '"]';
                $pos_label  = $meta['position'] === 'top' ? '⬆ Top' : '⬇ Bottom';
                $scr_label  = $meta['scroll_enabled'] ? '↔ Scrolling' : '— Static';
                $disp_label = ['all'=>'🌐 All Pages','selected'=>'📄 Selected','shortcode_only'=>'📋 Shortcode only'][$meta['display_on']] ?? '';
            ?>
            <div class="nb-card nb-msg-card <?php echo $meta['enabled'] ? 'is-active' : 'is-inactive'; ?>" data-id="<?php echo $msg->ID; ?>">

                <!-- Preview strip -->
                <div class="nb-card-preview" style="
                    background-color:<?php echo esc_attr($meta['bg_color']); ?>;
                    <?php if($meta['bg_image']): ?>background-image:url('<?php echo esc_url($meta['bg_image']); ?>');background-size:cover;<?php endif; ?>
                    color:<?php echo esc_attr($meta['font_color']); ?>;
                    font-size:<?php echo esc_attr($meta['font_size']); ?>px;
                    font-family:<?php echo esc_attr($meta['font_family']); ?>;
                    font-weight:<?php echo esc_attr($meta['font_weight']); ?>;
                    height:<?php echo esc_attr($meta['bar_height']); ?>px;
                ">
                    <div class="nb-card-preview-text <?php echo $meta['scroll_enabled'] ? 'is-scrolling' : ''; ?>">
                        <span><?php echo esc_html( wp_strip_all_tags($meta['text']) ); ?></span>
                        <?php if($meta['scroll_enabled']): ?><span class="nb-preview-dup">&nbsp;&nbsp;›&nbsp;&nbsp;<?php echo esc_html(wp_strip_all_tags($meta['text'])); ?></span><?php endif; ?>
                    </div>
                </div>

                <!-- Card body -->
                <div class="nb-card-body">
                    <div class="nb-card-title-row">
                        <h3 class="nb-card-title"><?php echo esc_html($msg->post_title); ?></h3>
                        <label class="nb-toggle" title="<?php esc_attr_e('Enable / Disable','bannercast'); ?>">
                            <input type="checkbox" class="nb-toggle-enabled" data-id="<?php echo $msg->ID; ?>" <?php checked($meta['enabled'], 1); ?>>
                            <span></span>
                        </label>
                    </div>

                    <div class="nb-card-badges">
                        <span class="nb-badge"><?php echo esc_html($pos_label); ?></span>
                        <span class="nb-badge"><?php echo esc_html($scr_label); ?></span>
                        <span class="nb-badge"><?php echo esc_html($disp_label); ?></span>
                    </div>

                    <!-- Shortcode -->
                    <div class="nb-sc-row">
                        <code class="nb-sc-code"><?php echo esc_html($sc); ?></code>
                        <button class="nb-copy-btn" data-sc="<?php echo esc_attr($sc); ?>" title="Copy shortcode">
                            <span class="dashicons dashicons-clipboard"></span>
                        </button>
                    </div>

                    <!-- Actions -->
                    <div class="nb-card-actions">
                        <a href="<?php echo admin_url('admin.php?page=notice-bar-new&edit=' . $msg->ID); ?>" class="button">
                            ✏️ <?php _e('Edit', 'bannercast'); ?>
                        </a>
                        <button class="button nb-delete-btn" data-id="<?php echo $msg->ID; ?>">
                            🗑 <?php _e('Delete', 'bannercast'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>
    </div>
    <?php
}

/* ═══════════════════════════════════════════════
   ADMIN PAGE: ADD / EDIT MESSAGE
═══════════════════════════════════════════════ */
function bc_page_edit() {
    $edit_id = intval($_GET['edit'] ?? 0);
    $meta    = $edit_id ? bc_get_meta($edit_id) : bc_meta_defaults();
    $title   = $edit_id ? get_the_title($edit_id) : '';

    $all_pages = get_pages();
    $all_posts = get_posts(['numberposts' => -1, 'post_status' => 'publish']);
    $all_items = array_merge($all_pages, $all_posts);

    $fonts = [
        'inherit'                  => 'Theme Default',
        'Arial, sans-serif'        => 'Arial',
        'Georgia, serif'           => 'Georgia',
        'Verdana, sans-serif'      => 'Verdana',
        '"Trebuchet MS", sans-serif' => 'Trebuchet MS',
        '"Courier New", monospace' => 'Courier New',
        '"Times New Roman", serif' => 'Times New Roman',
    ];
    ?>
    <div class="wrap nb-wrap">
        <div class="nb-page-header">
            <h1>
                <span class="dashicons dashicons-megaphone"></span>
                <?php echo $edit_id ? __('Edit Message', 'bannercast') : __('Add New Message', 'bannercast'); ?>
            </h1>
            <a href="<?php echo admin_url('admin.php?page=notice-bar'); ?>" class="button">← <?php _e('All Messages', 'bannercast'); ?></a>
        </div>

        <div class="nb-edit-layout">

            <!-- ══ FORM ══ -->
            <div class="nb-edit-main">
                <input type="hidden" id="nb-post-id" value="<?php echo $edit_id; ?>">

                <!-- CONTENT -->
                <div class="nb-card">
                    <h2><?php _e('Message Content', 'bannercast'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Internal Name', 'bannercast'); ?></th>
                            <td><input type="text" id="nb-title" value="<?php echo esc_attr($title); ?>" class="large-text" placeholder="e.g. Summer Sale Banner"></td>
                        </tr>
                        <tr>
                            <th><?php _e('Message Text', 'bannercast'); ?><br><small><?php _e('HTML allowed', 'bannercast'); ?></small></th>
                            <td>
                                <textarea id="nb-text" rows="4" class="large-text"><?php echo esc_textarea($meta['text']); ?></textarea>
                                <p class="description"><?php _e('You can use HTML: &lt;strong&gt;, &lt;em&gt;, &lt;a href="…"&gt;, emojis, etc.', 'bannercast'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Enabled', 'bannercast'); ?></th>
                            <td><label class="nb-toggle"><input type="checkbox" id="nb-enabled" <?php checked($meta['enabled'], 1); ?>><span></span></label></td>
                        </tr>
                    </table>
                </div>

                <!-- DISPLAY -->
                <div class="nb-card">
                    <h2><?php _e('Display Settings', 'bannercast'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Position', 'bannercast'); ?></th>
                            <td>
                                <div class="nb-radio-group">
                                    <label class="nb-radio-card <?php echo $meta['position']==='top'?'is-selected':''; ?>">
                                        <input type="radio" name="nb-position" value="top" <?php checked($meta['position'],'top'); ?>>
                                        <span class="dashicons dashicons-arrow-up-alt"></span> Top
                                    </label>
                                    <label class="nb-radio-card <?php echo $meta['position']==='bottom'?'is-selected':''; ?>">
                                        <input type="radio" name="nb-position" value="bottom" <?php checked($meta['position'],'bottom'); ?>>
                                        <span class="dashicons dashicons-arrow-down-alt"></span> Bottom
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Show On', 'bannercast'); ?></th>
                            <td>
                                <select id="nb-display-on">
                                    <option value="all"            <?php selected($meta['display_on'],'all'); ?>><?php _e('All Pages & Posts', 'bannercast'); ?></option>
                                    <option value="selected"       <?php selected($meta['display_on'],'selected'); ?>><?php _e('Selected Pages / Posts', 'bannercast'); ?></option>
                                    <option value="shortcode_only" <?php selected($meta['display_on'],'shortcode_only'); ?>><?php _e('Shortcode Only', 'bannercast'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr id="nb-selected-wrap" <?php echo $meta['display_on']!=='selected' ? 'style="display:none"' : ''; ?>>
                            <th><?php _e('Choose Pages / Posts', 'bannercast'); ?></th>
                            <td>
                                <div class="nb-post-list">
                                    <?php foreach($all_items as $p): ?>
                                    <label>
                                        <input type="checkbox" class="nb-selected-post" value="<?php echo $p->ID; ?>"
                                            <?php checked(in_array($p->ID, (array)$meta['selected_posts']), true); ?>>
                                        <?php echo esc_html($p->post_title); ?>
                                        <span class="nb-type-badge"><?php echo esc_html($p->post_type); ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Close Button', 'bannercast'); ?></th>
                            <td><label class="nb-toggle"><input type="checkbox" id="nb-close-button" <?php checked($meta['close_button'], 1); ?>><span></span></label></td>
                        </tr>
                        <tr>
                            <th><?php _e('Click-through URL', 'bannercast'); ?></th>
                            <td>
                                <input type="url" id="nb-link-url" value="<?php echo esc_attr($meta['link_url']); ?>" class="regular-text" placeholder="https://">
                                <label style="margin-left:12px">
                                    <input type="checkbox" id="nb-link-target" <?php checked($meta['link_target'],'_blank'); ?>> Open in new tab
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- SCROLL -->
                <div class="nb-card">
                    <h2><?php _e('Scroll / Ticker', 'bannercast'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Enable Scrolling', 'bannercast'); ?></th>
                            <td><label class="nb-toggle"><input type="checkbox" id="nb-scroll-enabled" <?php checked($meta['scroll_enabled'], 1); ?>><span></span></label></td>
                        </tr>
                        <tr id="nb-speed-row" <?php echo empty($meta['scroll_enabled']) ? 'style="display:none"' : ''; ?>>
                            <th><?php _e('Scroll Speed', 'bannercast'); ?></th>
                            <td>
                                <div class="nb-range-wrap">
                                    <span class="nb-range-label">Slow</span>
                                    <input type="range" id="nb-scroll-speed" min="10" max="200" value="<?php echo esc_attr($meta['scroll_speed']); ?>">
                                    <span class="nb-range-label">Fast</span>
                                    <span class="nb-range-val" id="nb-speed-val"><?php echo esc_html($meta['scroll_speed']); ?> px/s</span>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- STYLE -->
                <div class="nb-card">
                    <h2><?php _e('Style', 'bannercast'); ?></h2>
                    <div class="nb-style-grid">

                        <div class="nb-style-group">
                            <label><?php _e('Background Colour', 'bannercast'); ?></label>
                            <input type="text" id="nb-bg-color" value="<?php echo esc_attr($meta['bg_color']); ?>" class="nb-color-picker">
                        </div>

                        <div class="nb-style-group">
                            <label><?php _e('Text Colour', 'bannercast'); ?></label>
                            <input type="text" id="nb-font-color" value="<?php echo esc_attr($meta['font_color']); ?>" class="nb-color-picker">
                        </div>

                        <div class="nb-style-group nb-style-full">
                            <label><?php _e('Background Image', 'bannercast'); ?></label>
                            <div class="nb-media-wrap">
                                <img id="nb-bg-preview" src="<?php echo esc_url($meta['bg_image']); ?>" style="<?php echo $meta['bg_image'] ? '' : 'display:none;'; ?>max-height:56px;border-radius:4px;margin-right:10px;">
                                <input type="hidden" id="nb-bg-image"    value="<?php echo esc_attr($meta['bg_image']); ?>">
                                <input type="hidden" id="nb-bg-image-id" value="<?php echo esc_attr($meta['bg_image_id']); ?>">
                                <button type="button" id="nb-upload-bg" class="button"><?php _e('Select Image', 'bannercast'); ?></button>
                                <button type="button" id="nb-remove-bg" class="button" <?php echo empty($meta['bg_image']) ? 'style="display:none"' : ''; ?>><?php _e('Remove', 'bannercast'); ?></button>
                            </div>
                        </div>

                        <div class="nb-style-group">
                            <label><?php _e('Font Family', 'bannercast'); ?></label>
                            <select id="nb-font-family">
                                <?php foreach($fonts as $val=>$lbl): ?>
                                <option value="<?php echo esc_attr($val); ?>" <?php selected($meta['font_family'],$val); ?>><?php echo esc_html($lbl); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="nb-style-group">
                            <label><?php _e('Font Weight', 'bannercast'); ?></label>
                            <select id="nb-font-weight">
                                <option value="normal" <?php selected($meta['font_weight'],'normal'); ?>>Normal</option>
                                <option value="600"    <?php selected($meta['font_weight'],'600'); ?>>Semi Bold</option>
                                <option value="bold"   <?php selected($meta['font_weight'],'bold'); ?>>Bold</option>
                            </select>
                        </div>

                        <div class="nb-style-group">
                            <label><?php _e('Font Size (px)', 'bannercast'); ?></label>
                            <input type="number" id="nb-font-size" value="<?php echo esc_attr($meta['font_size']); ?>" min="10" max="60" class="small-text">
                        </div>

                        <div class="nb-style-group">
                            <label><?php _e('Bar Height (px)', 'bannercast'); ?></label>
                            <input type="number" id="nb-bar-height" value="<?php echo esc_attr($meta['bar_height']); ?>" min="28" max="200" class="small-text">
                        </div>

                        <div class="nb-style-group">
                            <label><?php _e('Padding X (px)', 'bannercast'); ?></label>
                            <input type="number" id="nb-padding-x" value="<?php echo esc_attr($meta['padding_x']); ?>" min="0" max="200" class="small-text">
                        </div>

                        <div class="nb-style-group">
                            <label><?php _e('Border Width (px)', 'bannercast'); ?></label>
                            <input type="number" id="nb-border-width" value="<?php echo esc_attr($meta['border_width']); ?>" min="0" max="20" class="small-text">
                        </div>

                        <div class="nb-style-group">
                            <label><?php _e('Border Colour', 'bannercast'); ?></label>
                            <input type="text" id="nb-border-color" value="<?php echo esc_attr($meta['border_color']); ?>" class="nb-color-picker">
                        </div>

                        <div class="nb-style-group nb-style-full">
                            <label><?php _e('Extra CSS (applied inline to bar)', 'bannercast'); ?></label>
                            <input type="text" id="nb-custom-css" value="<?php echo esc_attr($meta['custom_css']); ?>" class="large-text" placeholder="e.g. letter-spacing:2px; text-transform:uppercase;">
                        </div>

                    </div>
                </div>

                <!-- SAVE -->
                <div class="nb-save-row">
                    <button type="button" id="nb-save-btn" class="button button-primary button-large">
                        💾 <?php _e('Save Message', 'bannercast'); ?>
                    </button>
                    <span id="nb-save-feedback"></span>
                </div>
            </div>

            <!-- ══ LIVE PREVIEW ══ -->
            <div class="nb-edit-sidebar">
                <div class="nb-card nb-sticky-card">
                    <h2><?php _e('Live Preview', 'bannercast'); ?></h2>
                    <div class="nb-preview-frame">
                        <div id="nb-live-preview" class="notice-bar nb-inline nb-scroll" style="min-height:44px;overflow:hidden;background:#1a73e8;color:#fff;">
                            <div class="nb-ticker">
                                <div class="nb-ticker-inner" id="nb-preview-inner">
                                    <span class="nb-message-item">Your message preview…</span>
                                    <span class="nb-message-item">Your message preview…</span>
                                </div>
                            </div>
                            <button class="nb-close" style="color:#fff">&times;</button>
                        </div>
                    </div>

                    <!-- SHORTCODE -->
                    <div style="margin-top:16px;">
                        <h3 style="margin:0 0 8px;"><?php _e('Shortcode', 'bannercast'); ?></h3>
                        <?php if($edit_id): ?>
                        <div class="nb-sc-row">
                            <code class="nb-sc-code" id="nb-shortcode-display">[bannercast id="<?php echo $edit_id; ?>"]</code>
                            <button class="nb-copy-btn" data-sc='[bannercast id="<?php echo $edit_id; ?>"]' id="nb-sc-copy">
                                <span class="dashicons dashicons-clipboard"></span>
                            </button>
                        </div>
                        <?php else: ?>
                        <p class="description"><?php _e('Save the message first to get its shortcode.', 'bannercast'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div><!-- .nb-edit-layout -->
    </div>
    <?php
}
