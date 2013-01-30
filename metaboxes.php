<?php
add_action('add_meta_boxes','ppc_add_custom_metaboxes');
/*
 * Add custom meta boxes to post type edit screen
 * 
 * Use custom meta boxes on PPC pages to customize PPC pages
 */
function ppc_add_custom_metaboxes(){
    add_meta_box('my_meta_box_id','Post Submission Actions','ppc_post_submission','ppclps','normal','high');
}

/*
 * Create custom metabox for actions after submission
 * 
 * Create custom metabox to perform these actions after form submission
 */
function ppc_post_submission(){?>
    <label><strong>On Form Submission?</strong></label><input type="radio" name="post_submission[]" value="ppc_confirmation_message" checked>Display Confirmation Message <input type="radio" name="post_submission[]" value="ppc_redirect_page">Redirect To Page</p>
    <fieldset data-postsubmission="true">
        <p>Confirmation Message</p>
        <?php wp_editor('','2');?>
        <p>Other Actions</p>
        <textarea name="post_submission_events" placeholder="Place code to fire here" style="width:100%;" rows="7"></textarea>
    </fieldset>
    <fieldset data-postsubmission="true">
        <legend>Redirect Page</legend>
        <p>Select Thank You Page</p>
        <?php wp_dropdown_pages();?>
    </fieldset>
<?php
}
