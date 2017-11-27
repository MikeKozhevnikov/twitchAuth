<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$header = file_get_contents('./template/header.html', true);
$footer = file_get_contents('./template/footer.html', true);

require 'twitch.php';

$provider = new TwitchProvider([
    'clientId'                => '7wjd51pnh44udw328ns0odpwsbidfz',     // The client ID assigned when you created your application
    'clientSecret'            => 'ecjytqljrn19bgwzea14nchapvne28', // The client secret assigned when you created your application
    'redirectUri'             => 'http://twitch.webcodes.club',  // Your redirect URL you specified when you created your application
    'scopes'                  => ['user_read']                // The scopes you would like to request 
]);

// If we don't have an authorization code then get one
if (!isset($_GET['code'])) {

    // Fetch the authorization URL from the provider, and store state in session
    $authorizationUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();

    // Display link to start auth flow
    echo $header;
    echo '
        <div class="jumbotron">
          <h2 class="display-8">Аутентификация через Twitch API</h2>
          <p class="lead">Простая аутентификация пользователя через аккаунт Twitch.tv по <b>OAuth2</b>, используя <b>clientId</b>, <b>clientSecret</b> и <b>redirectUri</b>. После прохождения аутентификации будут показаны основные параметры вашего аккаунта Twitch.tv</p>
          <p><a class="btn btn-lg btn-success" href="'. $authorizationUrl.'" role="button">Sign in with Twitch</a></p>
        </div>';
    echo $footer;

    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['oauth2state'])) {

    if (isset($_SESSION['oauth2state'])) {
        unset($_SESSION['oauth2state']);
    }
    
    exit('Invalid state');

} else {

    try {

        // Get an access token using authorization code grant.
        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        // Using the access token, get user profile
        $resourceOwner = $provider->getResourceOwner($accessToken);
        $user = $resourceOwner->toArray();

        echo $header;
        echo '<div class="jumbotron">
          <h2 class="display-8">Привет, ' . htmlspecialchars($user['display_name']) . '</h2>
          <p class="lead">Вот информация о твоём аккаунте:</p>
        </div>

<table class="table table-inverse">
  <tbody>
    <tr>
      <th scope="row">Access Token</th>
      <td>' . htmlspecialchars($accessToken->getToken()) . '</td>
    </tr>
    <tr>
      <th scope="row">Refresh Token</th>
      <td>' . htmlspecialchars($accessToken->getRefreshToken()) . '</td>
    </tr>
    <tr>
      <th scope="row">Username</th>
      <td>' . htmlspecialchars($user['display_name']) . '</td>
    </tr>
    <tr>
      <th scope="row">Bio</th>
      <td>' . htmlspecialchars($user['bio']) . '</td>
    </tr>    
    <tr>
      <th scope="row">Image</th>
      <td><img src="' . htmlspecialchars($user['logo']) . '"></td>
    </tr>      
  </tbody>
</table>';


        // You can now create authenticated API requests through the provider.
        $request = $provider->getAuthenticatedRequest(
            'GET',
            'https://api.twitch.tv/helix/user',
            $accessToken
        );

echo '<br>';
echo '<div class="bs-callout bs-callout-info">
  <h4>JSON Request:</h4><p><pre><code>';
echo  var_export($request, TRUE);
echo '</code></pre></p>
</div>

<div class="bd-callout bd-callout-info">
<h4 id="dealing-with-specificity"></h4>
<p>';


echo '</div>';


echo $footer;

    } catch (Exception $e) {
        exit('Caught exception: '.$e->getMessage());
    }
    
}
