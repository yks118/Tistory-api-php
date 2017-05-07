<?php
// defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class Tistory_api
 *
 * PHP version 7.1
 *
 * api info     http://www.tistory.com/guide/api/oauth
 *
 * @author      KwangSeon Yun   <middleyks@hanmail.net>
 * @copyright   KwangSeon Yun
 * @license     https://raw.githubusercontent.com/yks118/Tistory-api-php/master/LICENSE     MIT License
 * @link        https://github.com/yks118/Tistory-api-php
 */
class Tistory_api {
	private $callback_url = '';
	private $client_id = '';
	private $secret_key = '';
	private $api_url = 'https://www.tistory.com/';
	private $access_token = '';

	public function __construct () {}

	public function __destruct () {}

	/**
	 * _get
	 *
	 * @param   string      $url
	 * @param   array       $data
	 *
	 * @return  array       $response
	 */
	private function _get ($url,$data = array()) {
		$url = $this->api_url.$url;

		if ($this->access_token) {
			$data['access_token'] = $this->access_token;
			$data['output'] = 'json';
		}

		$url .= '?'.http_build_query($data);
		$response = json_decode(file_get_contents($url),true);
		return $response;
	}

	/**
	 * get_content_curl
	 *
	 * @param   string      $url
	 * @param   array       $parameters
	 *
	 * @return  array       $data
	 */
	protected function get_content_curl ($url,$parameters = array()) {
		$data = array();

		// set CURLOPT_USERAGENT
		if (!isset($parameters[CURLOPT_USERAGENT])) {
			if (isset($_SERVER['HTTP_USER_AGENT'])) {
				$parameters[CURLOPT_USERAGENT] = $_SERVER['HTTP_USER_AGENT'];
			} else {
				// default IE11
				$parameters[CURLOPT_USERAGENT] = 'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko';
			}
		}

		// check curl_init
		if (function_exists('curl_init')) {
			$ch = curl_init();

			// url 설정
			curl_setopt($ch,CURLOPT_URL,$url);

			foreach ($parameters as $key => $value) {
				curl_setopt($ch,$key,$value);
			}

			// https
			if (!isset($parameters[CURLOPT_SSL_VERIFYPEER])) {
				curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
			}
			if (!isset($parameters[CURLOPT_SSLVERSION])) {
				curl_setopt($ch,CURLOPT_SSLVERSION,0);
			}

			// no header
			if (!isset($parameters[CURLOPT_HEADER])) {
				curl_setopt($ch,CURLOPT_HEADER,0);
			}

			// POST / GET (default : GET)
			if (!isset($parameters[CURLOPT_POST]) && !isset($parameters[CURLOPT_CUSTOMREQUEST])) {
				curl_setopt($ch,CURLOPT_POST,0);
			}

			// response get php value
			if (!isset($parameters[CURLOPT_RETURNTRANSFER])) {
				curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			}

			// HTTP2
			if (!isset($parameters[CURLOPT_HTTP_VERSION])) {
				curl_setopt($ch,CURLOPT_HTTP_VERSION,3);
			}
			if (!isset($parameters[CURLINFO_HEADER_OUT])) {
				curl_setopt($ch,CURLINFO_HEADER_OUT,TRUE);
			}

			$data['html'] = json_decode(curl_exec($ch),true);
			$data['response'] = curl_getinfo($ch);

			curl_close($ch);
		}

		return $data;
	}

	/**
	 * _post
	 *
	 * curl post
	 *
	 * @param   string      $url
	 * @param   array       $data
	 *
	 * @return  array       $response
	 */
	private function _post ($url,$data = array()) {
		$parameters = array();
		$parameters[CURLOPT_POST] = 1;

		// set access_token
		if (isset($this->access_token)) {
			$data['access_token'] = $this->access_token;
			$data['output'] = 'json';
		}

		if (count($data)) {
			$parameters[CURLOPT_POSTFIELDS] = http_build_query($data);
		}

		$url = $this->api_url.$url;
		$response = $this->get_content_curl($url,$parameters);

		if ($response['response']['http_code'] == 200) {
			$response = $response['html'];
		} else {
			$response = array();
		}

		return $response;
	}

