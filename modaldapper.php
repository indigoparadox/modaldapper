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
      //$password_clean = sanitize( $password, LDAP );
      $password_clean = $password;
   } else {
      $user_clean = sanitize(
         $modaldapper_config['ldap']['service_user'], LDAP
      );
      /* $password_clean = sanitize(
         $modaldapper_config['ldap']['service_pass'], LDAP
      ); */
      $password_clean = $modaldapper_config['ldap']['service_pass'];
   }

   // Perform the connection.
   $ldap = ldap_connect(
      $modaldapper_config['ldap']['host'],
      $modaldapper_config['ldap']['port']
   );

   ldap_set_option( $ldap, LDAP_OPT_PROTOCOL_VERSION, 3 );

   if( !empty( $user_clean ) && !empty( $password_clean ) ) {
      $bind_result = ldap_bind( $ldap, $user_clean, $password_clean );
   } else {
      // Try to bind anonymously if no credentials available.
      $bind_result = ldap_bind( $ldap );
   }

   return $bind_result ? $ldap : null;
}

// TODO: Verify HTTPS.

?><form action="modaldapper.php" method="post"><?php
switch( isset( $_GET['action'] ) ? $_GET['action'] : '' ) {

   case 'token':
      // TODO: Display the new password entry form.
      break;
      
   case 'login':

      // TODO: Determine if the login is valid.
      $service_bind = modaldapper_ldap_bind();
      $search_query = sprintf( '(uid=%s)', sanitize( $_GET['login'], LDAP ) );
      $search_result = ldap_search(
         $service_bind,
         $modaldapper_config['ldap']['basedn'],
         $search_query,
         array( 'uid' )
      );
      $ldap_entries = ldap_get_entries( $service_bind, $search_result );
      $login_valid = 0 < $ldap_entries['count'] ? true : false;

      if( $login_valid ) {
         // TODO: Send the token e-mail/SMS/IM/whatever.
         
         // Display the token entry form.
         ?><div>
            <label for="modaldapper-token">Key:</label>
            <input type="text" id="modaldapper-token" />
         </div>
         <div>
            <input type="hidden" id="modaldapper-action" value="token" />
            <input type="button" id="modaldapper-submit" value="Submit" />
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
         <label for="login">Login Name:</label>
         <input type="text" id="modaldapper-login" />
      </div>
      <div>
         <label for="modaldapper-retrieve">Retrieval Method:</label>
         <select id="modaldapper-retrieve">
            <option value="mail">E-Mail</option>
         </select>
      </div>
      <div>
         <input type="hidden" id="modaldapper-action" value="login" />
         <input type="button" id="modaldapper-submit" value="Submit" />
      </div><?php
      break;
}
?></form>
