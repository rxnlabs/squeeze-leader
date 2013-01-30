<?php
/*
 * Plugin Name: Squeeze Leader
 * Description: Quickly launch and manage landing pages using different layouts.
 * Author: De'Yonte Wilkinson
 * Version: 0.5
 */
        
class SqueezeLeader{
    
    private $config;

    public function __construct(){
        //assign class properties
        $this->config = file_get_contents(plugins_url('config.json',__FILE__));

        //do WordPress actions and hooks
        register_activation_hook(__FILE__, array(&$this,'install'));
        add_action('init', array(&$this,'register_post_type') );
        add_action('add_meta_boxes', array(&$this,'add_post_meta_boxes'));  
        add_action('save_post', array(&$this,'save_components'));
    }
    public function install(){
        //http://forums.phpfreaks.com/topic/151354-forward-slash-or-back-slash/
        $source = realpath(plugin_dir_path(__FILE__).'/templates/templates.zip');
        $fs_path_array = explode( DIRECTORY_SEPARATOR, $source );
        $source = implode( '/', $fs_path_array );
        
        
        $target = get_template_directory();
        $fs_target_array = explode( DIRECTORY_SEPARATOR, $target );
        $target = implode( '/', $fs_target_array );
        
        //http://wordpress.stackexchange.com/questions/8240/anyone-using-unzip-file-successfully-it-uploads-the-zip-but-doesnt-extract-it
        WP_Filesystem();
        $unzip = unzip_file($source,$target);
  
        if( !is_bool($unzip) )
            die( var_dump($unzip) );
        //copyr($source,$target);
    }
    
    /*
     * Register the landing page post type
     */
    public function register_post_type(){
        $lp_args = array(
            'labels' => array(
                'name' => __( 'Squeeze Leader' ),
                'singular_name' => __( 'Landing Page' ),
                'add_new' => __( 'Add New Landing Page' ),
                'add_new_item' => __( 'Add New Landing Page' ),
                'edit' => __( 'Edit' ),
                'edit_item' => __( 'Edit Landing Page' ),
                'new_item' => __( 'New Landing Page' ),
                'view' => __( 'View Landing Page' ),
                'view_item' => __( 'View Landing Page' ),
                'search_items' => __( 'Search Landing Pages' ),
                'not_found' => __( 'No Landing Pages Found' ),
                'not_found_in_trash' => __( 'No Landing Pages found in Trash' ),
                'parent' => __( 'Parent Landing Page' )
            ),
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'rewrite' => array( 'slug' => 'p', 'with_front' => false ),
            'capability_type' => 'post',
            'has_archive' => true, 
            'hierarchical' => true,
            'menu_position' => null,
            'supports' => array('title','editor','thumbnail','page-attributes','revisions')
        );                          

        register_post_type('lps',$lp_args);
        flush_rewrite_rules( false );
    }
    
    /*
     * Load the metabox to select what should be loaded on the landing page
     * @link http://wp.tutsplus.com/tutorials/plugins/how-to-create-custom-wordpress-writemeta-boxes/
     */
    public function add_post_meta_boxes(){
        add_meta_box( 'my-meta-box-id', 'Squeeze Leader Components', array(&$this,'select_components'), 'lps', 'normal', 'high' );
    }
    
    public function select_components(){
        global $post;
        $json = json_decode($this->config);
        $json = $json->{'javascript'};

        //load the saved components for the post
        $values = get_post_custom( $post->ID );
        $saved_components = $values['sl_load_components'][0];
        $pattern = array('[',']','"');
        $saved_components = str_replace($pattern, "", $saved_components);
        $saved_components = explode(',',$saved_components);


        // We'll use this nonce field later on when saving.  
        wp_nonce_field( 'sl_nonce', 'sl_nonce_metabox' );
        ?>
        <p>Select which files to load</p>
        <p><strong>Javascript</strong></p>
        <table class="landing_page wp-list-table widefat" border="1" width="100%">
            <thead>
                <tr>
                    <th class="manage-column column-cb check-column"><input type="checkbox" id="landing_page_select_all"></th>
                    <th class="manage-column">Script Name</th>
                    <th class="manage-column">Version</th>
                    <th class="manage-column">Location</th>
                    <th class="manage-column">Dependency</th>
                </tr>
            </thead>
        <?php foreach($json as $obj):?>
        <tr>
            <?php $checked = in_array( $obj->{'input_name'},$saved_components );//search for the selected component to see if the user saved this component?>
            <th class="check-column"><input type="checkbox" name="<?php echo $obj->{'input_name'};?>" value="<?php echo $obj->{'input_name'};?>" <?php echo ( $checked === true ? 'checked':'');?>></th>
            <td><?php echo $obj->{'script'};?></td>
            <td><?php echo $obj->{'version'};?></td>
            <td><?php echo $obj->{'location'};?></td>
            <td><?php echo $obj->{'dependency'};?></td>
        </tr>
        <?php endforeach;?>
        </table>
<?php
    }

    /**
    *
    * Save selected components for the post
    *
    **/
    
    public function save_components($post_id){
        // Bail if we're doing an auto save  
        if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        // if our nonce isn't there, or we can't verify it, bail 
        if( !isset( $_POST['sl_nonce_metabox'] ) || !wp_verify_nonce( $_POST['sl_nonce_metabox'], 'sl_nonce' ) ) return; 
     
        // if our current user can't edit this post, bail  
        if( !current_user_can( 'edit_post' ) ) return;
        
        //load the config file to get the names of the various scripts to save as metabox values
        $json = json_decode($this->config);
        $json = $json->{'javascript'};

        $saved_components = array();
        $temp = array();

        foreach($json as $obj):
            $input_name = $obj->{'input_name'};
            if( isset( $_POST[$input_name] ) )
                $saved_components[] = esc_attr( $_POST[$input_name] );
        endforeach;

        //save all options in one post_meta row
        $saved_components = json_encode($saved_components);


        if( !empty($saved_components) AND !is_null($saved_components) )
            update_post_meta( $post_id, 'sl_load_components', $saved_components);

    }

    /**
    *
    * Search json string for value
    *Use this method to search the returned selected values to see if the compoent was selected to load
    *@link http://stackoverflow.com/questions/8691382/php-search-json-for-value
    **/

    public function searchJson($haystack, $needle)
    {
        foreach($haystack as $item)
        {
            if(isset($item) && item == $needle)
            {
                return true;
            }
        }
        return null;
    }
    
    
}
$landing_pages = new SqueezeLeader;

/** 
* Copy a file, or recursively copy a folder and its contents 
* 
* @author      Aidan Lister <aidan@php.net> 
* @version     1.0.1 
* @param       string   $source    Source path 
* @param       string   $dest      Destination path 
* @return      bool     Returns TRUE on success, FALSE on failure 
*/ 
function copyr($source,$dest){
    // Simple copy for a file 
    if ( is_file($source)) {
        chmod($dest, 777);
        return copy($source, $dest); 
    } 

    // Make destination directory 
    if (!is_dir($dest)) { 
        mkdir('food'); 
    }

    chmod($dest, 777);

    // Loop through the folder 
    $dir = dir($source); 
    while (false !== $entry = $dir->read()){ 
        // Skip pointers 
        if ($entry == '.' || $entry == '..')
            continue;

        // Deep copy directories 
        if ($dest !== "$source/$entry") 
            copyr("$source/$entry", "$dest/$entry");
    } 

    // Clean up 
    $dir->close(); 
    return true;    
}