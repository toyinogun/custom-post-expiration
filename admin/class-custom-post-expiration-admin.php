<?php

class Custom_Post_Expiration_Admin {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/custom-post-expiration-admin.css', array(), $this->version, 'all' );
    }

    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/custom-post-expiration-admin.js', array( 'jquery' ), $this->version, false );
    }

    public function add_expiration_meta_box() {
        add_meta_box(
            'cpen_expiration_datetime',
            __( 'Post Expiration', 'custom-post-expiration' ),
            array( $this, 'render_expiration_meta_box' ),
            array( 'post', 'page' ),
            'side',
            'high'
        );
    }

    public function render_expiration_meta_box( $post ) {
        wp_nonce_field( 'cpen_expiration_datetime', 'cpen_expiration_datetime_nonce' );

        $expiration_datetime = get_post_meta( $post->ID, '_cpen_expiration_datetime', true );
        $expiration_date = '';
        $expiration_time = '';
        
        if ( $expiration_datetime ) {
            $datetime = new DateTime( $expiration_datetime );
            $expiration_date = $datetime->format( 'Y-m-d' );
            $expiration_time = $datetime->format( 'H:i' );
        }

        $current_datetime = new DateTime( current_time( 'mysql' ) );
        $current_date = $current_datetime->format( 'Y-m-d' );
        $current_time = $current_datetime->format( 'H:i' );

        ?>
        <p>
            <strong><?php _e( 'Current Date/Time:', 'custom-post-expiration' ); ?></strong>
            <span id="cpen_current_datetime"><?php echo esc_html( $current_date . ' ' . $current_time ); ?></span>
        </p>
        <p>
            <label for="cpen_expiration_date"><?php _e( 'Expiration Date:', 'custom-post-expiration' ); ?></label>
            <input type="date" id="cpen_expiration_date" name="cpen_expiration_date" value="<?php echo esc_attr( $expiration_date ); ?>">
        </p>
        <p>
            <label for="cpen_expiration_time"><?php _e( 'Expiration Time:', 'custom-post-expiration' ); ?></label>
            <input type="time" id="cpen_expiration_time" name="cpen_expiration_time" value="<?php echo esc_attr( $expiration_time ); ?>">
        </p>
        <?php
    }

    public function save_expiration_datetime( $post_id ) {
        if ( ! isset( $_POST['cpen_expiration_datetime_nonce'] ) || ! wp_verify_nonce( $_POST['cpen_expiration_datetime_nonce'], 'cpen_expiration_datetime' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $post_id ) ) {
                return;
            }
        } else {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }
        }

        if ( isset( $_POST['cpen_expiration_date'] ) && isset( $_POST['cpen_expiration_time'] ) ) {
            $expiration_date = sanitize_text_field( $_POST['cpen_expiration_date'] );
            $expiration_time = sanitize_text_field( $_POST['cpen_expiration_time'] );
            
            if ( $expiration_date && $expiration_time ) {
                $expiration_datetime = $expiration_date . ' ' . $expiration_time . ':00';
                update_post_meta( $post_id, '_cpen_expiration_datetime', $expiration_datetime );
            } else {
                delete_post_meta( $post_id, '_cpen_expiration_datetime' );
            }
        }
    }

    public function daily_expiration_check() {
        $this->expiration_check(true);
    }

    public function expiration_check($is_daily = false) {
        $args = array(
            'post_type' => array('post', 'page'),
            'meta_query' => array(
                array(
                    'key' => '_cpen_expiration_datetime',
                    'value' => current_time('mysql'),
                    'compare' => '<=',
                    'type' => 'DATETIME'
                )
            ),
            'posts_per_page' => -1
        );

        $expired_posts = new WP_Query($args);

        if ($expired_posts->have_posts()) {
            while ($expired_posts->have_posts()) {
                $expired_posts->the_post();
                $post_id = get_the_ID();

                $expiration_action = get_option('cpen_expiration_action', 'draft');

                switch ($expiration_action) {
                    case 'trash':
                        wp_trash_post($post_id);
                        break;
                    case 'delete':
                        wp_delete_post($post_id, true);
                        break;
                    case 'draft':
                    default:
                        wp_update_post(array('ID' => $post_id, 'post_status' => 'draft'));
                        break;
                }

                if (get_option('cpen_send_notification', false)) {
                    $this->send_expiration_notification($post_id);
                }
            }
        }

        wp_reset_postdata();
    }

    private function send_expiration_notification($post_id) {
        $to = get_option('cpen_notification_email', get_option('admin_email'));
        $subject = sprintf(__('Post Expired: %s', 'custom-post-expiration'), get_the_title($post_id));
        $message = sprintf(__('The following post has expired: %s', 'custom-post-expiration'), get_permalink($post_id));
        
        wp_mail($to, $subject, $message);
    }

    public function add_plugin_admin_menu() {
        add_options_page(
            __('Custom Post Expiration Settings', 'custom-post-expiration'),
            __('Post Expiration', 'custom-post-expiration'),
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_setup_page')
        );
    }

    public function display_plugin_setup_page() {
        include_once('partials/custom-post-expiration-admin-display.php');
    }

    public function register_settings() {
        add_settings_section(
            'cpen_general_settings',
            __('General Settings', 'custom-post-expiration'),
            array($this, 'cpen_general_settings_callback'),
            $this->plugin_name
        );

        add_settings_field(
            'cpen_expiration_action',
            __('Expiration Action', 'custom-post-expiration'),
            array($this, 'cpen_expiration_action_callback'),
            $this->plugin_name,
            'cpen_general_settings'
        );

        add_settings_field(
            'cpen_send_notification',
            __('Send Notification', 'custom-post-expiration'),
            array($this, 'cpen_send_notification_callback'),
            $this->plugin_name,
            'cpen_general_settings'
        );

        add_settings_field(
            'cpen_notification_email',
            __('Notification Email', 'custom-post-expiration'),
            array($this, 'cpen_notification_email_callback'),
            $this->plugin_name,
            'cpen_general_settings'
        );

        register_setting($this->plugin_name, 'cpen_expiration_action');
        register_setting($this->plugin_name, 'cpen_send_notification');
        register_setting($this->plugin_name, 'cpen_notification_email', array($this, 'validate_email'));
    }

    public function cpen_general_settings_callback() {
        echo '<p>' . __('Configure the general settings for post expiration.', 'custom-post-expiration') . '</p>';
    }

    public function cpen_expiration_action_callback() {
        $expiration_action = get_option('cpen_expiration_action', 'draft');
        ?>
        <select name="cpen_expiration_action">
            <option value="draft" <?php selected($expiration_action, 'draft'); ?>><?php _e('Set to Draft', 'custom-post-expiration'); ?></option>
            <option value="trash" <?php selected($expiration_action, 'trash'); ?>><?php _e('Move to Trash', 'custom-post-expiration'); ?></option>
            <option value="delete" <?php selected($expiration_action, 'delete'); ?>><?php _e('Delete Permanently', 'custom-post-expiration'); ?></option>
            </select>
        <?php
    }

    public function cpen_send_notification_callback() {
        $send_notification = get_option('cpen_send_notification', false);
        ?>
        <input type="checkbox" name="cpen_send_notification" value="1" <?php checked($send_notification, 1); ?>>
        <?php _e('Send email notification when a post expires', 'custom-post-expiration'); ?>
        <?php
    }

    public function cpen_notification_email_callback() {
        $notification_email = get_option('cpen_notification_email', get_option('admin_email'));
        ?>
        <input type="email" name="cpen_notification_email" value="<?php echo esc_attr($notification_email); ?>" class="regular-text">
        <?php
    }

    public function validate_email($input) {
        if (!is_email($input)) {
            add_settings_error('cpen_notification_email', 'invalid-email', __('Invalid email address', 'custom-post-expiration'));
            return get_option('cpen_notification_email');
        }
        return $input;
    }
}