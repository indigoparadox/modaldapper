<?php

class LDAPReset {
   protected $connection;
   public $result;

   // Reset the password for user specified by $user/$basedn as stored in their
   // $passfield.
   // TODO: Reset password clocks/retry counts/etc.
   // TODO: Allow specifying mail field.
   // TODO: Specify field names in an array.
   public function password( 
      $user, $new_password, $passfield, $cnfield, $basedn
   ) {
      // Salt and hash the password.
      mt_srand( (double)microtime() * 1000000 );
      $salt = pack( 'CCCC', mt_rand(), mt_rand(), mt_rand(), mt_rand() );
      $hash = '{SSHA}'.
         base64_encode( pack( 'H*', sha1( $new_password.$salt) ).$salt );

      // Update the LDAP directory.
      $entry = array(
         $passfield => $hash,
      );
      $user_dn = sprintf( '%s=%s,%s', $cnfield, $user, $basedn );
      return ldap_modify( $this->connection, $user_dn, $entry );
   }

   // Search for the user specified by $user/$basedn.
   public function search( $user, $cnfield, $basedn ) {
      $query = sprintf( '(%s=%s)', $cnfield, $user );
      $result = ldap_search(
         $this->connection,
         $basedn,
         $query,
         array( $cnfield, 'mail' )
      );
      return $this->result = ldap_get_entries( $this->connection, $result );
   }

   // Connect to the LDAP server.
   public function connect( $user, $password, $host, $port, $version ) {
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

