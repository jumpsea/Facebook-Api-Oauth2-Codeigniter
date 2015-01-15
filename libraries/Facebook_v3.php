<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 *
 * File Facebook Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Facebook Application
 * @author		Jumpsea Lim + Yee IT Loong
 * @version		2.3.2
 * @date		2014-06-23
 * @UpdateDate  2014-01-08 [Jumpsea]
 *
 */
 
 
require_once( BASEPATH.'facebook/Facebook_v3/Entities/SignedRequest.php');
require_once( BASEPATH.'facebook/Facebook_v3/Entities/AccessToken.php');
require_once( BASEPATH.'facebook/Facebook_v3/HttpClients/FacebookCurl.php');
require_once( BASEPATH.'facebook/Facebook_v3/HttpClients/FacebookHttpable.php');
require_once( BASEPATH.'facebook/Facebook_v3/HttpClients/FacebookCurlHttpClient.php');
require_once( BASEPATH.'facebook/Facebook_v3/HttpClients/FacebookStreamHttpClient.php');
require_once( BASEPATH.'facebook/Facebook_v3/FacebookSession.php' );
require_once( BASEPATH.'facebook/Facebook_v3/FacebookRequest.php' );
require_once( BASEPATH.'facebook/Facebook_v3/FacebookResponse.php' );
require_once( BASEPATH.'facebook/Facebook_v3/FacebookSDKException.php' );
require_once( BASEPATH.'facebook/Facebook_v3/FacebookRequestException.php' );
require_once( BASEPATH.'facebook/Facebook_v3/FacebookSignedRequestFromInputHelper.php');
require_once( BASEPATH.'facebook/Facebook_v3/FacebookAuthorizationException.php' );
require_once( BASEPATH.'facebook/Facebook_v3/FacebookRedirectLoginHelper.php' );
require_once( BASEPATH.'facebook/Facebook_v3/FacebookCanvasLoginHelper.php');
require_once( BASEPATH.'facebook/Facebook_v3/GraphObject.php' );
require_once( BASEPATH.'facebook/Facebook_v3/GraphSessionInfo.php' );
 
use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\FacebookResponse;
use Facebook\FacebookCanvasLoginHelper;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookSignedRequestFromInputHelper;
use Facebook\FacebookSDKException;
use Facebook\FacebookRequestException;
use Facebook\FacebookAuthorizationException;
use Facebook\GraphObject;
use Facebook\GraphSessionInfo;
 
 
class CI_Facebook_v3 {
	protected $FACEBOOK_APP_ID;
	protected $FACEBOOK_APPS_NAME;
	protected $FACEBOOK_SECRET;
	protected $FACEBOOK_PERMISSION;
	protected $FACEBOOK_REDIRECT;
	protected $FACEBOOK_METHOD;
	protected $FACEBOOK_CANCEL_URI;
	protected $graphObject;
	protected $accesstoken;
	protected $session;
	
	/***
	*
	* Graph Version Expiry Date
	* v1.0 - April 30, 2015	
	* v2.0 - Aug 7, 2016
	* v2.1 - Aug, 2017 ???
	*
	***/
	const Grahp_Version = 'v2.2'; //Graph API version
	const SDK_Version = 'v4-4.0'; //API SDK version

	public function __construct()
	{	
		$this->CI =& get_instance();
		log_message('debug', "Facebook_v3 Class Initialized");
		
		if (!session_id()) session_start();
		
		if ($this->CI->input->get('redirect')<>''){
			echo "<script type='text/javascript'>parent.location.href = '{$this->CI->input->get('redirect')}';</script>"; exit;
		}
		else if ($this->CI->input->get('re-direct')<>'') {
			echo "<script type='text/javascript'>parent.location.href = '{$this->CI->input->get('re-direct')}';</script>"; exit;
		}
	}
	
	
	/***
	* PHP FB API
	* Initialize FB APPS
	* 
	* @Param Initialize Parameter[Array]
	*
	* ---  Login Method --- 
	* Canvas Login function Worked
	* Redirect Login still unavailable
	* ---------------------
	*
	***/
	public function _init($param=''){
		$this->FACEBOOK_APP_ID=$param['app_id']; // application id 
		$this->FACEBOOK_APPS_NAME=$param['app_name']; //application name
		$this->FACEBOOK_SECRET=$param['app_skey']; // appication key
		$this->FACEBOOK_REDIRECT=$param['app_redirect']; // appication key
		$this->FACEBOOK_PERMISSION=isset($param['app_permission']) ? $param['app_permission'] : false ;
		$this->FACEBOOK_METHOD=isset($param['app_method']) && (strtolower($param['app_method'])=='redirect' || strtolower($param['app_method'])=='canvas') ? strtoupper($param['app_method']) : 'REDIRECT' ; //canvas, redirect
		$this->FACEBOOK_CANCEL_URI=isset($param['app_cancel_uri']) ? $param['app_cancel_uri'] : 'https://www.facebook.com/'.$param['app_name'] ; // Url for cancel redirect url
		
		if (($this->CI->input->get('error') <>'' && $this->CI->input->get('error_code') <> '') OR ($this->SessionFailedCount() >= 2))
		{
			$this->PurgeSessionFailed();
			echo "<script type='text/javascript'>parent.location.href = '{$this->FACEBOOK_CANCEL_URI}';</script>"; exit;
		}
		
		$this->facebooksession($this->FACEBOOK_APP_ID, $this->FACEBOOK_SECRET);
	}
	