	/**
	 * set_api
	 *
	 * @param   array       $data
	 *          string      $data['callback_url']
	 *          string      $data['client_id']
	 *          string      $data['secret_key']
	 *          string      $data['access_token']
	 */
	public function set_api ($data) {
		if (isset($data['callback_url'])) {
			$this->callback_url = $data['callback_url'];
		}

		if (isset($data['client_id'])) {
			$this->client_id = $data['client_id'];
		}

		if (isset($data['secret_key'])) {
			$this->secret_key = $data['secret_key'];
		}

		if (isset($data['access_token'])) {
			$this->access_token = $data['access_token'];
		}
	}

	/**
	 * authorize
	 *
	 * @param   string      $state
	 *
	 * @return  string      $url
	 */
	public function authorize ($state = '') {
		$data = array();
		$data['client_id'] = $this->client_id;
		$data['redirect_uri'] = $this->callback_url;
		$data['response_type'] = 'code';
		$data['state'] = $state;

		$url = $this->api_url.'oauth/authorize?'.http_build_query($data);
		return $url;
	}

	/**
	 * access_token
	 *
	 * @param   string      $code
	 *
	 * @return  array       $response
	 *          string      $response['access_token']
	 */
	public function access_token ($code) {
		$data = array();
		$data['client_id'] = $this->client_id;
		$data['client_secret'] = $this->secret_key;
		// $data['redirect_uri'] = urlencode($this->callback_url);
		$data['redirect_uri'] = $this->callback_url;
		$data['code'] = $code;
		$data['grant_type'] = 'authorization_code';

		$url = $this->api_url.'oauth/access_token?'.http_build_query($data);
		parse_str(file_get_contents($url),$response);
		return $response;
	}

	/**
	 * blog_info
	 *
	 * 블로그 정보 API
	 * http://www.tistory.com/guide/api/blog
	 *
	 * @return  array       $data
	 */
	public function blog_info () {
		$data = $this->_get('apis/blog/info');
		return $data;
	}

	/**
	 * post_list
	 *
	 * 최근 게시글 목록 API
	 * http://www.tistory.com/guide/api/post#post-list
	 *
	 * @param   array       $parameters
	 *          string      $parameters['blogName']     xxx.tistory.com 의 xxx
	 *
	 *          int         $parameters['page']         생략하면 첫번째 페이지
	 *          int         $parameters['count']        생략하면 한페이지당 10개, 최대 30개까지 설정가능
	 *          int         $parameters['categoryId']   생략하면 카테고리 구분없이 출력
	 *          string      $parameters['sort']         id:글번호, date:작성날짜, 생략시 글번호
	 *
	 * @return  array       $data
	 */
	public function post_list ($parameters) {
		$data = $this->_get('apis/post/list',$parameters);
		return $data;
	}

	/**
	 * post_write
	 *
	 * 게시글 작성하기 API
	 * http://www.tistory.com/guide/api/post#post-write
	 *
	 * @param   array       $parameters
	 *          string      $parameters['blogName']         xxx.tistory.com 의 xxx
	 *          string      $parameters['title']            post title
	 *
	 *          string      $parameters['visibility']       0: 비공개, 1: 보호, 2: 공개, 3: 발행, 생략시 비공개
	 *          int         $parameters['published']        UNIX_TIMESTAMP() 값을 넣을경우, 해당 날짜에 예약발행 처리
	 *          int         $parameters['category']         생략시 0(분류없음)
	 *          string      $parameters['content']          글 내용
	 *          string      $parameters['slogan']           문자 주소
	 *          string      $parameters['tag']              ,로 구분하며 이어서 입력
	 *
	 * @return  array       $data
	 */
	public function post_write ($parameters) {
		$data = $this->_post('apis/post/write',$parameters);
		return $data;
	}

	/**
	 * post_modify
	 *
	 * 게시글 수정하기 API
	 * http://www.tistory.com/guide/api/post#post-modify
	 *
	 * @param   array       $parameters
	 *          string      $parameters['blogName']         xxx.tistory.com 의 xxx
	 *          string      $parameters['title']            게시글 제목
	 *          int         $parameters['postId']           게시글 번호
	 *
	 *          int         $parameters['visibility']       0: 비공개, 1: 보호, 2: 공개, 3: 발행
	 *          int         $parameters['category']         생략시 0(분류없음)
	 *          string      $parameters['content']          글 내용
	 *          string      $parameters['slogan']           문자 주소
	 *          string      $parameters['tag']              ,로 구분하며 이어서 입력
	 *
	 * @return  array       $data
	 */
	public function post_modify ($parameters) {
		$data = $this->_post('apis/post/modify',$parameters);
		return $data;
	}

