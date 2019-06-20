<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Auth;
use Facebook\Facebook;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirectToProvider($provider)
    {
    	//return Socialite::with('facebook')->redirect();
    	return Socialite::with('facebook')->fields([
    		'id', 'name', 'email'
    	])->scopes([
    		'email', 'manage_pages', 'publish_pages', 'publish_pages'
    	])->redirect();
    }

    public function handleProviderCallback($provider)
    {
    	$fb_user = Socialite::driver('facebook')->user();

    	if($user = User::where('email', $fb_user->email)->first()){
    		return $this->authAndRedirect($user);
    	}else{
    		$user = User::create([
				'name'     => $fb_user->name,
				'email'    => $fb_user->email,
				'avatar'   => $fb_user->avatar,
				'password' => bcrypt(str_random(16)),
				'fb_token' => $fb_user->token,
				'fb_id'    => $fb_user->id
    		]);

    		return $this->authAndRedirect($user);
    	}
    }

    public function authAndRedirect($user)
    {
        Auth::login($user);

        return redirect()->to('/home#');
    }


    public function getPageData(Facebook $fb)
    {
    	//echo "<pre />";
    	$data = $fb->get('/me?fields=id,name,email,accounts', Auth::user()->fb_token);
    	$node = $data->getGraphNode();
    	$pages = $node->getField('accounts');
    	$dataPages = [];

    	foreach ($pages as $key => $page) {
    		$dataPages[$key]['token'] = $page['access_token'];
    		$dataPages[$key]['name'] = $page['name'];
    		$dataPages[$key]['id'] = $page['id'];
    	}

		$response = $fb->get(
			"/{$dataPages[0]['id']}?fields=about,attire,bio,location,parking,hours,emails,website",
			"{$dataPages[0]['token']}"
		);
		$graphNode = $response->getGraphNode();

		//update
		$update = $fb->post(
			"/{$dataPages[0]['id']}",
			array (
			  'about' => 'This is an awesome cafe!'
			),
			"{$dataPages[0]['token']}"
		);
		//dd($update);
		//
		$res = $fb->get(
			"/{$dataPages[0]['id']}?fields=about,attire,bio,location,parking,hours,emails,website",
			"{$dataPages[0]['token']}"
		);
		$updateNode = $res->getGraphNode();
		dd('after update', $updateNode);
    }

}
