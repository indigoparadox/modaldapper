
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
         var login = $('#modaldapper-' + action).val();
         modaldapper_path_query += '?action=' + action + '&login=' + login;
         break;
   }

   $.get( modaldapper_path_query, function( data ) {
      // Display the token form.
      $('#modaldapper-window').html( data );
      $('#modaldapper-submit').click( modaldapper_submit );
      $('#modaldapper-close').click( modaldapper_close );
   } );
}

function modaldapper_reset_link() {
   $('<link>')
      .appendTo( $('head') )
      .attr( {type : 'text/css', rel : 'stylesheet'} )
      .attr( 'href', modaldapper_get_path() + 'modaldapper.css' );
   $.modal(
      '<div id="modaldapper-window"></div>',
      { 'escClose': true }
   );
   modaldapper_submit();
}

function modaldapper_close() {
   
}

function modaldapper_get_path() {
   return $('script[src]').last().attr( 'src' ).split( '?' )[0].split( '/' )
      .slice( 0, -1 ).join( '/' ) + '/';
}

$(document).ready( function() {
   $('#password-reset-ldap').click( modaldapper_reset_link );
} );

