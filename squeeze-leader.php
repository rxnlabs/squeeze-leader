<?php
/*
 * Plugin Name: PPC Landing Pages
 * Description: Quickly launch and manage landing pages using different layouts.
 * Author: De'Yonte Wilkinson
 * Version: 0.5
 */
        
class LandingPages{
    
    public function __construct(){
        register_activation_hook(__FILE__, array(&$this,'install'));
        add_action('init', array(&$this,'register_post_type') );
        add_action('add_meta_boxes', array(&$this,'lazy_loading'));  
    }
    public function install(){
        //http://forums.phpfreaks.com/topic/151354-forward-slash-or-back-slash/
        $source = realpath(plugin_dir_path(__FILE__).'landingpage.zip');
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
                'name' => __( 'Landing Pages' ),
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
    public function lazy_loading(){
        add_meta_box( 'my-meta-box-id', 'Landing Page Scripts', array(&$this,'select_components'), 'lps', 'normal', 'high' );
    }
    
    public function select_components(){
        global $post;
        $config = plugins_url('config.json',__FILE__);
        echo $config;
        $json = file_get_contents($config);
        $json = json_decode($json);
        $json = $json->{'javascript'};
        ?>
        <p>Select which files to load</p>
        <p><strong>Javascript</strong></p>
        <table class="landing_page" border="1" width="100%">
            <th><input type="checkbox" id="landing_page_select_all"></th>
            <th>Script Name</th>
            <th>Version</th>
            <th>Location</th>
            <th>Dependency</th>
        <?php foreach($json as $obj):?>
        <tr>
            <td><input type="checkbox"/></td>
            <td><?php echo $obj->{'script'};?></td>
            <td><?php echo $obj->{'version'};?></td>
            <td><?php echo $obj->{'location'};?></td>
            <td><?php echo $obj->{'dependency'};?></td>
        </tr>
        <?php endforeach;?>
        </table>
<?php
    }
    
}
$landing_pages = new LandingPages;

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