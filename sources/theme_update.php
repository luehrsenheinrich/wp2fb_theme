<?php
/**
 * File that handles the auto update function of this theme.
 * The theme Files reside in the github repo, on new version it gets updated from there
 */
 
class lh_theme_updater {
	
	var $theme_repo_user, $theme_repo_name, $theme_repo_branch;
	
	
	
	/**
	 * Constructing the class
	 */
	public function __construct($user, $repo, $branch){
		$this->theme_repo_user = $user;
		$this->theme_repo_name = $repo;
		$this->theme_repo_branch = $branch;
	
		add_filter('pre_set_site_transient_update_themes', array($this, "theme_update"));
		
		// Uncomment only in dev!
		// set_site_transient('update_themes', null);
	}
	
	
	
	/**
	 * Look into the theme github repo and check, if the version number in style.css is higher than the current version number
	 */
	public function theme_update($checked_data) {
		global $wp_version;
		
		if($checked_data == NULL){
			return;
		}
		
	    $theme_data = wp_get_theme();
	    $theme_base = get_option('template');
		
		$api_url = "https://api.github.com/repos/".$this->theme_repo_user."/".$this->theme_repo_name."/contents/style.css?ref=".$this->theme_repo_branch;
		$raw_response = wp_remote_get($api_url);
		
		if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200)){
			$response = json_decode($raw_response['body']);
			$style_content = base64_decode($response->content);
			$repo_theme_data = $this->parse_header_data($style_content);
		}
	
		// Feed the update data into WP updater
		if (!empty($repo_theme_data) && $repo_theme_data['Version'] > $theme_data->get("Version")) {
			// We have to build the checked data
			$response = array(
					"package" 		=> "https://github.com/".$this->theme_repo_user."/".$this->theme_repo_name."/archive/".$this->theme_repo_branch.".zip",
					"new_version"	=> $repo_theme_data['Version'],
					"url"			=> "https://github.com/".$this->theme_repo_user."/".$this->theme_repo_name."/",
			);
			
			$checked_data->response[$theme_base] = $response;
		}
		
	
		return $checked_data;
	}	
	
	
	
	/**
	 * Parse the header information from the style.css and return the retrived values
	 */
	private function parse_header_data($style){
	
	    $file_data = str_replace( "\r", "\n", $style );

        $all_headers = array(
			'Name'        => 'Theme Name',
			'ThemeURI'    => 'Theme URI',
			'Description' => 'Description',
			'Author'      => 'Author',
			'AuthorURI'   => 'Author URI',
			'Version'     => 'Version',
			'Template'    => 'Template',
			'Status'      => 'Status',
			'Tags'        => 'Tags',
			'TextDomain'  => 'Text Domain',
			'DomainPath'  => 'Domain Path',
		);
	

        foreach ( $all_headers as $field => $regex ) {
				if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $file_data, $match ) && $match[1] )
                        $all_headers[ $field ] = _cleanup_header_comment( $match[1] );
                else
                        $all_headers[ $field ] = '';
        }
        
        return $all_headers;
	}
}

$lh_theme_updater = new lh_theme_updater("luehrsenheinrich", "bookpress", "stable");
