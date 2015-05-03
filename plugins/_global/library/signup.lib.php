<?php
/**
 * Send emails in various formats.
 * @author Aron Budinszky <aron@outlast.hu>
 * @version 3.0
 * @package Library
 *
 * API PHP File example
 * header('Access-Control-Allow-Origin: *');

// Webgalamb MySQL fájl meghívása (helyes útvonalat neked kell tudnod!)
require_once('./files/wg5conf.php');

// Webgalamb 5 API meghívása
require_once('wg5api.php');
$wg_api = new WG5_API($db_pre);

$name_key = utf8_encode('Teljes név');

// Rögzítendo adatok.
$subscriber_data = array (
// E-mail cím
'mail' => $_POST['email'],

// Feliratkozási dátum, ha nincs megadva akkor az aktuális dátum kerül be.
'datum' => date('Y-m-d'),

// Státusz: 1 -> aktív, 0 -> inaktív, 2 -> visszapattant
// Ha nincs megadva akkor aktív státusszal kerül be
'active' => 1,

$name_key => $_POST['name']
//'Telefonszám' => '06-70/123-4567',
//'Cégnév' => 'Minta Kft.'
);

// A feliratkozási csoport csoportazonosítója.
$group_id = $_POST['group'];


//
$result = $wg_api->InsertSubscriber($subscriber_data, $group_id);

// Eredmény kiírása, feldolgozása.
echo $result; 
 **/

class zajlib_signup extends zajLibExtension {

	/**
	 * @param string $email
	 * @param string $merge_vars array of merge vars
	 * @return string
	 */
    public function mailchimp($email, $merge_vars){
        $this->zajlib->config->load('signup.conf.ini');
        if(!$this->zajlib->email->valid($email)) return $this->zajlib->error('Invalid email address.',true);
        $signup = array();
        $api_dc = explode('-', $this->zajlib->config->variable->mailchimp_api_key);
        $signup['apikey'] = $this->zajlib->config->variable->mailchimp_api_key;
        $signup['id'] = $this->zajlib->config->variable->mailchimp_list_id;
        $signup['email']['email'] = $email;
		$signup['merge_vars'] = $merge_vars;
        $url = str_replace('%1',$api_dc[1],$this->zajlib->config->variable->mailchimp_subscribe_url);
        $result = $this->zajlib->request->curl($url, json_encode($signup), 'POST');
        return $result;
    }

    public function webgalamb($email, $name = '', $group = '1'){
        $this->zajlib->config->load('signup.conf.ini');
        if(!$this->zajlib->email->valid($email)) return $this->zajlib->error('Invalid email address.',true);
        $data = array('email'=>$email, 'name' => $name, 'group' => $group);
        $result = $this->zajlib->request->curl($this->zajlib->config->variable->webgalamb_url, $data, 'POST');
        return $result;
    }
}