<?php
/**
*   Plugin Name: FanDistro Favorites
*   Description: Display your favorite FanDistro tracks in a player on your site.
*   Version: 1.0
*   Author: Travis Ballard
*   Author URI: http://www.travisballard.com
*
*   Copyright 2012 Travis Ballard
*/

class FanDistroFavorites
{
    private $widget_id;
    private $user_id;
    private $settings;

    /**
    * magic
    *
    */
    public function __construct()
    {
        $this->hooks();
        $this->settings = $this->get_settings();

        # check for widget id, if not set notice user to enter fandistro user id. when user id is saved we will send to api and create widget id.
        $key = $this->get_api_key();
        if( empty( $key ) )
            add_action( 'admin_notices', array( $this, 'empty_api_key_notice' ) );
    }

    /**
    * hook into WordPress core
    *
    */
    public function hooks()
    {
        add_action( 'widgets_init', create_function( '', 'return register_widget( "FanDistroWidget" );' ) );
        add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_print_styles', array( $this, 'enqueue_styles' ) );
    }

    /**
    * notice about user id not being set
    *
    */
    public function empty_api_key_notice(){
        printf( '<div class="updated fade"><p>You need to <a href="%s">Enter Your FanDistro API Key</a> in order for the FanDistro Widget to work.</p></div>', admin_url( 'options-general.php?page=fandistro-settings' ) );
    }


    /**
    * add settings page to menu
    *
    */
    public function register_settings_page(){
        add_submenu_page( 'options-general.php', 'FanDistro Settings', 'FanDistro Settings', 'manage_options', 'fandistro-settings', array( $this, 'settings_page' ) );
    }

    /**
    * settings page
    *
    */
    public function settings_page(){
        $this->load( 'settings' );
    }

    /**
    * load a template file
    *
    * @param string $file
    */
    public function load( $file )
    {
        $file = sprintf( '%s/inc/tpl/%s.php', dirname( __FILE__ ), $file );
        if( file_exists( $file ) && is_readable( $file ) )
            include( $file );
        else
        {
            if( ! is_readable( $file ) && file_exists( $file ) )
                wp_die( sprintf( 'Unable to load template file %s. File is not readable. Check permissions and try again.', $file ) );
            elseif( ! file_exists( $file ) )
                wp_die( sprintf( 'Unable to load template file %s. File does no exist.', $file ) );
        }
    }

    /**
    * register theme settings
    *
    */
    public function register_settings(){
        register_setting( 'fd_settings', 'fd_settings' );
    }

    /**
    * get settings
    *
    */
    public function get_settings()
    {
        if( ! $settings = get_option( 'fd_settings' ) ){
            $settings = $this->default_settings();
            update_option( 'fd_settings', $settings );
        }

        return (object)$settings;
    }

    /**
    * default settings
    *
    */
    public function default_settings(){
        return array( 'api_key' => '' );
    }

    /**
    * get api key
    *
    */
    public function get_api_key(){
        return $this->settings->api_key;
    }

    /**
    * enqueue javascript
    *
    */
    public function enqueue_scripts()
    {
        wp_register_script( 'sm2', plugins_url( '/inc/js/soundmanager2.js', __FILE__ ) , array(), '1.0' );
        wp_register_script( 'cookie', 'http://fandistro.com/inc/js/cook.js', array( 'jquery' ), '1.0');
        wp_register_script( 'sbscroller', plugins_url( 'inc/js/jquery.sbscroller.js', __FILE__ ) , array( 'jquery', 'jquery-ui-core' ), '1.0' );
        wp_enqueue_script( 'fandistro_favorites', plugins_url( 'inc/js/fdf.js', __FILE__ ) , array( 'jquery', 'jquery-ui-slider', 'sm2', 'cookie', 'sbscroller' ), '1.0' );

        wp_localize_script( 'sm2', 'sm', array( 'swfurl' => sprintf( '%s/inc/swf/', str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, dirname( __FILE__ ) ) ), 'apikey' => $this->get_api_key() ) );
    }

    /**
    * enqueue styles
    *
    */
    public function enqueue_styles(){
        wp_enqueue_style( 'fandistro-favorites-widget', plugins_url( 'inc/css/favorites.css', __FILE__ ), array(), '1.0', 'screen' );
    }
}
$fdf = new FanDistroFavorites();


/**
*   widget
*/
class FanDistroWidget extends WP_Widget
{
    private static $api_url = 'http://api.fandistro.com';
    private $apikey;
    private $tracks;

    /**
    * magic
    *
    */
    public function FanDistroWidget()
    {
        global $fdf;
        $this->apikey = $fdf->get_api_key();
        parent::WP_Widget( 0, 'FanDistro Favorites Widget' );
    }

    /**
    * widget form
    *
    * @param mixed $i - instance
    */
    public function form( $i )
    {

    }

    /**
    * update widget
    *
    * @param mixed $n - new instance
    * @param mixed $o - old instance
    */
    public function update( $n, $o )
    {

    }

