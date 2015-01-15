<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Sample extends CI_Controller {


	public function index()
	{	
		$this->load->library('facebook_v3');
		
		$fb_param['app_id']='Your-App-ID';
		$fb_param['app_skey']='Your-App-SecretKey';
		$fb_param['app_name']='Your-App-Name';
		$fb_param['app_redirect']="https://apps.facebook.com/{$fb_param['app_name']}/"; //Canvas, Website or PageTab Url
		$fb_param['app_permission']=array('email', 'user_friends','public_profile','user_likes','user_photos','publish_actions'); // Scope 
		$fb_param['app_javascriptpermissions']='public_profile,email,user_friends,'; // experimental , no function
		$fb_param['app_method']='canvas'; //canvas, redirect
		//$fb_param['app_cancel_uri']='https://www.facebook.com/jumpsea.development'; //Redirect Url for prevent Cancel Button Unlimited Loop
		
		$this->facebook_v3->_init($fb_param); //Initialize facebook apps
		
		//User Profile
		$userdata = $this->facebook_v3->getGraphObject()->asArray();
		
		//Mapping Apps under same Business ID require
		$bid = $this->facebook_v3->BusinessId();
		
		if (!empty($bid))
		{
			foreach ($bid as $j)
			{
				echo 'FB User ID : ',@$j['id'],'<BR>';
				echo 'APP ID : ' , @$j['app']['id'],'<BR>';
				echo 'APP Name : ' , @$j['app']['name'],'<BR>';
				echo 'APP NameSpace : ' , @$j['app']['namespace'],'<BR><BR>';
			}
		}
		
	}
	
	
}
