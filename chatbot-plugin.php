<?php
/**
 * Plugin Name: Chatbot Plugin
 * Description: A chatbot that integrates with Dialogflow CX.
 * Version: 1.1
 * Author: Your Name
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Enqueue scripts and styles
function chatbot_enqueue_scripts() {
    wp_enqueue_style('chatbot-style', plugin_dir_url(__FILE__) . 'css/chatbot-style.css');
    wp_enqueue_script('chatbot-script', plugin_dir_url(__FILE__) . 'js/chatbot-script.js', array('jquery'), null, true);

    // Pass AJAX URL to JavaScript
    wp_localize_script('chatbot-script', 'chatbot_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'chatbot_enqueue_scripts');

// Add the chatbot HTML to the footer
function add_chatbot_html() {
    echo '
    <div id="chatbot-container">
        <div id="chatbot-button">Chat</div>
        <div id="chatbot-box">
            <div id="chatbot-header">Chatbot</div>
            <div id="chatbot-messages"></div>
            <input type="text" id="chatbot-input" placeholder="Type your message...">
        </div>
    </div>';
}
add_action('wp_footer', 'add_chatbot_html');

// Handle the AJAX request
function chatbot_handle_message() {
    if (!isset($_POST['message'])) {
        wp_send_json_error('No message provided');
        wp_die();
    }

    $message = sanitize_text_field($_POST['message']);

    // Path to your service account key file
    $service_account_path = plugin_dir_path(__FILE__) . 'Path to key file downloaded';

    require 'vendor/autoload.php';

    // Get a fresh access token
    $client = new Google_Client();
    $client->setAuthConfig($service_account_path);
    $client->addScope('https://www.googleapis.com/auth/cloud-platform');
    $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'];

    // Call Dialogflow CX API
    $projectId = ''; // Replace with your actual project ID
    $locationId = ''; // Replace with your actual location ID
    $agentId = ''; // Replace with your actual agent ID
    $sessionId = uniqid();
    $endpoint = "https://dialogflow.googleapis.com/v3/projects/{$projectId}/locations/{$locationId}/agents/{$agentId}/sessions/{$sessionId}:detectIntent";

    $response = wp_remote_post($endpoint, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode(array(
            'queryInput' => array(
                'text' => array(
                    'text' => $message,
                    'languageCode' => 'en',
                ),
            ),
        )),
    ));

    if (is_wp_error($response)) {
        wp_send_json_error('API request failed');
        wp_die();
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['queryResult']['responseMessages'])) {
        $reply = $data['queryResult']['responseMessages'][0]['text']['text'][0];
        wp_send_json_success($reply);
    } else {
        wp_send_json_error('No response from API');
    }

    wp_die();
}
add_action('wp_ajax_chatbot_message', 'chatbot_handle_message');
add_action('wp_ajax_nopriv_chatbot_message', 'chatbot_handle_message');
?>
