<?php

//namespace App\Controllers;

//use Exception;

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
            return $txt;
        }
        $response = [
            'code' => $code,
            'body' => json_decode($out, true),
        ];
        curl_close($curl);
        return json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS | JSON_PRETTY_PRINT);

    }

    function error_key($link, $method, $httpheader, $file) {
        $old_token_data = file_get_contents($file);
        $token = json_decode($old_token_data, true)['body']['refresh_token'];
        $data = [
            'client_id' => 'ae359eae-2a15-45ad-9e6e-326a304dcf03',
            'client_secret' => 'rTrNhvo2lkvfo5BbaylNpUOSc2jpHA8xFT8kEBFVZK7TAeuJaGd6rJpDDix37GgW',
            'grant_type' => 'refresh_token',
            'refresh_token' => $token,
            'redirect_uri' => 'https://krafti.ru',
        ];
        $res = $this -> make_request($link, $method, $httpheader, $data);
        $code = json_decode($res, true)['code'];
        try
        {   
            $code = json_decode($res, true)['code'];
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
        $file = '.\classes\keys.json';//'/var/www/html/keys.json';
        $old_token_data = file_get_contents($file);
        $old_token = json_decode($old_token_data, true)['body']['refresh_token'];
        $subdomain = 'krafti';
        $link = 'https://' . $subdomain . '.amocrm.ru/oauth2/access_token';
        $data = [
            'client_id' => 'ae359eae-2a15-45ad-9e6e-326a304dcf03',
            'client_secret' => 'rTrNhvo2lkvfo5BbaylNpUOSc2jpHA8xFT8kEBFVZK7TAeuJaGd6rJpDDix37GgW',
            'grant_type' => 'refresh_token',
            'refresh_token' => $old_token,
            'redirect_uri' => 'https://krafti.ru',
        ];
        $method = 'POST';
        $httpheader = ['Content-Type:application/json'];
        $res = $this -> make_request($link, $method, $httpheader, $data);
        
        try
        {   
            $code = json_decode($res, true)['code'];
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        }
        catch(Exception $e)
        {
            $error_out = $this -> error_key($link, $method, $httpheader, $file);
            return $error_out;
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
            $contact_id = json_decode($res, true)['body']['_embedded']['contacts'][0]['id'];
            if ($ans_info_leads) {
                $last_lead = end($ans_info_leads);
                $lead_id = $last_lead['id'];
                $lead_info = $this -> get_lead_by_id($lead_id);
                $lead_info_r = json_decode($lead_info, true);
                if ($lead_info_r['body']['pipeline_id'] == '4980019') {
                    if ($lead_info_r['body']['status_id'] == '143') {
                        $lead_name = 'krafti.ru ' . $user_name;
                        $create_lead = $this -> create_lead($lead_name, 44942410, 4980019, $contact_id);
                        $lead_rep_id = json_decode($create_lead, true)['body']['_embedded']['leads'][0]['id'];
                        $text = 'Регистрация на сайте: ' . $user_name . ', Телефон: ' . $phone . ', Почта: ' . $email_form;
                        $note = $this -> create_note($lead_rep_id, $text);
                        $task = $this -> create_quest($lead_rep_id);
                        echo $lead_info;
                    } elseif ($lead_info_r['body']['status_id'] == '142') {
                        $lead_name = 'krafti.ru ' . $user_name;
                        $create_lead = $this -> create_lead($lead_name, 45193339, 5015725, $contact_id);
                        $lead_rep_id = json_decode($create_lead, true)['body']['_embedded']['leads'][0]['id'];
                        $text = 'Регистрация на сайте: ' . $user_name . ', Телефон: ' . $phone . ', Почта: ' . $email_form;
                        $note = $this -> create_note($lead_rep_id, $text);
                        $task = $this -> create_quest($lead_rep_id);
                        echo $lead_info;
                    } else {
                        $text = 'Регистрация на сайте: ' . $user_name . ', Телефон: ' . $phone . ', Почта: ' . $email_form;
                        $note = $this -> create_note($lead_id, $text);
                        $task = $this -> create_quest($lead_id);
                        echo $lead_info;
                    }
                }               
            }
        } else {
            $new_contact = $this -> create_contact($user_name, $phone, $email_form);
            $lead_name = 'krafti.ru ' . $user_name;
            $new_contact_id = json_decode($new_contact, true)['body']['_embedded']['contacts'][0]['id'];
            $create_lead = $this -> create_lead($lead_name, 44942410, 4980019, $new_contact_id);
            return $new_contact;
        }
        return $res;

    }

    function buy_worker_begin($product_name, $price, $user_name, $phone, $email_form) {
        $res = $this -> get_contact($phone);
        $ans = json_decode($res, true)['body'];
        echo $res;
        if ($ans) {
            $ans_info_leads = json_decode($res, true)['body']['_embedded']['contacts'][0]['_embedded']['leads'];
            $contact_id = json_decode($res, true)['body']['_embedded']['contacts'][0]['id'];
            if ($ans_info_leads) {
                $last_lead = end($ans_info_leads);
                $lead_id = $last_lead['id'];
                $lead_info = $this -> get_lead_by_id($lead_id);
                $lead_info_r = json_decode($lead_info, true);
                if ($lead_info_r['body']['pipeline_id'] == '4980019') {
                    if ($lead_info_r['body']['status_id'] == '143') {
                        $lead_name = 'krafti.ru ' . $user_name;
                        $create_lead = $this -> create_lead($lead_name, 44942410, 4980019, $contact_id);
                        $lead_rep_id = json_decode($create_lead, true)['body']['_embedded']['leads'][0]['id'];
                        $edit_product = $this -> edit_lead_product($lead_rep_id, $product_name, $price);
                        echo $lead_info;
                    } elseif ($lead_info_r['body']['status_id'] == '142') {
                        $lead_name = 'krafti.ru ' . $user_name;
                        $create_lead = $this -> create_lead($lead_name, 45193339, 5015725, $contact_id);
                        $lead_rep_id = json_decode($create_lead, true)['body']['_embedded']['leads'][0]['id'];
                        $edit_product = $this -> edit_lead_product($lead_rep_id, $product_name, $price);
                        echo $lead_info;
                    } else {
                        $edit_product = $this -> edit_lead_product($lead_id, $product_name, $price);
                    }
                } elseif ($lead_info_r['body']['pipeline_id'] == '5015725') {
                        if ($lead_info_r['body']['status_id'] !== '45193339') {
                            $lead_name = 'krafti.ru ' . $user_name;
                            $create_lead = $this -> create_lead($lead_name, 45193339, 5015725, $contact_id);
                            $lead_rep_id = json_decode($create_lead, true)['body']['_embedded']['leads'][0]['id'];
                            $edit_product = $this -> edit_lead_product($lead_rep_id, $product_name, $price);
                            echo $lead_info;
                        } else {
                            $edit_product = $this -> edit_lead_product($lead_id, $product_name, $price);
                            echo $lead_info;
                        }
                }
        }
    } else {
            $new_contact = $this -> create_contact($user_name, $phone, $email_form);
            $lead_name = 'krafti.ru ' . $user_name;
            $new_contact_id = json_decode($new_contact, true)['body']['_embedded']['contacts'][0]['id'];
            $create_lead = $this -> create_lead($lead_name, 44942410, 4980019, $new_contact_id);
            $new_lead_id = json_decode($create_lead, true);//['body']['_embedded']['leads'][0]['id'];
            echo $new_lead_id;
            $edit_product = $this -> edit_lead_product($new_lead_id, $product_name, $price);
            echo $new_contact_id;
        }
        return $res;
    }

    function buy_worker_confirm($phone) {
        $res = $this -> get_contact($phone);
        $ans = json_decode($res, true)['body'];
        echo $res;
        if ($ans) {
            $ans_info_leads = json_decode($res, true)['body']['_embedded']['contacts'][0]['_embedded']['leads'];
            if ($ans_info_leads) {
                $last_lead = end($ans_info_leads);
                $lead_id = $last_lead['id'];
                $lead_info = $this -> get_lead_by_id($lead_id);
                $lead_info_r = json_decode($lead_info, true);
                $product_name_field = end($lead_info_r['body']['custom_fields_values']);
                $product_name = $product_name_field['values'][0]['value'];
                if ($lead_info_r['body']['pipeline_id'] == '4980019') {
                    $edit_lead = $this -> edit_lead_success($lead_id, 142);
                    $date = date_create();
                    $date_till = date_timestamp_get($date);
                    $text = 'Курс: ' . $product_name . ' успешно оплачен ' . $date_till;
                    $note = $this -> create_note($lead_id, $text);
                    echo $lead_info;
                } elseif ($lead_info_r['body']['pipeline_id'] == '5015725') {
                    $edit_lead = $this -> edit_lead_success($lead_id, 142);
                    $date = date_create();
                    $date_till = date_timestamp_get($date);
                    $text = 'Курс: ' . $product_name . ' успешно оплачен ' . $date_till;
                    $note = $this -> create_note($lead_id, $text);
                    echo $lead_info;
                }
        return $res;
    }
    }
    }

    function create_custom_field_text($product_field_name) {
        $file = '.\classes\keys.json';//'/var/www/html/keys.json';
        $old_token_data = file_get_contents($file);
        $token = json_decode($old_token_data, true)['body']['access_token'];
        $subdomain = 'krafti';
        $link = 'https://' . $subdomain . '.amocrm.ru/api/v4/leads/custom_fields/';
        $data = 
            [
                'name' => $product_field_name,
                'type' => 'text',
            
        ];
        $headers = [
            'Authorization: Bearer ' . $token
        ];
        $method = 'POST';
        $res = $this -> make_request($link, $method, $headers, $data);
        echo $res;
        $code = json_decode($res, true)['code'];
        try
        {
            if ($code < 200 || $code > 204) {
                $error_out = $this -> revoke_keys();
                $access_token = json_decode($error_out, true)['body']['access_token'];
                $headers_rev = [
                    'Authorization: Bearer ' . $access_token
                ];
                $res_rev = $this -> make_request($link, $method, $headers_rev, $data);
                echo $res_rev;
                $code = json_decode($res_rev, true)['code'];
                return $res_rev;
            }
        }
        catch(Exception $e)
        {
            $ex_text = 'Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode();
            echo $ex_text;
        }

        return $res;
    }

    function edit_lead_product($lead_id, $product_name, $price) {
        $file = '.\classes\keys.json';//'/var/www/html/keys.json';
        $old_token_data = file_get_contents($file);
        $token = json_decode($old_token_data, true)['body']['access_token'];
        $subdomain = 'krafti';
        $link = 'https://' . $subdomain . '.amocrm.ru/api/v4/leads/' . $lead_id;
        $data = [
            'price' => $price,
            'custom_fields_values' => [
                [
                    'field_id' => 978093,
                    'values' => [
                        [
                            'value' => $product_name,
                        ]
                    ]
                ],
            ]
        ];
        $headers = [
            'Authorization: Bearer ' . $token
        ];
        $method = 'PATCH';
        $res = $this -> make_request($link, $method, $headers, $data);
        echo $res;
        try
        {   
            $code = json_decode($res, true)['code'];
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        }
        catch(Exception $e)
        {
            $this -> revoke_keys();
            $result = $this -> edit_lead_product($lead_id, $product_name, $price);
            return $result;
            //$ex_text = 'Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode();
            //echo $ex_text;
        }

        return $res;
    }

    function edit_lead_success($lead_id, $status_id) {
        $file = '.\classes\keys.json';//'/var/www/html/keys.json';
        $old_token_data = file_get_contents($file);
        $token = json_decode($old_token_data, true)['body']['access_token'];
        $subdomain = 'krafti';
        $link = 'https://' . $subdomain . '.amocrm.ru/api/v4/leads/' . $lead_id;
        $date = date_create();
        $date_till = date_timestamp_get($date);
        $data = [
            'status_id' => $status_id,
            'custom_fields_values' => [
                [
                    'field_id' => 953007,
                    'values' => [
                        [
                            'value' => true,
                        ]
                    ]
                ],
                [
                    'field_id' => 953003,
                    'values' => [
                        [
                            'value' => $date_till,
                        ]
                    ]
                ]
            ]
        ];
        $headers = [
            'Authorization: Bearer ' . $token
        ];
        $method = 'PATCH';
        $res = $this -> make_request($link, $method, $headers, $data);
        echo $res;
        
        try
        {   
            $code = json_decode($res, true)['code'];
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        }
        catch(Exception $e)
        {
            $this -> revoke_keys();
            $result = $this -> edit_lead_success($lead_id, $status_id);
            return $result;
            //$ex_text = 'Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode();
            //echo $ex_text;
        }

        return $res;
    }

    function get_contact($phone) {
        $file = '.\classes\keys.json';//'/var/www/html/keys.json';
        $old_token_data = file_get_contents($file);
        $token = json_decode($old_token_data, true)['body']['access_token'];
        $subdomain = 'krafti';
        $link = 'https://' . $subdomain . '.amocrm.ru/api/v4/contacts?with=leads&query=' . $phone;
        $headers = [
            'Authorization: Bearer ' . $token
        ];
        $method = 'GET';
        $res = $this -> make_request($link, $method, $headers, false);
        try
        {   
            $code = json_decode($res, true)['code'];
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        }
        catch(Exception $e)
        {
            $this -> revoke_keys();
            $result = $this -> get_contact($phone);
            return $result;
            //$ex_text = 'Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode();
            //echo $ex_text;
        }

        return $res;
    }

    function get_lead_by_id($lead_id) {
        $file = '.\classes\keys.json';//'/var/www/html/keys.json';
        $old_token_data = file_get_contents($file);
        $token = json_decode($old_token_data, true)['body']['access_token'];
        $subdomain = 'krafti';
        $link = 'https://' . $subdomain . '.amocrm.ru/api/v4/leads/' . $lead_id;
        $headers = [
            'Authorization: Bearer ' . $token
        ];
        $method = 'GET';
        $res = $this -> make_request($link, $method, $headers, false);
        try
        {   
            $code = json_decode($res, true)['code'];
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        }
        catch(Exception $e)
        {
            $this -> revoke_keys();
            $result = $this -> get_lead_by_id($lead_id);
            return $result;
            //$ex_text = 'Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode();
            //echo $ex_text;
        }
        return $res;
    }

    function create_note($lead_id, $text) {
        $file = '.\classes\keys.json';//'/var/www/html/keys.json';
        $old_token_data = file_get_contents($file);
        $token = json_decode($old_token_data, true)['body']['access_token'];
        $subdomain = 'krafti';
        $link = 'https://' . $subdomain . '.amocrm.ru/api/v4/leads/notes';
        $data = [[
            'entity_id' => $lead_id,
            'note_type' => 'common',
            'params' => [
                'text' => $text,
            ],
        ]];
        $headers = [
            'Authorization: Bearer ' . $token
        ];
        $method = 'POST';
        $res = $this -> make_request($link, $method, $headers, $data);
        echo $res;
        //$code = json_decode($res, true)['code'];
        try
        {
            $code = json_decode($res, true)['code'];
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        }
        catch(Exception $e)
        {
            $this -> revoke_keys();
            $result = $this -> create_note($lead_id, $text);
            return $result;
            //$ex_text = 'Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode();
            //echo $ex_text;
        }

        return $res;
    }

    function create_quest($lead_id) {
        $file = '.\classes\keys.json';//'/var/www/html/keys.json';
        $old_token_data = file_get_contents($file);
        $token = json_decode($old_token_data, true)['body']['access_token'];
        $subdomain = 'krafti';
        $link = 'https://' . $subdomain . '.amocrm.ru/api/v4/tasks';
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
            'Authorization: Bearer ' . $token
        ];
        $method = 'POST';
        $res = $this -> make_request($link, $method, $headers, $data);
        echo $res;
        //$code = json_decode($res, true)['code'];
        try
        {
            $code = json_decode($res, true)['code'];
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        }
        catch(Exception $e)
        {
            $this -> revoke_keys();
            $result = $this -> create_quest($lead_id);
            return $result;
            //$ex_text = 'Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode();
            //echo $ex_text;
        }

        return $res;
    }

    function create_lead($user_name, $status_id, $pipeline_id, $contact_id) {
        $file = '.\classes\keys.json';//'/var/www/html/keys.json';
        $old_token_data = file_get_contents($file);
        $token = json_decode($old_token_data, true)['body']['access_token'];
        $subdomain = 'krafti';
        $link = 'https://' . $subdomain . '.amocrm.ru/api/v4/leads/complex';
        $data = [[
            'name' => $user_name,
            'status_id' => $status_id,
            'pipeline_id' => $pipeline_id,
            '_embedded' => [
                'contacts' => [
                    [
                        'id' => $contact_id,
                    ]
                ]
            ]
        ]];
        $headers = [
            'Authorization: Bearer ' . $token
        ];
        $method = 'POST';
        //$httpheader = ['Content-Type:application/json'];
        $res = $this -> make_request($link, $method, $headers, $data);
        echo $res;
        try
        {
            $code = json_decode($res, true)['code'];
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        }
        catch(Exception $e)
        {
            $this -> revoke_keys();
            $result = $this -> create_lead($user_name, $status_id, $pipeline_id, $contact_id);
            return $result;
            //$ex_text = 'Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode();
            //echo $ex_text;
        }
        return $res;
    }

    function create_contact($name, $phone, $email) {
        $file = '.\classes\keys.json';//'/var/www/html/keys.json';
        $old_token_data = file_get_contents($file);
        $token = json_decode($old_token_data, true)['body']['access_token'];
        $subdomain = 'krafti';
        $link = 'https://' . $subdomain . '.amocrm.ru/api/v4/contacts';
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
            'Authorization: Bearer ' . $token
        ];
        $method = 'POST';
        $res = $this -> make_request($link, $method, $headers, $data);
        echo $res;
        //$code = json_decode($res, true)['code'];
        try
        {
            $code = json_decode($res, true)['code'];
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        }
        catch(Exception $e)
        {
            $this -> revoke_keys();
            $result = $this -> create_contact($name, $phone, $email);
            return $result;
            //$ex_text = 'Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode();
            //echo $ex_text;
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