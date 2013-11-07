<?

// Your email address

$email = 'you@domain.com';

// You'll need to create a new Facebook app at https://developers.facebook.com/apps to get these:

$config['appId'] = 'get this from your Facebook app settings page';
$config['secret']  = 'get this from your Facebook app settings page';

// The URL of the directory where you'll be hosting the file

$url = 'http://www.yourdomain.com/likenotifications';

// Your database
$link = mysqli_connect("localhost", "username", "password", "database_name");

?>
