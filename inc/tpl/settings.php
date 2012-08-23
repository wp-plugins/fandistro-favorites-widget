<?php
    global $fdf;
    $settings = $fdf->settings;
    if( isset( $_POST['clear_cache'] ) && ! empty( $_POST['clear_cache'] ) && isset( $_POST['cc_nonce'] ) && wp_verify_nonce( $_POST['cc_nonce'], 'clear-cache' ) ){
        delete_transient( 'fd_favorites' );
        print '<div class="updated fade"><p>Cache Deleted</p></div>';
    }
?>
<div class="wrap">
    <h2>FanDistro Settings</h2>
    <p>These settings are used by the FanDistro Favorites Widget. You will need to <a href="<?php echo admin_url( 'widgets.php' ); ?>">add the widget to a sidebar</a> for it to be active on your website.</p>
    <form action="options.php" method="post">
        <?php settings_fields( 'fd_settings' ); ?>

        <table class="form-table" style="padding:10px; border:1px solid #dfdfdf; width:620px;">
            <tr>
                <th><label for="apikey">API Key:</label></th>
                <td><input name="fd_settings[api_key]" id="apikey" type="text" size="43" value="<?php echo $fdf->get_api_key(); ?>" /></td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" class="button-primary" value="Save Changes" />
        </p>
    </form>
    <h2>Cache</h2>
    <p>In order to keep api queries to a  minimum, requests are cached for a period of time. You can clear the cache and force a reload if necessary.</p>
    <form action="" method="post">
        <p>
            <input class="button" type="submit" name="clear_cache" value="Clear Cache" />
            <?php wp_nonce_field( 'clear-cache', 'cc_nonce' ); ?>
        </p>
    </form>
</div>