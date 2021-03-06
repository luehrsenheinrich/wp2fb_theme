<?php

/**
 * lh_fb_function class.
 * Available after action wp, prio 9999
 */
class lh_fb_toolset {

	private $signed_request;
	public $fist_visit_in_page_tab = true;

	/**
	 * __construct function.
	 *
	 * @access public
	 * @param mixed $fb_appid
	 * @param mixed $fb_secret
	 * @return void
	 */
	public function __construct(){
		add_action("wp", array($this, "load_signed_request"), 10000);
	}

	/**
	 * get_signed_request function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_signed_request(){
		return $this->signed_request;
	}

	/**
	 * load_signed_request function.
	 *
	 * @access public
	 * @return void
	 */
	public function load_signed_request(){
		if(isset($_POST['signed_request'])){ // First visit of the user, retrive and store that stuff!
			$this->signed_request = $this->parse_signed_request($_POST['signed_request'], FB_APP_SECRET);
			if($this->signed_request){
				$this->first_visit_in_page_tab = true;
				setcookie('bp_signed_request', $_POST['signed_request']);
			}
		} elseif(isset($_COOKIE['bp_signed_request'])){
			$this->signed_request = $this->parse_signed_request($_COOKIE['bp_signed_request'], FB_APP_SECRET);
		} else {
			$this->signed_request = false;
		}
	}

	/**
	 * is_in_fb_page_tab function.
	 *
	 * @access public
	 * @return void
	 */
	public function is_in_fb_page_tab(){
		if(isset($this->signed_request['page'])){
			return true;
		} else {
			return false;
		}
	}

	/**
	 * is_facebook_crawler function.
	 *
	 * @access public
	 * @return void
	 */
	public function is_facebook_crawler(){
		if (in_array($_SERVER['HTTP_USER_AGENT'], array(
		  'facebookexternalhit/1.1 (+https://www.facebook.com/externalhit_uatext.php)',
		  'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)'
		))) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * parse_signed_request function.
	 *
	 * @access private
	 * @param mixed $signed_request
	 * @param mixed $secret
	 * @return void
	 */
	private function parse_signed_request($signed_request, $secret) {
		list($encoded_sig, $payload) = explode('.', $signed_request, 2);

		// decode the data
		$sig = $this->base64_url_decode($encoded_sig);
		$data = json_decode($this->base64_url_decode($payload), true);

		// confirm the signature
		$expected_sig = hash_hmac('sha256', $payload, $secret, $raw = true);
		if ($sig !== $expected_sig) {
			// var_dump('Bad Signed JSON signature!');
			return null;
		}

		return $data;
	}

	/**
	 * base64_url_decode function.
	 *
	 * @access private
	 * @param mixed $input
	 * @return void
	 */
	private function base64_url_decode($input) {
		return base64_decode(strtr($input, '-_', '+/'));
	}
}
$lh_fb_toolset = new lh_fb_toolset();

function is_in_fb_page_tab(){
	global $lh_fb_toolset;

	return $lh_fb_toolset->is_in_fb_page_tab();
}
