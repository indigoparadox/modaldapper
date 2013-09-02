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

   case 'tokenform':
      
      // Always display the token entry form to psyche out guessers.
      break;

   default:
}
?></form>
