<?php
/**
 * Send emails in various formats.
 * @author Aron Budinszky <aron@outlast.hu>
 * @version 3.0
 * @package Library
 **/

class zajlib_signup extends zajLibExtension {

    public function mailchimp($email){
        $this->zajlib->config->load('signup.conf.ini');
        if(!$this->zajlib->email->valid($email)) return $this->zajlib->error('Invalid email address.',true);
        $signup = array();
        $api_dc = explode('-', $this->zajlib->config->variable->mailchimp_api_key);
        $signup['apikey'] = $this->zajlib->config->variable->mailchimp_api_key;
        $signup['id'] = $this->zajlib->config->variable->mailchimp_list_id;
        $signup['email']['email'] = $email;
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