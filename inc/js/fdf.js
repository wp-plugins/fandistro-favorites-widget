var currently_playing = false;
var current_hash = false;
var is_playing = false;
var mousedown = 0; // current mouse state - we do not advance scrubber on mouse down
var continuous = true; // play next track when one finishes

jQuery( document ).ready( function() {

    document.body.onmousedown = function(){ ++mousedown; }
    document.body.onmouseup = function(){ --mousedown; }

    jQuery( '.fdf-button' ).live( 'click', function(){
        jQuery(this).blur();
        window.open( jQuery(this).attr('href') );
        return false;
    } );

    soundManager.setup({
      preferFlash: true,
      flashVersion: 9,
      url: sm.swfurl,
      useHighPerformance: true,
      wmode: 'transparent',
      debugMode: false,
      onready: doInit
    });

    function doInit()
    {
        /**
        *   play track clicked in list
        */
        jQuery('.fandistro-favorites-widget ol.track-list li a').click(function(){
            jQuery(this).blur();
            var next = jQuery(this).parent().next('li').find('a');
            var track = 'http://fandistro.com/inc/listen3b.php5?'+ jQuery(this).data( 'rel' ) + '&apikey=' + sm.apikey + '&hash=' + jQuery(this).data('hash');

            jQuery( '.fandistro-favorites-widget .artwork img' ).attr( {src: jQuery(this).data('cover') } );
            jQuery( '.fandistro-favorites-widget .wave-image' ).attr( {src: jQuery(this).data('wave') } );

            if( currently_playing )
                currently_playing.destruct();

            is_playing = true;

            jQuery( '.track-title' ).html( '<marquee scrollamount="1">' + jQuery(this).data('artist') + ': ' + jQuery(this).data('track') + '</marquee>' );
            jQuery('.play-btn').removeClass( 'paused' );

            jQuery( '.track-options .buy' ).attr( { href: get_action_link( jQuery(this).data('grp'), jQuery(this).data('track'), 'buy' ) } );
            jQuery( '.track-options .distro' ).attr( { href: get_action_link( jQuery(this).data('grp'), jQuery(this).data('track'), 'distro' ) } );

            currently_playing = soundManager.createSound({
                id: 'id' + jQuery(this).data('hash'),
                url: track,
                scrubber: true,
                stream: true,
                autoLoad: true,
                autoPlay: true,
                multiShot: true,
                volume: 100,
                whileplaying: function(){
                    var h = Math.floor( ( this.position / 1000 ) / 3600 % 24 );
                    var m = Math.floor( ( this.position / 1000 ) / 60 % 60 );
                    var s = Math.floor( ( this.position / 1000 ) % 60 );
                    var current_position = pad( m, 2 )  + ':' + pad( s, 2 );

                    var duration = this.durationEstimate;
                    h = Math.floor( ( duration / 1000 ) / 3600 % 24 );
                    m = Math.floor( ( duration / 1000 ) / 60 % 60 );
                    s = Math.floor( ( duration / 1000 ) % 60 );
                    var total = pad( m, 2 )  + ':' + pad( s, 2 );

                    jQuery( '.fandistro-favorites-widget .wave .white .time .current' ).html( current_position );
                    jQuery( '.fandistro-favorites-widget .wave .white .time .total' ).html( total );
                    if( ! mousedown ) jQuery('.scrubber .ui-slider-handle').css({left: parseInt( ( this.position / duration ) * 100 ) + '%' });
                },
                onfinish: function(){
                    if( ! continuous ){
                        is_playing = false;
                        jQuery('.play-btn').addClass( 'paused' );
                        jQuery( '.fandistro-favorites-widget .wave .white .time .current' ).html( '00:00' );
                    }
                    else
                        next.click();
                }
            });

            jQuery( '.current-track' ).removeClass( 'current-track' )
            jQuery( this ).addClass( 'current-track' );

            return false;
        });


        /**
        *   default/first track
        */
        var track = 'http://fandistro.com/inc/listen3b.php5?'+ jQuery( '.play-btn' ).data( 'rel' ) + '&apikey=' + sm.apikey + '&hash=' + jQuery('.play-btn').data('hash');
        currently_playing = soundManager.createSound({
                id: 'id' + jQuery('.play-btn').data('hash'),
                url: track,
                stream: true,
                scrubber: true,
                autoLoad: true,
                autoPlay: true, // true so that the timer loads the duration.
                multiShot: true,
                volume: 100,
                whileplaying: function(){
                    var h = Math.floor( ( this.position / 1000 ) / 3600 % 24 );
                    var m = Math.floor( ( this.position / 1000 ) / 60 % 60 );
                    var s = Math.floor( ( this.position / 1000 ) % 60 );
                    var current_position = pad( m, 2 )  + ':' + pad( s, 2 );

                    var duration = this.durationEstimate;
                    h = Math.floor( ( duration / 1000 ) / 3600 % 24 );
                    m = Math.floor( ( duration / 1000 ) / 60 % 60 );
                    s = Math.floor( ( duration / 1000 ) % 60 );
                    var total = pad( m, 2 )  + ':' + pad( s, 2 );

                    jQuery( '.fandistro-favorites-widget .wave .white .time .current' ).html( current_position );
                    jQuery( '.fandistro-favorites-widget .wave .white .time .total' ).html( total );
                    if( ! mousedown ) jQuery('.scrubber .ui-slider-handle').css({left: parseInt( ( this.position / duration ) * 100 ) + '%' });
                },
                onfinish: function(){
                    is_playing = false;
                    if( ! continuous ){
                        jQuery('.play-btn').addClass( 'paused' );
                        jQuery( '.fandistro-favorites-widget .wave .white .time .current' ).html( '00:00' );
                    }
                    else
                    {
                        current_hash = jQuery('.play-btn').data('hash');
                        jQuery( 'ol.track-list li a').each(function(){
                            if( jQuery(this).data('hash') == current_hash ){
                                jQuery(this).parent().next('li').find('a').click();
                            }
                        });
                    }
                }
            }).pause();

            /**
            *   play/pause events
            */
            jQuery( '.play-btn' ).click( function(){
                // play or pause
                if( is_playing ){
                    currently_playing.pause();
                    jQuery(this).addClass( 'paused' );
                    is_playing = false;
                } else {
                    var title = jQuery( '.track-title' ).html();
                    if( /<marquee/i.test( title ) ){
                        // do nothing
                    } else {
                        jQuery( '.track-title' ).html( '<marquee scrollamount="1">' + title + '</marquee>' );
                    }

                    // if at end of track, play instead of resume
                    if( currently_playing.position != currently_playing.duration )
                        currently_playing.resume();
                    else
                        currently_playing.play();

                    jQuery(this).removeClass( 'paused' );
                    is_playing = true;
                }
                jQuery(this).blur();
            });

            /**
            *   ui-slider scrubber, update player position onchange.
            *   uses 100 for max value since the duration isn't available directly on page load. onchange we calculate the time based on a percentage
            *   of the total duration. Hopefully by the time a user has clicked the scrubber to update the position, the duration will be fully loaded.
            */
            jQuery('.scrubber').slider({
                animate:true,
                range: "min",
                value:0,
                min:1,
                max: 100,
                change: function( e, ui ){
                    ++mousedown;
                    setTimeout( function(){ // timeout to give currently_playing.duration a chance to load.
                        var selected = '.' + ui.value;
                        currently_playing.setPosition( parseInt( ( ( selected / 100 ) * 100 ) * currently_playing.duration ) ); // ((percentage/100)*100) * total, in timeout because sound/duration needs to be loaded
                        --mousedown;
                    }, 100 );
                }
            });
    }

    /**
    *   pad integers with 0's
    */
    function pad( n, l )
    {
        var s = '' + n;
        while( s.length < l ) { s = '0' + s; }
        return s;
    }

    /**
    *   return action links for buy/distro buttons
    */
    function get_action_link( grp, track, action )
    {
        if( typeof( action ) == 'undefined' ) action = 'buy';
        return 'http://fandistro.com/track/?grp=' + grp + '&wid=' + jQuery( '.fandistro-favorites-widget' ).data('wid') + '&track=' + encodeURIComponent( track ) + '&action=' + action;
    }

} );