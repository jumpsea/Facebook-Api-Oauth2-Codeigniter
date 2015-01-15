<?php 

//facebook rest api method

function fb_GetFqlQuery($query,$token)
{

	$method_uri="https://api.facebook.com/method/fql.query?query=";
	$uri=$method_uri.urlencode($query)."&access_token=".$token;	
	$fxml=file_get_contents($uri);	
	return $fxml;
}


function fb_CheckLike($facebook,$like)
{
	$likes = $facebook->api("/me/likes/",$like);
	return $likes;
}

function fb_sendEmail($uid,$subject,$text,$token)
{

	$method_uri="https://api.facebook.com/method/notifications.sendEmail?recipients=";
	$uri=$method_uri.$uid."&subject=".urlencode($subject)."&subject=".urlencode($subject)."&fbml=".urlencode($text)."&access_token=".$token;
	$fxml=file_get_contents($uri);
	return $fxml;
}

function fb_getAlbumAid($item,$fb,$uid)
{
	$albums = $fb->api("/$uid/albums");
	
	foreach ($albums as $i => $j )
	{		
		foreach ($j as $k=>$l)
		{
			if ($l['name']==$item['album'])				
			{				
				//$album_id=$l['id'];
				$album_link=$l['link'];
				$link=explode('aid=',$album_link);
				
				if (!is_numeric($link[1]))
				{
					$aid=explode('&=',$link);
				}
				else
				{
					$aid=$link[1];
				}				
				
				break;
			}
		}		
	}
	
	if(!isset($aid))
	{
		$aid='';
	}
	
	return $aid;
}

function fb_photoscreateAlbum($item,$token,$fb)
{
	/*
	//create album
	$method_uri="https://api.facebook.com/method/photos.createAlbum?name={$item['album']}";	
	$uri=$method_uri."&location=&visible=friends&uid=$uid&access_token=".$token;				
	$fxml=file_get_contents($uri);	
	$arr=xml_array($fxml);	
	$a_id=explode("_",$arr['photos_createAlbum_response']['aid']);	
	*/
	$privacy = Array('value'=>'ALL_FRIENDS');
	$privacy =  (object)$privacy;

	$albumDetails = array(
	'name' => $item['album'],
	'privacy' => $privacy
	);
	$a_id=$fb->api('/me/albums', 'post', $albumDetails);
	$aid=$a_id['id'];
	
	return $aid;
}

function fb_photosupload($item,$token,$fb='')
{
	/*
	$fb->setFileUploadSupport(true);
	$file=$item['images'];
	
	if (realpath($file)=="")
	{
		$f_path=str_replace("../", "", $file);
		$file_path=realpath(".")."/".$f_path;
	}
	else
	{
		$file_path=realpath($file);
	}
	
	$data = array(
            basename($file) => "@".$file_path,
            "caption" => $item['caption'],
            "aid" => $aid,
            "access_token" => $token,
            'method' => 'photos.upload'
			);
			
			
	$result =$fb->api($data);
	*/
	
	$fb->setFileUploadSupport(true);
	$album_details = array(
    'message'=>  $item['album_caption'],
    'name'=> $item['album']);
	
	
	//Album Event Handle
	$albums = $fb->api('/me/albums');
	
	foreach ($albums['data'] as $album) {
		//GET Aid if the current album name is already in facebook
		if($album['name'] == $item['album'])
		{
			$album_uid = $album['id'];
			$album_name = $album['name'];
			break;
		}
	} 
	
	 if(!isset($album_uid))
	 {
		//album dosn¡¯t exist, so wee need to create one
		$create_album = $fb->api('/me/albums', 'post', $album_details);
		$album_uid = $create_album['id'];
	}	
	
	
	//////////////// Upload a picture //////////////
	
	$photo_details = array(
    'message'=> $item['photo_caption'] ,
	'tags' 	=>  $item['photo_tags'],
					 );


$photo_details['image'] = '@' . $item['photo_path'];
//echo ('/'.$album_uid.'/photos','post', $photo_details);
$result = $fb->api('/'.$album_uid.'/photos', 'post', $photo_details);
	
	return $result;
}

function fb_setStatus($status,$token)
{
	$method_uri="https://api.facebook.com/method/users.setStatus?status=";
	$uri=$method_uri.urlencode($status)."&access_token=".$token;
	$fxml=file_get_contents($uri);
	return $fxml;
}



function fb_linkpost($item,$token,$uid)
{
	
	$method_uri="https://api.facebook.com/method/links.post?uid=";
	$uri=$method_uri.$uid."&image=".$item['images']."&comment=".$item['content']."&url=".$item['url']."&access_token=".$token;	
	$fxml=file_get_contents($uri);
	return $fxml;		
}

function fb_StreamPublish($attach,$token)
{
	$method_uri="https://api.facebook.com/method/stream.publish?attachment=";
	$uri=$method_uri.urlencode($attach)."&access_token=".$token;
	$fxml=file_get_contents($uri);
	return $fxml;	
}

function XML_Decode($fxml,$token)
{
	
	
	$f=fopen("cache/temp_".$token.".xml","w");
	fputs($f,$fxml);
	fclose($f);
	$xml=simpleXML_load_file("cache/temp_".$token.".xml");
	
	return $xml;
	
}


function fb_publish_album($item,$token,$id,$ssl='0')
{
	
	$file= $item['images'];
    
    $args[basename($file)] = '@' .$file;
	//print_r($args);
	$args['message'] = $item['content'];
	$url = 'https://graph.facebook.com/'.$id.'/photos?access_token='.$token;	
	//echo	$url;
    
	$ch = curl_init();        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
        $data = curl_exec($ch);
	
	return $data;
}



?>