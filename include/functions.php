<?php

/*
 * ==========================================================
 * FUNCTIONS.PHP
 * ==========================================================
 * Main PHP functions file. © 2017-2022 
 */

define("SB_VERSION", "2.28");

if (!defined("SB_PATH")) {
    $path = dirname(__DIR__, 1);
    define("SB_PATH", $path ? $path : dirname(__DIR__));
}
if (!defined("JSON_INVALID_UTF8_IGNORE")) {
    define("JSON_INVALID_UTF8_IGNORE", 0);
}


require_once SB_PATH . "/config.php";


global $SB_CONNECTION;
global $SB_SETTINGS;
global $SB_LOGIN;
global $SB_LANGUAGE;
global $SB_TRANSLATIONS;
const SELECT_FROM_USERS = "SELECT id, first_name, last_name, email, profile_image, user_type, creation_time, last_activity, department, token";

class SBError
{
    public $error;

    function __construct($error_code, $function = "", $message = "")
    {
        $this->error = [
            "message" => $message,
            "function" => $function,
            "code" => $error_code,
        ];
    }

    public function __toString()
    {
        return $this->code() . " " . $this->message();
    }

    function message()
    {
        return $this->error["message"];
    }

    function code()
    {
        return $this->error["code"];
    }

    function function_name()
    {
        return $this->error["function"];
    }
}

class SBValidationError
{
    public $error;

    function __construct($error_code)
    {
        $this->error = $error_code;
    }

    public function __toString()
    {
        return $this->error;
    }

    function code()
    {
        return $this->error;
    }
}


$sb_apps = [
    // "dialogflow",
    // "tickets",
    "messenger",
    "whatsapp",
    "whatsmeow",
    "waweb",
    "telegram",
    "twitter",
    "gbm",
];
for ($i = 0; $i < count($sb_apps); $i++) {
    $file = SB_PATH . "/apps/" . $sb_apps[$i] . "/functions.php";
    if (file_exists($file)) {
        require_once $file;
    }
}



/*
 * -----------------------------------------------------------
 * DATABASE
 * -----------------------------------------------------------
 *
 * 1. Connection to the database
 * 2. Get database values
 * 3. Insert or update database values
 * 4. Escape and sanatize values prior to databse insertion
 * 5. Escape a JSON string prior to databse insertion
 * 6. Set default database environment settings
 * 7. Database error function
 *
 */

function sb_db_connect()
{
    global $SB_CONNECTION;
    if (!defined("SB_DB_NAME") || !SB_DB_NAME) {
        return false;
    }
    if ($SB_CONNECTION) {
        sb_db_init_settings();
        return true;
    }
    $SB_CONNECTION = new mysqli(
        SB_DB_HOST,
        SB_DB_USER,
        SB_DB_PASSWORD,
        SB_DB_NAME,
        defined("SB_DB_PORT") && SB_DB_PORT
            ? intval(SB_DB_PORT)
            : ini_get("mysqli.default_port")
    );
    if ($SB_CONNECTION->connect_error) {
        echo "Connection error. Visit the admin area for more details or open the config.php file and check the database information. Message: " .
            $SB_CONNECTION->connect_error .
            ".";
        return false;
    }
    sb_db_init_settings();
    return true;
}

function sb_db_get($query, $single = true)
{
    global $SB_CONNECTION;
    $status = sb_db_connect();
    $value = $single ? "" : [];
    if ($status) {
        $result = $SB_CONNECTION->query($query);
        if ($result) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    if ($single) {
                        $value = $row;
                    } else {
                        array_push($value, $row);
                    }
                }
            }
        } else {
            return sb_db_error("sb_db_get");
        }
    } else {
        return $status;
    }
    return $value;
}

function sb_db_query($query, $return = false)
{
    global $SB_CONNECTION;
    $status = sb_db_connect();
    if ($status) {
        $result = $SB_CONNECTION->query($query);
        if ($result) {
            if ($return) {
                if (
                    isset($SB_CONNECTION->insert_id) &&
                    $SB_CONNECTION->insert_id > 0
                ) {
                    return $SB_CONNECTION->insert_id;
                } else {
                    return sb_db_error("sb_db_query");
                }
            } else {
                return true;
            }
        } else {
            return sb_db_error("sb_db_query");
        }
    } else {
        return $status;
    }
}

function sb_db_escape($value, $numeric = -1)
{
    if (is_numeric($value)) {
        return $value;
    } elseif ($numeric === true) {
        return false;
    }
    global $SB_CONNECTION;
    sb_db_connect();
    if ($SB_CONNECTION) {
        $value = $SB_CONNECTION->real_escape_string($value);
    }
    $value = str_replace(['\"', '"'], ['"', '\"'], $value);
    $value = str_replace(
        ["<script", "</script"],
        ["&lt;script", "&lt;/script"],
        $value
    );
    $value = str_replace(["javascript:", "onclick=", "onerror="], "", $value);
    $value = htmlspecialchars($value, ENT_NOQUOTES | ENT_SUBSTITUTE, "utf-8");
    return $value;
}

function sb_db_json_escape($array)
{
    global $SB_CONNECTION;
    sb_db_connect();
    $value = str_replace(
        ['"false"', '"true"'],
        ["false", "true"],
        json_encode($array, JSON_INVALID_UTF8_IGNORE)
    );
    $value = str_replace(
        ["<script", "</script"],
        ["&lt;script", "&lt;/script"],
        $value
    );
    $value = str_replace(["javascript:", "onclick=", "onerror="], "", $value);
    return $SB_CONNECTION ? $SB_CONNECTION->real_escape_string($value) : $value;
}

function sb_json_escape($value)
{
    return str_replace(['"', "\'"], ['\"', "'"], $value);
}

function sb_db_error($function)
{
    global $SB_CONNECTION;
    return new SBError("db-error", $function, $SB_CONNECTION->error);
}

function sb_db_check_connection(
    $name = false,
    $user = false,
    $password = false,
    $host = false,
    $port = false
) {
    global $SB_CONNECTION;
    $response = true;
    if ($name === false && defined("SB_DB_NAME")) {
        $name = SB_DB_NAME;
        $user = SB_DB_USER;
        $password = SB_DB_PASSWORD;
        $host = SB_DB_HOST;
        $port =
            defined("SB_DB_PORT") && SB_DB_PORT ? intval(SB_DB_PORT) : false;
    }
    if ($name === false || $name == "") {
        return "installation";
    }
    try {
        set_error_handler(function () {
        }, E_ALL);
        $SB_CONNECTION = new mysqli(
            $host,
            $user,
            $password,
            $name,
            $port === false ? ini_get("mysqli.default_port") : intval($port)
        );
        sb_db_init_settings();
    } catch (Exception $e) {
        $response = $e->getMessage();
    }
    if ($SB_CONNECTION->connect_error) {
        $response = $SB_CONNECTION->connect_error;
    }
    restore_error_handler();
    return $response;
}

function sb_db_init_settings()
{

    global $SB_CONNECTION;
    $SB_CONNECTION->set_charset("utf8mb4");
    $SB_CONNECTION->query(
        "SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))"
    );
}

function sb_external_db($action, $name, $query = "", $extra = false)
{
    $NAME = strtoupper($name);
    $name = strtolower($name);
    switch ($action) {
        case "connect":
            $connection = sb_isset($GLOBALS, "SB_" . $NAME . "_CONNECTION");
            $defined = defined("SB_" . $NAME . "_DB_NAME");
            if (!empty($connection) && $connection->ping()) {
                return true;
            }
            if (!$defined) {
                $prefix = "";
                $database = sb_get_setting($name . "-db");
                if (empty($database[$name . "-db-name"])) {
                    return new SBError(
                        "db-error",
                        "sb_external_db",
                        "Missing database details in " .
                            $name .
                            " settings area."
                    );
                }
                define(
                    "SB_" . $NAME . "_DB_HOST",
                    $database[$name . "-db-host"]
                );
                define(
                    "SB_" . $NAME . "_DB_USER",
                    $database[$name . "-db-user"]
                );
                define(
                    "SB_" . $NAME . "_DB_PASSWORD",
                    $database[$name . "-db-password"]
                );
                define(
                    "SB_" . $NAME . "_DB_NAME",
                    $database[$name . "-db-name"]
                );
                if ($name == "perfex" || $name == "whmcs") {
                    define(
                        "SB_" . $NAME . "_DB_PREFIX",
                        empty($database[$name . "-db-prefix"])
                            ? "tbl"
                            : $database[$name . "-db-prefix"]
                    );
                    $prefix =
                        PHP_EOL .
                        'define(\'SB_' .
                        $NAME .
                        '_DB_PREFIX\', \'' .
                        sb_isset($database, $name . "-db-prefix", "tbl") .
                        '\');';
                }
                sb_write_config_extra(
                    "/* " .
                        $NAME .
                        " CRM  */" .
                        PHP_EOL .
                        'define(\'SB_' .
                        $NAME .
                        '_DB_HOST\', \'' .
                        $database[$name . "-db-host"] .
                        '\');' .
                        PHP_EOL .
                        'define(\'SB_' .
                        $NAME .
                        '_DB_USER\', \'' .
                        $database[$name . "-db-user"] .
                        '\');' .
                        PHP_EOL .
                        'define(\'SB_' .
                        $NAME .
                        '_DB_PASSWORD\', \'' .
                        $database[$name . "-db-password"] .
                        '\');' .
                        PHP_EOL .
                        'define(\'SB_' .
                        $NAME .
                        '_DB_NAME\', \'' .
                        $database[$name . "-db-name"] .
                        '\');' .
                        $prefix
                );
            }
            $connection = new mysqli(
                constant("SB_" . $NAME . "_DB_HOST"),
                constant("SB_" . $NAME . "_DB_USER"),
                constant("SB_" . $NAME . "_DB_PASSWORD"),
                constant("SB_" . $NAME . "_DB_NAME")
            );

            if ($connection->connect_error) {
                if ($defined) {
                    $database = sb_get_setting($name . "-db");
                    if (
                        constant("SB_" . $NAME . "_DB_HOST") !=
                        $database[$name . "-db-host"] ||
                        constant("SB_" . $NAME . "_DB_USER") !=
                        $database[$name . "-db-user"] ||
                        constant("SB_" . $NAME . "_DB_PASSWORD") !=
                        $database[$name . "-db-password"] ||
                        constant("SB_" . $NAME . "_DB_NAME") !=
                        $database[$name . "-db-name"] ||
                        (defined("SB_" . $NAME . "_DB_PREFIX") &&
                            constant("SB_" . $NAME . "_DB_PREFIX") !=
                            $database[$name . "-db-prefix"])
                    ) {
                        $raw = file_get_contents(SB_PATH . "/config.php");
                        sb_file(
                            SB_PATH . "/config.php",
                            str_replace(
                                [
                                    "/* Perfex CRM  */",
                                    'define(\'SB_' .
                                        $NAME .
                                        '_DB_HOST\', \'' .
                                        constant("SB_" . $NAME . "_DB_HOST") .
                                        '\');',
                                    'define(\'SB_' .
                                        $NAME .
                                        '_DB_USER\', \'' .
                                        constant("SB_" . $NAME . "_DB_USER") .
                                        '\');',
                                    'define(\'SB_' .
                                        $NAME .
                                        '_DB_PASSWORD\', \'' .
                                        constant("SB_" . $NAME . "_DB_PASSWORD") .
                                        '\');',
                                    'define(\'SB_' .
                                        $NAME .
                                        '_DB_NAME\', \'' .
                                        constant("SB_" . $NAME . "_DB_NAME") .
                                        '\');',
                                    defined("SB_" . $NAME . "_DB_PREFIX")
                                        ? 'define(\'SB_' .
                                        $NAME .
                                        '_DB_PREFIX\', \'' .
                                        constant(
                                            "SB_" . $NAME . "_DB_PREFIX"
                                        ) .
                                        '\');'
                                        : "",
                                ],
                                "",
                                $raw
                            )
                        );
                    }
                }
                die($connection->connect_error);
            }
            $connection->set_charset("utf8mb4");
            $connection->query(
                "SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))"
            );
            $GLOBALS["SB_" . $NAME . "_CONNECTION"] = $connection;
            return true;
        case "read":
            $status = sb_external_db("connect", $name);
            $value = $extra ? "" : [];
            if ($status === true) {
                $result = $GLOBALS["SB_" . strtoupper($name) . "_CONNECTION"]->query($query);
                if ($result) {
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            if ($extra) {
                                $value = $row;
                            } else {
                                array_push($value, $row);
                            }
                        }
                    }
                } else {
                    return new SBError(
                        "db-error",
                        "sb_external_db",
                        $GLOBALS["SB_" . strtoupper($name) . "_CONNECTION"]->error
                    );
                }
            } else {
                return $status;
            }
            return $value;
        case "write":
            $status = sb_external_db("connect", $name);
            if ($status === true) {
                $connection = $GLOBALS["SB_" . $NAME . "_CONNECTION"];
                $result = $connection->query($query);
                if ($result) {
                    if ($extra) {
                        if (
                            isset($connection->insert_id) &&
                            $connection->insert_id > 0
                        ) {
                            return $connection->insert_id;
                        } else {
                            return sb_db_error("sb_db_query");
                        }
                    } else {
                        return true;
                    }
                } else {
                    return new SBError(
                        "db-error",
                        "sb_external_db",
                        $connection->error
                    );
                }
            }
            return $status;
    }
    return false;
}

function sb_is_error($object)
{
    return is_a($object, "SBError");
}

function sb_is_validation_error($object)
{
    return is_a($object, "SBValidationError");
}




/*
 * -----------------------------------------------------------
 * LOGIN AND ACCOUNT
 * -----------------------------------------------------------
 *
 * 1. Check if the login details are corrects and if yes set the login
 * 2. Update details of the login cookie
 * 3. Logout a user
 * 4. Return the logged in user information
 * 5. Set the login cookie
 * 6. Get the login cookie
 * 7. Verify the login password
 * 8. Check the the active user is an admin, bot, or agent
 * 9. Return the department of the active agent
 * 10. Check the the active user it the supervisor
 *
 */

function sb_login($email = "", $password = "", $user_id = "", $user_token = "")
{
    global $SB_LOGIN;
    $valid_login = false;
    $result = null;
    if ($email && $password) {
        // Login for registered users and agents
        $result = sb_db_get(
            'SELECT id, profile_image, first_name, last_name, email, password, user_type, token, department, password FROM sb_users WHERE email = "' .
                sb_db_escape($email) .
                '" LIMIT 1'
        );
        if (sb_is_error($result)) {
            return $result;
        }
        if (
            isset($result) &&
            $result &&
            isset($result["password"]) &&
            isset($result["user_type"]) &&
            sb_password_verify($password, $result["password"])
        ) {
            $valid_login = true;
            if ($SB_LOGIN && $SB_LOGIN["id"] != $result["id"]) {
                sb_db_query(
                    "UPDATE sb_conversations SET user_id = " .
                        $result["id"] .
                        " WHERE user_id = " .
                        $SB_LOGIN["id"]
                );
            }
        }
    } elseif ($user_id && $user_token) {
        // Login for visitors
        $result = sb_db_get(
            "SELECT id, profile_image, first_name, last_name, email, password, user_type, token FROM sb_users WHERE id = " .
                sb_db_escape($user_id, true) .
                ' AND token = "' .
                sb_db_escape($user_token) .
                '" LIMIT 1'
        );
        if (sb_is_error($result)) {
            return $result;
        }
        if (isset($result["user_type"]) && isset($result["token"])) {
            $valid_login = true;
        }
    }
    if ($valid_login) {
        $settings = [
            "id" => $result["id"],
            "profile_image" => $result["profile_image"],
            "first_name" => $result["first_name"],
            "last_name" => $result["last_name"],
            "email" => $result["email"],
            "user_type" => $result["user_type"],
            "token" => $result["token"],
            "url" => STMBX_URL,
            "password" => $result["password"],
        ];
        if (isset($result["department"])) {
            $settings["department"] = $result["department"];
        }
        sb_set_cookie_login($settings);
        $SB_LOGIN = $settings;
        return [$settings, sb_encryption(json_encode($settings))];
    }
    return false;
}

function sb_update_login(
    $profile_image,
    $first_name,
    $last_name,
    $email,
    $department = "",
    $user_type = false,
    $user_id = false
) {
    global $SB_LOGIN;
    $settings = sb_get_cookie_login();
    if (empty($settings)) {
        $settings = [];
    }
    if ($user_id) {
        $settings["id"] = $user_id;
    }
    $settings["profile_image"] = $profile_image;
    $settings["first_name"] = $first_name;
    $settings["last_name"] = $last_name;
    $settings["email"] = $email;
    $settings["department"] =
        $department == "NULL" || $department == "" || $department === false
        ? null
        : $department;
    if ($user_type) {
        $settings["user_type"] = $user_type;
    }
    if (!headers_sent()) {
        sb_set_cookie_login($settings);
    }
    $SB_LOGIN = $settings;
    return [$settings, sb_encryption(json_encode($settings))];
}

function sb_logout()
{
    global $SB_LOGIN;
    if (!headers_sent()) {
        $time = time() - 3600;
        setcookie("sb-login", "", $time);
    }
    $SB_LOGIN = null;
    return true;
}

function sb_get_active_user(
    $login_data = false,
    $database = false,
    $login_app = false,
    $user_token = false
) {
    global $SB_LOGIN;
    $return = false;
    if ($SB_LOGIN) {
        $return = $SB_LOGIN;
    }
    if ($return === false && !empty($login_data)) {
        $return = json_decode(sb_encryption($login_data, false), true);
    }
    if ($return === false) {
        $return = sb_get_cookie_login();
    }
    if ($login_app !== false) {
        if (!is_array($login_app)) {
            $login_app = json_decode($login_app, true);
        }
        $app = $login_app[1];
        $login_app_data = $login_app[0];
    }
    if (($database && $return && isset($return["id"])) || $user_token) {
        $keys = [
            "id",
            "profile_image",
            "first_name",
            "last_name",
            "email",
            "password",
            "user_type",
        ];
        $active_user = sb_db_get(
            "SELECT " .
                implode(",", $keys) .
                " FROM sb_users WHERE " .
                ($user_token
                    ? 'token = "' . sb_db_escape($user_token) . '"'
                    : "id = " . $return["id"])
        );
        if (
            $active_user &&
            (empty($return["password"]) ||
                empty($active_user["password"]) ||
                $return["password"] == $active_user["password"])
        ) {
            for ($i = 0; $i < count($keys); $i++) {
                $return[$keys[$i]] = $active_user[$keys[$i]];
            }
            $return["phone"] = sb_get_user_extra($return["id"], "phone");
            $return["cookie"] = sb_encryption(json_encode($return));
        } elseif ($login_data !== false && $login_app !== false) {
            unset($_COOKIE["sb-login"]);
            $SB_LOGIN = false;
            return sb_get_active_user(false, $database, $login_app);
        } else {
            $return = false;
        }
    }
    if ($return !== false) {
        if (!$SB_LOGIN) {
            $SB_LOGIN = $return;
        } elseif ($SB_LOGIN["id"] != $return["id"]) {
            sb_db_query(
                "UPDATE sb_conversations SET user_id = " .
                    $return["id"] .
                    " WHERE user_id = " .
                    $SB_LOGIN["id"]
            );
        }
    }
    return $return;
}

function sb_set_cookie_login($value)
{
    if (!headers_sent()) {
        setcookie(
            "sb-login",
            sb_encryption(json_encode($value)),
            time() + 315569260,
            "/",
            sb_get_setting("cookie-domain", "")
        );
    }
}

function sb_get_cookie_login()
{
    $cookie = isset($_COOKIE["sb-login"])
        ? $_COOKIE["sb-login"]
        : sb_isset($_POST, "login-cookie");
    if ($cookie) {
        $response = json_decode(sb_encryption($cookie, false), true);
        return empty($response) ? false : $response;
    }
    return false;
}

function sb_password_verify($password, $hash)
{
    $success = password_verify($password, $hash);
    return $success;
}

function sb_is_agent($user = false, $exclude_bot = false, $admin = false)
{
    if ($user === "") {
        return false;
    }
    $user =
        $user === false
        ? sb_get_active_user()
        : (is_string($user)
            ? ["user_type" => $user]
            : $user);
    if (!$user) {
        return !empty($GLOBALS["SB_FORCE_ADMIN"]);
    }
    return (!$admin && $user["user_type"] == "agent") ||
        $user["user_type"] == "admin" ||
        (!$exclude_bot && $user["user_type"] == "bot");
}

function sb_get_agent_department()
{
    if (sb_is_agent() && !defined("SB_API")) {
        $user = sb_get_active_user();
        return sb_isset($user, "department");
    }
    return false;
}

function sb_supervisor()
{
    $settings = sb_get_setting("supervisor");
    return in_array(
        sb_get_active_user_ID(),
        explode(",", str_replace(" ", "", sb_isset($settings, "supervisor-id")))
    )
        ? $settings
        : false;
}



/*
 * -----------------------------------------------------------
 * JAVASCRIPT
 * -----------------------------------------------------------
 *
 * Global JavaScript for the chat and admin page.
 *
 */

function sb_js_global()
{
    global $SB_LANGUAGE;
    if (!isset($SB_LANGUAGE)) {
        sb_init_translations();
    }
    $ajax_url = str_replace(
        "//include",
        "/include",
        STMBX_URL . "/include/ajax.php"
    );
    $code = '<script data-cfasync="false">';
    $code .= 'var SB_AJAX_URL = "' . $ajax_url . '";';
    $code .= 'var STMBX_URL = "' . STMBX_URL . '";';
    $code .=
        "var SB_LANG = " .
        ($SB_LANGUAGE ? json_encode($SB_LANGUAGE) : "false") .
        ";";
    $code .= "</script>";
    echo $code;
}

function sb_js_admin()
{
    $active_user = sb_get_active_user();
    $active_user_type = $active_user ? $active_user["user_type"] : false;
    $agent = $active_user_type == "agent";
    $language = sb_get_admin_language();
    $settings = [
        "bot-id" => sb_get_bot_id(),
        "close-message" => sb_get_multi_setting(
            "close-message",
            "close-active"
        ),
        "close-message-transcript" => sb_get_multi_setting(
            "close-message",
            "close-transcript"
        ),
        "routing" => (!$active_user || $agent) &&
            (sb_get_multi_setting("queue", "queue-active") ||
                sb_get_setting("routing") ||
                sb_get_multi_setting(
                    "agent-hide-conversations",
                    "agent-hide-conversations-active"
                )
            ),
        "desktop-notifications" => sb_get_setting("desktop-notifications"),
        "push-notifications" => sb_get_multi_setting(
            "push-notifications",
            "push-notifications-active"
        ),
        "push-notifications-users" => sb_get_multi_setting(
            "push-notifications",
            "push-notifications-users-active"
        ),
        "push-notifications-id" => sb_get_multi_setting(
            "push-notifications",
            "push-notifications-id"
        ),
        "flash-notifications" => sb_get_setting("flash-notifications"),
        "notifications-icon" => sb_get_setting("notifications-icon", STMBX_URL . "/media/icon.png"),

        // "auto-updates" => sb_get_setting("auto-updates"),
        "sounds" => sb_get_setting("chat-sound-admin"),
        "pusher" => sb_pusher_active(),
        "notify-user-email" =>
        sb_get_setting("notify-user-email") ||
            sb_get_multi_setting("email-piping", "email-piping-active"),
        "assign-conversation-to-agent" =>
        $agent &&
            sb_get_multi_setting(
                "agent-hide-conversations",
                "agent-hide-conversations-active"
            ) &&
            sb_get_multi_setting(
                "agent-hide-conversations",
                "agent-hide-conversations-view"
            ),
        "allow-agent-delete-message" =>
        $active_user_type == "admin" ||
            sb_get_multi_setting("agents", "agents-delete-message"),
        "supervisor" => sb_supervisor() ? true : false,
        "sms-active-users" => sb_get_multi_setting("sms", "sms-active-users"),
        "sms" => sb_get_multi_setting("sms", "sms-user"),
        "now-db" => gmdate("Y-m-d H:i:s"),
        "login-time" => time(),

        "single-agent" =>
        intval(
            sb_db_get(
                'SELECT COUNT(*) as count FROM sb_users WHERE user_type = "agent" OR user_type = "admin"'
            )["count"]
        ) == 1,
        "slack-active" => sb_get_setting("slack-active"),
        "zendesk-active" => sb_get_setting("zendesk-active"),
        "translation" => sb_get_setting("google-translation"),
        "active-agent-language" => sb_get_user_language(
            sb_get_active_user_ID()
        ),
        "transcript-message" => sb_get_multi_setting(
            "transcript",
            "transcript-message",
            ""
        ),
        "cookie-domain" => sb_get_setting("cookie-domain"),
        "online-users-notification" => sb_get_setting(
            "online-users-notification"
        )
            ? sb_("New user online")
            : false,
        "webhooks" => sb_get_multi_setting("webhooks", "webhooks-active")
            ? sb_get_multi_setting("webhooks", "webhooks-allowed", true)
            : false,
        "show-profile-images" => sb_get_setting("show-profile-images-admin"),
    ];
    $code = "<script>";

    if ($settings["pusher"]) {
        $settings["pusher-key"] = sb_get_multi_setting("pusher", "pusher-key");
        $settings["pusher-cluster"] = sb_get_multi_setting("pusher", "pusher-cluster");
    }
    if ($settings["supervisor"]) {
        $settings["allow-supervisor-delete-message"] = sb_get_multi_setting(
            "supervisor",
            "supervisor-delete-message"
        );
    }
    if ($active_user) {
        if (empty($active_user["url"]) || $active_user["url"] == STMBX_URL) {
            $code .=
                'var SB_ACTIVE_AGENT = { id: "' .
                $active_user["id"] .
                '", email: "' .
                $active_user["email"] .
                '", full_name: "' .
                sb_get_user_name($active_user) .
                '", user_type: "' .
                $active_user_type .
                '", profile_image: "' .
                $active_user["profile_image"] .
                '", department: "' .
                sb_isset($active_user, "department", "") .
                '"};';
        } else {
            $code .= "SBF.reset();";
        }
    } else {
        $code .=
            'var SB_ACTIVE_AGENT = { id: "", full_name: "", user_type: "", profile_image: "", email: "" };';
    }

    $translations =
        $language && $language != "en"
        ? file_get_contents(
            SB_PATH . "/resources/languages/admin/js/" . $language . ".json"
        )
        : "[]";
    $code .=
        "var SB_LANGUAGE_CODES = " .
        file_get_contents(
            SB_PATH . "/resources/languages/language-codes.json"
        ) .
        ";";
    $code .= "var SB_ADMIN_SETTINGS = " . json_encode($settings) . ";";
    $code .=
        "var SB_TRANSLATIONS = " . ($translations ? $translations : "[]") . ";";
    $code .= 'var SB_VERSIONS = {
        messenger: "' . (defined("SB_MESSENGER") ? SB_MESSENGER : -1) . '",
        whatsapp: "' . (defined("SB_WHATSAPP") ? SB_WHATSAPP : -1) . '",
        whatsmeow: "' . (defined("SB_WHATSMEOW") ? SB_WHATSMEOW : -1) . '",
        waweb: "' . (defined("SB_WAWEB") ? SB_WAWEB : -1) . '",
        telegram: "' . (defined("SB_TELEGRAM") ? SB_TELEGRAM : -1) . '",
        gbm: "' . (defined("SB_GBM") ? SB_GBM : -1) . '"
    };';
    $code .= "</script>";
    echo $code;
}









/*
 * -----------------------------------------------------------
 * USERS
 * -----------------------------------------------------------
 *
 * 1. Add a new user or agent.
 * 2. Add a new user extra details
 * 3. Add a new user and login it
 * 4. Delete a user and all the related information (conversations, messages)
 * 5. Delete multiple users and all the related information (conversations, messages)
 * 6. Delete all leads
 * 7. Update a user or agent.
 * 8. Update a user or agent detail or extra detail.
 * 9. Update a visitor to lead
 * 10. Update the current user and a conversation message
 * 11. Return the user with the given id
 * 12. Return all users, Agents
 * 13. Return the users registered after the given date
 * 14. Search users based on the gived keyword
 * 15. Return the users count grouped by user type
 * 16. Return the user additional details
 * 17. Return the agent or admin with the given ID
 * 18. Set the active admin if any and register if required
 * 19. Return the full name of a user
 * 20. Save a CSV file with all users details
 * 21. Save automatic information from the user: IP, Country, OS, Browser
 * 22. Set and get the current page URL of a user
 * 23. Create or update the bot
 * 24. Return the bot ID
 * 25. Return the user or the last agent of a conversation
 * 26. Return an array with the agents ids
 * 27. Generate the profile picture of the user from its name
 * 28. Return the users who have the requested details
 * 29. Return the ID of the active user
 * 30. Get a user from a detail
 * 31. Check if the user is typing on the chat
 * 32. Check if an agent is typing in a conversation
 * 33. Set the user typing status
 * 34. Set agent raring
 * 35. Get agent rating
 * 36. Split a full name into first name and last name
 *
 */

function sb_add_user(
    $settings = [],
    $settings_extra = [],
    $hash_password = true
) {
    $keys = [
        "profile_image",
        "first_name",
        "last_name",
        "email",
        "user_type",
        "password",
        "department",
    ];

    // Ensure required keys are set and sanitize input
    foreach ($keys as $key) {
        $settings[$key] = sb_isset($settings, $key, "");
        if (!is_string($settings[$key])) {
            $settings[$key] = trim($settings[$key][0]);
        }
    }

    // Check for duplicate email
    if (!empty($settings["email"])) {
        $settings["email"] = sb_db_escape($settings["email"]);
        $existingEmailUserType = sb_db_get(
            'SELECT user_type FROM sb_users WHERE email = "' . $settings["email"] . '" LIMIT 1'
        );

        if ($existingEmailUserType) {
            $isDuplicateEmail = sb_get_setting("duplicate-emails") && !sb_is_agent($existingEmailUserType["user_type"]);

            if ($isDuplicateEmail) {
                sb_db_query('UPDATE sb_users SET email = NULL WHERE email = "' . $settings["email"] . '"');
            } else {
                return new SBValidationError("duplicate-email");
            }
        }
    }

    // Check for duplicate phone number
    if (!empty($settings_extra["phone"]) && sb_get_user_by("phone", $settings_extra["phone"][0]) && !sb_get_setting("duplicate-emails")) {
        return new SBValidationError("duplicate-phone");
    }

    // Set default profile image if not provided
    if (empty($settings["profile_image"])) {
        $settings["profile_image"] = sb_get_avatar($settings["first_name"], $settings["last_name"]);
    }


    // Clean and format the phone number
    if (!empty($settings_extra["phone"])) {
        $cleanedPhone = preg_replace("/[^a-zA-Z0-9@.]/", "", $settings_extra["phone"][0]);

        // Add plus "+" symbol if not already present
        if (strpos($cleanedPhone, '+') === false) {
            $cleanedPhone = '+' . $cleanedPhone;
        }

        // Check if the phone number doesn't contain "@g.us", if not, append it
        if (strpos($cleanedPhone, "@g.us") === false) {
            $settings_extra["phone"][0] = $cleanedPhone . "@g.us";
        }

        $settings_extra["phone"][0] = $cleanedPhone;
    }


    // Set default first name and last name if not provided
    if (empty($settings["first_name"])) {
        $name = sb_get_setting("visitor-prefix");
        $settings["first_name"] = $name === false || $name == "" ? "User" : $name;
        $settings["last_name"] = "#" . rand(0, 99999);
    }

    // Set default user type if not provided
    if (empty($settings["user_type"])) {
        $settings["user_type"] = empty($settings["email"]) ? "visitor" : "user";
    } elseif (!in_array($settings["user_type"], ["visitor", "user", "lead", "agent", "admin", "bot"])) {
        return new SBValidationError("invalid-user-type");
    }

    // Adjust last name for user type "user"
    if ($settings["user_type"] == "user" && !empty($settings["first_name"]) && substr($settings["last_name"], 0, 1) == "#") {
        $settings["last_name"] = "";
    }

    // Check for security error
    if (sb_is_agent($settings) && !sb_is_agent(false, true, true)) {
        return new SBError("security-error", "sb_add_user");
    }

    // Hash the password if provided
    $password = $settings["password"];
    if (!empty($password) && $hash_password) {
        $password = password_hash($password, PASSWORD_DEFAULT);
    }

    // Set default department if not provided
    if (empty($settings["department"])) {
        $settings["department"] = "NULL";
    }

    // Prepare and execute the query
    $now = gmdate("Y-m-d H:i:s");
    $token = bin2hex(openssl_random_pseudo_bytes(20));
    $query = 'INSERT INTO sb_users(first_name, last_name, password, email, profile_image, user_type, creation_time, token, department, last_activity) VALUES ("' .
        sb_db_escape($settings["first_name"]) . '", "' .
        sb_db_escape($settings["last_name"]) . '", "' .
        sb_db_escape($password) . '", ' .
        ($settings["email"] == "" ? "NULL" : '"' . $settings["email"] . '"') . ', "' .
        sb_db_escape($settings["profile_image"]) . '", "' .
        $settings["user_type"] . '", "' .
        $now . '", "' .
        $token . '", ' .
        sb_db_escape($settings["department"]) . ', "' .
        $now . '")';

    $user_id = sb_db_query($query, true);

    // Add extra user data if successful
    if (!sb_is_error($user_id) && is_numeric($user_id) && $user_id > 0 && isset($settings_extra)) {
        sb_add_new_user_extra($user_id, $settings_extra);
    }

    // Perform additional actions based on user type
    if (!sb_is_error($user_id) && !sb_is_agent() && ($settings["user_type"] == "user" || $settings["user_type"] == "lead" || sb_get_setting("visitor-autodata"))) {
        sb_user_autodata($user_id);
    }

    if ($settings["user_type"] == "visitor") {
        sb_reports_update("visitors");
    }

    // Update reports based on payload
    if (isset($_POST["payload"]) && isset($_POST["payload"]["rich-messages"]) && isset($_POST["payload"]["rich-messages"]["registration"])) {
        sb_reports_update("registrations");
    }

    // Send newsletter if email is provided
    if ($settings["email"]) {
        sb_newsletter($settings["email"], $settings["first_name"], $settings["last_name"]);
    }

    return $user_id;
}


function sb_add_new_user_extra($user_id, $settings)
{
    $query = "";
    $user_id = sb_db_escape($user_id, true);
    foreach ($settings as $key => $setting) {
        if (is_array($setting) && $setting[0] && $setting[0] != "null") {
            $query .=
                '("' .
                $user_id .
                '", "' .
                sb_db_escape($key) .
                '", "' .
                sb_db_escape($setting[1]) .
                '", "' .
                sb_db_escape($setting[0]) .
                '"),';
        }
    }
    if ($query) {
        $query =
            "INSERT IGNORE INTO sb_users_data(user_id, slug, name, value) VALUES " .
            substr($query, 0, -1);
        return sb_db_query($query);
    }
    return false;
}
function sb_csv_users_add($csvfile)
{
    $csvmime = [
        "text/x-comma-separated-values",
        "text/comma-separated-values",
        "application/octet-stream",
        "application/vnd.ms-excel",
        "application/x-csv",
        "text/x-csv",
        "text/csv",
        "application/csv",
        "application/excel",
        "application/vnd.msexcel",
        "text/plain",
    ];
    if (
        !empty($csvfile["csv"]["name"]) &&
        in_array($csvfile["csv"]["type"], $csvmime)
    ) {
        $file = fopen($_FILES["csv"]["tmp_name"], "r");
        fgetcsv($file);
        $header = fgetcsv($file, 1000, ";", ",");
        $filesize = $_FILES["csv"]["size"];
        while (($data = fgetcsv($file, $filesize, ";", ",")) !== false) {
            $settings = [];
            $settings = array_combine($header, $data);
            $value = strtolower($settings["slug"]) == "phone" ? "+" . str_replace(" ", "", $settings["value"]) : $settings["value"];

            $settings_extra = [
                strtolower($settings["slug"]) => [$value, $settings["name"]],
            ];
            $now = gmdate("Y-m-d H:i:s");
            $token = !empty($settings["token"])
                ? $settings["token"]
                : bin2hex(openssl_random_pseudo_bytes(20));
            $user_id = sb_db_escape($settings["id"], true);
            //sb_db_query('DELETE FROM sb_users WHERE id = ' . $user_id);
            $ids = sb_db_get(
                "SELECT COUNT(*) as count FROM sb_users WHERE id = " . $user_id,
                false
            );
            if ($ids["count"] > 0) {
            } else {
                $query =
                    'INSERT INTO sb_users(id,first_name, last_name,email, profile_image, user_type, creation_time, token, department, last_activity) VALUES ("' .
                    sb_db_escape($settings["id"]) .
                    '","' .
                    sb_db_escape($settings["first_name"]) .
                    '", "' .
                    sb_db_escape($settings["last_name"]) .
                    '",' .
                    ($settings["email"] == ""
                        ? "NULL"
                        : '"' . $settings["email"] . '"') .
                    ', "' .
                    sb_db_escape($settings["profile_image"]) .
                    '", "' .
                    $settings["user_type"] .
                    '", "' .
                    $now .
                    '", "' .
                    $token .
                    '", ' .
                    sb_db_escape($settings["department"]) .
                    ', "' .
                    $now .
                    '")';
                sb_db_query($query, true);
                if ($user_id > 0 && isset($settings_extra)) {
                    sb_add_new_user_extra($user_id, $settings_extra);
                }
            }
        }
        $response = json_encode(["success" => "Users Create Successfully"]);
        return $response;
    } else {
        http_response_code(406);
        die($response);
    }
}

function sb_add_user_and_login(
    $settings,
    $settings_extra,
    $hash_password = true
) {
    $response = sb_add_user($settings, $settings_extra, $hash_password);
    if (is_numeric($response)) {
        $token = sb_db_get(
            "SELECT token FROM sb_users WHERE id = " . $response
        );
        return sb_login("", "", $response, $token["token"]);
    }
    return $response;
}

function sb_delete_user($user_id)
{
    $user_id = sb_db_escape($user_id, true);
    $ids = sb_db_get(
        "SELECT id FROM sb_conversations WHERE user_id = " . $user_id,
        false
    );
    for ($i = 0; $i < count($ids); $i++) {
        sb_delete_attachments($ids[$i]["id"]);
    }
    if (sb_get_setting("logs")) {
        sb_logs(
            "Agent " .
                sb_get_user_name() .
                " #" .
                sb_get_active_user_ID() .
                " deleted the user #" .
                $user_id
        );
    }
    return sb_db_query("DELETE FROM sb_users WHERE id = " . $user_id);
}

function sb_delete_users($user_ids)
{
    $query = "";
    for ($i = 0; $i < count($user_ids); $i++) {
        $user_id = sb_db_escape($user_ids[$i], true);
        $query .= $user_id . ",";
        $ids = sb_db_get(
            "SELECT id FROM sb_conversations WHERE user_id = " . $user_id,
            false
        );
        for ($j = 0; $j < count($ids); $j++) {
            sb_delete_attachments($ids[$j]["id"]);
        }
    }
    return sb_db_query(
        "DELETE FROM sb_users WHERE id IN (" .
            sb_db_escape(substr($query, 0, -1)) .
            ")"
    );
}

function sb_delete_leads()
{
    return sb_db_query('DELETE FROM sb_users WHERE user_type = "lead"');
}


function sb_update_user(
    $user_id,
    $settings,
    $settings_extra = [],
    $hash_password = true
) {
    $user_id = sb_db_escape($user_id, true);
    $keys = [
        "profile_image",
        "first_name",
        "last_name",
        "email",
        "user_type",
        "password",
        "department",
    ];
    for ($i = 0; $i < count($keys); $i++) {
        $settings[$keys[$i]] = sb_isset($settings, $keys[$i], "");
        if (!is_string($settings[$keys[$i]])) {
            $settings[$keys[$i]] = $settings[$keys[$i]][0];
        }
    }
    $profile_image = $settings["profile_image"];
    $first_name = trim($settings["first_name"]);
    $last_name = trim($settings["last_name"]);
    $email = trim($settings["email"]);
    $user_type = $settings["user_type"];
    $password =
        isset($settings["password"]) && $settings["password"] != "********"
        ? $settings["password"]
        : "";
    $department = sb_isset($settings, "department", "NULL");
    $active_user = sb_get_active_user();
    $query = "";
    if (sb_is_agent($user_type) && !sb_is_agent(false, true, true)) {
        return new SBError("security-error", "sb_update_user");
    }

    // Validate duplicate email
    if ($email) {
        $email = sb_db_escape($email);
        $existing_email = sb_db_get(
            'SELECT user_type, email FROM sb_users WHERE email = "' .
                $email .
                '" AND id <> ' .
                $user_id
        );
        if ($existing_email) {
            if (
                sb_get_setting("duplicate-emails") &&
                !sb_is_agent($existing_email["user_type"])
            ) {
                sb_db_query(
                    'UPDATE sb_users SET email = NULL WHERE email = "' .
                        $email .
                        '"'
                );
            } else {
                return new SBValidationError("duplicate-email");
            }
        }
    }

    // Validate duplicate phone number
    if (
        !empty($settings_extra["phone"]) &&
        intval(
            sb_db_get(
                'SELECT COUNT(*) as count FROM sb_users_data WHERE slug = "phone" AND (value = "' .
                    $settings_extra["phone"][0] .
                    '"' .
                    (strpos($settings_extra["phone"][0], "+") !== false
                        ? ' OR value = "' .
                        str_replace(
                            "+",
                            "00",
                            $settings_extra["phone"][0]
                        ) .
                        '"'
                        : "") .
                    ") AND user_id <> " .
                    sb_db_escape($user_id, true)
            )["count"]
        ) > 0 &&
        !sb_get_setting("duplicate-emails")
    ) {
        return new SBValidationError("duplicate-phone");
    }

    // Adjust last name for user type "user"
    if (
        $user_type == "user" &&
        $first_name &&
        $last_name &&
        substr($last_name, 0, 1) == "#"
    ) {
        $last_name = "";
    }

    // Set default profile image if not provided or invalid
    if (!$profile_image || $profile_image == STMBX_URL . "/media/user.svg") {
        $profile_image = sb_get_avatar($first_name, $last_name);
    }

    // Update user details query
    $query = '';
    if ($first_name) {
        $query .= ', first_name = "' . sb_db_escape($first_name) . '"';
    }
    if ($password) {
        if ($hash_password) {
            $password = password_hash($password, PASSWORD_DEFAULT);
        }
        $query .= ', password = "' . sb_db_escape($password) . '"';
    }
    if (!$department) {
        $department = "NULL";
    }
    if (!$user_type && !sb_is_agent($user_type)) {
        $user_type = $email
            ? "user"
            : (
                intval(
                    sb_db_get(
                        "SELECT COUNT(*) AS count FROM sb_conversations WHERE user_id = " .
                            $user_id
                    )["count"]
                ) > 0
                ? "lead"
                : "visitor");
    }

    $query_final =
        'UPDATE sb_users SET profile_image = "' .
        sb_db_escape($profile_image) .
        '", last_name = "' .
        sb_db_escape($last_name) .
        '", user_type = "' .
        sb_db_escape($user_type) .
        '", email = ' .
        (strlen($email) == 0 ? "NULL" : '"' . sb_db_escape($email) . '"') .
        ", department = " .
        sb_db_escape($department) .
        $query .
        " WHERE id = " .
        $user_id;


    // Execute user details update query
    $result = sb_db_query($query_final);


    // Update extra user details
    foreach ($settings_extra as $key => $setting) {
        if (is_array($setting)) {
            sb_db_query(
                'REPLACE INTO sb_users_data SET name = "' .
                    sb_db_escape($setting[1]) .
                    '", value = "' .
                    sb_db_escape($setting[0]) .
                    '", slug = "' .
                    sb_db_escape($key) .
                    '", user_id = ' .
                    $user_id
            );
        }
    }
    sb_db_query(
        "DELETE FROM sb_users_data WHERE user_id = " .
            $user_id .
            ' AND value = ""'
    );

    if ($active_user && $active_user["id"] == $user_id) {
        $result = sb_update_login(
            $profile_image,
            $first_name,
            $last_name,
            $email,
            $department,
            $user_type,
            $user_id
        );
        sb_user_autodata($user_id);
    }

    // Send newsletter if email is provided
    if ($email) {
        sb_newsletter($email, $first_name, $last_name);
    }

    // Log user update if agent and logging enabled
    if (sb_is_agent() && sb_get_setting("logs")) {
        sb_logs("updated the user details of the user #" . $user_id);
    }
    return $result;
}

function sb_update_user_value($user_id, $slug, $value, $name = false)
{
    $user_id = sb_db_escape($user_id, true);
    if (
        !sb_is_agent(false, true, true) &&
        ((sb_is_agent() &&
            sb_isset(
                sb_db_get(
                    "SELECT user_type FROM sb_users WHERE id = " . $user_id
                ),
                "user_type"
            ) == "admin") ||
            ($slug == "user_type" && ($value == "agent" || $value == "admin")))
    ) {
        return new SBError("security-error", "sb_update_user_value");
    }
    if (empty($value)) {
        return sb_db_query(
            "DELETE FROM sb_users_data WHERE user_id = " .
                $user_id .
                ' AND slug = "' .
                sb_db_escape($slug) .
                '"'
        );
    }
    if (
        in_array($slug, [
            "profile_image",
            "first_name",
            "last_name",
            "email",
            "password",
            "department",
            "user_type",
            "last_activity",
            "typing",
        ])
    ) {
        if ($slug == "password") {
            $value = password_hash($value, PASSWORD_DEFAULT);
        }
        if ($slug == "email") {
            sb_newsletter($value);
        }
        if ($user_id == sb_get_active_user_ID()) {
            $GLOBALS["SB_LOGIN"][$slug] = $value;
        }
        return sb_db_query(
            "UPDATE sb_users SET " .
                sb_db_escape($slug) .
                ' = "' .
                sb_db_escape($value) .
                '" WHERE id = ' .
                $user_id
        );
    }
    return sb_db_query(
        'REPLACE INTO sb_users_data SET name = "' .
            sb_db_escape($name ? $name : sb_string_slug($slug, "string")) .
            '", value = "' .
            sb_db_escape($value) .
            '", slug = "' .
            sb_db_escape($slug) .
            '", user_id = ' .
            $user_id
    );
}

function sb_update_user_to_lead($user_id)
{
    sb_user_autodata($user_id);
    return sb_update_user_value($user_id, "user_type", "lead");
}

function sb_update_user_and_message(
    $user_id,
    $settings,
    $settings_extra = [],
    $message_id = false,
    $message = false,
    $payload = false
) {
    $result = sb_update_user($user_id, $settings, $settings_extra);
    $rich_message = sb_isset($payload, "rich-messages");
    if (
        sb_is_validation_error($result) &&
        $result->code() == "duplicate-email"
    ) {
        return $result;
    }
    if ($message_id) {
        if ($message) {
            sb_update_message($message_id, $message, false, $payload);
        }
        $message = "";
        foreach ($settings as $key => $setting) {
            if ($setting[0]) {
                $message .=
                    sb_string_slug($key, "string") .
                    ": " .
                    $setting[0] .
                    PHP_EOL;
            }
        }
        foreach ($settings_extra as $key => $setting) {
            $message .=
                sb_string_slug($key, "string") . ": " . $setting[0] . PHP_EOL;
        }
        sb_send_agents_notifications(
            $message,
            false,
            sb_db_get(
                "SELECT conversation_id FROM sb_messages WHERE id = " .
                    sb_db_escape($message_id, true)
            )["conversation_id"]
        );
    }
    if ($rich_message) {
        if (isset($rich_message["sb-follow-up-form"])) {
            sb_reports_update("follow-up");
        }
        if (isset($rich_message["registration"])) {
            sb_reports_update("registrations");
        }
    }
    return $result;
}

function sb_get_user($user_id, $extra = false)
{
    $user = sb_db_get(
        SELECT_FROM_USERS .
            ", password FROM sb_users WHERE id = " .
            sb_db_escape($user_id, true)
    );
    if (isset($user) && is_array($user)) {
        if ($extra) {
            $user["details"] = sb_get_user_extra($user_id);
        }
        return $user;
    }
    return false;
}

// function sb_get_users($sorting = ['creation_time', 'DESC'], $user_types = [], $search = '', $pagination = 0, $extra = false, $user_ids = false)
// {
//     $query = '';
//     $query_search = '';
//     $count = count($user_types);
//     $sorting_field = $sorting[0];
//     $main_field_sorting = in_array($sorting_field, ['id', 'first_name', 'last_name', 'email', 'profile_image', 'user_type', 'creation_time', 'last_activity', 'department']);
//     if ($count) {
//         for ($i = 0; $i < $count; $i++) {
//             $query .= 'user_type = "' . sb_db_escape($user_types[$i]) . '" OR ';
//         }
//         $query = '(' . substr($query, 0, strlen($query) - 4) . ')';
//     }

//     if ($user_ids) {
//         $count_user_ids = count($user_ids);
//         if ($count_user_ids) {
//             if ($query)
//                 $query .= ' AND ';
//             $query .= ' id IN (' . sb_db_escape(implode(',', $user_ids)) . ')';
//         }
//     }
//     if ($search) {
//         $searched_users = sb_search_users($search);
//         $count_search = count($searched_users);
//         if ($count_search > 0) {
//             for ($i = 0; $i < $count_search; $i++) {
//                 $query_search .= $searched_users[$i]['id'] . ',';
//             }
//             $query .= ($query ? ' AND ' : '') . 'id IN (' . substr($query_search, 0, -1) . ')';
//         }
//     }
//     if ($query) {
//         $query = ' WHERE user_type <> "bot" AND ' . $query;
//     } else {
//         $query = ' WHERE user_type <> "bot"';
//     }
//     $users = sb_db_get(SELECT_FROM_USERS . ' FROM sb_users ' . $query . sb_routing_and_department_db('sb_conversations', true) . ($main_field_sorting ? (' ORDER BY ' . sb_db_escape($sorting_field) . ' ' . sb_db_escape($sorting[1])) : '') . ' LIMIT ' . intval(sb_db_escape($pagination, true)) * 30 . ',30', false);
//     $users_count = count($users);

//     if (!$users_count)
//         return [];
//     if (isset($users) && is_array($users)) {
//         $is_array = is_array($extra);
//         if ($extra && (!$is_array || count($extra))) {
//             $query = '';
//             $query_extra = '';
//             for ($i = 0; $i < $users_count; $i++) {
//                 $query .= $users[$i]['id'] . ',';
//                 $conversation = sb_db_get('SELECT * FROM sb_conversations A,sb_messages B WHERE A.id=B.conversation_id AND A.user_id=' . $users[$i]['id'], false);
//                 $users[$i]['label'] = $conversation[0]['label'];
//                 $users[$i]['extra'] = [];
//             }
//             if ($is_array) {
//                 for ($i = 0; $i < count($extra); $i++) {
//                     $query_extra .= 'slug = "' . $extra[$i] . '" OR ';
//                 }
//                 if ($query_extra) {
//                     $query_extra = ' AND (' . substr($query_extra, 0, -4) . ')';
//                 }
//             }
//             $users_extra = sb_db_get('SELECT user_id, slug, value FROM sb_users_data WHERE user_id IN (' . substr($query, 0, -1) . ')' . $query_extra . ' ORDER BY user_id', false);
//             for ($i = 0; $i < count($users_extra); $i++) {
//                 $user_id = $users_extra[$i]['user_id'];
//                 $slug = $users_extra[$i]['slug'];
//                 $value = $users_extra[$i]['value'];
//                 for ($j = 0; $j < $users_count; $j++) {
//                     if ($users[$j]['id'] == $user_id) {
//                         $users[$j]['extra'][$slug] = $value;
//                         break;
//                     }
//                 }
//             }
//         }
//         if (!$main_field_sorting) {
//             if ($sorting[1] == 'ASC') {
//                 usort($users, function ($a, $b) use ($sorting_field) {
//                     return isset($a['extra'][$sorting_field]) ? $a['extra'][$sorting_field] <=> (isset($b['extra'][$sorting_field]) ? $b['extra'][$sorting_field] : '') : -1;
//                 });
//             } else {
//                 usort($users, function ($a, $b) use ($sorting_field) {
//                     return isset($b['extra'][$sorting_field]) ? $b['extra'][$sorting_field] <=> (isset($a['extra'][$sorting_field]) ? $a['extra'][$sorting_field] : '') : -1;
//                 });
//             }
//         }
//         return $users;
//     } else {
//         return new SBError('db-error', 'sb_get_users', $users);
//     }
// }


function sb_get_users($sorting = ['creation_time', 'DESC'], $user_types = [], $search = '', $pagination = 0, $extra = false, $user_ids = false)
{
    // Initialize query parts
    $query = '';
    $query_search = '';

    // Extract sorting field and check if it's a main field
    $sorting_field = $sorting[0];
    $main_field_sorting = in_array($sorting_field, ['id', 'first_name', 'last_name', 'email', 'profile_image', 'user_type', 'creation_time', 'last_activity', 'department']);

    // Count user types
    $count = count($user_types);

    // Construct user type query
    if ($count) {
        for ($i = 0; $i < $count; $i++) {
            $query .= 'user_type = "' . sb_db_escape($user_types[$i]) . '" OR ';
        }
        $query = '(' . substr($query, 0, strlen($query) - 4) . ')';
    }

    // Add user IDs condition to query
    if ($user_ids) {
        $count_user_ids = count($user_ids);
        if ($count_user_ids) {
            if ($query)
                $query .= ' AND ';
            $query .= ' id IN (' . sb_db_escape(implode(',', $user_ids)) . ')';
        }
    }

    // Search for users
    if ($search) {
        $searched_users = sb_search_users($search);
        $count_search = count($searched_users);
        if ($count_search > 0) {
            for ($i = 0; $i < $count_search; $i++) {
                $query_search .= $searched_users[$i]['id'] . ',';
            }
            $query .= ($query ? ' AND ' : '') . 'id IN (' . substr($query_search, 0, -1) . ')';
        }
    }

    // Append conditions to the main query
    if ($query) {
        $query = ' WHERE user_type <> "bot" AND ' . $query;
    } else {
        $query = ' WHERE user_type <> "bot"';
    }

    // Retrieve users data with additional fields
    $users = sb_db_get(SELECT_FROM_USERS . ' FROM sb_users ' . $query . sb_routing_and_department_db('sb_conversations', true) . ($main_field_sorting ? (' ORDER BY ' . sb_db_escape($sorting_field) . ' ' . sb_db_escape($sorting[1])) : '') . ' LIMIT ' . intval(sb_db_escape($pagination, true)) * 30 . ',30', false);
    $users_count = count($users);

    // Handle empty result
    if (!$users_count)
        return [];

    // Process retrieved users data
    if (isset($users) && is_array($users)) {
        // Process additional data
        foreach ($users as &$user) {
            // Retrieve conversation data for each user
            $conversation = sb_db_get('SELECT * FROM sb_conversations WHERE user_id=' . $user['id'], false);
            // If conversation data exists, add it to the user data
            if (!empty($conversation)) {
                $user['label'] = $conversation[0]['label'];
                $user['conversation_source'] = $conversation[0]['source'];
            }
        }

        // Sort users if necessary
        if (!$main_field_sorting) {
            if ($sorting[1] == 'ASC') {
                usort($users, function ($a, $b) use ($sorting_field) {
                    return isset($a['extra'][$sorting_field]) ? $a['extra'][$sorting_field] <=> (isset($b['extra'][$sorting_field]) ? $b['extra'][$sorting_field] : '') : -1;
                });
            } else {
                usort($users, function ($a, $b) use ($sorting_field) {
                    return isset($b['extra'][$sorting_field]) ? $b['extra'][$sorting_field] <=> (isset($a['extra'][$sorting_field]) ? $a['extra'][$sorting_field] : '') : -1;
                });
            }
        }

        return $users;
    } else {
        return new SBError('db-error', 'sb_get_users', $users);
    }
}

function sb_get_new_users($datetime)
{
    $datetime = sb_db_escape($datetime);
    $users = sb_db_get(SELECT_FROM_USERS . ' FROM sb_users WHERE  user_type <> "bot" AND ' . (is_numeric($datetime) ? ('id > ' . $datetime) : ('creation_time > "' . $datetime . '"')) . ' ORDER BY id DESC', false);
    if (isset($users) && is_array($users)) {
        return $users;
    } else {
        return new SBError('db-error', 'sb_get_new_users', $users);
    }
}


function sb_search_users($search)
{
    $search = trim(sb_db_escape($search));
    $query = '';
    if (strpos($search, ' ') > 0) {
        $search = explode(' ', $search);
    } else {
        $search = [$search];
    }
    for ($i = 0; $i < count($search); $i++) {
        $search[$i] = sb_db_escape($search[$i]);
        $query .= 'A.first_name LIKE "%' . $search[$i] . '%" OR A.last_name LIKE "%' . $search[$i] . '%" OR ';
    }
    $query .= 'B.label LIKE "%' . $search[0] . '%" OR '; // Add condition for label search
    $result = sb_db_get('SELECT A.*, B.label FROM sb_users A,sb_conversations B,sb_messages C WHERE A.id=B.user_id AND B.id=C.conversation_id AND A.user_type <> "bot" AND (' . $query . ' A.email LIKE "%' . $search[0] . '%" OR A.id IN (SELECT user_id FROM sb_users_data WHERE value LIKE "%' . $search[0] . '%")) ' . sb_routing_and_department_db('sb_conversations', true) . ' GROUP BY A.id;', false);
    if (isset($result) && is_array($result)) {
        return $result;
    } else {
        return new SBError('db-error', 'sb_search_users', $result);
    }
}


function sb_count_users()
{
    $query = sb_routing_and_department_db("sb_conversations", true);
    if ($query) {
        $users = sb_db_get(
            substr($query, strpos($query, "(SE") + 1, -1),
            false
        );
        $query = "";
        for ($i = 0; $i < count($users); $i++) {
            $query .= $users[$i]["user_id"] . ",";
        }
        if ($query) {
            $query = "AND id IN (" . substr($query, 0, -1) . ")";
        }
    }
    return sb_db_get(
        'SELECT SUM(CASE WHEN user_type <> "bot" ' .
            $query .
            ' THEN 1 ELSE 0 END) AS `all`, SUM(CASE WHEN user_type = "lead"' .
            $query .
            ' THEN 1 ELSE 0 END) AS `lead`, SUM(CASE WHEN user_type = "user"' .
            $query .
            ' THEN 1 ELSE 0 END) AS `user`, SUM(CASE WHEN user_type = "visitor"' .
            $query .
            " THEN 1 ELSE 0 END) AS `visitor` FROM sb_users"
    );
}

function sb_get_user_extra($user_id, $slug = false, $default = false)
{
    if (empty($user_id)) {
        return false;
    }
    $response = sb_db_get(
        "SELECT slug, name, value FROM sb_users_data WHERE user_id = " .
            sb_db_escape($user_id, true) .
            ($slug ? ' AND slug = "' . sb_db_escape($slug) . '" LIMIT 1' : ""),
        $slug
    );
    return $slug ? sb_isset($response, "value", $default) : $response;
}

function sb_get_agent($agent_id)
{
    $user = sb_db_get(
        'SELECT id, first_name, last_name, profile_image, department FROM sb_users WHERE (user_type = "admin" OR user_type = "agent" OR user_type = "bot") AND id = ' .
            sb_db_escape($agent_id, true)
    );
    if (isset($user) && is_array($user)) {
        $user["details"] = sb_get_user_extra($agent_id);
        for ($i = 0; $i < count($user["details"]); $i++) {
            if ($user["details"][$i]["slug"] == "country") {
                $country = $user["details"][$i]["value"];
                $countries = json_decode(
                    file_get_contents(
                        SB_PATH . "/resources/json/countries.json"
                    ),
                    true
                );
                $user["country_code"] = $countries[$country];
                if (
                    isset($countries[$country]) &&
                    file_exists(
                        SB_PATH .
                            "/media/flags/" .
                            strtolower($countries[$country]) .
                            ".png"
                    )
                ) {
                    $user["flag"] = strtolower($countries[$country]) . ".png";
                }
                break;
            }
        }
        return $user;
    } else {
        return false;
    }
}

function sb_set_external_active_admin($external_user)
{
    $active_user = sb_get_active_user();
    if (
        !sb_is_agent($active_user) ||
        empty($active_user["url"]) ||
        $active_user["url"] != STMBX_URL ||
        empty($external_user["email"]) ||
        $external_user["email"] != $active_user["email"]
    ) {
        $settings = false;
        $db_user = sb_db_get(
            'SELECT * FROM sb_users WHERE email = "' .
                sb_db_escape($external_user["email"]) .
                '" LIMIT 1'
        );
        if (
            !empty($db_user) &&
            isset($db_user["password"]) &&
            $external_user["password"] == $db_user["password"]
        ) {
            if (!sb_is_agent($db_user)) {
                $db_user["user_type"] = "agent";
                sb_db_query(
                    'UPDATE sb_users SET user_type = "agent" WHERE email = "' .
                        sb_db_escape($external_user["email"]) .
                        '"'
                );
            }
            $settings = [
                "id" => $db_user["id"],
                "profile_image" => $db_user["profile_image"],
                "first_name" => $db_user["first_name"],
                "last_name" => $db_user["last_name"],
                "email" => $db_user["email"],
                "user_type" => $db_user["user_type"],
                "token" => $db_user["token"],
            ];
        } elseif (empty($db_user)) {
            $settings = [
                "id" => sb_isset($external_user, "id"),
                "profile_image" => sb_isset(
                    $external_user,
                    "profile_image",
                    ""
                ),
                "first_name" => $external_user["first_name"],
                "last_name" => $external_user["last_name"],
                "password" => $external_user["password"],
                "email" => $external_user["email"],
                "user_type" => "admin",
            ];
            if (!sb_is_agent($active_user)) {
                global $SB_LOGIN;
                $SB_LOGIN = $settings;
            }
            $settings["id"] = sb_add_user(
                $settings,
                sb_isset($external_user, "extra", []),
                false
            );
        } else {
            sb_logout();
            return "logout";
        }
        if ($settings) {
            unset($settings["password"]);
            global $SB_LOGIN;
            $settings["url"] = STMBX_URL;
            if (!headers_sent()) {
                sb_set_cookie_login($settings);
                $SB_LOGIN = $settings;
            }
            return true;
        }
        return false;
    }
    return true;
}

function sb_get_user_name($user = false)
{
    $user = $user === false ? sb_get_active_user() : $user;
    $name = trim(
        sb_isset($user, "first_name", "") .
            " " .
            sb_isset($user, "last_name", "")
    );
    return substr(sb_isset($user, "last_name", "-"), 0, 1) != "#"
        ? $name
        : sb_get_setting("visitor-default-name", $name);
}

function sb_csv_users()
{
    $custom_fields = sb_get_setting("user-additional-fields");
    $draft = [
        "Birthdate",
        "City",
        "Company",
        "Country",
        "Facebook",
        "Language",
        "LinkedIn",
        "Twitter",
    ];
    $header = ["Phone", "Website"];
    $users = sb_db_get(
        'SELECT id, first_name, last_name, email FROM sb_users WHERE user_type <> "bot" ORDER BY first_name',
        false
    );
    if (isset($custom_fields) && is_array($custom_fields)) {
        for ($i = 0; $i < count($custom_fields); $i++) {
            array_push($header, $custom_fields[$i]["extra-field-name"]);
        }
    }
    for ($i = 0; $i < count($users); $i++) {
        $user = $users[$i];
        if ($user["user_type"] != "visitor" && $user["user_type"] != "lead") {
            $user_extra = sb_db_get(
                "SELECT * FROM sb_users_data WHERE user_id = " . $user["id"],
                false
            );
            for ($j = 0; $j < count($header); $j++) {
                $key = $header[$j];
                $user[$key] = "";
                for ($y = 0; $y < count($user_extra); $y++) {
                    if ($user_extra[$y]["name"] == $key) {
                        $user[$key] = $user_extra[$y]["value"];
                        break;
                    }
                }
            }
        } else {
            for ($j = 0; $j < count($header); $j++) {
                $user[$header[$j]] = "";
            }
        }
        $users[$i] = $user;
    }
    $csvHeader = [
        "ID" => "ID",
        "Name" => "first_name",
        "Given Name" => "first_name",
        "Family Name" => "last_name",
        "E-mail 1 - Value" => "email",
        "Phone 1 - Value" => "Phone",
        "Website 1 - Value" => "Website",
    ];
    return sb_csv($users, $csvHeader, "users");
}
function sb_user_autodata($user_id)
{
    if (!defined("SB_API") && empty($GLOBALS["SB_FORCE_ADMIN"])) {
        $settings = [];
        $user_agent = $_SERVER["HTTP_USER_AGENT"];

        // IP and related data
        $ip =
            isset($_SERVER["HTTP_CF_CONNECTING_IP"]) &&
            substr_count($_SERVER["HTTP_CF_CONNECTING_IP"], ".") == 3
            ? $_SERVER["HTTP_CF_CONNECTING_IP"]
            : $_SERVER["REMOTE_ADDR"];
        if (strlen($ip) > 6) {
            $settings["ip"] = [$ip, "IP"];
            $ip_data = json_decode(
                sb_download(
                    "http://ip-api.com/json/" .
                        $ip .
                        "?fields=status,country,countryCode,city,timezone,currency"
                ),
                true
            );
            if (isset($ip_data["status"]) && $ip_data["status"] == "success") {
                if (isset($ip_data["city"]) && isset($ip_data["country"])) {
                    $settings["location"] = [
                        $ip_data["city"] . ", " . $ip_data["country"],
                        "Location",
                    ];
                }
                if (isset($ip_data["timezone"])) {
                    $settings["timezone"] = [$ip_data["timezone"], "Timezone"];
                }
                if (isset($ip_data["currency"])) {
                    $settings["currency"] = [$ip_data["currency"], "Currency"];
                }
                if (isset($ip_data["countryCode"])) {
                    $settings["country_code"] = [
                        $ip_data["countryCode"],
                        "Country Code",
                    ];
                }
            }
        }

        // Browser
        $browser = "";
        $agent = strtolower($user_agent);
        if (strpos($agent, "safari/") and strpos($agent, "opr/")) {
            $browser = "Opera";
        } elseif (
            strpos($agent, "safari/") and
            strpos($agent, "chrome/") and
            strpos($agent, "edge/") == false
        ) {
            $browser = "Chrome";
        } elseif (strpos($agent, "msie")) {
            $browser = "Internet Explorer";
        } elseif (strpos($agent, "firefox/")) {
            $browser = "Firefox";
        } elseif (strpos($agent, "edge/")) {
            $browser = "Microsoft Edge";
        } elseif (
            strpos($agent, "safari/") and
            strpos($agent, "opr/") == false and
            strpos($agent, "chrome/") == false
        ) {
            $browser = "Safari";
        }
        if ($browser) {
            $settings["browser"] = [$browser, "Browser"];
        }

        // Browser language
        if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
            $settings["language"] = [
                strtoupper(substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2)),
                "Language",
            ];
        }

        // OS
        $os = "";
        $os_array = [
            "/windows nt 10/i" => "Windows 10",
            "/windows nt 6.3/i" => "Windows 8.1",
            "/windows nt 6.2/i" => "Windows 8",
            "/windows nt 6.1/i" => "Windows 7",
            "/windows nt 6.0/i" => "Windows Vista",
            "/windows nt 5.2/i" => "Windows Server 2003/XP x64",
            "/windows xp/i" => "Windows XP",
            "/windows nt 5.0/i" => "Windows 2000",
            "/windows me/i" => "Windows ME",
            "/macintosh|mac os x/i" => "Mac OS X",
            "/mac_powerpc/i" => "Mac OS 9",
            "/linux/i" => "Linux",
            "/ubuntu/i" => "Ubuntu",
            "/iphone/i" => "iPhone",
            "/ipod/i" => "iPod",
            "/ipad/i" => "iPad",
            "/android/i" => "Android",
            "/blackberry/i" => "BlackBerry",
            "/webos/i" => "Mobile",
        ];
        foreach ($os_array as $regex => $value) {
            if (preg_match($regex, $user_agent)) {
                $os = $value;
            }
        }
        if ($os) {
            $settings["os"] = [$os, "OS"];
        }

        // Current url
        if (isset($_POST["current_url"])) {
            $settings["current_url"] = [$_POST["current_url"], "Current URL"];
        } elseif (isset($_SERVER["HTTP_REFERER"])) {
            $settings["current_url"] = [
                $_SERVER["HTTP_REFERER"],
                "Current URL",
            ];
        }

        // Save the data
        return sb_add_new_user_extra($user_id, $settings);
    }
    return false;
}

function sb_current_url($user_id = false, $url = false)
{
    if (!empty($user_id)) {
        if ($url === false) {
            $url = sb_db_get(
                'SELECT value FROM sb_users_data WHERE user_id ="' .
                    sb_db_escape($user_id, true) .
                    '" and slug = "current_url" LIMIT 1'
            );
            return isset($url["value"]) ? $url["value"] : false;
        }
        return sb_update_user_value(
            $user_id,
            "current_url",
            $url,
            "Current URL"
        );
    }
    return false;
}

function sb_update_bot($name = "", $profile_image = "")
{
    $bot = sb_db_get(
        'SELECT id, profile_image, first_name, last_name FROM sb_users WHERE user_type = "bot" LIMIT 1'
    );
    if ($name == "") {
        $name = "Bot";
    }
    if ($profile_image == "") {
        $profile_image = STMBX_URL . "/media/user.svg";
    }
    $settings = [
        "profile_image" => [$profile_image],
        "first_name" => [$name],
        "user_type" => ["bot"],
    ];
    if ($bot == "") {
        return sb_add_user($settings);
    } elseif (
        $bot["profile_image"] != $profile_image ||
        $bot["first_name"] != $name
    ) {
        return sb_update_user($bot["id"], $settings);
    }
    return false;
}

function sb_get_bot_id()
{
    if (isset($GLOBALS["sb-bot-id"])) {
        return $GLOBALS["sb-bot-id"];
    }
    $bot_id = sb_isset(
        sb_db_get('SELECT id FROM sb_users WHERE user_type = "bot" LIMIT 1'),
        "id"
    );
    if (!$bot_id) {
        $bot_id = sb_update_bot();
    }
    $GLOBALS["sb-bot-id"] = $bot_id;
    return $bot_id;
}

function sb_get_user_from_conversation($conversation_id, $agent = false)
{
    $conversation_id = sb_db_escape($conversation_id, true);
    $user_id = sb_isset(
        sb_db_get(
            $agent
                ? 'SELECT A.id FROM sb_users A, sb_messages B WHERE A.id = B.user_id AND (A.user_type = "admin" OR A.user_type = "agent") AND B.conversation_id = ' .
                $conversation_id .
                " GROUP BY A.id"
                : "SELECT A.id, A.email FROM sb_users A, sb_conversations B WHERE A.id = B.user_id AND B.id = " .
                $conversation_id
        ),
        "id"
    );
    return $user_id ? sb_get_user($user_id) : false;
}

function sb_get_agents_ids($admins = true)
{
    $agents_ids = sb_db_get(
        'SELECT id FROM sb_users WHERE user_type = "agent"' .
            ($admins ? ' OR user_type = "admin"' : ""),
        false
    );
    for ($i = 0; $i < count($agents_ids); $i++) {
        $agents_ids[$i] = intval($agents_ids[$i]["id"]);
    }
    return $agents_ids;
}
// function sb_get_avatar($first_name, $last_name = '')
// {
//     $picture_url = STMBX_URL . '/media/user.svg';
//     if (!empty($first_name) && substr($last_name, 0, 1) != '#') {
//         $file_name = rand(99, 9999999) . '.png';
//         $picture_url = sb_download_file('https://ui-avatars.com/api/?background=202234&color=FFFFFF&size=512&font-size=0.35&name=' . $first_name . '+' . $last_name, $file_name);
//         $path = sb_upload_path(false, true) . '/' . $file_name;
//         if (!file_exists($path) || filesize($path) < 1000) $picture_url = STMBX_URL . '/media/user.svg';
//     }
//     return $picture_url;
// }

function sb_get_avatar($first_name, $last_name = '')
{
    // return STMBX_URL . '/media/user.svg';
    return 'https://subzero.sirv.com/Images/user.svg';
}

// function sb_get_avatar()
// {
//     $file_name = rand(99, 9999999) . ".png";
//     $image_url = "https://source.unsplash.com/random/200x200/?places";
//     $picture_url = sb_download_file($image_url, $file_name);
//     $path = sb_upload_path(false, true) . "/" . $file_name;
//     if (!file_exists($path) || filesize($path) < 1000) {
//         $picture_url = STMBX_URL . "/media/user.svg";
//     }

//     return $picture_url;
// }


function sb_get_users_with_details($details, $user_ids = false)
{
    $response = [];
    $primary_details = ["last_name", "email", "profile_image", "department"];
    if ($user_ids == "all") {
        $user_ids = false;
    }
    if ($user_ids == "agents") {
        $user_ids = sb_get_agents_ids();
    }
    if ($user_ids) {
        $user_ids =
            "(" .
            (is_string($user_ids)
                ? str_replace(" ", "", sb_db_escape($user_ids))
                : sb_db_escape(substr(json_encode($user_ids), 1, -1))) .
            ")";
    }
    for ($i = 0; $i < count($details); $i++) {
        $detail = sb_db_escape($details[$i]);
        $primary = in_array($detail, $primary_details);
        if ($primary) {
            $query =
                "SELECT id, " .
                $detail .
                " AS `value` FROM sb_users WHERE " .
                $detail .
                " IS NOT NULL AND " .
                $detail .
                ' <> ""' .
                ($user_ids ? " AND id IN " . $user_ids : "");
        } else {
            $query =
                'SELECT user_id AS `id`, value FROM sb_users_data WHERE slug = "' .
                $detail .
                '"' .
                ($user_ids ? " AND user_id IN " . $user_ids : "");
        }
        $response[$detail] = sb_db_get($query, false);
    }
    return $response;
}

function sb_get_active_user_ID()
{
    $active_user = sb_get_active_user();
    return $active_user ? sb_isset($active_user, "id") : false;
}

function sb_is_typing($user_id, $conversation_id)
{
    $typing = sb_db_get(
        "SELECT COUNT(*) as typing FROM sb_users WHERE id = " .
            sb_db_escape($user_id, true) .
            ' AND typing = "' .
            sb_db_escape($conversation_id, true) .
            '"'
    );
    return $typing["typing"] != 0;
}

function sb_is_agent_typing($conversation_id)
{
    return sb_db_get(
        "SELECT id, first_name, last_name FROM sb_users WHERE typing = " .
            sb_db_escape($conversation_id, true) .
            ' AND (user_type = "agent" OR user_type = "admin") AND id <> ' .
            sb_get_active_user_ID()
    );
}

function sb_set_typing(
    $user_id = false,
    $conversation_id = false,
    $source = false
) {
    if ($source && isset($source[0])) {
        if ($source[0] == "fb") {
            return sb_messenger_set_typing($source[1], $source[2]);
        }
        if ($source[0] == "tw") {
            return sb_twitter_set_typing($source[1]);
        }
        return false;
    } else {
        return sb_pusher_active()
            ? sb_pusher_trigger("private-user-" . $user_id, "client-typing")
            : sb_db_query(
                "UPDATE sb_users SET typing = " .
                    sb_db_escape($conversation_id, true) .
                    " WHERE id = " .
                    sb_db_escape($user_id, true)
            );
    }
}

function sb_set_rating(
    $settings,
    $payload = false,
    $message_id = false,
    $message = false
) {
    if (!isset($settings["conversation_id"])) {
        return new SBValidationError("conversation-id-not-found");
    } elseif (sb_conversation_security_error($settings["conversation_id"])) {
        return new SBError("security-error", "sb_set_rating");
    }
    if (isset($settings["rating"])) {
        $ratings = sb_get_external_setting("ratings");
        if (!isset($ratings)) {
            $ratings = [];
        }
        $ratings[$settings["conversation_id"]] = $settings;
        sb_save_external_setting("ratings", $ratings);
        if ($message_id && $settings["source"] == "tk") {
            sb_update_message($message_id, $message, false, $payload);
        }
        return true;
    }
    return false;
}

function sb_get_rating($agent_id)
{
    $ratings = sb_get_external_setting("ratings");
    $positive = 0;
    $negative = 0;
    if (!empty($ratings)) {
        foreach ($ratings as $rating) {
            if (sb_isset($rating, "agent_id", -1) == $agent_id) {
                if ($rating["rating"] == 1) {
                    $positive++;
                } else {
                    $negative++;
                }
            }
        }
    }
    return [$positive, $negative];
}

function sb_split_name($name)
{
    $space_in_name = strpos($name, " ");
    return [
        $space_in_name
            ? trim(substr($name, 0, $space_in_name))
            : $name . $space_in_name,
        $space_in_name ? trim(substr($name, $space_in_name)) : "",
    ];
}






/*
 * -----------------------------------------------------------
 * ONLINE STATUS
 * -----------------------------------------------------------
 *
 * 1. Update the user last activity date
 * 2. Check if a date is considered online
 * 3. Check if at least one agent or admin is online
 * 4. Return the online users
 * 5. Return an array with the IDs of the online users
 * 6. Check if a user is online
 *
 */

// function sb_update_users_last_activity(
//     $user_id = -1,
//     $return_user_id = -1,
//     $check_slack = false
// ) {
//     $result =
//         $user_id != -1
//         ? sb_update_user_value(
//             $user_id,
//             "last_activity",
//             gmdate("Y-m-d H:i:s")
//         )
//         : false;
//     if ($return_user_id != -1) {
//         $last_activity = sb_db_get(
//             "SELECT last_activity FROM sb_users WHERE id = " .
//                 sb_db_escape($return_user_id, true)
//         );
//         if (!isset($last_activity["last_activity"])) {
//             return "offline";
//         }
//         if (sb_is_online($last_activity["last_activity"])) {
//             return "online";
//         } else {
//             return defined("SB_SLACK") && $check_slack
//                 ? sb_slack_presence($return_user_id)
//                 : "offline";
//         }
//     }
//     return $result;
// }


function sb_update_users_last_activity(
    $user_id = -1,
    $return_user_id = -1
) {
    $result =
        $user_id != -1
        ? sb_update_user_value(
            $user_id,
            "last_activity",
            gmdate("Y-m-d H:i:s")
        )
        : false;
    if ($return_user_id != -1) {
        $last_activity = sb_db_get(
            "SELECT last_activity FROM sb_users WHERE id = " .
                sb_db_escape($return_user_id, true)
        );
        if (!isset($last_activity["last_activity"])) {
            return "offline";
        }
        if (sb_is_online($last_activity["last_activity"])) {
            return "online";
        } else {
            return "offline";
        }
    }
    return $result;
}


// function sb_is_online($datetime)
// {
//     return strtotime($datetime) > strtotime(gmdate("Y-m-d H:i:s", time() - 30));
// }

// function sb_agents_online()
// {
//     $online = $online = sb_pusher_active()
//         ? sb_pusher_agents_online()
//         : intval(
//             sb_db_get(
//                 'SELECT COUNT(*) as count FROM sb_users WHERE (user_type = "agent" OR user_type = "admin") AND last_activity > "' .
//                     gmdate("Y-m-d H:i:s", time() - 30) .
//                     '"'
//             )["count"]
//         ) > 0;
//     return $online
//         ? true
//         : (defined("SB_SLACK") && sb_get_setting("slack-active")
//             ? sb_slack_presence() == "online"
//             : false);
// }

function sb_is_online($datetime)
{
    return strtotime($datetime) > strtotime(gmdate("Y-m-d H:i:s", time() - 30));
}

function sb_agents_online()
{
    $online = sb_pusher_active()
        ? sb_pusher_agents_online()
        : intval(
            sb_db_get(
                'SELECT COUNT(*) as count FROM sb_users WHERE (user_type = "agent" OR user_type = "admin") AND last_activity > "' .
                    gmdate("Y-m-d H:i:s", time() - 30) .
                    '"'
            )["count"]
        ) > 0;
    return $online;
}


function sb_get_online_users($sorting = "creation_time", $agents = false)
{
    $online_user_ids = sb_get_online_user_ids($agents);
    return empty($online_user_ids)
        ? []
        : sb_get_users([$sorting, "DESC"], [], "", 0, false, $online_user_ids);
}

function sb_get_online_user_ids($agents = false)
{
    $user_ids = [];
    $query =
        "SELECT id FROM sb_users WHERE (" .
        ($agents
            ? 'user_type = "admin" OR user_type = "agent"'
            : 'user_type = "visitor" OR user_type = "lead" OR user_type = "user"') .
        ")";
    if (sb_pusher_active()) {
        $users = sb_db_get($query, false);
        $users_id_check = [];
        $pusher_users = sb_pusher_get_online_users();
        for ($i = 0; $i < count($users); $i++) {
            array_push($users_id_check, $users[$i]["id"]);
        }
        for ($i = 0; $i < count($pusher_users); $i++) {
            $id = $pusher_users[$i]->id;
            if (in_array($id, $users_id_check)) {
                array_push($user_ids, $id);
            }
        }
    } else {
        $users = sb_db_get(
            $query .
                ' AND last_activity > "' .
                gmdate("Y-m-d H:i:s", time() - 30) .
                '"',
            false
        );
        for ($i = 0; $i < count($users); $i++) {
            array_push($user_ids, $users[$i]["id"]);
        }
    }
    return $user_ids;
}

// function sb_is_user_online($user_id)
// {
//     if (empty($user_id)) {
//         return false;
//     }
//     if (sb_pusher_active()) {
//         $users = sb_pusher_get_online_users();
//         for ($i = 0; $i < count($users); $i++) {
//             if ($users[$i]->id == $user_id) {
//                 return true;
//             }
//         }
//     } else {
//         $user = sb_db_get(
//             "SELECT last_activity, user_type FROM sb_users WHERE id = " .
//                 sb_db_escape($user_id, true)
//         );
//         if (
//             isset($user["last_activity"]) &&
//             sb_is_online($user["last_activity"])
//         ) {
//             return true;
//         }
//     }
//     if (
//         defined("SB_SLACK") &&
//         sb_get_setting("slack-active") &&
//         isset($user["user_type"]) &&
//         sb_is_agent($user["user_type"])
//     ) {
//         if (sb_slack_presence($user_id) == "online") {
//             return true;
//         }
//     }
//     return false;
// }

function sb_is_user_online($user_id)
{
    if (empty($user_id)) {
        return false;
    }
    if (sb_pusher_active()) {
        $users = sb_pusher_get_online_users();
        for ($i = 0; $i < count($users); $i++) {
            if ($users[$i]->id == $user_id) {
                return true;
            }
        }
    } else {
        $user = sb_db_get(
            "SELECT last_activity, user_type FROM sb_users WHERE id = " .
                sb_db_escape($user_id, true)
        );
        if (
            isset($user["last_activity"]) &&
            sb_is_online($user["last_activity"])
        ) {
            return true;
        }
    }
    return false;
}

function sb_get_user_by($by, $value)
{
    $query = SELECT_FROM_USERS . " FROM sb_users A WHERE ";
    if (empty($value)) {
        return false;
    }
    $value = sb_db_escape($value);
    switch ($by) {
        case "email":
            return sb_db_get($query . 'email = "' . $value . '" LIMIT 1');
        case "first_name":
            return sb_db_get($query . 'first_name = "' . $value . '" LIMIT 1');
        case "last_name":
            return sb_db_get($query . 'last_name = "' . $value . '" LIMIT 1');
        case "phone":
            return sb_db_get(
                $query .
                    'id IN (SELECT user_id FROM sb_users_data WHERE slug = "phone" AND (value = "' .
                    $value .
                    '" OR value = "' .
                    (strpos($value, "+") === false
                        ? "+" . $value
                        : str_replace("+", "00", $value)) .
                    '")) LIMIT 1'
            );
        default:
            return sb_db_get(
                $query .
                    'id IN (SELECT user_id FROM sb_users_data WHERE slug = "' .
                    sb_db_escape($by) .
                    '" AND value = "' .
                    $value .
                    '") LIMIT 1'
            );
    }
}





/*
 * -----------------------------------------------------------
 * CONVERSATIONS
 * -----------------------------------------------------------
 *
 * 1. Return the user details of each conversation. This function is used internally by other functions.
 * 2. Return the messages grouped by conversation
 * 3. Return only the conversations or messages older than the given date
 * 4. Return only the messages older than the given date of the conversation with the given ID
 * 5. Return only the conversations older than the given date of the user with the given ID
 * 6. Return the messages of the conversation with the given ID
 * 7. Search conversations by searching user details and messages contents
 * 8. Search conversations of the user with the given ID
 * 9. Create a new user covnersation and return the ID
 * 10. Return all the conversations of a user
 * 11. Return the ID of the last user conversation if any, otherwise create a new conversation and return its ID
 * 12. Update a conversation status with one of the allowed stutus:  live = 0, pending = 1, pending user = 2, archive = 3, trash = 4.
 * 13. Update the conversation department and alert the agents of that department
 * 14. Update the agent assigned to a conversation and alert the agent
 * 15. Save a conversation as a CSV file
 * 16. Internal notes
 * 17. Direct message
 * 18. Return an array with all agents who replied to a conversation
 * 19. Verify if the active user is an agent or if the given conversation is owned by the active user
 * 20. Set or update the conversation opened by the agent in the admin area
 * 21. Check if a conversation is currently open by an agent
 * 22. Count conversations
 * 23. Send all notifications types to all validated agents
 * 24. Check if the given conversation are assigned to a department or agent
 * 25. Return the ID of the last agent of a conversation
 * 26. Get the last message of a converation
 * 27. Delete conversation attachments
 *
 */


// refactored function to get conversation data


function sb_get_conversations_users($conversations)
{
    if (count($conversations) > 0) {
        $conversationIds = array_map(
            function ($conversation) {
                return sb_db_escape($conversation["conversation_id"]);
            },
            $conversations
        );

        // Modify the SQL query to include the tags field
        $result = sb_db_get(
            "SELECT sb_users.id, sb_users.first_name, sb_users.last_name, sb_users.profile_image, sb_users.user_type, sb_conversations.id AS `conversation_id`, sb_conversations.status_code, sb_conversations.label, sb_conversations.title, sb_conversations.agent_id, sb_conversations.source, sb_users_data.value AS phone, sb_conversations.tags 
            FROM sb_users 
            INNER JOIN sb_conversations ON sb_users.id = sb_conversations.user_id
            LEFT JOIN sb_users_data ON sb_users.id = sb_users_data.user_id AND sb_users_data.slug = 'phone'
            WHERE sb_conversations.id IN (" . implode(',', $conversationIds) . ")",
            false
        );

        foreach ($conversations as &$conversation) {
            $conversationId = $conversation["conversation_id"];
            $matchingResult = array_values(array_filter($result, function ($item) use ($conversationId) {
                return $item["conversation_id"] == $conversationId;
            }));

            if (!empty($matchingResult)) {
                $matchingResult = $matchingResult[0]; // Take the first matching result

                $conversation["first_name"] = $matchingResult["first_name"];
                $conversation["last_name"] = $matchingResult["last_name"];
                $conversation["profile_image"] = $matchingResult["profile_image"];
                $conversation["user_id"] = $matchingResult["id"];
                $conversation["conversation_status_code"] = $matchingResult["status_code"];
                $conversation["conversation_source"] = $matchingResult["source"];
                $conversation["user_type"] = $matchingResult["user_type"];
                $conversation["label"] = $matchingResult["label"];
                $conversation["tags"] = $matchingResult["tags"];

                // Include the phone number in the conversation data
                $conversation["phone"] = ($matchingResult["source"] == 'bm' || $matchingResult["source"] == 'tg' || $matchingResult["source"] == 'tw')
                    ? ucfirst($matchingResult["phone"])
                    : ($matchingResult["phone"] !== null ? $matchingResult["phone"] : 'Routin');
            }
        }
    }

    return $conversations;
}


// refactored function to search conversations
function sb_search_conversations($search) {
    $search = sb_db_escape(mb_strtolower($search));
    $cache_key = "search_$search";

    // Intenta obtener los datos de la caché
    $cached_result = cache_get($cache_key);
    if ($cached_result !== false) {
        return json_decode($cached_result, true); // Decodifica el JSON en array
    }

    // Construye la consulta SQL
    $query = "
        SELECT 
            sb_messages.*, 
            sb_users.user_type as message_user_type, 
            sb_conversations.title 
        FROM 
            sb_messages
            JOIN sb_users ON sb_users.id = sb_messages.user_id 
            JOIN sb_conversations ON sb_conversations.id = sb_messages.conversation_id
            JOIN sb_users_data ON sb_users_data.user_id = sb_users.id AND sb_users_data.slug = 'phone'
        WHERE 
            (
            LOWER(sb_users.first_name) LIKE '%$search%' OR 
            LOWER(sb_users.last_name) LIKE '%$search%' OR 
            LOWER(CONCAT(sb_users.first_name, ' ', sb_users.last_name)) LIKE '%$search%' OR 
            LOWER(sb_users.email) LIKE '%$search%' OR 
            LOWER(sb_conversations.title) LIKE '%$search%' OR
            LOWER(sb_conversations.tags) LIKE '%$search%' OR
            LOWER(sb_conversations.label) LIKE '%$search%' OR
            LOWER(sb_users_data.value) LIKE '%$search%' OR -- Condition for phone search
            LOWER(sb_messages.message) LIKE '%$search%' -- Condition for message search
            )
        GROUP BY sb_messages.conversation_id 
        ORDER BY sb_messages.creation_time DESC";

    $result = sb_db_get($query, false);

    if (is_array($result)) {
        $data = sb_get_conversations_users($result);
        cache_set($cache_key, json_encode($data)); // Almacena los datos en caché
        return $data;
    } else {
        return new SBError('db-error', 'sb_search_conversations', $result);
    }
}

function log_cache_action($message) {
    $log_file = 'log.txt'; // Ruta del archivo de log
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "$timestamp - $message\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

function cache_get($key) {
    $filename = 'cache/' . md5($key) . '.cache';
    if (file_exists($filename) && (filemtime($filename) > (time() - 600))) { // 10 minutos de expiración
        log_cache_action("Cache hit for key: $key");
        return file_get_contents($filename);
    }
    log_cache_action("Cache miss for key: $key");
    return false;
}

function cache_set($key, $data) {
    $filename = 'cache/' . md5($key) . '.cache';
    file_put_contents($filename, $data);
    log_cache_action("Cache set for key: $key");
}



// will work on this later
function sb_search_conversation_messages($search)
{
    $search = sb_db_escape(mb_strtolower($search));

    // Construct the SQL query to search messages and attachments
    $query = "
        SELECT 
            sb_messages.*, 
            sb_users.first_name, 
            sb_users.last_name, 
            sb_users.profile_image, 
            sb_users.user_type
        FROM 
            sb_messages
            JOIN sb_users ON sb_users.id = sb_messages.user_id
        WHERE 
            (
                LOWER(sb_messages.message) LIKE '%$search%' OR 
                LOWER(sb_messages.attachments) LIKE '%$search%'
            )
        ORDER BY sb_messages.creation_time DESC";

    // Execute the query and retrieve the results
    $result = sb_db_get($query, false);

    // Check the result and return the appropriate response
    if (is_array($result)) {
        return $result; // Return the list of messages that match the search criteria
    } else {
        return new SBError('db-error', 'sb_search_conversation_messages', $result);
    }
}




function sb_get_status_code($conversation_id)
{
    $code = "(";
    $code .= sb_db_escape($conversation_id) . ","; //$conversations[$i]['conversation_id']
    $code = substr($code, 0, -1) . ")";
    $result = sb_db_get(
        "SELECT sb_conversations.status_code AS status_code FROM sb_users, sb_conversations WHERE sb_users.id = sb_conversations.user_id AND sb_conversations.id IN " .
            $code,
        false
    );
    return $result[0]["status_code"];
}





function sb_get_conversations(
    $pagination = 0,
    $status_code = 0,
    $department = false,
    $agent = 0,
    $source = false,
    $tags = false
) {
    if ($status_code == 6) { // this number is a filter for my-conversations ul li
        $exclude_visitors =
            $agent > 0
            ? "AND C.id IN (SELECT A.id FROM sb_conversations A, sb_messages M,sb_users B WHERE A.agent_id=" .
            $agent .
            " AND M.user_id=" .
            $agent .
            ")"
            : "";
    } else {
        $exclude_visitors =
            $status_code == 3
            ? 'AND C.id IN (SELECT A.id FROM sb_conversations A, sb_users B WHERE B.user_type <> "visitor" AND A.user_id = B.id)'
            : "";
    }
    $status_code =
        $status_code == 0 || $status_code == 6
        ? "AND C.status_code <> 3 AND C.status_code <> 4"
        : "AND C.status_code = " . sb_db_escape($status_code);

    $tag_filter = $tags ? ' AND C.tags LIKE "%' . sb_db_escape($tags) . '%"' : '';

    // conversation quantity retrieved
    $result = sb_db_get(
        "SELECT A.*, B.user_type as message_user_type, C.title FROM sb_messages A, sb_users B, sb_conversations C WHERE B.id = A.user_id " .
            $status_code .
            " AND C.id = A.conversation_id" .
            ($source ? ' AND C.source = "' . sb_db_escape($source) . '"' : "") .
            ($department ? " AND C.department = " . sb_db_escape($department, true) : "") .
            sb_routing_and_department_db("C") .
            ' AND A.id IN (SELECT max(id) FROM sb_messages WHERE message <> "" OR attachments <> "" GROUP BY conversation_id) ' .
            $exclude_visitors .
            $tag_filter .
            " GROUP BY conversation_id ORDER BY A.creation_time DESC, A.id DESC LIMIT " .
            intval(sb_db_escape($pagination, true)) * 88 .
            ", 88",
        false
    );

    if (isset($result) && is_array($result)) {
        return sb_get_conversations_users($result);
    } else {
        return new SBError("db-error", "sb_get_conversations", $result);
    }
}


function sb_get_new_conversations($datetime, $department = false, $source = false, $tag = false)
{
    $datetime = sb_db_escape($datetime);
    // $result = sb_db_get('SELECT A.*, B.user_type as message_user_type, C.title FROM sb_messages A, sb_users B, sb_conversations C WHERE A.id IN (SELECT max(id) FROM sb_messages WHERE ' . (is_numeric($datetime) ? ('id > ' . $datetime) : ('creation_time > "' . $datetime . '"')) . ' GROUP BY conversation_id) AND B.id = A.user_id AND C.id = A.conversation_id' . sb_routing_and_department_db('C') . ' GROUP BY conversation_id ORDER BY A.id DESC', false);
    $result = sb_db_get(
        "SELECT A.*, B.user_type as message_user_type, C.title FROM sb_messages A, sb_users B, sb_conversations C WHERE A.id IN (SELECT max(id) FROM sb_messages WHERE " .
            (is_numeric($datetime)
                ? "id > " . $datetime
                : 'creation_time > "' . $datetime . '"') .
            " GROUP BY conversation_id) AND B.id = A.user_id AND C.id = A.conversation_id" .
            sb_routing_and_department_db("C") . ($source ? 'AND C.source = "' . sb_db_escape($source) . '"' : '') . ($tag ? ' AND C.tags LIKE "%' . sb_db_escape($tag) . '%"' : '') . ($department ? ' AND C.department = ' . sb_db_escape($department, true) : '') . ' GROUP BY conversation_id ORDER BY A.id DESC',
        false
    );
    if (isset($result) && is_array($result)) {
        return count($result) ? sb_get_conversations_users($result) : [];
    } else {
        return new SBError("db-error", "sb_get_new_conversations", $result);
    }
}


function sb_get_new_user_conversations($user_id, $datetime)
{
    $datetime = sb_db_escape($datetime);
    $user_id = sb_db_escape($user_id, true);
    return sb_db_get(
        "SELECT A.*, B.first_name, B.last_name, B.profile_image, B.user_type, C.status_code AS conversation_status_code, C.department, C.title FROM sb_messages A, sb_users B, sb_conversations C WHERE B.id = A.user_id AND A.conversation_id = C.id AND A.id IN (SELECT MAX(A.id) FROM sb_messages A, sb_conversations B WHERE A." .
            (is_numeric($datetime)
                ? "id > " . $datetime
                : 'creation_time > "' . $datetime . '"') .
            " AND A.conversation_id = B.id AND B.user_id = " .
            $user_id .
            " GROUP BY A.conversation_id) GROUP BY conversation_id ORDER BY C.id DESC",
        false
    );
}


function sb_get_user_conversations($user_id, $exclude_id = -1, $agent = false)
{
    $exclude =
        $exclude_id != -1
        ? " AND sb_messages.conversation_id <> " . sb_db_escape($exclude_id)
        : "";
    $user_id = sb_db_escape($user_id, true);
    $query_part = $agent
        ? "SELECT conversation_id FROM sb_messages WHERE user_id = " . $user_id
        : "SELECT id FROM sb_conversations WHERE user_id = " . $user_id;
    return sb_db_get(
        "SELECT sb_messages.*, sb_users.first_name, sb_users.last_name, sb_users.profile_image, sb_users.user_type, sb_conversations.status_code AS conversation_status_code, sb_conversations.department, sb_conversations.agent_id, sb_conversations.title FROM sb_messages, sb_users, sb_conversations WHERE sb_users.id = sb_messages.user_id" .
            sb_routing_and_department_db() .
            ' AND sb_messages.conversation_id = sb_conversations.id AND sb_messages.id IN (SELECT max(sb_messages.id) FROM sb_messages, sb_conversations WHERE (sb_messages.message <> "" OR sb_messages.attachments <> "") AND sb_messages.conversation_id = sb_conversations.id' .
            ($agent ? "" : "") .
            $exclude .
            " GROUP BY conversation_id) AND sb_messages.conversation_id IN (" .
            $query_part .
            ") GROUP BY conversation_id ORDER BY id DESC",
        false
    );
}


function sb_get_clientStatus_conversations($exclude_id = -1, $agent = false)
{
    $exclude = ($exclude_id != -1) ? " AND sb_messages.conversation_id <> " . sb_db_escape($exclude_id) : "";

    $clientStatus = array(
        "Abierto",
        "Presupuesto",
        "Consulta",
        "Contactado",
        "Visitado",
        "Calificado",
        "Confirmado",
        "Pendiente",
        "Resuelto",
        "Pagado",
        "VIP",
        "Descartado",
        "NA",
    );

    $labels_condition = implode("', '", $clientStatus);

    $query = "SELECT sb_messages.*, sb_users.first_name, sb_users.last_name, sb_users.profile_image, sb_users.user_type, sb_conversations.user_id, sb_conversations.status_code AS conversation_status_code, sb_conversations.department, sb_conversations.agent_id, sb_conversations.title 
        FROM sb_messages, sb_users, sb_conversations 
        WHERE sb_users.id = sb_messages.user_id" . sb_routing_and_department_db() . " 
        AND sb_messages.conversation_id = sb_conversations.id 
        AND sb_messages.id IN (SELECT MAX(sb_messages.id) FROM sb_messages, sb_conversations 
                                WHERE (sb_messages.message <> '' OR sb_messages.attachments <> '') 
                                AND sb_messages.conversation_id = sb_conversations.id" . ($agent ? "" : "") . $exclude . " 
                                GROUP BY conversation_id) 
        AND sb_messages.conversation_id IN 
            (SELECT id FROM sb_conversations WHERE user_id IN 
                (SELECT user_id FROM sb_conversations A, sb_users B 
                    WHERE B.id = A.user_id 
                    AND label IN ('$labels_condition'))
            ) 
        GROUP BY conversation_id 
        ORDER BY id DESC";

    return sb_db_get($query, false);
}


// refactored function to get last conversation id 

function sb_get_last_conversation_id_or_create($user_id, $conversation_status_code = 1)
{
    $user_id = sb_db_escape($user_id, true);

    $conversation_id = sb_isset(
        sb_db_get(
            "SELECT id FROM sb_conversations WHERE user_id = $user_id ORDER BY creation_time DESC LIMIT 1"
        ),
        "id"
    );

    if ($conversation_id) {
        return $conversation_id;
    } else {
        $newConversation = sb_new_conversation($user_id, $conversation_status_code);
        return sb_isset($newConversation, "id");
    }
}


function sb_get_new_messages(
    $user_id,
    $conversation_id,
    $last_datetime,
    $last_id = false
) {
    $last_datetime = sb_db_escape($last_datetime);
    $last_id = $last_id ? sb_db_escape($last_id, true) : false;
    $result = sb_db_get(
        'SELECT sb_messages.*, sb_users.first_name, sb_users.last_name, sb_users.profile_image, sb_users.user_type FROM sb_messages, sb_users, sb_conversations WHERE (sb_messages.creation_time > "' .
            $last_datetime .
            '"' .
            ($last_id ? " OR sb_messages.id > " . $last_id : "") .
            ") AND sb_messages.conversation_id = " .
            sb_db_escape($conversation_id, true) .
            " AND sb_users.id = sb_messages.user_id AND sb_conversations.user_id = " .
            sb_db_escape($user_id, true) .
            " AND sb_messages.conversation_id = sb_conversations.id ORDER BY sb_messages.id ASC",
        false
    );

    if (isset($result) && is_array($result)) {
        return $result;
    } else {
        // return new SBError("db-error", "sb_get_new_messages", $result);
    }
}
function sb_get_conversation_total($user_id = false, $conversation_id = false) {
    $user_id = $user_id ? $_POST["user_id"] : $user_id;
    $conversation_id = $conversation_id ? $_POST["conversation_id"] : $conversation_id;
    $cache_key = "conversation_total_$user_id_$conversation_id";
    
    // Intenta obtener los datos de la caché
    $cached_result = cache_get($cache_key);
    if ($cached_result !== false) {
        return intval($cached_result); // Decodifica el JSON en un entero
    }

    $limit = $_POST["limit"];
    $query = "SELECT sb_messages.*, sb_users.first_name, sb_users.last_name, sb_users.profile_image, sb_users.user_type 
              FROM sb_messages, sb_users, sb_conversations 
              WHERE sb_messages.conversation_id = $conversation_id" .
              (sb_is_agent() ? "" : " AND sb_conversations.user_id = $user_id") .
              " AND sb_messages.conversation_id = sb_conversations.id 
              AND sb_users.id = sb_messages.user_id 
              ORDER BY sb_messages.id ASC";
    
    $messages = sb_db_get($query, false);

    if (isset($messages) && is_array($messages)) {
        $total = COUNT($messages);
        cache_set($cache_key, $total); // Almacena el total en caché
        return $total;
    } else {
        return new SBError("db-error", "sb_get_conversation_total", $messages);
    }
}


function sb_get_conversation($user_id = false, $conversation_id = false) {
    $user_id = $user_id ? sb_db_escape($user_id, true) : false;
    $conversation_id = sb_db_escape($conversation_id, true);
    $cache_key = "conversation_$user_id_$conversation_id";

    // Intenta obtener los datos de la caché
    $cached_result = cache_get($cache_key);
    if ($cached_result !== false) {
        return json_decode($cached_result, true); // Decodifica el JSON en array
    }

    $rows = sb_get_conversation_total($user_id, $conversation_id);
    $start = isset($_POST["load_chat"]) && $rows - $_POST["load_chat"] > 0 ? abs($rows - $_POST["load_chat"]) : 0;
    $limit = $rows - $_POST["load_chat"] > 0 ? $_POST["limit"] : $rows % $_POST["limit"];
    $limit_query = $limit != 0 ? "ORDER BY sb_messages.id ASC LIMIT $start, $limit" : "";

    $query_messages = "SELECT sb_messages.*, sb_users.first_name, sb_users.last_name, sb_users.profile_image, sb_users.user_type 
                       FROM sb_messages, sb_users, sb_conversations 
                       WHERE sb_messages.conversation_id = $conversation_id" .
                       (sb_is_agent() ? "" : " AND sb_conversations.user_id = $user_id") .
                       " AND sb_messages.conversation_id = sb_conversations.id 
                       AND sb_users.id = sb_messages.user_id 
                       $limit_query";

    $messages = sb_db_get($query_messages, false);

    if (isset($messages) && is_array($messages)) {
        $query_details = "SELECT sb_users.id as user_id, sb_users.first_name, sb_users.last_name, sb_users.profile_image, sb_users.user_type, 
                          sb_conversations.id, sb_conversations.title, sb_conversations.creation_time, sb_conversations.status_code as conversation_status_code, 
                          sb_conversations.department, sb_conversations.agent_id, sb_conversations.source, sb_conversations.label, sb_conversations.extra, sb_conversations.tags 
                          FROM sb_users, sb_conversations 
                          WHERE sb_conversations.id = $conversation_id" .
                          (sb_is_agent() ? "" : " AND sb_users.id = $user_id") .
                          " AND sb_users.id = sb_conversations.user_id LIMIT 1";

        $details = sb_db_get($query_details);

        if ($details) {
            $details["busy"] = false;
            if (sb_is_agent()) {
                $active_user = sb_get_active_user();
                if ($active_user) {
                    $is_queue = sb_get_multi_setting("queue", "queue-active");
                    $is_routing = sb_get_setting("routing");
                    $is_hide_conversations = sb_get_multi_setting("agent-hide-conversations", "agent-hide-conversations-active");
                    $is_show_unassigned_conversations = sb_get_multi_setting("agent-hide-conversations", "agent-hide-conversations-view");
                   
                    // if (
                    //     $active_user["user_type"] == "agent" &&
                    //     ((!empty($active_user["department"]) &&
                    //         $active_user["department"] !=
                    //         $details["department"]) ||
                    //         ($is_hide_conversations &&
                    //             !$is_show_unassigned_conversations &&
                    //             empty($details["agent_id"])) ||
                    //         (!empty($details["agent_id"]) &&
                    //             $active_user["id"] != $details["agent_id"] &&
                    //             ($is_queue ||
                    //                 $is_routing ||
                    //                 $is_hide_conversations)))
                    // ) {
                    //     return "agent-not-authorized";
                    // }
                    if (!$is_queue && !$is_routing && (!$is_hide_conversations || $is_show_unassigned_conversations)) {
                        $agent_id = sb_is_active_conversation_busy($conversation_id, sb_get_active_user_ID());
                        if ($agent_id) {
                            $details["busy"] = sb_get_user($agent_id);
                        }
                        sb_set_agent_active_conversation($conversation_id);
                    }
                    if (!sb_get_setting('disable-notes')) {
                        $details['notes'] = sb_get_notes($conversation_id);
                    }
                    $details['tags'] = $details['tags'] ? explode(',', $details['tags']) : [];
                }
            }

            $result = [
                "messages" => $messages,
                "details" => $details,
                "total_rows" => $rows,
            ];
            cache_set($cache_key, json_encode($result)); // Almacena el resultado en caché
            return $result;
        }
    } else {
        return new SBError("db-error", "sb_get_conversation", $messages);
    }
    return false;
}

function sb_new_conversation(
    $user_id,
    $status_code = false,
    $title = "",
    $department = -1,
    $agent_id = -1,
    $source = false,
    $extra = false,
    $tags = false
) {
    if (!sb_isset_num($agent_id)) {
        if (
            sb_get_setting("routing") &&
            !sb_get_multi_setting("queue", "queue-active")
        ) {
            $agent_id = sb_routing(-1, $department);
        }
    }

    $user_id = sb_db_escape($user_id, true);

    $tagsValue = sb_db_escape(is_string($tags) ? $tags : implode(',', $tags));

    $conversation_id = sb_db_query(
        "INSERT INTO sb_conversations(user_id, title, status_code, creation_time, department, agent_id, source, extra, tags) VALUES (" .
            $user_id .
            ', "' .
            sb_db_escape(ucfirst($title)) .
            '", "' .
            ($status_code == -1 || $status_code === false || $status_code === ""
                ? 2
                : sb_db_escape($status_code)) .
            '", "' .
            gmdate("Y-m-d H:i:s") .
            '", ' .
            (sb_isset_num($department) ? sb_db_escape($department) : "NULL") .
            ", " .
            (sb_isset_num($agent_id) ? sb_db_escape($agent_id, true) : "NULL") .
            ", " .
            ($source ? '"' . sb_db_escape($source) . '"' : "NULL") .
            ", " .
            ($extra ? '"' . sb_db_escape($extra) . '"' : "NULL") .
            ", " .
            ($tags ? '"' . sb_db_escape($tagsValue) . '"' : "NULL") .
            ")",
        true
    );
    if (is_numeric($conversation_id)) {
        $conversation = sb_get_conversation($user_id, $conversation_id);
        if (sb_pusher_active()) {
            sb_pusher_trigger("private-user-" . $user_id, "new-conversation", [
                "conversation_user_id" => $user_id,
                "conversation_id" => $conversation_id,
            ]);
        }
        sb_webhooks("SBNewConversationCreated", $conversation);
        return $conversation;
    } elseif (
        sb_is_error($conversation_id) &&
        sb_db_get(
            "SELECT count(*) as count FROM sb_users WHERE id = " . $user_id
        )["count"] == 0
    ) {
        return new SBValidationError("user-not-found");
    }
    return $conversation_id;
}


function sb_update_conversation_source($conversation_id, $source)
{
    $conversation_id = sb_db_escape($conversation_id, true);
    $agent = sb_is_agent();
    $q =
        'UPDATE sb_conversations SET `source` = \'' .
        sb_db_escape($source) .
        '\' WHERE id = ' .
        $conversation_id;
    $response = sb_db_query($q);
    if ($agent && sb_get_setting("logs")) {
        sb_logs(
            "changed the source of the conversation #" .
                $conversation_id .
                " to " .
                $source
        );
    }
    return $response;
}
function sb_update_clientStatus_conversations($conversation_id, $label)
{
    $conversation_id = sb_db_escape($conversation_id, true);
    $agent = sb_is_agent();
    $q =
        'UPDATE sb_conversations SET `label` = \'' .
        sb_db_escape($label) .
        '\' WHERE id = ' .
        $conversation_id;
    $response = sb_db_query($q);
    if ($agent && sb_get_setting("logs")) {
        sb_logs(
            "changed the label of the conversation #" .
                $conversation_id .
                " to " .
                $label
        );
    }
    return $response;
}

function sb_update_conversation_status($conversation_id, $status)
{
    $response = false;
    $conversation_id = sb_db_escape($conversation_id, true);
    $agent = sb_is_agent();
    if ($status == 4) {
        $opt = "option";
        sb_update_conversation_agent($conversation_id, null, false);
        sb_update_conversation_department($conversation_id, 0, false);
        sb_db_query(
            'UPDATE sb_messages SET payload = "" WHERE payload LIKE "{\"welcome_option%" AND creation_time > "' .
                gmdate("Y-m-d H:i:s", time() - 864000) .
                '" AND conversation_id = ' .
                sb_db_escape($conversation_id, true)
        );
        sb_db_query(
            'UPDATE sb_messages SET payload = "" WHERE payload LIKE "{\"' .
                $opt .
                '_assigned%" AND creation_time > "' .
                gmdate("Y-m-d H:i:s", time() - 864000) .
                '" AND conversation_id = ' .
                sb_db_escape($conversation_id, true)
        );
    }
    if (in_array($status, [0, 1, 2, 3, 4])) {
        $response = sb_db_query(
            "UPDATE sb_conversations SET status_code = " .
                sb_db_escape($status) .
                " WHERE id = " .
                $conversation_id
        );
        if ($status == 3 || $status == 4) {
            sb_db_query(
                'DELETE FROM sb_messages WHERE payload = "{\"human-takeover\":true}" AND conversation_id = ' .
                    $conversation_id
            );
        }
    } else {
        if ($status == 5 && $agent) {
            $ids = sb_db_get(
                "SELECT id FROM sb_conversations WHERE status_code = 4",
                false
            );
            for ($i = 0; $i < count($ids); $i++) {
                sb_delete_attachments($ids[$i]["id"]);
            }
            $response = sb_db_query(
                "DELETE FROM sb_conversations WHERE status_code = 4"
            );
        } else {
            $response = new SBValidationError("invalid-status-code");
        }
    }
    if ($agent && in_array($status, [3, 4]) && sb_get_setting("logs")) {
        sb_logs(
            "changed the status of the conversation #" .
                $conversation_id .
                " to " .
                ($status == 3 ? "archived" : "deleted")
        );
    }
    if (in_array($status, [3, 4]) && $agent) {
        sb_update_conversation_event(
            "conversation-status-update-" . $status,
            $conversation_id
        );
    }
    if (
        $status == 3 &&
        $agent &&
        sb_pusher_active() &&
        sb_get_setting("close-chat")
    ) {
        sb_pusher_trigger(
            "private-user-" .
                sb_db_get(
                    "SELECT user_id FROM sb_conversations WHERE id = " .
                        $conversation_id
                )["user_id"],
            "new-message"
        );
    }
    return $response;
}

function sb_update_conversation_department(
    $conversation_id,
    $department,
    $message = false
) {
    if (sb_conversation_security_error($conversation_id)) {
        return new SBError(
            "security-error",
            "sb_update_conversation_department"
        );
    }
    $empty_department = empty($department) || $department == -1;
    $response = sb_db_query(
        "UPDATE sb_conversations SET department = " .
            ($empty_department ? "NULL" : sb_db_escape($department)) .
            " WHERE id = " .
            sb_db_escape($conversation_id, true)
    );
    if ($response) {
        if ($message) {
            sb_send_agents_notifications(
                $message,
                str_replace(
                    "{T}",
                    sb_is_agent()
                        ? sb_get_user_name()
                        : sb_get_setting("bot-name", "Dialogflow"),
                    sb_(
                        "This message has been sent because {T} assigned this conversation to your department."
                    )
                ),
                $conversation_id,
                false,
                false,
                ["force" => true]
            );
        }
        sb_update_conversation_event(
            "conversation-department-update-" . $department,
            $conversation_id,
            $message
        );
        if (sb_get_setting("logs")) {
            sb_logs(
                "assigned the conversation #" .
                    $conversation_id .
                    " to the department " .
                    ($empty_department ? "None" : "#" . $department)
            );
        }

        // Call sb_routing() to route conversation to an agent within the department
        if (!$empty_department) {
            sb_routing($conversation_id, $department, false);
        }

        return true;
    }
    return new SBError(
        "department-update-error",
        "sb_update_conversation_department",
        $response
    );
}

function sb_update_conversation_agent(
    $conversation_id,
    $agent_id,
    $message = false
) {
    if (sb_conversation_security_error($conversation_id)) {
        return new SBError("security-error", "sb_update_conversation_agent");
    }
    $conversation_id = sb_db_escape($conversation_id, true);
    if ($agent_id == "routing" || $agent_id == "routing-unassigned") {
        $agent_id = sb_routing(
            false,
            sb_isset(
                sb_db_get(
                    "SELECT department FROM sb_conversations WHERE id = " .
                        $conversation_id
                ),
                "department"
            ),
            $agent_id == "routing-unassigned"
        );
    }
    $empty_agent_id = empty($agent_id);
    if (
        !$empty_agent_id &&
        !in_array(
            sb_isset(
                sb_db_get(
                    "SELECT user_type FROM sb_users WHERE id = " .
                        sb_db_escape($agent_id, true)
                ),
                "user_type"
            ),
            ["agent", "admin"]
        )
    ) {
        return new SBError("not-an-agent", "sb_update_conversation_agent");
    }
    $response = sb_db_query(
        "UPDATE sb_conversations SET agent_id = " .
            ($empty_agent_id ? "NULL" : sb_db_escape($agent_id, true)) .
            ", status_code = 2 WHERE id = " .
            $conversation_id
    );
    if ($response) {
        if ($message) {
            sb_send_agents_notifications(
                $message,
                $empty_agent_id
                    ? ""
                    : str_replace(
                        "{T}",
                        sb_is_agent()
                            ? sb_get_user_name()
                            : sb_get_setting("bot-name", "Dialogflow"),
                        sb_(
                            "This message has been sent because {T} assigned this conversation to you."
                        )
                    ),
                $conversation_id,
                false,
                false,
                ["force" => true]
            );
        }
        if (!$empty_agent_id) {
            sb_update_conversation_event(
                "conversation-agent-update-" . $agent_id,
                $conversation_id,
                $message
            );
        }
        if (sb_get_setting("logs")) {
            sb_logs(
                "assigned the conversation #" .
                    $conversation_id .
                    " to the agent " .
                    ($empty_agent_id ? "None" : "#" . $agent_id)
            );
        }
        return true;
    }
    return new SBError(
        "agent-update-error",
        "sb_update_conversation_agent",
        $response
    );
}

function sb_update_conversation_event(
    $payload_event,
    $conversation_id,
    $message_preview = false
) {
    $payload = ["event" => sb_db_escape($payload_event)];
    if ($message_preview) {
        $payload["preview"] = sb_db_escape($message_preview);
    }
    sb_db_query(
        "INSERT INTO sb_messages(user_id, message, creation_time, status_code, attachments, payload, conversation_id) VALUES (" .
            sb_get_active_user_ID() .
            ', "", "' .
            gmdate("Y-m-d H:i:s") .
            '", 0, "", "' .
            sb_json_escape(json_encode($payload, JSON_INVALID_UTF8_IGNORE)) .
            '", ' .
            sb_db_escape($conversation_id, true) .
            ")"
    );
    if (sb_pusher_active()) {
        sb_pusher_trigger("agents", "update-conversations", [
            "conversation_id" => $conversation_id,
        ]);
    }
}

// Deprecated from V 3.2.4
function sb_csv_conversations($conversation_id)
{
    sb_transcript($conversation_id, "csv");
}

function sb_transcript($conversation_id, $type = false)
{
    if (sb_conversation_security_error($conversation_id)) {
        return new SBError("security-error", "sb_transcript");
    }
    $conversation = sb_db_get(
        "SELECT id, user_id, message, creation_time, attachments, payload FROM sb_messages WHERE conversation_id = " .
            sb_db_escape($conversation_id, true),
        false
    );
    $file_name =
        "conversation-" . $conversation_id . "-" . rand(100000, 999999999);
    $users = [];
    if ($type === false) {
        $type = sb_get_setting("transcript-type", "txt");
    }
    if ($type == "csv") {
        return sb_csv(
            $conversation,
            [
                "ID",
                "User ID",
                "Message",
                "Creation date",
                "Attachments",
                "Payload",
            ],
            $file_name
        );
    }
    if ($type == "txt") {
        $code = "";
        for ($i = 0; $i < count($conversation); $i++) {
            $message = $conversation[$i];
            if ($message["message"]) {
                $user_id = $message["user_id"];
                if (!isset($users[$user_id])) {
                    $users[$user_id] =
                        sb_get_user_name(sb_get_user($user_id)) .
                        " | ID " .
                        $user_id .
                        " | ";
                }
                $code .=
                    $users[$user_id] .
                    $message["message"] .
                    PHP_EOL .
                    PHP_EOL;
            }
        }
        sb_file(sb_upload_path() . "/" . $file_name . ".txt", $code);
        return sb_upload_path(true) . "/" . $file_name . ".txt";
    }
    return false;
}


function sb_get_notes($conversation_id)
{
    return sb_get_external_setting("notes-" . $conversation_id, []);
}

function sb_add_note(
    $conversation_id,
    $user_id,
    $name,
    $message,
    $alert,
    $timeZone,
    $status
    // $browserName,
    // $deviceType

) {
    $notes = sb_get_notes($conversation_id);
    $id = rand(0, 99999);
    array_push($notes, [
        "id" => $id,
        "user_id" => $user_id,
        "name" => $name,
        "message" => $message,
        "alert" => $alert,
        "time_zone" => $timeZone,
        "status" => $status
        // "browserName" => $browserName,
        // "deviceType" => $deviceType,
    ]);
    $response = sb_save_external_setting("notes-" . $conversation_id, $notes);
    return $response ? $id : $response;
}

function sb_update_note($conversation_id, $note_id, $status)
{
    $notes = sb_get_notes($conversation_id);
    for ($i = 0; $i < count($notes); $i++) {
        if ($notes[$i]["id"] == $note_id) {
            $notes[$i]["status"] = $status;
            return count($notes)
                ? sb_save_external_setting("notes-" . $conversation_id, $notes)
                : sb_db_query(
                    "UPDATE sb_settings SET value=" .
                        $notes .
                        ' WHERE name = "notes-' .
                        sb_db_escape($conversation_id) .
                        '" LIMIT 1'
                );
        }
    }
    return false;
}

function sb_delete_note($conversation_id, $note_id)
{
    $notes = sb_get_notes($conversation_id);
    for ($i = 0; $i < count($notes); $i++) {
        if ($notes[$i]["id"] == $note_id) {
            array_splice($notes, $i, 1);
            return count($notes)
                ? sb_save_external_setting("notes-" . $conversation_id, $notes)
                : sb_db_query(
                    'DELETE FROM sb_settings WHERE name = "notes-' .
                        sb_db_escape($conversation_id) .
                        '" LIMIT 1'
                );
        }
    }
    return false;
}

function sb_direct_message($user_ids, $message, $subject = false, $template = false)
{
    if ($user_ids == "all") {
        $user_ids = [];
        $items = sb_db_get(
            'SELECT id FROM sb_users WHERE user_type <> "agent" AND user_type <> "admin" AND user_type <> "bot"',
            false
        );
        for ($i = 0; $i < count($items); $i++) {
            array_push($user_ids, $items[$i]["id"]);
        }
    }

    $user_ids = is_string($user_ids)
        ? explode(",", str_replace(" ", "", $user_ids))
        : $user_ids;
    $user_ids_string = substr(json_encode($user_ids), 1, -1);
    $missing = sb_db_get(
        "SELECT id FROM sb_users WHERE id NOT IN (" .
            $user_ids_string .
            ") AND id NOT IN (SELECT user_id FROM sb_conversations)",
        false
    );


    //multiple users template send

    if ($template == "true") {
        $wa_to = [];
        foreach ($user_ids as $user_id) {
            sb_get_user_extra($user_id, 'phone') ? array_push($wa_to, sb_get_user_extra($user_id, 'phone')) : '';
        }
        if (count($user_ids) != count($wa_to)) {

            return new SBValidationError("Please Select All WhatsApp Users");
        }
        return ['template_phone' => $wa_to];
    }

    if (!empty($missing)) {

        $query =
            "INSERT INTO sb_conversations(user_id, title, status_code, creation_time) VALUES ";
        for ($i = 0; $i < count($missing); $i++) {
            $query .= "(" . $missing[$i]["id"] . ', "", 1, NOW()),';
        }

        sb_db_query(substr($query, 0, -1));
    }
    $conversations = sb_db_get(
        "SELECT user_id, id FROM sb_conversations WHERE user_id IN (" .
            $user_ids_string .
            ") GROUP BY user_id",
        false
    );

    $query =
        "INSERT INTO sb_messages(user_id, message, creation_time, status_code, attachments, payload, conversation_id) VALUES ";
    $active_user = sb_get_active_user();
    $active_user_id = $active_user["id"];
    $now = gmdate("Y-m-d H:i:s");
    $count = count($conversations);
    for ($i = 0; $i < $count; $i++) {
        $query .=
            "(" .
            $active_user_id .
            ', "' .
            sb_db_escape(
                sb_merge_fields($message, [
                    sb_get_user($conversations[$i]["user_id"]),
                ])
            ) .
            '", "' .
            $now .
            '", 0, "", "", ' .
            $conversations[$i]["id"] .
            "),";
    }
    $response = sb_db_query(substr($query, 0, -1));
    if (sb_is_error($response)) {
        return new SBValidationError($response);
    }

    // Pusher
    if (sb_pusher_active()) {
        $channels = [];
        for ($i = 0; $i < count($user_ids); $i++) {
            array_push($channels, "private-user-" . $user_ids[$i]);
        }
        sb_pusher_trigger($channels, "new-message");
        sb_update_users_last_activity($active_user_id);
    }

    // Push notifications
    if (
        sb_get_multi_setting(
            "push-notifications",
            "push-notifications-users-active"
        )
    ) {
        sb_push_notification(
            sb_get_user_name(),
            $message,
            $active_user["profile_image"],
            $user_ids
        );
    }

    // Messaging apps
    $conversations = sb_db_get(
        'SELECT user_id, id, source, extra FROM sb_conversations WHERE source <> "" AND user_id IN (' .
            $user_ids_string .
            ")",
        false
    );

    for ($i = 0; $i < count($conversations); $i++) {
        sb_messaging_platforms_send_message($message, $conversations[$i]);
    }

    sb_reports_update(
        "direct-messages",
        mb_substr($message, 0, 250) . " | " . $count
    );
    return $response;
}

function sb_get_agents_in_conversation($conversation_id)
{
    $rows = sb_db_get(
        'SELECT A.id, first_name, last_name, profile_image, B.conversation_id FROM sb_users A, sb_messages B WHERE (A.user_type = "agent" OR A.user_type = "admin") AND A.id = B.user_id AND conversation_id ' .
            (is_array($conversation_id)
                ? "IN (" . sb_db_escape(implode(",", $conversation_id)) . ")"
                : "= " . sb_db_escape($conversation_id, true)) .
            (sb_is_agent()
                ? ""
                : " AND conversation_id in (SELECT id FROM sb_conversations WHERE user_id = " .
                sb_get_active_user_ID() .
                ")") .
            " GROUP BY A.id, B.conversation_id",
        false
    );
    $response = [];
    for ($i = 0; $i < count($rows); $i++) {
        if (isset($response[$rows[$i]["conversation_id"]])) {
            array_push($response[$rows[$i]["conversation_id"]], $rows[$i]);
        } else {
            $response[$rows[$i]["conversation_id"]] = [$rows[$i]];
        }
    }
    return $response;
}

function sb_conversation_security_error($conversation_id)
{
    return !sb_is_agent() &&
        empty($GLOBALS["SB_FORCE_ADMIN"]) &&
        sb_isset(
            sb_db_get(
                "SELECT user_id FROM sb_conversations WHERE id = " .
                    $conversation_id
            ),
            "user_id"
        ) != sb_get_active_user_ID();
}

function sb_set_agent_active_conversation($conversation_id, $agent_id = false)
{
    $agent_id = $agent_id ? $agent_id : sb_get_active_user_ID();
    $active_agents_conversations = sb_get_external_setting(
        "active_agents_conversations",
        []
    );
    $previous_conversation_id = sb_isset(
        $active_agents_conversations,
        $agent_id,
        [false]
    );
    $active_agents_conversations[$agent_id] = [$conversation_id, time()];
    sb_save_external_setting(
        "active_agents_conversations",
        $active_agents_conversations
    );
    if (sb_pusher_active()) {
        sb_pusher_trigger("agents", "agent-active-conversation-changed", [
            "agent_id" => $agent_id,
            "previous_conversation_id" => $previous_conversation_id[0],
            "conversation_id" => $conversation_id,
        ]);
    }
}

function sb_is_active_conversation_busy($conversation_id, $skip = -1)
{
    $items = sb_get_external_setting("active_agents_conversations", []);
    $time = time();
    if (empty($items)) {
        return false;
    }
    foreach ($items as $agent_id => $value) {
        if (
            $agent_id != $skip &&
            $value[0] == $conversation_id &&
            $time - 3600 < $value[1] &&
            sb_is_user_online($agent_id)
        ) {
            return $agent_id;
        }
    }
    return false;
}

function sb_count_conversations($status_code = false)
{
    return sb_isset(
        sb_db_get(
            "SELECT COUNT(*) AS count FROM sb_conversations" .
                ($status_code
                    ? " WHERE status_code = " . sb_db_escape($status_code)
                    : "")
        ),
        "count"
    );
}

function sb_send_agents_notifications(
    $message,
    $bottom_message = false,
    $conversation_id = false,
    $attachments = false,
    $user = false,
    $extra = false
) {
    $user = $user
        ? $user
        : (sb_is_agent()
            ? sb_get_user_from_conversation($conversation_id)
            : sb_get_active_user());
    $user_name = sb_get_user_name($user);
    $recipients = "agents";
    $is_online = false;
    $force = sb_isset($extra, "force");
    if ($conversation_id) {
        $conversation = sb_db_get(
            "SELECT agent_id, department FROM sb_conversations WHERE id = " .
                sb_db_escape($conversation_id, true)
        );
        if ($conversation["department"]) {
            $recipients = "department-" . $conversation["department"];
        } elseif ($conversation["agent_id"]) {
            $recipients = $conversation["agent_id"];
            $is_online = sb_is_user_online($recipients);
        }
    }
    if (!$is_online) {
        if ($force || sb_get_setting("notify-agent-email")) {
            sb_email_create(
                $recipients,
                $user_name,
                $user["profile_image"],
                (isset($extra["email"]) ? $extra["email"] : $message) .
                    ($bottom_message
                        ? '<br><br><span style="color:#a8a8a8;font-size: var(--chat-text-size-8);">' .
                        $bottom_message .
                        "</span>"
                        : ""),
                $attachments,
                false,
                $conversation_id
            );
        }
        if ($force || sb_get_multi_setting("sms", "sms-active-agents")) {
            sb_send_sms(
                $message,
                $recipients,
                true,
                $conversation_id,
                $attachments
            );
        }
    }
    if (
        $force ||
        sb_get_multi_setting("push-notifications", "push-notifications-active")
    ) {
        sb_push_notification(
            $user_name,
            $message,
            $user["profile_image"],
            $recipients,
            $conversation_id,
            sb_isset($user, "id"),
            $attachments
        );
    }
    if (sb_pusher_active()) {
        sb_pusher_trigger("agents", "update-conversations", [
            "conversation_id" => $conversation_id,
        ]);
    }
    return true;
}

function sb_check_conversations_assignment(
    $conversations_ids,
    $agent_id = false,
    $department = false
) {
    if (empty($conversations_ids)) {
        return [];
    }
    $conversations_ids = sb_db_get(
        "SELECT id FROM sb_conversations WHERE id IN (" .
            sb_db_escape(implode(",", $conversations_ids)) .
            ") AND " .
            ($agent_id ? "agent_id <> " . sb_db_escape($agent_id, true) : "") .
            ($agent_id && $department ? " AND " : "") .
            ($department ? "department <> " . sb_db_escape($department) : ""),
        false
    );
    for ($i = 0; $i < count($conversations_ids); $i++) {
        $conversations_ids[$i] = $conversations_ids[$i]["id"];
    }
    return $conversations_ids;
}

function sb_get_last_agent_in_conversation($conversation_id)
{
    $agent = sb_db_get(
        "SELECT B.id, B.first_name, B.last_name, B.email, B.user_type, B.token, B.department  FROM sb_messages A, sb_users B WHERE A.conversation_id = " .
            sb_db_escape($conversation_id, true) .
            ' AND A.user_id = B.id AND (B.user_type = "agent" OR B.user_type = "admin") ORDER BY A.id LIMIT 1'
    );
    return isset($agent["id"]) ? $agent : false;
}

function sb_get_last_message(
    $conversation_id,
    $exclude_message = false,
    $user_id = false
) {
    return sb_db_get(
        'SELECT message, attachments, payload FROM sb_messages WHERE (message <> "" || attachments <> "")' .
            ($exclude_message
                ? ' AND message <> "' . sb_db_escape($exclude_message) . '"'
                : "") .
            " AND conversation_id = " .
            sb_db_escape($conversation_id, true) .
            ($user_id ? " AND user_id = " . sb_db_escape($user_id, true) : "") .
            " ORDER BY id DESC LIMIT 1"
    );
}

function sb_delete_attachments($conversation_id = false, $message_id = false)
{
    $attachments_all = sb_db_get(
        "SELECT attachments FROM sb_messages WHERE " .
            ($conversation_id ? "conversation_id" : "id") .
            " = " .
            sb_db_escape(
                $conversation_id ? $conversation_id : $message_id,
                true
            ),
        false
    );
    for ($i = 0; $i < count($attachments_all); $i++) {
        $attachments = sb_isset($attachments_all[$i], "attachments");
        if ($attachments) {
            $attachments = json_decode($attachments, true);
            for ($j = 0; $j < count($attachments); $j++) {
                $path =
                    sb_upload_path() .
                    str_replace(sb_upload_path(true), "", $attachments[$j][1]);
                if (file_exists($path)) {
                    unlink($path);
                }
            }
        }
    }
}




/*
 * -----------------------------------------------------------
 * QUEUE AND ROUTING
 * -----------------------------------------------------------
 *
 * 1. Update the queue and return the current queue status
 * 2. Internal function
 * 3. Assign the conversation to an agent
 * 4. Route conversations to agents
 *
 */

function sb_queue($conversation_id, $department = false)
{
    $position = 0;
    $queue_db = sb_get_external_setting("queue", []);
    $settings = sb_get_setting("queue");
    $queue = [];
    $index = 0;
    $unix_now = time();
    $unix_min = strtotime("-1 minutes");
    $conversation = sb_db_get(
        "SELECT user_id, agent_id, source FROM sb_conversations WHERE id = " .
            sb_db_escape($conversation_id, true)
    );
    $messaging_platform =
        !empty($conversation["source"]) && $conversation["source"] != "tk";
    $show_progress = !sb_execute_bot_message("offline", "check");
    if (!empty(sb_isset($conversation, "agent_id"))) {
        return 0;
    }
    if (!sb_isset_num($department) || $department == -1) {
        $department = false;
    }
    for ($i = 0; $i < count($queue_db); $i++) {
        if ($unix_min < intval($queue_db[$i][1])) {
            if ($queue_db[$i][0] == $conversation_id) {
                array_push($queue, [$conversation_id, $unix_now, $department]);
                $position = $index + 1;
            } else {
                array_push($queue, $queue_db[$i]);
            }
            if (!$department || $department == $queue_db[$i][2]) {
                $index++;
            }
        }
    }
    if (count($queue) == 0 || $position == 1) {
        $department = sb_db_escape($department);
        $counts = sb_db_get(
            "SELECT COUNT(*) AS `count`, agent_id FROM sb_conversations WHERE (status_code = 0 OR status_code = 1 OR status_code = 2) AND agent_id IS NOT NULL" .
                ($department ? " AND department = " . $department : "") .
                " GROUP BY agent_id",
            false
        );
        $cuncurrent_chats =
            !$settings || $settings["queue-concurrent-chats"] == ""
            ? 5
            : intval($settings["queue-concurrent-chats"]);
        $smaller = false;
        $pusher = sb_pusher_active();
        for ($i = 0; $i < count($counts); $i++) {
            $count = intval($counts[$i]["count"]);
            if (
                $count < $cuncurrent_chats &&
                ($smaller === false || $count < $smaller["count"])
            ) {
                $smaller = $counts[$i];
            }
        }
        if ($smaller === false) {
            $query = "";
            for ($i = 0; $i < count($counts); $i++) {
                $query .= $counts[$i]["agent_id"] . ",";
            }
            if ($pusher) {
                $agents_ids = sb_get_agents_ids(false);
                $online_agents = sb_pusher_get_online_users();
                for ($i = 0; $i < count($online_agents); $i++) {
                    $online_agents[$i] = $online_agents[$i]->id;
                }
                for ($i = 0; $i < count($agents_ids); $i++) {
                    if (!in_array($agents_ids[$i], $online_agents)) {
                        $query .= $agents_ids[$i] . ",";
                    }
                }
            }
            $smaller = sb_db_get(
                'SELECT id FROM sb_users WHERE user_type = "agent"' .
                    ($query
                        ? " AND id NOT IN (" . substr($query, 0, -1) . ")"
                        : "") .
                    ($pusher
                        ? ""
                        : ' AND last_activity > "' .
                        gmdate("Y-m-d H:i:s", time() - 30) .
                        '"') .
                    ($department ? " AND department = " . $department : "") .
                    " LIMIT 1"
            );
            if (empty($smaller)) {
                $smaller = false;
            } else {
                $smaller = ["agent_id" => $smaller["id"]];
            }
        }
        if ($smaller !== false) {
            sb_routing_assign_conversation(
                $smaller["agent_id"],
                $conversation_id
            );
            array_shift($queue);
            $position = 0;
            $user_id = $conversation["user_id"];
            $message = sb_(
                sb_isset(
                    $settings,
                    "queue-message-success",
                    'It\'s your turn! An agent will reply to you shortly.'
                )
            );
            $message_id = sb_send_message(
                sb_get_bot_id(),
                $conversation_id,
                $message,
                [],
                2
            )["id"];
            sb_send_agents_notifications(
                sb_isset(
                    sb_get_last_message($conversation_id, false, $user_id),
                    "message"
                ),
                false,
                $conversation_id
            );
            if ($messaging_platform) {
                sb_messaging_platforms_send_message(
                    $message,
                    $conversation_id,
                    $message_id
                );
            }
        } elseif ($position == 0) {
            array_push($queue, [$conversation_id, $unix_now, $department]);
            $position = $index + 1;
        }
    } elseif ($position == 0) {
        array_push($queue, [$conversation_id, $unix_now, $department]);
        $position = $index + 1;
    }
    sb_save_external_setting("queue", $queue);
    if ($messaging_platform && $position != 0) {
        sb_routing($conversation_id, $department);
        $message = sb_($settings["queue-message"]);
        if ($message && $show_progress) {
            $time =
                intval(sb_isset($settings, "queue-response-time", 5)) *
                $position;
            $message = str_replace(
                ["{position}", "{minutes}"],
                [$position, $time],
                $message
            );
            $message_id = sb_send_message(
                sb_get_bot_id(),
                $conversation_id,
                $message
            )["id"];
            sb_messaging_platforms_send_message(
                $message,
                $conversation_id,
                $message_id
            );
        }
    }
    return [$position, $show_progress];
}

function sb_routing_and_department_db(
    $table_name = "sb_conversations",
    $users = false
) {

    $hide = sb_get_multi_setting(
        "agent-hide-conversations",
        "agent-hide-conversations-active"
    );
    $routing =
        sb_isset(sb_get_active_user(), "user_type") != "agent" &&
        (sb_get_multi_setting("queue", "queue-active") ||
            sb_get_setting("routing") ||
            $hide);
    $routing_unassigned =
        $routing &&
        $hide &&
        sb_get_multi_setting(
            "agent-hide-conversations",
            "agent-hide-conversations-view"
        );
    $department = sb_get_agent_department();
    //this comment show all conversation list
    $query =
        ($routing
            ? " AND (" .
            $table_name .
            // ".agent_id = " .
            // sb_get_active_user_ID() .
            ($routing_unassigned
                ? " OR (" .
                $table_name .
                ".agent_id IS NULL OR " .
                $table_name .
                '.agent_id = ""))'
                : ")")
            : "");
    // ($department !== false
    //     ? " AND " . $table_name . ".department = " . $department
    //     : "");
    return $query
        ? ($users
            ? " AND id IN (SELECT user_id FROM sb_conversations WHERE " .
            substr($query, 4) .
            ")"
            : $query)
        : "";
}

function sb_routing_assign_conversation($agent_id, $conversation_id)
{
    return sb_db_query(
        "UPDATE sb_conversations SET agent_id = " .
            (is_null($agent_id) ? "NULL" : sb_db_escape($agent_id, true)) .
            " WHERE id = " .
            sb_db_escape($conversation_id, true)
    );
}

function sb_routing(
    $conversation_id = false,
    $department = false,
    $unassigned = false
) {
    $count_last = 0;
    $index = 0;
    $online_agents = sb_get_online_user_ids(true);
    $department = sb_db_escape($department, true);
    $agents = count($online_agents)
        ? sb_db_get(
            'SELECT id FROM sb_users WHERE user_type = "agent" AND id IN (' .
                implode(",", $online_agents) .
                ")" .
                (sb_isset_num($department)
                    ? " AND department = " . $department
                    : ""),
            false
        )
        : [];
    $count = count($agents);
    if ($count == 0) {
        if ($unassigned) {
            return $conversation_id
                ? sb_routing_assign_conversation(null, $conversation_id)
                : null;
        }
        $agents = sb_db_get(
            'SELECT id FROM sb_users WHERE user_type = "agent"' .
                (sb_isset_num($department)
                    ? " AND department = " . $department
                    : ""),
            false
        );
        $count = count($agents);
    }
    if ($count) {
        for ($i = 0; $i < $count; $i++) {
            $count_now = intval(
                sb_db_get(
                    "SELECT COUNT(*) AS `count` FROM sb_conversations WHERE (status_code = 0 OR status_code = 1 OR status_code = 2) AND agent_id = " .
                        $agents[$i]["id"]
                )["count"]
            );
            if ($count_last > $count_now) {
                $index = $i;
                break;
            }
            $count_last = $count_now;
        }
        return $conversation_id == -1 || !$conversation_id
            ? $agents[$index]["id"]
            : sb_routing_assign_conversation(
                $agents[$index]["id"],
                $conversation_id
            );
    }
    return false;
}









/*
 * -----------------------------------------------------------
 * MESSAGES
 * -----------------------------------------------------------
 *
 * 1. Add a message to a conversation
 * 2. Update an existing message
 * 3. Delete a message
 * 4. Send the default close message
 * 5. Convert the merge fields to the final values
 *
 */
function reply_get($message)
{
    $str = $message;
    $pattern = "/(\{)+[+,0-9]+[@,\w,\.]+(\})+|(\[)[\w\s]+(\])?/i";
    if (preg_match_all($pattern, $str, $matches)) {
        return preg_replace($pattern, "", $str);
    } else {
        return $message;
    }
}

function sb_send_message(
    $sender_id,
    $conversation_id,
    $message = "",
    $attachments = [],
    $conversation_status_code = -1,
    $payload = false,
    $queue = false,
    $recipient_id = false
) {
    $pusher = sb_pusher_active();
    $conversation_id = sb_db_escape($conversation_id, true);
    $user_id = $sender_id;
    if ($sender_id == -1) {
        $sender_id = sb_get_active_user_ID();
    } else {
        $sender_id = sb_db_escape($sender_id, true);
    }
    if ($sender_id != -1) {
        $attachments_json = "";
        $security = sb_is_agent();
        $attachments = sb_json_array($attachments);
        $conversation = sb_db_get(
            "SELECT status_code, agent_id, user_id, department, source FROM sb_conversations WHERE id = " .
                $conversation_id
        );
        $conversation_source = sb_isset($conversation, "source");
        $sender = sb_get_user($sender_id);
        $user = sb_db_get(
            "SELECT * FROM sb_users WHERE id = " . $conversation["user_id"]
        );
        $user_id = $user["id"];
        $is_sender_agent = sb_is_agent($sender);
        $is_sender_bot = sb_isset($sender, "user_type") == "bot";

        if (
            count($attachments) > 0
        ) {
            $attachments_json = "[";
            for ($i = 0; $i < count($attachments); $i++) {
                $attachments_json .=
                    '[\"' .
                    sb_db_escape($attachments[$i][0]) .
                    '\", \"' .
                    sb_db_escape($attachments[$i][1]) .
                    '\"],';
            }
            $attachments_json = substr($attachments_json, 0, -1) . "]";
        }
        if (
            $security ||
            $user_id == sb_get_active_user_ID() ||
            !empty($GLOBALS["SB_FORCE_ADMIN"])
        ) {
            // Message sending
            if ($recipient_id) {
                global $SB_LANGUAGE;
                $SB_LANGUAGE = [sb_get_user_language($recipient_id), "front"];
            }
            if (!$pusher) {
                sb_set_typing($sender_id, -1);
            }
            if ($payload !== false) {
                $payload = sb_json_array($payload);
            }
            $message = sb_merge_fields($message, [$user]);
            $response = sb_db_query(
                'INSERT INTO sb_messages(user_id, message, creation_time, status_code, attachments, payload, conversation_id) VALUES ("' .
                    $sender_id .
                    '", "' .
                    sb_db_escape($message) .
                    '", "' .
                    gmdate("Y-m-d H:i:s") .
                    '", 0, "' .
                    $attachments_json .
                    '", "' .
                    ($payload ? sb_db_json_escape($payload) : "") .
                    '", "' .
                    $conversation_id .
                    '")',
                true
            );

            if (!sb_is_agent()) {
                // Queue
                if ($queue) {
                    if ($conversation["status_code"] == 3) {
                        sb_routing_assign_conversation(null, $conversation_id);
                        $conversation["agent_id"] = "";
                    } else {
                        $queue = false;
                    }
                } elseif (
                    $conversation["status_code"] == 3 &&
                    (sb_get_setting("routing") ||
                        sb_get_multi_setting(
                            "agent-hide-conversations",
                            "agent-hide-conversations-active"
                        )
                    )
                ) {
                    // Routing change agent if offline
                    $last_agent = sb_get_last_agent_in_conversation(
                        $conversation_id
                    );
                    if ($last_agent && !sb_is_user_online($last_agent["id"])) {
                        sb_update_conversation_agent(
                            $conversation_id,
                            sb_get_setting("routing")
                                ? "routing"
                                : "routing-unassigned"
                        );
                    }
                }
            }

            // Conversation status code
            if ($conversation_status_code != "skip") {
                if (
                    $conversation_status_code == -1 ||
                    $conversation_status_code === false ||
                    !in_array($conversation_status_code, [0, 1, 2, 3, 4])
                ) {
                    $conversation_status_code =
                        $is_sender_agent && !$is_sender_bot
                        ? 1
                        : 2;
                }
                if ($conversation_status_code != $conversation["status_code"]) {
                    sb_db_query(
                        "UPDATE sb_conversations SET status_code = " .
                            sb_db_escape($conversation_status_code) .
                            " WHERE id = " .
                            $conversation_id
                    );
                }
            }

            if (sb_is_error($response)) {
                return $response;
            }
            if ($pusher) {
                $payload = [
                    "conversation_user_id" => $user_id,
                    "message_id" => $response,
                    "conversation_id" => $conversation_id,
                ];
                sb_pusher_trigger(
                    "private-user-" . $user_id,
                    "new-message",
                    $payload
                );
                sb_pusher_trigger("agents", "update-conversations", $payload);
                sb_update_users_last_activity($sender_id);
            }

            // Notifications
            $response_notifications = [];
            $recipient_id = false;
            $queue_active =
                empty($conversation["agent_id"]) &&
                !$is_sender_agent &&
                sb_get_multi_setting("queue", "queue-active");
            $user_name = sb_get_user_name($sender);
            if ($is_sender_agent) {
                $recipient_id = $user_id;
            } else {
                $last_agent = $last_agent
                    ? $last_agent
                    : sb_get_last_agent_in_conversation($conversation_id);
                if ($last_agent) {
                    $recipient_id = $last_agent["id"];
                } elseif (!empty($conversation["agent_id"])) {
                    $recipient_id = $conversation["agent_id"];
                } elseif (!empty($conversation["department"])) {
                    $recipient_id = "department-" . $conversation["department"];
                } else {
                    $recipient_id = "agents";
                }
                if (!empty($user["email"]) && defined("SB_TICKETS")) {
                    $channel = sb_get_setting("tickets-email-notification");
                    if (
                        $channel &&
                        ($channel == "all" ||
                            (!$conversation_source && $channel == "c") ||
                            $channel == $conversation_source ||
                            ($channel == "em-tk" &&
                                in_array($conversation_source, [
                                    "tk",
                                    "em",
                                ]))) &&
                        sb_db_get(
                            'SELECT COUNT(*) AS `count` FROM sb_messages WHERE conversation_id = "' .
                                $conversation_id .
                                '" LIMIT 1'
                        )["count"] == 1
                    ) {
                    }
                }
            }
            if (
                !$queue_active &&
                ((!$is_sender_agent &&
                    sb_get_multi_setting(
                        "push-notifications",
                        "push-notifications-active"
                    )
                ) ||
                    ($is_sender_agent &&
                        sb_get_multi_setting(
                            "push-notifications",
                            "push-notifications-users-active"
                        )
                    ))
            ) {
                //reply format msg get
                $message = reply_get($message);
                sb_push_notification(
                    $user_name,
                    $message,
                    $sender["profile_image"],
                    $recipient_id,
                    $conversation_id,
                    $user_id,
                    $attachments
                );
            }
            if (
                (!$queue_active ||
                    (!$is_sender_agent && !sb_agents_online())) &&
                !$is_sender_bot
            ) {
                $user_check =
                    $is_sender_agent &&
                    (!sb_is_user_online($user_id) ||
                        $conversation_source == "em");
                $agent_check =
                    !$is_sender_agent &&
                    (!is_numeric($recipient_id) ||
                        !sb_is_user_online($recipient_id));
                if (
                    ($agent_check &&
                        sb_get_multi_setting("sms", "sms-active-agents")) ||
                    ($user_check &&
                        sb_get_multi_setting("sms", "sms-active-users") &&
                        !in_array($conversation["source"], ["wa", "ww", "wx", "tg"]))
                ) {
                    $response_notification = sb_send_sms(
                        $message,
                        $recipient_id,
                        true,
                        $conversation_id,
                        $attachments
                    );
                    if ($response_notification) {
                        array_push($response_notifications, "sms");
                    }
                }
                if (
                    ($agent_check && sb_get_setting("notify-agent-email")) ||
                    ($user_check &&
                        !empty($user["email"]) &&
                        sb_get_setting("notify-user-email") &&
                        (empty($conversation["source"]) ||
                            in_array($conversation["source"], ["tk", "em"])))
                ) {
                    $response_notification = sb_email_create(
                        $recipient_id,
                        $user_name,
                        $sender["profile_image"],
                        $message,
                        $attachments,
                        $conversation["department"],
                        $conversation_id
                    );
                    if ($response_notification) {
                        array_push($response_notifications, "email");
                    }
                }
            }
            if ($is_sender_agent && sb_get_setting("logs")) {
                sb_logs(
                    "sent the message #" .
                        $response .
                        " in the conversation #" .
                        $conversation_id,
                    $sender
                );
            }
            sb_webhooks("SBMessageSent", [
                "user_id" => $sender_id,
                "conversation_user_id" => $user_id,
                "conversation_id" => $conversation_id,
                "conversation_status_code" => $conversation_status_code,
                "message_id" => $response,
                "message" => $message,
                " attachments" => $attachments,
            ]);
            return [
                "id" => $response,
                "queue" => $queue,
                "notifications" => $response_notifications,
            ];
        }
        return new SBError("security-error", "sb_send_message");
    } else {
        return new SBError("active-user-not-found", "sb_send_message");
    }
}


function sb_update_message(
    $message_id,
    $message = false,
    $attachments = false,
    $payload = false
) {
    return sb_update_or_delete_message(
        "update",
        $message_id,
        $message,
        $attachments,
        $payload
    );
}

function sb_delete_message($message_id)
{
    return sb_update_or_delete_message("delete", $message_id);
}

function sb_update_or_delete_message(
    $action,
    $message_id,
    $message = false,
    $attachments = false,
    $payload = false
) {
    $pusher = sb_pusher_active();
    $security = sb_is_agent() || !empty($GLOBALS["SB_FORCE_ADMIN"]);
    $conversation = false;
    $user_id = false;
    $response = false;
    $message_id = sb_db_escape($message_id, true);
    if (!$security || $pusher) {
        $conversation = sb_db_get(
            "SELECT id, user_id FROM sb_conversations WHERE id = (SELECT conversation_id FROM sb_messages WHERE id = " .
                $message_id .
                ")"
        );
        $user_id = sb_isset($conversation, "user_id");
        if ($user_id == sb_get_active_user_ID()) {
            $security = true;
        }
    }
    if ($security) {
        if ($action == "update") {
            if (
                $message === false &&
                $payload === false &&
                $attachments === false
            ) {
                return new SBValidationError("missing-arguments");
            }
            if ($attachments !== false) {
                $attachments = sb_json_array($attachments, false);
            }
            if ($payload !== false) {
                $payload = sb_json_array($payload, false);
            }
            $response = sb_db_query(
                "UPDATE sb_messages SET " .
                    ($message !== false
                        ? 'message = "' . sb_db_escape($message) . '",'
                        : "") .
                    ' creation_time = "' .
                    gmdate("Y-m-d H:i:s") .
                    '"' .
                    ($payload !== false
                        ? ', payload = "' . sb_db_json_escape($payload) . '"'
                        : "") .
                    ($attachments !== false
                        ? ', attachments = "' .
                        sb_db_json_escape($attachments) .
                        '"'
                        : "") .
                    " WHERE id = " .
                    $message_id
            );
        }
        if ($action == "delete") {
            sb_delete_attachments(false, $message_id);
            $response = sb_db_query(
                "DELETE FROM sb_messages WHERE id = " . $message_id
            );
        }
        if (sb_is_agent() && sb_get_setting("logs")) {
            sb_logs($action . "d the message #" . $message_id);
        }
        if ($response && $pusher) {
            $payload = [
                "conversation_user_id" => $user_id,
                "message_id" => $message_id,
                "conversation_id" => $conversation["id"],
            ];
            sb_pusher_trigger(
                "private-user-" . $user_id,
                "new-message",
                $payload
            );
            sb_pusher_trigger("agents", "update-conversations", $payload);
        }
        return $response;
    }
    return new SBError("security-error", "sb_" . $action . "_message");
}

function sb_close_message($conversation_id, $bot_id = false)
{
    $message = sb_get_multi_setting("close-message", "close-msg");
    if ($message) {
        if (!$bot_id) {
            $bot_id = sb_get_bot_id();
        }
        $message_id = sb_send_message(
            $bot_id,
            $conversation_id,
            $message,
            [],
            3,
            ["type" => "close-message"]
        )["id"];
        return sb_messaging_platforms_send_message(
            $message,
            $conversation_id,
            $message_id
        );
    }
    return false;
}



function sb_merge_fields($message, $marge_fields_values = [])
{
    $replace = "";
    $marge_fields = ["user_name", "user_email", "agent_name", "agent_email"];
    $marge_field = "";
    for ($i = 0; $i < count($marge_fields); $i++) {
        if (strpos($message, "{" . $marge_fields[$i]) !== false) {
            $marge_field = "{" . $marge_fields[$i] . "}";
            $value = isset($marge_fields_values[0])
                ? $marge_fields_values[0]
                : false;
            switch ($marge_fields[$i]) {
                case "user_name":
                    $replace = sb_get_user_name($value);
                    break;
                case "user_email":
                    $replace = $value
                        ? sb_isset($value, "email")
                        : sb_isset(
                            sb_get_active_user(),
                            "email",
                            "{user_email}"
                        );
                    break;
                case "agent_name":
                    $replace = sb_is_agent() ? sb_get_user_name() : "";
                    break;
                case "agent_email":
                    $replace = sb_is_agent()
                        ? sb_isset(sb_get_active_user(), "email", "")
                        : "";
                    break;
            }
        }
        $message = str_replace($marge_field, $replace, $message);
    }
    return $message;
}


/*
 * -----------------------------------------------------------
 * RICH MESSAGES
 * -----------------------------------------------------------
 *
 * 1. Get the custom rich messages ids including the built in ones
 * 2. Get the rich message with the given name
 * 3. Escape a rich message shortcode value
 * 4. Return the full shortcode and its parameters
 *
 */

function sb_get_rich_messages_ids()
{
    $result = sb_get_external_setting("rich-messages");
    $ids = ["email", "registration", "login", "timetable", "articles"];
    if (
        is_array($result) &&
        isset($result["rich-messages"]) &&
        is_array($result["rich-messages"][0])
    ) {
        for ($i = 0; $i < count($result["rich-messages"][0]); $i++) {
            array_push(
                $ids,
                $result["rich-messages"][0][$i]["rich-message-name"]
            );
        }
        return $ids;
    }

    return $ids;
}

function sb_get_rich_message($name, $settings = false)
{
    if (
        in_array($name, [
            "registration",
            "registration-tickets",
            "login",
            "login-tickets",
            "timetable",
            "articles",
        ])
    ) {
        $title = "";
        $message = "";
        $success = "";
        switch ($name) {
            case "registration-tickets":
            case "registration":
                $registration_tickets = $name == "registration-tickets";
                $active_user = sb_get_active_user();
                $last_name = sb_get_setting("registration-last-name");
                $user =
                    $active_user && !sb_is_agent($active_user["user_type"])
                    ? sb_get_user($active_user["id"], true)
                    : [
                        "profile_image" => "",
                        "first_name" => "",
                        "last_name" => "",
                        "email" => "",
                        "password" => "",
                        "user_type" => "visitor",
                        "details" => [],
                    ];
                $visitor =
                    !$user ||
                    $user["user_type"] == "visitor" ||
                    $user["user_type"] == "lead";
                $settings = sb_get_setting("registration");
                $registration_fields = sb_get_setting("registration-fields");
                $title = sb_(
                    sb_isset(
                        $settings,
                        "registration-title",
                        "Create new account"
                    )
                );
                $message = sb_(sb_isset($settings, "registration-msg", ""));
                $success = sb_(sb_isset($settings, "registration-success", ""));
                $profile_image = sb_get_setting("registration-profile-img")
                    ? '<div id="profile_image" data-type="image" class="sb-input sb-input-image sb-profile-image"><span>' .
                    sb_("Profile image") .
                    "</span><div" .
                    ($user["profile_image"] &&
                        strpos($user["profile_image"], "media/user.svg") ==
                        false
                        ? ' data-value="' .
                        $user["profile_image"] .
                        '" style="background-image:url(\'' .
                        $user["profile_image"] .
                        '\')"'
                        : "") .
                    ' class="image">' .
                    ($user["profile_image"] &&
                        strpos($user["profile_image"], "media/user.svg") ==
                        false
                        ? '<i class="bi-x-lg"></i>'
                        : "") .
                    "</div></div>"
                    : "";
                $password =
                    (!$registration_tickets &&
                        sb_get_setting("registration-password")) ||
                    ($registration_tickets &&
                        !sb_get_setting(
                            "tickets-registration-disable-password"
                        )
                    )
                    ? '<div id="password" data-type="text" class="sb-input sb-input-password"><span>' .
                    sb_("Password") .
                    '</span><input value="' .
                    ($user["password"] ? "********" : "") .
                    '" autocomplete="false" type="password" required></div><div id="password-check" data-type="text" class="sb-input sb-input-password"><span>' .
                    sb_("Repeat password") .
                    '</span><input value="' .
                    ($user["password"] ? "********" : "") .
                    '" autocomplete="false" type="password" required></div>'
                    : "";
                $link =
                    $settings["registration-terms-link"] ||
                    $settings["registration-privacy-link"]
                    ? '<div class="sb-link-area">' .
                    sb_(
                        "By clicking the button below, you agree to our"
                    ) .
                    ' <a target="_blank" href="' .
                    sb_isset(
                        $settings,
                        "registration-terms-link",
                        $settings["registration-privacy-link"]
                    ) .
                    '">' .
                    sb_(
                        $settings["registration-terms-link"]
                            ? "Terms of service"
                            : "Privacy Policy"
                    ) .
                    "</a>" .
                    ($settings["registration-privacy-link"] &&
                        $settings["registration-terms-link"]
                        ? " " .
                        sb_("and") .
                        ' <a target="_blank" href="' .
                        $settings["registration-privacy-link"] .
                        '">' .
                        sb_("Privacy Policy") .
                        "</a>"
                        : "") .
                    ".</div>"
                    : "";
                $email = sb_get_setting("registration-email-disable")
                    ? ""
                    : '<div id="email" data-type="text" class="sb-input sb-input-text"><span>' .
                    sb_("Email") .
                    '</span><input value="' .
                    $user["email"] .
                    '" autocomplete="off" type="email" required></div>';
                $code =
                    '<div class="sb-form-main sb-form">' .
                    $profile_image .
                    '<div id="first_name" data-type="text" class="sb-input sb-input-text"><span>' .
                    sb_($last_name ? "First name" : "Name") .
                    '</span><input value="' .
                    ($visitor ? "" : $user["first_name"]) .
                    '" autocomplete="false" type="text" required></div>' .
                    ($last_name
                        ? '<div id="last_name" data-type="text" class="sb-input sb-input-text"><span>' .
                        sb_("Last name") .
                        '</span><input value="' .
                        ($visitor ? "" : $user["last_name"]) .
                        '" autocomplete="false" type="text" required></div>'
                        : "") .
                    $email .
                    $password .
                    '</div><div class="sb-form-extra sb-form">';
                $extra = [];
                if (isset($user["details"])) {
                    for ($i = 0; $i < count($user["details"]); $i++) {
                        $extra[$user["details"][$i]["slug"]] =
                            $user["details"][$i]["value"];
                    }
                }
                foreach ($registration_fields as $key => $value) {
                    if ($value) {
                        $key = str_replace("reg-", "", $key);
                        $name = str_replace("-", " ", $key);
                        $filled = isset($extra[$name])
                            ? ' value="' . $extra[$name] . '"'
                            : "";
                        $type = $type_cnt = "text";
                        $custom_input = false;
                        switch ($key) {
                            case "birthday":
                                $type = "date";
                                break;
                            case "twitter":
                            case "linkedin":
                            case "facebook":
                            case "pinterest":
                            case "instagram":
                            case "website":
                                $type = "url";
                                break;
                            case "phone":
                                $type_cnt = "select-input";
                                $custom_input =
                                    "<div>" .
                                    sb_select_phone() .
                                    "</div><input" .
                                    $filled .
                                    ' autocomplete="false" type="tel" pattern="[0-9]+"' .
                                    (
                                        sb_get_setting(
                                            "registration-phone-required"
                                        )
                                        ? " required"
                                        : "") .
                                    ">";
                                break;
                            case "country":
                                $type_cnt = "select";
                                $custom_input = sb_select_countries();
                                break;
                            case "language":
                                $type_cnt = "select";
                                $custom_input = sb_select_languages();
                                break;
                        }
                        $code .=
                            '<div id="' .
                            $key .
                            '" data-type="' .
                            $type_cnt .
                            '" class="sb-input sb-input-' .
                            $type_cnt .
                            '"><span>' .
                            sb_(ucfirst($name)) .
                            "</span>" .
                            ($custom_input
                                ? $custom_input
                                : "<input" .
                                $filled .
                                ' autocomplete="false" type="' .
                                $type .
                                '">') .
                            "</div>";
                    }
                }
                if (sb_get_setting("registration-extra")) {
                    $additional_fields = sb_get_setting(
                        "user-additional-fields"
                    );
                    if ($additional_fields) {
                        for ($i = 0; $i < count($additional_fields); $i++) {
                            $value = $additional_fields[$i];
                            $name = $value["extra-field-name"];
                            $filled = isset($extra[$value["extra-field-slug"]])
                                ? ' value="' .
                                $extra[$value["extra-field-slug"]] .
                                '"'
                                : "";
                            if ($name) {
                                $code .=
                                    '<div id="' .
                                    $value["extra-field-slug"] .
                                    '" data-type="text" class="sb-input sb-input-text"><span>' .
                                    sb_(ucfirst($name)) .
                                    "</span><input" .
                                    $filled .
                                    ' autocomplete="false" type="text"></div>';
                            }
                        }
                    }
                }
                $code .=
                    "</div>" .
                    $link .
                    '<div class="sb-buttons"><div class="sb-btn sb-submit">' .
                    sb_(
                        $visitor
                            ? sb_isset(
                                $settings,
                                "registration-btn-text",
                                "Create account"
                            )
                            : "Update account"
                    ) .
                    "</div>" .
                    ($password
                        ? '<div class="sb-btn-text sb-login-area">' .
                        sb_("Sign in instead") .
                        "</div>"
                        : "") .
                    "</div>";
                break;
            case "login-tickets":
            case "login":
                $settings = sb_get_setting("login");
                $title = sb_(sb_isset($settings, "login-title", "Login"));
                $message = sb_($settings["login-msg"]);
                $code =
                    '<div class="sb-form"><div id="email" class="sb-input"><span>' .
                    sb_("Email") .
                    '</span><input autocomplete="false" type="email"></div><div id="password" class="sb-input"><span>' .
                    sb_("Password") .
                    '</span><input autocomplete="false" type="password"></div></div><div class="sb-buttons"><div class="sb-btn sb-submit-login">' .
                    sb_("Sign in") .
                    "</div>" .
                    (sb_get_setting("registration-required") == "login"
                        ? ""
                        : '<div class="sb-btn-text sb-registration-area">' .
                        sb_("Create new account") .
                        "</div>") .
                    "</div>";
                break;
            case "timetable":
                $settings = sb_get_settings();
                $timetable = sb_isset($settings, "timetable", [false])[0];
                $title =
                    $settings["chat-timetable"][0]["chat-timetable-title"][0];
                $message =
                    $settings["chat-timetable"][0]["chat-timetable-msg"][0];
                $title = sb_($title == "" ? "Office hours" : $title);
                $message = sb_($message);
                $code =
                    '<div class="sb-timetable" data-offset="' .
                    sb_get_setting("timetable-utc") .
                    '">';
                if ($timetable) {
                    foreach ($timetable as $day => $hours) {
                        if ($hours[0][0]) {
                            $code .=
                                "<div><div>" .
                                sb_(ucfirst($day)) .
                                '</div><div data-time="' .
                                $hours[0][0] .
                                "|" .
                                $hours[1][0] .
                                "|" .
                                $hours[2][0] .
                                "|" .
                                $hours[3][0] .
                                '"></div></div>';
                        }
                    }
                }
                $code .= "<span></span></div>";
                break;
                // case "articles":
                //     $articles_title = sb_get_setting("articles-title");
                //     $articles_button_link = sb_get_setting("articles-button-link");
                //     $code =
                //         '<div class="sb-dashboard-articles"><div class="sb-title">' .
                //         sb_(
                //             $articles_title == "" ? "Help Center" : $articles_title
                //         ) .
                //         '</div><div class="sb-input sb-input-btn"><input placeholder="' .
                //         sb_("Search for articles...") .
                //         '" autocomplete="off"><div class="sb-submit-articles sb-icon-arrow-right"></div></div><div class="sb-articles">';
                //     $articles = sb_get_articles(
                //         -1,
                //         2,
                //         false,
                //         false,
                //         sb_get_user_language()
                //     );
                //     for ($i = 0; $i < count($articles); $i++) {
                //         $code .=
                //             '<div data-id="' .
                //             $articles[$i]["id"] .
                //             '"><div>' .
                //             $articles[$i]["title"] .
                //             "</div><span>" .
                //             $articles[$i]["content"] .
                //             "</span></div>";
                //     }
                //     $code .=
                //         '</div><div class="sb-btn sb-btn-all-articles"' .
                //         ($articles_button_link
                //             ? ' onclick="document.location.href = \'' .
                //             $articles_button_link .
                //             '\'"'
                //             : "") .
                //         ">" .
                //         sb_("All articles") .
                //         "</div></div>";
                //     break;
        }
        return ($title == ""
            ? ""
            : '<div class="sb-top">' . $title . "</div>") .
            ($message == ""
                ? ""
                : '<div class="sb-text">' . $message . "</div>") .
            $code .
            '<div data-success="' .
            $success .
            '" class="sb-info"></div>';
    } else {
        $result = sb_get_external_setting("rich-messages");
        if (is_array($result)) {
            $rich_messages = sb_isset($result, "rich-messages")[0];
            if (is_array($rich_messages)) {
                for ($i = 0; $i < count($rich_messages); $i++) {
                    $item = $result["rich-messages"][0][$i];
                    if ($item["rich-message-name"] == $name) {
                        return $item["rich-message-content"];
                    }
                }
            }
        }
    }
    return false;
}

function sb_rich_value(
    $value,
    $merge_fields = true,
    $tranlsate = true,
    $shortcodes = false
) {
    if (!$shortcodes) {
        $value = str_replace('"', '\'', strip_tags($value));
        $value = str_replace(["[", "]"], "", $value);
    }
    if ($tranlsate) {
        $value = sb_($value);
    }
    return trim(
        preg_replace(
            "/\s+/",
            " ",
            $merge_fields ? sb_merge_fields($value) : $value
        )
    );
}

function sb_get_shortcode($message, $name = false, $merge_field = false)
{
    $separator = $merge_field ? ["{", "}"] : ["[", "]"];
    $response = false;
    $position = false;
    if (strpos($message, $separator[0]) === false) {
        return false;
    }
    if (!$name) {
        $shortcode_names = $merge_field
            ? []
            : [
                "chips",
                "buttons",
                "select",
                "inputs",
                "card",
                "slider-images",
                "slider",
                "list-image",
                "list",
                "button",
                "video",
                "image",
                "rating",
                "email",
                "phone",
                "registration",
                "timetable",
                "articles",
                "table",
                "share",
            ];
        for ($i = 0; $i < count($shortcode_names); $i++) {
            $position = strpos($message, $separator[0] . $shortcode_names[$i]);
            if ($position !== false) {
                $name = $shortcode_names[$i];
                break;
            }
        }
        if (!$name) {
            return false;
        }
    }
    $position = $position ? $position : strpos($message, $separator[0] . $name);
    if ($position !== false) {
        $code = substr(
            $message,
            $position,
            strpos($message, $separator[1], $position) + 1
        );
        $response = ["shortcode" => $code, "shortcode_name" => $name];
        $values = [];
        if (
            preg_match_all(
                '/([\w-]+)\s*=\s*"([^"]*)"(?:\s|$)|([\w-]+)\s*=\s*\'([^\']*)\'(?:\s|$)|([\w-]+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|\'([^\']*)\'(?:\s|$)|(\S+)(?:\s|$)/',
                substr($code, 1, -1),
                $values,
                PREG_SET_ORDER
            )
        ) {
            for ($i = 0; $i < count($values); $i++) {
                if (
                    count($values[$i]) == 3 &&
                    !empty($values[$i][1]) &&
                    !empty($values[$i][2])
                ) {
                    $response[$values[$i][1]] =
                        $values[$i][2] === "false"
                        ? false
                        : ($values[$i][2] === "true"
                            ? true
                            : $values[$i][2]);
                }
            }
        }
    }
    return $response;
}






/*
 * -----------------------------------------------------------
 * TRANSLATIONS
 * -----------------------------------------------------------
 *
 * 1. Return the translation of a string
 * 2. Echo the translation of a string
 * 3. Initialize the translations
 * 4. Return the current translations array
 * 5. Return all the translations of both admin and front areas of all languages
 * 6. Return the translations of a language
 * 7. Save a translation langauge file and a copy of it as backup
 * 8. Restore a translation language file from a backup
 * 9. Return the user langauge code
 * 10. Return the langauge code of the admin area relative to the active agent
 * 11. Translate a string in the given language
 *
 */

function sb_($string)
{
    global $SB_TRANSLATIONS;
    if (!isset($SB_TRANSLATIONS)) {
        sb_init_translations();
    }
    return empty($SB_TRANSLATIONS[$string])
        ? $string
        : $SB_TRANSLATIONS[$string];
}

function sb_s($string)
{
    global $SB_TRANSLATIONS_SETTINGS;
    if (!isset($SB_TRANSLATIONS_SETTINGS)) {
        $language = sb_get_admin_language();
        if ($language && $language != "en") {
            $SB_TRANSLATIONS_SETTINGS = json_decode(
                file_get_contents(
                    SB_PATH .
                        "/resources/languages/admin/settings/" .
                        $language .
                        ".json"
                ),
                true
            );
        }
    }
    return empty($SB_TRANSLATIONS_SETTINGS[$string])
        ? $string
        : $SB_TRANSLATIONS_SETTINGS[$string];
}

function sb_e($string)
{
    echo sb_($string);
}

function sb_se($string)
{
    echo sb_s($string);
}


function sb_init_translations()
{
    global $SB_TRANSLATIONS;
    global $SB_LANGUAGE;
    if (!empty($SB_LANGUAGE) && $SB_LANGUAGE[0] != "en") {
        $path =
            SB_PATH .
            "/resources/languages/" .
            $SB_LANGUAGE[1] .
            "/" .
            $SB_LANGUAGE[0] .
            ".json";
        if (file_exists($path)) {
            $SB_TRANSLATIONS = json_decode(file_get_contents($path), true);
        } else {
            $SB_TRANSLATIONS = false;
        }
    } elseif (!isset($SB_LANGUAGE)) {
        $SB_TRANSLATIONS = false;
        $SB_LANGUAGE = false;
        $admin = sb_is_agent();
        $language = $admin ? sb_get_admin_language() : sb_get_user_language();
        $auto_translation = $admin
            ? false
            : sb_get_setting("front-auto-translations");
        if (
            !(!$language ||
                $language == "en" ||
                (!$admin &&
                    !isset($_GET["lang"]) &&
                    !$auto_translation)
            )
        ) {
            switch ($language) {
                case "nn":
                case "nb":
                    $language = "no";
                    break;
            }
            $area = $admin ? "admin" : "front";
            $path =
                SB_PATH .
                "/resources/languages/" .
                $area .
                "/" .
                $language .
                ".json";
            if (file_exists($path)) {
                $SB_TRANSLATIONS = json_decode(file_get_contents($path), true);
                $SB_LANGUAGE = [$language, $area];
            }
        }
    }
}




function sb_get_current_translations()
{
    global $SB_TRANSLATIONS;
    if (!isset($SB_TRANSLATIONS)) {
        sb_init_translations();
    }
    return $SB_TRANSLATIONS;
}

function sb_get_translations($is_user = false, $language_code = false)
{
    $translations = [];
    $path = $is_user ? "/uploads" : "/resources";
    $language_codes = json_decode(
        file_get_contents(SB_PATH . "/resources/languages/language-codes.json"),
        true
    );
    $paths = ["front", "admin", "admin/js", "admin/settings"];
    for ($i = 0; $i < count($paths); $i++) {
        $files = scandir(SB_PATH . $path . "/languages/" . $paths[$i]);
        for ($j = 0; $j < count($files); $j++) {
            $file = $files[$j];
            if (strpos($file, ".json")) {
                $code = substr($file, 0, -5);
                if (
                    !isset($language_codes[$code]) ||
                    ($language_code && $language_code != $code)
                ) {
                    continue;
                }
                if (!isset($translations[$code])) {
                    $translations[$code] = ["name" => $language_codes[$code]];
                }
                $translation_strings = json_decode(
                    file_get_contents(
                        SB_PATH . $path . "/languages/" . $paths[$i] . "/" . $file
                    ),
                    true
                );
                $translations[$code][$paths[$i]] = $translation_strings;
            }
        }
    }
    return $translations;
}

function sb_get_translation($language_code)
{
    return sb_get_translations(false, $language_code)[$language_code];
}





function sb_get_user_language($user_id = false)
{
    if ($user_id && is_numeric($user_id)) {
        $language = sb_get_user_extra($user_id, "language");
        $language = $language
            ? $language
            : sb_get_user_extra($user_id, "browser_language");
        if ($language) {
            return strtolower($language);
        }
    }
    global $SB_LANGUAGE;
    if (empty($SB_LANGUAGE)) {
        return strtolower(
            isset($_GET["lang"])
                ? $_GET["lang"]
                : (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])
                    ? substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2)
                    : "")
        );
    }
    return $SB_LANGUAGE[0];
}

function sb_get_admin_language($user_id = false)
{
    $language = defined("SB_ADMIN_LANG")
        ? trim(strtolower(SB_ADMIN_LANG))
        : (sb_get_setting("admin-auto-translations")
            ? trim(
                strtolower(
                    sb_get_user_language(
                        $user_id ? $user_id : sb_get_active_user_ID()
                    )
                )
            )
            : "es"); // Set default language to "es"
    return $language;
}



function sb_translate_string($string, $language_code)
{
    global $SB_LANGUAGE;
    global $SB_TRANSLATIONS;
    $translations = [];
    if (
        !empty($SB_LANGUAGE) &&
        $SB_LANGUAGE[0] == $language_code &&
        !empty($SB_TRANSLATIONS)
    ) {
        $translations = $SB_TRANSLATIONS;
    } else {
        $path =
            SB_PATH . "/resources/languages/front/" . $language_code . ".json";
        if (file_exists($path)) {
            $translations = json_decode(file_get_contents($path), true);
        }
    }
    return empty($translations[$string]) ? $string : $translations[$string];
}




/*
 * -----------------------------------------------------------
 * SETTINGS
 * -----------------------------------------------------------
 *
 * 1. Populate the admin area with the settings of the file /resources/json/settings.json
 * 2. Pupulate the admin area of the apps
 * 3. Return the HTML code of a setting element
 * 4. Save the all settings and external settings
 * 5. Save an external setting
 * 6. Return the settings array
 * 7. Return all settings and external settings
 * 8. Return the setting with the given name
 * 9. Return a single setting of a multi values setting
 * 10. Return the external setting with the given name
 * 11. Return a multilingual external setting
 * 12. Return the settings of the front-end
 * 13. Return the setting of block message
 * 14. Return the HTML code of the color palette
 * 15. Export all settings and external settings
 * 16. Import all settings and external settings
 *
 */

function sb_populate_settings($category, $settings, $echo = true)
{
    if (
        !isset($settings) &&
        file_exists(SB_PATH . "/resources/json/settings.json")
    ) {
        $settings = json_decode(
            file_get_contents(SB_PATH . "/resources/json/settings.json"),
            true
        );
    }
    $settings = $settings[$category];
    $code = "";
    for ($i = 0; $i < count($settings); $i++) {
        $code .= sb_get_setting_code($settings[$i]);
    }
    if ($echo) {
        echo $code;
        return true;
    } else {
        return $code;
    }
}

function sb_populate_app_settings($app_name)
{
    $file = SB_PATH . "/apps/" . $app_name . "/settings.json";
    $settings = [$app_name => []];
    if (file_exists($file)) {
        $settings[$app_name] = json_decode(file_get_contents($file), true);
    }
    return sb_populate_settings($app_name, $settings, false);
}

function sb_get_setting_code($setting)
{
    if (isset($setting)) {
        $id = $setting["id"];
        $type = $setting["type"];
        $content =
            '<div id="' .
            $id .
            '" data-type="' .
            $type .
            '"' .
            (isset($setting["setting"])
                ? ' data-setting="' . $setting["setting"] . '"'
                : "") .
            '  class="sb-setting sb-type-' .
            $type .
            '"><div style="width:auto;padding: 10px;" class="sb-setting-content"><h2 class="settings-button" style="cursor: pointer;">' .
            sb_s($setting["title"]) .
            ' </h2><p class="active">' .
            sb_s($setting["content"]) .
            (isset($setting["help"]) ? ' <a href="' . $setting["help"] . '" target="_blank" style=" font-size: var(--chat-text-size-1-0); color: var(--pink-root-color); line-height: 0; " class="bi bi-play-circle"></a>' : "") .
            '</p></div><div style="padding-left: 1%" class="active input">';
        switch ($type) {
            case "multi-input":
                $values = $setting["value"];
                for ($i = 0; $i < count($values); $i++) {
                    $sub_type = $values[$i]["type"];
                    $sub_value = $values[$i]["value"];
                    $content .=
                        '<div style="text-align: start;" id="' .
                        $values[$i]["id"] .
                        '" data-type="' .
                        $sub_type .
                        '" class="multi-input-' .
                        $sub_type .
                        '"><label>' .
                        sb_s($values[$i]["title"]) .
                        "</label>";
                    switch ($sub_type) {
                        case "text":
                            $content .= '<input type="text">';
                            break;
                        case "password":
                            $content .= '<input type="password">';
                            break;
                        case "number":
                            $content .= '<input type="number">';
                            break;
                        case "textarea":
                            $content .= "<textarea></textarea>";
                            break;
                        case "upload":
                            $content .=
                                '<input type="url"><button type="button">' .
                                sb_("Choose file") .
                                "</button>";
                            break;
                        case "upload-image":
                            $content .=
                                '<div class="image"><i class="bi-x-lg"></i></div>';
                            break;

                        case "checkbox":
                            $content .= '<input class="switch-toggle" type="checkbox">';
                            break;
                        case "select":
                            $content .= "<select>";
                            $items = $values[$i]["value"];
                            for ($j = 0; $j < count($items); $j++) {
                                $content .=
                                    '<option value="' .
                                    $items[$j][0] .
                                    '">' .
                                    sb_s($items[$j][1]) .
                                    "</option>";
                            }
                            $content .= "</select>";
                            break;
                        case "button":
                            $content .=
                                '<a class="sb-btn sb-repeater-add" target="_blank" href="' .
                                $values[$i]["button-url"] .
                                '">' .
                                sb_s($values[$i]["button-text"]) .
                                "</a>";
                            break;
                        case "select-checkbox":
                            $items = $values[$i]["value"];
                            $content .=
                                '<input type="text" class="sb-select-checkbox-input" readonly><div class="sb-select-checkbox">';
                            for ($i = 0; $i < count($items); $i++) {
                                $content .=
                                    '<div class="multi-input-checkbox"><input id="' .
                                    $items[$i][0] .
                                    '" type="checkbox" class="switch-toggle" ><label>' .
                                    sb_s($items[$i][1]) .
                                    "</label></div>";
                            }
                            $content .= "</div>";
                            break;
                    }
                    $content .= "</div>";
                }
                break;
            case "repeater":
                $content .=
                    '<div class="sb-repeater"><div class="repeater-item">';
                for ($i = 0; $i < count($setting["items"]); $i++) {
                    $item = $setting["items"][$i];
                    $content .=
                        "<div>" .
                        (isset($item["name"])
                            ? "<label>" . sb_s($item["name"]) . "</label>"
                            : "");
                    switch ($item["type"]) {
                        case "text":
                        case "number":
                        case "password":
                            $content .=
                                '<input data-id="' .
                                $item["id"] .
                                '" type="' .
                                $item["type"] .
                                '">';
                            break;
                        case "textarea":
                            $content .=
                                '<textarea data-id="' .
                                $item["id"] .
                                '"></textarea>';
                            break;
                        case "checkbox":
                            $content .=
                                '<input data-id="' .
                                $item["id"] .
                                '" type="checkbox" class="switch-toggle">';
                            break;
                        case "auto-id":
                            $content .=
                                '<input data-type="auto-id" data-id="' .
                                $item["id"] .
                                '" value="1" type="text" readonly="true">';
                            break;
                        case "hidden":
                            $content .=
                                '<input data-id="' .
                                $item["id"] .
                                '" type="hidden">';
                            break;
                        case "color-palette":
                            $content .= sb_color_palette($item["id"]);
                            break;
                        case "upload-image":
                            $content .=
                                '<div data-type="upload-image"><div data-id="' .
                                $item["id"] .
                                '" class="image"><i class="bi-x-lg"></i></div></div>';
                            break;
                    }
                    $content .= "</div>";
                }
                $content .=
                    '<i class="bi-x-lg"></i></div></div><a class="sb-btn sb-repeater-add">' .
                    sb_("Add new item") .
                    "</a>";
                break;
            case "color":
                $content .=
                    '<input type="text"><i class="sb-close bi-x-lg"></i>';
                break;
            case "text":
                $content .= '<input type="text" placeholder="">';
                break;
            case "password":
                $content .= '<input type="password">';
                break;
            case "textarea":
                $content .= "<textarea></textarea>";
                break;
            case "article":
                $content .= '
                 <div>
                 <p class="active">Download link: <a class="sb-btn" href="https://routin.cloud/webchat" target="_blank">Obtener</a>
                 </p>
                 </div>';
                break;
            case "cta":
                $content .=
                    '<div class="active input" style="max-width: 100%;margin: 10px auto;border-radius:  var(--chat-rounded-size-7);padding: 10px 20px;text-align: center;box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);display: flex;flex-direction: column;justify-content: center;align-items: center;height: 200px;"> <p style=" font-size: 1.1em; color: white; ">Multiple Business in your hands ⚡️  <strong>Stemboxchat</strong>! </p> <ul style=" list-style-type: none; padding: 0; margin: 0; "> <!-- Add your list items here if needed --> </ul> </div>';
                break;
            case "select":
                $values = $setting["value"];
                $content .= "<select>";
                for ($i = 0; $i < count($values); $i++) {
                    $content .=
                        '<option value="' .
                        $values[$i][0] .
                        '">' .
                        sb_s($values[$i][1]) .
                        "</option>";
                }
                $content .= "</select>";
                break;
            case "checkbox":
                $content .= '<input type="checkbox" class="switch-toggle">';
                break;
            case "radio":
                $values = $setting["value"];
                for ($i = 0; $i < count($values); $i++) {
                    $content .=
                        '<div><input type="radio" name="' .
                        $id .
                        '" value="' .
                        strtolower(str_replace(" ", "-", $values[$i])) .
                        '"><label>' .
                        $setting["value"][$i] .
                        "</label></div>";
                }
                break;
            case "number":
                $content .=
                    '<input type="number">' .
                    (isset($setting["unit"])
                        ? "<label>" . $setting["unit"] . "</label>"
                        : "");
                break;
            case "upload":
                $content .=
                    (empty($setting["text-field"])
                        ? ""
                        : '<input type="url">') .
                    '<a class="sb-btn">' .
                    sb_(sb_isset($setting, "button-text", "Choose file")) .
                    "</a>";
                break;
            case "upload-image":
                $content .=
                    '<div class="image"' .
                    (isset($setting["background-size"])
                        ? ' style="background-size: ' .
                        $setting["background-size"] .
                        '"'
                        : "") .
                    '><i class="bi-x-lg"></i></div>';
                break;
            case "input-button":
                $content .=
                    '<input type="text"><a class="sb-btn sb-repeater-add">' .
                    sb_s($setting["button-text"]) .
                    "</a>";
                break;
            case "button":
                $content .=
                    '<a class="sb-btn sb-repeater-add" target="_blank" href="' .
                    $setting["button-url"] .
                    '">' .
                    sb_s($setting["button-text"]) .
                    "</a>";
                break;
            case "range":
                $range = key_exists("range", $setting)
                    ? $setting["range"]
                    : [0, 100];
                $unit = key_exists("unit", $setting)
                    ? "<label>" . $setting["unit"] . "</label>"
                    : "";
                $content .=
                    '<label class="range-value">' .
                    $range[0] .
                    '</label><input type="range" min="' .
                    $range[0] .
                    '" max="' .
                    $range[1] .
                    '" value="' .
                    $range[0] .
                    '" />' .
                    $unit;
                break;
            case "timetable":
                $days = [
                    "Monday",
                    "Tuesday",
                    "Wednesday",
                    "Thursday",
                    "Friday",
                    "Saturday",
                    "Sunday",
                ];
                $hours = [
                    ["", ""],
                    ["closed", sb_("Closed")],
                    ["00:00", "12:00 am"],
                    ["00:30", "12:30 am"],
                    ["01:00", "1:00 am"],
                    ["01:30", "1:30 am"],
                    ["02:00", "2:00 am"],
                    ["02:30", "2:30 am"],
                    ["03:00", "3:00 am"],
                    ["03:30", "3:30 am"],
                    ["04:00", "4:00 am"],
                    ["04:30", "4:30 am"],
                    ["05:00", "5:00 am"],
                    ["05:30", "5:30 am"],
                    ["06:00", "6:00 am"],
                    ["06:30", "6:30 am"],
                    ["07:00", "7:00 am"],
                    ["07:30", "7:30 am"],
                    ["08:00", "8:00 am"],
                    ["08:30", "8:30 am"],
                    ["09:00", "9:00 am"],
                    ["09:30", "9:30 am"],
                    ["10:00", "10:00 am"],
                    ["10:30", "10:30 am"],
                    ["11:00", "11:00 am"],
                    ["11:30", "11:30 am"],
                    ["12:00", "12:00 pm"],
                    ["12:30", "12:30 pm"],
                    ["13:00", "1:00 pm"],
                    ["13:30", "1:30 pm"],
                    ["14:00", "2:00 pm"],
                    ["14:30", "2:30 pm"],
                    ["15:00", "3:00 pm"],
                    ["15:30", "3:30 pm"],
                    ["16:00", "4:00 pm"],
                    ["16:30", "4:30 pm"],
                    ["17:00", "5:00 pm"],
                    ["17:30", "5:30 pm"],
                    ["18:00", "6:00 pm"],
                    ["18:30", "6:30 pm"],
                    ["19:00", "7:00 pm"],
                    ["19:30", "7:30 pm"],
                    ["20:00", "8:00 pm"],
                    ["20:30", "8:30 pm"],
                    ["21:00", "9:00 pm"],
                    ["21:30", "9:30 pm"],
                    ["22:00", "10:00 pm"],
                    ["22:30", "10:30 pm"],
                    ["23:00", "11:00 pm"],
                    ["23:30", "11:30 pm"],
                ];
                $select = '<div class="sb-custom-select">';
                for ($i = 0; $i < count($hours); $i++) {
                    $select .=
                        '<span data-value="' .
                        $hours[$i][0] .
                        '">' .
                        $hours[$i][1] .
                        "</span>";
                }
                $content .= '<div class="sb-timetable">';
                for ($i = 0; $i < 7; $i++) {
                    $content .=
                        '<div data-day="' .
                        strtolower($days[$i]) .
                        '"><label>' .
                        sb_($days[$i]) .
                        "</label><div><div></div><span>" .
                        sb_("To") .
                        "</span><div></div><span>" .
                        sb_("And") .
                        "</span><div></div><span>" .
                        sb_("To") .
                        "</span><div></div></div></div>";
                }
                $content .= $select . "</div></div>";
                break;
            case "select-images":
                $content .= '<div class="bi-x-lg"></div>';
                for ($i = 0; $i < count($setting["images"]); $i++) {
                    $content .=
                        '<div data-value="' .
                        $setting["images"][$i] .
                        '" style="background-image: url(\'' .
                        STMBX_URL .
                        "/media/" .
                        $setting["images"][$i] .
                        '\')"></div>';
                }
                break;
            case "select-checkbox":
                $values = $setting["value"];
                $content .=
                    '<select disabled><option>AA</option></select><div class="sb-select-checkbox">';
                for ($i = 0; $i < count($values); $i++) {
                    $content .=
                        '<div id="' .
                        $values[$i]["id"] .
                        '" data-type="checkbox" class="multi-input-checkbox"><input class="switch-toggle" type="checkbox"><label>' .
                        sb_s($values[$i]["title"]) .
                        "</label></div>";
                }
                $content .= "</div>";
                break;
        }
        if (
            isset($setting["setting"]) &&
            ($type == "multi-input" || !empty($setting["multilingual"]))
        ) {
            //$content .= '<div class="sb-language-switcher-cnt"><label>' . sb_('Languages') . '</label></div>';
        }
        return $content . "</div></div>";
    }
    return "";
}


function sb_save_settings(
    $settings,
    $external_settings = [],
    $external_settings_translations = []
) {
    if (isset($settings)) {
        global $SB_SETTINGS;
        if (is_string($settings)) {
            $settings = json_decode($settings, true);
        }
        $settings_encoded = sb_db_json_escape($settings);
        if (isset($settings_encoded) && is_string($settings_encoded)) {
            // Save main settings
            $query =
                'INSERT INTO sb_settings(name, value) VALUES (\'settings\', \'' .
                $settings_encoded .
                '\') ON DUPLICATE KEY UPDATE value = \'' .
                $settings_encoded .
                '\'';
            $result = sb_db_query($query);
            if (sb_is_error($result)) {
                return $result;
            }

            // Save external settings
            foreach ($external_settings as $key => $value) {
                sb_save_external_setting($key, $value);
            }

            // Save external settings translations
            $db = "";
            foreach ($external_settings_translations as $key => $value) {
                $name = "external-settings-translations-" . $key;
                sb_save_external_setting($name, $value);
                $db .= '"' . $name . '",';
            }
            if ($db) {
                sb_db_query(
                    'DELETE FROM sb_settings WHERE name LIKE "external-settings-translations-%" AND name NOT IN (' .
                        substr($db, 0, -1) .
                        ")"
                );
            }

            // Update bot
            sb_update_bot($settings["bot-name"][0], $settings["bot-image"][0]);

            $SB_SETTINGS = $settings;
            return true;
        } else {
            return new SBError("json-encode-error", "sb_save_settings");
        }
    } else {
        return new SBError("settings-not-found", "sb_save_settings");
    }
}

function sb_save_external_setting($name, $value)
{
    $settings_encoded = sb_db_json_escape($value);
    return JSON_ERROR_NONE !== json_last_error()
        ? json_last_error_msg()
        : sb_db_query(
            'INSERT INTO sb_settings(name, value) VALUES (\'' .
                sb_db_escape($name) .
                '\', \'' .
                $settings_encoded .
                '\') ON DUPLICATE KEY UPDATE value = \'' .
                $settings_encoded .
                '\''
        );
}

function sb_get_settings()
{
    global $SB_SETTINGS;
    if (!isset($SB_SETTINGS)) {
        $SB_SETTINGS = sb_get_external_setting("settings", []);
        if (isset($GLOBALS["SB_LOCAL_SETTINGS"])) {
            $SB_SETTINGS = array_merge(
                $SB_SETTINGS,
                $GLOBALS["SB_LOCAL_SETTINGS"]
            );
        }
    }
    return $SB_SETTINGS;
}


function sb_get_all_settings()
{
    $translations = [];
    $settings = [];
    $rows = sb_db_get(
        'SELECT value FROM sb_settings WHERE name="emails" || name="rich-messages" || name="wc-emails"',
        false
    );
    for ($i = 0; $i < count($rows); $i++) {
        $settings = array_merge(
            $settings,
            json_decode($rows[$i]["value"], true)
        );
    }
    $rows = sb_db_get(
        'SELECT name, value FROM sb_settings WHERE name LIKE "external-settings-translations-%"',
        false
    );
    for ($i = 0; $i < count($rows); $i++) {
        $translations[substr($rows[$i]["name"], -2)] = json_decode(
            $rows[$i]["value"],
            true
        );
    }
    return array_merge(sb_get_settings(), $settings, [
        "external-settings-translations" => $translations,
    ]);
}

function sb_get_setting($id, $default = false)
{
    $settings = sb_get_settings();
    if (!sb_is_error($settings)) {
        if (isset($settings[$id]) && !empty($settings[$id][0])) {
            $setting = $settings[$id][0];
            if (is_array($setting) && !isset($setting[0])) {
                $settings_result = [];
                foreach ($setting as $key => $value) {
                    $settings_result[$key] = $value[0];
                }
                return $settings_result;
            } else {
                return $setting;
            }
        } else {
            return $default;
        }
    } else {
        return $settings;
    }
}

function sb_get_multi_setting($id, $sub_id, $default = false)
{
    $setting = sb_get_setting($id);
    if ($setting && !empty($setting[$sub_id])) {
        return $setting[$sub_id];
    }
    return $default;
}

function sb_get_external_setting($name, $default = false)
{
    $result = sb_db_get(
        'SELECT value FROM sb_settings WHERE name = "' .
            sb_db_escape($name) .
            '"',
        false
    );
    $settings = [];
    if (empty($result)) {
        return $default;
    }
    if (sb_is_error($settings)) {
        return $settings;
    }
    if (!is_array($result)) {
        return $result;
    }
    if (count($result) == 1) {
        return json_decode($result[0]["value"], true);
    }
    for ($i = 0; $i < count($result); $i++) {
        $settings = array_merge(
            $settings,
            json_decode($result[$i]["value"], true)
        );
    }
    return $settings;
}

function sb_get_multilingual_setting($name, $sub_name, $language = false)
{
    $language = $language ? $language : sb_get_user_language();
    $value =
        $language && $language != "en"
        ? sb_isset(
            sb_get_external_setting(
                "external-settings-translations-" . $language
            ),
            $sub_name
        )
        : false;
    if ($value) {
        return $value;
    }
    $value = sb_isset(sb_get_external_setting($name), $sub_name);
    if ($value && is_array($value)) {
        $value = $value[0];
        if (!empty($value) && !is_string($value) && [] !== $value) {
            foreach ($value as $key => $setting) {
                $value[$key] = $setting[0];
            }
        }
    }
    return $value;
}

// Deprecated from V 3.1.6
function sb_get_external_settings($name, $default = false)
{
    return sb_get_external_setting($name, $default);
}

function sb_get_select_setting($setting)
{
    $select_setting = [];
    foreach ($setting as $value) {
        $name = $value["name"];
        $select_setting[$name] =
            $value["type"] == "multi"
            ? sb_get_multi_setting($value["name"], $value["id"])
            : sb_get_setting($value["name"]);
    }
    return $select_setting;
}
function sb_get_front_settings()
{
    global $SB_LANGUAGE;
    $active_user = sb_get_active_user();
    $return = [
        "registration-required" => sb_get_setting("registration-required"),
        "registration-timetable" => sb_get_setting("registration-timetable"),
        "registration-offline" => sb_get_setting("registration-offline"),
        "registration-link" => sb_get_setting("registration-link", ""),
        "registration-details" => sb_get_setting(
            "registration-user-details-success"
        ),
        "visitors-registration" => sb_get_setting("visitors-registration"),
        "privacy" => sb_get_multi_setting("privacy", "privacy-active"),
        "popup" => sb_get_block_setting("popup"),
        "follow" => sb_get_block_setting("follow"),
        "popup-mobile-hidden" => sb_get_multi_setting(
            "popup-message",
            "popup-mobile-hidden"
        ),
        "welcome" => sb_get_multi_setting("welcome-message", "welcome-active"),
        "welcome-trigger" => sb_get_multi_setting(
            "welcome-message",
            "welcome-trigger",
            "load"
        ),
        "welcome-delay" => sb_get_multi_setting(
            "welcome-message",
            "welcome-delay",
            2500
        ),
        "welcome-disable-office-hours" => sb_get_multi_setting(
            "welcome-message",
            "welcome-disable-office-hours"
        ),
        "subscribe" => sb_get_multi_setting(
            "subscribe-message",
            "subscribe-active"
        ),
        "subscribe-delay" => sb_get_multi_setting(
            "subscribe-message",
            "subscribe-delay",
            2000
        ),
        "chat-manual-init" => sb_get_setting("chat-manual-init"),
        "chat-login-init" => sb_get_setting("chat-login-init"),
        "chat-sound" => sb_get_setting("chat-sound", "n"),
        "header-name" => sb_get_setting("header-name", ""),
        "desktop-notifications" =>
        sb_get_setting("desktop-notifications") &&
            !sb_get_multi_setting(
                "push-notifications",
                "push-notifications-active"
            ),
        "flash-notifications" => sb_get_setting("flash-notifications"),
        "push-notifications" => sb_get_multi_setting(
            "push-notifications",
            "push-notifications-active"
        ),
        "notifications-icon" => sb_get_setting("notifications-icon", STMBX_URL . "/media/icon.png"),
        "bot-id" => sb_get_bot_id(),
        "bot-name" => sb_get_setting("bot-name", ""),
        "bot-image" => sb_get_setting("bot-image", ""),
        "bot-delay" => sb_get_setting("dialogflow-bot-delay", 2000),
        "bot-office-hours" => sb_get_setting("dialogflow-timetable"),
        "dialogflow-active" => sb_dialogflow_active(),
        "dialogflow-human-takeover" => sb_get_multi_setting(
            "dialogflow-human-takeover",
            "dialogflow-human-takeover-active"
        ),
        "dialogflow-welcome" => false,
        "slack-active" => defined("SB_SLACK") && sb_get_setting("slack-active"),
        "rich-messages" => sb_get_rich_messages_ids(),
        "display-users-thumb" => sb_get_setting("display-users-thumb"),
        "hide-agents-thumb" => sb_get_setting("hide-agents-thumb"),
        "translations" => sb_get_current_translations(),
        "auto-open" => sb_get_setting("auto-open"),
        "office-hours" => sb_office_hours(),
        "disable-office-hours" => sb_get_setting("chat-timetable-disable"),
        "disable-offline" => sb_get_setting("chat-offline-disable"),
        "timetable" => sb_get_multi_setting(
            "chat-timetable",
            "chat-timetable-active"
        ),
        "timetable-type" => sb_get_multi_setting(
            "chat-timetable",
            "chat-timetable-type"
        ),
        "timetable-hide" => sb_get_multi_setting(
            "chat-timetable",
            "chat-timetable-hide"
        ),
        "timetable-disable-agents" => sb_get_multi_setting(
            "chat-timetable",
            "chat-timetable-agents"
        ),
        "articles" => sb_get_setting("articles-active"),
        "articles-title" => sb_get_setting("articles-title", ""),
        "init-dashboard" =>
        sb_get_setting("init-dashboard") &&
            !sb_get_setting("disable-dashboard"),
        "disable-dashboard" => sb_get_setting("disable-dashboard"),
        "queue" => sb_get_multi_setting("queue", "queue-active"),
        "queue-message" => sb_get_multi_setting("queue", "queue-message", ""),
        "queue-response-time" => sb_get_multi_setting(
            "queue",
            "queue-response-time",
            5
        ),
        "hide-conversations-routing" =>
        !sb_get_multi_setting("queue", "queue-active") &&
            sb_get_multi_setting(
                "agent-hide-conversations",
                "agent-hide-conversations-active"
            ) &&
            sb_get_multi_setting(
                "agent-hide-conversations",
                "agent-hide-conversations-routing"
            ),
        "webhooks" => sb_get_multi_setting("webhooks", "webhooks-active")
            ? sb_get_multi_setting("webhooks", "webhooks-allowed", true)
            : false,
        "agents-online" => sb_agents_online(),
        "cron" => date("H") != sb_get_external_setting("cron"),
        "cron-email-piping" =>
        sb_get_multi_setting("email-piping", "email-piping-active") &&
            !sb_get_multi_setting(
                "email-piping",
                "email-piping-disable-cron"
            ) &&
            date("i") != sb_get_external_setting("cron-email-piping"),
        "cron-email-piping-active" =>
        sb_get_multi_setting("email-piping", "email-piping-active") &&
            !sb_get_multi_setting("email-piping", "email-piping-disable-cron"),
        "messenger" => defined("SB_MESSENGER"),
        "pusher" => sb_pusher_active(),
        "cookie-domain" => sb_get_setting("cookie-domain"),
        "visitor-default-name" => sb_get_setting(
            "visitor-default-name",
            "User"
        ),
        "sms-active-agents" => sb_get_multi_setting("sms", "sms-active-agents"),
        "language-detection" => false,
        "automations" => sb_automations_run_all(),
        "phone-codes" => sb_get_multi_setting(
            "performance",
            "performance-phone-codes"
        )
            ? []
            : (sb_get_setting("phone-code")
                ? [sb_get_setting("phone-code")]
                : array_values(
                    json_decode(
                        file_get_contents(
                            SB_PATH . "/resources/json/phone.json"
                        ),
                        true
                    )
                )
            ),
        "rtl" => sb_get_setting("rtl"),
        "close-chat" => sb_get_setting("close-chat"),
    ];

    if ($return["push-notifications"]) {
        $return["push-notifications-id"] = sb_get_multi_setting(
            "push-notifications",
            "push-notifications-id"
        );
        $return["push-notifications-url"] = sb_get_multi_setting(
            "push-notifications",
            "push-notifications-sw-url"
        );
        $return["push-notifications-users"] = sb_get_multi_setting(
            "push-notifications",
            "push-notifications-users-active"
        );
    }
    if ($return["pusher"]) {
        $return["pusher-key"] = sb_get_multi_setting("pusher", "pusher-key");
        $return["pusher-cluster"] = sb_get_multi_setting("pusher", "pusher-cluster");
    }
    if ($return["timetable-hide"] || !empty($return["timetable-type"])) {
        $return["timetable-message"] = [
            sb_(sb_get_multi_setting("chat-timetable", "chat-timetable-title")),
            sb_(sb_get_multi_setting("chat-timetable", "chat-timetable-msg")),
        ];
    }
    if (defined("SB_TICKETS")) {
        $return["tickets"] = true;
        $return["tickets-registration-required"] = sb_get_setting(
            "tickets-registration-required"
        );
        $return["tickets-registration-redirect"] = sb_get_setting(
            "tickets-registration-redirect",
            ""
        );
        $return["tickets-default-form"] = sb_get_setting(
            "tickets-registration-disable-password"
        )
            ? "registration"
            : sb_get_setting("tickets-default-form", "login");
        $return["tickets-conversations-title-user"] = sb_get_setting(
            "tickets-conversations-title-user"
        );
        $return["tickets-welcome-active"] = sb_get_multi_setting(
            "tickets-welcome-message",
            "tickets-welcome-message-active"
        );
        $return["tickets-welcome-message"] = sb_merge_fields(
            sb_(
                sb_get_multi_setting(
                    "tickets-welcome-message",
                    "tickets-welcome-message-msg"
                )
            )
        );
        $return["tickets-conversation-name"] = sb_get_setting(
            "tickets-conversation-name",
            ""
        );
        $return["tickets-enter-button"] = sb_get_setting(
            "tickets-enter-button"
        );
        $return["tickets-manual-init"] = sb_get_setting("tickets-manual-init");
        $return["tickets-default-department"] = sb_get_setting(
            "tickets-default-department"
        );
        $return["tickets-names"] = sb_get_setting("tickets-names");
        $return["tickets-names"] = sb_get_setting("tickets-names");
        $return["tickets-recaptcha"] = sb_get_multi_setting(
            "tickets-recaptcha",
            "tickets-recaptcha-active"
        )
            ? sb_get_multi_setting("tickets-recaptcha", "tickets-recaptcha-key")
            : false;
        $return["tickets-first-ticket"] = sb_get_multi_setting(
            "tickets-disable-features",
            "tickets-first-ticket"
        );
    }

    if ($return["dialogflow-active"]) {
        $return["dialogflow-welcome"] = sb_get_setting("dialogflow-welcome");
        $return["dialogflow-send-user-details"] = sb_get_setting(
            "dialogflow-send-user-details"
        );
        $return["dialogflow-departments"] = sb_get_setting(
            "dialogflow-departments"
        );
        if ($return["queue"] && $return["dialogflow-human-takeover"]) {
            $return["queue"] = false;
            $return["queue-human-takeover"] = true;
        }
    } elseif (
        defined("SB_DIALOGFLOW") &&
        sb_get_multi_setting(
            "dialogflow-language-detection",
            "dialogflow-language-detection-active"
        )
    ) {
        $return["language-detection"] = true;
    }
    if ($active_user) {
        $user_id = $active_user["id"];
        $current_url = false;
        if (!sb_is_agent($active_user)) {
            try {
                $current_url = isset($_POST["current_url"])
                    ? $_POST["current_url"]
                    : $_SERVER["HTTP_REFERER"];
                if ($current_url) {
                    sb_current_url($user_id, $current_url);
                }
            } catch (Exception $e) {
            }
            if ($return["pusher"]) {
                sb_pusher_trigger("private-user-" . $user_id, "init", [
                    "current_url" => $current_url,
                ]);
            }
        }
        sb_update_users_last_activity($user_id);
    }
    return $return;
}

function sb_get_block_setting($value)
{
    $default = ["active" => false];
    switch ($value) {
        case "privacy":
            $settings = sb_get_setting("privacy");
            return $settings
                ? [
                    "active" => sb_isset($settings, "privacy-active"),
                    "title" => sb_rich_value($settings["privacy-title"]),
                    "message" => sb_rich_value($settings["privacy-msg"]),
                    "decline" => sb_rich_value(
                        $settings["privacy-msg-decline"]
                    ),
                    "link" => $settings["privacy-link"],
                    "link-name" => sb_rich_value(
                        sb_isset($settings, "privacy-link-text", ""),
                        false
                    ),
                    "btn-approve" => sb_rich_value(
                        $settings["privacy-btn-approve"],
                        false
                    ),
                    "btn-decline" => sb_rich_value(
                        $settings["privacy-btn-decline"],
                        false
                    ),
                ]
                : $default;
        case "popup":
            $settings = sb_get_setting("popup-message");
            return $settings
                ? [
                    "active" => sb_isset($settings, "popup-active"),
                    "title" => sb_rich_value($settings["popup-title"]),
                    "message" => sb_rich_value(nl2br($settings["popup-msg"])),
                    "image" => $settings["popup-image"],
                ]
                : $default;
        case "welcome":
            $settings = sb_get_setting("welcome-message");
            return $settings
                ? [
                    "active" => sb_isset($settings, "welcome-active"),
                    "message" => sb_rich_value(
                        $settings["welcome-msg"],
                        true,
                        true,
                        true
                    ),
                    "open" => $settings["welcome-open"],
                    "sound" => $settings["welcome-sound"],
                ]
                : $default;
        case "follow":
            $settings = sb_get_setting("follow-message");
            return $settings
                ? [
                    "active" => sb_isset($settings, "follow-active"),
                    "title" => sb_rich_value($settings["follow-title"]),
                    "message" => sb_rich_value(
                        $settings["follow-msg"],
                        false,
                        true
                    ),
                    "name" => $settings["follow-name"] ? "true" : "false",
                    "last-name" => sb_isset($settings, "follow-last-name")
                        ? "true"
                        : "false",
                    "phone" => sb_isset($settings, "follow-phone")
                        ? "true"
                        : "false",
                    "phone-required" => sb_isset(
                        $settings,
                        "follow-phone-required"
                    )
                        ? "true"
                        : "false",
                    "success" => sb_rich_value(
                        str_replace(
                            "{user_name}",
                            "{user_name_}",
                            $settings["follow-success"]
                        )
                    ),
                    "placeholder" => sb_rich_value(
                        sb_isset($settings, "follow-placeholder", "Email")
                    ),
                    "delay" => sb_isset($settings, "follow-delay"),
                    "disable-office-hours" => sb_isset(
                        $settings,
                        "follow-disable-office-hours"
                    ),
                ]
                : $default;
        case "subscribe":
            $settings = sb_get_setting("subscribe-message");
            $settings_follow = sb_get_setting("follow-message");
            $message =
                '[email id="sb-subscribe-form" title="' .
                sb_rich_value($settings["subscribe-title"]) .
                '" message="' .
                sb_rich_value($settings["subscribe-msg"], false) .
                '" success="' .
                sb_rich_value(
                    str_replace(
                        "{user_name}",
                        "{user_name_}",
                        $settings["subscribe-msg-success"]
                    )
                ) .
                '" placeholder="' .
                sb_rich_value(
                    sb_isset($settings, "follow-placeholder", "Email")
                ) .
                '" name="' .
                ($settings_follow["follow-name"] ? "true" : "false") .
                '" last-name="' .
                ($settings_follow["follow-last-name"] ? "true" : "false") .
                '"]';
            return $settings
                ? [
                    "active" => sb_isset($settings, "subscribe-active"),
                    "message" => $message,
                    "sound" => $settings["subscribe-sound"],
                ]
                : $default;
    }
    return false;
}

function sb_color_palette($id = "")
{
    return '<div data-type="color-palette" data-value="" data-id="' .
        $id .
        '" class="sb-color-palette"><span></span><ul><li data-value=""></li><li data-value="red"></li><li data-value="yellow"></li><li data-value="green"></li><li data-value="pink"></li><li data-value="gray"></li><li data-value="blue"></li></ul></div>';
}

function sb_export_settings()
{
    $setting_keys = ["automations", "emails", "rich-messages", "settings"];
    $settings = [];
    for ($i = 0; $i < count($setting_keys); $i++) {
        $value = sb_isset(
            sb_db_get(
                'SELECT value FROM sb_settings WHERE name = "' .
                    $setting_keys[$i] .
                    '"'
            ),
            "value"
        );
        if ($value) {
            $value = json_decode($value, true);
            if ($value) {
                $settings[$setting_keys[$i]] = $value;
            }
        }
    }
    $settings = json_encode($settings, JSON_INVALID_UTF8_IGNORE);
    if ($settings) {
        $name = "settings" . "_" . rand(100000, 999999999) . ".json";
        $response = sb_file(SB_PATH . "/uploads/" . $name, $settings);
        return $response ? STMBX_URL . "/uploads/" . $name : $response;
    }
    return JSON_ERROR_NONE !== json_last_error()
        ? json_last_error_msg()
        : false;
}

function sb_import_settings($file_url)
{
    $settings = json_decode(sb_download($file_url), true);
    if ($settings) {
        foreach ($settings as $key => $setting) {
            sb_save_external_setting($key, $setting);
        }
        unlink(SB_PATH . substr($file_url, strpos($file_url, "/uploads/")));
        return true;
    }
    return JSON_ERROR_NONE !== json_last_error()
        ? json_last_error_msg()
        : false;
}


/*
 * ----------------------------------------------------------
 * EMAIL
 * ----------------------------------------------------------
 *
 * 1. Create the email contents
 * 2. Create the email contents secondary function
 * 3. Send an email to the given address
 * 4. Send an email to the address of the given user ID
 * 5. Send a test email
 * 6. Check if the active user can send the requested email
 * 7. Email piping function
 * 8. Send the successfull subscription email
 * 9. Append the email header and the signature to an email content
 * 10. Convert the text formatting of Routin to HTML
 * 11. Remove the text formatting of Routin
 * 12. Newsletter
 *
 */

function sb_email_create(
    $recipient_id,
    $sender_name,
    $sender_profile_image,
    $message,
    $attachments = [],
    $department = false,
    $conversation_id = false
) {
    $recipient = false;
    $recipient_name = "";
    $recipient_email = "";
    $recipient_user_type = "agent";
    if ($recipient_id == "email-test") {
        $recipient_name = "Test user";
    } elseif (
        $recipient_id == -1 ||
        $recipient_id == "agents" ||
        $recipient_id == "all-agents" ||
        strpos($recipient_id, "department-") !== false
    ) {
        $department = $department
            ? $department
            : (strpos($recipient_id, "department-") !== false
                ? substr($recipient_id, 11)
                : false);
        $agents = sb_db_get(
            'SELECT id, first_name, last_name, email FROM sb_users WHERE (user_type = "agent" OR user_type = "admin") ' .
                (empty($department) || $department == -1
                    ? ($recipient_id == "agents"
                        ? ' AND (department IS NULL OR department = "")'
                        : "")
                    : " AND department = " . sb_db_escape($department)),
            false
        );
        $online_agents_ids = sb_get_online_user_ids(true);
        for ($i = 0; $i < count($agents); $i++) {
            if (!in_array($agents[$i]["id"], $online_agents_ids)) {
                $recipient_name .= sb_get_user_name($agents[$i]) . ", ";
                $recipient_email .= $agents[$i]["email"] . ",";
                if ($i == 0) {
                    $recipient_id = $agents[$i]["id"];
                }
            }
        }
        $recipient_name = mb_substr($recipient_name, 0, -2);
        $recipient_email = substr($recipient_email, 0, -1);
    } else {
        if (
            !sb_email_security($recipient_id) &&
            sb_get_active_user_ID() != $recipient_id
        ) {
            return new SBError("security-error", "sb_email_create");
        }
        $recipient = sb_get_user($recipient_id);
        if (!$recipient || !$recipient["email"]) {
            return new SBValidationError("email-not-found");
        }
        $recipient_name = sb_get_user_name($recipient);
        $recipient_email = $recipient["email"];
        $recipient_user_type = $recipient["user_type"];
    }

    $suffix = sb_is_agent($recipient_user_type) ? "agent" : "user";
    $settings = sb_get_multilingual_setting(
        "emails",
        "email-" . $suffix,
        sb_get_user_language($recipient_id)
    );
    $email = sb_email_create_content(
        $settings["email-" . $suffix . "-subject"],
        $settings["email-" . $suffix . "-content"],
        $attachments,
        [
            "conversation_url_parameter" =>
            $recipient && $conversation_id
                ? "?conversation=" .
                $conversation_id .
                "&token=" .
                $recipient["token"]
                : "",
            "message" => $message,
            "recipient_name" => $recipient_name,
            "sender_name" => $sender_name,
            "sender_profile_image" => $sender_profile_image,
            "conversation_id" => $conversation_id,
        ]
    );
    $piping = sb_email_piping_suffix($conversation_id);
    $piping_delimiter =
        !empty($piping) &&
        sb_get_multi_setting("email-piping", "email-piping-delimiter")
        ? '<div style="color:#b5b5b5">### ' .
        sb_("Please type your reply above this line") .
        " ###</div><br><br>"
        : "";
    return sb_email_send(
        $recipient_email,
        $email[0],
        $piping_delimiter . $email[1],
        $piping
    );
}

function sb_email_create_content($subject, $body, $attachments, $replacements)
{
    if (empty($attachments)) {
        $attachments = [];
    }
    if (!$subject) {
        $subject = "Message from {sender_name}";
    }
    if (!$body) {
        $body = "Hello {recipient_name}!<br />{message}{attachments}";
    }
    $subject = str_replace(
        ["{recipient_name}", "{sender_name}"],
        [
            $replacements["recipient_name"],
            sb_isset($replacements, "sender_name"),
        ],
        $subject
    );
    $body = str_replace(
        [
            "{conversation_url_parameter}",
            "{recipient_name}",
            "{sender_name}",
            "{sender_profile_image}",
            "{message}",
            "{attachments}",
            "{conversation_link}",
        ],
        [
            "conversation_url_parameter" => sb_isset(
                $replacements,
                "conversation_url_parameter",
                ""
            ),
            $replacements["recipient_name"],
            sb_isset($replacements, "sender_name"),
            sb_isset($replacements, "sender_profile_image"),
            $replacements["message"],
            sb_email_attachments_code($attachments),
            STMBX_URL .
                "/admin.php" .
                (isset($replacements["conversation_id"])
                    ? "?conversation=" . $replacements["conversation_id"]
                    : ""),
        ],
        $body
    );
    return [$subject, $body];
}

function sb_email_send($to, $subject, $body, $sender_suffix = "")
{
    $settings = sb_get_setting("email-server");
    $host = sb_isset($settings, "email-server-host");
    if (empty($to)) {
        return false;
    }
    if ($host) {
        require_once SB_PATH . "/vendor/phpmailer/PHPMailerAutoload.php";
        $port = $settings["email-server-port"];
        $mail = new PHPMailer();
        $body = nl2br(trim(sb_text_formatting_to_html($body)));
        $mail->CharSet = "UTF-8";
        $mail->Encoding = "base64";
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $settings["email-server-user"];
        $mail->Password = $settings["email-server-password"];
        $mail->SMTPSecure = $port == 25 ? "" : ($port == 465 ? "ssl" : "tls");
        $mail->Port = $port;
        $mail->setFrom(
            $settings["email-server-from"],
            sb_isset($settings, "email-sender-name", "") . $sender_suffix
        );
        $mail->isHTML(true);
        $mail->Subject = trim($subject);
        $mail->Body = $body;
        $mail->AltBody = $body;
        if (strpos($to, ",") > 0) {
            $emails = explode(",", $to);
            for ($i = 0; $i < count($emails); $i++) {
                $mail->addAddress($emails[$i]);
            }
        } else {
            $mail->addAddress($to);
        }
        if (!$mail->send()) {
            return new SBError(
                "email-error",
                "sb_email_send",
                $mail->ErrorInfo
            );
        } else {
            return true;
        }
    } else {
        return mail($to, $subject, $body);
    }
}

function sb_email($recipient_id, $message, $attachments = [], $sender_id = -1)
{
    if (!$recipient_id || empty($message)) {
        return new SBValidationError("missing-user-id-or-message");
    }
    if (!sb_email_security($recipient_id)) {
        return new SBError("security-error", "sb_email");
    }
    $sender = $sender_id == -1 ? sb_get_active_user() : sb_get_user($sender_id);
    $user = sb_get_user($recipient_id);
    if ($sender && $user && isset($sender["id"]) && isset($user["id"])) {
        if ($user["email"] == "") {
            return new SBValidationError("user-email-not-found");
        }
        $email_type = sb_is_agent($user["id"]) ? "agent" : "user";
        $emails = sb_get_multilingual_setting(
            "emails",
            "email-" . $email_type,
            sb_get_user_language($recipient_id)
        );
        $email = sb_email_create_content(
            $emails["email-" . $email_type . "-subject"],
            $emails["email-" . $email_type . "-content"],
            $attachments,
            [
                "message" => $message,
                "recipient_name" => sb_get_user_name($user),
                "sender_name" => sb_get_user_name($sender),
                "sender_profile_image" => $sender["profile_image"],
            ]
        );
        return sb_email_send($user["email"], $email[0], $email[1]);
    } else {
        return new SBError("user-or-sender-not-found", "sb_email");
    }
}

function sb_email_send_test($to, $email_type)
{
    $user = sb_get_active_user();
    $name = sb_get_user_name($user);
    $image = STMBX_URL . "/media/user.png";
    $attachments = [["Example link", $image], ["Example link two", $image]];
    $settings = sb_get_multilingual_setting("emails", "email-" . $email_type);
    $email = sb_email_create_content(
        $settings["email-" . $email_type . "-subject"],
        $settings["email-" . $email_type . "-content"],
        $attachments,
        [
            "message" =>
            "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam",
            "recipient_name" => $name,
            "sender_name" => $name,
            "sender_profile_image" => $user["profile_image"],
        ]
    );
    return sb_email_send($to, $email[0], $email[1]);
}

function sb_email_security($user_id)
{
    if (sb_is_agent() || !empty($GLOBALS["SB_FORCE_ADMIN"])) {
        return true;
    } else {
        $user = sb_db_get(
            "SELECT user_type FROM sb_users WHERE id = " . $user_id
        );
        return !sb_is_error($user) &&
            isset($user["user_type"]) &&
            sb_is_agent($user["user_type"]);
    }
}

function sb_email_piping($force = false)
{
    if (!$force && date("i") == sb_get_external_setting("cron-email-piping")) {
        return false;
    }
    sb_save_external_setting("cron-email-piping", date("i"));
    $settings = sb_get_setting("email-piping");
    if ($settings && !empty($settings["email-piping-active"])) {
        $port = $settings["email-piping-port"];
        $host = $settings["email-piping-host"];
        $all_emails = sb_isset($settings, "email-piping-all");
        $today = date("d F Y");
        $last_check = sb_get_external_setting("email-piping-check");
        ini_set("default_socket_timeout", 5);
        imap_timeout(IMAP_OPENTIMEOUT, 5);
        $inbox = imap_open(
            "{" .
                $host .
                ":" .
                $port .
                "/" .
                ($port == 143 || $port == 993 ? "imap" : "pop3") .
                ($port == 995 || $port == 993 ? "/ssl" : "") .
                ($port == 995 ? "/novalidate-cert" : "") .
                "}INBOX",
            $settings["email-piping-user"],
            $settings["email-piping-password"]
        );
        ini_restore("default_socket_timeout");
        $attachments_path = sb_upload_path(false, true) . "/";
        $attachments_url = sb_upload_path(true, true) . "/";
        if ($inbox) {
            set_time_limit(1000); // Set a higher time limit for processing emails
            $emails = imap_search(
                $inbox,
                'ALL SINCE "' .
                    (empty($last_check) ? $today : $last_check) .
                    '"'
            );
            if ($emails) {
                $department_id = sb_isset($settings, "email-piping-department");
                $history = sb_get_external_setting("email-piping-history", []);
                $history_new = [];
                rsort($emails);
                foreach ($emails as $email_number) {
                    $overview = imap_fetch_overview($inbox, $email_number, 0);
                    $to = $overview[0]->to;
                    $from = $overview[0]->from;
                    $follow_up = strpos($to, "| SB");
                    if ($all_emails || $follow_up) {
                        $conversation_id = false;
                        $id = false;
                        if ($follow_up) {
                            $conversation_id = substr(
                                $to,
                                strpos($to, "| SB") + 4
                            );
                            $conversation_id = substr(
                                $conversation_id,
                                0,
                                strpos($conversation_id, "<") - 1
                            );
                            $conversation_id = explode("-", $conversation_id);
                            $id = hash(
                                "sha1",
                                $conversation_id[1] . $overview[0]->date
                            );
                            $conversation_id = sb_db_escape(
                                $conversation_id[0]
                            );
                            if (
                                !sb_db_get(
                                    "SELECT id FROM sb_conversations WHERE id = " .
                                        $conversation_id
                                )
                            ) {
                                $follow_up = false;
                                $conversation_id = false;
                            }
                        }
                        if (!$follow_up) {
                            $id = hash("sha1", $from . $overview[0]->date);
                        }
                        if (!in_array($id, $history)) {
                            $from_email = mb_strpos($from, "<")
                                ? trim(
                                    mb_substr(
                                        $from,
                                        mb_strpos($from, "<") + 1,
                                        -1
                                    )
                                )
                                : $from;
                            $from_name =
                                mb_strpos($from, "<") &&
                                mb_strpos($from, "=") === false &&
                                mb_strpos($from, "?") === false
                                ? trim(
                                    mb_substr(
                                        $from,
                                        0,
                                        mb_strpos($from, "<")
                                    )
                                )
                                : "";
                            $sender = sb_db_get(
                                'SELECT * FROM sb_users WHERE email = "' .
                                    sb_db_escape($from_email) .
                                    '" LIMIT 1'
                            );

                            if (!$sender) {
                                $name = sb_split_name($from_name);
                                $sender = sb_add_user([
                                    "email" => $from_email,
                                    "first_name" => $name[0],
                                    "last_name" => $name[1],
                                ]);
                                $sender = sb_db_get(
                                    "SELECT * FROM sb_users WHERE id = " .
                                        $sender
                                );
                            }
                            if (
                                $sender &&
                                ($follow_up || !sb_is_agent($sender))
                            ) {
                                $message = imap_fetchbody(
                                    $inbox,
                                    $email_number,
                                    1
                                );
                                $structure = imap_fetchstructure(
                                    $inbox,
                                    $email_number
                                );
                                $agent = sb_is_agent($sender);

                                // Message decoding
                                $message_temp = false;
                                $position = strpos(
                                    $message,
                                    ": multipart/alternative"
                                );
                                if ($position) {
                                    $message_temp = substr(
                                        $message,
                                        strpos($message, ": text/plain")
                                    );
                                    $position = strpos(
                                        $message_temp,
                                        "Content-Type:"
                                    );
                                    if ($position) {
                                        $message_temp = substr(
                                            $message_temp,
                                            0,
                                            $position
                                        );
                                    }
                                    $message_temp = substr(
                                        $message_temp,
                                        strpos($message_temp, ": text/plain")
                                    );
                                    if ($message_temp) {
                                        $message = $message_temp;
                                    }
                                }
                                $position = strpos($message, ": base64");
                                if ($position) {
                                    $message_temp = substr(
                                        $message,
                                        $position + 8
                                    );
                                    $position = strpos(
                                        $message_temp,
                                        ": base64"
                                    );
                                    if ($position) {
                                        $message_temp = substr(
                                            $message_temp,
                                            0,
                                            sb_mb_strpos_reverse(
                                                $message_temp,
                                                PHP_EOL,
                                                $position
                                            )
                                        );
                                    }
                                    $position = strpos($message_temp, "--");
                                    if ($position) {
                                        $message_temp = substr(
                                            $message_temp,
                                            0,
                                            $position
                                        );
                                    }
                                    $message_temp = str_replace(
                                        ["\r", "\n"],
                                        "",
                                        $message_temp
                                    );
                                    $message_temp = imap_base64($message_temp);
                                    if ($message_temp) {
                                        $message = $message_temp;
                                    }
                                }
                                if (strpos($message, "quoted-printable")) {
                                    $message = quoted_printable_decode(
                                        $message
                                    );
                                } else {
                                    $encoding =
                                        isset($structure->parts) &&
                                        count($structure->parts)
                                        ? $structure->parts[0]->encoding
                                        : ($structure->encoding
                                            ? $structure->encoding
                                            : false);
                                    if ($encoding) {
                                        switch ($encoding) {
                                            case 0:
                                            case 1:
                                                $message = imap_8bit($message);
                                                break;
                                            case 2:
                                                $message = imap_binary(
                                                    $message
                                                );
                                                break;
                                            case 3:
                                                $message = imap_base64(
                                                    $message
                                                );
                                                break;
                                            default:
                                                $message = quoted_printable_decode(
                                                    $message
                                                );
                                                break;
                                        }
                                    }
                                }
                                if (mb_detect_encoding($message) != "UTF-8") {
                                    $message = mb_convert_encoding($message, "UTF-8");
                                }


                                // Message formatting
                                $message = str_replace(
                                    ["<br>", "<br/>", "<br />"],
                                    PHP_EOL,
                                    $message
                                );
                                $position = mb_strpos($message, " | SB");
                                if ($position) {
                                    $message = mb_substr(
                                        $message,
                                        0,
                                        sb_mb_strpos_reverse(
                                            $message,
                                            PHP_EOL,
                                            $position
                                        )
                                    );
                                }
                                $position = mb_strpos(
                                    $message,
                                    $from_name . " <"
                                );
                                if ($position) {
                                    $message = mb_substr(
                                        $message,
                                        0,
                                        sb_mb_strpos_reverse(
                                            $message,
                                            PHP_EOL,
                                            $position
                                        )
                                    );
                                }
                                $position = mb_strpos(
                                    $message,
                                    "Content-Type:"
                                );
                                if ($position) {
                                    $message = mb_substr(
                                        $message,
                                        mb_strpos(
                                            mb_substr($message, $position),
                                            PHP_EOL
                                        ) + $position
                                    );
                                    $position = mb_strpos(
                                        $message,
                                        "Content-Type:"
                                    );
                                    if ($position) {
                                        $message = mb_substr(
                                            $message,
                                            0,
                                            $position
                                        );
                                    }
                                }
                                $position = mb_strpos(
                                    $message,
                                    "______________________________"
                                );
                                if ($position) {
                                    $message = mb_substr(
                                        $message,
                                        0,
                                        $position
                                    );
                                }
                                $position = mb_strpos($message, "Outlook");
                                if ($position) {
                                    $message = mb_substr(
                                        $message,
                                        0,
                                        mb_strrpos(
                                            $message,
                                            "\n",
                                            $position * -1
                                        )
                                    );
                                }
                                $position = mb_strpos(
                                    $message,
                                    "Content-Transfer-Encoding:"
                                );
                                $position_2 = mb_strpos(
                                    $message,
                                    "Content-Type: text/plain"
                                );
                                if ($position) {
                                    if (
                                        $position_2 &&
                                        $position_2 < $position
                                    ) {
                                        $message = mb_substr(
                                            $message,
                                            mb_strpos(
                                                $message,
                                                "\n",
                                                $position_2
                                            ),
                                            mb_strpos($message, "\n", $position)
                                        );
                                    } else {
                                        $message = mb_substr(
                                            $message,
                                            mb_strpos($message, "\n", $position)
                                        );
                                    }
                                }
                                $strings_check = [">:", "> wrote:", "--0"];
                                for ($i = 0; $i < count($strings_check); $i++) {
                                    if (
                                        mb_strpos($message, $strings_check[$i])
                                    ) {
                                        $message = mb_substr(
                                            $message,
                                            0,
                                            sb_mb_strpos_reverse(
                                                $message,
                                                PHP_EOL,
                                                mb_strpos(
                                                    $message,
                                                    $strings_check[$i]
                                                )
                                            )
                                        );
                                    }
                                }
                                $message = str_replace(
                                    ["wrote:" . PHP_EOL, "wrote:"],
                                    "",
                                    $message
                                );
                                if (
                                    $settings["email-piping-delimiter"] &&
                                    mb_strpos($message, "### ")
                                ) {
                                    $message = str_replace(
                                        "> ###",
                                        "###",
                                        $message
                                    );
                                    $message = mb_substr(
                                        $message,
                                        0,
                                        mb_strpos($message, "### ")
                                    );
                                }
                                if (!empty($message)) {
                                    $message = preg_replace(
                                        '/(<(script|style)\b[^>]*>).*?(<\/\2>)/is',
                                        "$1$3",
                                        $message
                                    );
                                    $message = strip_tags($message);
                                    $message = preg_replace(
                                        "/\[image[\s\S]+?\]/",
                                        "",
                                        $message
                                    );
                                    $message = str_replace(
                                        "&nbsp;",
                                        " ",
                                        $message
                                    );
                                    while (
                                        mb_strpos($message, PHP_EOL . "> ")
                                    ) {
                                        $message = mb_substr(
                                            $message,
                                            0,
                                            mb_strpos(
                                                $message,
                                                PHP_EOL . "> "
                                            ) - 2
                                        );
                                    }
                                    while (
                                        strpos($message, " " . PHP_EOL) !==
                                        false ||
                                        strpos($message, PHP_EOL . " ") !==
                                        false
                                    ) {
                                        $message = str_replace(
                                            [" " . PHP_EOL, PHP_EOL . " "],
                                            PHP_EOL,
                                            $message
                                        );
                                    }
                                    while (
                                        strpos(
                                            $message,
                                            PHP_EOL . PHP_EOL . PHP_EOL
                                        ) !== false
                                    ) {
                                        $message = str_replace(
                                            PHP_EOL . PHP_EOL . PHP_EOL,
                                            PHP_EOL . PHP_EOL,
                                            $message
                                        );
                                    }
                                    $message = trim($message);
                                }

                                // Attachments
                                $attachments = sb_email_piping_attachments(
                                    $structure,
                                    $inbox,
                                    $email_number
                                );
                                $attachments_2 = [];
                                if (
                                    count($attachments) &&
                                    !file_exists($attachments_path)
                                ) {
                                    mkdir($attachments_path, 0777, true);
                                }
                                for ($i = 0; $i < count($attachments); $i++) {
                                    $file_name =
                                        rand(1000, 99999) .
                                        "_" .
                                        $attachments[$i]["filename"];
                                    sb_file(
                                        $attachments_path . $file_name,
                                        $attachments[$i]["attachment"]
                                    );
                                    array_push($attachments_2, [
                                        $attachments[$i]["filename"],
                                        $attachments_url . $file_name,
                                    ]);
                                }

                                // Send message
                                if (!empty($message)) {
                                    $GLOBALS["SB_FORCE_ADMIN"] = true;
                                    if (!$follow_up) {
                                        $conversation_id = sb_isset(
                                            sb_new_conversation(
                                                $sender["id"],
                                                2,
                                                mb_decode_mimeheader(
                                                    $overview[0]->subject
                                                ),
                                                $department_id,
                                                -1,
                                                "em"
                                            ),
                                            "details",
                                            []
                                        )["id"];
                                    }
                                    sb_send_message(
                                        $sender["id"],
                                        $conversation_id,
                                        $message,
                                        $attachments_2,
                                        $agent ? 1 : 2
                                    );

                                    // Notifications
                                    $recipient = sb_get_user_from_conversation(
                                        $conversation_id,
                                        !$agent
                                    );
                                    if (
                                        isset($recipient["id"]) &&
                                        !sb_is_user_online($recipient["id"])
                                    ) {
                                        if (
                                            ($agent &&
                                                sb_get_setting(
                                                    "notify-user-email"
                                                )
                                            ) ||
                                            (!$agent &&
                                                sb_get_setting(
                                                    "notify-agent-email"
                                                )
                                            )
                                        ) {
                                            sb_email_create(
                                                $recipient["id"],
                                                sb_get_user_name($sender),
                                                $sender["profile_image"],
                                                $message,
                                                $attachments_2,
                                                false,
                                                $conversation_id
                                            );
                                        }
                                        if (
                                            ($agent &&
                                                sb_get_multi_setting(
                                                    "sms",
                                                    "sms-active-users"
                                                )
                                            ) ||
                                            (!$agent &&
                                                sb_get_multi_setting(
                                                    "sms",
                                                    "sms-active-agents"
                                                )
                                            )
                                        ) {
                                            $phone = sb_get_user_extra(
                                                $recipient["id"],
                                                "phone"
                                            );
                                            if ($phone) {
                                                sb_send_sms(
                                                    $message,
                                                    $phone,
                                                    true,
                                                    $conversation_id,
                                                    $attachments_2
                                                );
                                            }
                                        }
                                    } elseif (
                                        !$follow_up &&
                                        sb_get_setting("notify-agent-email")
                                    ) {
                                        sb_send_agents_notifications(
                                            $message,
                                            false,
                                            $conversation_id,
                                            $attachments_2
                                        );
                                    }

                                    // Dialogflow and Slack
                                    if (!$agent) {
                                        if (
                                            defined("SB_DIALOGFLOW") &&
                                            sb_get_setting(
                                                "dialogflow-email-piping"
                                            )
                                        ) {
                                            sb_messaging_platforms_functions(
                                                $conversation_id,
                                                $message,
                                                $attachments_2,
                                                $sender,
                                                [
                                                    "source" => "em",
                                                    "user_id" => $sender["id"],
                                                    "conversation_id" => $conversation_id,
                                                ]
                                            );
                                            sb_db_query(
                                                "DELETE FROM sb_messages WHERE conversation_id = " .
                                                    $conversation_id .
                                                    ' AND payload LIKE "%NO_MATCH%" AND creation_time < "' .
                                                    gmdate(
                                                        "Y-m-d H:i:s",
                                                        time() + 60
                                                    ) .
                                                    '" ORDER BY id DESC LIMIT 1'
                                            );
                                        }
                                    }

                                    $GLOBALS["SB_FORCE_ADMIN"] = false;
                                }
                                array_push($history_new, $id);
                            }
                        }
                    }
                }
                if ($last_check != $today) {
                    $history = [];
                }
                sb_save_external_setting(
                    "email-piping-history",
                    array_merge($history, $history_new)
                );
            }
            if ($last_check != $today) {
                sb_save_external_setting("email-piping-check", $today);
            }
            imap_close($inbox);
            return true;
        }
        return new SBError(
            "connection-error",
            "sb_email_piping",
            imap_last_error()
        );
    }
    return true;
}

function sb_email_piping_attachments(
    $structure,
    &$inbox,
    &$email_number,
    $part_index = false
) {
    $attachments = [];
    $count = isset($structure->parts) ? count($structure->parts) : 0;
    for ($i = 0; $i < $count; $i++) {
        $part = $structure->parts[$i];
        $attachment = false;
        $parameters = $part->ifdparameters
            ? $part->dparameters
            : ($part->ifparameters
                ? $part->parameters
                : []);
        foreach ($parameters as $object) {
            if (
                in_array(strtolower($object->attribute), ["name", "filename"])
            ) {
                $attachment = ["filename" => $object->value];
            }
        }
        if ($attachment) {
            $index = ($part_index ? $part_index : $i) + 1;
            $attachment_temp = imap_fetchbody(
                $inbox,
                $email_number,
                $index . "." . ($i + 1)
            );
            if (!$attachment_temp) {
                $attachment_temp = imap_fetchbody(
                    $inbox,
                    $email_number,
                    $index
                );
            }
            if ($part->encoding == 3) {
                $attachment_temp = base64_decode($attachment_temp);
            } elseif ($part->encoding == 4) {
                $attachment_temp = quoted_printable_decode($attachment_temp);
            }
            $attachment["attachment"] = $attachment_temp;
            array_push($attachments, $attachment);
        }
        if (property_exists($part, "parts") && $part->parts) {
            array_merge(
                $attachments,
                sb_email_piping_attachments($part, $inbox, $email_number, $i)
            );
        }
    }
    return $attachments;
}

function sb_email_piping_suffix($conversation_id)
{
    return $conversation_id &&
        sb_get_multi_setting("email-piping", "email-piping-active")
        ? " | SB" . $conversation_id . "-" . rand(100, 9999)
        : "";
}

function sb_subscribe_email($email)
{
    $settings = sb_get_multilingual_setting("emails", "email-subscribe");
    $subject = $settings["email-subscribe-subject"];
    $content = $settings["email-subscribe-content"];
    sb_reports_update("subscribe");
    if ($settings && !empty($subject) && !empty($content)) {
        return sb_email_send(
            $email,
            sb_merge_fields($subject),
            sb_merge_fields($content)
        );
    }
    return false;
}

function sb_email_default_parts($body, $user_id = false)
{
    $lang = $user_id ? sb_get_user_language($user_id) : "en";
    return sb_get_multilingual_setting("emails", "email-header", $lang, "") .
        PHP_EOL .
        $body .
        PHP_EOL .
        sb_get_multilingual_setting("emails", "email-signature", $lang, "");
}

function sb_email_attachments_code($attachments)
{
    $code = "";
    for ($i = 0; $i < count($attachments); $i++) {
        $code .=
            '<a style="display:block;text-decoration:none;line-height:25px;color:rgb(102, 102, 102);" href="' .
            str_replace(" ", "%20", $attachments[$i][1]) .
            '">' .
            $attachments[$i][0] .
            "</a>";
    }
    if ($code) {
        $code = '<div style="margin-top: 30px">' . $code . "</div>";
    }
    return $code;
}

function sb_text_formatting_to_html($message, $clear = false)
{
    $regex = $clear
        ? [
            ["/\*(.*?)\*/", "", ""],
            ["/_(.*?)_/", "", ""],
            ["/~(.*?)~/", "", ""],
            ["/```(.*?)```/", "", ""],
        ]
        : [
            ["/\*(.*?)\*/", "<b>", "</b>"],
            ["/_(.*?)_/", "<em>", "</em>"],
            ["/~(.*?)~/", "<del>", "</del>"],
            ["/```(.*?)```/", "<code>", "</code>"],
        ];

    for ($i = 0; $i < count($regex); $i++) {
        $values = [];
        if (preg_match_all($regex[$i][0], $message, $values, PREG_SET_ORDER)) {
            for ($j = 0; $j < count($values); $j++) {
                $message = str_replace(
                    $values[$j][0],
                    $regex[$i][1] . $values[$j][1] . $regex[$i][2],
                    $message
                );
            }
        }
    }
    return $message;
}


function sb_clear_text_formatting($message)
{
    return sb_text_formatting_to_html($message, true);
}

function sb_newsletter($email, $first_name = "", $last_name = "")
{
    // $settings = sb_get_setting('newsletter');
    // if ($settings && $settings['newsletter-active']) {
    //     $post_fields = '';
    //     $header = ['Content-Type: application/json', 'Accept: application/json'];
    //     $url = false;
    //     $list_id = $settings['newsletter-list-id'];
    //     $key = $settings['newsletter-key'];
    //     $type = 'POST';
    //     switch ($settings['newsletter-service']) {
    //         case 'mailchimp':
    //             $url = 'https://' . substr($key, strpos($key, '-') + 1) . '.api.mailchimp.com/3.0/lists/' . $list_id . '/members/';
    //             $post_fields = ['email_address' => $email, 'status' => 'subscribed', 'merge_fields' => ['FNAME' => $first_name, 'LNAME' => $last_name]];
    //             array_push($header, 'Authorization: Basic ' . base64_encode('user:' . $key));
    //             break;
    //         case 'sendinblue':
    //             $url = 'https://api.sendinblue.com/v3/contacts';
    //             $post_fields = ['email' => $email, 'listIds' => [$list_id], 'updateEnabled' => false, 'attributes' => ['FIRSTNAME' => $first_name, 'LASTNAME' => $last_name]];
    //             array_push($header, 'api-key: ' . $key);
    //             break;
    //         case 'sendgrid':
    //             $url = 'https://api.sendgrid.com/v3/marketing/contacts';
    //             $post_fields = ['list_ids' => [$list_id], 'contacts' => [['email' => $email, 'first_name' => $first_name, 'last_name' => $last_name]]];
    //             array_push($header, 'Authorization: Bearer ' . $key);
    //             $type = 'PUT';
    //             break;
    //         case 'elasticemail':
    //             $url = 'https://api.elasticemail.com/v2/contact/add?email=' . $email .  '&publicAccountID=' . $key . '&listName=' . urlencode($list_id) . '&firstName=' . urlencode($first_name) . '&lastName=' . urlencode($last_name) . '&sendActivation=false';
    //             $type = 'GET';
    //             break;
    //         case 'campaignmonitor':
    //             $url = 'https://api.createsend.com/api/v3.2/subscribers/' . $list_id . '.json';
    //             $post_fields = ['EmailAddress' => $email, 'name' => $first_name . ' ' . $last_name, 'ConsentToTrack'=> 'Yes', 'Resubscribe' => true, 'RestartSubscriptionBasedAutoresponders' => true, 'CustomFields' => []];
    //             array_push($header, 'Authorization: Basic ' . base64_encode($key));
    //             break;
    //         case 'hubspot':
    //             $contact_id = sb_isset(sb_curl('https://api.hubapi.com/crm/v3/objects/contacts?hapikey=' . $key, json_encode(['properties' => ['email' => $email, 'firstname' => $first_name, 'lastname' => $last_name]]), $header), 'id');
    //             if ($contact_id && $list_id) {
    //                 $url = 'https://api.hubapi.com/contacts/v1/lists/' . $list_id . '/add?hapikey=' . $key;
    //                 $post_fields = ['vids' => [$contact_id]];
    //             }
    //             break;
    //         case 'moosend':
    //             $url = 'https://api.moosend.com/v3/subscribers/' . $list_id . '/subscribe.json?apikey=' . $key;
    //             $post_fields = ['Email' => $email, 'Name' => $first_name . ' ' . $last_name];
    //             break;
    //         case 'getresponse':
    //             $url = 'https://api.getresponse.com/v3/contacts';
    //             $post_fields = ['email' => $email, 'name' => $first_name . ' ' . $last_name, 'campaign' => ['campaignId' => $list_id]];
    //             array_push($header, 'X-Auth-Token: api-key ' . $key);
    //             break;
    //         case 'convertkit':
    //             $url = 'https://api.convertkit.com/v3/forms/' . $list_id . '/subscribe';
    //             $post_fields = ['api_secret' => $key, 'first_name' => $first_name . ' ' . $last_name, 'email' => $email];
    //             break;
    //         case 'activecampaign':
    //             $list_id = explode(':', $list_id);
    //             array_push($header, 'Api-Token: ' . $key);
    //             $contact_id = sb_isset(sb_curl('https://' . $list_id[0] . '.api-us1.com/api/3/contacts', json_encode(['contact' => ['email' => $email, 'firstName' => $first_name, 'lastName' => $last_name]]), $header), 'contact');
    //             if ($contact_id) {
    //                 $url = 'https://' . $list_id[0] . '.api-us1.com/api/3/contactLists';
    //                 $post_fields = ['contactList' => ['list' => $list_id[1], 'contact' => $contact_id['id'], 'status' => 1]];
    //             }
    //             break;
    //         case 'mailerlite':
    //             $url = 'https://api.mailerlite.com/api/v2/groups/' . $list_id . '/subscribers';
    //             $post_fields = ['email' => $email, 'apiKey' => $key, 'name' => $first_name, 'id' => $list_id, 'fields' => ['last_name' => $last_name]];
    //             break;
    //     }
    //     if ($url) return sb_curl($url, json_encode($post_fields), $header, $type);
    // }
    return false;
}




/*
 * ----------------------------------------------------------
 * INSTALLATION
 * ----------------------------------------------------------
 *
 * 1. Plugin installation function
 * 2. Update the config.php file
 * 3. Return the upload path or url
 * 4. Return the installation directory name
 *
 */

function sb_installation($details, $force = false)
{
    $database = [];
    if (sb_db_check_connection() === true && !$force) {
        return true;
    }
    if (
        !isset($details["db-name"]) ||
        !isset($details["db-user"]) ||
        !isset($details["db-password"]) ||
        !isset($details["db-host"])
    ) {
        return new SBValidationError("missing-details");
    } else {
        $database = [
            "name" => $details["db-name"][0],
            "user" => $details["db-user"][0],
            "password" => $details["db-password"][0],
            "host" => $details["db-host"][0],
            "port" =>
            isset($details["db-port"]) && $details["db-port"][0]
                ? intval($details["db-port"][0])
                : ini_get("mysqli.default_port"),
        ];
    }
    if (!isset($details["url"])) {
        return new SBValidationError("missing-url");
    } elseif (substr($details["url"], -1) == "/") {
        $details["url"] = substr($details["url"], 0, -1);
    }

    $connection_check = sb_db_check_connection(
        $database["name"],
        $database["user"],
        $database["password"],
        $database["host"],
        $database["port"]
    );
    $db_respones = [];
    $success = "";
    if ($connection_check === true) {
        // Create the database
        $connection = new mysqli(
            $database["host"],
            $database["user"],
            $database["password"],
            $database["name"],
            $database["port"]
        );
        $connection->set_charset("utf8mb4");

        $db_respones["users"] = $connection->query(
            "CREATE TABLE IF NOT EXISTS sb_users (id INT NOT NULL AUTO_INCREMENT, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, password VARCHAR(100), email VARCHAR(191) UNIQUE, profile_image VARCHAR(191), user_type VARCHAR(10) NOT NULL, creation_time DATETIME NOT NULL, token VARCHAR(50) NOT NULL UNIQUE, last_activity DATETIME, typing INT DEFAULT -1, department TINYINT, PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $db_respones["users_data"] = $connection->query(
            "CREATE TABLE IF NOT EXISTS sb_users_data (id INT NOT NULL AUTO_INCREMENT, user_id INT NOT NULL, slug VARCHAR(191) NOT NULL, name VARCHAR(191) NOT NULL, value TEXT NOT NULL, PRIMARY KEY (id), FOREIGN KEY (user_id) REFERENCES sb_users(id) ON DELETE CASCADE, UNIQUE INDEX sb_users_data_index (user_id, slug)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $db_respones["conversations"] = $connection->query(
            "CREATE TABLE IF NOT EXISTS sb_conversations (id int NOT NULL AUTO_INCREMENT, user_id INT NOT NULL, title VARCHAR(191), creation_time DATETIME NOT NULL, status_code TINYINT DEFAULT 0, department TINYINT, agent_id INT, source VARCHAR(2), label VARCHAR(191), templates VARCHAR(191), extra VARCHAR(191), tags VARCHAR(191), PRIMARY KEY (id), FOREIGN KEY (agent_id) REFERENCES sb_users(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES sb_users(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $db_respones["messages"] = $connection->query(
            "CREATE TABLE IF NOT EXISTS sb_messages (id int NOT NULL AUTO_INCREMENT, user_id INT NOT NULL, message TEXT NOT NULL, creation_time DATETIME NOT NULL, status_code TINYINT DEFAULT 0, attachments TEXT, payload TEXT, conversation_id INT NOT NULL, PRIMARY KEY (id), FOREIGN KEY (user_id) REFERENCES sb_users(id) ON DELETE CASCADE, FOREIGN KEY (conversation_id) REFERENCES sb_conversations(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin"
        );
        $db_respones["settings"] = $connection->query(
            "CREATE TABLE IF NOT EXISTS sb_settings (name VARCHAR(191) NOT NULL, value LONGTEXT, PRIMARY KEY (name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $db_respones["reports"] = $connection->query(
            "CREATE TABLE IF NOT EXISTS sb_reports (id INT NOT NULL AUTO_INCREMENT, name VARCHAR(191) NOT NULL, value VARCHAR(191) NOT NULL, creation_time DATE NOT NULL, external_id INT, extra VARCHAR(191), PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        // Create the admin user
        if (
            isset($details["first-name"]) &&
            isset($details["last-name"]) &&
            isset($details["email"]) &&
            isset($details["password"])
        ) {
            $now = gmdate("Y-m-d H:i:s");
            $token = bin2hex(openssl_random_pseudo_bytes(20));
            $db_respones["admin"] = $connection->query(
                'INSERT IGNORE INTO sb_users(first_name, last_name, password, email, profile_image, user_type, creation_time, token, last_activity) VALUES ("' .
                    sb_db_escape($details["first-name"][0]) .
                    '", "' .
                    sb_db_escape($details["last-name"][0]) .
                    '", "' .
                    password_hash(
                        $details["password"][0],
                        PASSWORD_DEFAULT
                    ) .
                    '", "' .
                    sb_db_escape($details["email"][0]) .
                    '", "' .
                    sb_db_escape($details["url"]) .
                    "/media/user.svg" .
                    '", "admin", "' .
                    $now .
                    '", "' .
                    $token .
                    '", "' .
                    $now .
                    '")'
            );
        }

        // Create the config.php file
        $raw = file_get_contents(SB_PATH . "/resources/config-source.php");
        $raw = str_replace(
            ["[url]", "[name]", "[user]", "[password]", "[host]", "[port]"],
            [
                $details["url"],
                $database["name"],
                $database["user"],
                $database["password"],
                $database["host"],
                isset($details["db-port"]) && $details["db-port"][0]
                    ? $database["port"]
                    : "",
            ],
            $raw
        );

        sb_file(SB_PATH . "/config.php", $raw);

        // Return
        sb_get("admin.php?site=" . $details["url"]);
        foreach ($db_respones as $key => $value) {
            if ($value !== true) {
                $success .=
                    $key . ": " . ($value === false ? "false" : $value) . ",";
            }
        }
        if ($success == "") {
            return true;
        } else {
            return substr($success, 0, -1);
        }
    } else {
        return $connection_check;
    }
}


function sb_write_config_extra($content)
{
    $raw = file_get_contents(SB_PATH . "/config.php");
    sb_file(
        SB_PATH . "/config.php",
        str_replace("?>", $content . PHP_EOL . PHP_EOL . "?>", $raw)
    );
}

function sb_upload_path($url = false, $date = false)
{
    $uploadPath = ($url ? STMBX_URL . "/" : SB_PATH . "/") . "uploads" . ($date ? "/" . date("d-m-y") : "");

    // Create the uploads directory if it doesn't exist
    if (!file_exists($uploadPath) && !is_dir($uploadPath)) {
        mkdir($uploadPath, 0777, true); // Create recursively with full permissions
    }

    return $uploadPath;
}


function sb_dir_name()
{
    return substr(STMBX_URL, strrpos(STMBX_URL, "/") + 1);
}


/*
 * ----------------------------------------------------------
 * PUSHER
 * ----------------------------------------------------------
 *
 * 1. Send a Push notification.
 * 2. Trigger a event on a channel
 * 3. Get all online users including admins and agents
 * 4. Check if there is at least one agent online
 * 5. Check if pusher is active
 * 6. Initialize the Pusher PHP SDK
 *
 */

function sb_push_notification(
    $title = "",
    $message = "",
    $icon = "",
    $interest = false,
    $conversation_id = false,
    $user_id = false,
    $attachments = false
) {
    $recipient_agent = false;
    if (
        $interest == "agents" ||
        (is_string($interest) && strpos($interest, "department-") !== false)
    ) {
        $agents = sb_db_get(
            'SELECT id FROM sb_users WHERE (user_type = "admin" OR user_type = "agent") AND ' .
                ($interest == "agents"
                    ? 'department IS NULL OR department = ""'
                    : " department = " . substr($interest, 11)),
            false
        );
        $interest = [];
        for ($i = 0; $i < count($agents); $i++) {
            array_push($interest, $agents[$i]["id"]);
        }
        $recipient_agent = true;
    } elseif (is_numeric($interest) || is_array($interest)) {
        $agents_ids = sb_get_agents_ids();
        $is_user = !sb_is_agent();
        if (is_numeric($interest)) {
            if (!in_array(intval($interest), $agents_ids)) {
                if ($is_user && empty($GLOBALS["SB_FORCE_ADMIN"])) {
                    return new SBError(
                        "security-error",
                        "sb_push_notification"
                    );
                }
            } else {
                $recipient_agent = true;
            }
        } else {
            for ($i = 0; $i < count($interest); $i++) {
                if (!in_array(intval($interest[$i]), $agents_ids)) {
                    if ($is_user && empty($GLOBALS["SB_FORCE_ADMIN"])) {
                        return new SBError(
                            "security-error",
                            "sb_push_notification"
                        );
                    }
                } else {
                    $recipient_agent = true;
                }
            }
        }
    } elseif ($interest == "all-agents") {
        $interest == "agents";
    }
    if (empty($icon) || strpos($icon, "user.svg")) {
        $icon = sb_get_setting("notifications-icon", STMBX_URL . "/media/icon.png");
    }
    if (sb_is_agent() && !$recipient_agent) {
        $link = $conversation_id
            ? sb_isset(
                sb_db_get(
                    "SELECT B.value FROM sb_conversations A, sb_users_data B WHERE A.id = " .
                        sb_db_escape($conversation_id, true) .
                        ' AND A.user_id = B.user_id AND B.slug = "current_url" LIMIT 1'
                ),
                "value",
                ""
            )
            : false;
    } else {
        $link = STMBX_URL . "/admin.php";
    }
    $image =
        $attachments &&
        count($attachments) &&
        in_array(pathinfo($attachments[0][1], PATHINFO_EXTENSION), [
            "jpeg",
            "jpg",
            "png",
            "gif",
        ])
        ? $attachments[0][1]
        : false;
    $instance_ID = sb_get_multi_setting(
        "push-notifications",
        "push-notifications-id"
    );
    $query =
        ',"web":{"notification":{"title":"' .
        str_replace('"', "", $title) .
        '","body":"' .
        str_replace(
            '"',
            "",
            sb_clear_text_formatting(trim(preg_replace("/\s+/", " ", $message)))
        ) .
        '","icon":"' .
        $icon .
        '"' .
        ($link ? ',"deep_link":"' . $link . '"' : "") .
        ',"hide_notification_if_site_has_focus":true}, "data": {"conversation_id":"' .
        $conversation_id .
        '","user_id":"' .
        ($user_id ? $user_id : sb_get_active_user_ID()) .
        '","image":"' .
        ($image ? $image : "") .
        '"}}}';
    $url =
        "https://" .
        $instance_ID .
        ".pushnotifications.pusher.com/publish_api/v1/instances/" .
        $instance_ID .
        "/publishes";
    $header = [
        "Content-Type: application/json",
        "Authorization: Bearer " .
            sb_get_multi_setting("push-notifications", "push-notifications-key"),
    ];
    if (empty($interest)) {
        return false;
    }
    if (is_array($interest) && count($interest) > 100) {
        $interests = [];
        $index = 0;
        $count = count($interest);
        for ($i = 0; $i < $count; $i++) {
            array_push($interests, $interest[$i]);
            $index++;
            if ($index == 100 || $i == $count - 1) {
                $response = sb_curl(
                    $url,
                    '{"interests":' . json_encode($interests) . $query,
                    $header
                );
                $interests = [];
                $index = 0;
            }
        }
    } else {
        $response = sb_curl(
            $url,
            '{"interests":' .
                (is_array($interest)
                    ? json_encode($interest)
                    : '["' . str_replace(" ", "", $interest) . '"]') .
                $query,
            $header
        );
    }
    if (isset($response["error"])) {
        return trigger_error($response["description"]);
    } else {
        return $response;
    }
}

function sb_pusher_trigger($channel, $event, $data = [])
{
    $pusher = sb_pusher_init();
    $user_id = sb_get_active_user_ID();
    $data["user_id"] = $user_id;
    $security = false;
    $count = is_array($channel) ? count($channel) : false;
    switch ($event) {
        case "set-agent-status":
        case "agent-active-conversation-changed":
        case "add-user-presence":
        case "init":
        case "new-message":
        case "new-conversation":
        case "client-typing":
        case "typing":
            $security = sb_is_agent() || $channel == "private-user-" . $user_id;
            break;
        case "update-conversations":
            if ($user_id) {
                $security = true;
            }
            break;
    }

    if ($security) {
        if ($count > 100) {
            $channels = [];
            $index = 0;
            for ($i = 0; $i < $count; $i++) {
                array_push($channels, $channel[$i]);
                $index++;
                if ($index == 100 || $i == $count - 1) {
                    $response = $pusher->trigger($channels, $event, $data);
                    $channels = [];
                    $index = 0;
                }
            }
            return $response;
        } else {
            return $pusher->trigger($channel, $event, $data);
        }
    }
    return new SBError("pusher-security-error", "sb_pusher_trigger");
}

function sb_pusher_get_online_users()
{
    $index = 1;
    $pusher = sb_pusher_init();
    $continue = true;
    $users = [];
    $account_id = ""; // Removed the condition related to sb_is_cloud() and sb_cloud_account()
    while ($continue) {
        $channel = $pusher->get_users_info("presence-" . $index . $account_id);
        if (!empty($channel)) {
            $channel = $channel->users;
            $users = array_merge($users, $channel);
            if (count($channel) > 98) {
                $continue = true;
                $index++;
            } else {
                $continue = false;
            }
        } else {
            $continue = false;
        }
    }
    return $users;
}

function sb_pusher_agents_online()
{
    $agents_id = sb_get_agents_ids();
    $users = sb_pusher_get_online_users();
    for ($i = 0; $i < count($users); $i++) {
        if (in_array($users[$i]->id, $agents_id)) {
            return true;
        }
    }
    return false;
}


function sb_pusher_active()
{
    return sb_get_multi_setting("pusher", "pusher-active");
}

function sb_pusher_init()
{
    require_once SB_PATH . "/vendor/pusher/autoload.php";
    $settings = sb_get_setting("pusher");
    return new Pusher\Pusher(
        $settings["pusher-key"],
        $settings["pusher-secret"],
        $settings["pusher-id"],
        ["cluster" => $settings["pusher-cluster"]]
    );
}

/*
 * -----------------------------------------------------------
 * UTILITY
 * -----------------------------------------------------------
 *
 * 1. Check if a value and key of an array exists and is not empty and return it
 * 2. Check if a number and key of an array exists and is not empty and return it
 * 3. Check if a constant exists
 * 4. Encrypt a string or decrypt an encrypted string
 * 5. Convert a string to a slug or a slug to a string
 * 6. Send a curl request
 * 7. Return the content of a URL as a string
 * 8. Return the content of a URL as a string via GET
 * 9. Create a CSV file from an array
 * 10. Create a new file containing the given content and save it in the destination path.
 * 11. Delete a file
 * 12. Debug function
 * 13. Convert a JSON string to an array
 *
 */

function sb_isset($array, $key, $default = false)
{
    if (sb_is_error($array) || sb_is_validation_error($array)) {
        return $array;
    }
    return !empty($array) && isset($array[$key]) && $array[$key] !== ""
        ? $array[$key]
        : $default;
}

function sb_isset_num($value)
{
    return $value != -1 &&
        $value &&
        !is_null($value) &&
        !is_bool($value) &&
        is_numeric($value);
}

function sb_defined($name, $default = -1)
{
    return defined($name) ? constant($name) : $default;
}




// function sb_encryption($string, $encrypt = true)
// {
//     $output = false;
//     $encrypt_method = "AES-256-CBC";
//     $secret_key = defined("SB_CLOUD_KEY")
//         ? SB_CLOUD_KEY
//         : sb_get_setting("custom", "qwerty");
//     $key = hash("sha256", $secret_key);
//     $iv = substr(hash("sha256", "qwerty_iv"), 0, 16);
//     if ($encrypt) {
//         $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
//         $output = base64_encode($output);
//         if (substr($output, -1) == "=") {
//             $output = substr($output, 0, -1);
//         }
//     } else {
//         $output = openssl_decrypt(
//             base64_decode($string),
//             $encrypt_method,
//             $key,
//             0,
//             $iv
//         );
//         if ($output === false && $secret_key != "qwerty") {
//             $output = openssl_decrypt(
//                 base64_decode($string),
//                 $encrypt_method,
//                 hash("sha256", "qwerty"),
//                 0,
//                 $iv
//             );
//         }
//     }
//     return $output;
// }


function sb_encryption($string, $encrypt = true)
{
    $output = false;
    $encrypt_method = "AES-256-CBC";
    $token = defined("TOKEN") ? TOKEN : "";

    // Check if TOKEN is a valid future date
    if (strtotime($token) > strtotime(date('Y-m-d'))) {
        $secret_key = $token;
    } else {
        $secret_key = "";
    }

    // Check if the secret key is empty
    if (empty($secret_key)) {
        return false; // Return false if the secret key is empty
    }

    $key = hash("sha256", $secret_key);
    $iv = substr(hash("sha256", "iv"), 0, 16);

    if ($encrypt) {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
        if (substr($output, -1) == "=") {
            $output = substr($output, 0, -1);
        }
    } else {
        $output = openssl_decrypt(
            base64_decode($string),
            $encrypt_method,
            $key,
            0,
            $iv
        );
    }

    return $output;
}






function sb_string_slug($string, $action = "slug")
{
    $string = trim($string);
    if ($action == "slug") {
        return strtolower(
            str_replace([" ", '\'', '"'], ["-", "", ""], $string)
        );
    } elseif ($action == "string") {
        return ucfirst(strtolower(str_replace(["-", "_"], " ", $string)));
    }
    return $string;
}

function sb_curl($url, $post_fields = "", $header = [], $type = "POST")
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, "SB");

    switch ($type) {
        case "DELETE":
        case "PUT":
        case "PATCH":
        case "POST":
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                is_string($post_fields)
                    ? $post_fields
                    : http_build_query($post_fields)
            );
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 7);
            if ($type != "POST") {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
            }
            break;
        case "GET":
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
            curl_setopt($ch, CURLOPT_TIMEOUT, 70);
            curl_setopt($ch, CURLOPT_HEADER, false);
            break;
        case "DOWNLOAD":
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
            curl_setopt($ch, CURLOPT_TIMEOUT, 70);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            break;
        case "FILE":
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
            curl_setopt($ch, CURLOPT_TIMEOUT, 400);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);


            $path = sb_upload_path(false, true);

            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }
            if (strpos($url, "?")) {
                $url = substr($url, 0, strpos($url, "?"));
            }

            $basename = htmlspecialchars(
                str_replace(
                    [
                        "javascript:",
                        "onclick=",
                        "onerror=",
                        "<script",
                        "</script",
                    ],
                    "",
                    basename($url)
                ),
                ENT_NOQUOTES | ENT_SUBSTITUTE,
                "utf-8"
            );
            $basename = basename($url);

            break;
        case "UPLOAD":
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
            curl_setopt($ch, CURLOPT_TIMEOUT, 400);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
            $header = array_merge($header, [
                "Content-Type: multipart/form-data",
            ]);
            break;
    }
    if (!empty($header)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }
    $response = curl_exec($ch);
    if ($type == "FILE") {
        $file = fopen($path . "/" . $basename, "w");
        fwrite($file, $response);
        fclose($file);
    }
    if (curl_errno($ch) > 0) {
        $error = curl_error($ch);
        curl_close($ch);
        return $error;
    }
    curl_close($ch);

    switch ($type) {
        case "UPLOAD":
        case "PATCH":
        case "POST":
            $response_json = json_decode($response, true);
            return JSON_ERROR_NONE !== json_last_error()
                ? $response
                : $response_json;
        case "FILE":
            return sb_upload_path(true) .
                "/" .
                date("d-m-y") .
                "/" .
                basename($url);
    }
    return $response;
}

function sb_download($url)
{
    return sb_curl($url, "", "", "DOWNLOAD");
}

function sb_download_file($url, $file_name = false, $mime = false, $header = [])
{
    $url = sb_curl($url, "", $header, "FILE");
    $allowed_extensions = [
        "webp",
        "psd",
        "ai",
        "jpg",
        "jpeg",
        "png",
        "gif",
        "pdf",
        "rtf",
        "doc",
        "docx",
        "key",
        "ppt",
        "xls",
        "xlsx",
        "mp3",
        "m4a",
        "ogg",
        "oga",
        "wav",
        "mp4",

    ];
    $extension = pathinfo(
        basename($file_name ? $file_name : $url),
        PATHINFO_EXTENSION
    );
    if ($extension && !in_array($extension, $allowed_extensions)) {
        return "extension-not-allowed";
    }
    if ($file_name && !sb_is_error($url) && !empty($url)) {
        $date = date("d-m-y");
        $path = sb_upload_path() . "/" . $date;
        if ($mime) {
            $mime_types = [
                ["image/gif", "gif"],
                ["image/jpeg", "jpg"],
                ["video/mpeg", "mp3"],
                ["application/pdf", "pdf"],
                ["image/x-png", "png"],
                ["application/rtf", "rtf"],
                ["text/plain", "txt"],
                ["video/mp4", "mp4"],
                ["audio/mp4", "mp4"],
                ["audio/mpeg", "mp3"],
            ];
            $mime = mime_content_type($path . "/" . basename($url));

            for ($i = 0; $i < count($mime_types); $i++) {
                if ($mime == $mime_types[$i][0]) {
                    if ($extension) {
                        $file_name = str_replace('.' . $extension, '', $file_name);
                    }
                    $file_name .= "." . $mime_types[$i][1];
                    break;
                }
            }
        }


        rename($path . "/" . basename($url), $path . "/" . $file_name);
        $url = sb_upload_path(true) . "/" . $date . "/" . $file_name;
    }
    return $url;
}

function sb_get($url, $json = false)
{
    $response = sb_curl($url, "", "", "GET");
    return $json ? json_decode($response, true) : $response;
}

function sb_csv($items, $header, $filename)
{
    $path = "/" . $filename . time() . ".csv";
    $file = fopen(sb_upload_path() . $path, "w");
    $googleHeader = [
        "Name",
        "Given Name",
        "Additional Name",
        "Family Name",
        "Yomi Name",
        "Given Name Yomi",
        "Additional Name Yom",
        "Family Name Yomi",
        "Name Prefix",
        "Name Suffix",
        "Initials",
        "Nickname",
        "Short Name",
        "Maiden Name",
        "Birthday",
        "Gender",
        "Location",
        "Billing Information",
        "Directory Server",
        "Mileage",
        "Occupation",
        "Hobby",
        "Sensitivity",
        "Priority",
        "Subject",
        "Notes",
        "Language",
        "Photo",
        "Group",
        "E-mail 1 - Type",
        "E-mail 1 - Value",
        "Phone 1 - Type",
        "Phone 1 - Value",
    ];
    if ($header) {
        fputcsv($file, $googleHeader);
    }
    for ($i = 0; $i < count($items); $i++) {
        $rows = [];
        for ($j = 0; $j < count($googleHeader); $j++) {
            $gheader = $googleHeader[$j];
            $key = isset($header[$gheader]) ? $header[$gheader] : "";
            $rows[$gheader] = isset($items[$i][$key]) ? $items[$i][$key] : "";
        }
        fputcsv($file, $rows);
    }
    fclose($file);
    $url = sb_upload_path(true) . $path;
    $csv = file_get_contents($url);
    $base64 = base64_encode($csv);
    sb_file_delete($path);
    return json_encode(["file" => $base64, "filename" => "users.csv"]);
}
function sb_csv_new($items, $header, $filename)
{
    $path = "/" . $filename . time() . ".csv";
    $file = fopen(sb_upload_path() . $path, "w");
    if ($header) {
        fputcsv($file, $header);
    }
    for ($i = 0; $i < count($items); $i++) {
        fputcsv($file, $items[$i]);
    }
    fclose($file);
    return sb_upload_path(true) . $path;
}

function sb_file($path, $content)
{
    try {
        $file = fopen($path, "w");
        fwrite($file, $content);
        fclose($file);
        return true;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

function sb_file_delete($path)
{
    $path = SB_PATH . "/uploads/" . basename($path);
    return unlink($path);
}

function sb_debug($value)
{
    $value = is_string($value) ? $value : json_encode($value);
    if (file_exists("debug.txt")) {
        $value = file_get_contents("debug.txt") . PHP_EOL . $value;
    }
    sb_file("debug.txt", $value);
}

function sb_json_array($json, $default = [])
{
    if (is_string($json)) {
        $json = json_decode($json, true);
        return $json === false || $json === null ? $default : $json;
    } else {
        return $json;
    }
}

/*
 * -----------------------------------------------------------
 * MISCELLANEOUS
 * -----------------------------------------------------------
 *
 * 1. Return the departments array
 * 2. Echo the departments list
 * 3. Check if the current time is within the office hours
 * 4. Generate the CSS with values setted in the settings area
 * 5. Delete visitors older than 24h, messages in trash older than 30 days. Archive conversation older than 24h with status code equal to 4 (pending user reply).
 * 6. Check the system for requirements and issues
 * 7. Countries list
 * 8. Langauges list
 * 9. Phone codes list
 * 10. Chat editor
 * 11. Return the position of the least occurence on left searching from right to left
 * 12. Verification cookie
 * 13. On Routin Cloud close
 * 14. Auto messages server-side execution
 * 15. Messaging platforms function
 * 16. Send a messaging platform message
 * 17. Check if dialogflow active
 * 18. Logs
 * 19. Webhook
 * 20. Add a cron job
 * 21. Run cron jobs
 * 22. Text message function
 *
 */


function sb_get_departments()
{
    $items = sb_get_setting("departments");
    $count = is_array($items) ? count($items) : 0;
    $departments = [];
    for ($i = 0; $i < $count; $i++) {
        $departments[$items[$i]["department-id"]] = [
            "name" => sb_($items[$i]["department-name"]),
            "color" => $items[$i]["department-color"],
            "image" => sb_isset($items[$i], "department-image", ""),
        ];
    }
    return $departments;
}

function sb_departments($type)
{
    $items = sb_get_setting("departments");
    $count = is_array($items) ? count($items) : 0;
    if ($count) {
        switch ($type) {
            case "select":
                $code =
                    '<div id="department" data-type="select" class="sb-input sb-input-select"><span>' .
                    sb_("Department") .
                    '</span><select><option value=""></option>';
                for ($i = 0; $i < $count; $i++) {
                    $code .=
                        '<option value="' .
                        $items[$i]["department-id"] .
                        '">' .
                        ucfirst(sb_($items[$i]["department-name"])) .
                        "</option>";
                }
                echo $code . "</select></div>";
                break;
            case "custom-select":
                $code =
                    '<div class="sb-inline sb-inline-departments"><i class="bi-building" style="padding:0px 5px"></i> <h3>' .
                    sb_("Department") .
                    '</h3><div id="conversation-department" class="sb-select hover-selector sb-select-colors"><p>' .
                    sb_("None") .
                    '</p><ul class="sb-responsive-absolute-position"><li data-id="" data-value="">' .
                    sb_("None") .
                    "</li>";
                for ($i = 0; $i < $count; $i++) {
                    $id = $items[$i]["department-id"];
                    $department_name = mb_substr(ucfirst(sb_($items[$i]["department-name"])), 0, 10);
                    if (mb_strlen($items[$i]["department-name"]) > 10) {
                        $department_name .= '...';
                    }
                    $code .= '<li data-id="' .
                        $id .
                        '" data-value="' .
                        sb_isset($items[$i], "department-color", $id) .
                        '">' . '' .
                        $department_name .
                        "</li>";
                }


                echo $code . "</ul></div></div>";
                break;
            case "dashboard":
                $settings = sb_get_setting("departments-settings");
                if ($settings) {
                    $is_image =
                        sb_isset($settings, "departments-images") &&
                        sb_isset($items[0], "department-image");
                    $code =
                        '<div class="sb-dashboard-departments"><div class="sb-title">' .
                        sb_(
                            sb_isset(
                                $settings,
                                "departments-title",
                                "Departments"
                            )
                        ) .
                        '</div><div class="sb-departments-list">';
                    for ($i = 0; $i < $count; $i++) {
                        $code .=
                            '<div data-id="' .
                            $items[$i]["department-id"] .
                            '">' .
                            ($is_image
                                ? '<img src="' .
                                $items[$i]["department-image"] .
                                '">'
                                : '<div data-color="' .
                                sb_isset($items[$i], "department-color") .
                                '"></div>') .
                            "<span>" .
                            sb_($items[$i]["department-name"]) .
                            "</span></div>";
                    }
                    echo $code . "</div></div>";
                    break;
                }
        }
    }
}

function sb_office_hours()
{
    $settings = sb_get_settings();
    $timetable = sb_isset($settings, "timetable", [[]])[0];
    $now = time();
    $offset = intval(sb_get_setting("timetable-utc", 0));
    $offset_2 = $now - $offset * 3600;
    $today = strtolower(gmdate("l", $offset_2));
    $today_array = explode("-", gmdate("m-d-y", $offset_2));
    $today_array = [
        intval($today_array[0]),
        intval($today_array[1]),
        intval($today_array[2]),
    ];

    $status = false;
    if (isset($timetable[$today]) && !empty($timetable[$today][0][0])) {
        for ($i = 0; $i < 3; $i += 2) {
            if (
                !empty($timetable[$today][$i][0]) &&
                $timetable[$today][$i][0] != "closed"
            ) {
                $start = explode(":", $timetable[$today][$i][0]);
                $end = explode(":", $timetable[$today][$i + 1][0]);
                $office_hours_start = gmmktime(
                    intval($start[0]) + $offset,
                    intval($start[1]),
                    0,
                    $today_array[0],
                    $today_array[1],
                    $today_array[2]
                );
                $office_hours_end = gmmktime(
                    intval($end[0]) + $offset,
                    intval($end[1]),
                    0,
                    $today_array[0],
                    $today_array[1],
                    $today_array[2]
                );
                if ($now >= $office_hours_start && $now <= $office_hours_end) {
                    $status = true;
                }
            }
        }
    }
    
    // Debug log to track office hours status
    $log_message = "Current time: " . gmdate("Y-m-d H:i:s", $now) .
        ", Today: " . $today .
        ", Office Hours Status: " . ($status ? "Open" : "Closed");
    // file_put_contents('log.txt', $log_message . PHP_EOL, FILE_APPEND);

    return $status;
}

function sb_css(
    $color_1 = false,
    $return = false
) {
    $css = "";
    $color_1 = $color_1 ? $color_1 : sb_get_setting("color-1");
    $chat_button_offset_top = sb_get_multi_setting(
        "chat-button-offset",
        "chat-button-offset-top"
    );
    $chat_button_offset_bottom = sb_get_multi_setting(
        "chat-button-offset",
        "chat-button-offset-bottom"
    );
    $chat_button_offset_right = sb_get_multi_setting(
        "chat-button-offset",
        "chat-button-offset-right"
    );
    $chat_button_offset_left = sb_get_multi_setting(
        "chat-button-offset",
        "chat-button-offset-left"
    );
    $chat_button_offset_left_mobile = sb_get_multi_setting(
        "chat-button-offset",
        "chat-button-offset-mobile"
    );
    $chat_button_offset_left_mobile =
        $chat_button_offset_left_mobile == "desktop"
        ? ["@media (min-width: 768px) {", "}"]
        : ($chat_button_offset_left_mobile == "mobile"
            ? ["@media (max-width: 768px) {", "}"]
            : ["", ""]);
    if ($color_1) {
        $css .=
            '.sb-chat-btn, .sb-label-date-top.sb-active { background-color: ' .
            $color_1 .
            "; }";
    }

    if ($chat_button_offset_top) {
        $css .=
            $chat_button_offset_left_mobile[0] .
            ".sb-chat-btn { top: " .
            $chat_button_offset_top .
            "px; }" .
            $chat_button_offset_left_mobile[1];
    }
    if ($chat_button_offset_bottom) {
        $css .=
            $chat_button_offset_left_mobile[0] .
            ".sb-chat-btn { bottom: " .
            $chat_button_offset_bottom .
            "px; }" .
            $chat_button_offset_left_mobile[1];
    }
    if ($chat_button_offset_right) {
        $css .=
            $chat_button_offset_left_mobile[0] .
            ".sb-chat-btn { right: " .
            $chat_button_offset_right .
            "px; }" .
            $chat_button_offset_left_mobile[1];
    }
    if ($chat_button_offset_left) {
        $css .=
            $chat_button_offset_left_mobile[0] .
            ".sb-chat-btn { left: " .
            $chat_button_offset_left .
            "px; }" .
            $chat_button_offset_left_mobile[1];
    }
    if ($return) {
        return $css;
    }
    if ($css) {
        echo "<style>" . $css . "</style>";
    }
    return false;
}

function sb_clean_data()
{
    $time_24h = gmdate("Y-m-d H:i:s", time() - 86400);
    $time_30d = gmdate("Y-m-d H:i:s", time() - 2592000);
    $ids = sb_db_get(
        'SELECT id FROM sb_conversations WHERE status_code = 4 AND creation_time < "' .
            $time_30d .
            '"',
        false
    );
    sb_db_query(
        'DELETE FROM sb_users WHERE user_type = "visitor" AND creation_time < "' .
            $time_24h .
            '"'
    );
    for ($i = 0; $i < count($ids); $i++) {
        sb_delete_attachments($ids[$i]["id"]);
    }
    sb_db_query(
        'DELETE FROM sb_conversations WHERE status_code = 4 AND creation_time < "' .
            $time_30d .
            '"'
    );
    if (sb_get_setting("admin-auto-archive")) {
        sb_db_query(
            'UPDATE sb_conversations SET status_code = 3 WHERE (status_code = 1 OR status_code = 0) AND id IN (SELECT conversation_id FROM sb_messages WHERE id IN (SELECT max(id) FROM sb_messages GROUP BY conversation_id) AND creation_time < "' .
                $time_24h .
                '")'
        );
    }
    return true;
}

function sb_system_requirements()
{
    $checks = [];

    // PHP version
    $checks["php-version"] = version_compare(PHP_VERSION, "7.2.0") >= 0;

    // ZipArchive
    $checks["zip-archive"] = class_exists("ZipArchive");

    // File permissions
    $permissions = [
        ["plugin", SB_PATH],
        ["uploads", sb_upload_path()],
        ["apps", SB_PATH . "/apps"],
        ["languages", SB_PATH . "/resources/languages"],
    ];
    for ($i = 0; $i < count($permissions); $i++) {
        $path = $permissions[$i][1] . "/sb-permissions-check.txt";
        sb_file($path, "permissions-check");
        $checks[$permissions[$i][0] . "-folder"] =
            file_exists($path) &&
            file_get_contents($path) == "permissions-check";
        if (file_exists($path)) {
            unlink($path);
        }
    }

    // AJAX file
    $checks["ajax"] =
        function_exists("curl_init") &&
        sb_download(STMBX_URL . "/include/ajax.php") == "true";

    // MySQL UTF8MB4 support
    $checks["UTF8mb4"] = !sb_is_error(sb_db_query("SET NAMES UTF8mb4"));

    return $checks;
}

function sb_select_countries()
{
    $code = '<select><option value=""></option>';
    $countries = array_keys(
        json_decode(
            file_get_contents(SB_PATH . "/resources/json/countries.json"),
            true
        )
    );
    for ($i = 0; $i < count($countries); $i++) {
        $code .=
            '<option value="' .
            $countries[$i] .
            '">' .
            sb_($countries[$i]) .
            "</option>";
    }
    return $code . "</select>";
}

function sb_select_languages()
{
    $code = '<select><option value=""></option>';
    $languages = json_decode(
        file_get_contents(SB_PATH . "/resources/languages/language-codes.json"),
        true
    );
    foreach ($languages as $key => $value) {
        $code .= '<option value="' . $key . '">' . sb_($value) . "</option>";
    }
    return $code . "</select>";
}

function sb_select_phone()
{
    $single = sb_get_setting("phone-code");
    if ($single) {
        return $single;
    } else {
        $code = '<select style="color:#48ffb3;"><option value=""></option>';
        $phones = array_values(
            json_decode(
                file_get_contents(SB_PATH . "/resources/json/phone.json"),
                true
            )
        );
        for ($i = 0; $i < count($phones); $i++) {
            $code .=
                '<option value="+' .
                $phones[$i] .
                '">+' .
                $phones[$i] .
                "</option>";
        }
        return $code . "</select>";
    }
}

function sb_component_editor($admin = false)
{
?>

    <div class="sb-editor" id="controls">

        <?php if ($admin) {
            echo '<div class="sb-agent-label"></div>';
        } ?>

        <div class="sb-bar-icons sb-hide">
            <div id="set-status">
                <i class="bi bi-kanban-fill"></i>
                <span><?php sb_e("Status"); ?></span>
            </div>
            <div id="load-saved-replies">
                <i class="bi bi-envelope-fill"></i>
                <span><?php sb_e("Replies"); ?></span>
            </div>
            <?php if ($admin || !sb_get_setting("disable-uploads")) { ?>
                <div id="upload-files">
                    <i class="bi bi-folder-fill"></i>
                    <span><?php sb_e("Uploads"); ?></span>
                </div>
            <?php } ?>


            <div id="send-rating-button">
                <i class="bi bi-stoplights-fill"></i>
                <span><?php sb_e("Feedback"); ?></span>
            </div>
            <div id="set-agent-name">
                <input type="checkbox" id="agentNameToggle" class="switch-toggle">
                <span><?php sb_e("Name"); ?></span>
            </div>
            <div class="api-whatsapp-button sb-hide" id="open-modal-button">
                <i class="bi bi-wind wind-whatsapp-color"></i>
                <span><?php sb_e("Templates"); ?></span>
            </div>

        </div>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const agentNameToggle = document.getElementById('agentNameToggle');
                const savedState = localStorage.getItem('agentNameToggle') === 'true';
                agentNameToggle.checked = savedState;
                agentNameToggle.addEventListener('change', () => {
                    localStorage.setItem('agentNameToggle', agentNameToggle.checked);
                });
            });
            document.addEventListener("DOMContentLoaded", function() {
                var toggleButton = document.querySelector(".to-make-invisible-sb-text-area");
                var recordButton = document.querySelector("#stopButton");
                var textAreaDiv = document.querySelector(".sb-textarea");
                var emojiGrinDiv = document.querySelector(".bi-emoji-grin");

                function toggleVisibility() {
                    if (textAreaDiv.classList.contains("sb-invisible")) {
                        textAreaDiv.classList.remove("sb-invisible");
                        emojiGrinDiv.classList.remove("sb-invisible");
                        textAreaDiv.style.visibility = "visible";
                        emojiGrinDiv.style.visibility = "visible";
                    } else {
                        textAreaDiv.classList.add("sb-invisible");
                        emojiGrinDiv.classList.add("sb-invisible");
                        setTimeout(function() {
                            textAreaDiv.style.visibility = "hidden";
                            emojiGrinDiv.style.visibility = "hidden";
                        }, 500);
                    }
                }
                toggleButton.addEventListener("click", toggleVisibility);
                recordButton.addEventListener("click", toggleVisibility);
            });
        </script>


        <div class="sb-show-menu-bar flex-align-center-relative">
            <div class="menu-plus bi-plus-lg"></div>
            <div style="min-height: 35px;" class="sb-textarea">
                <?php
                $source = "wa";
                $disabled = ($source !== "wa") ? "visibility: hidden;" : "";
                ?>
                <textarea placeholder="<?php sb_e("Write a message..."); ?>" autofocus <?php echo $disabled; ?>></textarea>
            </div>
            <div class="sb-bar sb-space-between">
                <div id="recordButton" class="bi-mic-fill to-make-invisible-sb-text-area"></div>
                <div id="stopButton" disabled class="bi-record-fill"></div>
                <div class="bi-arrow-up-circle-fill sb-submit"></div>
                <img class="sb-loader" src="<?php echo STMBX_URL; ?>/media/loading.svg" alt="loading..." />
            </div>
        </div>
        <script type="text/javascript">
            // Initialize the emoji picker
            new lc_emoji_picker('textarea');
        </script>

        <?php if ($admin) { ?>
            <div id="CstBtn" class="cstdown-content sb-popup sb-status-chat" style="height: auto;">

                <?php
                $clientStatus = array(
                    "Abierto",
                    "Presupuesto",
                    "Consulta",
                    "Contactado",
                    "Visitado",
                    "Calificado",
                    "Confirmado",
                    "Pendiente",
                    "Resuelto",
                    "Pagado",
                    "VIP",
                    "Descartado",
                    "NA",
                );
                foreach ($clientStatus as $label) {
                ?>
                    <a id="<?= $label ?>" class="sb-input-setting cst-a">
                        <i class="<?= "cst-i bi-kanban-fill tags-" . $label ?>"></i>&nbsp;<?= $label ?>
                    </a>
                <?php } ?>


            </div>

            <div class="sb-popup sb-replies">
                <div class="sb-header" style="justify-content: space-between;margin: -7px -2px -7px auto;">
                    <div style="line-height: 30px;font-size: var(--chat-text-size-1-1);white-space: pre;"><?php sb_e("Saved replies"); ?></div>
                    <div class="sb-search-btn">
                        <i class="sb-icon bi-search"></i>
                        <input type="text" autocomplete="false" placeholder="<?php sb_e(
                                                                                    "Search replies..."
                                                                                ); ?>" />
                    </div>
                </div>
                <div class="sb-replies-list sb-scroll-area">
                    <ul style="margin: 0px auto;" class="sb-loading"></ul>
                </div>
            </div>

            <div class="sb-popup sb-emoji">
                <div class="sb-header" style="justify-content: space-between;">
                    <div class="sb-select">
                        <p>
                            <?php sb_e("All"); ?>
                        </p>
                        <ul>
                            <li data-value="all" class="sb-active">
                                <?php sb_e("All"); ?>
                            </li>
                            <li data-value="Smileys">
                                <?php sb_e("Smileys & Emotions"); ?>
                            </li>
                            <li data-value="People">
                                <?php sb_e("People & Body"); ?>
                            </li>
                            <li data-value="Animals">
                                <?php sb_e("Animals & Nature"); ?>
                            </li>
                            <li data-value="Food">
                                <?php sb_e("Food & Drink"); ?>
                            </li>
                            <li data-value="Travel">
                                <?php sb_e("Travel & Places"); ?>
                            </li>
                            <li data-value="Activities">
                                <?php sb_e("Activities"); ?>
                            </li>
                            <li data-value="Objects">
                                <?php sb_e("Objects"); ?>
                            </li>
                            <li data-value="Symbols">
                                <?php sb_e("Symbols"); ?>
                            </li>
                        </ul>
                    </div>
                    <div class="sb-search-btn">
                        <i class="sb-icon bi-search"></i>
                        <input type="text" placeholder="<?php sb_e(
                                                            "Search emoji..."
                                                        ); ?>" />
                    </div>
                </div>
                <div class="sb-emoji-list">
                    <ul></ul>
                </div>
                <div class="sb-emoji-bar"></div>
            </div>
        <?php } ?>

        <form class="sb-upload-form-editor" action="#" method="post" enctype="multipart/form-data">
            <input type="file" name="files[]" class="sb-upload-files" multiple />
        </form>
        <div class="sb-attachments"> </div>

    </div>



<?php
}

function sb_strpos_reverse($string, $search, $offset)
{
    return strrpos(substr($string, 0, $offset), $search);
}

function sb_mb_strpos_reverse($string, $search, $offset)
{
    $index = mb_strrpos(mb_substr($string, 0, $offset), $search);
    return $index ? $index : $offset;
}

function sb_on_close()
{
    sb_set_agent_active_conversation(0);
}











function sb_execute_bot_message($name, $conversation_id, $last_user_message = false)
{
    $valid = false;
    $settings = false;
    $message = "";

    switch ($name) {
        case "offline":
            // Check if the welcome message is active
            if (sb_get_multi_setting("welcome-message", "welcome-active")) {
                // Set the message to empty if welcome message is active
                $message = "";
                break;
            }

            $settings = sb_get_setting("chat-timetable");
            $valid = $settings["chat-timetable-active"] && (!sb_office_hours() || (!$settings["chat-timetable-agents"] && !sb_agents_online()));
            $message = $valid ? sb_get_multi_setting("chat-timetable", "chat-timetable-msg") : "";
            break;
        case "welcome":
            if (!sb_get_multi_setting("welcome-message", "welcome-active")) {
                return false; // Skip if welcome-active is not active
            }

            // Check if office hours are disabled for welcome messages
            if (sb_get_multi_setting("welcome-message", "welcome-disable-office-hours") && !sb_office_hours()) {
                $message = ""; // Send an empty message if office hours are closed and it's configured to disable
            } else {
                // Optionally, you can uncomment the following line if you want to use it later
                // $flowData = sb_get_flow_data();
                // $welcomeMessage = isset($flowData['welcome_message'][0]['bot_reply'][0]['message']) ? $flowData['welcome_message'][0]['bot_reply'][0]['message'] : "";
                // $message = $welcomeMessage;
            }
            $valid = true;
            break;
        default:
            return false;
    }

    if ($conversation_id == "check" || empty($message)) {
        return false;
    }

    $message_id = sb_send_message(sb_get_bot_id(), $conversation_id, $message, [], -1, [$name . "_message" => true, "auto_reply" => 1, "preview" => $last_user_message ? $last_user_message : $message])["id"];

    return [
        "message" => $message,
        "attachments" => [],
        "id" => $message_id,
    ];
}


function sb_get_flow_data()
{
    $jsonFlow = sb_get_multi_setting("welcome-message", "json-flow");
    return json_decode($jsonFlow, true);
}



function sb_send_fallback_message($conversation_id)
{
    $flowData = sb_get_flow_data();
    $fallbackMessage = isset($flowData['fallback'][0]['bot_reply'][0]['message']) ? $flowData['fallback'][0]['bot_reply'][0]['message'] : "";
    $message_id = sb_send_message(sb_get_bot_id(), $conversation_id, $fallbackMessage, [], -1, ["fallback_message" => true])["id"];
    return [
        "id" => $message_id,
        "message" => $fallbackMessage,
    ];
}
 

function sb_find_reply_by_option($option, $flowData)
{
    foreach ($flowData['main_flow'] as $flow_name => $flows) {
        foreach ($flows as $flow) {
            if (isset($flow['keywords']) && is_array($flow['keywords'])) {
                foreach ($flow['keywords'] as $keyword) {
                    if ($option === strtolower($keyword)) {
                        return [
                            "option" => $keyword,
                            "reply" => isset($flow['bot_reply']) ? $flow['bot_reply'] : [],
                            "actions" => isset($flow['actions']) ? $flow['actions'] : [],
                            "next_flow" => isset($flow['next_flow']) ? $flow['next_flow'] : null,
                        ];
                    }
                }
            }
        }
    }
    return [];
}

function sb_option_process_reply($option, $conversation_id)
{
    $option = strtolower($option);
    $flowData = sb_get_flow_data();
    $reply = sb_find_reply_by_option($option, $flowData);

    if ($option === '/quit') {
        return sb_handle_quit_trigger($conversation_id);
    }

    $assigned_department = sb_get_assigned_department($conversation_id);
    if (!empty($assigned_department)) {
        return false;
    }

    $query = 'SELECT COUNT(*) AS count FROM sb_messages WHERE payload LIKE "{\"option_assigned%" AND creation_time > "' . gmdate("Y-m-d H:i:s", time() - 864000) . '" AND conversation_id = ' . sb_db_escape($conversation_id, true);
    if (sb_db_get($query)["count"] == 0) {
        if (!empty($reply)) {
            $response_ids = sb_send_bot_replies($reply["reply"], $conversation_id);

            if (isset($reply["actions"]) && !empty($reply["actions"])) {
                sb_process_actions($reply["actions"], $conversation_id);
            }

            if ($reply["next_flow"]) {
                sb_update_conversation_flow($conversation_id, $reply["next_flow"]);
            }

            return [
                "ids" => $response_ids,
                "messages" => $reply["reply"],
            ];
        } else {
            return sb_send_fallback_message($conversation_id);
        }
    }

    return false;
}

function sb_send_bot_replies($bot_replies, $conversation_id)
{
    $response_ids = [];
    foreach ($bot_replies as $bot_reply) {
        $delay = isset($bot_reply['delay']) ? (int)$bot_reply['delay'] : 0;

        if ($delay > 0) {
            usleep($delay * 1500); // Convert milliseconds to microseconds
        }

        $message = $bot_reply['message'];
        $response_id = sb_send_message(sb_get_bot_id(), $conversation_id, $message)["id"];
        sb_messaging_platforms_send_message($message, $conversation_id, $response_id);
        $response_ids[] = $response_id;
    }
    return $response_ids;
}


function sb_process_actions($actions, $conversation_id)
{
    foreach ($actions as $action) {
        if (isset($action["assign"]) && !empty($action["assign"])) {
            sb_update_conversation_department($conversation_id, $action["assign"], false);
        }

        if (isset($action["move"]) && !empty($action["move"])) {
            sb_move_conversation_flow($action, $conversation_id);
        }
    }
}

function sb_move_conversation_flow($action, $conversation_id)
{
    if (isset($action["move"]) && !empty($action["move"])) {
        $move_flow = $action["move"];
        if (isset($action["bot_reply"]) && is_array($action["bot_reply"])) {
            $move_reply = $action["bot_reply"];
            $move_message = $move_reply['message'];
            $move_delay = isset($move_reply['delay']) ? (int)$move_reply['delay'] : 0;

            $move_response_id = sb_send_message(sb_get_bot_id(), $conversation_id, $move_message)["id"];
            sb_messaging_platforms_send_message($move_message, $conversation_id, $move_response_id);

            if ($move_delay > 0) {
                usleep($move_delay * 1000);
            }
        }
        sb_update_conversation_flow($conversation_id, $move_flow);
    }
}

// ---- Database and Utility Functions ----

function sb_count_fallback_messages($conversation_id)
{
    $query = 'SELECT COUNT(*) AS count FROM sb_messages WHERE payload LIKE \'%"fallback_message"%\' AND conversation_id = ' . sb_db_escape($conversation_id, true);
    $result = sb_db_get($query)['count'];

    return $result;
}

function sb_get_assigned_department($conversation_id)
{
    $query = 'SELECT department FROM sb_conversations WHERE id = ' . sb_db_escape($conversation_id, true);
    $result = sb_db_get($query)['department'];

    return $result;
}

function sb_update_conversation_flow($conversation_id, $new_flow)
{
    $query = 'UPDATE sb_conversations SET current_flow = ' . sb_db_escape($new_flow, true) . ' WHERE id = ' . sb_db_escape($conversation_id, true);
    sb_db_query($query);
}

function sb_get_user_detail($conversation_id)
{
    $query = "SELECT user_id, agent_id, source FROM sb_conversations WHERE id = " . sb_db_escape($conversation_id, true);
    $result = sb_db_get($query);

    return $result;
}
function sb_handle_quit_trigger($conversation_id)
{
    // file_put_contents('file.txt', "Entering sb_handle_quit_trigger with conversation_id: " . $conversation_id . PHP_EOL, FILE_APPEND);

    $query = 'UPDATE sb_conversations SET agent_id = NULL WHERE id = ' . sb_db_escape($conversation_id, true);
    sb_db_query($query);

    $query = 'UPDATE sb_conversations SET department = NULL WHERE id = ' . sb_db_escape($conversation_id, true);
    sb_db_query($query);

    $query = 'UPDATE sb_conversations SET status_code = 4 WHERE id = ' . sb_db_escape($conversation_id, true);
    sb_db_query($query);

    $flowData = sb_get_flow_data();
    $welcomeMessage = isset($flowData['welcome_message'][0]['bot_reply'][0]['message']) ? $flowData['welcome_message'][0]['bot_reply'][0]['message'] : "Default welcome message";

    return [
        "message" => $welcomeMessage,
    ];
}


















function sb_messaging_platforms_functions(
    $conversation_id,
    $message,
    $attachments,
    $user,
    $source
) {
    $last_message = sb_db_get(
        'SELECT message FROM sb_messages WHERE message <> "" AND message <> "' .
            sb_db_escape($message) .
            '" AND conversation_id = ' .
            $conversation_id .
            " ORDER BY id DESC LIMIT 1"
    );
    $user_id = $user["id"];
    $conversation = sb_get_user_detail($conversation_id);
    $source_name = $source["source"];
    $bot_messages = true;
    $human_takeover = false;

    $message_id = false;
    $source["user_id"] = $user_id;

    // Rich messages
    if ($last_message) {
        $last_message = $last_message["message"];
        $shortcode = sb_get_shortcode($last_message);
        if ($shortcode) {
            switch ($shortcode["shortcode_name"]) {
                case "phone":
                case "email":
                    if (!in_array($source_name, ["em", "tm"])) {
                        $valid = false;
                        $is_email = $shortcode["shortcode_name"] == "email";
                        $filter = $is_email
                            ? ["@", "email"]
                            : (strpos($message, "+") !== false
                                ? ["+", "phone"]
                                : false);
                        if ($filter) {
                            $words = explode(" ", $message);
                            for ($i = 0; $i < count($words); $i++) {
                                if (strpos($words[$i], $filter[0]) !== false) {
                                    $value = trim($words[$i]);
                                    if (substr($value, -1) == ".") {
                                        $value = substr($value, 0, -1);
                                    }
                                    if (
                                        strlen($value) > 3 &&
                                        (($is_email && strpos($value, ".")) ||
                                            (!$is_email &&
                                                is_numeric(substr($value, 1))))
                                    ) {
                                        sb_update_user_value(
                                            $user_id,
                                            $filter[1],
                                            $value
                                        );
                                        if (!empty($shortcode["success"])) {
                                            if (
                                                $is_email &&
                                                !empty($shortcode["phone"]) &&
                                                $source_name != "wa" &&
                                                !sb_get_user_extra(
                                                    $user_id,
                                                    "phone"
                                                )
                                            ) {
                                                $message_new =
                                                    '[phone message="' .
                                                    sb_(
                                                        "Enter your phone number"
                                                    ) .
                                                    '" success="' .
                                                    sb_($shortcode["success"]) .
                                                    '"]';
                                            } else {
                                                $message_new = sb_(
                                                    sb_merge_fields(
                                                        $shortcode["success"]
                                                    )
                                                );
                                            }
                                            $message_id = sb_send_message(
                                                sb_get_bot_id(),
                                                $conversation_id,
                                                $message_new,
                                                [],
                                                -1,
                                                ["event" => "update-user"]
                                            )["id"];
                                            sb_messaging_platforms_send_message(
                                                $message_new,
                                                $source,
                                                $message_id
                                            );
                                        }
                                        $valid = true;
                                    }
                                }
                            }
                        }
                        if (
                            !$valid &&
                            !empty($shortcode["required-messaging-apps"])
                        ) {
                            $message_id = sb_send_message(
                                sb_get_bot_id(),
                                $conversation_id,
                                $last_message
                            )["id"];
                            sb_messaging_platforms_send_message(
                                $last_message,
                                $source,
                                $message_id
                            );
                        }
                        $skip_dialogflow = true;
                    } else {
                        $bot_messages = false;
                    }
                    break;
            }
        }
    }

    // Bot messages
    if ($bot_messages || $human_takeover) {
        $bot_messages = ["offline", "follow_up", "subscribe", "welcome"];
        for ($i = 0; $i < count($bot_messages); $i++) {
            $bot_message =
                $i == 0 || empty($user["email"])
                ? sb_execute_bot_message(
                    $bot_messages[$i],
                    $conversation_id,
                    $last_message
                )
                : false;

            // Handle welcome message
            if (
                empty($user["department"]) &&
                empty($conversation["agent_id"]) &&
                $i == 3
            ) {
                // Check if welcome-active is enabled
                $log_message = "Processing welcome message: welcome-active=" . (sb_get_multi_setting("welcome-message", "welcome-active") ? "true" : "false") .
                    ", welcome-disable-office-hours=" . (sb_get_multi_setting("welcome-message", "welcome-disable-office-hours") ? "true" : "false") .
                    ", office-hours=" . (sb_office_hours() ? "true" : "false");
                // file_put_contents('log.txt', $log_message . PHP_EOL, FILE_APPEND);

                if (
                    sb_get_multi_setting("welcome-message", "welcome-active") &&
                    (!sb_get_multi_setting(
                        "welcome-message",
                        "welcome-disable-office-hours"
                    ) ||
                        sb_office_hours())
                ) {
                    if (
                        sb_db_get(
                            'SELECT COUNT(*) AS `count` FROM sb_messages WHERE payload LIKE "{\"' .
                                $bot_messages[$i] .
                                '_option%" AND creation_time > "' .
                                gmdate("Y-m-d H:i:s", time() - 864000) .
                                '" AND conversation_id = ' .
                                sb_db_escape($conversation_id, true)
                        )["count"] == 0
                    ) {
                        $flowData = sb_get_flow_data();
                        $welcomeMessage = isset($flowData['welcome_message'][0]['bot_reply'][0]['message']) ? $flowData['welcome_message'][0]['bot_reply'][0]['message'] : "";
                        if (sb_office_hours()) {
                            $welcomeMessage = "";
                        }
                        $bot_message = [
                            "id" => sb_send_message(
                                sb_get_bot_id(),
                                $conversation_id,
                                $welcomeMessage,
                                $attachments,
                                -1,
                                ["welcome_option" => true]
                            )["id"],
                            "message" => $welcomeMessage,
                        ];
                    } else {
                        $bot_message = sb_option_process_reply(
                            $message,
                            $conversation_id
                        );
                    }
                } else {
                    $bot_message = false; // Skip welcome message if not active
                }
            }

            if ($bot_message) {
                sb_messaging_platforms_send_message(
                    $bot_message["message"],
                    $source,
                    $bot_message["id"]
                );
            }
        }
    }

    return $human_takeover ? "human_takeover" : true;
}

function sb_messaging_platforms_send_message(
    $message,
    $conversation,
    $message_id = false,
    $attachments = []
) {
    $conversation = is_numeric($conversation)
        ? sb_db_get(
            "SELECT user_id, source, extra FROM sb_conversations WHERE id = " .
                $conversation
        )
        : $conversation;
    $platform_value = sb_isset($conversation, "platform_value");
    $user_id = $conversation["user_id"];

    switch ($conversation["source"]) {
        case "ig":
        case "fb":
            return sb_messenger_send_message(
                $platform_value
                    ? $platform_value
                    : sb_get_user_extra($user_id, "facebook-id"),
                isset($conversation["page_id"])
                    ? $conversation["page_id"]
                    : $conversation["extra"],
                $message,
                $attachments,
                $message_id
            );
        case "wa":
            return sb_whatsapp_send_message(
                $platform_value ? $platform_value : sb_get_user_extra($user_id, "phone"),
                $message,
                $attachments,
                $conversation["extra"]
            );
        case "ww":
            return sb_whatsmeow_send_message(
                $platform_value
                    ? $platform_value
                    : sb_get_user_extra($user_id, "phone"),
                $message,
                $attachments
            );
        case "wx":
            return sb_waweb_send_message(
                $platform_value
                    ? $platform_value
                    : sb_get_user_extra($user_id, "phone"),
                $message,
                $attachments
            );
        case "tg":
            return sb_telegram_send_message(
                $platform_value
                    ? $platform_value
                    : sb_isset(
                        $conversation,
                        "chat_id",
                        $conversation["extra"]
                    ),
                $message,
                $attachments
            );
        case "tw":
            return sb_twitter_send_message(
                $platform_value
                    ? $platform_value
                    : sb_get_user_extra($user_id, "twitter-id"),
                $message,
                $attachments
            );
        case "bm":
            return sb_gbm_send_message(
                $platform_value
                    ? $platform_value
                    : sb_get_user_extra($user_id, "gbm-id"),
                $message,
                $attachments
            );
    }
    return false;
}


function sb_dialogflow_active()
{
    return defined("SB_DIALOGFLOW") && sb_get_setting("dialogflow-active");
}

function sb_logs($string, $user = false)
{
    $string =
        date("d-m-Y H:i:s") .
        " Agent " .
        sb_get_user_name($user) .
        " #" .
        ($user ? $user["id"] : sb_get_active_user_ID()) .
        " " .
        $string;
    $path = SB_PATH . "/log.txt";
    if (file_exists($path)) {
        $string = file_get_contents($path) . PHP_EOL . $string;
    }
    sb_file($path, $string);
}


function sb_webhooks($function_name, $parameters)
{
    $names = [
        "SBSMSSent" => "sms-sent",
        "SBLoginForm" => "login",
        "SBRegistrationForm" => "registration",
        "SBUserDeleted" => "user-deleted",
        "SBMessageSent" => "message-sent",
        "SBBotMessage" => "bot-message",
        "SBEmailSent" => "email-sent",
        "SBNewMessagesReceived" => "new-message",
        "SBNewConversationReceived" => "new-conversation",
        "SBNewConversationCreated" => "new-conversation-created",
        "SBActiveConversationStatusUpdated" => "conversation-status-updated",
        "SBSlackMessageSent" => "slack-message-sent",
        "SBMessageDeleted" => "message-deleted",
        "SBRichMessageSubmit" => "rich-message",
        "SBNewEmailAddress" => "new-email-address",
    ];
    $webhook_name = sb_isset($names, $function_name);
    if ($webhook_name) {
        $webhooks = sb_get_setting("webhooks");
        if (
            $webhooks &&
            $webhooks["webhooks-url"] &&
            $webhooks["webhooks-active"]
        ) {
            $allowed_webhooks = $webhooks["webhooks-allowed"];
            if ($allowed_webhooks && $allowed_webhooks !== true) {
                $allowed_webhooks = explode(
                    ",",
                    str_replace(" ", "", $allowed_webhooks)
                );
                if (!in_array($webhook_name, $allowed_webhooks)) {
                    return false;
                }
            }
            $query = json_encode([
                "function" => $webhook_name,
                "key" => $webhooks["webhooks-key"],
                "data" => $parameters,
                "sender-url" => isset($_SERVER["HTTP_REFERER"])
                    ? $_SERVER["HTTP_REFERER"]
                    : "",
            ]);
            if ($query) {
                return sb_curl($webhooks["webhooks-url"], $query, [
                    "Content-Type: application/json",
                    "Content-Length: " . strlen($query),
                ]);
            } else {
                return new SBError("webhook-json-error");
            }
        } else {
            return new SBValidationError("webhook-not-active-or-empty-url");
        }
    } else {
        return new SBValidationError("webhook-not-found");
    }
}





function sb_tags_update($conversation_id, $tags, $add = false, $clicked_tag = null)
{
    // Sanitize tags
    for ($i = 0; $i < count($tags); $i++) {
        $tags[$i] = trim(str_replace(',', '', $tags[$i]));
    }

    // Merge tags if $add is true
    if ($add) {
        $existing_tags = sb_isset(sb_db_get('SELECT tags FROM sb_conversations WHERE id = ' . sb_db_escape($conversation_id, true)), 'tags');
        if ($existing_tags) {
            $existing_tags = explode(',', $existing_tags);
            $tags = array_unique(array_merge($tags, $existing_tags));
        }
    }

    // If $clicked_tag is provided, remove it from the $tags array
    if ($clicked_tag !== null) {
        $tags = array_diff($tags, [$clicked_tag]);
    }

    // Update the tags in the database
    $response = sb_db_query('UPDATE sb_conversations SET tags = "' . sb_db_escape(implode(',', $tags)) . '" WHERE id = ' . sb_db_escape($conversation_id, true));
    $all_tags = sb_db_get('SELECT tags FROM sb_conversations WHERE tags IS NOT NULL AND tags <> ""', false);
    $all_tags_final = [];
    for ($i = 0; $i < count($all_tags); $i++) {
        $tags = explode(',', $all_tags[$i]['tags']);
        for ($j = 0; $j < count($tags); $j++) {
            if (!in_array($tags[$j], $all_tags_final)) {
                array_push($all_tags_final, $tags[$j]);
            }
            if ($tags === null) {
                $tags = [];
            }
        }
    }
    sb_save_external_setting('tags', $all_tags_final);
    return [$response, $all_tags_final];
}


function sb_tags_get()
{
    return sb_get_external_setting('tags', []);
}







function sb_cron_jobs_add($key, $content = false, $job_time = false)
{
    // Add the job to the cron jobs
    $cron_functions = sb_get_external_setting("cron-functions");
    if (empty($cron_functions) || empty($cron_functions["value"])) {
        sb_save_external_setting("cron-functions", [$key]);
    } else {
        $cron_functions = json_decode($cron_functions["value"], true);
        if (!in_array($key, $cron_functions)) {
            array_push($cron_functions, $key);
            sb_db_query(
                'UPDATE sb_settings SET value = \'' .
                    sb_db_json_escape($cron_functions) .
                    '\' WHERE name = "cron-functions"'
            );
        }
    }

    // Set the cron job data
    if (!empty($content) && !empty($job_time)) {
        $user = sb_get_active_user();
        if ($user) {
            $key = "cron-" . $key;
            $scheduled = sb_get_external_setting($key);
            if (empty($scheduled)) {
                $scheduled = [];
            }
            $scheduled[$user["id"]] = [$content, strtotime("+" . $job_time)];
            sb_save_external_setting($key, $scheduled);
        }
    }
}


function sb_cron_jobs()
{
    ignore_user_abort(true);
    set_time_limit(180);
    $now = date("H");
    $cron_functions = sb_get_external_setting("cron-functions");

    // Removed WooCommerce and AEcommerce related code

    sb_clean_data();
    sb_db_query('DELETE FROM sb_settings WHERE name="cron-functions"');
    sb_save_external_setting("cron", $now);
}


function sb_send_sms(
    $message,
    $to,
    $template = true,
    $conversation_id = true,
    $attachments = false
) {
    $settings = sb_get_setting("sms");
    $to_agents =
        $to == "agents" ||
        $to == "all-agents" ||
        strpos($to, "department-") !== false;

    // Retrive phone number
    if ($to_agents) {
        $phones = sb_db_get(
            'SELECT A.id, value FROM sb_users A, sb_users_data B WHERE A.id = B.user_id AND (user_type = "agent" OR user_type = "admin") AND slug = "phone"' .
                ($to == "agents"
                    ? ' AND (department IS NULL OR department = "")'
                    : (strpos($to, "department-") !== false
                        ? " AND department = " . substr($to, 11)
                        : "")),
            false
        );
        $online_agents_ids = sb_get_online_user_ids(true);
        for ($i = 0; $i < count($phones); $i++) {
            if (!in_array($phones[$i]["id"], $online_agents_ids)) {
                sb_send_sms(
                    $message,
                    $phones[$i]["value"],
                    $template,
                    $conversation_id,
                    $attachments
                );
            }
        }
        return false;
    } elseif (strpos($to, "+") === false && substr($to, 0, 2) != "00") {
        $to = sb_get_user_extra($to, "phone");
        if (empty($to)) {
            return false;
        }
    }

    // Recipient user details, security, and merge fields
    $user = sb_get_user_by("phone", $to);
    if (
        !sb_is_agent() &&
        !sb_is_agent($user) &&
        sb_get_active_user_ID() != sb_isset($user, "id") &&
        empty($GLOBALS["SB_FORCE_ADMIN"])
    ) {
        return new SBError("security-error", "sb_send_sms");
    }
    $message_template = $template
        ? sb_(
            $settings[sb_is_agent() &&
                empty($GLOBALS["SB_FORCE_ADMIN"]) &&
                !$to_agents
                ? "sms-message-user"
                : "sms-message-agent"]
        )
        : false;
    $message = $message_template
        ? str_replace("{message}", $message, $message_template)
        : $message;
    $message = str_replace(
        [
            "{conversation_url_parameter}",
            "{recipient_name}",
            "{sender_name}",
            "{recipient_email}",
            "{sender_email}",
        ],
        [
            $conversation_id && $user
                ? "?conversation=" .
                $conversation_id .
                "&token=" .
                $user["token"]
                : "",
            sb_get_user_name($user),
            sb_get_user_name(),
            sb_isset($user, "email"),
            sb_isset(sb_get_active_user(), "email", ""),
        ],
        sb_merge_fields($message)
    );

    // Send the SMS
    $message = sb_clear_text_formatting(strip_tags($message));
    $query = [
        "Body" => $message,
        "From" => $settings["sms-sender"],
        "To" => $to,
    ];

    // Check if $attachments is an array and not empty
    if (is_array($attachments) && !empty($attachments)) {
        $mime_types = ["jpeg", "jpg", "png", "gif"];
        foreach ($attachments as $attachment) {
            // Check if $attachment is an array and has a second element
            if (is_array($attachment) && count($attachment) >= 2) {
                $file_path = $attachment[1];
                if (in_array(pathinfo($file_path, PATHINFO_EXTENSION), $mime_types)) {
                    $query["MediaUrl"][] = $file_path;
                } else {
                    $message .= PHP_EOL . PHP_EOL . $file_path;
                }
            }
        }
        // Update the "Body" parameter in $query
        $query["Body"] = $message;
    }

    $query = http_build_query($query);
    // Replace multiple MediaUrl parameters with a single one
    if (strpos($query, "MediaUrl")) {
        $query = preg_replace('/MediaUrl\d/', 'MediaUrl', $query);
    }

    // Perform the Twilio API request
    return sb_curl(
        "https://api.twilio.com/2010-04-01/Accounts/" .
            $settings["sms-user"] .
            "/Messages.json",
        $query,
        [
            "Authorization: Basic  " .
                base64_encode($settings["sms-user"] . ":" . $settings["sms-token"]),
        ]
    );
}





/*
 * -----------------------------------------------------------
 * REPORTS
 * -----------------------------------------------------------
 *
 * 1. Return the data of a report
 * 2. Update the values of a report
 *
 */

function sb_reports($report_name, $date_start = false, $date_end = false)
{
    $date = "";
    $data = [];
    $data_final = [];
    $title = "";
    $table = [sb_("Date"), sb_("Count")];
    $description = "";
    $period = [];
    $query = "";
    $time_range = true;
    $label_type = 1;
    $chart_type = "line";

    // Set up date range
    if ($date_start) {
        $date_start = date(
            "Y-m-d",
            strtotime(str_replace("/", "-", $date_start))
        );
        $date = 'A.creation_time >= "' . sb_db_escape($date_start) . ' 00:00"';
    }
    if ($date_end) {
        $date_end = date("Y-m-d", strtotime(str_replace("/", "-", $date_end)));
        $date .=
            ($date == "" ? "" : " AND ") .
            'A.creation_time <= "' .
            sb_db_escape($date_end) .
            ' 23:59"';
    }

    // Get the data

    switch ($report_name) {
        case "conversations":
            $query =
                'SELECT A.creation_time FROM sb_conversations A, sb_users B WHERE B.id = A.user_id AND B.user_type <> "visitor"';
            $title = "Conversations count";
            $description = "Count of new conversations started by users.";
            break;
        case "missed-conversations":
            $query =
                'SELECT creation_time FROM sb_conversations A WHERE id NOT IN (SELECT conversation_id FROM sb_messages A, sb_users B WHERE A.user_id = B.id AND (B.user_type = "agent" OR B.user_type = "admin"))';
            $title = "Missed conversations count";
            $description =
                "Count of conversations without a reply from an human agent. Conversations with a reply from the bot are counted.";
            break;
        case "conversations-time":
            $query = "SELECT creation_time, conversation_id FROM sb_messages A WHERE creation_time >= DATE_SUB(NOW(), INTERVAL 8 DAY)";
            $title = "Average conversations duration";
            $description =
                "Average conversations duration. Messages sent more than 7 days after the previous message are counted as part of a new conversation.";
            $table = [sb_("Date"), sb_("Average time")];
            $label_type = 2;
            break;
        case "visitors":
            $query =
                'SELECT creation_time, value FROM sb_reports A WHERE name = "visitors"';
            $title = "Visitor registrations count";
            $description =
                "Visitors count. Visitors are users who have not started any conversations and who are not registered.";
            break;
        case "leads":
            $query =
                'SELECT creation_time FROM sb_users A WHERE user_type = "lead"';
            $title = "Lead registrations count";
            $description =
                "Leads count. Leads are users who have started at least one conversation but who are not registered.";
            break;
        case "users":
            $query =
                'SELECT creation_time FROM sb_users A WHERE user_type = "user"';
            $title = "User registrations count";
            $description =
                "Users count. Users are registered with an email address.";
            break;
        case "agents-conversations":
            $title = "Agent conversations count";
            $description =
                "Number of conversations which at least one reply from the agent.";
            $table = [sb_("Agent name"), sb_("Count")];
            $chart_type = "bar";
            $time_range = false;
            break;
        case "agents-response-time":
            $title = "Average agent response time";
            $description =
                "Average time for agents to send the first reply after the user sends the first message.";
            $table = [sb_("Agent name"), sb_("Average time")];
            $time_range = false;
            $chart_type = "bar";
            $label_type = 2;
            break;
        case "agents-conversations-time":
            $query = "SELECT creation_time, conversation_id FROM sb_messages A WHERE creation_time >= DATE_SUB(NOW(), INTERVAL 8 DAY)";
            $title = "Average agent conversations duration";
            $description =
                "Average conversations duration of each agent. Messages sent more than 7 days after the previous message are counted as part of a new conversation.";
            $table = [sb_("Agent name"), sb_("Average time")];
            $chart_type = "bar";
            $label_type = 2;
            $time_range = false;
            break;
        case "agents-ratings":
            $title = "Agent ratings";
            $description = "Ratings assigned to agents.";
            $table = [sb_("Agent name"), sb_("Ratings")];
            $chart_type = "horizontalBar";
            $time_range = false;
            $label_type = 3;
            break;
        case "countries":
            $title = "User countries";
            $description = "Countries of users who started at least one chat.";
            $table = [sb_("Country"), sb_("Count")];
            $time_range = false;
            $chart_type = "pie";
            $label_type = 4;
            break;

        case "browsers":
            $title = "User browsers";
            $description =
                "Browsers used by users who started at least one chat.";
            $table = [sb_("Browser"), sb_("Count")];
            $time_range = false;
            $chart_type = "pie";
            $label_type = 4;
            break;
        case "os":
            $title = "User operating systems";
            $description =
                "Operating systems used by users who started at least one chat.";
            $table = [sb_("Operating system"), sb_("Count")];
            $time_range = false;
            $chart_type = "pie";
            $label_type = 4;
            break;


        case "registrations":
            $query =
                'SELECT creation_time, value FROM sb_reports A WHERE name = "registrations"';
            $title = "Registrations count";
            $description =
                "Number of users who created an account via the registration form of the chat.";
            break;

        case "sms-automations":
        case "email-automations":
        case "message-automations":
            $query =
                'SELECT creation_time, value FROM sb_reports A WHERE name = "' .
                $report_name .
                '"';
            $title = $description =
                sb_string_slug($report_name, "string") . " count";
            break;
        case "direct-sms":
        case "direct-emails":
        case "direct-messages":
            $query =
                'SELECT creation_time, value FROM sb_reports A WHERE name = "' .
                $report_name .
                '"';
            $name =
                $report_name == "direct-emails"
                ? "emails"
                : ($report_name == "direct-messages"
                    ? "chat messages"
                    : "text messages");
            $title = "Direct " . $name;
            $description =
                "Direct messages sent to users. The details column shows the first part of the message and the number of users to which it has been sent to.";
            $table = [sb_("Date"), sb_("Sent message | Qty")];
            break;

        case "status-client":
            $clientStatus = array("Abierto", "Presupuesto", "Consulta", "Contactado", "Visitado", "Calificado", "Confirmado", "Pendiente", "Resuelto", "Pagado", "VIP", "Descartado", "NA");
            $query = 'SELECT * FROM sb_conversations A, sb_users B WHERE B.id = A.user_id AND label IN ("' . implode('", "', $clientStatus) . '")';
            $extra = sb_get_clientStatus_conversations();
            $description = "Count of all tagged conversations by team members.";
            break;
    }

    switch ($report_name) {
        case "status-client":
            $rows = sb_db_get($query, false);
            $data = $rows;
            return [
                "title" => sb_($title),
                "description" => sb_($description),
                "data" => $data,
                "extra" => $extra,
            ];
        case "sms-automations":
        case "email-automations":
        case "message-automations":
        case "registrations":
        case "follow-up":
        case "subscribe":
        case "users":
        case "leads":
        case "visitors":
        case "conversations":
        case "missed-conversations":
            $rows = sb_db_get(
                $query .
                    ($date == "" ? "" : " AND " . $date) .
                    ' ORDER BY STR_TO_DATE(A.creation_time, "%Y-%m-%d %T")',
                false
            );
            $sum = !in_array($report_name, [
                "visitors",
                "subscribe",
                "follow-up",
                "registrations",
                "message-automations",
                "email-automations",
                "sms-automations",
            ]);
            for ($i = 0; $i < count($rows); $i++) {
                $date_row = date(
                    "d/m/Y",
                    strtotime($rows[$i]["creation_time"])
                );
                $data[$date_row] = $sum
                    ? [empty($data[$date_row]) ? 1 : $data[$date_row][0] + 1]
                    : [$rows[$i]["value"]];
            }
            break;



        case "agents-conversations-time":
        case "conversations-time":
            $rows = sb_db_get(
                $query .
                    ($date == "" ? "" : " WHERE " . $date) .
                    ' ORDER BY STR_TO_DATE(creation_time, "%Y-%m-%d %T")',
                false
            );
            $count = count($rows);
            if ($count == 0) {
                return false;
            }
            $last_id = $rows[0]["conversation_id"];
            $first_time = $rows[0]["creation_time"];
            $times = [];
            $agents_times = $report_name == "agents-conversations-time";
            for ($i = 1; $i < $count; $i++) {
                $time = $rows[$i]["creation_time"];
                if (
                    $rows[$i]["conversation_id"] != $last_id ||
                    strtotime("+7 day", strtotime($first_time)) <
                    strtotime($time)
                ) {
                    $last_time = strtotime($rows[$i - 1]["creation_time"]);
                    array_push($times, [
                        $agents_times ? $last_id : date("d/m/Y", $last_time),
                        $last_time - strtotime($first_time),
                    ]);
                    $first_time = $time;
                    $last_id = $rows[$i]["conversation_id"];
                }
            }
            if ($agents_times) {
                $agents_counts = [];
                $agents_conversations = [];
                $rows = sb_db_get(
                    'SELECT conversation_id, first_name, last_name FROM sb_messages A, sb_users B WHERE A.user_id = B.id AND (B.user_type = "agent" OR  B.user_type = "admin") GROUP BY conversation_id',
                    false
                );
                for ($i = 0; $i < count($rows); $i++) {
                    $agents_conversations[$rows[$i]["conversation_id"]] =
                        $rows[$i]["first_name"] . " " . $rows[$i]["last_name"];
                }
                for ($i = 0; $i < count($times); $i++) {
                    if (isset($agents_conversations[$times[$i][0]])) {
                        $name = $agents_conversations[$times[$i][0]];
                        $data[$name] = empty($data[$name])
                            ? $times[$i][1]
                            : $data[$name] + $times[$i][1];
                        $agents_counts[$name] = empty($agents_counts[$name])
                            ? 1
                            : $agents_counts[$name] + 1;
                    }
                }
                foreach ($data as $key => $value) {
                    $data[$key] = [
                        intval($value / $agents_counts[$key]),
                        gmdate("H:i:s", $value / $agents_counts[$key]),
                    ];
                }
            } else {
                for ($i = 0; $i < count($times); $i++) {
                    $time = $times[$i][0];
                    $count = 0;
                    $sum = 0;
                    if (!isset($data[$time])) {
                        for ($y = 0; $y < count($times); $y++) {
                            if ($times[$y][0] == $time) {
                                $sum += $times[$y][1];
                                $count++;
                            }
                        }
                        $data[$time] = [
                            intval($sum / $count),
                            gmdate("H:i:s", $sum / $count),
                        ];
                    }
                }
            }
            break;
        case "agents-conversations":
            $rows = sb_db_get(
                'SELECT first_name, last_name FROM sb_messages A, sb_users B WHERE A.user_id = B.id AND (B.user_type = "agent" OR  B.user_type = "admin") ' .
                    ($date ? " AND " . $date : "") .
                    " GROUP BY conversation_id, B.id",
                false
            );
            for ($i = 0; $i < count($rows); $i++) {
                $name = $rows[$i]["first_name"] . " " . $rows[$i]["last_name"];
                $data[$name] = [empty($data[$name]) ? 1 : $data[$name][0] + 1];
            }
            break;
            // case "agents-response-time":
            //     $conversations = sb_db_get(
            //         'SELECT A.user_id, B.user_type, A.conversation_id, A.creation_time FROM sb_messages A, sb_users B WHERE B.id = A.user_id AND A.conversation_id IN (SELECT conversation_id FROM sb_messages A WHERE user_id IN (SELECT id FROM sb_users WHERE user_type = "agent" OR user_type = "admin") ' .
            //             ($date == "" ? "" : " AND " . $date) .
            //             ') AND A.creation_time >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY A.conversation_id, STR_TO_DATE(A.creation_time, "%Y-%m-%d %T")',
            //         false
            //     );


            // ORIGINAL
        case "agents-response-time":
            $conversations = sb_db_get(
                'SELECT A.user_id, B.user_type, A.conversation_id, A.creation_time FROM sb_messages A, sb_users B WHERE B.id = A.user_id AND A.conversation_id IN (SELECT conversation_id FROM sb_messages A WHERE user_id IN (SELECT id FROM sb_users WHERE user_type = "agent" OR user_type = "admin") ' .
                    ($date == "" ? "" : " AND " . $date) .
                    ') ORDER BY A.conversation_id, STR_TO_DATE(A.creation_time, "%Y-%m-%d %T")',
                false
            );


            $count = count($conversations);
            if ($count == 0) {
                return false;
            }
            $agents = [];
            $active_conversation = $conversations[0];
            $skip = false;
            $agents_ids = "";
            for ($i = 1; $i < $count; $i++) {
                if ($skip) {
                    if (
                        $active_conversation["conversation_id"] !=
                        $conversations[$i]["conversation_id"]
                    ) {
                        $active_conversation = $conversations[$i];
                        $skip = false;
                    }
                    continue;
                }
                if (sb_is_agent($conversations[$i], true)) {
                    $conversation_time =
                        strtotime($conversations[$i]["creation_time"]) -
                        strtotime($active_conversation["creation_time"]);
                    $agent_id = $conversations[$i]["user_id"];
                    if (!isset($agents[$agent_id])) {
                        $agents[$agent_id] = [];
                        $agents_ids .= $agent_id . ",";
                    }
                    array_push($agents[$agent_id], $conversation_time);
                    $skip = true;
                }
            }
            $rows = sb_db_get(
                "SELECT id, first_name, last_name FROM sb_users WHERE id IN (" .
                    substr($agents_ids, 0, -1) .
                    ")",
                false
            );
            $agent_names = [];
            for ($i = 0; $i < count($rows); $i++) {
                $agent_names[$rows[$i]["id"]] =
                    $rows[$i]["first_name"] . " " . $rows[$i]["last_name"];
            }
            foreach ($agents as $key => $times) {
                $sum = 0;
                $count = count($times);
                for ($i = 0; $i < $count; $i++) {
                    $sum += $times[$i];
                }
                $data[$agent_names[$key]] = [
                    intval($sum / $count),
                    gmdate("H:i:s", $sum / $count),
                ];
            }
            break;
        case "agents-ratings":
            $article = $report_name == "articles-ratings";
            $ratings = $article
                ? sb_db_get($query, false)
                : sb_get_external_setting("ratings");
            if ($ratings) {
                $rows = sb_db_get(
                    'SELECT id, first_name, last_name FROM sb_users WHERE user_type = "agent" OR user_type = "admin"',
                    false
                );
                $items = [];
                for ($i = 0; $i < count($rows); $i++) {
                    $items[$rows[$i]["id"]] = $article
                        ? $rows[$i]["title"]
                        : $rows[$i]["first_name"] .
                        " " .
                        $rows[$i]["last_name"];
                }
                if ($article) {
                    for ($i = 0; $i < count($ratings); $i++) {
                        $rating = $ratings[$i];
                        if (isset($rating["extra"])) {
                            $id = $rating["extra"];
                            if (
                                isset($items[$id]) &&
                                !empty($rating["value"])
                            ) {
                                $article_ratings = json_decode(
                                    $rating["value"]
                                );
                                $positives = 0;
                                $negatives = 0;
                                $name =
                                    strlen($items[$id]) > 40
                                    ? substr($items[$id], 0, 40) . "..."
                                    : $items[$id];
                                for (
                                    $y = 0;
                                    $y < count($article_ratings);
                                    $y++
                                ) {
                                    $positives +=
                                        $article_ratings[$y] == 1 ? 1 : 0;
                                    $negatives +=
                                        $article_ratings[$y] == 1 ? 0 : 1;
                                }
                                $data[$name] = [$positives, $negatives];
                            }
                        }
                    }
                } else {
                    foreach ($ratings as $rating) {
                        if (isset($rating["agent_id"])) {
                            $id = $rating["agent_id"];
                            if (isset($items[$id])) {
                                $positive = $rating["rating"] == 1 ? 1 : 0;
                                $negative = $rating["rating"] == 1 ? 0 : 1;
                                $name = $items[$id];
                                $data[$name] = isset($data[$name])
                                    ? [
                                        $data[$name][0] + $positive,
                                        $data[$name][1] + $negative,
                                    ]
                                    : [$positive, $negative];
                            }
                        }
                    }
                }
                foreach ($data as $key => $value) {
                    $positive = $value[0];
                    $negative = $value[1];
                    $average = round(
                        ($positive / ($negative + $positive)) * 100,
                        2
                    );
                    $data[$key] = [
                        $average,
                        '<i class="bi-hand-thumbs-up-fill"></i>' .
                            $positive .
                            " (" .
                            $average .
                            '%) <i class="bi-hand-thumbs-down-fill"></i>' .
                            $negative,
                    ];
                }
            }
            break;

        case "os":
        case "browsers":
        case "languages":
        case "countries":
            $field = "location";
            $is_languages = $report_name == "languages";
            $is_browser = $report_name == "browsers";
            $is_os = $report_name == "os";
            $is_country = $report_name == "countries";
            if ($is_languages) {
                $field = "browser_language";
            }
            if ($is_browser) {
                $field = "browser";
            }
            if ($is_os) {
                $field = "os";
            }
            $language_codes = json_decode(
                file_get_contents(
                    SB_PATH . "/resources/languages/language-codes.json"
                ),
                true
            );
            $country_codes = $is_country
                ? json_decode(
                    file_get_contents(
                        SB_PATH . "/resources/json/countries.json"
                    ),
                    true
                )
                : false;
            $rows = sb_db_get(
                'SELECT value FROM sb_users_data WHERE slug = "' .
                    $field .
                    '" AND user_id IN (SELECT id FROM sb_users A WHERE (user_type = "lead" OR user_type = "user")' .
                    ($date == "" ? "" : " AND " . $date) .
                    ")",
                false
            );
            $total = 0;
            $flags = [];
            for ($i = 0; $i < count($rows); $i++) {
                $value = $rows[$i]["value"];
                $valid = false;
                if ($is_country && strpos($value, ",")) {
                    $value = trim(substr($value, strpos($value, ",") + 1));
                    $valid = true;
                }
                if (
                    ($is_languages &&
                        isset($language_codes[strtolower($value)])) ||
                    ($is_country && isset($country_codes[strtoupper($value)]))
                ) {
                    $code = strtolower(
                        $is_languages
                            ? $value
                            : $country_codes[strtoupper($value)]
                    );
                    $value = $language_codes[$code];
                    if (
                        !isset($flags[$value]) &&
                        file_exists(SB_PATH . "/media/flags/" . $code . ".png")
                    ) {
                        $flags[$value] = $code;
                    }
                    $valid = true;
                }
                if ($valid || $is_browser || $is_os) {
                    $data[$value] = empty($data[$value])
                        ? 1
                        : $data[$value] + 1;
                    $total++;
                }
            }
            arsort($data);
            foreach ($data as $key => $value) {
                $image = "";
                if (isset($flags[$key])) {
                    $image =
                        '<img class="sb-flag" src="' .
                        STMBX_URL .
                        "/media/flags/" .
                        $flags[$key] .
                        '.png" />';
                }
                if ($is_browser) {
                    $lowercase = strtolower($key);
                    if (strpos($lowercase, "chrome") !== false) {
                        $image = "chrome";
                    } elseif (strpos($lowercase, "edge") !== false) {
                        $image = "edge";
                    } elseif (strpos($lowercase, "firefox") !== false) {
                        $image = "firefox";
                    } elseif (strpos($lowercase, "opera") !== false) {
                        $image = "opera";
                    } elseif (strpos($lowercase, "safari") !== false) {
                        $image = "safari";
                    }
                    if ($image) {
                        $image =
                            '<img src="' .
                            STMBX_URL .
                            "/media/devices/" .
                            $image .
                            '.svg" />';
                    }
                }
                if ($is_os) {
                    $lowercase = strtolower($key);
                    if (strpos($lowercase, "windows") !== false) {
                        $image = "windows";
                    } elseif (
                        strpos($lowercase, "mac") !== false ||
                        strpos($lowercase, "apple") !== false ||
                        strpos($lowercase, "ipad") !== false ||
                        strpos($lowercase, "iphone") !== false
                    ) {
                        $image = "apple";
                    } elseif (strpos($lowercase, "android") !== false) {
                        $image = "android";
                    } elseif (strpos($lowercase, "linux") !== false) {
                        $image = "linux";
                    } elseif (strpos($lowercase, "ubuntu") !== false) {
                        $image = "ubuntu";
                    }
                    if ($image) {
                        $image =
                            '<img src="' .
                            STMBX_URL .
                            "/media/devices/" .
                            $image .
                            '.svg" />';
                    }
                }
                $data[$key] = [
                    $value,
                    $image .
                        $value .
                        " (" .
                        round(($value / $total) * 100, 2) .
                        "%)",
                ];
            }
            break;
        case "direct-sms":
        case "direct-emails":
        case "direct-messages":
        case "articles-searches":
            $rows = sb_db_get(
                $query .
                    ($date == "" ? "" : " AND " . $date) .
                    ' ORDER BY STR_TO_DATE(A.creation_time, "%Y-%m-%d %T")',
                false
            );
            for ($i = 0; $i < count($rows); $i++) {
                $date_row = date(
                    "d/m/Y",
                    strtotime($rows[$i]["creation_time"])
                );
                $search = "<div>" . $rows[$i]["value"] . "</div>";
                $data[$date_row] = empty($data[$date_row])
                    ? [1, $search]
                    : [$data[$date_row][0] + 1, $data[$date_row][1] . $search];
            }
            break;
    }

    // Generate all days, months, years within the date range
    if (!count($data)) {
        return false;
    }
    if ($time_range) {
        if (!$date_start) {
            $date_start = date(
                "Y-m-d",
                strtotime(str_replace("/", "-", array_keys($data)[0]))
            );
        }
        if (!$date_end) {
            $date_end = date(
                "Y-m-d",
                strtotime(
                    str_replace("/", "-", array_keys($data)[count($data) - 1])
                )
            );
        }
        $period = new DatePeriod(
            new DateTime($date_start),
            new DateInterval("P1D"),
            new DateTime(date("Y-m-d", strtotime($date_end . "+1 days")))
        );
        $period = iterator_to_array($period);
        $period_count = count($period);
        $date_format =
            $period_count > 730 ? "Y" : ($period_count > 60 ? "m/Y" : "d/m/Y");
        $is_array = count(reset($data)) > 1;
        $counts = [];
        $average = $label_type == 2;
        for ($i = 0; $i < $period_count; $i++) {
            $key = $period[$i]->format($date_format);
            $key_original = $period[$i]->format("d/m/Y");
            $value = empty($data[$key_original]) ? 0 : $data[$key_original][0];
            $data_final[$key] = [
                empty($data_final[$key])
                    ? $value
                    : $data_final[$key][0] + $value,
            ];
            if ($average) {
                $counts[$key] = empty($counts[$key]) ? 1 : $counts[$key] + 1;
            }
            if ($is_array) {
                array_push(
                    $data_final[$key],
                    empty($data[$key_original][1])
                        ? ""
                        : $data[$key_original][1]
                );
            }
        }
        if ($average && $period_count > 60) {
            foreach ($data_final as $key => $value) {
                $data_final[$key] = [
                    intval($value[0] / $counts[$key]),
                    gmdate("H:i:s", $value[0] / $counts[$key]),
                ];
            }
        }
    } else {
        $data_final = $data;
    }

    // Return the data
    return [
        "title" => sb_($title),
        "description" => sb_($description),
        "data" => $data_final,
        "table" => $table,
        "table-inverse" => $time_range,
        "label_type" => $label_type,
        "chart_type" => $chart_type,
    ];
}

function sb_reports_update(
    $name,
    $value = false,
    $external_id = false,
    $extra = false
) {
    if (sb_get_multi_setting("performance", "performance-reports")) {
        return false;
    }
    $now = gmdate("Y-m-d");
    $name = sb_db_escape($name);
    $extra = sb_db_escape($extra);
    switch ($name) {
        case "direct-sms":
        case "direct-emails":
        case "direct-messages":
        case "articles-searches":
            return sb_db_query(
                'INSERT INTO sb_reports (name, value, creation_time, external_id, extra) VALUES ("' .
                    $name .
                    '", "' .
                    sb_db_escape($value) .
                    '", "' .
                    $now .
                    '", NULL, NULL)'
            );

        default:
            $where =
                ' WHERE name = "' .
                $name .
                '" AND creation_time = "' .
                $now .
                '"';
            $count = sb_db_get("SELECT value FROM sb_reports" . $where);
            return sb_db_query(
                empty($count)
                    ? 'INSERT INTO sb_reports (name, value, creation_time, external_id, extra) VALUES ("' .
                    $name .
                    '", 1, "' .
                    $now .
                    '", ' .
                    ($external_id === false
                        ? "NULL"
                        : '"' . $external_id . '"') .
                    ", " .
                    ($extra === false ? "NULL" : '"' . $extra . '"') .
                    ")"
                    : "UPDATE sb_reports SET value = " .
                    (intval($count["value"]) + 1) .
                    $where
            );
    }
}

/*
 * -----------------------------------------------------------
 * AUTOMATIONS
 * -----------------------------------------------------------
 *
 * 1. Get all automations
 * 2. Save all automations
 * 3. Run all valid automations and return the ones which need client-side validations
 * 4. Check if an automation is valid and can be executed
 * 5. Execute an automation
 *
 */

function sb_automations_get()
{
    $types = ["messages", "emails", "sms", "popups", "design", "more"];
    $automations = sb_get_external_setting("automations");
    $translations = [];
    $rows = sb_db_get(
        'SELECT name, value FROM sb_settings WHERE name LIKE "automations-translations-%"',
        false
    );
    for ($i = 0; $i < count($rows); $i++) {
        $translations[substr($rows[$i]["name"], -2)] = json_decode(
            $rows[$i]["value"],
            true
        );
    }
    for ($i = 0; $i < count($types); $i++) {
        if (!isset($automations[$types[$i]])) {
            $automations[$types[$i]] = [];
        }
    }
    return [$automations, $translations];
}

function sb_automations_save($automations, $translations = false)
{
    if ($translations) {
        $db = "";
        foreach ($translations as $key => $value) {
            $name = "automations-translations-" . $key;
            sb_save_external_setting($name, $value);
            $db .= '"' . $name . '",';
        }
        sb_db_query(
            'DELETE FROM sb_settings WHERE name LIKE "automations-translations-%" AND name NOT IN (' .
                substr($db, 0, -1) .
                ")"
        );
    }
    return sb_save_external_setting(
        "automations",
        empty($automations) ? [] : $automations
    );
}

function sb_automations_run_all()
{
    if (sb_is_agent()) {
        return false;
    }
    $response = [];
    $automations_all = sb_automations_get();
    $user_language = sb_get_user_language();
    foreach ($automations_all[0] as $type => $automations) {
        for ($i = 0; $i < count($automations); $i++) {
            $automations[$i]["type"] = $type;
            $validation = sb_automations_validate($automations[$i]);
            if ($validation) {
                $automation_id = $automations[$i]["id"];
                $conditions = $validation["conditions"];

                // Translations
                if (
                    $user_language &&
                    isset($automations_all[1][$user_language])
                ) {
                    $translations = sb_isset(
                        $automations_all[1][$user_language],
                        $type,
                        []
                    );
                    for ($x = 0; $x < count($translations); $x++) {
                        if ($translations[$x]["id"] == $automation_id) {
                            $automations[$i] = $translations[$x];
                            $automations[$i]["type"] = $type;
                            break;
                        }
                    }
                }
                if ($validation["repeat_id"]) {
                    $automations[$i]["repeat_id"] = $validation["repeat_id"];
                }
                if (
                    count($conditions) ||
                    $type == "popups" ||
                    $type == "design" ||
                    $type == "more" ||
                    !sb_get_active_user()
                ) {
                    // Automation with client-side conditions, server-side invalid conditions, or popup, design
                    $automations[$i]["conditions"] = $conditions;
                    array_push($response, $automations[$i]);
                } else {
                    // Run automation
                    sb_automations_run($automations[$i]);
                }
            }
        }
    }
    return $response;
}

function sb_automations_validate($automation)
{
    $conditions = sb_isset($automation, "conditions", []);
    $invalid_conditions = [];
    $repeat_id = false;
    $valid = false;
    $active_user = sb_get_active_user();
    $active_user_id = sb_isset($active_user, "id");
    for ($i = 0; $i < count($conditions); $i++) {
        $valid = false;
        $criteria = $conditions[$i][1];
        switch ($conditions[$i][0]) {
            case "datetime":
                $now = time();
                $offset = intval(sb_get_setting("timetable-utc", 0)) * 3600;
                if ($criteria == "is-between") {
                    $dates = explode(" - ", $conditions[$i][2]);
                    if (count($dates) == 2) {
                        $unix =
                            date_timestamp_get(
                                DateTime::createFromFormat(
                                    "d/m/Y H:i",
                                    $dates[0] .
                                        (strpos($dates[0], ":") ? "" : " 00:00")
                                )
                            ) + (strpos($dates[0], ":") ? $offset : 0);
                        $unix_end =
                            date_timestamp_get(
                                DateTime::createFromFormat(
                                    "d/m/Y H:i",
                                    $dates[1] .
                                        (strpos($dates[1], ":") ? "" : " 23:59")
                                )
                            ) + (strpos($dates[1], ":") ? $offset : 0);
                        $valid = $now >= $unix && $now <= $unix_end;
                        $continue = true;
                    }
                } elseif ($criteria == "is-exactly") {
                    $is_time = strpos($conditions[$i][2], ":");
                    $unix =
                        date_timestamp_get(
                            DateTime::createFromFormat(
                                "d/m/Y H:i",
                                $conditions[$i][2] . ($is_time ? "" : " 00:00")
                            )
                        ) + $offset;
                    $valid =
                        $now == $unix ||
                        (!$is_time && $now > $unix && $now < $unix + 86400);
                }
                if (!$valid) {
                    for ($j = 0; $j < count($conditions); $j++) {
                        if ($conditions[$j][0] == "repeat") {
                            $condition = $conditions[$j][1];
                            if ($criteria == "is-between" && $continue) {
                                $hhmm = false;
                                $hhmm_end = false;
                                if (
                                    strpos($dates[0], ":") &&
                                    strpos($dates[1], ":")
                                ) {
                                    $hhmm =
                                        strtotime(
                                            date(
                                                "Y-m-d " .
                                                    explode(" ", $dates[0])[1]
                                            )
                                        ) + $offset;
                                    $hhmm_end =
                                        strtotime(
                                            date(
                                                "Y-m-d " .
                                                    explode(" ", $dates[1])[1]
                                            )
                                        ) + $offset;
                                }
                                if ($condition == "every-day") {
                                    $valid = $hhmm
                                        ? $now >= $hhmm && $now <= $hhmm_end
                                        : true;
                                    $repeat_id = $valid ? date("z") : false;
                                } else {
                                    $letter =
                                        $condition == "every-week"
                                        ? "w"
                                        : ($condition == "every-month"
                                            ? "d"
                                            : "z");
                                    $letter_value_now = date($letter);
                                    $letter_value_unix = date($letter, $unix);
                                    $letter_value_unix_end = date(
                                        $letter,
                                        $unix_end
                                    );
                                    if ($letter == "z") {
                                        $letter_value_now -= date("L");
                                        $letter_value_unix -= date("L", $unix);
                                        $letter_value_unix_end -= date(
                                            "L",
                                            $unix_end
                                        );
                                    }
                                    $valid =
                                        $letter_value_now >=
                                        $letter_value_unix &&
                                        date(
                                            $letter,
                                            strtotime(
                                                "+" .
                                                    ($letter_value_unix_end -
                                                        $letter_value_unix -
                                                        ($letter_value_now >=
                                                            $letter_value_unix &&
                                                            $letter_value_now <=
                                                            $letter_value_unix_end
                                                            ? $letter_value_now -
                                                            $letter_value_unix
                                                            : 0)) .
                                                    " days"
                                            )
                                        ) <= $letter_value_unix_end;
                                    if ($valid && $hhmm) {
                                        $valid =
                                            $now >= $hhmm && $now <= $hhmm_end;
                                    }
                                    $repeat_id = $valid
                                        ? $letter_value_now
                                        : false;
                                }
                            } elseif ($criteria == "is-exactly") {
                                if ($condition == "every-day") {
                                    $valid = true;
                                    $repeat_id = date("z");
                                } else {
                                    $letter =
                                        $condition == "every-week"
                                        ? "w"
                                        : ($condition == "every-month"
                                            ? "d"
                                            : "z");
                                    $valid =
                                        $letter == "z"
                                        ? date($letter, $unix) -
                                        date("L", $unix) ==
                                        date($letter) - date("L")
                                        : date($letter, $unix) ==
                                        date($letter);
                                    $repeat_id = $valid ? date($letter) : false;
                                }
                            }
                            break;
                        }
                    }
                }
                break;
            case "include_urls":
            case "exclude_urls":
                $url = str_replace(
                    ["https://", "http://", "www."],
                    "",
                    sb_isset($_POST, "current_url", $_SERVER["HTTP_REFERER"])
                );
                $checks = explode(",", $conditions[$i][2]);
                $include = $conditions[$i][0] == "include_urls";
                if (!$include) {
                    $valid = true;
                }
                for ($j = 0; $j < count($checks); $j++) {
                    $checks[$j] = trim(
                        str_replace(
                            ["https://", "http://", "www."],
                            "",
                            $checks[$j]
                        )
                    );
                    if (
                        ($criteria == "contains" &&
                            strpos($url . "/", $checks[$j]) !== false) ||
                        ($criteria == "does-not-contain" &&
                            strpos($url, $checks[$j]) === false) ||
                        ($criteria == "is-exactly" && $checks[$j] == $url) ||
                        ($criteria == "is-not" && $checks[$j] != $url)
                    ) {
                        $valid = $include;
                        break;
                    }
                }
                break;
            case "user_type":
                if ($active_user) {
                    $user_type = sb_isset($active_user, "user_type");
                    $valid =
                        ($criteria == "is-visitor" &&
                            $user_type == "visitor") ||
                        ($criteria == "is-lead" && $user_type == "is-lead") ||
                        ($criteria == "is-user" && $user_type == "user") ||
                        ($criteria == "is-not-visitor" &&
                            $user_type != "visitor") ||
                        ($criteria == "is-not-lead" && $user_type != "lead") ||
                        ($criteria == "is-not-user" && $user_type != "user");
                } else {
                    $valid = true;
                    array_push($invalid_conditions, $conditions[$i]);
                }
                break;
            case "cities":
            case "languages":
            case "countries":
                if ($active_user) {
                    if ($conditions[$i][0] == "languages") {
                        $user_value = sb_get_user_extra(
                            $active_user_id,
                            "language"
                        );
                        if (!$user_value) {
                            $user_value = sb_get_user_extra(
                                $$active_user_id,
                                "browser_language"
                            );
                            if (strlen($user_value) > 2) {
                                $user_value = substr($user_value, 0, 2);
                            }
                        }
                    } elseif ($conditions[$i][0] == "cities") {
                        $user_value = sb_get_user_extra(
                            $active_user_id,
                            "location"
                        );
                        if ($user_value) {
                            $user_value = substr(
                                $user_value,
                                0,
                                strpos($user_value, ",")
                            );
                        } else {
                            $user_value = sb_get_user_extra(
                                $active_user_id,
                                "city"
                            );
                        }
                    } else {
                        $user_value = sb_get_user_extra(
                            $active_user_id,
                            "country_code"
                        );
                        if (!$user_value) {
                            $user_value = sb_get_user_extra(
                                $active_user_id,
                                "country"
                            );
                            if (!$user_value) {
                                $user_value = sb_get_user_extra(
                                    $active_user_id,
                                    "location"
                                );
                                if ($user_value) {
                                    $user_value = trim(
                                        substr(
                                            $user_value,
                                            strpos($user_value, ",")
                                        )
                                    );
                                }
                            }
                            if ($user_value) {
                                $countries = json_decode(
                                    file_get_contents(
                                        SB_PATH .
                                            "/resources/json/countries.json"
                                    ),
                                    true
                                );
                                if (isset($countries[$user_value])) {
                                    $user_value = $countries[$user_value];
                                } elseif (strlen($user_value) > 2) {
                                    $user_value = substr($user_value, 0, 2);
                                }
                            }
                        }
                    }
                    if ($user_value) {
                        $user_value = strtolower(trim($user_value));
                        $condition_values = explode(",", $criteria);
                        for ($j = 0; $j < count($condition_values); $j++) {
                            if (
                                strtolower(trim($condition_values[$j])) ==
                                $user_value
                            ) {
                                $valid = true;
                                break;
                            }
                        }
                    }
                } else {
                    $valid = true;
                    array_push($invalid_conditions, $conditions[$i]);
                }
                break;
            case "returning_visitor":
                $is_first_visitor = $criteria == "first-time-visitor";
                if ($active_user) {
                    $times = sb_db_get(
                        "SELECT creation_time, last_activity FROM sb_users WHERE id = " .
                            $active_user_id
                    );
                    $difference =
                        strtotime($times["last_activity"]) -
                        strtotime($times["creation_time"]);
                    $valid = $is_first_visitor
                        ? $difference < 86400
                        : $difference > 86400;
                } elseif ($is_first_visitor) {
                    $valid = true;
                }
                break;
            case "repeat":
                $valid = true;
                break;
            default:
                $valid = true;
                array_push($invalid_conditions, $conditions[$i]);
                break;
        }
        if (!$valid) {
            break;
        }
    }
    if (
        $valid &&
        !sb_automations_is_sent($active_user_id, $automation, $repeat_id)
    ) {
        // Check user details conditions
        if (
            $automation["type"] == "emails" &&
            (!$active_user || empty($active_user["email"]))
        ) {
            array_push($invalid_conditions, ["user_email"]);
        } elseif (
            $automation["type"] == "sms" &&
            !sb_get_user_extra($active_user_id, "phone")
        ) {
            array_push($invalid_conditions, ["user_phone"]);
        }

        // Return the result
        return ["conditions" => $invalid_conditions, "repeat_id" => $repeat_id];
    }
    return false;
}

function sb_automations_run($automation, $validate = false)
{
    $active_user = sb_get_active_user();
    $response = false;
    if ($validate) {
        $validation = sb_automations_validate($automation);
        if (!$validation || count($validation["conditions"]) > 0) {
            return false;
        }
    }
    if ($active_user) {
        $active_user_id = $active_user["id"];
        if (sb_automations_is_sent($active_user_id, $automation)) {
            return false;
        }
        switch ($automation["type"]) {
            case "messages":
                $response = sb_send_message(
                    sb_get_bot_id(),
                    sb_get_last_conversation_id_or_create($active_user_id, 3),
                    $automation["message"],
                    [],
                    3,
                    '{ "event": "open-chat" }'
                );
                sb_reports_update("message-automations");
                break;
            case "emails":
                $response = empty($active_user["email"])
                    ? false
                    : sb_email_send(
                        $active_user["email"],
                        sb_merge_fields($automation["subject"]),
                        sb_merge_fields(
                            sb_email_default_parts(
                                $automation["message"],
                                $active_user_id
                            )
                        )
                    );
                sb_reports_update("email-automations");
                break;
            case "sms":
                $phone = sb_get_user_extra($active_user_id, "phone");
                $response = $phone
                    ? sb_send_sms(
                        sb_merge_fields($automation["message"]),
                        $phone,
                        false
                    )
                    : false;
                sb_reports_update("sms-automations");
                break;
            default:
                trigger_error(
                    "Invalid automation type in sb_automations_run()"
                );
                return false;
        }
        $history = sb_get_external_setting("automations-history", []);
        $history_value = [$active_user["id"], $automation["id"]];
        if (count($history) > 10000) {
            $history = array_slice($history, 1000);
        }
        if (isset($automation["repeat_id"])) {
            array_push($history_value, $automation["repeat_id"]);
        }
        if ($response) {
            array_push($history, $history_value);
        }
        sb_save_external_setting("automations-history", $history);
    }
    return $response;
}

// function sb_automations_is_sent(
//     $active_user_id,
//     $automation,
//     $repeat_id = false
// ) {
//     $history = sb_get_external_setting("automations-history", []);
//     if ($active_user_id) {
//         for ($x = 0, $length = count($history); $x < $length; $x++) {
//             if (
//                 $history[$x][0] == $active_user_id &&
//                 $history[$x][1] == $automation["id"] &&
//                 (!$repeat_id ||
//                     (count($history[$x]) > 2 && $history[$x][2] == $repeat_id))
//             ) {
//                 return true;
//             }
//         }
//     }
//     return false;
// }


function steambox_pusher()
{
    return defined("SB_CLOUD");
}


?>