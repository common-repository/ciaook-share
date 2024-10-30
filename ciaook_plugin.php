<?php
/*
Plugin Name: Ciaook Share Plugin
Plugin URI: http://ciaook.com/wordpress_share
Description: This plugin can help you to share items placed on Ciaook on your WordPress site via [ciaook_share] shortcode or widget. You can specify API Key as [ciaook_share api=xxx] or via Settings for shortcode. You can specify API Key in input field in widget administration or via Settings for widget.
Text Domain: ciaook_textdomain
Domain Path: /languages
Version: 0.1.1
Author: Ciaook Team
Author URI: http://ciaook.com
*/

register_activation_hook(__FILE__, 'ciaook_install');
function ciaook_install()
{
    if (!get_option('ciaook_version')) {
        add_option('ciaook_api_key');
        add_option('ciaook_version', 0.1);
    }
}

register_uninstall_hook(__FILE__, 'ciaook_uninstall');
function ciaook_uninstall()
{
    delete_option('ciaook_api_key');
    delete_option('ciaook_version');
}

add_action('set_current_user', 'ciaook_save_data');
function ciaook_save_data($id, $name = '')
{
    if (isset($_POST['ciaook_edit'])) {
        if ($_POST['ciaook_edit'] == 1) {
            if (isset($_POST['ciaook_api_key']))
                if (update_option('ciaook_api_key', sanitize_text_field($_POST['ciaook_api_key'])))
                    $fl = 1;
            if ((isset($fl)) && ($fl)) {
                header('Location: http' . ($_SERVER['HTTPS'] ? 's' : '') . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] . '&ciaook_sd=1');
                exit();
            }
        }
    }
}

function ciaook_plugin_init()
{
    load_plugin_textdomain('ciaook_textdomain', false, dirname(plugin_basename(__FILE__)).'/languages');
}
add_action('plugins_loaded', 'ciaook_plugin_init');

add_action('wp_enqueue_scripts', 'ciaook_add_my_stylesheet');
function ciaook_add_my_stylesheet()
{
    wp_enqueue_style('ciaook_', plugin_dir_url(__FILE__) . 'style.css');
}

add_action('admin_menu', 'ciaook_plugin_menu');
function ciaook_plugin_menu()
{
    load_plugin_textdomain('ciaook_textdomain', false, dirname(plugin_basename(__FILE__)).'/languages');
    add_options_page('Ciaook', esc_attr__('Ciaook Share', 'ciaook_textdomain'), 'manage_options', 'ciaook_share', 'ciaook_show_api_key');
}

function ciaook_show_api_key()
{
    global $wpdb;
    if (!current_user_can('manage_options'))
        wp_die(__('You do not have sufficient permissions to access this page', 'ciaook_textdomain'));
    echo '<div class="wrap"><h2>' . esc_html__('Ciaook Settings', 'ciaook_textdomain') . '</h2>';
    if ((isset($_GET['ciaook_sd'])) && ($_GET['ciaook_sd'] == 1))
        echo '<div id="message" class="updated"><p>' . esc_html__('Ciaook API Key edited successfully', 'ciaook_textdomain') . '</p></div>';
    echo '<form method="post"><input type=hidden name="ciaook_edit" value=1><label>' . esc_html__('Enter Ciaook API Key (you can find it in', 'ciaook_textdomain') . ' <a href="https://ciaook.com/index.php?page=userpage#user_api_key" target="_blank">' . esc_html__('your personal page once logged on ciaook.com', 'ciaook_textdomain') . '</a>)<input name="ciaook_api_key" type="text" class="regular-text" value="' . esc_html(get_option('ciaook_api_key')) . '"></label><input type="submit" value="' . esc_attr__('Save', 'ciaook_textdomain') . '" class="button button-primary"></form></div>';
}

