<?php

$f3 = require( 'include/f3/base.php' );
$f3->config( 'config/modaldapper.cfg' );

require_once( 'include/sanitize.php' );

//$f3->route( 'GET /token [ajax]', function() {
$f3->route( 'GET /token', function() {
   global $f3;

   // Determine if the login is valid.
   $ldap = require( 'include/reset.php' );
   $ldap->connect(
      $f3->get( 'ldap_service_user' ), $f3->get( 'ldap_service_pass' ),
      $f3->get( 'ldap_host' ), $f3->get( 'ldap_port' ),
      $f3->get( 'ldap_version' )
   );
   $result = $ldap->search(
      sanitize( $f3->get( 'GET.login' ), LDAP ),
      $f3->get( 'ldap_cn_field' ),
      $f3->get( 'ldap_base_dn' )
   );
   $login_valid = 0 < $result['count'] ? true : false;
   if( $login_valid ) {
      // Connect to the database.
      $database = new DB\SQL(
         $f3->get( 'db_config' ), $f3->get( 'db_user' ), $f3->get( 'db_pass' )
      );
      
      // Generate and store the token.
      $crypto = true;
      $token = substr( base64_encode(
         openssl_random_pseudo_bytes( mt_rand( 20, 50 ), $crypto )
      ), 0, -2 );
      $database->exec(
         'INSERT INTO `tokens` (`login`, `token_hash`) VALUES (\'%s\', \'%s\')',
         $f3->get( 'GET.login' ),
         // Hash the token for storage.
         crypt(
            $token,
            '$5$rounds=5000$'.$f3->get( 'database_salt' ).'$'
         )
      );

      // E-mail the administrator.
      if( $f3->get( 'admin_email' ) ) {
         $f3->set( 'req_op', 'generated' );
         $f3->set( 'req_user', $f3->get( 'GET.login' ) );
         $f3->set( 'req_ip', $f3->get( 'SERVER.REMOTE_ADDR' ) );
         mail(
            $f3->get( 'admin_email' ),
            sprintf( '%s New Password Reset Request', $f3->get( 'site_name' ) ),
            wordwrap(
               Template::instance()->render( 'mail/admin.txt' ), 70, "\r\n"
            ),
            sprintf( "From: %s\r\n", $f3->get( 'site_email' ) )
         );
      }
   }
         
/*
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

   }
*/
   echo( Template::instance()->render( 'templates/token.html' ) );
} );

$f3->route( 'GET /', function() {
   echo( Template::instance()->render( 'templates/login.html' ) );
} );

$f3->run();

