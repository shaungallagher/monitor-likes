<?

include_once 'config.php';
include_once '/path/to/facebook-php-sdk/src/facebook.php';

$facebook = new Facebook(array(
  'appId'  => $config['appId'],
  'secret' => $config['secret']
));


// Even if we have only one user, we still need to store their info
// in the database so we can persist their access_token info, which
// can't be hard-coded because it frequently changes.  One benefit
// of storing the user information in the database is that we can
// easily have multiple users, although watch out, because Facebook
// rate-limits its API, and an API call is required for each user.

$stmt = mysqli_prepare($link, "SELECT user_id, access_token, user_email FROM fb_likenotifications");
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $user_id, $access_token, $user_email);
while (mysqli_stmt_fetch($stmt)) {

   if ($access_token == 'expired') {
      continue;
   }

   $params = array('access_token' => $access_token);

   try {
      $result = $facebook->api('/'.$user_id.'/feed', 'GET', $params);
   } catch (Exception $e) {

      // If the access token has expired, alert the user by email.
      // The user will not get any more emails until they update their
      // access token.

      $stmt2 = mysqli_prepare($link, "UPDATE fb_likenotifications SET access_token = 'expired' WHERE user_id = ?");
      mysqli_stmt_bind_param($stmt2, "s", $user_id);
      mysqli_stmt_execute($stmt2);

      mail($user_email, 'Login required', '<p>Your access token has expired.</p><p>Please update it here:</p><p><a href="'.$url.'">'.$url.'</a></p>', $headers);
      continue;

   }

   $new = array();

   if (count($result[data]) > 0) {

      foreach($result[data] as $key => $value) {
         $post_id = $value[id];
         $message = $value[message];
         if ($value[from][id] == $user_id && count($value[likes][data]) > 0) {
            foreach($value[likes][data] as $key2 => $value2) {
               $friend_id = $value2[id];
               $friend_name = $value2[name];

               // We only want to alert the user to Likes that they have not
               // yet been alerted about, so we store that information in the database.

               $stmt2 = mysqli_prepare($link, "SELECT * FROM fb_likewho WHERE user_id = ? AND who_likes_id = ? AND post_id = ?");
               mysqli_stmt_bind_param($stmt2, "sss", $user_id, $friend_id, $post_id);
               mysqli_stmt_execute($stmt2);
               mysqli_stmt_store_result($stmt2);

               if (mysqli_stmt_num_rows($stmt) == 0) {
                  $now_time = time();
                  $stmt3 = mysqli_prepare($link, "INSERT INTO fb_likewho VALUES (?, ?, ?, ?, ?)");
                  mysqli_stmt_bind_param($stmt3, "ssssi", $user_id, $friend_id, $friend_name, $post_id, $now_time);
                  mysqli_stmt_execute($stmt2);
                  $new[$post_id][] = array($message, $friend_id, $friend_name);
                  $unique_friend[$friend_id] = $friend_name;
                  $non_unique_friend++;
               }
            }
         }
      }
   }

   // Construct the email

   $body = '<div style="background-color:#EEE; padding:8px">';

   if (count($new) > 0) {
      $statuses = (count($new) > 1) ? 'statuses' : 'status';
      foreach($new as $key => $value) {
         $url_part = explode("_", $key);
         $body .= '<div style="background-color:#FFF; font-size:13px; font-family:arial; margin-top:6px; border:1px solid #CCC; padding:8px"><p>';
         if (count($value) > 0) {
            foreach ($value as $key2 => $value2) {
               if ($key2 == 0) {
                  $body .= $value2[0]."</p><p>New likes: ";
               }
               $body .= '<a href="http://www.facebook.com/profile.php?id='.$value2[1];
               $body .= '" style="text-decoration:none; color:#03B; font-weight:bold">'.$value2[2].'</a> &nbsp; ';
            }
         }

         // Because access tokens expire unless periodically refreshed,
         // we employ a little trick here.  We set up our "View post on Facebook"
         // link as a redirect that passes the user through a re-authentication
         // page, extending the life of the access token.

         $body .= '</p><p style="border-top:1px solid #CCC; padding-top:8px;"><a href="'.$url.'/index.php?forward=';
         $body .= urlencode('http://www.facebook.com/'.$url_part[0].'/posts/'.$url_part[1]);
         $body .= '" style="text-decoration:none; color:#03B; font-weight:bold">View post on Facebook</a></p></div>';
      }
   }

   $body .= '</div>';

   // Construct the subject line

   if (count($unique_friend) == 1) {
      $subject = array_pop($unique_friend).' likes your status';
   }
   else if (count($unique_friend) > 1 && count($new) == 1) {
      if (count($unique_friend) > 2) {
         $plus = ' (and '.(count($unique_friend)-2).' more)';
      }
      $subject = array_pop($unique_friend).' and '.array_pop($unique_friend).$plus.' like your status';
   }
   else if (count($unique_friend) > 1 && count($new) == 2) {
      if (count($unique_friend) > 2) {
         $plus = ' (and '.(count($unique_friend)-2).' more)';
      }
      $subject = array_pop($unique_friend).' and '.array_pop($unique_friend).$plus.' like your statuses';
   }

   // Send the alert email

   if (count($new) > 0) {
      mail($user_email, $subject.' ('.date("g:i a").')', $body, "From: 'Like Notifications' <".$email.">\nContent-type: text/html");
   }

}