	/***
	* 
	* FB Session & Login Method
	*
	* @Param Facebook_APP_ID[String] , Facebook_App_SecretKey[String]
	*
	***/
	public function facebooksession($key, $secret){
		FacebookSession::setDefaultApplication($key, $secret);
		
		if ($this->FACEBOOK_METHOD == 'CANVAS'  )
			$this->facebookcanvasloginhelper($this->FACEBOOK_REDIRECT,$this->FACEBOOK_PERMISSION);
		else if ($this->FACEBOOK_METHOD == 'REDIRECT'  )
			$this->facebookredirectloginhelper($this->FACEBOOK_REDIRECT,$this->FACEBOOK_PERMISSION);
		else
			echo 'System Error, Please Contact Customer Services';
	
	}
	
	/***
	* 
	* FB Aps Access Token
	*
	* @return accesstoken [String]
	*
	***/
	public function getaccesstoken(){
		return $this->accesstoken;
	}
	
	/***
	* 
	* FB Apps Scope ID
	*
	* @return Apps scope ID [String]
	*
	***/
	public function app_scope_id(){
		return $this->app_scope_id;
	}
	
	/***
	* 
	* FB Apps Canvas Login method
	*
	* @Param RedirectUrl[String], PermissionScope[String]
	*
	* @return GraphObject [Object]
	*
	***/
	public function facebookcanvasloginhelper($redirect_url,$scope){ //facebooksessionFacebook apps for Canvas Type
		$helper = new Facebook\FacebookCanvasLoginHelper();
		
		try {
			$session = $helper->getSession();
			if($session){
				//get fb user profile via graph object
				$request = new Facebook\FacebookRequest($session, 'GET', '/me');
				$response = $request->execute();
				$this->graphObject = $response->getGraphObject();
				$this->accesstoken = $session->getToken();
				$this->PurgeSessionFailed();
				return $this->graphObject;
			}
		} catch(FacebookRequestException $ex) {
		   return $ex;   
		} 

		if (!isset($session))
		{
			//no session
			$helper = new Facebook\FacebookRedirectLoginHelper($redirect_url);
			//$helper->disableSessionStatusCheck();
			$loginUrl = $helper->getLoginUrl($scope);
			$this->SessionFailed();
			echo "<html><head><script>parent.location.href='{$loginUrl}';</script></head><body></body></html>";
			/*
                        $permission = is_array($this->FACEBOOK_PERMISSION) ? implode(',',$this->FACEBOOK_PERMISSION) : $this->FACEBOOK_PERMISSION ;
                        $facebookLoginHtml = "https://www.facebook.com/dialog/oauth?client_id={$this->FACEBOOK_APP_ID}&redirect_uri={$this->FACEBOOK_REDIRECT}&scope={$permission}";
                        echo "<html><head><script>parent.location.href='{$facebookLoginHtml}';</script></head><body></body></html>";
			*/
                        exit;
		}
	}//end public function facebookcanvasloginhelper
		
	public function facebookredirectloginhelper($redirect_url,$scope){ //login facebook with PHP SDK, This is not a CanvasBase
		
		$helper = new Facebook\FacebookRedirectLoginHelper($redirect_url);
		
		try {
		  $session = $helper->getSessionFromRedirect();
		  $this->session = $session;
		}catch(FacebookRequestException $ex){
			return $ex; // When Facebook returns an error
		}catch(\Exception $ex){
			return $ex; //When validation fails or other local issues 
		}
		
		if (!isset( $session )){
			//no session
			//facebook look like change  canvas app, need user allow the app with javascript login
			$helper = new Facebook\FacebookRedirectLoginHelper($redirect_url);
			//$helper->disableSessionStatusCheck();
			$loginUrl = $helper->getLoginUrl($scope);
			$this->SessionFailed();
			echo "<html><head><script>parent.location.href='{$loginUrl}';</script></head><body></body></html>";
		} else {
			//get fb user profile via graph object
			$request = new Facebook\FacebookRequest($session, 'GET', '/me');
			$response = $request->execute();
			$this->graphObject = $response->getGraphObject();
			$this->accesstoken = $session->getToken();
			$this->PurgeSessionFailed();
			return $this->graphObject;
		}
		
	}//end public function redirectloginhelper($redirect_url,$scope)
	
