<?php

namespace enrol_billplz;

class Connect
{
    private $api_key;
    private $x_signature_key;
    private $collection_id;

    private $process;
    public $is_production;
    public $detect_mode = false;
    public $url;
    public $webhook_rank;

    public $header;

    const PRODUCTION_URL = 'https://www.billplz.com/api/';
    const STAGING_URL = 'https://www.billplz-sandbox.com/api/';

    public function __construct($api_key)
    {
        $this->api_key = $api_key;

        $this->process = new \curl();

        $this->process->setopt(array(
            'CURLOPT_HEADER' => 0,
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_SSL_VERIFYHOST' => 0,
            'CURLOPT_SSL_VERIFYPEER' => 0,
            'CURLOPT_TIMEOUT' => 10,
            'CURLOPT_USERPWD' => $api_key.':',
        ));
    }

    public function setStaging($is_staging = false)
    {
        $this->is_staging = $is_staging;
        if ($is_staging) {
            $this->url = self::STAGING_URL;
        } else {
            $this->url = self::PRODUCTION_URL;
        }
    }

    public function createCollection($title, $optional = array())
    {
        $url = $this->url . 'v4/collections';

        $body = http_build_query(['title' => $title]);
        if (isset($optional['split_header'])) {
            $split_header = http_build_query(array('split_header' => $optional['split_header']));
        }

        $split_payments = [];
        if (isset($optional['split_payments'])) {
            foreach ($optional['split_payments'] as $param) {
                $split_payments[] = http_build_query($param);
            }
        }

        if (!empty($split_payments)) {
            $body .= '&' . implode('&', $split_payments);

            if (!empty($split_header)) {
                $body .= '&' . $split_header;
            }
        }

        $body = $this->process->post($url, $body);
        $header = $this->process->info['http_code'];
        $return = array($header, $body);

        return $return;
    }

    public function getCollectionIndex($parameter = array())
    {
        $url = $this->url . 'v4/collections';

        $body = $this->process->post($url, $parameter);
        $header = $this->process->info['http_code'];
        $return = array($header, $body);

        return $return;
    }

    public function createOpenCollection($parameter, $optional = array())
    {
        $url = $this->url . 'v4/open_collections';

        $body = http_build_query($parameter);
        if (isset($optional['split_header'])) {
            $split_header = http_build_query(array('split_header' => $optional['split_header']));
        }

        $split_payments = [];
        if (isset($optional['split_payments'])) {
            foreach ($optional['split_payments'] as $param) {
                $split_payments[] = http_build_query($param);
            }
        }

        if (!empty($split_payments)) {
            unset($optional['split_payments']);
            $body .= '&' . implode('&', $split_payments);
            if (!empty($split_header)) {
                unset($optional['split_header']);
                $body .= '&' . $split_header;
            }
        }

        if (!empty($optional)) {
            $body .= '&' . http_build_query($optional);
        }

        $body = $this->process->post($url, $body);
        $header = $this->process->info['http_code'];
        $return = array($header, $body);

        return $return;
    }

    public function getCollection($id)
    {
        $url = $this->url . 'v4/collections/' . $id;

        $body = $this->process->get($url);
        $header = $this->process->info['http_code'];

        $return = array($header, $body);

        return $return;
    }

    public function getOpenCollection($id)
    {
        $url = $this->url . 'v4/open_collections/' . $id;

        $body = $this->process->get($url);
        $header = $this->process->info['http_code'];
        $return = array($header, $body);

        return $return;
    }

    public function getOpenCollectionIndex($parameter = array())
    {
        $url = $this->url . 'v4/open_collections?' . http_build_query($parameter);

        $body = $this->process->get($url);
        $header = $this->process->info['http_code'];
        $return = array($header, $body);
        
        return $return;
    }

    public function createMPICollection($title)
    {
        $url = $this->url . 'v4/mass_payment_instruction_collections';

        $data = ['title' => $title];

        $body = $this->process->post($url, $data);
        $header = $this->process->info['http_code'];
        $return = array($header, $body);
        
        return $return;
    }

    public function getMPICollection($id)
    {
        $url = $this->url . 'v4/mass_payment_instruction_collections/' . $id;
        
        $body = $this->process->get($url);
        $header = $this->process->info['http_code'];
        $return = array($header, $body);
        
        return $return;
    }

    public function createMPI($parameter, $optional = array())
    {
        $url = $this->url . 'v4/mass_payment_instructions';

        //if (sizeof($parameter) !== sizeof($optional) && !empty($optional)){
        //    throw new \Exception('Optional parameter size is not match with Required parameter');
        //}

        $data = array_merge($parameter, $optional);

        $body = $this->process->post($url, $data);
        $header = $this->process->info['http_code'];
        $return = array($header, $body);

        return $return;
    }

    public function getMPI($id)
    {
        $url = $this->url . 'v4/mass_payment_instructions/' . $id;

        $body = $this->process->get($url);
        $header = $this->process->info['http_code'];
        $return = array($header, $body);

        return $return;
    }