function ciaook_share_func($atts)
{
    if (isset($atts['api']))
        $api = rawurlencode($atts['api']);
    elseif (get_option('ciaook_api_key'))
        $api = rawurlencode(get_option('ciaook_api_key'));
    else
        $api = null;
    if ($api) {
        if ($c = @file_get_contents('http://ciaook.com/share.php?api=' . $api)) {
            $c  = new SimpleXMLElement($c);
            $st = '';
            // echo '$c='; print_r($c);
            // echo '<br>$c->items='; print_r($c->item);
            // echo '<br>$c->children()='; print_r($c->children());
            // echo '<br>$c->items->item='; print_r($c->items->item);
            // echo '<br>$c->items[\'name\']='; print_r($c->item['type']);
            if ((isset($c)) && ($c)) {
                if (null !== $c->children()) {
                    foreach ($c->children() as $v) {
                        $st .= '<div class="ciaook_item ciaook_item_' . $v['type'] . '"><h5 class="ciaook_item_name"><span class="ciaook_item_thumb"><a href="' . htmlspecialchars($v['link']) . '" target="_blank"><img src="' . htmlspecialchars($v['img']) . '" class="ciaook_thumb"></a></span><span class="ciaook_item_name_head"><a href="' . htmlspecialchars($v['link']) . '" target="_blank">' . htmlspecialchars($v['name']) . '</a></span></h5><p class="ciaook_item_description"><a href="' . htmlspecialchars($v['link']) . '" target="_blank">' . htmlspecialchars($v['desc']) . '</a></p>';
                        if (($p = floatval($v['costhour'])) && ($pm = floatval($v['minhour'])) && ($pm2 = floatval($v['minday'])) && ($pm != $pm2)) {
                            $st .= '<p class="ciaook_item_price ciaook_item_price_hour"><a href="' . htmlspecialchars($v['link']) . '" target="_blank">';
                            //if ($v['type']=='att')
                            $st .= '<span class="ciaook_item_price_from">' . esc_html__('from', 'ciaook_textdomain') . '</span>';
                            $st .= '<span class="ciaook_item_price_digit">' . $p . '</span><span class="ciaook_item_price_currency">' . $v['curr'] . '</span><span class="ciaook_item_price_mesure">' . esc_html__('per hour', 'ciaook_textdomain') . '</span>';
                            if (($pm) && ($p != $pm))
                                $st .= '<span class="ciaook_item_min">(' . esc_html__('minimum', 'ciaook_textdomain') . '<span class="ciaook_item_price_digit">' . $pm . '</span><span class="ciaook_item_price_currency">' . $v['curr'] . '</span>)</span>';
                            $st .= '</a></p>';
                        } elseif ($p = floatval($v['costday'])) {
                            $pm = floatval($v['minday']);
                            $st .= '<p class="ciaook_item_price ciaook_item_price_day"><a href="' . htmlspecialchars($v['link']) . '" target="_blank">';
                            //if ($v['type']=='att') 
                            $st .= '<span class="ciaook_item_price_from">' . esc_html__('from', 'ciaook_textdomain') . '</span>';
                            $st .= '<span class="ciaook_item_price_digit">' . $p . '</span><span class="ciaook_item_price_currency">' . $v['curr'] . '</span>';
                            if ($v['type'] != 'att')
                                $st .= '<span class="ciaook_item_price_mesure">' . esc_html__('per day', 'ciaook_textdomain') . '</span>';
                            if (($pm) && ($p != $pm))
                                $st .= '<span class="ciaook_item_min">(' . esc_html__('minimum', 'ciaook_textdomain') . '<span class="ciaook_item_price_digit">' . $pm . '</span><span class="ciaook_item_price_currency">' . $v['curr'] . '</span>)</span>';
                            $st .= '</a></p>';
                        }
                        $st .= '</div><hr />';
                    }
                    return '<div class="ciaook_items">' . $st . '</div>';
                } else
                    return '<!--ciaook xml is empty ->';
            } else
                return '<!--ciaook xml is not set ->';
        } else
            return '<!--ciaook xml not found ->';
    } else
        return '<!--ciaook API Key not set ->';
}
add_shortcode('ciaook_share', 'ciaook_share_func');

function ciaook_add_settings_link($links)
{
    $settings_link = '<a href="options-general.php?page=ciaook_share">' . esc_html__('Settings') . '</a>';
    array_push($links, $settings_link);
    return $links;
}

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'ciaook_add_settings_link');


class ciaook_Widget extends WP_Widget
{
    public function __construct()
    {
        parent::__construct('ciaook_widget', 'Ciaook', array(
            'description' => esc_attr__('Widget for sharing Ciaook items', 'ciaook_textdomain')
        ));
    }
    
