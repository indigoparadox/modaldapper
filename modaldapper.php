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
         array( 'uid', 'mail' )
      );
      $ldap_entries = ldap_get_entries( $service_bind, $search_result );
      $login_valid = 0 < $ldap_entries['count'] ? true : false;

      switch( $_GET['retrieve'] ) {
         case 'email':
            $retrieve = 'E-Mail';
            break;
      }

      if( $login_valid ) {
         
         $mail_headers = sprintf(
            "From: %s\r\n",
            $modaldapper_config['site']['email']
         );

         // TODO: Generate and store the verification token.
         $token = '';

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
                  $modaldapper_configconfig['site']['name']
               ),
               wordwrap( $admin_message, 70, "\r\n" ),
               $mail_headers
            );
         }
      }
         
      // Always display the token entry form to psyche out guessers.
      ?><p>
         A verification token has been transmitted to you through
         <?php echo( $retrieve ) ?>. Please enter that token in the field below.
      </p>
      <div>
         <label for="modaldapper-token">Token:</label>
         <input type="text" id="modaldapper-token" />
         <input type="hidden" id="modaldapper-action" value="token" />
      </div><?php
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
            <option value="email">E-Mail</option>
         </select>
         <input type="hidden" id="modaldapper-action" value="login" />
      </div><?php
      break;
}
?></form>
