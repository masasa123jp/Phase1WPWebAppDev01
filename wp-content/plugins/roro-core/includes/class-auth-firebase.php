<?php
namespace RoroCore\Auth;
defined('ABSPATH') || exit;

use WP_REST_Controller;
use WP_REST_Request;
use wpdb;
use Firebase\JWT\JWK;                 // firebase/php-jwt >= 6.9 
use Firebase\JWT\JWT;
use Kreait\Firebase\Factory;          // kreait/firebase-php 
use Kreait\Firebase\Auth as FirebaseAuth;

final class Auth_Controller extends WP_REST_Controller {

	private wpdb $db;
	private FirebaseAuth $fb;

	public function __construct(wpdb $wpdb) {
		$this->db = $wpdb;
		$this->namespace = 'roro/v1';
		$this->rest_base = 'auth';
		$this->fb = (new Factory)
			->withServiceAccount(__DIR__.'/service-account.json')
			->createAuth();
	}

	public function register_routes() {
		/* Firebase providers */
		register_rest_route($this->namespace, "/{$this->rest_base}/firebase", [
			'methods'=>'POST',
			'callback'=>[$this,'firebase_login'],
			'permission_callback'=>'__return_true',
			'args'=>['idToken'=>['required'=>true]]
		]);
		/* LINE → custom token → signin */
		register_rest_route($this->namespace, "/{$this->rest_base}/line", [
			'methods'=>'POST',
			'callback'=>[$this,'line_login'],
			'permission_callback'=>'__return_true',
			'args'=>['accessToken'=>['required'=>true]]
		]);
	}

	/* ---------- Google/X/Facebook/MS/Yahoo flow ---------- */
	public function firebase_login(WP_REST_Request $r){
		try{$verified=$this->fb->verifyIdToken($r['idToken']);}
		catch(\Throwable $e){return new \WP_Error('bad_token',$e->getMessage(),['status'=>401]);}

		$uid  =$verified->claims()->get('sub');
		$mail =$verified->claims()->get('email');
		$idp  =$verified->claims()->get('firebase')['sign_in_provider']; // google.com etc.

		return $this->complete_login($uid,$idp,$mail,$verified->claims()->get('name',''));
	}

	/* ---------- LINE flow ---------- */
	public function line_login(WP_REST_Request $r){
		$token=$r['accessToken'];
		/* 1. verify with LINE API */
		$verify=json_decode(wp_remote_retrieve_body(
			wp_remote_get("https://api.line.me/oauth2/v2.1/verify?access_token=$token")),true);
		if(empty($verify['client_id'])){return new \WP_Error('line_fail','verify failed',['status'=>401]);}

		$user=json_decode(wp_remote_retrieve_body(
			wp_remote_get('https://api.line.me/v2/profile',[
				'headers'=>['Authorization'=>"Bearer $token"])),true);
		$uid='line:'.$user['userId'];               // ensure uniqueness
		$name=$user['displayName']; $mail=$uid.'@line.local'; // LINE doesn’t give email on JP consumer
		/* 2. mint custom token */
		$custom=$this->fb->createCustomToken($uid, ['provider'=>'line']);
		/* 3. send custom token back to front OR sign in here & continue*/
		$idToken=$this->fb->signInWithCustomToken($custom)->idToken(); // one round trip
		$verified=$this->fb->verifyIdToken($idToken);
		return $this->complete_login($uid,'line',$mail,$name);
	}

	/* ---------- shared logic ---------- */
	private function complete_login(string $uid,string $idp,string $email,string $name){
		$row=$this->db->get_row(
			$this->db->prepare("SELECT customer_id, wp_user_id FROM {$this->db->prefix}roro_identity WHERE uid=%s",$uid),
			ARRAY_A);
		if(!$row){
			$wpUser=get_user_by('email',$email);
			if(!$wpUser){
				$user_id=wp_create_user($email,wp_generate_password(),$email);
				wp_update_user(['ID'=>$user_id,'display_name'=>$name]);
			}else{$user_id=$wpUser->ID;}

			/* customer */
			$this->db->insert("{$this->db->prefix}roro_customer",[
				'name'=>$name,'email'=>$email,'breed_id'=>1],['%s','%s','%d']);
			$customer_id=(int)$this->db->insert_id;

			/* identity link */
			$this->db->insert("{$this->db->prefix}roro_identity",[
				'uid'=>$uid,'customer_id'=>$customer_id,'wp_user_id'=>$user_id,'idp'=>$idp],
				['%s','%d','%d','%s']);
		}else{
			$customer_id=(int)$row['customer_id'];
			$user_id=(int)$row['wp_user_id'];
		}
		wp_set_current_user($user_id);
		wp_set_auth_cookie($user_id,true);

		return rest_ensure_response([
			'ok'=>true,'customer_id'=>$customer_id,'wp_user_id'=>$user_id,'idp'=>$idp
		]);
	}
}
