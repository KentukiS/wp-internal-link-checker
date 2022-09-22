<?php

/*
 * Plugin Name: Internal Links Counter
 * Author: KentukiS
 */

// Start up the engine
class WP_Internal_Links_Counter
{

    /**
     * Static property to hold our singleton instance
     *
     */
    static $instance = false;

    public $option_name = "ilc_option";
    public $ilc_limit;

    /**
     *
     *
     * @return void
     */
    private function __construct() {
        add_action('admin_menu', array($this, 'add_option_field_to_writing_admin_page'));
        add_filter('wp_insert_post_data', array($this, 'check_internal_before_save'),1,2);
    }

    /**
     * If an instance exists, this returns it.  If not, it creates one and
     * retuns it.
     *
     * @return WpInternalLinksCounter
     */

    public static function getInstance() {
        if ( !self::$instance )
            self::$instance = new self;
        return self::$instance;
    }

    /**
     * Check inout in admin panel for wrong data
     *
     * @return int
     */

    public function ilc_option_check( $val ) {
        if (!preg_match("/^([0-9])+$/", $val) ) {
            $type = 'error';
            $message = 'The "Minimal internal links for post" field must be a number';
        }
        if($message){
            add_settings_error($this->option_name, 'settings_updated', $message, $type);
        }
        if($type === 'error')
            return get_option($this->option_name);
        else
            return $val;
    }

    /**
     * Adding new option to WP system
     *
     * @return void
     */

    public function add_option_field_to_writing_admin_page(){
        if ( get_option($this->option_name) === false ){
            update_option($this->option_name, '0');
        }
        $args = array(
            'sanitize_callback' => array($this, 'ilc_option_check'),
        );

        register_setting( 'writing', $this->option_name, $args);

        add_settings_field( 
            'ilc_setting-id', 
            'Minimal internal links for post', 
            array($this, 'ilc_setting_callback_function'), 
            'writing', 
            'default', 
            array( 
                'id' => 'ilc_setting-id', 
                'option_name' => $this->option_name 
            )
        );
    }

    /**
     * Adding field in Settings page
     *
     * @return void
     */

    public function ilc_setting_callback_function($val){
        $id = $val['id'];
        $option_name = $val['option_name'];
        ?>
        <input 
            type="text" 
            name="<?php echo $option_name; ?>" 
            id="<?php echo $id; ?>" 
            value="<?php echo esc_attr(get_option($option_name)); ?>" 
        /> 
        <?
    }

    /**
     * Check posts content for internal links
     *
     * @return array
     */

    public function check_internal_before_save( $data ) {
        if($data['post_status'] != "auto-draft" && ('page' === $data['post_type'] || 'post' === $data['post_type'])){
            $domain = $this->get_domain();

            preg_match_all( '/<a(.*?)<\/a>/is', $data['post_content'], $links_array);
             
            $internal_links = array();
            foreach ($links_array[0] as $link)
            {
                if (strpos($link, $domain) !== false)
                {
                    $internal_links[] = $link;
                }
            }

            $internal_count = count($internal_links);
            $this->ilc_limit = get_option($this->option_name);

            if($this->ilc_limit > $internal_count){
                add_action( 'save_post', array($this, 'save_post_error'), 10, 3 );
            }
        }
        return $data;
    }

    /**
     *
     * @return string
     */

    public function get_domain() {
        $site_url = get_site_url();
        $parsed_url = parse_url($site_url);
        $domain = $parsed_url['host'];

        return $domain;
    }

     /**
     * Error for validation form data
     * 
     * @return void
     */

    public function save_post_error( $post_ID, $post, $update ) {
        $msg = "You can't save post within ".$this->ilc_limit." internal links in content";
        wp_die( $msg );
    }
}


// Instantiate our class
$WpInternalLinksCounter = WP_Internal_Links_Counter::getInstance();