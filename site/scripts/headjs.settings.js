/*! Javascript settings for ontowiki.net
 *
 *  Uses HeadJS to load all necessary core and plugin libraries, using different
 *  site scopes.
 *
 *  @since 1.0
 *  @package OWNET
 *  @subpackage WebsitePublic
 *
 *  @author Michael Haschke, http://48augen.de/
 */

/* scope:global
   include all core & plugin libs which are always necessary ---------------- */
head.js(
        // -- core libraries --
        //"http://ajax.googleapis.com/ajax/libs/jquery/1.5.2/jquery.min.js",
        siteBase + '/libs/jquery/jquery.min.js',
        // -- plugin libraries --
        //"http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.11/jquery-ui.min.js"
        siteBase + '/libs/jquery/jqueryui.min.js',
        siteBase + '/scripts/jquery.prefill.js'
        // -- settings and configuration --
        //"./js/zettings/global.min.js"
       );

// check document and load dynamically all optional JS
head.ready(function() {
    // scope: tabbing
    // $('.tabcontainer').tabs();

    // scope: lightbox for images and videos
    if ($('a.showlightbox').size() > 0) {
        head.js(
            // -- plugin libraries --
            "./utils/libs/jquery/accessible-ria/ui.ariaLightbox.js",
            // -- settings and configuration --
            //"./util/js/zettings/global.min.js"
            function() {
                var lb_settings = {
                    prevText: 'prev',
                    nextText: 'next',
                    titleText: function() { return 'Media Window'; },
                    pictureText: 'Media',
                    ofText: 'of',
                    closeText: 'close',
                    animationSpeed: 'fast',
                    useDimmer: true,
                    zIndex: 2000,
                    pos: "auto",
                    em: false,
                    altText: function() { return $(this).find("img").attr("alt"); },
                    descText: function() { return $(this).find("img").attr("alt"); }
                };

                $('a.showlightbox').ariaLightbox(lb_settings);

                lb_settings.imageArray = 'li.image a';

                $('.gallery').ariaLightbox(lb_settings);
            }
        );
    }
});
