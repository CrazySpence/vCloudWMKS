<?php
/* vCloud WMKS example
 * This will connect to a vm you specify in the URL and display a console
 * Do not use the URL method in production it is only meant as an example
 */

if(isset($_GET["vmName"]) && isset($_GET["vApp"]) && isset($_GET["org"])) {
    $vmName    = htmlspecialchars_decode($_GET["vmName"]);
    $vmx       = "";
    $ticket    = "";
    $hostname  = "";
    $port      = "";
    $vApp      = htmlspecialchars_decode($_GET["vApp"]);
    $orgName   = htmlspecialchars_decode($_GET["org"]);
    $ticketObj = getMKS($orgName,$vApp,$vmName);
    if($ticketObj != NULL) {
        $ticket = $ticketObj->getTicket();
        $vmx = $ticketObj->getVmx();
        $hostname = $ticketObj->getHost();
        $port = $ticketObj->getPort();
    } else {
        echo "No ticket generated";
        exit(1);
    }
} else {
    echo "No parameters http://testurl/index.php?org=testorg&vApp=test%20vapp&vmName=testVm";
    exit(1);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="ISO-8859-1">
    <title><?php echo $vmName; ?> Console</title>
    <link href="wmks/css/wmks-all.css" rel="stylesheet" type="text/css" />
    <script src="support/javascript/jquery/jquery-1.7.2.min.js"></script>
    <script src="support/javascript/jquery/jquery-ui.1.8.16.min.js"></script>
    <script type="text/javascript" src="wmks/wmks.min.js" type="text/javascript"></script>
    <script>
        $(document).ready(function() {
            var wmks = WMKS.createWMKS("wmksContainer", {
                enableUint8Utf8: true,
                VCDProxyHandshakeVmxPath: "<?php echo $vmx; ?>",
            })
                .register(WMKS.CONST.Events.CONNECTION_STATE_CHANGE,
                    function (event, data) {
                        if (data.state == WMKS.CONST.ConnectionState.CONNECTED) {
                            console.log("connection state change : connected");
                        }
                    });
            wmks.connect("wss://<?php echo $hostname; ?>/<?php echo $port; ?>;<?php echo $ticket; ?>");
            console.log(wmks.getConnectionState());
        });
    </script >

</head>
<body>
<div  id="wmksContainer"  style="position:absolute;width:100%;height:100%"></div>
</body>
</html>

<?php
function getMKS($orgName,$vApp,$vm)
{
    //This function will get a vCloud ticket object from the vm in vapp from org
    require_once 'config.php';

    // Initialize parameters
    $httpConfig = array('ssl_verify_peer' => false, 'ssl_verify_host' => false);

    // login
    $service = VMware_VCloud_SDK_Service::getService();
    $service->login($server, array('username' => $user, 'password' => $pswd), $httpConfig, $sdkVersion);

    $orgRefs = $service->getOrgRefs($orgName);
    if (!empty($orgRefs)) {
        $sdkOrg = $service->createSDKObj($orgRefs[0]);
        $vdcRefs = $sdkOrg->getVdcRefs();
        if (!empty($vdcRefs)) {
            foreach ($vdcRefs as $vdcRef) {
                $sdkVdc = $service->createSDKObj($vdcRef);
                $vappRefs = $sdkVdc->getVAppRefs();
                if (!empty($vappRefs)) {
                    foreach ($vappRefs as $vappRef) {
                        if($vappRef->get_name() == $vApp) {
                            $sdkvApp = $service->createSDKObj($vappRef);
                            $vmRefs = $sdkvApp->getContainedVmRefs();
                            foreach ($vmRefs as $vmRef) {
                                if ($vmRef->get_name() == $vm) {
                                    $sdkVm = $service->createSDKObj($vmRef);
                                    $ticketObj = $sdkVm->acquireMksTicket();
                                    return $ticketObj;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // log out
    $service->logout();
}
?>