    /**
    * widget output
    *
    * @param mixed $a - args
    * @param mixed $i - instance
    */
    public function widget( $a, $i )
    {
        extract( $a );
        $tracks = $this->get_favorite_tracks();
        $all_tracks = (array)$tracks;
        $first_track = array_shift( $all_tracks );
        $first_track = $first_track[0];
        unset( $all_tracks );

        echo $before_widget;
        ?>
        <div class="fandistro-favorites-widget" data-wid="<?php echo $this->get_wid(); ?>">
            <a class="fd-btn" href="http://fandistro.com?wid=<?php echo $this->get_wid(); ?>">FanDistro</a>
            <div class="track-list-container">
                <ol class="track-list">
                    <?php
                        $x=0;
                        foreach( $tracks as $artist => $tracks )
                        {
                            foreach( $tracks as $track ){
                                printf(
                                    '<li><a data-grp="%d" data-cover="%s" data-hash="%s" data-rel="grp=%d&track=%s" data-wave="http://fandistro.com/wav/wavs/img/%d/%s.png" data-artist="%s" data-track="%s" class="%s" href="%s"><span class="num">%s</span> %s: %s</a></li>',
                                    intval( $track->grp_id ),
                                    $this->get_image( $track->grp_id ),
                                    $track->hash,
                                    $track->grp_id,
                                    urlencode( $track->name ),
                                    $track->grp_id,
                                    $track->name,
                                    $artist,
                                    $track->name,
                                    ++$x == 1 ? 'current-track' : '',
                                    sprintf( 'http://fandistro.com/track/?grp=%d&track=%s&wid=%d', $track->grp_id, $track->name, $this->get_wid() ),
                                    sprintf( '%02d. ', $x ),
                                    $artist,
                                    $track->name
                                );
                            }
                        }
                    ?>
                </ol>
            </div>
            <div class="player">
                <div class="artwork">
                    <img src="<?php echo $this->get_image( $first_track->grp_id ); ?>" alt="" />
                </div>
                <div class="info-wrap">
                    <div class="wave">
                        <div class="scrubber"></div>
                        <img class="wave-image"src="http://fandistro.com/wav/wavs/img/<?php echo $first_track->grp_id; ?>/<?php echo $first_track->name; ?>.png" alt="" />
                        <div class="white">
                            <span class="time"><span class="current">00:00</span> / <span class="total">00:00</span></span>
                        </div>
                    </div>
                    <a href="javascript://" class="play-btn paused" data-wave="http://fandistro.com/wav/wavs/img/<?php echo $first_track->grp_id; ?>/<?php echo $first_track->name; ?>.png" data-cover="<?php echo $this->get_image( $first_track->grp_id ); ?>" data-rel="<?php printf( 'grp=%d&track=%s', $first_track->grp_id, urlencode( $first_track->name ) ); ?>" data-hash="<?php echo $first_track->hash; ?>">Play</a>
                    <p class="track-title"><?php printf( '%s: %s', $first_track->artist, $first_track->name ); ?></p>
                    <div class="track-options">
                        <a href="<?php echo $this->get_action_link( $first_track->grp_id, $first_track->name, 'buy' ); ?>" class="buy fdf-button">Buy</a>
                        <a href="<?php echo $this->get_action_link( $first_track->grp_id, $first_track->name, 'distro' ); ?>" class="distro fdf-button">Distro</a>
                    </div>
                </div>
                <div class="fdfclear"></div>
            </div>
        </div>
        <?php
        echo $after_widget;
    }

    /**
    * get favorite tracks from api
    *
    */
    public function get_favorite_tracks()
    {
        $reply = $this->get_cached_api_response();
        return (object)array_reverse( (array)$reply->tracks );
    }

    public function get_wid(){
        $reply = $this->get_cached_api_response();
        return $reply->wid;
    }

    /**
    * get cached reply from fandistro api
    *
    */
    public function get_cached_api_response()
    {
        if( false === ( $reply = get_transient( 'fd_favorites' ) ) )
        {
            $response = wp_remote_post( self::$api_url, array(
                'method' => 'POST',
                'timeout' => 45,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking' => true,
                'headers' => array(),
                'body' => array( 'action' => 'get_favorites', 'api_key' => $this->apikey ),
                'cookies' => array()
            ) );

            if( ! is_wp_error( $response ) )
            {
                $reply = json_decode( $response['body'] );
                if( $reply->status == 'ok' ){
                    set_transient( 'fd_favorites', $reply );
                }
            }
        }

        return $reply;
    }

    /**
    * get cover thumbnail
    *
    * @param mixed $grp_id
    * @return string
    */
    public function get_image( $grp_id ){
        return sprintf( 'http://fandistro.com/coverArt/thumbs/%d.jpg' , $grp_id );
    }

    /**
    * get widget action url
    *
    * @param mixed $grp
    * @param mixed $track
    * @param mixed $action - buy or distro
    * @return string
    */
    public function get_action_link( $grp, $track, $action = 'buy' ){
        return sprintf( 'http://fandistro.com/track/?grp=%d&wid=%d&track=%s&action=%s', intval( $grp ), intval( $this->get_wid() ), urlencode( $track ), strtolower( $action ) );
    }
}