    // Creating widget front-end
    public function widget($args, $instance)
    {
        $title = apply_filters('widget_title', $instance['title']);
        $api   = $instance['api'];
        
        if ($api) {
            if ($c = @file_get_contents('http://ciaook.com/share.php?api=' . $api)) {
                $c  = new SimpleXMLElement($c);
                $st = '';
                if ((isset($c)) && ($c)) {
                    if (null !== $c->children()) {
                        foreach ($c->children() as $v) {
                            $st .= '<div class="ciaook_item ciaook_item_' . $v['type'] . '"><h5 class="ciaook_item_name"><span class="ciaook_item_thumb"><a href="' . htmlspecialchars($v['link']) . '" target="_blank"><img src="' . htmlspecialchars($v['img']) . '" class="ciaook_thumb"></a></span><span class="ciaook_item_name_head"><a href="' . htmlspecialchars($v['link']) . '" target="_blank">' . htmlspecialchars($v['name']) . '</a></span></h5><p class="ciaook_item_description"><a href="' . htmlspecialchars($v['link']) . '" target="_blank">' . htmlspecialchars($v['desc']) . '</a></p>';
                            if (($p = floatval($v['costhour'])) && ($pm = floatval($v['minhour'])) && ($pm2 = floatval($v['minday'])) && ($pm != $pm2)) {
                                $st .= '<p class="ciaook_item_price ciaook_item_price_hour"><a href="' . htmlspecialchars($v['link']) . '" target="_blank">';
                                //if ($v['type']=='att') 
                                $st .= '<span class="ciaook_item_price_from">' . esc_html__('from', 'ciaook_textdomain') . '</span>';
                                $st .= '<span class="ciaook_item_price_digit">' . $p . '</span><span class="ciaook_item_price_currency">' . $v['curr'] . '</span><span class="ciaook_item_price_mesure">' . esc_html__('per hour', 'ciaook_textdomain') . '</span>';
                                if (($pm) && ($p != $pm))
                                    $st .= '<span class="ciaook_item_min">(' . esc_html__('minimum', 'ciaook_textdomain') . '<span class="ciaook_item_price_digit">' . $pm . '</span><span class="ciaook_item_price_currency">' . $v['curr'] . '</span>)</span>';
                                $st .= '</a></p>';
                            } elseif ($p = floatval($v['costday'])) {
                                $pm = floatval($v['minday']);
                                $st .= '<p class="ciaook_item_price ciaook_item_price_day"><a href="' . htmlspecialchars($v['link']) . '" target="_blank">';
                                //if ($v['type']=='att')
                                $st .= '<span class="ciaook_item_price_from">' . esc_html__('from', 'ciaook_textdomain') . '</span>';
                                $st .= '<span class="ciaook_item_price_digit">' . $p . '</span><span class="ciaook_item_price_currency">' . $v['curr'] . '</span>';
                                if ($v['type'] != 'att')
                                    $st .= '<span class="ciaook_item_price_mesure">' . esc_html__('per day', 'ciaook_textdomain') . '</span>';
                                if (($pm) && ($p != $pm))
                                    $st .= '<span class="ciaook_item_min">(' . esc_html__('minimum', 'ciaook_textdomain') . '<span class="ciaook_item_price_digit">' . $pm . '</span><span class="ciaook_item_price_currency">' . $v['curr'] . '</span>)</span>';
                                $st .= '</a></p>';
                            }
                            $st .= '</div><hr />';
                        }
                        echo $args['before_widget'];
                        if (!empty($title))
                            echo $args['before_title'] . $title . $args['after_title'];
                        echo '<div class="ciaook_items ciaook_widget">' . $st . '</div>' . $args['after_widget'];
                    } else
                        echo '<!--ciaook xml is empty ->';
                } else
                    echo '<!--ciaook xml is not set ->';
            } else
                echo '<!--ciaook xml not found ->';
        } else
            echo '<!--ciaook API Key not set ->';
    }
    
    
    /* update/save function
     */
    public function update($new_instance, $old_instance)
    {
        $instance          = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['api']   = htmlspecialchars($new_instance['api']);
        return $instance;
    }
    
    /**
     *  admin control form
     */
    public function form($instance)
    {
        $default  = array(
            'title' => 'Ciaook',
            'api' => get_option('ciaook_api_key')
        );
        $instance = wp_parse_args((array) $instance, $default);
        
        $title_id   = $this->get_field_id('title');
        $title_name = $this->get_field_name('title');
        $api_id     = $this->get_field_id('api');
        $api_name   = $this->get_field_name('api');
        echo "\r\n" . '<p><label for="' . $title_id . '">' . esc_html__('Title') . ': <input type="text" class="widefat" id="' . $title_id . '" name="' . $title_name . '" value="' . esc_attr($instance['title']) . '" /><label></p>' . "\r\n" . '<p><label for="' . $api_id . '">' . esc_html__('API Key', 'ciaook_textdomain') . ': <input type="text" class="widefat" id="' . $api_id . '" name="' . $api_name . '" value="' . esc_attr($instance['api']) . '" /><label></p>';
    }
}

/* register widget when loading the WP core */
add_action('widgets_init', function()
{
    register_widget('ciaook_Widget');
});


?>