	/**
	 * post_read
	 *
	 * 글 읽기 API (단일)
	 * http://www.tistory.com/guide/api/post#post-read
	 *
	 * @param   array       $parameters
	 *          string      $parameters['blogName']         xxx.tistory.com 의 xxx
	 *          int         $parameters['postId']           게시글 번호
	 *
	 * @return  array       $data
	 */
	public function post_read ($parameters) {
		$data = $this->_get('apis/post/modify',$parameters);
		return $data;
	}

	/**
	 * post_attach
	 *
	 * 파일 첨부 API
	 * http://www.tistory.com/guide/api/post#post-attach
	 *
	 * @param   array       $parameters
	 *          string      $parameters['blogName']         xxx.tistory.com 의 xxx
	 *          string      $parameters['uploadedfile']     multipart file data
	 *
	 * @return  array       $data
	 */
	public function post_attach ($parameters) {
		$data = array();

		$parameters[CURLOPT_HTTPHEADER] = array('Content-Type'=>'multipart/form-data');
		$parameters[CURLOPT_POST] = true;

		// set access_token
		if (isset($this->access_token)) {
			$data['access_token'] = $this->access_token;
			$data['output'] = 'json';
		}

		if (is_file($parameters['uploadedfile'])) {
			$mime_type = mime_content_type($parameters['uploadedfile']);

			$cf = curl_file_create($parameters['uploadedfile'],$mime_type,pathinfo($parameters['uploadedfile'],PATHINFO_BASENAME));
			$data['uploadedfile'] = $cf;
		}

		$data['blogName'] = $parameters['blogName'];
		unset($parameters['blogName']);

		unset($parameters['uploadedfile']);
		$parameters[CURLOPT_POSTFIELDS] = $data;
		$response = $this->get_content_curl($this->api_url.'apis/post/attach',$parameters);

		$data = array();
		if ($response['response']['http_code'] == 200) {
			$data = $response['html'];
		}

		return $data;
	}

	/**
	 * post_delete
	 *
	 * 글 삭제 API
	 * http://www.tistory.com/guide/api/post#post-delete
	 *
	 * @param   array       $parameters
	 *          string      $parameters['blogName']         xxx.tistory.com 의 xxx
	 *          int         $parameters['postId']           게시글 번호
	 *
	 * @return  array       $data
	 */
	public function post_delete ($parameters) {
		$data = $this->_post('apis/post/delete',$parameters);
		return $data;
	}

	/**
	 * category_list
	 *
	 * 카테고리 목록 API
	 * http://www.tistory.com/guide/api/category#category-list
	 *
	 * @param   array       $parameters
	 *          string      $parameters['blogName']         xxx.tistory.com 의 xxx
	 *
	 * @return  array       $data
	 */
	public function category_list ($parameters) {
		$data = $this->_get('apis/category/list',$parameters);
		return $data;
	}

	/**
	 * comment_list
	 *
	 * 게시글 댓글 목록 API
	 * http://www.tistory.com/guide/api/comment#comment-list
	 *
	 * @param   array       $parameters
	 *          string      $parameters['blogName']         xxx.tistory.com 의 xxx
	 *          int         $parameters['postId']           게시글 번호
	 *
	 * @return  array       $data
	 */
	public function comment_list ($parameters) {
		$data = $this->_get('apis/comment/list',$parameters);
		return $data;
	}

	/**
	 * comment_newest
	 *
	 * 최근 댓글 목록 API
	 * http://www.tistory.com/guide/api/comment#comment-newest
	 *
	 * @param   array       $parameters
	 *          string      $parameters['blogName']         xxx.tistory.com 의 xxx
	 *
	 *          int         $parameters['page']             생략하면 첫번째 페이지
	 *          int         $parameters['count']            생략하면 한페이지당 10개, 최대 10개까지 설정가능
	 *
	 * @return  array       $data
	 */
	public function comment_newest ($parameters) {
		$data = $this->_get('apis/comment/newest',$parameters);
		return $data;
	}

