<?php

$f3 = require( 'include/f3/base.php' );

$f3->set( 'DEBUG', 3 );

require_once( 'config.inc.php' );

//$f3->route( 'GET /token [ajax]', function() {
$f3->route( 'GET /token', function() {

   global $modaldapper_config;

   $ldap = require( 'include/reset.php' );

   $ldap->connect(
      $modaldapper_config['ldap']['service_user'],
      $modaldapper_config['ldap']['service_pass'],
      $modaldapper_config['ldap']['host'],
      $modaldapper_config['ldap']['port'],
      $modaldapper_config['ldap']['version']
   );

   $ldap->search(
      // XXX
      '',
      $modaldapper_config['ldap']['cn_field'],
      $modaldapper_config['ldap']['base_dn']
   );
/*
   // Determine if the login is valid.

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
*/
} );

$f3->route( 'GET /', function() {
   $template = new Template;
   echo( $template->render( 'templates/login.html' ) );
} );

$f3->run();