    public static function getXSignature($x_signature_key)
    {
        $signingString = '';

        if (isset($_GET['billplz']['id']) && isset($_GET['billplz']['paid_at']) && isset($_GET['billplz']['paid']) && isset($_GET['billplz']['x_signature'])) {
            $data = array(
                'id' => $_GET['billplz']['id'],
                'paid_at' => $_GET['billplz']['paid_at'],
                'paid' => $_GET['billplz']['paid'],
                'x_signature' => $_GET['billplz']['x_signature'],
            );
            $type = 'redirect';
        } elseif (isset($_POST['x_signature'])) {
            $data = array(
                'amount' => isset($_POST['amount']) ? $_POST['amount'] : '',
                'collection_id' => isset($_POST['collection_id']) ? $_POST['collection_id'] : '',
                'due_at' => isset($_POST['due_at']) ? $_POST['due_at'] : '',
                'email' => isset($_POST['email']) ? $_POST['email'] : '',
                'id' => isset($_POST['id']) ? $_POST['id'] : '',
                'mobile' => isset($_POST['mobile']) ? $_POST['mobile'] : '',
                'name' => isset($_POST['name']) ? $_POST['name'] : '',
                'paid_amount' => isset($_POST['paid_amount']) ? $_POST['paid_amount'] : '',
                'paid_at' => isset($_POST['paid_at']) ? $_POST['paid_at'] : '',
                'paid' => isset($_POST['paid']) ? $_POST['paid'] : '',
                'state' => isset($_POST['state']) ? $_POST['state'] : '',
                'url' => isset($_POST['url']) ? $_POST['url'] : '',
                'x_signature' => isset($_POST['x_signature']) ? $_POST['x_signature'] : '',
            );
            $type = 'callback';
        } else {
            return false;
        }

        foreach ($data as $key => $value) {
            if (isset($_GET['billplz']['id'])) {
                $signingString .= 'billplz' . $key . $value;
            } else {
                $signingString .= $key . $value;
            }
            if (($key === 'url' && isset($_POST['x_signature'])) || ($key === 'paid' && isset($_GET['billplz']['id']))) {
                break;
            } else {
                $signingString .= '|';
            }
        }

        /*
         * Convert paid status to boolean
         */
        $data['paid'] = $data['paid'] === 'true' ? true : false;

        $signedString = hash_hmac('sha256', $signingString, $x_signature_key);

        if ($data['x_signature'] === $signedString) {
            $data['type'] = $type;
            return $data;
        }

        throw new \Exception('X Signature Calculation Mismatch!');
    }

    public function deactivateCollection($title, $option = 'deactivate')
    {
        $url = $this->url . 'v3/collections/' . $title . '/' . $option;

        $body = $this->process->post($url);
        $header = $this->process->info['http_code'];
        $return = array($header, $body);

        return $return;
    }

    public function createBill($parameter, $optional = array())
    {
        $url = $this->url . 'v3/bills';

        //if (sizeof($parameter) !== sizeof($optional) && !empty($optional)){
        //    throw new \Exception('Optional parameter size is not match with Required parameter');
        //}

        $data = array_merge($parameter, $optional);

        $body = $this->process->post($url, $data);
        $header = $this->process->info['http_code'];
        $return = array($header, $body);

        return $return;
    }

    public function getBill($id)
    {
        $url = $this->url . 'v3/bills/' . $id;

        $body = $this->process->get($url);
        $header = $this->process->info['http_code'];
        $return = array($header, $body);

        return $return;
    }

    public function deleteBill($id)
    {
        $url = $this->url . 'v3/bills/' . $id;

        $body = $this->process->delete($url);
        $header = $this->process->info['http_code'];
        $return = array($header, $body);
 
        return $return;
    }

    public function bankAccountCheck($id)
    {
        $url = $this->url . 'v3/check/bank_account_number/' . $id;

        $body = $this->process->get($url);
        $header = $this->process->info['http_code'];
        $return = array($header, $body);

        return $return;
    }

    public function getPaymentMethodIndex($id)
    {
        $url = $this->url . 'v3/collections/' . $id . '/payment_methods';

        $body = $this->process->get($url);
        $header = $this->process->info['http_code'];
        $return = array($header, $body);
 
        return $return;
    }

    public function getTransactionIndex($id, $parameter)
    {
        $url = $this->url . 'v3/bills/' . $id . '/transactions';

        $body = $this->process->get($url, $parameter);
        $header = $this->process->info['http_code'];
        $return = array($header, $body);

        return $return;
    }

    public function updatePaymentMethod($parameter)
    {
        if (!isset($parameter['collection_id'])) {
            throw new \Exception('Collection ID is not passed on updatePaymethodMethod');
        }
        $url = $this->url . 'v3/collections/' . $parameter['collection_id'] . '/payment_methods';

        unset($parameter['collection_id']);
        $data = $parameter;

        $body = [];
        foreach ($data['payment_methods'] as $param) {
            $body[] = http_build_query($param);
        }

        $body = $this->process->put($url, $body);
        $header = $this->process->info['http_code'];
        $return = array($header, $body);

        return $return;
    }

    public function getBankAccountIndex($parameter)
    {
        if (!is_array($parameter['account_numbers'])) {
            throw new \Exception('Not valid account numbers.');
        }

        $parameter = http_build_query($parameter);
        $parameter = preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', $parameter);

        $url = $this->url . 'v3/bank_verification_services?' . $parameter;

        $body = $this->process->get($url);
        $header = $this->process->info['http_code'];
        $return = array($header, $body);

        return $return;
    }

    public function getBankAccount($id)
    {
        $url = $this->url . 'v3/bank_verification_services/' . $id;

        $body = $this->process->get($url);
        $header = $this->process->info['http_code'];
        $return = array($header, $body);

        return $return;
    }

    public function createBankAccount($parameter)
    {
        $url = $this->url . 'v3/bank_verification_services';

        $body = $this->process->post($url, $parameter);
        $header = $this->process->info['http_code'];
        $return = array($header, $body);
        return $return;
    }

    public function getFpxBanks()
    {
        $url = $this->url . 'v3/fpx_banks';

        $body = $this->process->get($url);
        $header = $this->process->info['http_code'];
        $return = array($header, $body);

        return $return;
    }

    public function toArray($json)
    {
        return array($json[0], \json_decode($json[1], true));
    }
}