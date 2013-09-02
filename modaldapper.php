<?php

require_once( 'config.php' );
require_once( 'sanitize.inc.php' );

function modaldapper_ldap_bind( $user=null, $password=null ) {

   // Bind to LDAP with the given credentials or bind with the service account
   // if no credentials provided.

   global $modaldapper_config;

   // Figure out the credentials to use.
   if( $user && $password ) {
      $user_clean = sanitize( $user, LDAP );
      $password_clean = sanitize( $password, LDAP );
   } else {
      $user_clean = sanitize(
         $modaldapper_config['ldap']['service_user'], LDAP
      );
      $password_clean = sanitize(
         $modaldapper_config['ldap']['service_pass'], LDAP
      );
   }

   // Perform the connection.
   $ldap = ldap_connect(
      $modaldapper_config['ldap']['host'],
      $modaldapper_config['ldap']['port']
   );

   ldap_set_option( $ldap, LDAP_OPT_PROTOCOL_VERSION, 3 );

   if( !empty( $user_clean ) && !empty( $password_clean ) ) {
      return ldap_bind( $ldap, $user_clean, $password_clean );
   } else {
      // Try to bind anonymously if no credentials available.
      return ldap_bind( $ldap );
   }
}

// TODO: Verify HTTPS.

?><form action="modaldapper.php" method="post"><?php
switch( isset( $_POST['action'] ) ? $_POST['action'] : '' ) {

   case 'token':
      // TODO: Display the new password entry form.
      break;
      
   case 'login':

      // TODO: Determine if the login is valid.
      $service_bind = modaldapper_ldap_bind();
      print_r( $service_bind );
      $login_valid = $service_bind ? true : false;

      if( $login_valid ) {
         // TODO: Send the token e-mail/SMS/IM/whatever.
         
         // Display the token entry form.
         ?><div>
            <label for="modaldapper-token">Key:</label>
            <input type="password" id="modaldapper-token" />
         </div>
         <div>
            <input type="hidden" name="action" value="token" />
            <input type="submit" value="Submit" />
         </div><?php
      } else {
         ?>
         <div class="modaldapper-error">Invalid.</div>
         <?php
      }
      break;

   default:
      // Show login entry form.
      ?><p>
         Please enter your current login name and select a method to
         retrieve your password change token.
      </p>
      <div>
         <label for="login">Login name:</label>
         <input type="text" id="login" name="login" />
      </div>
      <div>
         <label for="retrieve">Retrieval Method:</label>
         <select id="retrieve" name="retrieve">
            <option value="mail">E-Mail</option>
         </select>
      </div>
      <div>
         <input type="hidden" name="action" value="login" />
         <input type="submit" value="Submit" />
      </div><?php
      break;
}

