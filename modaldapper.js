
var modaldapper_path = modaldapper_get_path() + '/modaldapper.php';

function modaldapper_submit_login() {
   var login = $('#modaldapper-login').val();
   $.get( modaldapper_path + '?action=login&login=' + login, function( data ) {
      // Display the token form.
      $('#modaldapper-window').html( data );
      $('#modaldapper-close').click( modaldapper_close );
   } );
}

function modaldapper_reset_link() {
   $('<link>')
      .appendTo( $('head') )
      .attr( {type : 'text/css', rel : 'stylesheet'} )
      .attr( 'href', modaldapper_get_path() + 'modaldapper.css' );
   $.get( modaldapper_path, function( data ) {
      // Display the login form.
      $.modal(
         '<div id="modaldapper-window">' + data + '</div>',
         { 'escClose': true }
      );
      $('#modaldapper-login-submit').click( modaldapper_submit_login );
      $('#modaldapper-close').click( modaldapper_close );
   } );
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

