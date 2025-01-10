<?php
  date_default_timezone_set(get_option('timezone_string'));

  function convert_post_datetime($date, $time) {
    return date("F d, Y h:iA", strtotime($date." ".$time));
  }

  function get_optimal_post_datetime() {
    $current_date = date("Y-m-d");
    $current_day = date("w");
    $current_hour = date("H");

    // define optimal values
    $first_preferred_hour = 8;
    $last_preferred_hour = 10;
    $first_preferred_day = 1;
    $last_preferred_day = 5;
	
    switch($current_day) {
      case 0:
        $post_time = $first_preferred_hour.":00:00";
	$post_date = date("Y-m-d", strtotime("next Monday"));
	break;

      case 1:
      case 2:
      case 3:
      case 4:
        if ($current_hour < $first_preferred_hour) {
          $post_time = $first_preferred_hour.":00:00";
	  $post_date = date("Y-m-d");
	} elseif (($current_hour >= $first_preferred_hour) && ($current_hour <= $last_preferred_hour)) {
	  $post_time = $current_hour.":00:00";
	  $post_date = date("Y-m-d");
	} else {
	  $post_time = $first_preferred_hour.":00:00";
	  $post_date = date("Y-m-d", strtotime("tomorrow"));
	}
	break;

      case 5:
	if ($current_hour < $first_preferred_hour) {
	  $post_time = $first_preferred_hour.":00:00";
	  $post_date = date("Y-m-d");
  	} elseif (($current_hour >= $first_preferred_hour) && ($current_hour <= $last_preferred_hour)) {
	  $post_time = $current_hour.":00:00";
	  $post_date = date("Y-m-d");
	} else {
	  $post_time = $first_preferred_hour.":00:00";
	  $post_date = date("Y-m-d", strtotime("next Monday"));
	}
	break;

      case 6:
	$post_time = $first_preferred_hour.":00:00";
	$post_date = date("Y-m-d", strtotime("next Monday"));
	break;
    }
    return date("F d, Y h:iA", strtotime($post_date." ".$post_time));
  }

