<? session_start();

if ($_GET[forward]) {
    $_SESSION[forward] = $_GET[forward];
}

include_once 'config.php';
include_once '/path/to/facebook-php-sdk/src/facebook.php';




// YOU SHOULD NOT NEED TO EDIT ANYTHING BELOW THIS LINE

$facebook = new Facebook(array(
    'appId'  => $config['appId'],
    'secret' => $config['secret']
));

// See if there is a user from a cookie
$user = $facebook->getUser();

if ($user) {
    try {
        // Proceed knowing you have a logged in user who's authenticated.
        $user_profile = $facebook->api('/me');
    } catch (FacebookApiException $e) {
        echo '<pre>'.htmlspecialchars(print_r($e, true)).'</pre>';
        $user = null;
    }
}

if ($user) {
    $facebook->setExtendedAccessToken();
    $access_token = $facebook->getAccessToken();

    $user_id = $user_profile[id];

    $stmt = mysqli_prepare($link, "UPDATE fb_likenotifications SET access_token = ? WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "ss", $access_token, $user_id);
    mysqli_stmt_execute($stmt);

    if ($_SESSION[forward]) {
        header("Location: ".$_SESSION[forward]);
        exit;
    }
}

?><!DOCTYPE html>
<html xmlns:fb="http://www.facebook.com/2008/fbml">
    <body>
        <? if ($user) { ?>
            Your refreshed long-lived access token:
            <pre>
                <?= $access_token; ?>
            </pre>
        <? } else { ?>
            <fb:login-button></fb:login-button>
        <? } ?>
        <div id="fb-root"></div>
        <script>
            window.fbAsyncInit = function() {
                FB.init({
                    appId: '<?= $facebook->getAppID() ?>',
                    cookie: true,
                    xfbml: true,
                    oauth: true
                });
                FB.Event.subscribe('auth.login', function(response) {
                    window.location.reload();
                });
                FB.Event.subscribe('auth.logout', function(response) {
                    window.location.reload();
                });
            };
            (function() {
                var e = document.createElement('script'); e.async = true;
                e.src = document.location.protocol +
                    '//connect.facebook.net/en_US/all.js';
                document.getElementById('fb-root').appendChild(e);
            }());
        </script>
    </body>
</html>