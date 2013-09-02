<?php

require_once( 'sanitize.php' );

class LDAPReset {
   protected $connection;

/*
   public function password( $user, $new_password ) {
      global $modaper_config;
      
      // Salt and hash the password.
      mt_srand( (double)microtime() * 1000000 );
      $salt = pack( 'CCCC', mt_rand(), mt_rand(), mt_rand(), mt_rand() );
      $hash = '{SSHA}'.base64_encode( pack( 'H*', sha1( $new_password.$salt) ).
         $salt );

      // Update the LDAP directory.
      $entry = array(
         $modaper_config['ldap']['password_field'] => $hash,
      );
      $user_dn = sprintf(
         '%s=%s,%s',
         $modaper_config['ldap']['cn_field'],
         sanitize( $user, LDAP ),
         $modaper_config['ldap']['basedn']
      );
      return ldap_modify( $ldap, $user_dn, $entry );
   }
*/

   public function search( $user, $cnfield, $basedn ) {
      $search_query = sprintf(
         '(%s=%s)',
         sanitize( $cnfield, LDAP ),
         sanitize( $user, LDAP )
      );
      $search_result = ldap_search(
         $this->connection,
         $basedn,
         $search_query,
         array( $cnfield, 'mail' )
      );
      $ldap_entries = ldap_get_entries( $this->connection, $search_result );
      $login_valid = 0 < $ldap_entries['count'] ? true : false;
   }

   public function connect( $user, $password, $host, $port, $version ) {

      if( !empty( $user ) ) {
         $user = sanitize( $user, LDAP );
         // It seems _bind may already sanitize its inputs. Sanitizing here
         // causes a password with special characters not to work.
         //$password = sanitize( $password, LDAP );
      }

      // Perform the connection.
      $this->connection = ldap_connect( $host, $port );

      ldap_set_option( $this->connection, LDAP_OPT_PROTOCOL_VERSION, $version );

      if( !empty( $user ) && !empty( $password ) ) {
         return ldap_bind( $this->connection, $user, $password );
      } else {
         // Try to bind anonymously if no credentials available.
         return ldap_bind( $this->connection );
      }
   }
}

return new LDAPReset();

