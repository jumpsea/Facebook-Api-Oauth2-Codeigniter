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
 * @author		Jumpsea Lim
 * @version		1.3.0
 * @date		2012-11-25
 *
 *
 */
class CI_Facebook {

	private $FACEBOOK_APP_ID;
	private $FACEBOOK_APPS_NAME;
	private $FACEBOOK_SECRET;
	private $FACEBOOK_PERMISSION;
	private $FACEBOOK_REDIRECT;
	private $FACEBOOK_SSL;
	private $facebook;
	private $fb_uid;
	private $fb_profile;
	private $CI;

	function __construct()
	{
		include_once  (BASEPATH.'facebook/facebook.php');
		include_once  (BASEPATH.'facebook/facebook_rest_api.php');
		$this->CI =& get_instance();
		log_message('debug', "Facebook Class Initialized");
		
		if ($this->CI->input->get('redirect')<>'')
		{
			echo "<script type='text/javascript'>window.top.location = '{$this->CI->input->get('redirect')}';</script>";
			exit;
		}
	}
	
	function _init($param) //initialize facebook settings
	{
		$this->FACEBOOK_APP_ID=$param['app_id']; // application id 
		$this->FACEBOOK_APPS_NAME=$param['app_name']; //application name
		$this->FACEBOOK_SECRET=$param['app_skey']; // appication key
		$this->FACEBOOK_SSL=isset($param['app_ssl']) ? $param['app_ssl'] : false ;
		$ssl=$this->FACEBOOK_SSL==true ? "https" : "http"; //SSL cert
		$this->FACEBOOK_LOGIN_URL="{$ssl}://apps.facebook.com/".$this->FACEBOOK_APPS_NAME."/";
		if ($_SERVER['QUERY_STRING'] <>'') { $this->FACEBOOK_LOGIN_URL.="?".urlencode($_SERVER['QUERY_STRING']);}
		$this->FACEBOOK_REDIRECT=(isset($param['app_redirect']) && $param['app_redirect']<>'') ? $param['app_redirect'] : '' ;
		if (isset($param['app_redirect']) && $param['app_redirect']<>'') 
		{  $this->FACEBOOK_LOGIN_URL.=$_SERVER['QUERY_STRING'] <>'' ? "&redirect=".urlencode($param['app_redirect']) : "?redirect=".urlencode($param['app_redirect']); }
		$this->FACEBOOK_PERMISSION=isset($param['app_permission'])? $param['app_permission'] : "user_likes,offline_access,user_photos,user_status,email,publish_stream,status_update,read_stream"; //permission settings
		$this->facebook = new Facebook(array(
		  'appId' => $param['app_id'],
		  'secret' => $param['app_skey'],
		  'cookie' => true,
		));
		
		$user=null;

		$this->fb_uid = $this->fb_get_uid();  
		
		if ($this->fb_uid=="0")
		{
			$this->fb_permission();
		}
		else
		{
			$this->fb_profile = $this->fb_get_profile(); //get fb profile
			$this->token=$this->facebook->getAccessToken(); //get fb access token
			$sign=array("uid"=>$this->fb_uid,"signed_request"=>$this->CI->input->post('signed_request'));
			if ($this->CI->session->userdata('signed_request')<>$this->CI->input->post('signed_request')) 
			{
				$this->CI->session->set_userdata($sign); //keep latest signed request into session  * Used for IE post method
			} 
		}
	}
	
	function api($item)
	{
		$ssl=$this->FACEBOOK_SSL==true ? "https" : "http";
		$url="{$ssl}://apps.facebook.com/".$this->FACEBOOK_APPS_NAME."/";
		
		if (isset($item['redirect']) && ($item['redirect'] <>''))
		{
			$url.="?direct_url={$item['redirect']}";
		}
		
		if ($item['action']=="PostWall") // POST Feed on FB User Wall
		{
			$attach=array(
			"name"=>"{$item['name']}",
			"caption"=>"{$item['caption']}",
			"description"=>"{$item['content']}",
			"picture"=>"{$item['images']}",
			"source"=>"{$item['images']}",
			"media" => array(array("type" => "image",
			   "src"=> "{$item['images']}",
			   "width"=>"200",
						"href"=>"$url"))
			);		
		
			$attach=json_encode($attach);		
			$result=fb_StreamPublish($attach,$this->token); //FB API CALLED
		}
		
		if ($item['action']=="Publish_Album") // Upload Photo to FB User Album
		{
			$rs=fb_photosupload($item,$this->token,$this->facebook);//upload photo to album
		}
		
		if ($item['action']=="PageLike")
		{
			foreach ($item['page_id'] as $i => $page_id)
			{
				$likes = $this->facebook->api("/me/likes/$page_id");
				
				if (empty($likes['data']))
				{
					return false;
					break;
				}

			}
			return true;
		}
		
		if ($item['action']=="GetProfilePic")
		{
			$pic= isset($item['id'])? "https://graph.facebook.com/{$item['id']}/picture?access_token={$this->token}" : "https://graph.facebook.com/{$this->fb_uid}/picture?access_token={$this->token}";
			
			if ((isset($item['type'])) && ($item['type']<>''))
			{
				$pic.="&type={$item['type']}";
			}
			return $pic;
		}
		
		if ($item['action']=="GetFriendList")
		{
			$url=isset($item['id']) ? "https://graph.facebook.com/{$item['id']}/friends?access_token={$this->token}" :"https://graph.facebook.com/{$this->fb_uid}/friends?access_token={$this->token}";
			return file_get_contents($url);
		}
	}
	
	function fb_get_profile() //Get Fb user Profile
	{
		$profile=$this->fb_profile<>'' ? $this->fb_profile : $this->facebook->api('/me') ;
		return $profile;
	}
	
	function fb_permission() //allow user account permission
	{
		$ssl=$this->FACEBOOK_SSL==true ? "https" : "http";
		echo "<script type='text/javascript'>window.top.location = '$ssl://www.facebook.com/dialog/oauth?client_id={$this->FACEBOOK_APP_ID}&redirect_uri={$this->FACEBOOK_LOGIN_URL}&scope={$this->FACEBOOK_PERMISSION}'; </script>";
		exit;
	}
	
	function fb_get_uid() //get facebook uid
	{
		$uid=$this->fb_uid<>'' ? $this->fb_uid : $this->facebook->getUser();
		return $uid;
	}

	function fb_get_accesstoken()
	{
		return $this->token;
	}
	

}
// END Facebook Class

/* End of file Facebook.php */
/* Location: ./system/core/Facebook.php */
?>