function post_to_sendy($id, $post, $update, $post_before) { 
    // check required options are set
    $post2sendy_options = get_option( 'post2sendy_option_name' ); // Array of All Options
    $serverURL = $post2sendy_options['server_url_0']; // Server URL
    $apiKey = $post2sendy_options['api_key_1']; // API Key
    $brandID = $post2sendy_options['brand_id_2']; // Brand ID
    $listID = $post2sendy_options['list_id_3']; // List ID
    $templateURL = $post2sendy_options['template_url_4']; // Template URL
    $fromName = $post2sendy_options['from_name_5']; // From Name
    $fromEmail = $post2sendy_options['from_email_6']; // From Email
    $replyTo = $post2sendy_options['reply_to_7']; // Reply To
    $campaignTitle = $post2sendy_options['campaign_title_format_8']; // Campaign Title Format
    $emailSubject = $post2sendy_options['email_subject_format_9']; // Email Subject Format
    $emailContent = $post2sendy_options['email_content_format_10']; // Email Content Format
    $trackOpens = $post2sendy_options['track_opens_11']; // Track Opens
    $trackClicks = $post2sendy_options['track_clicks_12']; // Track Clicks
    $emailStatus = $post2sendy_options['default_email_status_13']; // Default Email Status

    if(($serverURL == null) || ($apiKey == null) || ($brandID == null) || 
       ($listID == null) || ($fromName == null) || ($fromEmail == null) ||
       ($replyTo == null) || ($campaignTitle == null) || ($templateURL == null) ||
       ($emailSubject == null) || ($emailContent == null)) { 
      return;
    }

    $doing_autosave = ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE );
    if ( $doing_autosave || wp_is_post_revision( $post_id ) ||
        wp_doing_cron() || wp_doing_ajax()
    ) {
        return;
    }
	
    // Check that post is not a custom post type.
    if ($post->post_type != "post") return;

    // Avoid sending an auto post
    //if (($post->post_status != 'publish') || ($post_before->post_status == 'publish')) return;
    if ($post->post_status != 'publish') return;

    $radio = get_post_meta($post->ID, "meta-box-post2sendy-radio", true);

    switch ($radio) {
      case "Previously":
        return;
        break;
      case "Never":
        return;
        break;
      case "Custom":
        $publish_date = get_post_meta($post->ID, "meta-box-post2sendy-date", true);
        $publish_time = get_post_meta($post->ID, "meta-box-post2sendy-time", true);
        $publish_datetime = convert_post_datetime($publish_date, $publish_time);
        break;
      case "Immediately":
        $publish_datetime = "";
        break;
      case "Optimal":
        $publish_datetime = get_optimal_post_datetime();
        break;
      default:
        $publish_date = get_post_meta($post->ID, "meta-box-post2sendy-publish-date", true);
        $publish_time = get_post_meta($post->ID, "meta-box-post2sendy-publish-time", true);
        $publish_datetime = convert_post_datetime($publish_date, $publish_time);
        break;
    }

    switch($emailStatus) {
      case "Publish":
	$send_campaign = 1;
	break;
      case "Draft":
	$send_campaign = 0;
	break;
      default:
	$send_campaign = 0;
	break;
    }

    if ($trackOpens == "track_opens_11") $track_opens = 1;
	else $track_opens = 0;

    if ($trackClicks == "track_clicks_12") $track_clicks = 1;
	else $track_clicks = 0;

    // Create Campaign API URL
    $apiURL = $serverURL."/api/campaigns/create.php";

    $id = $post->ID;
    $author = $post->post_author;
    $authorName = get_the_author_meta('display_name', $author);
    $postTitle = $post->post_title;
    $postContent = apply_filters('the_content', $post->post_content);
    $postSummary = apply_filters('the_content', $post->post_excerpt);
    $featuredImage = wp_get_attachment_url(get_post_thumbnail_id($id), 'large');
    if (!$postSummary) $postSummary = substr($postContent, 0, 500)."...<br>";
    $permalink = get_permalink($id);

    // read template from url and build email content
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $templateURL);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
    $message = curl_exec($curl);
    curl_close($curl);

    // replace placeholders
    $message = str_replace("[CONTENT]", $emailContent, $message);
    $message = str_replace("[POST_SUMMARY]", $postSummary, $message);
    $message = str_replace("[POST_CONTENT]", $postContent, $message);
    $message = str_replace("[AUTHOR]", $authorName, $message);
    $message = str_replace("[TITLE]", $postTitle, $message);
    $message = str_replace("[SUBJECT]", $postTitle, $message);
    $message = str_replace("[POST_URL]", $permalink, $message);
    $message = str_replace("[YEAR]", date("Y"), $message);
    $message = str_replace("[IMAGE]", $featuredImage, $message);

    $message = html_entity_decode($message);
    $message = do_shortcode($message);

    // build query string for CURL post to Sendy API
    if ($publish_datetime != "") {
      $postData = http_build_query(array('api_key'=>$apiKey,
		      'from_name'=>$fromName, 
		      'from_email'=>$fromEmail, 
		      'reply_to'=>$replyTo, 
		      'title'=>str_replace("[TITLE]", $postTitle, $campaignTitle),  
		      'subject'=>str_replace("[TITLE]", $postTitle, $emailSubject),
		      'plain_text'=>strip_tags($message),
		      'html_text'=>$message,
		      'list_ids'=>$listID,
		      'brand_id'=>$brandID,
		      'track_opens'=>$track_opens,
		      'track_clicks'=>$track_clicks,
		      'schedule_date_time' => $publish_datetime,
                      'schedule_timezone' => get_option('timezone_string'),
		      'send_campaign'=>$send_campaign
		     ));
    } else {
      $postData = http_build_query(array('api_key'=>$apiKey,
		      'from_name'=>$fromName, 
		      'from_email'=>$fromEmail, 
		      'reply_to'=>$replyTo, 
		      'title'=>str_replace("[TITLE]", $postTitle, $campaignTitle),
		      'subject'=>str_replace("[TITLE]", $postTitle, $emailSubject),
		      'plain_text'=>strip_tags($message),
		      'html_text'=>$message,
		      'list_ids'=>$listID,
		      'brand_id'=>$brandID,
		      'track_opens'=>$track_opens,
		      'track_clicks'=>$track_clicks,
		      'send_campaign'=>$send_campaign
		     ));
    }

    // Make Sendy API Call via CURL
    $curl = curl_init($apiURL);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
    $response = curl_exec($curl);
    $errors = curl_error($curl);        
    curl_close($curl);
     
    log_message($errors);
}
 
function log_message($log) {
      if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
}

// Trigger function call after post is inserted so that we have the featured image
add_action('wp_after_insert_post', 'post_to_sendy', 10, 4);
