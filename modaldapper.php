<?php

require_once( 'config.php' );

// TODO: Verify HTTPS.

switch( isset( $_GET['action'] ) ? $_GET['action'] : '' ) {

   case 'token':
      // TODO: Display the new password entry form.
      break;
      
   case 'login':

      // TODO: Determine if the login is valid.
      $ldap = ldap_connect(
         $modaldapper_config['ldap']['host'],
         $modaldapper_config['ldap']['port']
      );
      $login_valid = $ldap ? true : false;

      if( $login_valid ) {
         // TODO: Send the token e-mail/SMS/IM/whatever.
         
         // Display the token entry form.
         ?><form>
            <div>
               <label for="modaldapper-token">Key:</label>
               <input type="password" id="modaldapper-token" />
            </div>
            <div>
               <input 
                  id="modaldapper-token-submit" type="button" value="Submit"
               />
               <input id="modaldapper-close" type="button" value="Close" />
            </div>
         </form><?php
      } else {
         ?><form>
         <div class="modaldapper-error">Invalid.</div>
         <input id="modaldapper-close" type="button" value="Close" />
         <?php
      }
      break;

   default:
      // Show login entry form.
      ?><form>
         <div>
            <label for="modaldapper-login">Login name:</label>
            <input type="text" id="modaldapper-login" />
         </div>
         <div>
            <input id="modaldapper-login-submit" type="button" value="Submit" />
         </div>
      </form><?php
      break;
}

