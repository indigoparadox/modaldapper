
var modaldapper_path = modaldapper_get_path() + 'modaldapper.php';

function modaldapper_submit() {

   // Figure out what the current form does and build the submit path
   // accordingly. Sanitization and validation happen server-side.
   var action = $('#modaldapper-action').val();
   var modaldapper_path_query = modaldapper_path + '?action=' + action;
   switch( action ) {
      case 'token':
         var login = $('#modaldapper-login').val();
         var token = $('#modaldapper-token').val();
         modaldapper_path_query += '&login=' + login + '&token=' + token;
         break;

      case 'login':
         var login = $('#modaldapper-login').val();
         var retrieve = $('#modaldapper-retrieve').val();
         modaldapper_path_query += '&login=' + login + '&retrieve=' + retrieve;
         break;
   }

   // Disable continue button until load complete.
   $('#modaldapper-submit').unbind( 'click' );
   $('#modaldapper-submit').attr( 'disabled', 'disabled' );

   // Fade out during load and then fade back in.
   $('#modaldapper-window-contents').animate(
      { 'opacity': 0 },
      250,
      function() {
         $.get( modaldapper_path_query, function( data ) {
            // Display the form.
            $('#modaldapper-window-contents').html( data ).animate(
               { 'opacity': 1 },
               250,
               function() {
                  $('#modaldapper-submit').bind( 'click', modaldapper_submit );
                  $('#modaldapper-submit').attr( 'disabled', false );

                  // Prepare special elements that may be present.
                  $('#modaldapper-password').passStrengthify();
               }
            );
         } );
      }
   );
}

function modaldapper_reset_link() {
   $('<link>')
      .appendTo( $('head') )
      .attr( {type : 'text/css', rel : 'stylesheet'} )
      .attr( 'href', modaldapper_get_path() + 'modaldapper.css' );
   $.modal(
      '<div id="modaldapper-window-contents"></div>' +
      '<div id="modaldapper-window-controls">' +
      '<input type="button" id="modaldapper-submit" value="Continue" />' +
      '<input type="button" class="modaldapper-close" value="Close" />' +
      '</div>',
      {
         'escClose': true,
         'closeClass': 'modaldapper-close',
         'containerId': 'modaldapper-window',
         'minWidth': 280,
         'minHeight': 230,
      }
   );
   modaldapper_submit();
}

function modaldapper_get_path() {
   return $('script[src]').last().attr( 'src' ).split( '?' )[0].split( '/' )
      .slice( 0, -1 ).join( '/' ) + '/';
}

$(document).ready( function() {
   // Load dependencies before we're allowed to proceed.
   $('#password-reset-ldap').css( 'display', 'none' );
   $.getScript(
      modaldapper_get_path() + 'jquery.simplemodal.min.js',
      function() {
         $.getScript(
            modaldapper_get_path() + 'jquery.passstrength.js',
            function() {
               $('#password-reset-ldap').fadeIn();
               $('#password-reset-ldap').click( modaldapper_reset_link );
            }
         );
      }
   );
} );

