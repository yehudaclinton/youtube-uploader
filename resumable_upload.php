<?php

/**
 * Library Requirements
 *
 * 1. Install composer (https://getcomposer.org)
 * 2. On the command line, change to this directory (api-samples/php)
 * 3. Require the google/apiclient library
 *    $ composer require google/apiclient:~2.0
 */
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
  throw new \Exception('please run "composer require google/apiclient:~2.0" in "' . __DIR__ .'"');
}

require_once __DIR__ . '/vendor/autoload.php';
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
/*
 * You can acquire an OAuth 2.0 client ID and client secret from the
 * {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
 * For more information about using OAuth 2.0 to access Google APIs, please see:
 * <https://developers.google.com/youtube/v3/guides/authentication>
 * Please ensure that you have enabled the YouTube Data API for your project.
 */
$OAUTH2_CLIENT_ID = '56000165blablak5orm.apps.googleusercontent.com';
$OAUTH2_CLIENT_SECRET = 'GOCSPX-9ktEblablamrNoO';

$client = new Google_Client();
$client->setClientId($OAUTH2_CLIENT_ID);
$client->setClientSecret($OAUTH2_CLIENT_SECRET);
$client->setScopes('https://www.googleapis.com/auth/youtube');
$redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],
    FILTER_SANITIZE_URL);
$client->setRedirectUri($redirect);

// Define an object that will be used to make all API requests.
$youtube = new Google_Service_YouTube($client);

// Check if an auth token exists for the required scopes
$tokenSessionKey = 'token-' . $client->prepareScopes();


if (isset($_GET['code'])) { 
  $code=$_GET['code'];
  $scope=$_GET['scope'];
  print $code;
  print "<form method='post' enctype='multipart/form-data'>
    <input type='hidden' name='code' value='$code' />
    <input type='hidden' name='scope' value='$scope' />
    video location: <input type='text' name='video'><br>
    <input type='submit'>
  </form>";
//thumbnail: <input type='text' name='thumbnail'><br>
}

if (isset($_POST['video'])) {
//file_put_contents("php://stdout", 'POST worked')
echo 'POST worked';

  $client->authenticate($_POST['code']);
  $_SESSION[$tokenSessionKey] = $client->getAccessToken();
  //header('Location: ' . $redirect);
} else { 

echo 'no POST';

 // $client->authenticate($_GET['code']);
  $_SESSION[$tokenSessionKey] = $client->getAccessToken();
}

if (isset($_SESSION[$tokenSessionKey])) {
  $client->setAccessToken($_SESSION[$tokenSessionKey]);
}

// Check to ensure that the access token was successfully acquired.
if ($client->getAccessToken()) {
  $htmlBody = '';
  try{

    $videoPath = $_POST['video'];//$_FILES['video'];

    $snippet = new Google_Service_YouTube_VideoSnippet();
    $snippet->setTitle("Test 13");
    $snippet->setDescription("Test descr");
    $snippet->setTags(array("tag1", "tag2"));

    // https://developers.google.com/youtube/v3/docs/videoCategories/list
    $snippet->setCategoryId("22");

    $status = new Google_Service_YouTube_VideoStatus();
    $status->privacyStatus = "public";

        // Create a YouTube video with snippet and status
        $video = new Google_Service_YouTube_Video();
        $video->setSnippet($snippet);
        $video->setStatus($status);
 
        // Size of each chunk of data in bytes. Setting it higher leads faster upload (less chunks,
        // for reliable connections). Setting it lower leads better recovery (fine-grained chunks)
        $chunkSizeBytes = 1 * 1024 * 1024;
 
        // Setting the defer flag to true tells the client to return a request which can be called
        // with ->execute(); instead of making the API call immediately.
        $client->setDefer(true);
 
        // Create a request for the API's videos.insert method to create and upload the video.
        $insertRequest = $youtube->videos->insert("status,snippet", $video);
 
        // Create a MediaFileUpload object for resumable uploads.
        $media = new Google_Http_MediaFileUpload(
            $client,
            $insertRequest,
            'video/*',
            null,
            true,
            $chunkSizeBytes
        );
        $media->setFileSize(filesize($videoPath));
 
 
        // Read the media file and upload it chunk by chunk.
        $status = false;
        $handle = fopen($videoPath, "rb");
        while (!$status && !feof($handle)) {
            $chunk = fread($handle, $chunkSizeBytes);
            $status = $media->nextChunk($chunk);
        }
 
        fclose($handle);
 
        /**
         * Video has successfully been upload, now lets perform some cleanup functions for this video
         */
        if ($status->status['uploadStatus'] == 'uploaded') {
            // Actions to perform for a successful upload
            // $uploaded_video_id = $status['id'];
        }
 
        // If you want to make other calls after the file upload, set setDefer back to false
        $client->setDefer(true);
        
} catch(Google_Service_Exception $e) {
    print "Caught Google service Exception ".$e->getCode(). " message is ".$e->getMessage();
    print "Stack trace is ".$e->getTraceAsString();
}catch (Exception $e) {
    print "Caught Google service Exception ".$e->getCode(). " message is ".$e->getMessage();
    print "Stack trace is ".$e->getTraceAsString();
}
        
} else {
  // If the user hasn't authorized the app, initiate the OAuth flow
  $state = mt_rand();
  $client->setState($state);
  $_SESSION['state'] = $state;

  $authUrl = $client->createAuthUrl();
  $htmlBody = <<<END
  <h3>Authorization Required</h3>
  <p>You need to <a href="$authUrl">authorize access</a> before proceeding.<p>
END;
}
?>

<!doctype html>
<html>
<head>
<title>Video Uploaded</title>
</head>
<body>
  <?=$htmlBody?>
</body>
</html>
