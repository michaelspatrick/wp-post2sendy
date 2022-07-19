<?php

function post2sendy_meta_box_markup($post) {
    $optimal_date = get_optimal_post_datetime();
    $radio = get_post_meta($post->ID, "meta-box-post2sendy-radio", true); 
    $status = $post->post_status;
    if ($status === 'new' || $status === 'auto-draft') {
      $radio = "Optimal";
    } elseif ($radio == "") {
    //} else {
      $radio = "Never";
    }
    $custom_date = get_post_meta($post->ID, "meta-box-post2sendy-date", true);
    $custom_time = get_post_meta($post->ID, "meta-box-post2sendy-time", true);
    wp_nonce_field(basename(__FILE__), "meta-box-post2sendy-nonce");
    $publish_date = get_post_meta($post->ID, "meta-box-post2sendy-publish-date", true);
    ?>
        <div>
            <label for="meta-box-post2sendy-radio">Send to Email List:</label><br>

            <?php if ($publish_date): ?>
            <?php if ($radio == "Previously"): ?>
              <input name="meta-box-post2sendy-radio" type="radio" value="Previously" checked> Previously: <?php echo $publish_date; ?><br>
	    <?php else: ?>
              <input name="meta-box-post2sendy-radio" type="radio" value="Previously"> Previously: <?php echo $publish_date; ?><br>
            <?php endif; ?>
            <?php endif; ?>

            <?php if ($radio == "Never"): ?>
              <input name="meta-box-post2sendy-radio" type="radio" value="Never" checked> Never<br>
	    <?php else: ?>
              <input name="meta-box-post2sendy-radio" type="radio" value="Never"> Never<br>
            <?php endif; ?>

            <?php if ($radio == "Immediately"): ?>
              <input name="meta-box-post2sendy-radio" type="radio" value="Immediately" checked> Immediately<br>
            <?php else: ?>
              <input name="meta-box-post2sendy-radio" type="radio" value="Immediately"> Immediately<br>
            <?php endif; ?>

            <?php if ($radio == "Optimal"): ?>
              <input name="meta-box-post2sendy-radio" type="radio" value="Optimal" checked> Optimal: <?php echo $optimal_date; ?><br>
            <?php else: ?>
              <input name="meta-box-post2sendy-radio" type="radio" value="Optimal"> Optimal: <?php echo $optimal_date; ?><br>
            <?php endif; ?>

            <?php if ($radio == "Custom"): ?>
              <input name="meta-box-post2sendy-radio" type="radio" value="Custom" checked> Custom:
            <?php else: ?>
              <input name="meta-box-post2sendy-radio" type="radio" value="Custom"> Custom: 
            <?php endif; ?>
            <div>
              <input name="meta-box-post2sendy-date" type="date" value="<?php echo $custom_date; ?>">
              <input name="meta-box-post2sendy-time" type="time" value="<?php echo $custom_time; ?>">
            </div>
            <br>
        </div>
    <?php      
}

function add_post2sendy_meta_box() {
    add_meta_box("post2sendy-meta-box", "Post2Sendy", "post2sendy_meta_box_markup", "post", "side", "high", null);
}
add_action("add_meta_boxes", "add_post2sendy_meta_box");

function save_custom_post2sendy_meta_box($post_id, $post, $update) {
    if (!isset($_POST["meta-box-post2sendy-nonce"]) || !wp_verify_nonce($_POST["meta-box-post2sendy-nonce"], basename(__FILE__)))
        return $post_id;

    if(!current_user_can("edit_post", $post_id))
        return $post_id;

    if(defined("DOING_AUTOSAVE") && DOING_AUTOSAVE)
        return $post_id;

    if($post->post_type != "post")
        return $post_id;

    if(isset($_POST["meta-box-post2sendy-radio"])) {
        update_post_meta($post_id, "meta-box-post2sendy-radio", $_POST["meta-box-post2sendy-radio"]);
    }   

    if(isset($_POST["meta-box-post2sendy-date"])) {
        update_post_meta($post_id, "meta-box-post2sendy-date", $_POST["meta-box-post2sendy-date"]);
    }   

    if(isset($_POST["meta-box-post2sendy-time"])) {
        update_post_meta($post_id, "meta-box-post2sendy-time", $_POST["meta-box-post2sendy-time"]);
    }   
}
add_action("save_post", "save_custom_post2sendy_meta_box", 10, 3);
