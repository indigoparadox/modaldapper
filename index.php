<?php

/* 
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

$f3 = require( 'include/f3/base.php' );
$f3->config( 'config/modaldapper.cfg' );

require_once( 'include/sanitize.php' );

// TODO: Verify HTTPS.

function modaldapper_hash( $token ) {
   global $f3;
   return crypt( $token, '$5$rounds=5000$'.$f3->get( 'db_salt' ).'$' );
}

function modaldapper_email_admin( $op, $user, $ip ) {
   global $f3;
   if( $f3->get( 'admin_email' ) ) {
      $f3->set( 'req_op', $op );
      $f3->set( 'req_user', $user );
      $f3->set( 'req_ip', $ip );
      mail(
         $f3->get( 'admin_email' ),
         sprintf( '%s Password Reset Information', $f3->get( 'site_name' ) ),
         wordwrap(
            Template::instance()->render( 'mail/admin.txt' ), 70, "\r\n"
         ),
         sprintf( "From: %s\r\n", $f3->get( 'site_email' ) )
      );
   }
}

$f3->route( 'GET /password [ajax]', function() {
   global $f3;

   // Hash the token for comparison.
   $token_hash = modaldapper_hash( $f3->get( 'GET.token' ) );

   // Find a matching hash in the database.
   $db = new DB\SQL(
      $f3->get( 'db_config' ), $f3->get( 'db_user' ), $f3->get( 'db_pass' )
   );
   $tokens = new DB\SQL\Mapper( $db, 'tokens' );
   $tokens->load();
   while( !$tokens->dry() ) {
      if( $tokens->token_hash == $token_hash ) {
         $token_login = $tokens->login;
         // Delete all existing tokens for this user.
         $db->exec( 'DELETE FROM tokens WHERE login=:login', $token_login );
         break;
      }
      $tokens->next();
   }

   // Display an error message and hidden redirect to token form.
   if( empty( $token_login ) ) {
      echo( Template::instance()->render( 'templates/badtoken.html' ) );
      return;
   }

   // Validate the new account password.
   if(
      $f3->get( 'site_pwd_min_length' ) > strlen( $f3->get( 'GET.password' ) )
   ) {
      echo( Template::instance()->render( 'templates/badpwdshort.html' ) );
      return;
   }

   // Set the new account password.
   $ldap = require( 'include/reset.php' );
   $ldap->fields( $f3->get( 'ldap_fieldmap' ) );
   $ldap->connect(
      $f3->get( 'ldap_service_user' ), $f3->get( 'ldap_service_pass' ),
      $f3->get( 'ldap_host' ), $f3->get( 'ldap_port' ),
      $f3->get( 'ldap_version' ), $f3->get( 'ldap_base_dn' )
   );
   $success = $ldap->password( $token_login, $f3->get( 'GET.password' ) );

   modaldapper_email_admin(
      'redeemed', $token_login, $f3->get( 'SERVER.REMOTE_ADDR' )
   );

   // Display outcome.
   if( false === $success ) {
      echo( Template::instance()->render( 'templates/problem.html' ) );
   } else {
      echo( Template::instance()->render( 'templates/success.html' ) );
   }
} );

$f3->route( 'GET /tokenform [ajax]', function() {
   echo( Template::instance()->render( 'templates/token.html' ) );
} );

$f3->route( 'GET /token [ajax]', function() {
   global $f3;

   // Determine if the login is valid.
   $ldap = require( 'include/reset.php' );
   $ldap->fields( $f3->get( 'ldap_fieldmap' ) );
   $ldap->connect(
      $f3->get( 'ldap_service_user' ), $f3->get( 'ldap_service_pass' ),
      $f3->get( 'ldap_host' ), $f3->get( 'ldap_port' ),
      $f3->get( 'ldap_version' ), $f3->get( 'ldap_base_dn' )
   );
   $ldap->search( sanitize( $f3->get( 'GET.login' ), LDAP ) );
   $login_valid = 0 < $ldap->result['count'] ? true : false;

   echo( Template::instance()->render( 'templates/token.html' ) );

   // Don't create a token if login is invalid.
   if( !$login_valid ) {
      return;
   }

   // Generate the token.
   $crypto = true;
   $token = str_replace( '+', '', substr( base64_encode(
      openssl_random_pseudo_bytes( mt_rand( 20, 50 ), $crypto )
   ), 0, -2 ) );

   // Store the token.
   $db = new DB\SQL(
      $f3->get( 'db_config' ), $f3->get( 'db_user' ), $f3->get( 'db_pass' )
   );
   $tokens = new DB\SQL\Mapper( $db, 'tokens' );
   $tokens->login = $f3->get( 'GET.login' );
   $tokens->token_hash = modaldapper_hash( $token );
   $tokens->save();
   
   // Send the token.
   $notify = require( 'include/notify.php' );
   $notify->via( $f3->get( 'GET.retrieve' ) );
   $notify->send(
      array(
         'email' => $ldap->result[0]['mail'][0],
      ),
      $token
   );

   modaldapper_email_admin(
      'generated', $f3->get( 'GET.login' ), $f3->get( 'SERVER.REMOTE_ADDR' )
   );
} );

$f3->route( 'GET / [ajax]', function() {
   echo( Template::instance()->render( 'templates/login.html' ) );
} );

$f3->run();

