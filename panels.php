<?php
ini_set('error_log', 'error_log');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Marzban.php';
require_once __DIR__ . '/function.php';
require_once __DIR__ . '/x-ui_single.php';

class ManagePanel
{
    public $pdo, $domainhosts, $name_panel, $new_marzban;
    function createUser($name_panel, $code_product, $usernameC, array $Data_Config)
    {
        $Output = [];
        global $pdo, $domainhosts, $new_marzban;
        if (strlen($usernameC) < 3) {
            return array(
                "status" => "Unsuccessful",
                "msg" => "Username must be at least 3 characters long."
            );
        }
        // input time expire timestep use $Data_Config
        // input data_limit byte use $Data_Config
        // input username use $Data_Config
        // input from_id use $Data_Config
        // input type config use $Data_Config
        $Get_Data_Panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        if ($Get_Data_Panel == false) {
            $Output['status'] = 'Unsuccessful';
            $Output['msg'] = 'Panel Not Found';
            return $Output;
        }
        if ($Get_Data_Panel['subvip'] == "onsubvip") {
            $inoice = select("invoice", "*", "username", $usernameC, "select");
        } else {
            $inoice = false;
        }
        if (!in_array($code_product, ["usertest", "🛍 حجم دلخواه", "customvolume"])) {

            $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :name_panel OR Location = '/all')  AND code_product = :code_product");
            $stmt->bindParam(':name_panel', $name_panel);
            $stmt->bindParam(':code_product', $code_product);
            $stmt->execute();
            $Get_Data_Product = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            if ($code_product == "usertest") {
                $Get_Data_Product['name_product'] = "usertest";
            } else {
                $Get_Data_Product['name_product'] = false;
            }
            $Get_Data_Product['data_limit_reset'] = "no_reset";
        }
        $expire = $Data_Config['expire'];
        $data_limit = $Data_Config['data_limit'];
        $note = "{$Data_Config['from_id']} | {$Data_Config['username']} | {$Data_Config['type']}";
        if ($Get_Data_Panel['type'] == "marzban") {
            //create user
            $ConnectToPanel = adduser($Get_Data_Panel['name_panel'], $data_limit, $usernameC, $expire, $note, $Get_Data_Product['data_limit_reset'], $Get_Data_Product['name_product']);
            if (!empty($ConnectToPanel['status']) && $ConnectToPanel['status'] == 500) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $ConnectToPanel['status']
                );
            }
            if (!empty($ConnectToPanel['error'])) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $ConnectToPanel['error']
                );
            }
            $data_Output = json_decode($ConnectToPanel['body'], true);
            if (!empty($data_Output['detail']) && $data_Output['detail']) {
                $Output['status'] = 'Unsuccessful';
                if ($data_Output['detail']) {
                    $Output['msg'] = $data_Output['detail'];
                } else {
                    $Output['msg'] = '';
                }
            } else {
                if (!preg_match('/^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(:\d+)?((\/[^\s\/]+)+)?$/', $data_Output['subscription_url'])) {
                    $data_Output['subscription_url'] = $Get_Data_Panel['url_panel'] . "/" . ltrim($data_Output['subscription_url'], "/");
                }
                if ($new_marzban) {
                    $out_put_link = outputlunk($data_Output['subscription_url']);
                    if (isBase64($out_put_link)) {
                        $data_Output['links'] = base64_decode(outputlunk($data_Output['subscription_url']));
                    }
                    $data_Output['links'] = explode("\n", $data_Output['links']);
                }
                if ($inoice != false) {
                    $data_Output['subscription_url'] = buildSubscriptionUrl($inoice['id_invoice'], $inoice['id_user'], $domainhosts);
                }
                $Output['status'] = 'successful';
                $Output['username'] = $data_Output['username'];
                $Output['subscription_url'] = $data_Output['subscription_url'];
                $Output['configs'] = $data_Output['links'];
            }
        } elseif ($Get_Data_Panel['type'] == "x-ui_single") {
            $subId = bin2hex(random_bytes(8));
            if (isset($Get_Data_Product['inbounds']) and $Get_Data_Product['inbounds'] != null) {
                $inbounds = $Get_Data_Product['inbounds'];
            } else {
                $inbounds = $Get_Data_Panel['inboundid'];
            }
            $data_Output = addClient($Get_Data_Panel['name_panel'], $usernameC, $expire, $data_limit, generateUUID(), "", $subId, $inbounds, $Get_Data_Product['name_product'], $note);
            if (!empty($data_Output['error'])) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $data_Output['error']
                );
            } elseif (!empty($data_Output['status']) && $data_Output['status'] != 200) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $data_Output['status']
                );
            } else {
                $data_Output = json_decode($data_Output['body'], true);
                if (!$data_Output['success']) {
                    $Output['status'] = 'Unsuccessful';
                    $Output['msg'] = $data_Output['msg'];
                } else {
                    $links_user = outputlunk($Get_Data_Panel['linksubx'] . "/{$subId}");
                    if (isBase64($links_user)) {
                        $links_user = base64_decode($links_user);
                    }
                    $links_user = explode("\n", trim($links_user));
                    $Output['status'] = 'successful';
                    $Output['username'] = $usernameC;
                    $Output['subscription_url'] = $Get_Data_Panel['linksubx'] . "/{$subId}";
                    $Output['configs'] = $links_user;
                    if ($inoice != false) {
                        $Output['subscription_url'] = buildSubscriptionUrl($inoice['id_invoice'], $inoice['id_user'], $domainhosts);
                    }
                }
            }
        } elseif ($Get_Data_Panel['type'] == "Manualsale") {
            $statement = $pdo->prepare("SELECT * FROM manualsell WHERE codepanel = :code_panel AND status = 'active' AND codeproduct = '$code_product' ORDER BY RAND() LIMIT 1");
            $statement->execute(array(':code_panel' => $Get_Data_Panel['code_panel']));
            $configman = $statement->fetch(PDO::FETCH_ASSOC);
            $Output['status'] = 'successful';
            $Output['username'] = $usernameC;
            $Output['subscription_url'] = $configman['contentrecord'];
            $Output['configs'] = "";
            update("manualsell", "status", "selled", "id", $configman['id']);
            update("manualsell", "username", $usernameC, "id", $configman['id']);
        } else {
            $Output['status'] = 'Unsuccessful';
            $Output['msg'] = 'Panel Not Found';
        }
        return $Output;
    }
    function DataUser($name_panel, $username)
    {
        $Output = array();
        global $pdo, $domainhosts, $new_marzban;
        $Get_Data_Panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        if (!$Get_Data_Panel || !is_array($Get_Data_Panel)) {
            return array(
                'status' => 'Unsuccessful',
                'msg' => 'Panel Not Found'
            );
        }
        if (isset($Get_Data_Panel['subvip']) && $Get_Data_Panel['subvip'] == "onsubvip") {
            $inoice = select("invoice", "*", "username", $username, "select");
        } else {
            $inoice = false;
        }
        if ($Get_Data_Panel['type'] == "marzban") {
            $UsernameData = getuser($username, $Get_Data_Panel['name_panel']);
            if (!empty($UsernameData['error'])) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['error']
                );
            } elseif (!empty($UsernameData['status']) && $UsernameData['status'] == 500) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['status']
                );
            } else {
                $UsernameData = json_decode($UsernameData['body'], true);
                if (!empty($UsernameData['detail'])) {
                    return array(
                        'status' => 'Unsuccessful',
                        'msg' => $UsernameData['detail']
                    );
                }
                if (!preg_match('/^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(:\d+)?((\/[^\s\/]+)+)?$/', $UsernameData['subscription_url'])) {
                    $UsernameData['subscription_url'] = $Get_Data_Panel['url_panel'] . "/" . ltrim($UsernameData['subscription_url'], "/");
                }
                if ($new_marzban) {
                    $UsernameData['expire'] = strtotime($UsernameData['expire']);
                    $UsernameData['links'] = base64_decode(outputlunk($UsernameData['subscription_url']));
                    $UsernameData['links'] = explode("\n", $UsernameData['links']);
                    $sublist_update = get_list_update($name_panel, $username);
                    if (!empty($sublist_update['error'])) {
                        return array(
                            'status' => 'Unsuccessful',
                            'msg' => $sublist_update['error']
                        );
                    } elseif (!empty($sublist_update['status']) && $sublist_update['status'] == 500) {
                        return array(
                            'status' => 'Unsuccessful',
                            'msg' => $sublist_update['status']
                        );
                    }
                    $sublist_update_body = json_decode($sublist_update['body'], true);
                    if (!empty($sublist_update_body['updates']) && is_array($sublist_update_body['updates'])) {
                        $first_update = $sublist_update_body['updates'][0];
                        $UsernameData['sub_updated_at'] = isset($first_update['created_at']) ? $first_update['created_at'] : null;
                        $UsernameData['sub_last_user_agent'] = isset($first_update['user_agent']) ? $first_update['user_agent'] : null;
                    } else {
                        $UsernameData['sub_updated_at'] = isset($UsernameData['sub_updated_at']) ? $UsernameData['sub_updated_at'] : null;
                        $UsernameData['sub_last_user_agent'] = isset($UsernameData['sub_last_user_agent']) ? $UsernameData['sub_last_user_agent'] : null;
                    }
                } else {
                    $UsernameData['expire'] = $UsernameData['expire'];
                }
                if ($inoice != false) {
                    $UsernameData['subscription_url'] = buildSubscriptionUrl($inoice['id_invoice'], $inoice['id_user'], $domainhosts);
                }
                if ($new_marzban) {
                    $UsernameData['proxies'] = isset($UsernameData['proxy_settings']) ? $UsernameData['proxy_settings'] : null;
                }
                $Output = array(
                    'status' => $UsernameData['status'],
                    'username' => $UsernameData['username'],
                    'data_limit' => $UsernameData['data_limit'],
                    'expire' => $UsernameData['expire'],
                    'online_at' => $UsernameData['online_at'],
                    'used_traffic' => $UsernameData['used_traffic'],
                    'links' => $UsernameData['links'],
                    'subscription_url' => $UsernameData['subscription_url'],
                    'sub_updated_at' => $UsernameData['sub_updated_at'],
                    'sub_last_user_agent' => $UsernameData['sub_last_user_agent'],
                    'uuid' => $UsernameData['proxies'],
                    'data_limit_reset' => $UsernameData['data_limit_reset_strategy']
                );
            }
        } elseif ($Get_Data_Panel['type'] == "x-ui_single") {
            $user_data = get_clinets($username, $Get_Data_Panel['name_panel']);
            if (!empty($user_data['error'])) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $user_data['error']
                );
            } elseif (!empty($user_data['status']) && $user_data['status'] != 200) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => json_encode($user_data)
                );
            }
            $user_data = json_decode($user_data['body'], true);

            if (!is_array($user_data)) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => 'object invalid'
                );
            }
            if (empty($user_data['obj'])) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => "User not found"
                );
            }
            $user_data = $user_data['obj'];
            $expire = $user_data['expiryTime'] / 1000;
            if ($user_data['enable']) {
                $user_data['enable'] = "active";
            } else {
                $user_data['enable'] = "disabled";
            }
            if ((intval($user_data['total'])) != 0) {
                if ((intval($user_data['total']) - ($user_data['up'] + $user_data['down'])) <= 0)
                    $user_data['enable'] = "limited";
            }
            if (intval($user_data['expiryTime']) != 0) {
                if ($expire - time() <= 0)
                    $user_data['enable'] = "expired";
            }
            if ($user_data['expiryTime'] < -10000) {
                $user_data['enable'] = "on_hold";
                $expire = 0;
            }
            $linksub = $Get_Data_Panel['linksubx'] . "/{$user_data['subId']}";
            $links_user = outputlunk($Get_Data_Panel['linksubx'] . "/{$user_data['subId']}");
            if (isBase64($links_user))
                $links_user = base64_decode($links_user);
            $links_user = explode("\n", trim($links_user));
            if ($inoice != false)
                $linksub = buildSubscriptionUrl($inoice['id_invoice'], $inoice['id_user'], $domainhosts);
            $user_data['lastOnline'] = $user_data['lastOnline'] == 0 ? "offline" : (new DateTime('@' . ($user_data['lastOnline'] / 1000)))->format('Y-m-d H:i:s');
            $Output = array(
                'status' => $user_data['enable'],
                'username' => $user_data['email'],
                'data_limit' => $user_data['total'],
                'expire' => $expire,
                'online_at' => $user_data['lastOnline'],
                'used_traffic' => $user_data['up'] + $user_data['down'],
                'links' => $links_user,
                'subscription_url' => $linksub,
                'sub_updated_at' => null,
                'sub_last_user_agent' => null,
            );

        } elseif ($Get_Data_Panel['type'] == "Manualsale") {
            $stmt = $pdo->prepare("SELECT * FROM manualsell WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $configman = $stmt->fetch(PDO::FETCH_ASSOC);
            $service = select("invoice", "*", "username", $username, "select");
            $Output = array(
                'status' => $service['Status'],
                'username' => $service['username'],
                'data_limit' => null,
                'expire' => $service['time_sell'],
                'online_at' => null,
                'used_traffic' => null,
                'links' => [],
                'subscription_url' => $configman['contentrecord'],
                'sub_updated_at' => null,
                'sub_last_user_agent' => null,
                'uuid' => null
            );
        } else {
            $Output = array(
                'status' => 'Unsuccessful',
                'msg' => 'Panel Not Found'
            );
        }
        return $Output;
    }
    function Revoke_sub($name_panel, $username)
    {
        $Output = array();
        $ManagePanel = new ManagePanel();
        $Get_Data_Panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        if ($Get_Data_Panel['type'] == "marzban") {
            $revoke_sub = revoke_sub($username, $name_panel);
            if (isset($revoke_sub['detail']) && $revoke_sub['detail']) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $revoke_sub['detail']
                );
            } else {
                $config = new ManagePanel();
                $Data_User = $config->DataUser($name_panel, $username);
                if (!preg_match('/^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(:\d+)?((\/[^\s\/]+)+)?$/', $Data_User['subscription_url'])) {
                    $Data_User['subscription_url'] = $Get_Data_Panel['url_panel'] . "/" . ltrim($Data_User['subscription_url'], "/");
                }
                $Output = array(
                    'status' => 'successful',
                    'configs' => $Data_User['links'],
                    'subscription_url' => $Data_User['subscription_url']
                );
            }
        } elseif ($Get_Data_Panel['type'] == "x-ui_single") {
            $subId = bin2hex(random_bytes(8));
            $config = array(
                'settings' => json_encode(
                    array(
                        'clients' => array(
                            array(
                                "id" => generateUUID(),
                                "enable" => true,
                                "subId" => $subId,
                            )
                        ),
                    )
                )
            );
            $updateinbound = $ManagePanel->Modifyuser($username, $Get_Data_Panel['name_panel'], $config);
            if (!$updateinbound['status']) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => 'Unsuccessful'
                );
            } else {
                $Output = array(
                    'status' => 'successful',
                    'configs' => [outputlunk($Get_Data_Panel['linksubx'] . "/{$subId}")],
                    'subscription_url' => $Get_Data_Panel['linksubx'] . "/{$subId}",
                );
            }
        } else {
            $Output = array(
                'status' => 'Unsuccessful',
                'msg' => 'Panel Not Found'
            );
        }
        return $Output;
    }
    function RemoveUser($name_panel, $username)
    {
        $Output = array();
        $Get_Data_Panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        if ($Get_Data_Panel['type'] == "marzban") {
            $UsernameData = removeuser($Get_Data_Panel['name_panel'], $username);
            if (!empty($UsernameData['status']) && $UsernameData['status'] != 200) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['status']
                );
            } elseif (!empty($UsernameData['error'])) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['error']
                );
            }
            $UsernameData = json_decode($UsernameData['body'], true);
            if ($UsernameData['detail'] != "User successfully deleted") {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['detail']
                );
            } else {
                $Output = array(
                    'status' => 'successful',
                    'username' => $username,
                );
            }
        } elseif ($Get_Data_Panel['type'] == "x-ui_single") {
            $UsernameData = removeClient($Get_Data_Panel['name_panel'], $username);
            if (!empty($UsernameData['status']) && $UsernameData['status'] != 200) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['status']
                );
            } elseif (!empty($UsernameData['error'])) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['error']
                );
            }
            $UsernameData = json_decode($UsernameData['body'], true);
            if (!$UsernameData['success']) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['msg']
                );
            } else {
                $Output = array(
                    'status' => 'successful',
                    'username' => $username,
                );
            }
        } elseif ($Get_Data_Panel['type'] == "Manualsale") {
            update("manualsell", "status", "delete", "username", $username);
            $Output = array(
                'status' => 'successful',
                'username' => $username,
            );
        } else {
            $Output = array(
                'status' => 'Unsuccessful',
                'msg' => 'Panel Not Found'
            );
        }
        return $Output;
    }
    function Modifyuser($username, $name_panel, $config = array())
    {
        global $new_marzban;
        $Output = array();
        $Get_Data_Panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        if ($Get_Data_Panel['type'] == "marzban") {
            if ($new_marzban) {
                $result = getuser($username, $name_panel);
                $result = json_decode($result['body'], true);
                $config['proxy_settings'] = $result['proxy_settings'];
            }
            $modify = Modifyuser($name_panel, $username, $config);
            if (!empty($modify['error'])) {
                return array(
                    'status' => false,
                    'msg' => $modify['error']
                );
            } elseif (!empty($modify['status']) && $modify['status'] == 500) {
                return array(
                    'status' => false,
                    'msg' => 'error code : ' . $modify['status']
                );
            }
            $modifycheck = json_decode($modify['body'], true);
            if (!empty($modifycheck['detail'])) {
                return array(
                    'status' => false,
                    'msg' => $modifycheck['detail']
                );
            }
            return array(
                'status' => true,
                'data' => $modify
            );
        } elseif ($Get_Data_Panel['type'] == "x-ui_single") {
            $clients = get_clinets($username, $name_panel);
            if (!empty($clients['error'])) {
                return array(
                    'status' => false,
                    'msg' => $clients['error']
                );
            } elseif (!empty($clients['status']) && $clients['status'] != 200) {
                return array(
                    'status' => false,
                    'msg' => json_encode($clients)
                );
            }
            $clients = json_decode($clients['body'], true);
            if (!is_array($clients)) {
                return array(
                    'status' => false,
                    'msg' => 'object invalid'
                );
            }
            if (empty($clients['obj'])) {
                return array(
                    'status' => false,
                    'msg' => "User not found"
                );
            }
            $clients = $clients['obj'];
            $configs = array(
                'id' => intval($clients['inboundId']),
                'settings' => json_encode(
                    array(
                        'clients' => array(
                            array(
                                "id" => $clients['uuid'],
                                "flow" => "",
                                "email" => $clients['email'],
                                "totalGB" => $clients['total'],
                                "expiryTime" => $clients['expiryTime'],
                                "enable" => true,
                                "subId" => $clients['subId'],
                            )
                        ),
                        'decryption' => 'none',
                        'fallbacks' => array(),
                    )
                ),
            );
            $configs['settings'] = json_encode(array_replace_recursive(json_decode($configs['settings'], true), json_decode($config['settings'], true)));
            $modify = updateClient($Get_Data_Panel['name_panel'], $clients['uuid'], $configs);
            if (!empty($modify['error'])) {
                return array(
                    'status' => false,
                    'msg' => $modify['error']
                );
            } elseif (!empty($modify['status']) && $modify['status'] != 200) {
                return array(
                    'status' => false,
                    'msg' => 'error code : ' . $modify['status']
                );
            }
            $modify = json_decode($modify['body'], true);
            if (!$modify['success']) {
                return array(
                    'status' => false,
                    'msg' => 'error :' . $modify['msg']
                );
            }
            return array(
                'status' => true,
                'data' => $modify
            );
        }
    }
    function Change_status($username, $name_panel)
    {
        $ManagePanel = new ManagePanel();
        $DataUserOut = $ManagePanel->DataUser($name_panel, $username);
        $Get_Data_Panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        if ($DataUserOut['status'] == "Unsuccessful") {
            $Output = array(
                'status' => 'Unsuccessful',
                'msg' => $DataUserOut['detail']
            );
            return;
        }
        if (!in_array($DataUserOut['status'], ["active", "disabled"])) {
            $Output = array(
                'status' => 'Unsuccessful',
                'msg' => "status invalid"
            );
            return;
        }
        if ($Get_Data_Panel['type'] == "marzban") {
            if ($DataUserOut['status'] == "active") {
                $status = "disabled";
            } else {
                $status = "active";
            }
            $configs = array("status" => $status);
            $ManagePanel->Modifyuser($username, $name_panel, $configs);
            $Output = array(
                'status' => 'successful',
                'msg' => null
            );
        } elseif ($Get_Data_Panel['type'] == "x-ui_single") {
            if ($DataUserOut['status'] == "active") {
                $status = false;
            } else {
                $status = true;
            }
            $configs = array(
                'settings' => json_encode(array(
                    'clients' => array(
                        array(
                            "enable" => $status,
                        )
                    ),
                )),
            );
            $ManagePanel->Modifyuser($username, $name_panel, $configs);
            $Output = array(
                'status' => 'successful',
                'msg' => null
            );
        }

        return $Output;
    }
    function ResetUserDataUsage($username, $name_panel)
    {
        $panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        if ($panel == false) {
            return array(
                'status' => false,
                'msg' => 'data not found'
            );
        }
        if ($panel['type'] == "marzban") {
            $reset = ResetUserDataUsage($username, $panel['name_panel']);
            if (!empty($reset['status']) && $reset['status'] != 200) {
                return array(
                    'status' => false,
                    'msg' => 'error code : ' . $reset['status']
                );
            } elseif (!empty($reset['error'])) {
                return array(
                    'status' => false,
                    'msg' => 'error  : ' . $reset['error']
                );
            }
            $reset = json_decode($reset['body'], true);
            if (!empty($reset['detail'])) {
                return array(
                    'status' => false,
                    'msg' => $reset['detail']
                );
            }
            return array(
                'status' => true,
                'msg' => 'successful'
            );
        } elseif ($panel['type'] == 'x-ui_single') {
            $reset = ResetUserDataUsagex_uisin($username, $panel['name_panel']);
            if (!empty($reset['status']) && $reset['status'] != 200) {
                return array(
                    'status' => false,
                    'msg' => 'error code : ' . $reset['status']
                );
            } elseif (!empty($reset['error'])) {
                return array(
                    'status' => false,
                    'msg' => 'error  : ' . $reset['error']
                );
            }
            $reset = json_decode($reset['body'], true);
            if (!$reset['success']) {
                return array(
                    'status' => false,
                    'msg' => 'error :' . $reset['msg']
                );
            }
            return array(
                'status' => true,
                'data' => $reset
            );
        }
    }
    function extend($Method_extend, $new_limit, $time_day, $username, $code_product, $name_panel)
    {
        $panel = select("marzban_panel", "*", "code_panel", $name_panel, "select");
        $product = select("product", "*", "code_product", $code_product, "select");
        $invoice = select("invoice", "*", "username", $username, "select");
        if ($code_product == "custom_volume")
            $product = true;
        if ($panel == false || $product == false) {
            return array(
                'status' => false,
                'msg' => 'data not found'
            );
        }
        $data_user = $this->DataUser($panel['name_panel'], $username);
        if ($data_user['status'] == "Unsuccessful") {
            return array(
                'status' => false,
                'msg' => $data_user['msg']
            );
        }
        $notifctions = json_encode(array(
            'volume' => false,
            'time' => false,
        ));
        update("invoice", "notifctions", $notifctions, 'id_invoice', $invoice['id_invoice']);
        $data_limit_old = $data_user['data_limit'];
        $time_old = $data_user['expire'];
        $time_old = time() - $time_old > 0 ? time() : $time_old;
        $data_limit_new = $new_limit == 0 ? 0 : $new_limit * pow(1024, 3);
        $data_limit_new_add = $new_limit == 0 ? 0 : $data_limit_old + ($new_limit * pow(1024, 3));
        $time_new = $time_day == 0 ? 0 : time() + $time_day * 86400;
        $time_old = $time_old == 0 ? time() : $time_old;
        $time_new_add = $time_day == 0 ? 0 : $time_old + ($time_day * 86400);
        //inboud and proxies 
        $inbound_id = isset($panel['inboundid']) ? $panel['inboundid'] : 1;
        $inbounds = is_string($panel['inbounds']) ? json_decode($panel['inbounds']) : "{}";
        $inbounds = $product['inbounds'] != null ? json_decode($product['inbounds']) : $inbounds;
        update("invoice", 'user_info', null, "username", $username);
        update("invoice", 'uuid', null, "username", $username);
        update("invoice", 'Status', "active", "username", $username);
        if ($Method_extend == "ریست حجم و زمان") {
            $reset = $this->ResetUserDataUsage($username, $panel['name_panel']);
            if ($reset['status'] == false) {
                return array(
                    'status' => false,
                    'msg' => 'error reset : ' . $reset['msg']
                );
            }
        } elseif ($Method_extend == "اضافه شدن زمان و حجم به ماه بعد") {
            $data_limit_new = $data_limit_new_add;
            $time_new = $time_new_add;
        } elseif ($Method_extend == "ریست زمان و اضافه کردن حجم قبلی") {
            $data_limit_new = $data_limit_new_add;
        } elseif ($Method_extend == "ریست شدن حجم و اضافه شدن زمان") {
            $reset = $this->ResetUserDataUsage($username, $panel['name_panel']);
            if ($reset['status'] == false) {
                return array(
                    'status' => false,
                    'msg' => 'error reset : ' . $reset['msg']
                );
            }
            $time_new = $time_new_add;
        } elseif ($Method_extend == "اضافه شدن زمان و تبدیل حجم کل به حجم باقی مانده") {
            $reset = $this->ResetUserDataUsage($username, $panel['name_panel']);
            if ($reset['status'] == false) {
                return array(
                    'status' => false,
                    'msg' => 'error reset : ' . $reset['msg']
                );
            }
            $time_new = $time_new_add;
            $data_limit_last = $data_user['data_limit'] - $data_user['used_traffic'];
            $data_limit_last = $data_limit_last < 0 ? 0 : $data_limit_last;
            $data_limit_new = $data_limit_new + $data_limit_last;
        }
        if ($panel['type'] == "marzban") {
            $data = array(
                'data_limit' => $data_limit_new,
                'expire' => $time_new,
                'inbounds' => $inbounds,
            );
            if ($invoice != false && $invoice['uuid'] != null) {
                $data['proxies'] = json_decode($invoice['uuid'], true);
            }
        } elseif ($panel['type'] == "x-ui_single") {
            $data = array(
                'settings' => json_encode(
                    array(
                        'clients' => array(
                            array(
                                "totalGB" => $data_limit_new,
                                "expiryTime" => $time_new * 1000,
                                "enable" => true,
                            )
                        ),
                        'decryption' => 'none',
                        'fallbacks' => array(),
                    )
                ),
            );
        }
        $extend = $this->Modifyuser($username, $panel['name_panel'], $data);
        if ($extend['status'] == false) {
            return array(
                'status' => false,
                'msg' => $extend['msg']
            );
        }
        return $extend;
    }
    function extra_volume($username_account, $code_panel, $limit_volume_new)
    {
        $panel = select("marzban_panel", "*", "code_panel", $code_panel, "select");
        $invoice = select("invoice", "*", "username", $username_account, "select");
        if ($panel == false) {
            return array(
                'status' => false,
                'msg' => 'data not found'
            );
        }
        $notif_value = json_decode($invoice['notifctions'], true);
        $notifctions = json_encode(array(
            'volume' => false,
            'time' => $notif_value['time'],
        ));
        update("invoice", "notifctions", $notifctions, 'id_invoice', $invoice['id_invoice']);
        $user_info = $this->DataUser($panel['name_panel'], $username_account);
        if ($user_info['status'] == "Unsuccessful") {
            return array(
                'status' => false,
                'msg' => $user_info['msg']
            );
        }
        $old_limit_volume = $user_info['data_limit'];
        $new_limit = $limit_volume_new == 0 ? 0 : ($limit_volume_new * pow(1024, 3)) + $old_limit_volume;
        $inbound_id = isset($panel['inboundid']) ? $panel['inboundid'] : 1;
        $inbounds = is_string($panel['inbounds']) ? json_decode($panel['inbounds']) : "{}";
        update("invoice", 'user_info', null, "username", $username_account);
        update("invoice", 'uuid', null, "username", $username_account);
        update("invoice", 'Status', "active", "username", $username_account);
        if ($panel['type'] == "marzban") {
            $data = array(
                'data_limit' => $new_limit,
                'inbounds' => $inbounds,
            );
            if ($invoice != false && $invoice['uuid'] != null) {
                $data['proxies'] = json_decode($invoice['uuid'], true);
            }
        } elseif ($panel['type'] == "x-ui_single") {
            $data = array(
                'settings' => json_encode(
                    array(
                        'clients' => array(
                            array(
                                "totalGB" => $new_limit,
                            )
                        ),
                    )
                ),
            );
        }
        $extra_volume = $this->Modifyuser($username_account, $panel['name_panel'], $data);
        if ($extra_volume['status'] == false) {
            return array(
                'status' => false,
                'msg' => $extra_volume['msg']
            );
        }
        return $extra_volume;
    }
    function extra_time($username_account, $code_panel, $limit_time_new)
    {
        $panel = select("marzban_panel", "*", "code_panel", $code_panel, "select");
        $invoice = select("invoice", "*", "username", $username_account, "select");
        if ($panel == false) {
            return array(
                'status' => false,
                'msg' => 'data not found'
            );
        }
        $notif_value = json_decode($invoice['notifctions'], true);
        $notifctions = json_encode(array(
            'volume' => $notif_value['volume'],
            'time' => false,
        ));
        update("invoice", "notifctions", $notifctions, 'id_invoice', $invoice['id_invoice']);
        $user_info = $this->DataUser($panel['name_panel'], $username_account);
        if ($user_info['status'] == "Unsuccessful") {
            return array(
                'status' => false,
                'msg' => $user_info['msg']
            );
        }
        $old_limit_time = $user_info['expire'];
        $old_limit_time = time() - $old_limit_time > 0 ? time() : $old_limit_time;
        $new_limit = $limit_time_new == 0 ? 0 : $limit_time_new * 86400 + $old_limit_time;
        $inbound_id = isset($panel['inboundid']) ? $panel['inboundid'] : 1;
        $inbounds = is_string($panel['inbounds']) ? json_decode($panel['inbounds']) : "{}";
        update("invoice", 'user_info', null, "username", $username_account);
        update("invoice", 'uuid', null, "username", $username_account);
        update("invoice", 'Status', "active", "username", $username_account);
        if ($panel['type'] == "marzban") {
            $data = array(
                'expire' => $new_limit,
                'inbounds' => $inbounds,
            );
            if ($invoice != false && $invoice['uuid'] != null) {
                $data['proxies'] = json_decode($invoice['uuid'], true);
            }
        } elseif ($panel['type'] == "x-ui_single") {
            $new_limit = $new_limit * 1000;
            $data = array(
                'settings' => json_encode(
                    array(
                        'clients' => array(
                            array(
                                "expiryTime" => $new_limit,
                            )
                        ),
                    )
                ),
            );
        }
        $extra_time = $this->Modifyuser($username_account, $panel['name_panel'], $data);
        if ($extra_time['status'] == false) {
            return array(
                'status' => false,
                'msg' => $extra_time['msg']
            );
        }
        return $extra_time;
    }
}
