<?php
ini_set('session.cookie_httponly', true);
session_start();
session_regenerate_id(true);
require_once '../config.php';
require_once '../function.php';
require_once '../botapi.php';

$pepper = defined('APP_PEPPER') ? APP_PEPPER : '';

function hashAdminPassword($password, $pepper)
{
    $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    return password_hash($password . $pepper, $algo);
}

function verifyAdminPassword($password, $pepper, $storedHash)
{
    if (password_verify($password . $pepper, $storedHash)) {
        return true;
    }

    if ($password === $storedHash) {
        return 'rehash';
    }

    return false;
}

$allowed_ips = select("setting","*",null,null,"select");

$user_ip = $_SERVER['REMOTE_ADDR'];
$admin_ids = select("admin", "id_admin",null,null,"FETCH_COLUMN");
$check_ip = $allowed_ips['iplogin'] == $user_ip ? true:false;
$texterrr = "";
$_SESSION["user"] = null;

if (isset($_POST['login'])) {
    $now = time();
    if (!isset($_SESSION['login_attempts']) || !is_array($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = ['count' => 0, 'first' => $now];
    }
    if (($now - $_SESSION['login_attempts']['first']) > 300) {
        $_SESSION['login_attempts'] = ['count' => 0, 'first' => $now];
    }
    if ($_SESSION['login_attempts']['count'] >= 5) {
        $texterrr = 'تعداد تلاش‌های ورود زیاد شد؛ لطفاً چند دقیقه بعد دوباره امتحان کنید.';
    } else {
        $username = htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8');
        $password = htmlspecialchars($_POST['password'], ENT_QUOTES, 'UTF-8');
        $query = $pdo->prepare("SELECT * FROM admin WHERE username=:username");
        $query->bindParam("username", $username, PDO::PARAM_STR);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            $texterrr = 'نام کاربری وارد شده اشتباه است!';
            $_SESSION['login_attempts']['count']++;
        } else {
            $verifyStatus = verifyAdminPassword($password, $pepper, $result["password"]);
            if ($verifyStatus === true || $verifyStatus === 'rehash') {
                if ($verifyStatus === 'rehash' || password_needs_rehash($result["password"], defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT)) {
                    $hashedPassword = hashAdminPassword($password, $pepper);
                    $updateStmt = $pdo->prepare("UPDATE admin SET password = :password WHERE username = :username");
                    $updateStmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
                    $updateStmt->bindParam(':username', $username, PDO::PARAM_STR);
                    $updateStmt->execute();
                }
                foreach ($admin_ids as $admin) {
                    $texts = "ورود جدید به پنل تحت وب: کاربر با نام کاربری {$username} وارد شد";
                    sendmessage($admin, $texts, null, 'html');
                }
                $_SESSION["user"] = $result["username"];
                $_SESSION['login_attempts'] = ['count' => 0, 'first' => $now];
                header('Location: index.php');
            } else {
                $texterrr =  'رمز عبور اشتباه است.';
                $_SESSION['login_attempts']['count']++;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="Mosaddek">
    <meta name="keyword" content="FlatLab, Dashboard, Bootstrap, Admin, Template, Theme, Responsive, Fluid, Retina">
    <link rel="shortcut icon" href="img/favicon.html">

    <title>ورود به پنل مدیریت</title>

    <!-- Bootstrap core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-reset.css" rel="stylesheet">
    <!--external css-->
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <!-- Custom styles for this template -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 tooltipss and media queries -->
    <!--[if lt IE 9]>
    <script src="js/html5shiv.js"></script>
    <script src="js/respond.min.js"></script>
    <![endif]-->
</head>

  <body class="login-body">
    <div class="container">
        <?php if(!$check_ip){?>
        <div class="error-card">
            
            <div class="error-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm0-2a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm-1-5h2v2h-2v-2zm0-8h2v6h-2V7z"/>
                </svg>
            </div>
            
            <h1>دسترسی از این IP مجاز نیست</h1>
            <p>آی‌پی شما برای ورود به این پنل مجاز نیست. لطفاً با مدیر سیستم برای اضافه کردن آی‌پی هماهنگ کنید یا مقدار iplogin را به‌روز کنید.</p>
            
            <div class="ip-address" id="user-ip"><?php echo $user_ip; ?></div>
        </div>
        <?php } ?>
        <?php if($check_ip){?>
      <form method="post" class="form-signin" action="/panel/login.php">
        <h2 class="form-signin-heading">ورود به پنل مدیریت</h2>
        <div class="login-wrap">
            <p><?php echo $texterrr; ?></p>
            <input type="text" name ="username" class="form-control" placeholder="نام کاربری" autofocus>
            <input type="password" name = "password" class="form-control" placeholder="رمز عبور">
            <button class="btn btn-lg btn-login btn-block"  name="login" type="submit">ورود</button>
        </div>

      </form>
      <?php } ?>
    </div>


  </body>
</html>
