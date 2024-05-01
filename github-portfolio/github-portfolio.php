<?php
/*
Plugin Name: GitHub Portfolio
Description: Displays GitHub projects and contributions on your WordPress site.
Version: 1.0
Author: Ceekay Codes
*/

defined('ABSPATH') or die('Direct script access disallowed.');

function gp_init() {
    add_shortcode('github_portfolio', 'gp_display');
}
add_action('init', 'gp_init');

function gp_display($atts = [], $content = null, $tag = '') {
    $options = get_option('gp_settings');
    if (!is_array($options) || !isset($options['gp_text_field_0'])) {
        return 'GitHub username is not set. Please check your plugin settings.';
    }

    $username = $options['gp_text_field_0']; // Get the stored GitHub username
    $user_url = "https://api.github.com/users/$username";
    $repos_url = "https://api.github.com/users/$username/repos";

    // Fetch user profile
    $user_response = wp_remote_get($user_url, array('headers' => array('User-Agent' => 'WordPress/GitHub Portfolio Plugin')));
    if (is_wp_error($user_response)) {
        return 'Failed to retrieve user data.';
    }
    $user_data = json_decode(wp_remote_retrieve_body($user_response), true);

    // Fetch repositories
    $repos_response = wp_remote_get($repos_url, array('headers' => array('User-Agent' => 'WordPress/GitHub Portfolio Plugin')));
    if (is_wp_error($repos_response)) {
        return 'Failed to retrieve repositories.';
    }
    $repos = json_decode(wp_remote_retrieve_body($repos_response), true);

    // Start output
    $output = '<div class="github-portfolio">';
    if (!empty($user_data)) {
        $output .= '<div class="github-user">';
        $output .= '<img src="' . esc_url($user_data['avatar_url']) . '" alt="GitHub Avatar" style="width: 100px; height: 100px; border-radius: 50%;">';
        $output .= '<h3>' . esc_html($user_data['name']) . '</h3>';
        $output .= '</div>';
    }

    $output .= '<ul>';
    foreach ($repos as $repo) {
        $output .= '<li><a href="' . esc_url($repo['html_url']) . '">' . esc_html($repo['name']) . '</a></li>';
    }
    $output .= '</ul>';
    $output .= '</div>';

    return $output;
}




function gp_add_admin_menu() {
    add_menu_page('GitHub Portfolio', 'GitHub Portfolio', 'manage_options', 'github_portfolio', 'gp_options_page');
}
add_action('admin_menu', 'gp_add_admin_menu');

function gp_settings_init() {
    register_setting('gpPlugin', 'gp_settings');
    add_settings_section('gp_plugin_page_section', __('Your Section Title', 'wordpress'), 'gp_settings_section_callback', 'gpPlugin');
    add_settings_field('gp_text_field_0', __('GitHub Username', 'wordpress'), 'gp_text_field_0_render', 'gpPlugin', 'gp_plugin_page_section');
}

function gp_text_field_0_render() {
    $options = get_option('gp_settings');
    ?>
    <input type='text' name='gp_settings[gp_text_field_0]' value='<?php echo $options['gp_text_field_0']; ?>'>
    <?php
}

function gp_settings_section_callback() {
    echo __('Please enter your GitHub username to display your projects.', 'wordpress');
}

function gp_options_page() {
    ?>
    <form action='options.php' method='post'>
        <h2>GitHub Portfolio</h2>
        <?php
        settings_fields('gpPlugin');
        do_settings_sections('gpPlugin');
        submit_button();
        ?>
    </form>
    <?php
}

add_action('admin_init', 'gp_settings_init');

