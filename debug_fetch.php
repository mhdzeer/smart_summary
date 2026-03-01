<?php
// Debug script to test YouTube transcript fetching
require_once('wp-load.php');

function debug_svs_fetch($video_url)
{
    echo "Testing URL: $video_url\n";

    $response = wp_remote_get($video_url, [
        'timeout' => 25,
        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    ]);

    if (is_wp_error($response)) {
        die("WP Error: " . $response->get_error_message());
    }

    $html = wp_remote_retrieve_body($response);
    echo "HTML Length: " . strlen($html) . " bytes\n";

    if (preg_match('/<title>(.*?)<\/title>/', $html, $title_matches)) {
        echo "Detected Title Tag: " . $title_matches[1] . "\n";
    }

    if (preg_match('/ytInitialPlayerResponse\s*=\s*({.+?});/s', $html, $matches)) {
        echo "Found ytInitialPlayerResponse!\n";
        $json = json_decode($matches[1], true);

        $title = isset($json['videoDetails']['title']) ? $json['videoDetails']['title'] : 'Unknown';
        echo "JSON Video Title: $title\n";

        $caption_tracks = isset($json['captions']['playerCaptionsTracklistRenderer']['captionTracks']) ? $json['captions']['playerCaptionsTracklistRenderer']['captionTracks'] : [];
        echo "Caption Tracks Found: " . count($caption_tracks) . "\n";

        foreach ($caption_tracks as $index => $track) {
            echo "Track $index: " . $track['vssId'] . " (" . $track['languageCode'] . ") - URL: " . substr($track['baseUrl'], 0, 50) . "...\n";
        }

        if (!empty($caption_tracks)) {
            $base_url = $caption_tracks[0]['baseUrl'];
            $transcript_res = wp_remote_get($base_url . '&fmt=json3');
            if (!is_wp_error($transcript_res)) {
                $transcript_json = json_decode(wp_remote_retrieve_body($transcript_res), true);
                if (isset($transcript_json['events'])) {
                    $text = "";
                    foreach (array_slice($transcript_json['events'], 0, 10) as $event) { // Just get first 10 events
                        if (isset($event['segs'])) {
                            foreach ($event['segs'] as $seg) {
                                $text .= $seg['utf8'] . ' ';
                            }
                        }
                    }
                    echo "Transcript Preview (First few chars): " . mb_substr($text, 0, 200) . "\n";
                } else {
                    echo "No 'events' in transcript JSON.\n";
                }
            } else {
                echo "Transcript Fetch Error: " . $transcript_res->get_error_message() . "\n";
            }
        }
    } else {
        echo "Could NOT find ytInitialPlayerResponse in HTML.\n";
        // Check for cookie consent
        if (strpos($html, 'consent.youtube.com') !== false) {
            echo "Detected YouTube Consent Redirect.\n";
        }
    }
}

$test_url = "https://www.youtube.com/watch?v=jUv4_ypE8mI";
debug_svs_fetch($test_url);
