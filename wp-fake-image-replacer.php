<?php
/*
Plugin Name: WP Fake Image Replacer
Plugin URI:
Description: Replace an empty post thumbnail with a same size fake image. Useful in theme developement process. Just activate and it works.
Version: 1.2
License: GPL
Author: Maxime BERNARD-JACQUET
Author URI: http://dysign.fr
*/

if ( !defined('ABSPATH')) die();

class WPFakeImageReplacer 
{

    public $img_lib_url = "holder.js";     // name of the js lib
    public $nb_gal_pics = 6 ;              // number of fake images to put in a gallery

  
    public function __construct()
    {

        // load holder.js
        add_action('wp_enqueue_scripts', array($this, 'wpfir_load_scripts'));

        // add filter for post thumbnails
        add_filter( 'post_thumbnail_html', array($this,'wpfir_post_thumbnail_html'), 10, 5 );

        // ACF Support    
        global $acf;

        if ($acf) 
        {
            // Image
            add_filter('acf/format_value_for_api/type=image', array($this, 'wpfir_acf_image_filter'), 11, 3);
            
            // Gallery
            add_filter('acf/format_value_for_api/type=gallery', array($this, 'wpfir_acf_gallery_filter'), 11, 3);
        }    

    }



    /**
     *
     * Load plugin javascript
     * 
     * Action. Add holder.js script in WP
     *
     */

    public function wpfir_load_scripts()
    {
      if (!is_admin()) 
      {
        wp_enqueue_script('holder', plugins_url('./js/'.$this->img_lib_url, __FILE__), '', '', false);
      }
    }



    /**
     *
     * Filter for WP Post Thumbnail
     * 
     * Filter. Replace empty WP image by a fake image
     *
     * @param string $html the original html code to output
     * @param int $post_id id of the post
     * @param int $post_thumbnail_id the post thumbnail id
     * @param array $attr some attributes
     * @return array of the modified value
     *
     */

    public function wpfir_post_thumbnail_html( $html, $post_id, $post_thumbnail_id, $size, $attr )
    {

        // check for empty post thumbnail
        if (empty( $html )) 
        {

            $s = $this->wpfir_get_thumbnail_size($size);

            // replace with holder image
            $html = '<img data-src="'.$this->img_lib_url.'/'.$s['w'].'x'.$s['h'].'">';
        }

        return $html;
    }


    /**
     *
     * Support for ACF Image field
     * 
     * Filter. Replace empty ACF image by a fake image
     *
     * @param array $value the default field value
     * @param int $post_id id of the post
     * @param array $field the field parameters
     * @return array of the modified value
     *
     */

    public function wpfir_acf_image_filter($value, $post_id, $field)
    {
        
        // if no image is set
        if (!$value) 
        {
            // Save format
            $save_format = $field['save_format'];
            
            // Object
            if ($save_format == "object") 
            {

                $s = $this->wpfir_get_all_thumbnail_sizes();

                $large = $this->wpfir_get_thumbnail_size('large');

                // set this instead of empty datas
                $value = array(
                    'id' => 0,
                    'title' => 'a Fake Image',
                    'caption' => 'a Fake Image',
                    'description' => 'a Fake Image',
                    'url' => $this->img_lib_url.'/'.$large['w'].'x'.$large['h'],
                    'sizes' => $s
                );
            }

            // ** Sorry but I can't do anything more with ID and URL **
            if ($save_format == "url" or $save_format == "id" )
            {
                $value = $this->img_lib_url.'/1280x800';
            }
     
        }

        return $value;
    }


    /**
     *
     * Support for ACF Gallery field
     * 
     * Filter. Replace empty ACF gallery by three fake images
     *
     * @param array $value the default field value
     * @param int $post_id id of the post
     * @param array $field the field parameters
     * @return array of the modified value
     *
     */

    public function wpfir_acf_gallery_filter($value, $post_id, $field)
    {

        // if gallery is empty
        if (!$value) 
        {

            $s = $this->wpfir_get_all_thumbnail_sizes();

            $large = $this->wpfir_get_thumbnail_size('large');

            // set this instead of empty datas
            $image = array(
                'id' => 0,
                'title' => 'a Fake Image',
                'caption' => 'a Fake Image',
                'description' => 'a Fake Image',
                'url' => $this->img_lib_url.'/'.$large['w'].'x'.$large['h'],
                'sizes' => $s
            );

            // insert more than one pic in gallery
            for ($i=0;$i<$this->nb_gal_pics;$i++)
            {
                $value[$i] = $image;
            }
     
        }

        return $value;
    }




    /**
     *
     * Get a specific thumbnail size
     * 
     * Helper. Get a size from default and Theme added images sizes
     *
     * @param string $size the name of the size
     * @return array of urls
     *
     */

    private function wpfir_get_thumbnail_size($size)
    {

        $default_wp_sizes = array('thumbnail', 'medium', 'large');

        // default Wordpress Thumb sizes
        if (in_array($size, $default_wp_sizes))
        {

            $s['w'] = get_option($size."_size_w");
            $s['h'] = get_option($size."_size_h");
        }

        // Additionnal size (added in functions.php with set_post_thumbnail_size or add_image_size)
        else {

            global $_wp_additional_image_sizes;

            // get Width and Height
            $s['w'] = $_wp_additional_image_sizes[$size]['width'];
            $s['h'] = $_wp_additional_image_sizes[$size]['height'];

        }

        return $s;

    }


    /**
     *
     * Get all Wordpress thumbnail sizes
     * 
     * Helper. Get Default and Theme added images sizes
     *
     * @return array of urls
     *
     */

    private function wpfir_get_all_thumbnail_sizes()
    {

        // default Wordpress Thumb sizes

        $default_wp_sizes = array('thumbnail', 'medium', 'large');

        foreach($default_wp_sizes as $size) 
        {

            $w = get_option($size."_size_w");
            $h = get_option($size."_size_h");

            $s[$size] = $this->img_lib_url.'/'.$w.'x'.$h;
        }

        // Additionnal size (added in functions.php with set_post_thumbnail_size or add_image_size)

        global $_wp_additional_image_sizes;

        foreach($_wp_additional_image_sizes as $key => $value) 
        {
            $s[$key] = $this->img_lib_url.'/'.$value['width'].'x'.$value['height'];
        }

        return $s;
    }

}

$wpfir = new WPFakeImageReplacer();