	/**
	 * comment_write
	 *
	 * 댓글 작성 API
	 * http://www.tistory.com/guide/api/comment#comment-write
	 *
	 * @param   array       $parameters
	 *          string      $parameters['blogName']         xxx.tistory.com 의 xxx
	 *          int         $parameters['postId']           게시글 번호
	 *          string      $parameters['content']          댓글 내용
	 *
	 *          int         $parameters['parentId']         댓글의 댓글일 경우만 사용
	 *          int         $parameters['secret']           1:비밀댓글 / 그외 공개 댓글
	 *
	 * @return  array       $data
	 */
	public function comment_write ($parameters) {
		$data = $this->_post('apis/comment/write',$parameters);
		return $data;
	}

	/**
	 * comment_modify
	 *
	 * 댓글 수정 API
	 * http://www.tistory.com/guide/api/comment#comment-modify
	 *
	 * @param   array       $parameters
	 *          string      $parameters['blogName']         xxx.tistory.com 의 xxx
	 *          int         $parameters['postId']           게시글 번호
	 *          int         $parameters['commentId']        댓글ID
	 *          string      $parameters['content']          댓글 내용
	 *
	 *          int         $parameters['parentId']         댓글의 댓글일 경우만 사용
	 *          int         $parameters['secret']           1:비밀댓글 / 그외 공개 댓글
	 *
	 * @return  array       $data
	 */
	public function comment_modify ($parameters) {
		$data = $this->_post('apis/comment/modify',$parameters);
		return $data;
	}

	/**
	 * comment_delete
	 *
	 * 댓글 삭제 API
	 * http://www.tistory.com/guide/api/comment#comment-delete
	 *
	 * @param   array       $parameters
	 *          string      $parameters['blogName']         xxx.tistory.com 의 xxx
	 *          int         $parameters['postId']           게시글 번호
	 *          int         $parameters['commentId']        댓글ID
	 *
	 * @return  array       $data
	 */
	public function comment_delete ($parameters) {
		$data = $this->_post('apis/comment/delete',$parameters);
		return $data;
	}

	/**
	 * guestbook_list
	 *
	 * 방명록 목록 API
	 * http://www.tistory.com/guide/api/guestbook#guestbook-list
	 *
	 * @param   array       $parameters
	 *          string      $parameters['blogName']         xxx.tistory.com 의 xxx
	 *
	 * @return  array       $data
	 */
	public function guestbook_list ($parameters) {
		$data = $this->_get('apis/guestbook/list',$parameters);
		return $data;
	}

	/**
	 * guestbook_write
	 *
	 * 방명록 작성 API
	 * http://www.tistory.com/guide/api/guestbook#guestbook-write
	 *
	 * @param   array       $parameters
	 *          string      $parameters['blogName']         xxx.tistory.com 의 xxx
	 *          string      $parameters['content']          방명록 내용
	 *
	 *          int         $parameters['parentId']         방명록의 답글일 경우만 사용
	 *          int         $parameters['secret']           1:비밀 방명록 / 그외 공개 방명록
	 *
	 * @return  array       $data
	 */
	public function guestbook_write ($parameters) {
		$data = $this->_post('apis/guestbook/write',$parameters);
		return $data;
	}

	/**
	 * guestbook_modify
	 *
	 * 방명록 수정 API
	 * http://www.tistory.com/guide/api/guestbook#guestbook-modify
	 *
	 * @param   array       $parameters
	 *          string      $parameters['blogName']         xxx.tistory.com 의 xxx
	 *          int         $parameters['guestbookId']      방명록ID
	 *          string      $parameters['content']          방명록 내용
	 *
	 *          int         $parameters['parentId']         방명록의 답글일 경우만 사용
	 *          int         $parameters['secret']           1:비밀 방명록 / 그외 공개 방명록
	 *
	 * @return  array       $data
	 */
	public function guestbook_modify ($parameters) {
		$data = $this->_post('apis/guestbook/modify',$parameters);
		return $data;
	}

	/**
	 * guestbook_delete
	 *
	 * 방명록 삭제 API
	 * http://www.tistory.com/guide/api/guestbook#guestbook-delete
	 *
	 * @param   array       $parameters
	 *          string      $parameters['blogName']         xxx.tistory.com 의 xxx
	 *          int         $parameters['guestbookId']      방명록ID
	 *
	 * @return  array       $data
	 */
	public function guestbook_delete ($parameters) {
		$data = $this->_post('apis/guestbook/delete',$parameters);
		return $data;
	}
}
