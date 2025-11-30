<?php
session_start();
require_once '../config.php';
require_once '../botapi.php';
require_once '../function.php';

$query = $pdo->prepare("SELECT * FROM admin WHERE username=:username");
$query->bindParam("username", $_SESSION["user"], PDO::PARAM_STR);
$query->execute();
$result = $query->fetch(PDO::FETCH_ASSOC);

$targetId = $_GET["id"] ?? null;
$query = $pdo->prepare("SELECT * FROM user WHERE id=:id");
$query->bindParam("id", $targetId, PDO::PARAM_STR);
$query->execute();
$user = $query->fetch(PDO::FETCH_ASSOC);
$setting = select("setting","*",null,null);
$otherservice = select("topicid","idreport","report","otherservice","select")['idreport'];
$paymentreports = select("topicid","idreport","report","paymentreport","select")['idreport'];
if( !isset($_SESSION["user"]) || !$result ){
    header('Location: login.php');
    return;
}

$csrf_token = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

function isValidCsrfToken($token)
{
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if (!isValidCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        exit('Invalid CSRF token');
    }
    $postId = $_POST['id'] ?? $targetId;

    if(isset($_POST['status']) and $_POST['status']){
        if($_POST['status'] == "block"){
            $textblok = "UcOO�O\"O� O\"O O�UOO_UO O1O_O_UO
{$postId}  O_O� O�O\"OO� U.O3O_U^O_ U_O�O_UOO_ 

OO_U.UOU+ U.O3O_U^O_ UcU+U+O_U� : U_U+U, O�O-O� U^O"
U+OU. UcOO�O\"O�UO  : {$_SESSION['user']}";
        if (strlen($setting['Channel_Report']) > 0) {
            telegram('sendmessage',[
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $otherservice,
                'text' => $textblok,
                'parse_mode' => "HTML",
                'reply_markup' => $Response
            ]);
        }
        }else{
            sendmessage($postId,"�o3�,? O-O3OO\" UcOO�O\"O�UO O'U.O OO� U.O3O_U^O_UO OrOO�O� O'O_ �o3�,?
OUcU+U^U+ U.UOO�U^OU+UOO_ OO� O�O\"OO� OO3O�U?OO_U� UcU+UOO_ ", null, 'HTML');
        }
        update("user", "User_Status", $_POST['status'], "id", $postId);
        header("Location: user.php?id={$postId}");
        exit;
    }

    if(isset($_POST['priceadd']) and $_POST['priceadd']){
        $priceadd = number_format($_POST['priceadd'],0);
        $textadd = "dY'Z UcOO�O\"O� O1O�UOO� U.O\"U,O� {$priceadd} O�U^U.OU+ O\"U� U.U^O�U^O_UO UcUOU? U_U^U, O�OU+ OOOU?U� U_O�O_UOO_.";
        sendmessage($postId, $textadd, null, 'HTML');
         if (strlen($setting['Channel_Report']) > 0) {
            $textaddbalance = "dY\"O UOUc OO_U.UOU+ U.U^O�U^O_UO UcOO�O\"O� O�O OO� U_U+U, O�O-O� U^O\" OU?O�OUOO' O_OO_U� OO3O� :
        
dY�� OO�U,OO1OO� OO_U.UOU+ OU?O�OUOO' O_U�U+O_U� U.U^O�U^O_UO : 
U+OU. UcOO�O\"O�UO : {$_SESSION['user']}
dY` OO�U,OO1OO� UcOO�O\"O� O_O�UOOU?O� UcU+U+O_U� U.U^O�U^O_UO :
O�UOO_UO O1O_O_UO UcOO�O\"O�  : {$postId}
U.O\"U,O� U.U^O�U^O_UO : $priceadd";
            telegram('sendmessage',[
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $paymentreports,
                'text' => $textaddbalance,
                'parse_mode' => "HTML"
            ]);
        }
        $value = intval($user['Balance'])+intval($_POST['priceadd']);
        update("user", "Balance", $value, "id", $postId);
        header("Location: user.php?id={$postId}");
        exit;
    }

    if(isset($_POST['pricelow']) and $_POST['pricelow']){
        $priceadd = number_format($_POST['pricelow'],0);
         if (strlen($setting['Channel_Report']) > 0) {
            $textaddbalance = "dY\"O UOUc OO_U.UOU+ U.U^O�U^O_UO UcOO�O\"O� O�O OO� U_U+U, O�O-O� U^O\" UcO3O� UcO�O_U� OO3O� :
        
dY�� OO�U,OO1OO� OO_U.UOU+ UcO3O� UcU+U+O_U� U.U^O�U^O_UO : 
U+OU. UcOO�O\"O�UO : {$_SESSION['user']}
dY` OO�U,OO1OO� UcOO�O\"O� :
O�UOO_UO O1O_O_UO UcOO�O\"O�  : {$postId}
U.O\"U,O� U.U^O�U^O_UO : $priceadd";
            telegram('sendmessage',[
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $paymentreports,
                'text' => $textaddbalance,
                'parse_mode' => "HTML"
            ]);
        }
        $value = intval($user['Balance'])-intval($_POST['pricelow']);
        update("user", "Balance", $value, "id", $postId);
        header("Location: user.php?id={$postId}");
        exit;
    }

    if(isset($_POST['agent']) and $_POST['agent']){
        update("user", "agent", $_POST['agent'], "id", $postId);
        header("Location: user.php?id={$postId}");
        exit;
    }

    if(isset($_POST['textmessage']) and $_POST['textmessage']){
        $messagetext = "dY\"� UOUc U_UOOU. OO� U.O_UOO�UOO� O\"O�OUO O'U.O OO�O3OU, O'O_.

U.O�U+ U_UOOU. : {$_POST['textmessage']}";
        sendmessage($postId, $messagetext, null, 'HTML');
         if (strlen($setting['Channel_Report']) > 0) {
            $textaddbalance = "dY\"O OO� O�O�UOU, U_U+U, O�O-O� U^O\" UOUc U_UOOU. O\"O�OUO UcOO�O\"O� OO�O3OU, O'O_
        
dY�� OO�U,OO1OO� OO_U.UOU+ OO�O3OU, UcU+U+O_U�  : 
U+OU. UcOO�O\"O�UO : {$_SESSION['user']}
dY` OO�U,OO1OO� OO�O3OU, :
O�UOO_UO O1O_O_UO UcOO�O\"O�  : {$postId}
U.O�U+ OO�O3OU, O'O_U� : {$_POST['textmessage']}";
            telegram('sendmessage',[
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $otherservice,
                'text' => $textaddbalance,
                'parse_mode' => "HTML"
            ]);
        }
        header("Location: user.php?id={$postId}");
        exit;
    }
}

$status_user = [
            'Active' => "U?O1OU,",
            'active' => "U?O1OU,",
            "block" => "O\"U,OUc",
][$user['User_Status']];
if($user['number'] == "none")$user['number'] ="O\"O_U^U+ O'U.OO�U� ";
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

    <title>U_U+U, U.O_UOO�UOO� O�O\"OO� U.UOO�O�O</title>

    <!-- Bootstrap core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-reset.css" rel="stylesheet">
    <!--external css-->
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/jquery-easy-pie-chart/jquery.easy-pie-chart.css" rel="stylesheet" type="text/css" media="screen"/>
    <link rel="stylesheet" href="css/owl.carousel.css" type="text/css">
    <!-- Custom styles for this template -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 tooltipss and media queries -->
    <!--[if lt IE 9]>
      <script src="js/html5shiv.js"></script>
      <script src="js/respond.min.js"></script>
    <![endif]-->
  </head>


<body>

    <section id="container" class="">
<?php include("header.php");
?>
        <!--main content start-->
        <section id="main-content">
            <section class="wrapper">
                <!-- page start-->
                <div class="row">
                    <aside class="profile-nav col-lg-3">
                        <section class="panel">
                            <div class="user-heading round">
                                <h1><?php echo $user['id'];?></h1>
                                <p><a style = "border:0;color:#fff;font-size:15px;" href = "https://t.me/<?php echo $user['username'];?>"><?php echo $user['username'];?></a></p>
                            </div>

                            <ul class="nav nav-pills nav-stacked">
                                <li class="active"><a href="profile.html"><i class="icon-user"></i>U_O�U^U?OUOU,</a></li>
                            </ul>

                        </section>
                    </aside>
                    <aside class="profile-info col-lg-9">
                        <section class="panel">
                            <div class="panel-body bio-graph-info">
                                <h1>OO�U,OO1OO� UcOO�O\"O�</h1>
                                <div class="row">
                                    <div class="bio-row">
                                        <p><span>U+OU. UcOO�O\"O�UO</span>: <?php echo $user['username'];?></p>
                                    </div>
                                    <div class="bio-row">
                                        <p><span>U.O-O_U^O_UOO� O�O3O� </span>: <?php echo $user['limit_usertest'];?></p>
                                    </div>
                                    <div class="bio-row">
                                        <p><span>O'U.OO�U� U.U^O\"OUOU,  </span>: <?php echo $user['number'];?></p>
                                    </div>
                                    <div class="bio-row">
                                        <p><span>U.U^O�U^O_UO</span>: <?php echo number_format($user['Balance']);?></p>
                                    </div>
                                    <div class="bio-row">
                                        <p><span>U^OO1UOO� UcOO�O\"O� </span>: <?php echo $status_user;?></p>
                                    </div>
                                    <div class="bio-row">
                                        <p><span>U+U^O1 UcOO�O\"O� </span>: <?php echo $user['agent'];?></p>
                                    </div>
                                    <div class="bio-row">
                                        <p><span>O�O1O_OO_ O�UOO�U.O�U.U^O1U�  </span>: <?php echo $user['affiliatescount'];?> U+U?O�</p>
                                    </div>
                                    <div class="bio-row">
                                        <p><span>O�UOO�U.O�U.U^O1U� UcOO�O\"O�  </span>: <?php echo $user['affiliates'];?></p>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <section class="panel">
                            <header class="panel-heading">
                                U.O_UOO�UOO� UcOO�O\"O�
                            </header>
                            <div class="panel-body">
                                <form method="post" action="user.php?id=<?php echo $user['id'];?>" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token;?>">
                                    <input type="hidden" name="id" value="<?php echo $user['id'];?>">
                                    <input type="hidden" name="status" value="block">
                                    <button type="submit" class="btn btn-default btn-sm">U.O3O_U^O_ UcO�O_U+ UcOO�O\"O�</button>
                                </form>
                                <form method="post" action="user.php?id=<?php echo $user['id'];?>" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token;?>">
                                    <input type="hidden" name="id" value="<?php echo $user['id'];?>">
                                    <input type="hidden" name="status" value="active">
                                    <button type="submit" class="btn btn-success  btn-sm">O�U?O1 U.O3O_U^O_UO UcOO�O\"O�</button>
                                </form>
                                <a href="#addbalance" data-toggle="modal" class="btn btn-info  btn-sm">OU?O�OUOO' U.U^O�U^O_UO</a>
                                <a href="#lowbalance" data-toggle="modal" class="btn btn-warning  btn-sm">UcU. UcO�O_U+ U.U^O�U^O_UO</a>
                                <a href="#changeagent" data-toggle="modal" class="btn btn-primary  btn-sm">O�O�UOUOO� U+U^O1 UcOO�O\"O�</a>
                                <form method="post" action="user.php?id=<?php echo $user['id'];?>" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token;?>">
                                    <input type="hidden" name="id" value="<?php echo $user['id'];?>">
                                    <input type="hidden" name="agent" value="f">
                                    <button type="submit" class="btn btn-danger  btn-sm">O-O�U? U+U.OUOU+O_U�</button>
                                </form>
                                <a href="#sendmessage" data-toggle="modal" class="btn btn-info  btn-sm">OO�O3OU, U_UOOU. O\"U� UcOO�O\"O�</a>
                            </div>
                        </section>
                    </aside>
                    <div aria-hidden="true" aria-labelledby="myModalLabel" role="dialog" tabindex="-1" id="addbalance" class="modal fade">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <button aria-hidden="true" data-dismiss="modal" class="close" type="button">A-</button>
                                                <h4 class="modal-title">OOOU?U� UcO�O_U+ U.U^O�U^O_UO</h4>
                                            </div>
                                            <div class="modal-body">
                                                <form action = "user.php?id=<?php echo $user['id'];?>" method = "POST" class="form-horizontal" role="form">
                                                    <div class="form-group">
                                                    <input type="hidden" value = "<?php echo $user['id'];?>" name = "id" class="form-control" id="inputEmail4">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token;?>">
                                                        <label for="inputEmail1" class="col-lg-2 control-label">U.O\"U,O�</label>
                                                        <div class="col-lg-10">
                                                            <input type="number" name = "priceadd" class="form-control" id="inputEmail4" placeholder="U.U^O�U^O_UO UcU� U.UO OrU^OU�UOO_ OU?O�OUOO' O_OO_U� O'U^O_ O�O U^OO�O_ U+U.OUOUOO_">
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="col-lg-offset-2 col-lg-10">
                                                            <button type="submit" class="btn btn-default">OU?O�OUOO' U.U^O�U^O_UO</button>
                                                        </div>
                                                    </div>
                                                </form>

                                            </div>

                                        </div>
                                    </div>
                                </div>
                    <div aria-hidden="true" aria-labelledby="myModalLabel" role="dialog" tabindex="-1" id="sendmessage" class="modal fade">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <button aria-hidden="true" data-dismiss="modal" class="close" type="button">A-</button>
                                                <h4 class="modal-title">OO�O3OU, U_UOOU. O\"U� UcOO�O\"O�</h4>
                                            </div>
                                            <div class="modal-body">
                                                <form action = "user.php?id=<?php echo $user['id'];?>" method = "POST" class="form-horizontal" role="form">
                                                    <div class="form-group">
                                                    <input type="hidden" value = "<?php echo $user['id'];?>" name = "id" class="form-control" id="iduser">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token;?>">
                                                        <label for="text" class="col-lg-2 control-label">U.O�U+ U_UOOU.</label>
                                                        <div class="col-lg-10">
                                                            <input type="text" name = "textmessage" class="form-control" id="text" placeholder="U.O�U+ U_UOOU. OrU^O_ O�O O\"U+U^UOO3UOO_">
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="col-lg-offset-2 col-lg-10">
                                                            <button type="submit" class="btn btn-default">OO�O3OU, U_UOOU.</button>
                                                        </div>
                                                    </div>
                                                </form>

                                            </div>

                                        </div>
                                    </div>
                                </div>
                    <div aria-hidden="true" aria-labelledby="myModalLabel" role="dialog" tabindex="-1" id="lowbalance" class="modal fade">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <button aria-hidden="true" data-dismiss="modal" class="close" type="button">A-</button>
                                                <h4 class="modal-title">UcU. UcO�O_U+ U.U^O�U^O_UO</h4>
                                            </div>
                                            <div class="modal-body">
                                                <form action = "user.php?id=<?php echo $user['id'];?>" method = "POST" class="form-horizontal" role="form">
                                                    <div class="form-group">
                                                    <input type="hidden" value = "<?php echo $user['id'];?>" name = "id" class="form-control" id="inputEmail4">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token;?>">
                                                        <label for="inputEmail1" class="col-lg-2 control-label">U.O\"U,O�</label>
                                                        <div class="col-lg-10">
                                                            <input type="number" name = "pricelow" class="form-control" id="inputEmail4" placeholder="U.U^O�U^O_UO UcU� U.UO OrU^OU�UOO_ UcO3O� O'U^O_ O�O U^OO�O_ U+U.OUOUOO_">
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="col-lg-offset-2 col-lg-10">
                                                            <button type="submit" class="btn btn-default">UcO3O� U.U^O�U^O_UO</button>
                                                        </div>
                                                    </div>
                                                </form>

                                            </div>

                                        </div>
                                    </div>
                                </div>
                    <div aria-hidden="true" aria-labelledby="myModalLabel" role="dialog" tabindex="-1" id="changeagent" class="modal fade">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <button aria-hidden="true" data-dismiss="modal" class="close" type="button">A-</button>
                                                <h4 class="modal-title">O�O�UOUOO� U+U^O1 U+U.OUOU+O_U�</h4>
                                            </div>
                                            <div class="modal-body">
                                                <form action = "user.php?id=<?php echo $user['id'];?>" method = "POST" class="form-horizontal" role="form">
                                                    <div class="form-group">
                                                    <input type="hidden" value = "<?php echo $user['id'];?>" name = "id" class="form-control" id="inputEmail4">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token;?>">
                                                        <label for="inputEmail1" class="col-lg-2 control-label">U+U^O1 UcOO�O\"O�UO</label>
                                                        <div class="col-lg-10">
                                            <select style ="padding:0;" name = "agent" class="form-control input-sm m-bot15">
                                                <option value = "f">UcOO�O\"O� O1OO_UO</option>
                                                <option value = "n">U+U.OUOU+O_U� U.O1U.U^U,UO</option>
                                                <option value = "n2">U+U.OUOU+O_U� U_UOO'O�U?O�U�</option>
                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="col-lg-offset-2 col-lg-10">
                                                            <button type="submit" class="btn btn-default">O�O�UOUOO� U+U^O1 UcOO�O\"O�UO</button>
                                                        </div>
                                                    </div>
                                                </form>

                                            </div>

                                        </div>
                                    </div>
                                </div>
                </div>

                <!-- page end-->
            </section>
        </section>
        <!--main content end-->
    </section>

    <!-- js placed at the end of the document so the pages load faster -->
    <script src="js/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.scrollTo.min.js"></script>
    <script src="js/jquery.nicescroll.js" type="text/javascript"></script>
    <script src="assets/jquery-knob/js/jquery.knob.js"></script>

    <!--common script for all pages-->
    <script src="js/common-scripts.js"></script>

    <script>

        //knob
        $(".knob").knob();

  </script>


</body>
</html>