	/***
	* 
	* FB Apps Graph API
	*
	* @Param Type[String] >>> permissions,albums,likes,photos ...
	*
	* @return GraphAPI Response[Array]
	*
	***/
	public function api($type=''){ //similiar with $facebook->api() in Version 3.2.3
		
		$user = $this->getFBid();
		$accesstoken = $this->accesstoken;;
		$url='https://graph.facebook.com/'.self::Grahp_Version.'/';
		$url.="$user/$type?access_token=$accesstoken";
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, '5');
		$content = trim(curl_exec($ch));
		curl_close($ch);
		return json_decode($content,true);
	}
	
	/***
	* 
	* FB Apps Create New Album
	*
	* @Param AlbumName[String], AlbumMessage[String]
	*
	* @return Album ID[String]
	*
	***/
	public function create_album($name,$message='')
	{
		$privacy = Array('value'=>'EVERYONE');
		$privacy =  (object)$privacy;
		/* 
		//--- Privacy settings return error, under observation ---
		$request = new FacebookRequest(
		  $this->session,
		  'POST',
		  '/me/albums',
		  array (
			'name' => $name,
			'message' => $message,
			'privacy' => $privacy
		  )
		);
		*/
		
		$request = new FacebookRequest(
		  $this->session,
		  'POST',
		  '/me/albums',
		  array (
			'name' => $name,
			'message' => $message
		  )
		);
		$response = $request->execute();
		
		return @$response->getGraphObject()->getProperty('id');
	}
	
	
	/***
	* 
	* FB Apps Publish Photo into Album
	*
	* @Permission Require : user_photos, publish_actions
	*
	* @Param Photo Parameter[Array] >>> album_name[String], album_message[String], message[String], images[Url]
	*
	* @return Photo ID[String]
	*
	***/
	public function photo_publish($item)
	{
		$album_id = $this->album_match(@$item['album_name']);
		$album_id = ($album_id == '') ? $this->create_album(@$item['album_name'], @$item['album_message']) : $album_id;
		
		try {
			// Upload to a user's profile. The photo will be in the
			// first album in the profile. You can also upload to
			// a specific album by using /ALBUM_ID as the path  
		    // If you're not using PHP 5.5 or later, change the file reference to:
			// 'source' => '@/path/to/file.name'  
			$response = (new FacebookRequest(
			  $this->session, 'POST', '/'.$album_id.'/photos/' , array(
			    'message' => @$item['message'],
				'url' => @$item['images'],
			  )
			))->execute()->getGraphObject();
			
			return @$response->getProperty('id');

		  } catch(FacebookRequestException $e) {
			echo "Exception occured, code: " . $e->getCode();
			echo " with message: " . $e->getMessage();
			return '0';
		  }   
		  
	}
	
	/***
	* 
	* FB Apps Feed
	*
	* @Permission Require : publish_actions
	*
	* @Param Feed Parameter[Array] >>> link[String], message[String], name[String], caption[Url], description[String], picture[String:Url]
	*
	* @return Post ID[String]
	*
	***/
	public function feed($item='')
	{	
		try {
			$response = (new FacebookRequest(
			  $this->session, 'POST', '/me/feed', array(
				'link' => @$item['link'] ,
				'message' => @$item['message'],
				'name'=>@$item['name'],
				'caption'=>@$item['caption'],
				'description'=>@$item['description'],
				'picture'=>@$item['images'],
			  )
			))->execute()->getGraphObject();

			return @$response->getProperty('id');

		 } catch(FacebookRequestException $e) {

			echo "Exception occured, code: " . $e->getCode();
			echo " with message: " . $e->getMessage();
			return '0';
		  }   
		
	}
	
	/***
	* 
	* FB Apps Check Like 
	*
	* @Permission Require : user_likes
	*
	* @Param APPS ID Parameter[String|Array]
	* 
	* @return Boolean
	*
	***/
	public function is_like($likes='')
	{
		$bool = true;
		$id = array();
		
		$likes = is_array($likes) ? $likes : array($likes) ;
		
		$response = $this->api('likes');
		
		if (isset($response['data'])) {
			foreach($response['data'] as $j) {
				$id[] = isset($j['id']) ? $j['id'] : ''; 
			}
		}
		
		if (!empty($likes)) {
			foreach ( $likes as $c ) {
				if (!in_array($c,$id)) {
					$bool = false; break; 
				}
			}
		} else {
			$bool = false;
		}
		
		return $bool;
	}
	
	/***
	* 
	* FB Apps search Friends, ONLY show who using this app 
	*
	* @Permission Require : user_friends
	*
	* @return Friend List[Array]
	*
	***/
	public function friend()
	{
		$response = $this->api('friends');
		
		if (isset($response['data'])) {
			return $response['data'];
		}
		
		return array();
	}
	
	/***
	* 
	* FB Apps count total Friends 
	*
	* @Permission Require : user_friends
	*
	* @return Total friend[String]
	*
	***/
	public function total_friend()
	{
		$response = $this->api('friends');
		
		if (isset($response['summary']['total_count'])) {
			return $response['summary']['total_count'];
		}
		
		return '0';
	}
	
	/***
	* 
	* FB Apps Match Album by Name 
	*
	* @Permission Require : user_photos
	*
	* @Param Album Name[String:Utf8]
	*
	* @return AlbumID[String]
	*
	***/
	public function album_match($name='')
	{
		$response = $this->api('albums');
		
		$album = array();
		
		if (isset($response['data'])) {
			foreach($response['data'] as $j) {
				if ($j['name'] == $name ) {
					$album = $j; break;
				}
			}
		}
		
		return isset($album['id']) ? $album['id'] : '';
	}
	
	/***
	* 
	* FB Apps Return FB ID 
	*
	* @return Facebook User Scope ID[String]
	*
	***/
	public function getFBid()
	{
		return @$this->graphObject->getProperty('id');
	}
	
	/***
	* 
	* FB Apps Business ID 
	*
	* @return Facebook Business ID[Array]
	*
	***/
	public function BusinessId()
	{
		$bid = array();
		
		$response = $this->api('ids_for_business');

		$bid = isset($response['data']) ? $response['data'] : $bid;

		return $bid;
	}
	
	/***
	* 
	* FB Apps Check Business ID 
	*
	* @Permission Require : Mapping Apps under same Business ID 
	*
	* @Param FB Apps ID Parameter[String|Array]
	* 
	* @return Boolean
	*
	***/
	public function is_ids_business($ids='')
	{
		$bool = true;
		$id = array();
		
		$ids = is_array($ids) ? $ids : array($ids) ;
		
		$response = $this->api('ids_for_business');
		
		if (isset($response['data'])) {
			foreach($response['data'] as $j) {
				$id[] = isset($j['app']['id']) ? $j['app']['id'] : ''; 
				//$name[] = isset($j['app']['name']) ? $j['app']['name'] : ''; 
				//$namespace[] = isset($j['app']['namespace']) ? $j['app']['namespace'] : ''; 
			}
		}
		
		if (!empty($ids)) {
			foreach ( $ids as $c ) {
				if (!in_array($c,$id)) {
					$bool = false; break; 
				}
			}
		} else {
			$bool = false;
		}
		
		return $bool;
	}
	
	/***
	* 
	* FB Apps Return FB User Object
	*
	* @return Facebook User Data[Object]
	*
	***/
	public function getGraphObject()
	{
		return @$this->graphObject;
	}
	
        /***
	* 
	* FB Apps Failed Login Session 
	*
        * to avoid users fall unlimited cancel loop
        * 
	* @return void
	*
	***/
	public function SessionFailed()
	{
		if (isset($_SESSION['FB_Failed'])) {
                    if (isset($_SESSION['FB_Failed_Date']) && $_SESSION['FB_Failed_Date']+60 < time()) { //Reset Login Failed Counter, every 60sec
			$_SESSION['FB_Failed'] = 1;
                    }
                    else {
                        $_SESSION['FB_Failed']++;
                    }
                }
		else {
			$_SESSION['FB_Failed'] = 1;
                }
                
                $_SESSION['FB_Failed_Date'] = time();
	}
	
        /*
         * Return session failed counter
         * 
         * @Return Int
         */
	public function SessionFailedCount()
	{
		return isset($_SESSION['FB_Failed']) ? $_SESSION['FB_Failed'] : 0;
	}
	
        /*
         * Purge login failed session
         * 
         * @Return void
         */
	public function PurgeSessionFailed()
	{
		if(isset($_SESSION['FB_Failed'])) {
			$_SESSION['FB_Failed'] = 0;
                        $_SESSION['FB_Failed_Date'] = '';
			unset($_SESSION['FB_Failed']);
                        unset($_SESSION['FB_Failed_Date']);
		}
	}
	
	
	
}
// END Facebook Class

/* End of file Facebook_v3.php */
/* Location: ./system/libraries/Facebook_v3.php */
?>