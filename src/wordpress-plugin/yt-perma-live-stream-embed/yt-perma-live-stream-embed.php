<?php
/**
 * Plugin Name: YT Perma Live Stream Embed
 * Description: Embed a channel's latest YouTube live stream.
 * Version: 1.0
 * Author: yak
 * License: GPL3
 */

function yt_perma_live_stream_embed_shortcode($atts) {
    $options = get_option('yt_perma_live_stream_embed_options');
    $apiKey = $options['api_key'] ?? '';
    $channelId = $options['channel_id'] ?? '';

    if (empty($apiKey) || empty($channelId)) {
        return "YouTube API Key or Channel ID not set.";
    }

    $apiUrl = sprintf('https://www.googleapis.com/youtube/v3/search?key=%s&channelId=%s&part=snippet&type=video&eventType=live&order=date&maxResults=1', $apiKey, $channelId);

    $response = wp_remote_get($apiUrl);

    if (is_wp_error($response)) {
        return "Failed to retrieve data.";
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($data['items'])) {
        return "No live streams are currently active.";
    }

    $videoId = $data['items'][0]['id']['videoId'];
    $embedHtml = sprintf('<div style="position:relative;padding-bottom:56.25%;overflow:hidden;height:0;max-width:100%;"><iframe width="1280"" height="720" src="https://www.youtube.com/embed/%s" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div>', $videoId);

    return $embedHtml;
}

add_shortcode('yt-perma-live-stream-embed', 'yt_perma_live_stream_embed_shortcode');

// Create settings page
function yt_perma_live_stream_embed_menu() {
    add_options_page(
        'YT Perma Live Stream Embed Settings',
        'YT Perma Live Stream Embed',
        'manage_options',
        'yt-perma-live-stream-embed',
        'yt_perma_live_stream_embed_settings_page'
    );
}
add_action('admin_menu', 'yt_perma_live_stream_embed_menu');

// Render settings page
function yt_perma_live_stream_embed_settings_page() {
    ?>
    <div class="wrap">
        <h1>YT Perma Live Stream Embed Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('yt_perma_live_stream_embed_options_group');
            do_settings_sections('youtube_live_embed');
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
        'youtube_live_embed'
    );

    add_settings_field(
        'yt_perma_live_stream_embed_api_key',
        'YouTube API Key',
        'yt_perma_live_stream_embed_api_key_render',
        'youtube_live_embed',
        'yt_perma_live_stream_embed_section'
    );

    add_settings_field(
        'yt_perma_live_stream_embed_channel_id',
        'YouTube Channel ID',
        'yt_perma_live_stream_embed_channel_id_render',
        'youtube_live_embed',
        'yt_perma_live_stream_embed_section'
    );
}
add_action('admin_init', 'yt_perma_live_stream_embed_settings_init');

// Render input fields
function yt_perma_live_stream_embed_api_key_render() {
    $options = get_option('yt_perma_live_stream_embed_options');
    ?>
    <input type="text" name="yt_perma_live_stream_embed_options[api_key]" value="<?php echo isset($options['api_key']) ? esc_attr($options['api_key']) : ''; ?>" size="50">
    <?php
}

function yt_perma_live_stream_embed_channel_id_render() {
    $options = get_option('yt_perma_live_stream_embed_options');
    ?>
    <input type="text" name="yt_perma_live_stream_embed_options[channel_id]" value="<?php echo isset($options['channel_id']) ? esc_attr($options['channel_id']) : ''; ?>" size="50">
    <?php
}
?>