<?php

/**
 * Plugin Name: Goftino
 * Author: Ali Rahimi
 * Plugin URI: https://www.goftino.com
 * Description: افزونه گفتینو | با کاربران خود آنلاین صحبت کنید ، پشتیبانی کنید و از فروش بیشتر لذت ببرید
 * Version: 1.2
 *
 * Text Domain:   goftino
 * Domain Path:   /
 */
if (!defined('ABSPATH')) {
    die("Error!");
}

load_plugin_textdomain('goftino');
define("GOFTINO_IMG_URL", plugin_dir_url(__FILE__) . "/img/");
register_activation_hook(__FILE__, 'goftinoInstall');
register_deactivation_hook(__FILE__, 'goftinoDelete');

function menu_goftino() {
    load_plugin_textdomain('goftino');
    add_menu_page(__('گفتینو', 'goftino'), __('گفتینو', 'goftino'), 'manage_options', basename(__FILE__), 'goftinoPreferences', GOFTINO_IMG_URL . "logo-m.png");
}
add_action('admin_menu', 'menu_goftino');
function goftino_validate($a) {
    return $a;
}
add_action('admin_init', 'goftino_register_settings');
function goftino_register_settings() {
    register_setting('goftino_send_userdata', 'goftino_send_userdata', 'goftino_validate');
    register_setting('goftino_widget_id', 'goftino_widget_id', 'goftino_validate');
}
add_action('admin_post_wp_save_goftino', 'wp_save_goftino');
add_action('admin_post_nopriv_wp_save_goftino', 'wp_save_goftino');
add_action('wp_footer', 'goftinoAppend', 100000);

function goftinoInstall() {
    return goftino::getInstance()->install();
}
function goftinoDelete() {
    return goftino::getInstance()->delete();
}
function goftinoAppend() {
    echo goftino::getInstance()->append(goftino::getInstance()->getId(),goftino::getInstance()->getData());
}
function goftinoPreferences() {
    if (isset($_POST["widget_id"]) || isset($_POST["send_userdata"])) {
        goftino::getInstance()->save();
    }
    load_plugin_textdomain('goftino');
    wp_register_style('goftino_style', plugins_url('goftino.css', __FILE__));
    wp_enqueue_style('goftino_style');
    echo goftino::getInstance()->render();
}
function wp_save_goftino() {
    $goftinoError = null;
    if (trim($_POST['submit']) !== '' && wp_verify_nonce( $_POST['_wpnonce'], 'goftino_nonce'.get_current_user_id())) {
        $g_id = trim(sanitize_text_field($_POST['widget_id']));
        if ($g_id !== '') {
            if ($_POST['send_userdata'] == 1) {$dt='1';}else{$dt='0';}
            if (preg_match("/^[0-9a-f]{24}$/", $g_id) || preg_match("/^[0-9a-zA-Z]{6}$/", $g_id)) {
                if (get_option('goftino_widget_id') !== false) {
                    update_option('goftino_widget_id', $g_id);
                    update_option('goftino_send_userdata', $dt);
                } else {
                    add_option('goftino_widget_id', $g_id, null, 'no'); add_option('goftino_send_userdata', $dt, null, 'no');
                }
                $goftino = goftino::getInstance();
                $goftino->install();
            } else {
                $goftinoError = "شناسه نامعتبر است.";
            }
        } else {
            $goftinoError = "شناسه نمی تواند خالی باشد.";
        }
        set_transient('error_goftino', $goftinoError);
    }
    wp_redirect($_SERVER['HTTP_REFERER']);
    exit();
}

class goftino {
    protected static $instance;

    private function __construct()
    {
        $this->send_userdata = get_option('goftino_send_userdata');
        $this->widget_id = get_option('goftino_widget_id');
    }

    private $widget_id = '';
    private $send_userdata = '';

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new goftino();
        }
        return self::$instance;
    }
    public function install()
    {
        if (!$this->widget_id) {
            if (($out = get_option('goftino_widget_id')) !== false) {
                $this->widget_id = $out;
            }
        }
        $this->save();
    }
    public function delete()
    {
        delete_transient('error_goftino');
        if (get_option('goftino_widget_id') !== false) {
            delete_option('goftino_widget_id');
            delete_option('goftino_send_userdata');
        }
    }

    public function getId()
    {
        return $this->widget_id;
    }
    public function getData()
    {
        return $this->send_userdata;
    }
    public function render()
    {
        $widget_id = $this->widget_id;
        $send_userdata = $this->send_userdata;
        require_once "setting.php";
    }
    public function append($widget_id = false, $send_userdata = 0)
    {
        $user_data_script = '';

        if( $send_userdata > 0 && is_user_logged_in() ) {

            $user       = wp_get_current_user();

            $full_name  = trim( $user->first_name .' ' . $user->last_name );

            $user_data  = array(
                'email'     => $user->user_email,
                'phone'     => apply_filters( 'goftino_user_phone', false ),
                'name'      => $full_name ? $full_name : $user->display_name,
                'about'     => apply_filters( 'goftino_user_about', false ),
                'avatar'    => apply_filters( 'goftino_user_avatar', false ),
            );

            /**
             * Remove Empty data such as about, avatar and phone
             */
            $user_data = array_filter( $user_data, function ( $value ) {
                return $value;
            } );

            /**
             * Generate Goftino function for send user data
             */
            $user_data_script = 'window.addEventListener(\'goftino_ready\', function (p) { Goftino.setUser(' . json_encode( $user_data ) . ');})';

        }

        if ($widget_id) {
            echo '<script type="text/javascript">
!function(){function g(){var g = document.createElement("script"),s="https://www.goftino.com/widget/'.$widget_id.'";g.type = "text/javascript", g.async = !0,g.src=localStorage.getItem("goftino")?s+"?o="+localStorage.getItem("goftino"):s;var e = document.getElementsByTagName("script")[0];e.parentNode.insertBefore(g, e);}
var a = window;"complete" === document.readyState ? g() : a.attachEvent ? a.attachEvent("onload", g) : a.addEventListener("load", g, !1);}();';
            echo $user_data_script;
            echo "</script>";
        }

    }

    public function save() {
        update_option('goftino_widget_id', $this->widget_id);
        update_option('goftino_send_userdata', $this->send_userdata);
    }

}

