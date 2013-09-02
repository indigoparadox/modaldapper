
var modaldapper_path = modaldapper_get_path() + 'modaldapper.php';

function modaldapper_submit() {

   // Figure out what the current form does and build the submit path
   // accordingly. Sanitization and validation happen server-side.
   var action = $('#modaldapper-action').val();
   var modaldapper_path_query = modaldapper_path;
   switch( action ) {
      case 'token':
         break;

      case 'login':
         var login = $('#modaldapper-login').val();
         var retrieve = $('#modaldapper-retrieve').val();
         modaldapper_path_query += '?action=' + action + '&login=' + login +
            '&retrieve=' + retrieve;
         break;
   }

   // TODO: Disable continue button until load complete.

   // Fade out during load and then fade back in.
   $('#modaldapper-window-contents').animate(
      { 'opacity': 0 },
      250,
      function() {
         $.get( modaldapper_path_query, function( data ) {
            // Display the token form.
            $('#modaldapper-window-contents').html( data ).animate(
               { 'opacity': 1 }, 250
            );
            $('#modaldapper-submit').click( modaldapper_submit );
            $('#modaldapper-close').click( modaldapper_close );
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
   $('#password-reset-ldap').click( modaldapper_reset_link );
} );

