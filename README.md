# Tistory-api-php
Tistory php api ( http://www.tistory.com/guide/api/oauth )

## 사용법
1. access_token이 없는 경우
<pre>
require_once '/path/Tistory_api.php';

$tistory_api = new Tistory_api();

$api_data = array();
$api_data['callback_url'] = '클라이언트에 등록한 콜백주소';
$api_data['client_id'] = '티스토리에서 발급받은 클라이언트 아이디';
$api_data['secret_key'] = '티스토리에서 발급받은 클라이언트 비밀키';
$tistory_api->set_api($api_data);

$url = $tistory_api->authorize();
// 위의 $url로 접속(a 링크)하여, 어플리케이션을 승인하면, access_token을 발급받을 수 있습니다.

// 승인이 완료되어 access_token이 발급되면, 콜백주소로 티스토리에서 get 메소드로 code와 state를 리턴해줍니다.
$code = $_GET['code'];
$access_token = $tistory_api->access_token($code);

// access_token은 계속 사용할것이기때문에, 세션등으로 저장해줍니다.
if (isset($access_token['access_token'])) {
  $_SESSION['access_token'] = $access_token['access_token'];
  $tistory_api->set_api(array('access_token'=>$access_token['access_token']));
}

// access_token이 발급되었기 때문에 그 토큰을 사용하여, 티스토리의 api를 사용합니다.
print_r($tistory_api->blog_info());
</pre>

2. access_token이 존재하는 경우.
<pre>
require_once '/path/Tistory_api.php';

$tistory_api = new Tistory_api();
$tistory_api->set_api(array('access_token'=>$_SESSION['access_token']));
print_r($tistory_api->blog_info());
</pre>

## 개발자 Tistory
http://pureani.tistory.com/
