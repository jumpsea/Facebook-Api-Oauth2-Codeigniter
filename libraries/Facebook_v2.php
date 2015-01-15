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
 * @version		1.3.0
 * @date		2014-06-23
 *
 *
 */
 
 
require_once( BASEPATH.'facebook/Facebook_v2/Entities/SignedRequest.php');
require_once( BASEPATH.'facebook/Facebook_v2/HttpClients/FacebookCurl.php');
require_once( BASEPATH.'facebook/Facebook_v2/HttpClients/FacebookHttpable.php');
require_once( BASEPATH.'facebook/Facebook_v2/HttpClients/FacebookCurlHttpClient.php');
require_once( BASEPATH.'facebook/Facebook_v2/HttpClients/FacebookStreamHttpClient.php');

require_once( BASEPATH.'facebook/Facebook_v2/FacebookSession.php' );
require_once( BASEPATH.'facebook/Facebook_v2/FacebookRequest.php' );
require_once( BASEPATH.'facebook/Facebook_v2/FacebookResponse.php' );
require_once( BASEPATH.'facebook/Facebook_v2/FacebookSDKException.php' );
require_once( BASEPATH.'facebook/Facebook_v2/FacebookRequestException.php' );
require_once( BASEPATH.'facebook/Facebook_v2/FacebookSignedRequestFromInputHelper.php');
require_once( BASEPATH.'facebook/Facebook_v2/FacebookAuthorizationException.php' );
require_once( BASEPATH.'facebook/Facebook_v2/FacebookRedirectLoginHelper.php' );
require_once( BASEPATH.'facebook/Facebook_v2/FacebookCanvasLoginHelper.php');
require_once( BASEPATH.'facebook/Facebook_v2/GraphObject.php' );
require_once( BASEPATH.'facebook/Facebook_v2/GraphSessionInfo.php' );
 
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
 
 
class CI_Facebook_v2 {
	private $FACEBOOK_APP_ID;
	private $FACEBOOK_APPS_NAME;
	private $FACEBOOK_SECRET;
	private $FACEBOOK_PERMISSION;
	private $FACEBOOK_REDIRECT;
	private $graphObject;
	private $accesstoken;

	public function __construct()
	{
		
		$this->CI =& get_instance();
		log_message('debug', "Facebook_v2 Class Initialized");
		
		if (!session_id()) 
		{
			session_start();
		}
		
		if ($this->CI->input->get('redirect')<>'')
		{
			echo "<script type='text/javascript'>parent.location.href = '{$this->CI->input->get('redirect')}';</script>";
			exit;
		}
		else if ($this->CI->input->get('re-direct')<>'')
		{
			echo "<script type='text/javascript'>parent.location.href = '{$this->CI->input->get('re-direct')}';</script>";
			exit;
		}
		
	}
	
	public function _init($param=''){
		
		$this->FACEBOOK_APP_ID=$param['app_id']; // application id 
		$this->FACEBOOK_APPS_NAME=$param['app_name']; //application name
		$this->FACEBOOK_SECRET=$param['app_skey']; // appication key
		$this->FACEBOOK_REDIRECT=$param['app_redirect']; // appication key
		$this->FACEBOOK_PERMISSION=isset($param['app_permission']) ? $param['app_permission'] : false ;
		$this->facebooksession($this->FACEBOOK_APP_ID, $this->FACEBOOK_SECRET);
	}
	
	public function facebooksession($key, $secret){
		FacebookSession::setDefaultApplication($key, $secret);
		$this->facebookcanvasloginhelper($this->FACEBOOK_REDIRECT,$this->FACEBOOK_PERMISSION);
		//$this->redirectloginhelper($this->FACEBOOK_REDIRECT,$this->FACEBOOK_PERMISSION);

	}
	
	public function getaccesstoken(){
		return $this->accesstoken;
	}
	
	public function app_scope_id(){
		return $this->app_scope_id;
	}
	
	public function facebookcanvasloginhelper($redirect_url,$scope){ //facebooksessionFacebook apps for Canvas Type
		
		$helper = new Facebook\FacebookCanvasLoginHelper();
	
		try {
		  $session = $helper->getSession();
		}catch(FacebookRequestException $ex){
			return $ex; // When Facebook returns an error
		}catch(\Exception $ex){
			return $ex;//When validation fails or other local issues  
		}
		
		if (!isset( $session )){
			$helper = new Facebook\FacebookRedirectLoginHelper($redirect_url);
			$helper->disableSessionStatusCheck();
			$loginUrl=$helper->getLoginUrl($scope);
			//Force_Direct($loginUrl);
			echo "<html><head><script>parent.location.href='{$loginUrl}';</script></head><body></body></html>";
			
			//no session
			//facebook look like change  canvas app, need user allow the app with javascript login
		}else{
			$request = new Facebook\FacebookRequest($session, 'GET', '/me');
			$response = $request->execute();
			$this->graphObject = $response->getGraphObject();
			//$acctoken=(array)$session;
			$this->accesstoken=$session->getToken();
			//$this->accesstoken=$acctoken[key($acctoken)];
			return $this->graphObject;
		}
	}//end public function facebookcanvasloginhelper
		
	public function redirectloginhelper($redirect_url,$scope){ //login facebook with PHP SDK, This is not a CanvasBase
		
		$helper = new Facebook\FacebookRedirectLoginHelper($redirect_url);
		try {
			
			$session=$helper->getSessionFromRedirect() ;
			
			
			/*
				if ( isset( $_SESSION ) && isset( $_SESSION['fb_token'] ) ) 
				{
					$session_temp=$helper->getSessionFromRedirect() ;	
					print_R($session_temp);
					print_r($_SESSION);
					if (!empty($session_temp) && !empty($_SESSION['fb_token'])) 
					{
						$session=new Facebook\FacebookSession( $_SESSION['fb_token'] );
					}
					$session=$helper->getSessionFromRedirect() ;	
				}
				else
				{
					$session=$helper->getSessionFromRedirect() ;	
				}
			*/
		
		} catch( FacebookRequestException $ex ) {
			   return $ex; // When Facebook returns an error
		} catch( Exception $ex ) {
			   return $ex; // When validation fails or other local issues
		}
		
		
		if(!isset( $session ) ) {
			echo "<html><head><script>parent.location.href='{$helper->getLoginUrl($scope)}';</script></head><body></body></html>";
		}
		else
		{
			if (!isset($_SESSION['fb_token']))
			{
				$_SESSION['fb_token'] = $session->getToken();
			}
			$this->accesstoken=$_SESSION['fb_token'];
			$request = new Facebook\FacebookRequest($session, 'GET', '/me');
			$response = $request->execute();
			$this->graphObject = $response->getGraphObject();
	
			return $this->graphObject;
		}
	}//end public function redirectloginhelper($redirect_url,$scope)
	
	public function api($accesstoken,$user){ //similiar with $facebook->api() in Version 3.2.3
		//$user='/me/permissions';
		$url='https://graph.facebook.com/';
		$url.="$user/?access_token=$accesstoken";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, '5');
		$content = trim(curl_exec($ch));
		curl_close($ch);
		return json_decode($content,true);
	}
	
	public function getGraphObject()
	{
		return $this->graphObject;
	}
	
	
}
// END Facebook Class

/* End of file Facebook.php */
/* Location: ./system/core/Facebook.php */
?>