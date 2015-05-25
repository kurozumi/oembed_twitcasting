<?php
/*
Plugin Name: oEmbed TwitCasting
Version: 0.1-alpha
Description: PLUGIN DESCRIPTION HERE
Author: kurozumi
Author URI: http://a-zumi.net
Plugin URI: http://a-zumi.net
Text Domain: oembed-twitcasting
Domain Path: /languages
*/

$oembed_twitcasting = new oEmbed_TwitCasting();
$oembed_twitcasting->register();

class oEmbed_TwitCasting
{
	private $options;
	
	private $option_name = "oembed_twitcasting";
	
	private $option_group = "oembed_twitcasting";
	
	private $menu_slug = "oembed_twitcasting";
	
	private $section_id = "oembed_twitcasting_section_id";
	
	private $width = 640;
	
	function register()
	{
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}
	
	public function plugins_loaded()
	{
		add_action('admin_menu', array($this, 'admin_menu'));
		
		add_action('admin_init', array($this, 'admin_init'));
		
		wp_embed_register_handler(
			'twicasting',
			'#http://twitcasting.tv/(.*)$#i',
			array( $this, 'handler' )
		);
		
	}
	
	public function handler($m, $attr, $url, $rawattr)
	{
		$this->options = get_option($this->option_name);
		
		$width  = isset($this->options['width']) ? $this->options['width'] : $this->width;
		$height = $width/16*10;
		
		return <<< __EOS__
			<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=10,0,0,0" width="{$width}" height="{$height}" id="livestreamer" align="middle">
			<param name="allowScriptAccess" value="always" />
			<param name="allowFullScreen" value="true" />
			<param name="flashVars" value="user={$m[1]}&lang=ja&mute=0&cupdate=0&offline=" />
			<param name="movie" value="http://twitcasting.tv/swf/livestreamer2sp.swf" />
			<param name="quality" value="high" />
			<param name="bgcolor" value="#ffffff" />
			<embed src="http://twitcasting.tv/swf/livestreamer2sp.swf" quality="high" bgcolor="#ffffff" width="{$width}" height="{$height}" name="livestreamer" id="livestreamderembed" align="middle" allowScriptAccess="always" allowFullScreen="true" type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer" flashVars="user={$m[1]}&lang=ja&mute=0&cupdate=0&offline=" >
			</object>
__EOS__;
	}
	
	public function admin_menu()
	{
		/**
		 * Add a top level menu page
		 * 
		 * @param string $page_title 設定ページのtitle
		 * @param string $menu_title メニュー名
		 * @param string $capability 権限
		 * @param string $menu_slug メニューのslug
		 * @param callback $function 設定ページの出力を行う関数
		 * @param string $icon_url メニューに表示するアイコン
		 * @param int $position メニューの位置
		 *
		 * @return string The resulting page's hook_suffix
		 */
		add_menu_page( 'oEmbed Twicasting', 'oEmbed Twicasting', 'manage_options', $this->menu_slug, array( $this, 'admin_page' ) );
	}
	
	public function admin_init()
	{
		/**
		 * Register a setting and its sanitization callback
		 * 
		 * @param string $option_group 設定のグループ名
		 * @param string $option_name 設定項目名(DBに保存するオプション名)
		 * @param callable $sanitize_callback 入力値のサニタイズを行う際に呼ばれる関数
		 */
		register_setting($this->option_group, $this->option_name, array( $this, 'sanitize' ) );
		
		/**
		 * Add a new section to a settings page.
		 *
		 * @param string $id セクションID
		 * @param string $title セクション名
		 * @param string $callback セクションの説明などを出力するための関数
		 * @param string $page 設定ページのslug ※add_menu_page()の$menu_slugと同じものにする
		 */
		add_settings_section( $this->section_id, '', '', $this->menu_slug );
		
		/**
		 * Add a new field to a section of a settings page
		 *
		 * @param string $id 入力項目ID
		 * @param string $title 入力項目名
		 * @param string $callback 入力項目のHTMLを出力する関数
		 * @param string $page 設定ページのslug ※addd_menu_page()の$menu_slugと同じものにする
		 * @param string $section セクションID add_settings_section()の$idと同じものにする
		 * @param array  $args $callbackの追加引数
		 */
		add_settings_field('width', __('Width'), array($this, 'input_callback'), $this->menu_slug, $this->section_id, array('name' => 'width'));
	}
	
	public function admin_page()
	{
		$this->options = get_option($this->option_name);
		?>
<div class="wrap">
	<h2>oEmbed TwitCasting設定</h2>
	<?php
	global $parent_file;
	if($parent_file != 'options-general.php')
		require(ABSPATH . 'wp-admin/options-head.php');
	?>
	<form method="post" action="options.php">
	<?php
		// 隠しフィールド出力
		settings_fields($this->option_group);
		// 入力項目出力
		do_settings_sections($this->menu_slug);
		// 送信ボタン出力
		submit_button();
	?>
	</from>
</div>
		<?php
	}
	
	public function input_callback($args)
	{
		$value = isset( $this->options[$args['name']] ) ? $this->options[$args['name']] : '';
		?>
		<input type="text" id="<?php echo  $args['name'];?>" name="<?php printf("%s[%s]", $this->option_name, $args['name']);?>" value="<?php esc_attr_e($value) ?>" />
		<?php
	}
	
	public function sanitize($input)
	{
		global $wp_settings_fields;
		
		$section = $wp_settings_fields[$this->menu_slug][$this->section_id];
		
		$this->options = get_option($this->option_name);
				
		$new_input = array();
		
		foreach($input as $k => $v)
		{
			if(trim($v) != '') {
				$new_input[$k] = sanitize_text_field($v);
			}else{

				/**
				 * Register a settings error to be displayed to the user
				 *
				 * @param string $setting 設定ページのslug ※addd_menu_page()の$menu_slugと同じものにする
				 * @param string $code    エラーコードのslug
				 * @param string $message エラーメッセージの内容
				 * @param string $type    メッセージのタイプ。'updated' (成功) か 'error' (エラー) のどちらか
				 */
				add_settings_error($this->menu_slug, $k, sprintf('%sを入力して下さい。', $section[$k]['title']));

				$new_input[$k] = isset($this->options[$k]) ? $this->options[$k] : '';
			}
		}
		
		return $new_input;
	}
}