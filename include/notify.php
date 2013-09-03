<?php

class LDAPNotify {
   protected $types_available = array( 'email' );
   protected $type = '';

   protected function send_email( $address, $token ) {
      global $f3;

      $f3->set( 'req_token', $token );

      mail(
         $address,
         sprintf( '%s New Password Reset Request', $f3->get( 'site_name' ) ),
         wordwrap(
            Template::instance()->render( 'mail/reset.txt' ), 70, "\r\n"
         ),
         sprintf( "From: %s\r\n", $f3->get( 'site_email' ) )
      );
   }

   public function send( $user, $token ) {
      if( empty( $this->type ) ) {
         return false;
      }

      // Call the via-specific method for the selected avenue.
      return call_user_func_array(
         array( $this, 'send_'.$this->type ),
         array( $user[$this->type], $token )
      );
   }
   
   public function via( $type ) {
      if( in_array( $type, $this->types_available ) ) {
         $this->type = $type;
      }
   }
}

return new LDAPNotify();

