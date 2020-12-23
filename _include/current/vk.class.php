<?php

class vk
{
    private static $instance;
    private static $nameSocial='vk';

    private function getUrlAuth()
    {
      return 'social_login.php?module='.self::$nameSocial;
    }

    private function getUrlToken()
    {
        return  '';
    }

    public function getParamsAuth()
    {
        $client_id = Common::getOption(self::$nameSocial.'_appid');
        $client_secret = Common::getOption(self::$nameSocial.'_secret');

        $params = array(
            'response_type' => 'code',
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'scope' => 'email'
        );

        return $params;
    }

    public function getUserInfo($code)
    {
        $result = false;
        $userInfo=false;

        $userInfo=get_session(self::$nameSocial.'_user_info',false);

        return $userInfo;
    }


    public static function getInstance()
    {

        if(Common::isAppAndroid()) {
            return false;
        }

        global $g;
        if (isset($g['options'][self::$nameSocial.'_appid'])
            && isset($g['options'][self::$nameSocial.'_secret'])
            && $g['options'][self::$nameSocial.'_appid'] != ''
            && $g['options'][self::$nameSocial.'_secret'] != ''
        ) {

            if(self::$instance === null){
                // Create our Application instance (replace this with your appId and secret)
                self::$instance = new self(array(
                    'appId' => $g['options'][self::$nameSocial.'_appid'],
                    'secret' => $g['options'][self::$nameSocial.'_secret'],
                    'cookie' => true,
                ));

            }
            return  self::$instance;
        } else {
            return false;
        }
    }

    public function parse()
    {

    }

    public function getUserId()
    {
        $userInfo = get_session(self::$nameSocial.'_user_info');

        if(isset($userInfo['id'])){
            return $userInfo['id'];
        } else {
            return false;
        }
    }

    public function loginRedirectUrl()
    {
        $url = '';
        $url = $this->getUrlAuth();
        return $url;
    }

    public function setJoinInfo()
    {
        global $g;


        $me = get_session(self::$nameSocial.'_user_info');

        set_session(self::$nameSocial.'_id', 0);
        set_session(self::$nameSocial.'_photo', false);
        set_session('social_id', 0);
        set_session('social_photo', false);

        // check if already registered
        if ($me) {

            if (isset($me['email'])) {
                if (get_param('email') == '') {
                    $_GET['email'] = $me['email'];
                    $_GET['verify_email'] = $me['email'];
                }
            }

            if(!isset($me['first_name'])){
                $me['first_name']='';
            }

            if(!isset($me['last_name'])){
                $me['last_name']='';
            }

            if (isset($me['first_name'])) {
                if (get_param('join_handle') == '') {
                    $_GET['join_handle'] = implode(' ',array($me['first_name'],$me['last_name']));
                }
            }

                if (isset($me['bdate']) && $me['bdate']) {
                    $birthDate = explode('.', $me['bdate']);

                    if (is_array($birthDate) && count($birthDate)) {
                        if (get_param('month') == '' && isset($birthDate[1])) {
                            $_GET['month'] = $birthDate[1];
                        }
                        if (get_param('day') == '' && isset($birthDate[0])) {
                            $_GET['day'] = $birthDate[0];
                        }
                        if (get_param('year') == '' && isset($birthDate[2])) {
                            $_GET['year'] = $birthDate[2];
                        }
                    }
                }


            set_session(self::$nameSocial.'_id', $me['id']);
            set_session('social_id', $me['id']);
            set_session('social_type', self::$nameSocial);
            // set picture if exists
            if(isset($me['photo_big']) && strpos($me['photo_big'],'camera_200.png')===false) {
                set_session('social_photo', $me['photo_big']);
            } else {
                set_session('social_photo', '');
            }

                    if(isset($me['sex'])) {
                        $gender = $me['sex'];
                        if($gender == 1) {
                            $gender = 'F';
                        }
                        if($gender == 2) {
                            $gender = 'M';
                        }

                        $sql = 'SELECT id FROM const_orientation
                            WHERE gender = ' . to_sql($gender, 'Text') . '
                            ORDER BY id ASC LIMIT 1';
                        $orientation = DB::result($sql);

                        if($orientation) {
                            $_GET['orientation'] = $orientation;
                        }
                    }

        }
    }


    static function getLikeButtonScript()
    {

        return '';

    }

    static function getLikeButtonHtml()
    {
        return '';
    }

