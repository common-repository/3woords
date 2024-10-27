    <?php

/*
Plugin Name: 3woords - Social surveys made easy
Plugin URI: https://3woords.com/
Description: Generates plots on the basis of response. You can add this short code into page/post and it will be visible.<code>[3woords_block question-code="QUE1" question-title="Put you question title here" language="en" width="300" show-credit-link=0]</code> <br/> <strong>question-code</strong> is mandatory. width is in pixel. <strong>show-credit-link</strong> is option whether you want to show credit link or not. 
Version: 1.2
Author: Sverker
*/

class Twoords
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'twoods_add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'twoods_page_init' ) );
    }

    /**
     * Add options page
     */
    public function twoods_add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin', 
            '3woords', 
            'manage_options', 
            '3woords-setting-admin', 
            array( $this, 'twoods_create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function twoods_create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'woords_option_name' );
        ?>
        <div class="wrap">
            <h1>3woords - Settings</h1>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'twoods_option_group' );
                do_settings_sections( '3woords-setting-admin' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function twoods_page_init()
    {        
        register_setting(
            'twoods_option_group', // Option group
            'woords_option_name', // Option name
            array( $this, 'twoords_sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            '', // access_key
            array( $this, 'twoords_print_section_info' ), // Callback
            '3woords-setting-admin' // Page
        );  

        add_settings_field(
            'access_token', // ID
            'Access Token', // access_key 
            array( $this, 'twoords_access_token_callback' ), // Callback
            '3woords-setting-admin', // Page
            'setting_section_id' // Section           
        );      

        add_settings_field(
            'access_key', 
            'Access Key', 
            array( $this, 'twoords_access_key_callback' ), 
            '3woords-setting-admin', 
            'setting_section_id'
        );      
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function twoords_sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['access_token'] ) )
            $new_input['access_token'] = sanitize_text_field( $input['access_token'] );

        if( isset( $input['access_key'] ) )
            $new_input['access_key'] = sanitize_text_field( $input['access_key'] );

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function twoords_print_section_info()
    {
        print "<br>Enter your credentials from 3woords : <br>(for more <a href='http://api.3woords.com/'> 3woords</a> )";
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function twoords_access_token_callback()
    {
        printf(
            '<input class="regular-text" type="text" id="access_token" name="woords_option_name[access_token]" value="%s" />',
            isset( $this->options['access_token'] ) ? esc_attr( $this->options['access_token']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function twoords_access_key_callback()
    {
        printf(
            '<input class="regular-text" type="text" id="access_key" name="woords_option_name[access_key]" value="%s" />',
            isset( $this->options['access_key'] ) ? esc_attr( $this->options['access_key']) : ''
        );
    }
}

if( is_admin() )
    $my_settings_page = new Twoords();

// shortcode function
function twoords_woordsPlot_func( $atts ) {

    global $content;
    ob_start();

    $lang =  $atts['language'] ? $atts['language'] : 'en';
    $width =  $atts['width'] ? $atts['width'].'px' : 'auto';
    $qcode =  $atts['question-code'] ? $atts['question-code'] : null;

    // credit link will not show as per plugin guidelines
    $showCreditLink =  $atts['show-credit-link'] ? $atts['show-credit-link'] : 0;

    $option = get_option('woords_option_name',true);

    // get credentials for api from settings
    $accessToken = $option['access_token'];
    $accessKey = $option['access_key'];

    // random string on single page for make unique
    $rand_s = rand(100000,999999); 

    // background color of cloud and color map
    $twoods_background =  $atts['background'] ? $atts['background'] : '255,255,255';
    $twoods_colormap =  $atts['colormap'] ? $atts['colormap'] : '';

    if (!$accessToken || !$accessKey) {
        echo 'Please set credentials before using 3woords API.';
        return;
    } 
    if (is_null($qcode)) {
        echo '"question-code" option is required.';
        return;
    }
    
    ?>
    <div class="clearboth"></div>
    <div class="woords-panel ren_<?php echo $rand_s; ?>" style="width:<?php echo $width; ?>;">
        <form class="words-form" onsubmit="return false;">
            <div class="form-group">
                <span class="woords-tspan">Tell your opinion in three words</span>

                <h3 class='woords-question-title'><?php echo $atts['question-title']; ?></h3>
                <div class="woords-question-blocks">
                    <div class="twoords-response-box">
                        <label>1.</label>
                        <input type="text" class="form-control" autocomplete="off" name="woords1" required="">
                    </div>
                    <div class="twoords-response-box">    
                        <label>2.</label>
                        <input type="text" class="form-control" autocomplete="off" name="woords2" required="">
                    </div>
                    <div class="twoords-response-box">
                        <label>3.</label>
                        <input type="text" class="form-control" autocomplete="off" name="woords3" required="">
                    </div>
                </div>   
            </div>
            <div class="woords-button-section">
                <button type="submit" class="woods-button">Submit</button>
            </div>
            <div class="woords-response"></div>
            <?php if ($showCreditLink) { ?>
                <div class="word3-placeholder">
                    Powered by <strong><a target="_blank" href="https://3woords.com/">3woords</a></strong>
                </div>
            <?php } ?>
        </form>
    </div>
    <style>
        .woords-tspan{
    display: block;
    text-align: center;
    font-size: 0.9em;
}
.woords-question-blocks {
    margin-bottom: 15px;
    width: 100%;
    display: block;
    float: left;
    margin: 0 auto;
}
        .woords-panel .form-group input[type="text"]{
            border: none;
            border-bottom: 1px solid #000;
            border-radius: 0;
            box-sizing: border-box;
            margin: 0 5px;
            display: inline-block;
            min-width: 100px;
            padding: 0px 0px;
            width:calc(100% - 22px);
            float: right;
        }
        .twoords-response-box label{
            width: 12px;
    display: inline-block;
}
    .twoords-response-box{ width: calc(33.30% - 15px);float: left; min-width: 180px;}
        

        @media screen and (max-width: 460px) {
            .woords-panel .form-group input[type="text"]{
                border: none;
                border-bottom: 1px solid #000;
                border-radius: 0;
                box-sizing: border-box;
                margin: 0 5px;
                display: inline-block;
                min-width: 100px;
            }
                .twoords-response-box{ width: 100%;}

        }
        
        .woords-question-title{text-align: center;margin-bottom: 5px;}
        .woords-question-blocks input,.woords-question-blocks input:focus{background: transparent;}
        .woords-question-blocks{margin-bottom: 15px;}
        .woords-button-section{text-align: center;}
        .woords-button-section .woods-button{padding: 8px 10px;font-weight: normal;background: #333;color: #FFF;border-radius: 0;}
        .woords-button-section .woods-button:hover{background: #111;}
        .resoponse-cloud{position: relative;}
        .word3-placeholder{text-align: right;}
        .word3-placeholder a{text-decoration: none;opacity: 0.8}
        .woords-panel{position: relative;}
    </style>
    <script>
        // Fetch wordclound from API
        (function($){
            $('.ren_<?php echo $rand_s; ?> .words-form').submit(function(event){
                event.preventDefault();
                if ( $('.ren_<?php echo $rand_s; ?> [name="woords1"]').val() == '' ||
                    $('.ren_<?php echo $rand_s; ?> [name="woords2"]').val() == '' ||
                    $('.ren_<?php echo $rand_s; ?> [name="woords3"]').val() == '') {
                    return false;
                }
                $.ajax({
                    type: 'POST',
                    url: 'https://3woords.com/api/get-plot',
                    dataType: 'json',
                    beforeSend: function(request) {
                        $('.ren_<?php echo $rand_s; ?> .woords-response').html('Loading..');
                        request.setRequestHeader("access-key", '<?php echo $accessKey; ?>');
                        request.setRequestHeader("access-token", '<?php echo $accessToken; ?>');
                    },
                    data: {
                        language: '<?php echo $lang; ?>',
                        data: [$('.ren_<?php echo $rand_s; ?> [name="woords1"]').val()+' '+$('.ren_<?php echo $rand_s; ?> [name="woords2"]').val()+' '+$('.ren_<?php echo $rand_s; ?> [name="woords3"]').val()],
                        questionCode: '<?php echo $qcode; ?>',
                        identifier: [Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 7)],
                        plotCloudType: 'words',
                        plotCluster: 0,
                        plotWordcloud: 1,
                        plotBackGroundColor : '<?php echo $twoods_background; ?>',
                        plotColorMap : '<?php echo $twoods_colormap; ?>'
                    },
                    success: function(data){
                        if(data.success){
                            $('.ren_<?php echo $rand_s; ?> .woords-response').html('<img src = "'+data.results['plot-url']+'">');
                            $('.ren_<?php echo $rand_s; ?> [name="woords1"]').val('');
                            $('.ren_<?php echo $rand_s; ?> [name="woords2"]').val('');
                            $('.ren_<?php echo $rand_s; ?> [name="woords3"]').val('');
                        }else{
                            $(',ren_<?php echo $rand_s; ?> .woords-response').html('Something went wrong. Try again.');
                        }
                    }
                });
            });

            // Fetch last plot word cloud
            $.ajax({
                type: 'POST',
                url: 'https://3woords.com/api/get-last-plot',
                dataType: 'json',
                beforeSend: function(request) {
                    $('.woords-response').html('Loading..');
                    request.setRequestHeader("access-key", '<?php echo $accessKey; ?>');
                    request.setRequestHeader("access-token", '<?php echo $accessToken; ?>');
                },
                data: {
                    questionCode: '<?php echo $qcode; ?>',
                },
                success: function(data){
                    if(data.success){
                        $('.ren_<?php echo $rand_s; ?> .woords-response').html('<img src = "'+data['plot-url']+'">');
                    }else{
                       $('.ren_<?php echo $rand_s; ?> .woords-response').html(''); 
                    }
                }
            });
        })(jQuery);
    </script>
    <?php  $output = ob_get_clean();
    return $output;
}

// create shortcode for cloud
add_shortcode( '3woords_block', 'twoords_woordsPlot_func' );

// Add setting link
function woords_settings_link( $links ) {
    $settings_link = '<a href="options-general.php?page=3woords-setting-admin">' . __( 'Settings' ) . '</a>';
    array_push( $links, $settings_link );
    return $links;
}
$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'woords_settings_link' );
