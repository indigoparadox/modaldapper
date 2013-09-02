<?php

require_once( 'config.inc.php' );
require_once( 'sanitize.inc.php' );

function modaldapper_ldap_set_password( $ldap, $login=null, $password ) {

}

function modaldapper_ldap_bind( $user=null, $password=null ) {

}

function modaldapper_database_compare_token_hash( $connection, $hash ) {
   global $modaldapper_config;
   switch( $modaldapper_config['database']['type'] ) {
      case 'mysqli':
         // Fetch rows that match the given login and compare them to input
         // hash.
         $result = mysqli_query( $connection, 'SELECT * FROM `tokens`' );
         if( $result ) {
            while( $row = mysqli_fetch_assoc( $result ) ) {
               if( $row['token_hash'] == $hash ) {
                  // TODO: Delete all existing tokens for this user.
                  return $row['login'];
               }
            }
         }

         // Fail by default.
         return null;
   }
}

function modaldapper_database_store_token_hash( $connection, $login, $hash ) {
   global $modaldapper_config;
   switch( $modaldapper_config['database']['type'] ) {
      case 'mysqli':
         $values = sprintf(
            'VALUES (\'%s\', \'%s\')',
            mysqli_real_escape_string( $connection, $login ),
            mysqli_real_escape_string( $connection, $hash )
         );
         mysqli_query(
            $connection,
            'INSERT INTO `tokens` '.
               '(`login`, `token_hash`) '.
               $values
         );
         break;
   }
}

function modaldapper_database_connect() {
   global $modaldapper_config;
   switch( $modaldapper_config['database']['type'] ) {
      case 'mysqli':
         // Connect to the database.
         $connection = new mysqli(
            $modaldapper_config['database']['host'],
            $modaldapper_config['database']['user'],
            $modaldapper_config['database']['pass'],
            $modaldapper_config['database']['db']
         );

         // See if the tokens table exists.
         $result = mysqli_query( $connection, 'SELECT * FROM `tokens`' );
         if( empty( $result ) ) {
            // Create the tokens table.
            $result = mysqli_query(
               $connection,
               'CREATE TABLE tokens ( '.
                  '`id` int(11) AUTO_INCREMENT, '.
                  '`login` varchar(255) NOT NULL, '.
                  '`token_hash` varchar(255) NOT NULL, '.
                  '`created` timestamp DEFAULT CURRENT_TIMESTAMP, '.
                  'PRIMARY KEY (ID)'.
               ')'
            );
         }

         return $connection;
   }
}

function modaldapper_generate_token() {
   $length = mt_rand( 20, 50 );
   $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

   // Try to use a stronger method if available.
   if( function_exists( 'openssl_random_pseudo_bytes' ) ) {
      $crypto = true;
      return substr(
         base64_encode( openssl_random_pseudo_bytes( $length, $crypto ) ), 0, -2
      );
   } else {
      $str = '';
      while( $length-- ) {
         $str .= $charset[mt_rand( 0, strlen( $charset ) - 1 )];
      }
      return $str;
   }
}

// TODO: Verify HTTPS.

?><form action="modaldapper.php" method="post"><?php
switch( isset( $_GET['action'] ) ? $_GET['action'] : '' ) {

   case 'token':
      // Test the token.
      $database = modaldapper_database_connect();
      $token_login = modaldapper_database_compare_token_hash(
         $database,
         crypt(
            $_GET['token'],
            '$5$rounds=5000$'.$modaldapper_config['database']['salt'].'$'
         )
      );

      if( !empty( $token_login ) ) {

         // TODO: Validate the new account password.

         // Set the new account password.
         $ldap = modaldapper_ldap_bind();
         $success = modaldapper_ldap_set_password(
            $ldap, $token_login, $_GET['password']
         );

         // TODO: E-Mail the site administrator.

         if( false === $success ) {
            ?><p>
               There was a problem setting your password. Please contact the
               site administrator.
            </p><?php
         } else {
            ?><p>Your password has been reset successfully.</p><?php
         }

      } else {
         // Display an error message and hidden redirect to token form.
         ?><p>
            An invalid token was specified. Click Continue below to return to
            the token entry page.
         </p>
         <div>
            <input type="hidden" id="modaldapper-action" value="tokenform" />
         </div><?php
      }
      break;
      
   case 'login':

      // Determine if the login is valid.
      $service_bind = modaldapper_ldap_bind();
      $search_query = sprintf( '(uid=%s)', sanitize( $_GET['login'], LDAP ) );
      $search_result = ldap_search(
         $service_bind,
         $modaldapper_config['ldap']['basedn'],
         $search_query,
         array( 'uid', 'mail' )
      );
      $ldap_entries = ldap_get_entries( $service_bind, $search_result );
      $login_valid = 0 < $ldap_entries['count'] ? true : false;

      if( $login_valid ) {
         
         $mail_headers = sprintf(
            "From: %s\r\n",
            $modaldapper_config['site']['email']
         );

         // Generate and store the verification token.
         $database = modaldapper_database_connect();
         $token = modaldapper_generate_token();
         modaldapper_database_store_token_hash(
            $database,
            $_GET['login'],
            crypt(
               $token,
               '$5$rounds=5000$'.$modaldapper_config['database']['salt'].'$'
            )
         );

         // Send the verification token.
         switch( $_GET['retrieve'] ) {
            case 'email':
               $token_message = sprintf(
                  'A password reset request has been generated at %s for an '.
                  "account with this e-mail address attached to it. \r\n\r\n".
                  'If you did not generate this request, please ignore this '.
                  'message. Otherwise, please enter the following token into '.
                  "the token field on the password reset page: \r\n\r\n%s",
                  $modaldapper_config['site']['name'],
                  $token
               );
               mail(
                  $ldap_entries[0]['mail'][0],
                  sprintf(
                     '%s Password Reset Verification',
                     $modaldapper_config['site']['name']
                  ),
                  wordwrap( $token_message, 70, "\r\n" ),
                  $mail_headers
               );
               break;
         }

         // E-mail the administrator.
         if( !empty( $config['admin']['email'] ) ) {
            $admin_message = sprintf(
               'A new password reset request has been generated for the user '.
               '%s by a client at the IP %s.',
               sanitize( $_GET['login'], PARANOID ),
               sanitize( $_SERVER['REMOTE_ADDR'], PARANOID )
            );
            mail(
               $config['admin']['email'],
               sprintf(
                  '%s New Password Reset Request',
                  $modaldapper_config['site']['name']
               ),
               wordwrap( $admin_message, 70, "\r\n" ),
               $mail_headers
            );
         }
      }

      // Don't break or anything, here. Proceed directly to the token form.

   case 'tokenform':
      
      // Always display the token entry form to psyche out guessers.
      ?><p>
         A verification token has been transmitted to you through the alternate
         channel you have selected. Please enter that token in the field below
         along with a new password.
      </p>
      <div>
         <label for="modaldapper-token">Token:</label>
         <input type="text" id="modaldapper-token" />
         <input type="hidden" id="modaldapper-action" value="token" />
         <label for="modaldapper-password">Password:</label>
         <input type="password" id="modaldapper-password" />
      </div><?php
      break;

   default:
}
?></form>
