<?php
/**
 * Plugin Name: YT Perma Live Stream Embed
 * Description: Embed a channel's latest YouTube live stream.
 * Version: 1.0
 * Author: yak
 * License: GPL3
 */

$plugin_name = 'YT Perma Live Stream Embed';

function yt_perma_live_stream_embed_shortcode($atts) {
    global $plugin_name;
    $options = get_option('yt_perma_live_stream_embed_options');
    $apiKey = $options['api_key'] ?? '';
    $channelId = $options['channel_id'] ?? ''; // Lofi Girl test channel ID: UCSJ4gkVC6NrvII8umztf0Ow
    
    // Set default shortcode attributes
    $default_width = '';
    $default_height = '';

    // Extract shortcode attributes
    $attributes = shortcode_atts(
        [
            'width' => $default_width,
            'height' => $default_height,
            'responsive' => $default_responsive,
        ],
        $atts
    );

    if (empty($apiKey) || empty($channelId)) {
        $settings_url = admin_url('options-general.php?page=yt-perma-live-stream-embed');
        return sprintf(
            '<p>%s settings are missing. Please configure <a href="%s" rel="nofollow">plugin settings</a>.</p>',
            $plugin_name,
            esc_url($settings_url)
        );
    }

    $apiUrl = sprintf('https://www.googleapis.com/youtube/v3/search?key=%s&channelId=%s&part=snippet&type=video&eventType=live&order=date&maxResults=1', $apiKey, $channelId);

    // Set up the args with custom referrer
    $args = [
        'headers' => [
            'Referer' => home_url(),
        ],
    ];

    $response = wp_remote_get($apiUrl, $args);

    if (is_wp_error($response)) {
        // Retrieve response code and message
        $response_code = wp_remote_retrieve_response_code($response);
        $response_message = wp_remote_retrieve_response_message($response);
        $response_body = wp_remote_retrieve_body($response);

        // Log details
        error_log($plugin_name . ' YouTube API Response Code: ' . $response_code);
        error_log($plugin_name . ' YouTube API Response Message: ' . $response_message);
        error_log($plugin_name . ' YouTube API Response Body: ' . $response_body);

        return sprintf("<p>%s could not retrieve YouTube data. Check error logs for more details.</p>", $plugin_name);
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($data['items'])) {
        return "<p>Stream is not live right now.</p>";
    }

    $videoId = $data['items'][0]['id']['videoId'];

    if(!empty($attributes['width']) && !empty($attributes['height'])) {
        $style = '';
        $width =  esc_attr($attributes['width']);
        $height =  esc_attr($attributes['height']);
    } 
    else {
        $style = 'aspect-ratio: 16 / 9; width: 100%;'; // Responsive by default
        $width = '';
        $height = '';
    }

    $embedHtml = sprintf(
        '<iframe style="%s" width="%s" height="%s" src="https://www.youtube.com/embed/%s" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>',
        $style,
        $width,
        $height,
        esc_attr($videoId)
    );

    return $embedHtml;
}

add_shortcode('yt-perma-live-stream-embed', 'yt_perma_live_stream_embed_shortcode');

// Create settings page
function yt_perma_live_stream_embed_menu() {
    global $plugin_name;
    add_options_page(
        $plugin_name . ' Settings',
        $plugin_name,
        'manage_options',
        'yt-perma-live-stream-embed',
        'yt_perma_live_stream_embed_settings_page'
    );
}
add_action('admin_menu', 'yt_perma_live_stream_embed_menu');

// Render settings page
function yt_perma_live_stream_embed_settings_page() {
    global $plugin_name;
    ?>
    <div class="wrap">
        <h1><?php echo $plugin_name; ?> Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('yt_perma_live_stream_embed_options_group');
            do_settings_sections('yt_perma_live_stream_embed');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
function yt_perma_live_stream_embed_settings_init() {
    register_setting('yt_perma_live_stream_embed_options_group', 'yt_perma_live_stream_embed_options', 'sanitize_callback');

    add_settings_section(
        'yt_perma_live_stream_embed_section',
        'YouTube API Settings',
        null,
        'yt_perma_live_stream_embed'
    );

    add_settings_field(
        'yt_perma_live_stream_embed_api_key',
        'YouTube API Key',
        'yt_perma_live_stream_embed_api_key_render',
        'yt_perma_live_stream_embed',
        'yt_perma_live_stream_embed_section'
    );

    add_settings_field(
        'yt_perma_live_stream_embed_channel_id',
        'YouTube Channel ID',
        'yt_perma_live_stream_embed_channel_id_render',
        'yt_perma_live_stream_embed',
        'yt_perma_live_stream_embed_section'
    );
}
add_action('admin_init', 'yt_perma_live_stream_embed_settings_init');

// Render input fields
function yt_perma_live_stream_embed_api_key_render() {
    $options = get_option('yt_perma_live_stream_embed_options');
    ?>
    <input type="text" name="yt_perma_live_stream_embed_options[api_key]" value="<?php echo isset($options['api_key']) ? esc_attr($options['api_key']) : ''; ?>" size="50">
    <p>
        <a href="https://youtu.be/ZCfrNvu6nMc" target="_blank">How to create a YouTube API key</a>
    </p>
    <?php
}

function yt_perma_live_stream_embed_channel_id_render() {
    $options = get_option('yt_perma_live_stream_embed_options');
    ?>
    <input type="text" name="yt_perma_live_stream_embed_options[channel_id]" value="<?php echo isset($options['channel_id']) ? esc_attr($options['channel_id']) : ''; ?>" size="50">
    <p>
        <a href="https://support.google.com/youtube/answer/3250431" target="_blank">How to find a YouTube channel ID</a>
    </p>
    <?php
}
?>