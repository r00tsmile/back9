<?php

require_once("Rest.inc.php");

	class API extends REST {

		public $data = "";
		const demo_version = false;

		private $db 	= NULL;
		private $mysqli = NULL;
		public function __construct() {
			// Init parent contructor
			parent::__construct();
			// Initiate Database connection
			$this->dbConnect();	
		}

		/*
		 *  Connect to Database
		*/
		private function dbConnect() {
			require_once ("../../includes/config.php");
			$this->mysqli = new mysqli($host, $user, $pass, $database);
			$this->mysqli->query('SET CHARACTER SET utf8');
		}

		public function processApi() {
			if(isset($_REQUEST['x']) && $_REQUEST['x']!=""){
				$func = strtolower(trim(str_replace("/","", $_REQUEST['x'])));
				if((int)method_exists($this,$func) > 0) {
					$this->$func();
				} else {
					header( 'Content-Type: application/json; charset=utf-8' );
					echo 'processApi - method not exist';
					exit;
				}
			} else {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo 'processApi - method not exist';
				exit;
			}
		}		

		/* Api Checker */
		public function check_connection() {
			if (mysqli_ping($this->mysqli)) {
                $respon = array(
                    'status' => 'ok', 'database' => 'connected'
                );
                $this->response($this->json($respon), 200);
			} else {
                $respon = array(
                    'status' => 'failed', 'database' => 'not connected'
                );
                $this->response($this->json($respon), 404);
			}
		}

		public function get_wallpapers() {

			if($this->get_request_method() != "GET") $this->response('',406);
			$limit = isset($this->_request['count']) ? ((int)$this->_request['count']) : 10;
			$page = isset($this->_request['page']) ? ((int)$this->_request['page']) : 1;

			$order = $_GET['order'];
			$filter = $_GET['filter'];

			$offset = ($page * $limit) - $limit;
			$count_total = $this->get_count_result("SELECT COUNT(DISTINCT g.id) FROM tbl_gallery g WHERE $filter $order");

			$query = "SELECT g.id AS 'image_id', g.image_name, g.image AS 'image_upload', g.image_url, g.type, g.image_resolution AS 'resolution', g.image_size AS 'size', g.image_extension AS 'mime', g.view_count AS 'views', g.download_count AS 'downloads', g.featured, g.tags, c.cid AS 'category_id', c.category_name, g.last_update FROM tbl_category c, tbl_gallery g WHERE c.cid = g.cat_id AND $filter $order LIMIT $limit OFFSET $offset";

			$post = $this->get_list_result($query);
			$count = count($post);
			$respon = array(
				'status' => 'ok', 'count' => $count, 'count_total' => $count_total, 'pages' => $page, 'posts' => $post
			);
			$this->response($this->json($respon), 200);

		}

		public function get_one_wallpaper() {

			if($this->get_request_method() != "GET") $this->response('',406);
			$id = $_GET['id'];
			$query = "SELECT g.id AS 'image_id', g.image_name, g.image AS 'image_upload', g.image_url, g.type, g.image_resolution AS 'resolution', g.image_size AS 'size', g.image_extension AS 'mime', g.view_count AS 'views', g.download_count AS 'downloads', g.featured, g.tags, c.cid AS 'category_id', c.category_name, g.last_update FROM tbl_category c, tbl_gallery g WHERE c.cid = g.cat_id AND g.id = $id";

			$wallpaper = $this->get_one_result($query);

			$respon = array(
				'status' => 'ok', 'wallpaper' => $wallpaper
			);
			$this->response($this->json($respon), 200);

		}

		public function get_categories() {

			include ("../../includes/config.php");

			if($this->get_request_method() != "GET") $this->response('',406);
			$setting_qry = "SELECT * FROM tbl_settings where id = '1'";
			$result = mysqli_query($connect, $setting_qry);
			$row    = mysqli_fetch_assoc($result);
			$sort   = $row['category_sort'];
			$order  = $row['category_order'];

			$query = "SELECT DISTINCT c.cid AS 'category_id', c.category_name, c.category_image, COUNT(DISTINCT g.id) as total_wallpaper
			FROM tbl_category c LEFT JOIN tbl_gallery g ON c.cid = g.cat_id GROUP BY c.cid ORDER BY $sort $order";

			$categories = $this->get_list_result($query);
			
			$count = count($categories);
			$respon = array(
				'status' => 'ok', 'count' => $count, 'categories' => $categories
			);
			$this->response($this->json($respon), 200);

		}		

		public function get_category_details() {

			if($this->get_request_method() != "GET") $this->response('',406);
			$limit = isset($this->_request['count']) ? ((int)$this->_request['count']) : 10;
			$page = isset($this->_request['page']) ? ((int)$this->_request['page']) : 1;

			$id = $_GET['id'];
			$order = $_GET['order'];
			$filter = $_GET['filter'];

			$offset = ($page * $limit) - $limit;
			$count_total = $this->get_count_result("SELECT COUNT(DISTINCT g.id) FROM tbl_gallery g WHERE g.cat_id = $id AND $filter $order");

			$query = "SELECT g.id AS 'image_id', g.image_name, g.image AS 'image_upload', g.image_url, g.type, g.image_resolution AS 'resolution', g.image_size AS 'size', g.image_extension AS 'mime', g.view_count AS 'views', g.download_count AS 'downloads', g.featured, g.tags, c.cid AS 'category_id', c.category_name, g.last_update FROM tbl_category c, tbl_gallery g WHERE c.cid = $id AND c.cid = g.cat_id AND $filter $order LIMIT $limit OFFSET $offset";

			$post = $this->get_list_result($query);
			$count = count($post);
			$respon = array(
				'status' => 'ok', 'count' => $count, 'count_total' => $count_total, 'pages' => $page, 'posts' => $post
			);
			$this->response($this->json($respon), 200);

		}

		public function get_search() {

			if($this->get_request_method() != "GET") $this->response('',406);
			$limit = isset($this->_request['count']) ? ((int)$this->_request['count']) : 10;
			$page = isset($this->_request['page']) ? ((int)$this->_request['page']) : 1;

			$search = $_GET['search'];
			$order = $_GET['order'];

			$offset = ($page * $limit) - $limit;
			$count_total = $this->get_count_result("SELECT COUNT(DISTINCT g.id) FROM tbl_gallery g, tbl_category c WHERE c.cid = g.cat_id AND (g.image_name LIKE '%$search%' OR g.tags LIKE '%$search%')");

			$query = "SELECT g.id AS 'image_id', g.image_name, g.image AS 'image_upload', g.image_url, g.type, g.image_resolution AS 'resolution', g.image_size AS 'size', g.image_extension AS 'mime', g.view_count AS 'views', g.download_count AS 'downloads', g.featured, g.tags, c.cid AS 'category_id', c.category_name, g.last_update FROM tbl_category c, tbl_gallery g WHERE c.cid = g.cat_id AND (g.image_name LIKE '%$search%' OR g.tags LIKE '%$search%') $order LIMIT $limit OFFSET $offset";

			$post = $this->get_list_result($query);
			$count = count($post);
			$respon = array(
				'status' => 'ok', 'count' => $count, 'count_total' => $count_total, 'pages' => $page, 'posts' => $post
			);
			$this->response($this->json($respon), 200);

		}

		public function get_search_category() {

			include ("../../includes/config.php");

			if($this->get_request_method() != "GET") $this->response('',406);

			$search = $_GET['search'];

			$query = "SELECT DISTINCT c.cid AS 'category_id', c.category_name, c.category_image, COUNT(DISTINCT g.id) as total_wallpaper
			FROM tbl_category c LEFT JOIN tbl_gallery g ON c.cid = g.cat_id WHERE c.category_name LIKE '%$search%' GROUP BY c.cid ORDER BY c.cid DESC";

			$post = $this->get_list_result($query);
			$count = count($post);
			$respon = array(
				'status' => 'ok', 'count' => $count, 'categories' => $post
			);
			$this->response($this->json($respon), 200);			
		}

		public function update_view() {

			include ("../../includes/config.php");

			$image_id = $_POST['image_id'];

			$sql = "UPDATE tbl_gallery SET view_count = view_count + 1 WHERE id = '$image_id'";
			
			if (mysqli_query($connect, $sql)) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode(array('response' => "View updated"));
			} else {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode(array('response' => "Failed"));
			}
			mysqli_close($connect);

		}
		
		public function update_download() {

			include ("../../includes/config.php");

			$image_id = $_POST['image_id'];

			$sql = "UPDATE tbl_gallery SET download_count = download_count + 1 WHERE id = '$image_id'";
			
			if (mysqli_query($connect, $sql)) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode(array('response' => "Download updated"));
			} else {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode(array('response' => "Failed"));
			}
			mysqli_close($connect);

		}

		public function get_ads() {
			
			if($this->get_request_method() != "GET") $this->response('',406);

			$sql_ads = "SELECT * FROM tbl_ads";
			$sql_ads_status = "SELECT * FROM tbl_ads_status";

			$ads = $this->get_one_result($sql_ads);
			$ads_status = $this->get_one_result($sql_ads_status);

			$respon = array(
				'status' => 'ok',
			    'ads'=> array (
			        'id' => '1',
			        'ad_status' => 'on',
			        'ad_type' => 'startapp',
			        'backup_ads' => 'none',
			        'admob_publisher_id' => 'pub-6858304905719677',
			        'admob_app_id' => 'ca-app-pub-3940256099942544~3347511713',
			        'admob_banner_unit_id' => 'ca-app-pub-6858304905719677/8946446605',
			        'admob_interstitial_unit_id' => 'ca-app-pub-6858304905719677/9026337659',
			        'admob_native_unit_id' => 'ca-app-pub-6858304905719677',
			        'admob_app_open_ad_unit_id' => 'ca-app-pub-39402',
			        'fan_banner_unit_id' => '816848046188888_816850719521954',
			        'fan_interstitial_unit_id' => '816848046188888_816850776188615',
			        'fan_native_unit_id' => '816848046188888_816850862855273',
			        'startapp_app_id' => '207102749',
			        'unity_game_id' => '4423290',
			        'unity_banner_placement_id' => 'anime_ads_banner',
			        'unity_interstitial_placement_id' => 'anime_ads',
			        'applovin_banner_ad_unit_id' => '0',
			        'applovin_interstitial_ad_unit_id' => '0',
			        'applovin_native_ad_manual_unit_id' => '0',
			        'applovin_banner_zone_id' => '0',
			        'applovin_interstitial_zone_id' => '0',
			        'ad_manager_banner_unit_id' => '/6499/example/banner',
			        'ad_manager_interstitial_unit_id' => '/6499/example/interstitial',
			        'ad_manager_native_unit_id' => '/6499/example/native',
			        'ad_manager_app_open_ad_unit_id' => '/6499/example/app-open',
			        'ironsource_app_key' => '85460dcd',
			        'ironsource_banner_placement_name' => 'DefaultBanner',
			        'ironsource_interstitial_placement_name' => 'DefaultInterstitial',
			        'mopub_banner_ad_unit_id' => 'b195f8dd8ded45fe847ad89ed1d016da',
			        'mopub_interstitial_ad_unit_id' => '24534e1901884e398f1253216226017e',
			        'interstitial_ad_interval' => '1',
			        'native_ad_interval' => '13',
			        'native_ad_index' => '6',
			        'last_update_ads' => '2022-11-04 04:02:13'
			    ),
			    'ads_status' => array (
			        'ads_status_id' => '1',
			        'banner_ad_on_home_page' => '1',
			        'banner_ad_on_search_page' => '1',
			        'banner_ad_on_wallpaper_detail' => '1',
			        'banner_ad_on_wallpaper_by_category' => '1',
			        'interstitial_ad_on_click_wallpaper' => '1',
			        'interstitial_ad_on_wallpaper_detail' => '1',
			        'native_ad_on_wallpaper_list' => '1',
			        'native_ad_on_exit_dialog' => '1',
			        'app_open_ad' => '0',
			        'last_update_ads_status' => '2022-10-10 12:29:13'
			    )
			);
			$this->response($this->json($respon), 200);	
			
			
		}

		public function get_settings() {
			
			if($this->get_request_method() != "GET") $this->response('',406);

			$sql_settings = "SELECT * FROM tbl_settings";
			$sql_ads = "SELECT * FROM tbl_ads";
			$sql_ads_status = "SELECT * FROM tbl_ads_status";

			$settings = $this->get_one_result($sql_settings);
			$ads = $this->get_one_result($sql_ads);
			$ads_status = $this->get_one_result($sql_ads_status);

			$respon = array(
				'status' => 'ok',
			    'app' => array(
			        'package_name' => '',
			        'status' => '',
			        'redirect_url' => ''
			    ),
			    'settings' => array(
			        'id' => '1',
			        'limit_recent_wallpaper' => '30',
			        'category_sort' => 'category_name',
			        'category_order' => 'ASC',
			        'onesignal_app_id' => '7a541c69-b3de-4bc2-94a7-05d1d65f1ef9',
			        'onesignal_rest_api_key' => 'AAAAaaP74RU:APA91bGuA7gP-CJ24TW0DOW6YnfKxZL_uQ3dhiJeUAr_gGp0ozcflPomQ3tqO1sH6j22jX1H6JKutjidp1elP2IwglNfSM63iOQrfKIe-lQkwgmEc9R-B7MHZvplMlN5ZfW2yaLWIEd2',
			        'providers' => 'firebase',
			        'protocol_type' => '',
			        'privacy_policy' => '<p>Solodroid built the Material Wallpaper app as a Free app. This SERVICE is provided by Solodroid at no cost and is intended for use as is.</p>\r\n\r\n<p>This page is used to inform visitors regarding our policies with the collection, use, and disclosure of Personal Information if anyone decided to use our Service.</p>\r\n\r\n<p>If you choose to use our Service, then you agree to the collection and use of information in relation to this policy. The Personal Information that we collect is used for providing and improving the Service. We will not use or share your information with anyone except as described in this Privacy Policy.</p>\r\n\r\n<p>The terms used in this Privacy Policy have the same meanings as in our Terms and Conditions, which is accessible at Material Wallpaper unless otherwise defined in this Privacy Policy.</p>\r\n\r\n<p><strong>Information Collection and Use</strong></p>\r\n\r\n<p>For a better experience, while using our Service, we may require you to provide us with certain personally identifiable information. The information that we request will be retained by us and used as described in this privacy policy.</p>\r\n\r\n<p>The app does use third party services that may collect information used to identify you.</p>\r\n\r\n<p>Link to privacy policy of third party service providers used by the app</p>\r\n\r\n<ul>\r\n\t<li><a href=\"https://www.google.com/policies/privacy/\" target=\"_blank\">Google Play Services</a></li>\r\n\t<li><a href=\"https://support.google.com/admob/answer/6128543?hl=en\" target=\"_blank\">AdMob</a></li>\r\n\t<li><a href=\"https://firebase.google.com/policies/analytics\" target=\"_blank\">Google Analytics for Firebase</a></li>\r\n\t<li><a href=\"https://www.facebook.com/about/privacy/update/printable\" target=\"_blank\">Facebook</a></li>\r\n\t<li><a href=\"https://unity3d.com/legal/privacy-policy\" target=\"_blank\">Unity</a></li>\r\n\t<li><a href=\"https://onesignal.com/privacy_policy\" target=\"_blank\">One Signal</a></li>\r\n\t<li><a href=\"https://www.applovin.com/privacy/\" target=\"_blank\">AppLovin</a></li>\r\n\t<li><a href=\"https://www.startapp.com/privacy/\" target=\"_blank\">StartApp</a></li>\r\n</ul>\r\n\r\n<p><strong>Log Data</strong></p>\r\n\r\n<p>We want to inform you that whenever you use our Service, in a case of an error in the app we collect data and information (through third party products) on your phone called Log Data. This Log Data may include information such as your device Internet Protocol (&ldquo;IP&rdquo;) address, device name, operating system version, the configuration of the app when utilizing our Service, the time and date of your use of the Service, and other statistics.</p>\r\n\r\n<p><strong>Cookies</strong></p>\r\n\r\n<p>Cookies are files with a small amount of data that are commonly used as anonymous unique identifiers. These are sent to your browser from the websites that you visit and are stored on your device&#39;s internal memory.</p>\r\n\r\n<p>This Service does not use these &ldquo;cookies&rdquo; explicitly. However, the app may use third party code and libraries that use &ldquo;cookies&rdquo; to collect information and improve their services. You have the option to either accept or refuse these cookies and know when a cookie is being sent to your device. If you choose to refuse our cookies, you may not be able to use some portions of this Service.</p>\r\n\r\n<p><strong>Service Providers</strong></p>\r\n\r\n<p>We may employ third-party companies and individuals due to the following reasons:</p>\r\n\r\n<ul>\r\n\t<li>To facilitate our Service;</li>\r\n\t<li>To provide the Service on our behalf;</li>\r\n\t<li>To perform Service-related services; or</li>\r\n\t<li>To assist us in analyzing how our Service is used.</li>\r\n</ul>\r\n\r\n<p>We want to inform users of this Service that these third parties have access to your Personal Information. The reason is to perform the tasks assigned to them on our behalf. However, they are obligated not to disclose or use the information for any other purpose.</p>\r\n\r\n<p><strong>Security</strong></p>\r\n\r\n<p>We value your trust in providing us your Personal Information, thus we are striving to use commercially acceptable means of protecting it. But remember that no method of transmission over the internet, or method of electronic storage is 100% secure and reliable, and we cannot guarantee its absolute security.</p>\r\n\r\n<p><strong>Links to Other Sites</strong></p>\r\n\r\n<p>This Service may contain links to other sites. If you click on a third-party link, you will be directed to that site. Note that these external sites are not operated by us. Therefore, we strongly advise you to review the Privacy Policy of these websites. We have no control over and assume no responsibility for the content, privacy policies, or practices of any third-party sites or services.</p>\r\n\r\n<p><strong>Children&rsquo;s Privacy</strong></p>\r\n\r\n<p>These Services do not address anyone under the age of 13. We do not knowingly collect personally identifiable information from children under 13 years of age. In the case we discover that a child under 13 has provided us with personal information, we immediately delete this from our servers. If you are a parent or guardian and you are aware that your child has provided us with personal information, please contact us so that we will be able to do necessary actions.</p>\r\n\r\n<p><strong>Changes to This Privacy Policy</strong></p>\r\n\r\n<p>We may update our Privacy Policy from time to time. Thus, you are advised to review this page periodically for any changes. We will notify you of any changes by posting the new Privacy Policy on this page.</p>\r\n\r\n<p>This policy is effective as of 2021-09-29</p>\r\n\r\n<p><strong>Contact Us</strong></p>\r\n\r\n<p>If you have any questions or suggestions about our Privacy Policy, do not hesitate to contact us at help.solodroid@gmail.com.</p>\r\n',
			        'package_name' => 'com.anime.yumiwallpaper',
			        'fcm_server_key' => 'AAAAaaP74RU:APA91bH8m9h2gQOv4UPNRUec6uRQDCIZ3hkZWal28JLq-8eMj_7F-Zw_9FFuyRyCSrXf7U85jBwWVEwux-9HrUICTsUyE0JKRnIdT48CrZ_zy-Oi7X_P33QlSCVe7h0pFCA4tzDhN4zq',
			        'fcm_notification_topic' => 'material_wallpaper_topic',
			        'more_apps_url' => 'https://play.google.com/store/apps/developer?id=wallpapers+yumi+yumi'
			    ),
			    'ads' => array(
			        'id' => '1',
			        'ad_status' => 'on',
			        'ad_type' => 'startapp',
			        'backup_ads' => 'none',
			        'admob_publisher_id' => 'pub-6858304905719677',
			        'admob_app_id' => 'ca-app-pub-3940256099942544~3347511713',
			        'admob_banner_unit_id' => 'ca-app-pub-6858304905719677/8946446605',
			        'admob_interstitial_unit_id' => 'ca-app-pub-6858304905719677/9026337659',
			        'admob_native_unit_id' => 'ca-app-pub-6858304905719677',
			        'admob_app_open_ad_unit_id' => 'ca-app-pub-39402',
			        'fan_banner_unit_id' => '816848046188888_816850719521954',
			        'fan_interstitial_unit_id' => '816848046188888_816850776188615',
			        'fan_native_unit_id' => '816848046188888_816850862855273',
			        'startapp_app_id' => '207102749',
			        'unity_game_id' => '4423290',
			        'unity_banner_placement_id' => 'anime_ads_banner',
			        'unity_interstitial_placement_id' => 'anime_ads',
			        'applovin_banner_ad_unit_id' => '0',
			        'applovin_interstitial_ad_unit_id' => '0',
			        'applovin_native_ad_manual_unit_id' => '0',
			        'applovin_banner_zone_id' => '0',
			        'applovin_interstitial_zone_id' => '0',
			        'ad_manager_banner_unit_id' => '/6499/example/banner',
			        'ad_manager_interstitial_unit_id' => '/6499/example/interstitial',
			        'ad_manager_native_unit_id' => '/6499/example/native',
			        'ad_manager_app_open_ad_unit_id' => '/6499/example/app-open',
			        'ironsource_app_key' => '85460dcd',
			        'ironsource_banner_placement_name' => 'DefaultBanner',
			        'ironsource_interstitial_placement_name' => 'DefaultInterstitial',
			        'mopub_banner_ad_unit_id' => 'b195f8dd8ded45fe847ad89ed1d016da',
			        'mopub_interstitial_ad_unit_id' => '24534e1901884e398f1253216226017e',
			        'interstitial_ad_interval' => '1',
			        'native_ad_interval' => '13',
			        'native_ad_index' => '6',
			        'last_update_ads' => '2022-11-04 04:02:13'
			    ),
			    'ads_status' => array(
			        'ads_status_id' => '1',
			        'banner_ad_on_home_page' => '1',
			        'banner_ad_on_search_page' => '1',
			        'banner_ad_on_wallpaper_detail' => '1',
			        'banner_ad_on_wallpaper_by_category' => '1',
			        'interstitial_ad_on_click_wallpaper' => '1',
			        'interstitial_ad_on_wallpaper_detail' => '1',
			        'native_ad_on_wallpaper_list' => '1',
			        'native_ad_on_exit_dialog' => '1',
			        'app_open_ad' => '0',
			        'last_update_ads_status' => '2022-10-10 12:29:13'
			    )
			);
			$this->response($this->json($respon), 200);	
			
			
			
		}

		 /*
		 * ======================================================================================================
		 * =============================== API utilities # DO NOT EDIT ==========================================
		 */

		private function get_list($query) {
			$r = $this->mysqli->query($query) or die($this->mysqli->errog.__LINE__);
			if($r->num_rows > 0) {
				$result = array();
				while($row = $r->fetch_assoc()) {
					$result[] = $row;
				}
				$this->response($this->json($result), 200); // send user details
			}
			$this->response('',204);	// If no records "No Content" status
		}
		
		private function get_list_result($query) {
			$result = array();
			$r = $this->mysqli->query($query) or die($this->mysqli->errog.__LINE__);
			if($r->num_rows > 0) {
				while($row = $r->fetch_assoc()) {
					$result[] = $row;
				}
			}
			return $result;
		}

		private function get_category_result($query) {
			$result = array();
			$r = $this->mysqli->query($query) or die($this->mysqli->errog.__LINE__);
			if($r->num_rows > 0) {
				while($row = $r->fetch_assoc()) {
					$result = $row;
				}
			}
			return $result;
		}

		private function get_one_result($query) {
			$result = array();
			$r = $this->mysqli->query($query) or die($this->mysqli->errog.__LINE__);
			if($r->num_rows > 0) $result = $r->fetch_assoc();
				return $result;
		}

		private function get_one($query) {
			$r = $this->mysqli->query($query) or die($this->mysqli->errog.__LINE__);
			if($r->num_rows > 0) {
				$result = $r->fetch_assoc();
				$this->response($this->json($result), 200); // send user details
			}
			$this->response('',204);	// If no records "No Content" status
		}

		private function get_one_detail($query) {
			$result = array();
			$r = $this->mysqli->query($query) or die($this->mysqli->errog.__LINE__);
			if($r->num_rows > 0) $result = $r->fetch_assoc();
			return $result;
		}		
		
		private function get_count($query) {
			$r = $this->mysqli->query($query) or die($this->mysqli->errog.__LINE__);
			if($r->num_rows > 0) {
				$result = $r->fetch_row();
				$this->response($result[0], 200); 
			}
			$this->response('',204);	// If no records "No Content" status
		}
		
		private function get_count_result($query) {
			$r = $this->mysqli->query($query) or die($this->mysqli->errog.__LINE__);
			if($r->num_rows > 0) {
				$result = $r->fetch_row();
				return $result[0];
			}
			return 0;
		}
		
		private function post_one($obj, $column_names, $table_name) {
			$keys 		= array_keys($obj);
			$columns 	= '';
			$values 	= '';
			foreach($column_names as $desired_key) { // Check the recipe received. If blank insert blank into the array.
			  if(!in_array($desired_key, $keys)) {
			   	$$desired_key = '';
				} else {
					$$desired_key = $obj[$desired_key];
				}
				$columns 	= $columns.$desired_key.',';
				$values 	= $values."'".$this->real_escape($$desired_key)."',";
			}
			$query = "INSERT INTO ".$table_name."(".trim($columns,',').") VALUES(".trim($values,',').")";
			//echo "QUERY : ".$query;
			if(!empty($obj)) {
				//$r = $this->mysqli->query($query) or trigger_error($this->mysqli->errog.__LINE__);
				if ($this->mysqli->query($query)) {
					$status = "success";
			    $msg 		= $table_name." created successfully";
				} else {
					$status = "failed";
			    $msg 		= $this->mysqli->errog.__LINE__;
				}
				$resp = array('status' => $status, "msg" => $msg, "data" => $obj);
				$this->response($this->json($resp),200);
			} else {
				$this->response('',204);	//"No Content" status
			}
		}

		private function post_update($id, $obj, $column_names, $table_name) {
			$keys = array_keys($obj[$table_name]);
			$columns = '';
			$values = '';
			foreach($column_names as $desired_key){ // Check the recipe received. If key does not exist, insert blank into the array.
			  if(!in_array($desired_key, $keys)) {
			   	$$desired_key = '';
				} else {
					$$desired_key = $obj[$table_name][$desired_key];
				}
				$columns = $columns.$desired_key."='".$this->real_escape($$desired_key)."',";
			}

			$query = "UPDATE ".$table_name." SET ".trim($columns,',')." WHERE id=$id";
			if(!empty($obj)) {
				// $r = $this->mysqli->query($query) or die($this->mysqli->errog.__LINE__);
				if ($this->mysqli->query($query)) {
					$status = "success";
					$msg 	= $table_name." update successfully";
				} else {
					$status = "failed";
					$msg 	= $this->mysqli->errog.__LINE__;
				}
				$resp = array('status' => $status, "msg" => $msg, "data" => $obj);
				$this->response($this->json($resp),200);
			} else {
				$this->response('',204);	// "No Content" status
			}
		}

		private function delete_one($id, $table_name) {
			if($id > 0) {
				$query="DELETE FROM ".$table_name." WHERE id = $id";
				if ($this->mysqli->query($query)) {
					$status = "success";
			    $msg 		= "One record " .$table_name." successfully deleted";
				} else {
					$status = "failed";
			    $msg 		= $this->mysqli->errog.__LINE__;
				}
				$resp = array('status' => $status, "msg" => $msg);
				$this->response($this->json($resp),200);
			} else {
				$this->response('',204);	// If no records "No Content" status
			}
		}
		
		private function responseInvalidParam() {
			$resp = array("status" => 'Failed', "msg" => 'Invalid Parameter' );
			$this->response($this->json($resp), 200);
		}

		/* ==================================== End of API utilities ==========================================
		 * ====================================================================================================
		 */

		/* Encode array into JSON */
		private function json($data) {
			if(is_array($data)) {
				// return json_encode($data, JSON_NUMERIC_CHECK);
				return json_encode($data);
			}
		}

		/* String mysqli_real_escape_string */
		private function real_escape($s) {
			return mysqli_real_escape_string($this->mysqli, $s);
		}
	}

	// Initiiate Library

	$api = new API;
	if (isset($_GET['get_wallpapers'])) {
		$api->get_wallpapers();
	} else if (isset($_GET['get_one_wallpaper'])) {
		$api->get_one_wallpaper();
	} else if (isset($_GET['get_categories'])) {
		$api->get_categories();
	} else if (isset($_GET['get_category_details'])) {
		$api->get_category_details();
	} else if (isset($_GET['get_search'])) {
		$api->get_search();
	} else if (isset($_GET['get_search_category'])) {
		$api->get_search_category();
	} else if (isset($_GET['update_view'])) {
		$api->update_view();
	} else if (isset($_GET['update_download'])) {
		$api->update_download();
	} else if (isset($_GET['get_ads'])) {
		$api->get_ads();
	} else if (isset($_GET['get_settings'])) {
		$api->get_settings();
	} else {
		$api->processApi();
	}
	
?>
