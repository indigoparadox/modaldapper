<?php

class LDAPReset {
   protected $connection;
   protected $fieldmap =
      array( 'cn' => 'cn', 'userPassword' => 'userPassword' );
   public $result;

   // Reset the password for user specified by $user/$basedn as stored in their
   // $passfield.
   // TODO: Reset password clocks/retry counts/etc.
   public function password( $user, $new_password, $fieldmap=array() ) {
      // Salt and hash the password.
      mt_srand( (double)microtime() * 1000000 );
      $salt = pack( 'CCCC', mt_rand(), mt_rand(), mt_rand(), mt_rand() );
      $hash = '{SSHA}'.
         base64_encode( pack( 'H*', sha1( $new_password.$salt) ).$salt );

      // Update the LDAP directory.
      $entry = array( $this->fieldmap['userPassword'] => $hash );
      $user_dn = sprintf(
         '%s=%s,%s', $this->fieldmap['cn'], $user, $this->basedn
      );
      return ldap_modify( $this->connection, $user_dn, $entry );
   }

   // Search for the user specified by $user.
   public function search( $user ) {
      if( empty( $this->fieldmap ) ) {
         throw new Exception( 'No fieldmap specified.' );
         return null;
      }

      $query = sprintf( '(%s=%s)', $this->fieldmap['cn'], $user );
      $result = ldap_search(
         $this->connection,
         $this->basedn,
         $query,
         array( $this->fieldmap['cn'], $this->fieldmap['mail'] )
      );
      return $this->result = ldap_get_entries( $this->connection, $result );
   }

   // Connect to the LDAP server.
   public function connect( $user, $password, $host, $port, $ver, $basedn ) {
      $this->basedn = $basedn;
      $this->connection = ldap_connect( $host, $port );
      ldap_set_option( $this->connection, LDAP_OPT_PROTOCOL_VERSION, $ver );
      if( !empty( $user ) && !empty( $password ) ) {
         return ldap_bind( $this->connection, $user, $password );
      } else {
         // Try to bind anonymously if no credentials available.
         return ldap_bind( $this->connection );
      }
   }

   // Set the field map. Should be done before anything.
   public function fields( $fieldmap ) {
      $this->fieldmap = $fieldmap;
   }
}

return new LDAPReset();

