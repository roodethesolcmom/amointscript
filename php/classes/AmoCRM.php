<?php

//namespace App;

class AmoCRM {

    function make_request($url, $method, $httpheader, $data) {
        $curl = curl_init();
        if ($method != 'GET') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_SLASHES));
        }
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $httpheader);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 1 - с проверкой серта, 0 - без
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $code = (int)$code;
        $errors = [
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];
        if(curl_errno($curl)){
            echo 'Curl error: ' . curl_error($curl);
        }
        // print_r(curl_getinfo($curl));
        try
        {
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        }
        catch(Exception $e)
        {
            $txt = 'Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode();
            echo $txt;
        }
        $response = [
            'code' => $code,
            'body' => json_decode($out, true),
        ];
        return json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS | JSON_PRETTY_PRINT);

    }

    function error_key($link, $method, $httpheader, $file) {
        $old_token_data = file_get_contents($file);
        $token = json_decode($old_token_data, true)['body']['refresh_token'];
        $data = [
            'client_id' => '508c6123-d88d-4452-a7bb-df7829dab499',
            'client_secret' => '4TnmQPFY7hiOoWdV9CWGosSXIUE7Ut01eQxSuKbHW3GhWEIC9JCq6jFkk1a5KVME',
            'grant_type' => 'refresh_token',
            'refresh_token' => $token,
            'redirect_uri' => 'https://krafti.ru',
        ];
        $res = $this -> make_request($link, $method, $httpheader, $data);
        $code = json_decode($res, true)['code'];
        try
        {
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        }
        catch(Exception $e)
        {
            $txt = 'Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode();
            return $txt;
        }
        file_put_contents($file, $res);
        return $res;
    }

    function revoke_keys() {
        $file = '.\classes\keys.json';
        $old_token_data = file_get_contents($file);
        $old_token = json_decode($old_token_data, true)['body']['refresh_token'];
        $subdomain = 'krafti';
        $link = 'https://' . $subdomain . '.amocrm.ru/oauth2/access_token';
        $data = [
            'client_id' => '508c6123-d88d-4452-a7bb-df7829dab499',
            'client_secret' => '4TnmQPFY7hiOoWdV9CWGosSXIUE7Ut01eQxSuKbHW3GhWEIC9JCq6jFkk1a5KVME',
            'grant_type' => 'refresh_token',
            'refresh_token' => $old_token,
            'redirect_uri' => 'https://krafti.ru',
        ];
        $method = 'POST';
        $httpheader = ['Content-Type:application/json'];
        $res = $this -> make_request($link, $method, $httpheader, $data);
        $code = json_decode($res, true)['code'];
        try
        {
            if ($code < 200 || $code > 204) {
                $error_out = $this -> error_key($link, $method, $httpheader, $file);
                return $error_out;
            }
        }
        catch(Exception $e)
        {
            return $e;
        }
        file_put_contents($file, $res);
        return $res;
    }

    function user_worker($user_name, $phone, $email_form) {
        $res = $this -> get_contact($phone);
        $ans = json_decode($res, true)['body'];
        echo $res;
        if ($ans) {
            $ans_info_leads = json_decode($res, true)['body']['_embedded']['contacts'][0]['_embedded']['leads'];
            $ans_info_leads_email = json_decode($res, true)['body']['_embedded']['contacts'][0]['custom_fields_values'][1]['values'][0]['value'];
            $ans_info_leads_name = json_decode($res, true)['body']['_embedded']['contacts'][0]['name'];
            if ($ans_info_leads) {
                foreach ($ans_info_leads as $lead) {
                    $lead_id = $lead['id'];
                    $lead_info = $this -> get_lead_by_id($lead_id);
                    $lead_info_r = json_decode($lead_info, true);
                    if ($lead_info_r['body']['pipeline_id'] == '4980019') {
                        if ($lead_info_r['body']['status_id'] == '143') {
                            $create_lead = $this -> create_lead($user_name, 44942410, 4980019);
                            $repeat = $this -> make_request($link, $method, $headers, false);
                            $repeat_lead_info = json_decode($repeat, true)['body']['_embedded']['contacts'][0]['_embedded']['leads'];
                            foreach ($repeat_lead_info as $lead_rep) {
                                $lead_rep_id = $lead_rep['id'];
                                $lead_rep_info = $this -> get_lead_by_id($lead_rep_id);
                                $lead_rep_info_r = json_decode($lead_info, true);
                                if ($lead_rep_info_r['body']['pipeline_id'] == '4980019'){
                                    if ($lead_rep_info_r['body']['status_id'] == '44942410') {
                                        $name = $ans_info_leads_name;
                                        $email = $ans_info_leads_email;
                                        $note = $this -> create_note($lead_rep_id, $phone, $email, $name);
                                        $task = $this -> create_quest($lead_rep_id);
                                        echo $repeat;
                                    }

                                }
                            }
                        } elseif ($lead_info_r['body']['status_id'] == '142') {
                            $create_lead = $this -> create_lead($user_name, 45193339, 5015725);
                            $repeat = $this -> make_request($link, $method, $headers, false);
                            $repeat_lead_info = json_decode($repeat, true)['body']['_embedded']['contacts'][0]['_embedded']['leads'];
                            foreach ($repeat_lead_info as $lead_rep) {
                                $lead_rep_id = $lead_rep['id'];
                                $lead_rep_info = $this -> get_lead_by_id($lead_rep_id);
                                $lead_rep_info_r = json_decode($lead_info, true);
                                if ($lead_rep_info_r['body']['pipeline_id'] == '5015725'){
                                    if ($lead_rep_info_r['body']['status_id'] == '45193339') {
                                        $name = $ans_info_leads_name;
                                        $email = $ans_info_leads_email;
                                        $note = $this -> create_note($lead_rep_id, $phone, $email, $name);
                                        $task = $this -> create_quest($lead_rep_id);
                                        echo $repeat;
                                    }
                                }
                            }
                        } else {
                            $name = $ans_info_leads_name;
                            $email = $ans_info_leads_email;
                            $note = $this -> create_note($lead_id, $phone, $email, $name);
                            $task = $this -> create_quest($lead_id);
                            echo $lead_info;
                        }
                    }

                }
            }
        } else {
            $new_contact = $this -> create_contact($user_name, $phone, $email_form);
            $create_lead = $this -> create_lead($user_name, 44942410, 4980019);
            return $new_contact;
        }
        return $ans_info_leads;

    }

    function edit_lead($lead_id) {
        $new_token = $this ->revoke_keys();
        $subdomain = 'krafti';
        $link = 'https://' . $subdomain . '.amocrm.ru/api/v4/leads/' . $lead_id;
        $access_token = json_decode($new_token, true)['body']['access_token'];
        $text_data = 'Регистрация на сайте:' . $name . ', Телефон: ' . $phone . ', Почта: ' . $email;
        echo $text_data;
        $data = [
            'entity_id' => $lead_id,
            'note_type' => 'common',
            'custom_fields_values' => [[
                'field_id' => 633665,
                'field_name' => 'Срок доступа',
                'field_type' => 'multiselect',
                'values' => [
                    [
                        'value' => '',
                        'enum_id' => 370269,

                    ],
                ]
            ],
        ],
        ];
        $headers = [
            'Authorization: Bearer ' . $access_token
        ];
        $method = 'PATCH';
        $res = $this -> make_request($link, $method, $headers, $data);
        echo $res;
        $code = json_decode($res, true)['code'];
        try
        {
            if ($code < 200 || $code > 204) {
                $error_out = $this -> revoke_keys();
                return $error_out;
            }
        }
        catch(Exception $e)
        {
            $ex_text = 'Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode();
            echo $ex_text;
        }

        return $res;
    }

    function get_contact($phone) {
        $new_token = $this ->revoke_keys();
        $subdomain = 'krafti';
        $link = 'https://' . $subdomain . '.amocrm.ru/api/v4/contacts?with=leads&query=' . $phone;
        $access_token = json_decode($new_token, true)['body']['access_token'];
        $headers = [
            'Authorization: Bearer ' . $access_token
        ];
        $method = 'GET';
        $res = $this -> make_request($link, $method, $headers, false);
        $code = json_decode($res, true)['code'];
        try
        {
            if ($code < 200 || $code > 204) {
                $error_out = $this -> revoke_keys();
                return $error_out;
            }
        }
        catch(Exception $e)
        {
            $ex_text = 'Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode();
            echo $ex_text;
        }

        return $res;
    }

    function get_lead_by_id($lead_id) {
        $new_token = $this ->revoke_keys();
        $subdomain = 'krafti';
        $link = 'https://' . $subdomain . '.amocrm.ru/api/v4/leads/' . $lead_id;
        $access_token = json_decode($new_token, true)['body']['access_token'];
        $headers = [
            'Authorization: Bearer ' . $access_token
        ];
        $method = 'GET';
        $res = $this -> make_request($link, $method, $headers, false);
        $code = json_decode($res, true)['code'];
        try
        {
            if ($code < 200 || $code > 204) {
                $error_out = $this -> revoke_keys();
                return $error_out;
            }
        }
        catch(Exception $e)
        {
            $ex_text = 'Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode();
            echo $ex_text;
        }
        return $res;
    }

    function create_note($lead_id, $phone, $email, $name) {
        $new_token = $this ->revoke_keys();
        $subdomain = 'krafti';
        $link = 'https://' . $subdomain . '.amocrm.ru/api/v4/leads/notes';
        $access_token = json_decode($new_token, true)['body']['access_token'];
        $text_data = 'Регистрация на сайте:' . $name . ', Телефон: ' . $phone . ', Почта: ' . $email;
        echo $text_data;
        $data = [[
            'entity_id' => $lead_id,
            'note_type' => 'common',
            'params' => [
                'text' => $text_data,
            ],
        ]];
        $headers = [
            'Authorization: Bearer ' . $access_token
        ];
        $method = 'POST';
        $res = $this -> make_request($link, $method, $headers, $data);
        echo $res;
        $code = json_decode($res, true)['code'];
        try
        {
            if ($code < 200 || $code > 204) {
                $error_out = $this -> revoke_keys();
                return $error_out;
            }
        }
        catch(Exception $e)
        {
            $ex_text = 'Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode();
            echo $ex_text;
        }

        return $res;
    }

    function create_quest($lead_id) {
        $new_token = $this ->revoke_keys();
        $subdomain = 'krafti';
        $link = 'https://' . $subdomain . '.amocrm.ru/api/v4/tasks';
        $access_token = json_decode($new_token, true)['body']['access_token'];
        $text_data = 'Клиент зарегистрировался на сайте';
        echo $text_data;
        $date = date_create();
        $date_till = date_timestamp_get($date);
        $data = [[
            'text' => $text_data,
            'complete_till' => $date_till,
            'entity_id' => $lead_id,
            'entity_type' => 'leads',
        ]];
        $headers = [
            'Authorization: Bearer ' . $access_token
        ];
        $method = 'POST';
        $res = $this -> make_request($link, $method, $headers, $data);
        echo $res;
        $code = json_decode($res, true)['code'];
        try
        {
            if ($code < 200 || $code > 204) {
                $error_out = $this -> revoke_keys();
                return $error_out;
            }
        }
        catch(Exception $e)
        {
            $ex_text = 'Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode();
            echo $ex_text;
        }

        return $res;
    }

    function create_lead($user_name, $status_id, $pipeline_id) {
        $new_token = $this ->revoke_keys();
        $subdomain = 'krafti';
        $link = 'https://' . $subdomain . '.amocrm.ru/api/v4/leads';
        $access_token = json_decode($new_token, true)['body']['access_token'];
        $data = [[
            'name' => $user_name,
            'status_id' => $status_id,
            'pipeline_id' => $pipeline_id,
        ]];
        $headers = [
            'Authorization: Bearer ' . $access_token
        ];
        $method = 'POST';
        //$httpheader = ['Content-Type:application/json'];
        $res = $this -> make_request($link, $method, $headers, $data);
        echo $res;
        $code = json_decode($res, true)['code'];
        try
        {
            if ($code < 200 || $code > 204) {
                $error_out = $this -> revoke_keys();
                return $error_out;
            }
        }
        catch(Exception $e)
        {
            $ex_text = 'Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode();
            echo $ex_text;
        }

        return $res;
    }

    function create_contact($name, $phone, $email) {
        $new_token = $this ->revoke_keys();
        $subdomain = 'krafti';
        $link = 'https://' . $subdomain . '.amocrm.ru/api/v4/contacts';
        $access_token = json_decode($new_token, true)['body']['access_token'];
        $data = [[
            'name' => $name,
            'custom_fields_values' => [[
                'field_id' => 394753,
                'values' => [[
                    'value' => $phone,
                ]]],
                [
                'field_id' => 394755,
                'values' => [[
                    'value' => $email,
                ]]]]
        ]];
        $headers = [
            'Authorization: Bearer ' . $access_token
        ];
        $method = 'POST';
        $res = $this -> make_request($link, $method, $headers, $data);
        echo $res;
        $code = json_decode($res, true)['code'];
        try
        {
            if ($code < 200 || $code > 204) {
                $error_out = $this -> revoke_keys();
                return $error_out;
            }
        }
        catch(Exception $e)
        {
            $ex_text = 'Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode();
            echo $ex_text;
        }

        return $res;
    }

}
?>

//44942410 - новый лид, первая продажа
//142 - успех
//143 - отказ
//45193339 - клиент, повторная продажа
//5015725 - pipline повторная продажа