    public function oAuthApi()
    {
        $nameSocial=self::$nameSocial;
        $params=$this->getParamsAuth();
        $currentUrl = Common::urlSite() . $this->loginRedirectUrl();
        $team_id = 'WPA82QTVFN';
        $key_id = 'N36GY4F82P';
	$client_id = $params['client_id'];
        $key = <<<KEY
-----BEGIN PRIVATE KEY-----
MIGTAgEAMBMGByqGSM49AgEGCCqGSM49AwEHBHkwdwIBAQQghMqSgboB06tMrqLk
DJy5GWSQuQBOTpLSS1JAjZZNdJmgCgYIKoZIzj0DAQehRANCAAQVaOaSdGCzezR7
xxKLjnNFdj8YKBoIwyndot8d2X8Xc0wZCewjac13Iw0vy52z95MF2KlFuiIVo+BV
qxNzQ65k
-----END PRIVATE KEY-----
KEY;
        if(isset($_POST['code'])) {

            $ecdsa_key = openssl_pkey_get_private($key);

            $header = array('typ' => 'JWT', 'alg' => 'ES256');
            $header['kid'] = $key_id;


            $payload = [
                'iss' => $team_id,
                'iat' => time(),
                'exp' => time() + 86400 * 180,
                'aud' => 'https://appleid.apple.com',
                'sub' => $client_id,
            ];

            $segments = array();
            $segments[] = $this->urlsafeB64Encode(json_encode($header));
            $segments[] = $this->urlsafeB64Encode(json_encode($payload));
            $signing_input = \implode('.', $segments);
            $signature = '';

            $success = \openssl_sign($signing_input, $signature, $key, 'SHA256');
            if (!$success) {
                throw new DomainException("OpenSSL unable to sign data");
            } else {
                if ($header['alg'] === 'ES256') {
                    $signature = $this->signatureFromDER($signature, 256);
                }
            }

            $segments[] = $this->urlsafeB64Encode($signature);

            $clientSecret = \implode('.', $segments);


            $code = $_POST['code'];
            $curl = curl_init();
            $postData = [
                "client_id=$client_id",
                "client_secret=$clientSecret",
                "code=$code",
                "grant_type=authorization_code"
            ];


            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://appleid.apple.com/auth/token",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => implode('&', $postData),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            $response = json_decode($response);
            if(!isset($response->access_token)) {
                echo '<p>Error getting an access token:</p>';
                echo '<p><a href="/">Start Over</a></p>';
                die();
            }

            $claims = explode('.', $response->id_token)[1];
            $claims = json_decode(base64_decode($claims));
            if (isset($claims->email)) {
                $userInfo['email'] = $claims->email;
		$userInfo['id'] = $claims->sub;
                $userInfo['status']=array();
                $userInfo['entities']=array();
                set_session('vk_user_info', $userInfo);
                redirect('join_facebook.php?cmd=vk_login');
            } else {
                Common::toHomePage();
            }
        }


        $url = 'https://appleid.apple.com/auth/authorize';
        $params2 = array(
            'client_id' => $params['client_id'],
            'redirect_uri'  => $currentUrl,
            'response_type' => 'code',
            'response_mode' => 'form_post',
            'scope' => 'email'            
        );

        redirect($url . '?' . http_build_query($params2));

    }
    private function urlsafeB64Encode($input)
    {
        return \str_replace('=', '', \strtr(\base64_encode($input), '+/', '-_'));
    }

    private function signatureFromDER($der, $keySize)
    {

        // OpenSSL returns the ECDSA signatures as a binary ASN.1 DER SEQUENCE
        list($offset, $_) = $this->readDER($der);
        list($offset, $r) = $this->readDER($der, $offset);
        list($offset, $s) = $this->readDER($der, $offset);

        // Convert r-value and s-value from signed two's compliment to unsigned
        // big-endian integers
        $r = \ltrim($r, "\x00");
        $s = \ltrim($s, "\x00");

        // Pad out r and s so that they are $keySize bits long
        $r = \str_pad($r, $keySize / 8, "\x00", STR_PAD_LEFT);
        $s = \str_pad($s, $keySize / 8, "\x00", STR_PAD_LEFT);

        return $r . $s;
    }


    private function readDER($der, $offset = 0)
    {
        $ASN1_BIT_STRING = 0x03;

        $pos = $offset;
        $size = \strlen($der);
        $constructed = (\ord($der[$pos]) >> 5) & 0x01;
        $type = \ord($der[$pos++]) & 0x1f;

        // Length
        $len = \ord($der[$pos++]);
        if ($len & 0x80) {
            $n = $len & 0x1f;
            $len = 0;
            while ($n-- && $pos < $size) {
                $len = ($len << 8) | \ord($der[$pos++]);
            }
        }

        // Value
        if ($type == $ASN1_BIT_STRING) {
            $pos++; // Skip the first contents octet (padding indicator)
            $data = \substr($der, $pos, $len - 1);
            $pos += $len - 1;
        } elseif (!$constructed) {
            $data = \substr($der, $pos, $len);
            $pos += $len;
        } else {
            $data = null;
        }

        return array($pos, $data);
    }
}