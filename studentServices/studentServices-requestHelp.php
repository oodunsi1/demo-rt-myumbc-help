<?php
$SERVICE_AREA = "Academic & Student Services";
$SERVICE_HEADER = "studentServices";

//$devMode = "DEV";
//$devMode = "BETA";
$devMode = "PRD";

require_once("../../Utils/UmbcRTApi.php");
require_once("../../Utils/UmbcRTBoxApi.php");
require_once("../../Utils/UMBCUtils.php");
require_once("../../Utils/RTUnauthENV.php");
require_once("./" . $SERVICE_HEADER . "Modules/reqModule.template.php");

spl_autoload_register(function($className) {
    include "./" . $SERVICE_HEADER . "Modules/$className.class.php";
});

session_start();
$_SESSION['activeUser'] = true;

Global $devMode;
Global $process_mode;
Global $RT;
Global $req;
Global $authenticated;
Global $reqModule;
Global $reqType;

define("MODE_START", 1);
define("MODE_FINISH", 2);

$process_mode = MODE_START;

if (isset($_POST['reqSubmit']) && !empty($_POST))
{
    $process_mode = MODE_FINISH;
}

$RT             = "";
$req            = "";
$authenticated  = "";

$reqCategoryList        = array();
$reqCategoryInfoList    = array();
$reqTypeList            = array();
$reqAuthList            = array();
$reqTagList             = array();
$reqModuleList          = array();

$found      = "";
$embedMode  = "";
$tagCF      = "";

if ($process_mode == MODE_FINISH)
{
    $reqType = $_POST['reqType'];
}
else
{
    $reqType = filter_var(base64_decode($_GET['r']), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
}
$embedMode = filter_var($_GET['embed'], FILTER_SANITIZE_NUMBER_INT);

$returnTo = "https://rtforms.umbc.edu/rt_myumbcHelpPage/" . $SERVICE_HEADER . "/" . $SERVICE_HEADER . "-requestHelp.php?r=" . base64_encode($reqType);

$tagCF = '4050';
if ($devMode == "DEV")
{
    $tagCF = '4010';
}

$RT = new UmbcRTApi();
if ($devMode == "DEV")
{
    $RT->setProductionFlag(false);
}

$config = fopen("../helpUtils/umbchelp_reqtype_config.csv", 'r');

// parse request types and load all existing modules
$headers = fgetcsv($config);
while ($reqLine = fgetcsv($config))
{
    $reqServiceArea = iconv("UTF-8", "ASCII//TRANSLIT", array_shift($reqLine));
    $reqCategory = iconv("UTF-8", "ASCII//TRANSLIT", array_shift($reqLine));
    $reqName = iconv("UTF-8", "ASCII//TRANSLIT", array_shift($reqLine));
    $reqDesc = iconv("UTF-8", "ASCII//TRANSLIT", array_shift($reqLine));
    $reqAuth = iconv("UTF-8", "ASCII//TRANSLIT", array_shift($reqLine));
    $reqAction = iconv("UTF-8", "ASCII//TRANSLIT", array_shift($reqLine));
    $reqURL = iconv("UTF-8", "ASCII//TRANSLIT", array_shift($reqLine));
    $reqClassName = iconv("UTF-8", "ASCII//TRANSLIT", array_shift($reqLine));

    if ($reqServiceArea == $SERVICE_AREA)
    {
        if (isset($reqCategoryList[$reqCategory]))
        {
            array_push($reqCategoryList[$reqCategory], $reqName);
        }
        else
        {
            $reqCategoryList[$reqCategory] = array($reqName);
        }
        $reqTypeList[$reqName] = array($reqDesc, $reqAuth, $reqAction, $reqURL, $reqClassName, $reqCategory);

        $reqAuthList[$reqName] = $reqAuth;
    }
}
fclose($config);

$reqTypeList["I'm not sure / Other"] = array("", 'Y', "Custom", "N/A", "genSupportModule", "Other");

// Load all defined modules
$reqModuleList = array();
$reqNames = array_keys($reqTypeList);

if (array_search($reqType, array_keys($reqTypeList)) !== false)
{
    if ($reqTypeList[$reqType][2] == "Embed" || $reqTypeList[$reqType][2] == "Custom")
    {
        if (!empty($reqTypeList[$reqType][4]) && $reqTypeList[$reqType][4] != "N/A")
        {
            $className = $reqTypeList[$reqType][4];
            require_once("./" . $SERVICE_HEADER . "Modules/$className.class.php");
            $reqModuleList[$reqType] = new $className($reqType, $reqTypeList[$reqType][0], $reqTagsList[$reqType]);
        }
    }
}
// Load Category Info
$config = fopen("./" . $SERVICE_HEADER . "Config/" . $SERVICE_HEADER . "_CategoryInfo.csv", 'r');
$headers = fgetcsv($config);
while ($reqLine = fgetcsv($config))
{
    $reqCategory = iconv("UTF-8", "ASCII//TRANSLIT", array_shift($reqLine));
    $reqCorrespondingType = iconv("UTF-8", "ASCII//TRANSLIT", array_shift($reqLine));
    $reqMenu = iconv("UTF-8", "ASCII//TRANSLIT", array_shift($reqLine));
    $reqClickAction = iconv("UTF-8", "ASCII//TRANSLIT", array_shift($reqLine));
    $reqClickArgs = iconv("UTF-8", "ASCII//TRANSLIT", array_shift($reqLine));

    $temp = array($reqMenu, $reqClickAction, $reqClickArgs);

    if (!isset($reqCategoryInfoList[$reqCategory]))
    {
        $reqCategoryInfoList[$reqCategory] = array();
    }

    if (isset($reqCategoryInfoList[$reqCategory][$reqCorrespondingType]))
    {
        array_push($reqCategoryInfoList[$reqCategory][$reqCorrespondingType], $temp);
    }
    else
    {
        $reqCategoryInfoList[$reqCategory][$reqCorrespondingType] = array($temp);
    }
}
fclose($config);

$authenticated = false;

if ($process_mode == MODE_FINISH || (array_search($reqType, array_keys($reqTypeList)) !== false))
{
    $auth = $reqTypeList[$reqType][1];
    if ($auth == "Y")
    {
        if ($embedMode == 1)
        {
            $req = UMBCUtils::getShibbolethInfo(false, $returnTo);
        }
        else
        {
            $req = UMBCUtils::getShibbolethInfo(true);
        }
        $authenticated = true;
    }
}
else
{
    header("Location: ./" . $SERVICE_HEADER . "-support.php");
    exit();
}

if ($process_mode == MODE_FINISH)
{
    if (!$authenticated)
    {
        $authFail = 0;
        $url = "https://www.google.com/recaptcha/api/siteverify";

        $privateKey = getenv("ENV_GRECAPTCHA_PKEY", true);

        $response = file_get_contents($url."?secret=".$privateKey."&response=".$_POST['g-recaptcha-response']."&remoteip=".$_SERVER['REMOTE_ADDR']);

        $data = json_decode($response);
        if (isset($data->success) && $data->success == true)
        {
            $authFail = 1;
        }
        else
        {
            $authFail = -1;
        }

        if ($authFail < 0)
        {
        ?>
        <div id="spot-container">
            <p style="color: #f00; font-size: 20px">
                We're sorry, but something has gone wrong.<br />
                reCAPTCHA validation failed.  Either the request failed, or the service is unavailable.<br />
                You may try to submit again, or contact UMBC DoIT for support.
            </p>
        </div>
        <?php
            $process_mode = MODE_START;
        }
    }
}
?>

<!DOCTYPE HTML>
<html>
<head>
<?php
if ($devMode == "BETA" || $devMode == "PRD")
{
?>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-PZ7WJ674');</script>
    <!-- End Google Tag Manager -->
<?php
}
?>
    <link rel="apple-touch-icon" sizes="180x180" href="https://sites.umbc.edu/wp-content/themes/sights/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="https://sites.umbc.edu/wp-content/themes/sights/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://sites.umbc.edu/wp-content/themes/sights/images/favicon-16x16.png">
    <meta http-equiv="Content-Type" content="text/html; charset=windows-1252" />
    <meta name="keywords" content="UMBC, University of Maryland Baltimore County, Univ. of MD Balt. Co." />
    <meta name="description" content="UMBC An Honors University in Maryland" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title> UMBC Help - <?php echo $SERVICE_AREA; ?> - UMBC: An Honors University in Maryland </title>

    <script src="https://code.jquery.com/jquery-3.7.0.js" integrity="sha256-JlqSTELeR4TLqP0OG9dxM7yDPqX1ox/HfgiSLBj8+kM=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<?php
if ($devMode == "DEV")
{
?>
    <link rel="stylesheet" href="../../Utils/css/UMBCBootstrap5_DEV.css" />
<?php
}
else
{
?>
    <link rel="stylesheet" href="../../Utils/css/UMBCBootstrap5.css" />
<?php
}
if (!$authenticated)
{
?>
    <script src="https://www.google.com/recaptcha/api.js"></script>
<?php
}
if ($process_mode == MODE_START)
{
    echo reqModule::js_formatCurrency();
    echo reqModule::js_checkCampusID();
    echo reqModule::js_outputFlowControl();
?>
    <script type="text/javascript">
    // Main form functions
<?php
if ($authenticated)
{
?>
    let authPreSelect = "<?php echo $authPreSelect;?>";
<?php
    $_SESSION['PreSelect'] = "";
}
?>
    let currentSelection = "";
    let fileStore = new DataTransfer();
    let totalFileSize = 0;

    $(document).ready(function()
    {
        updateFormControls();
<?php
    if ($authenticated)
    {
?>
        if (authPreSelect != "")
        {
            reqMakeSelect(authPreSelect);
        }
<?php
    }
?>
    });

    function validateInput()
    {
        let emailBehalf = document.getElementById("reqBehalfInfo_Email");
        let emailCC = document.getElementById("reqAltEmail");
        let target = "-group@umbc.edu";

        $(emailBehalf).removeClass("is-invalid");
        $(emailCC).removeClass("is-invalid");

        if (emailBehalf.value.toLowerCase().indexOf(target) > -1)
        {
            alert("Sorry, but Google Groups email addresses have been known to cause errors in RT."
                + "\nAs such, we have disallowed CC-ing them for the time being.");
            $(emailBehalf).addClass("is-invalid");
            return false;
        }

        if (emailCC.value.toLowerCase().indexOf(target) > -1)
        {
            alert("Sorry, but Google Groups email addresses have been known to cause errors in RT."
                + "\nAs such, we have disallowed CC-ing them for the time being.");
            $(emailCC).addClass("is-invalid");
            return false;
        }

        let type = document.getElementById("reqType").value;

        switch (type)
        {
            default:
            break;
        }

        <?php
if ($authenticated)
{
?>
        if (type)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
<?php
}
else
{
?>
        if (type.value == "")
        {
            if (captchaCheck())
            {
                return true;
            }
            else
            {
                return false;
            }
        }
	}
    function isCaptchaChecked() {
        return grecaptcha && grecaptcha.getResponse().length !== 0;
    }
    function captchaCheck() {
        if (isCaptchaChecked() == true)
        {
            return true;
        }
        else
        {
            alert('Please complete the captcha.');
            return false;
        }
    }
<?php
}
?>

    function fixURL(urlField)
    {
        var inURL = urlField.value;

        if (inURL)
        {
            var noProtocol = inURL.indexOf("://");
            if (noProtocol != -1)
            {
                inURL = inURL.slice(noProtocol+3);
            }
            urlField.value = "https://" + inURL;
        }
    }

    function reqCheckCampusID()
    {
        let campusID = document.getElementById("reqBehalfInfo_CampusID");
        let test = "";
        let newCampusID = "";
        if (campusID.value.length <= 2)
        {
            newCampusID = campusID.value;
            test = /[^a-zA-Z]/g;
            newCampusID = newCampusID.replace(test, "");
        }
        else
        {
            test = /[a-zA-Z].*[a-zA-Z]/;
            let cidLetters = campusID.value.match(test);
            if (cidLetters)
            {
                cidLetters = cidLetters[0].slice(0,2);
            }
            else
            {
                cidLetters = campusID.value.slice(0,2);
                campusID.value = campusID.value.slice(3);
            }

            test = /[^a-zA-Z]/g;
            cidLetters = cidLetters.replace(test, "");

            test = /[^0-9]/g;
            let replace = campusID.value.replace(test, "");

            newCampusID = cidLetters + replace;
        }

        newCampusID  = newCampusID.toUpperCase();
        campusID.value = newCampusID;

        if (newCampusID.length == 7)
        {
            reqGetLdapInfo("CampusID");
        }
    }
    function reqGetLdapInfo(source)
    {
        let lookupParam = document.getElementById("reqBehalfInfo_"+source).value;
        if (source == "CampusID")
        {
            lookupParam += "@umbc.edu";
        }
        let queryName = "ldap_ByEmail";

        let url = "../../Utils/ajaxLdapDBInfo_forRT.php?queryName=" + queryName;
        if (lookupParam.length > 0)
        {
            url += "&queryParms=" + lookupParam;
        }
        url += "&queryReturnFields=umbccampusid,mail,umbcpreferredgivenname,umbcpreferredsurname,umbcprimarygivenname,umbcprimarysurname";
        let jqxhr = $.ajax({
            url: url,
            method: "GET",
            //data: { id : 'test' },
            //dataType: "json"
        })
        .done(function(retData) {
            let jsonData = null;
            let char1 = retData.charCodeAt(0);
            try {
                if (char1 == 65279)
                {
                    jsonData = JSON.parse(retData.substr(1));
                }
                else
                {
                    jsonData = JSON.parse(retData);
                }

                let httpStatus = jsonData.status;
                let numRows = jsonData.numrows;

                //if ((httpStatus == 200 && numRows>0) || (prefix == "student" && httpStatus == 410 && numRows > 0 && <?php echo ($isAdmin !== false ? "true" : "false"); ?>))
                if ((httpStatus == 200 && numRows>0))
                {
                    let dataRow = jsonData.rows[0];
                    //console.log(dataRow);
                    let ldap_campusid = reqSearchLdapRow(dataRow, "L.umbccampusid");
                    let ldap_email = reqSearchLdapRow(dataRow, "L.mail");

                    let ldap_firstName = reqSearchLdapRow(dataRow,"L.umbcpreferredgivenname");
                    let ldap_lastName = reqSearchLdapRow(dataRow,"L.umbcpreferredsurname");

                    if (ldap_firstName == null)
                    {
                        ldap_firstName = reqSearchLdapRow(dataRow,"L.umbcprimarygivenname");
                    }
                    if (ldap_lastName == null)
                    {
                        ldap_lastName = reqSearchLdapRow(dataRow,"L.umbcprimarysurname");
                    }

                    document.getElementById("reqBehalfInfo_FirstName").value = ldap_firstName;
                    document.getElementById("reqBehalfInfo_FirstName").readOnly = true;
                    document.getElementById("reqBehalfInfo_LastName").value = ldap_lastName;
                    document.getElementById("reqBehalfInfo_LastName").readOnly = true;
                    document.getElementById("reqBehalfInfo_Email").value = ldap_email;
                    document.getElementById("reqBehalfInfo_Email").readOnly = true;
                    document.getElementById("reqBehalfInfo_CampusID").value = ldap_campusid;
                    document.getElementById("reqBehalfInfo_CampusID").readOnly = true;

                    document.getElementById("reqLdapChangeHeader").innerHTML = "Account found: " + ldap_firstName + " " + ldap_lastName + " (" + ldap_campusid + ", " + ldap_email + ")";
                    showField("reqLdapChangeRow");
                }
                else
                {
                    document.getElementById("reqLdapChangeHeader").innerHTML = "No account found.  Please enter a valid UMBC email address or Campus ID.";
                    showField("reqLdapChangeRow");
                    /*document.getElementById(prefix+'InfoStatus').value='0';
                    infoElt.innerHTML = "Error searching for "+campusId;
                    document.getElementById(prefix+'Info').value='';  // Clear out campus ID input field*/
                }
            }
            catch(e)
            {
                alert(e); // error in the above string (in this case, yes)!
                /*document.getElementById(prefix+'InfoStatus').value='0';
                document.getElementById(prefix+'Info').value='';  // Clear out campus ID input field*/
            }
        })
        .fail(function(jqXHR, textStatus) {
            //console.log("updateInfoFromCampusId error-"+textStatus+" Status Code="+jqXHR.status); //+"  Response="+jqXHR.getResponseHeader() );
            /*document.getElementById(prefix+'InfoStatus').value='0';
            document.getElementById(prefix+'Info').value='';  // Clear out campus ID input field*/
        })
        .always(function(jqXHRa) {
            //console.log("updateInfoFromCampusId complete   ");
            //console.log("Always Status="+jqXHRa.status);
            //returnFromLongOp();
        });
    }
    function reqSearchLdapRow(dataRow, nameStr)
    {
        let retValue = null;
        for (let col in dataRow)
        {
            let dbFldName = dataRow[col].name;
            if (nameStr == dbFldName)
            {
                retValue = dataRow[col].value;
                break;
            }
        }
        return retValue;
    }
    function reqResetLdap()
    {
        document.getElementById("reqBehalfInfo_FirstName").value = "";
        document.getElementById("reqBehalfInfo_FirstName").readOnly = false;
        document.getElementById("reqBehalfInfo_LastName").value = "";
        document.getElementById("reqBehalfInfo_LastName").readOnly = false;
        document.getElementById("reqBehalfInfo_Email").value = "";
        document.getElementById("reqBehalfInfo_Email").readOnly = false;
        document.getElementById("reqBehalfInfo_CampusID").value = "";
        document.getElementById("reqBehalfInfo_CampusID").readOnly = false;
        hideField("reqLdapChangeRow");
    }

    function prepareSubmit()
    {
        $('#waitOnSubmit').modal({backdrop:"static"});
        $('#waitOnSubmit').modal("show");
    }

    // formatBytes from StackOverflow community
    // https://stackoverflow.com/questions/15900485/correct-way-to-convert-size-in-bytes-to-kb-mb-gb-in-javascript
    function formatBytes(bytes, decimals = 2)
    {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    function UpdateFile()
    {
        const MAX_FILE_SIZE = 25000000;
        const input = document.getElementById("reqAttachment");
        let paths = input.files;
        let outList = document.getElementById("reqAttachList");
        let filesAlreadyExist = (outList.childElementCount > 0) ? true : false;

        if (paths.length > 0)
        {
            let newFileSize = 0;
            for (let i = 0; i < paths.length; i++)
            {
                newFileSize += paths[i].size;
            }
            totalFileSize += newFileSize;

            if (totalFileSize <= MAX_FILE_SIZE)
            {
                let out = "";
                let numExistingFiles = fileStore.files.length;
                for (let i = 0; i < paths.length; i++)
                {
                    const file = paths[i];
                    fileStore.items.add(file);
                }
                input.files = fileStore.files;

                if (paths.length > 0 && !filesAlreadyExist)
                {
                    numExistingFiles = 0;
                    for (let i = 0; i < paths.length; i++)
                    {
                        let fileNum = i + 1;

                        if (i == paths.length - 1)
                        {
                            out += "<li id=\"lastFileRow\" class=\"list-group-item pe-0 py-0\">";
                        }
                        else
                        {
                            out += "<li class=\"list-group-item pe-0 py-0\">";
                        }
                        out += "<div class=\"row\">\
                                    <div class=\"col-1 d-flex justify-content-center align-items-center border-end\">"+fileNum+"</div>\
                                    <div class=\"col d-flex align-items-center\">"+paths[i].name+" - "+formatBytes(paths[i].size)+"</div>\
                                    <div class=\"col-1 d-flex justify-content-end\">\
                                        <button id=\"rmvFile_"+fileNum+"\" class=\"btn btn-secondary\" type=\"button\" onclick=\"removeFile(this);\">\
                                            <svg xmlns=\"http://www.w3.org/2000/svg\" width=\"16\" height=\"16\" fill=\"currentColor\" class=\"bi bi-trash\" viewBox=\"0 0 16 16\">\
                                                <path d=\"M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z\"/>\
                                                <path fill-rule=\"evenodd\" d=\"M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z\"/>\
                                            </svg>\
                                        </button>\
                                    </div>\
                                </div>\
                            </li>";
                    }
                }
                else if (paths.length > 0 && filesAlreadyExist)
                {
                    document.getElementById('lastFileRow').className = "list-group-item pe-0 py-0";
                    document.getElementById('lastFileRow').id = "";
                    document.getElementById("fileSizeTotal").remove();
                    out = outList.innerHTML;

                    for (let i = 0; i < paths.length; i++)
                    {
                        let fileNum = i + 1 + outList.childElementCount;

                        if (i == paths.length - 1)
                        {
                            out += "<li id=\"lastFileRow\" class=\"list-group-item pe-0 py-0\">";
                        }
                        else
                        {
                            out += "<li class=\"list-group-item pe-0 py-0\">";
                        }
                        out += "<div class=\"row\">\
                                    <div class=\"col-1 d-flex justify-content-center align-items-center border-end\">"+fileNum+"</div>\
                                    <div class=\"col d-flex align-items-center\">"+paths[i].name+" - "+formatBytes(paths[i].size)+"</div>\
                                    <div class=\"col-1 d-flex justify-content-end\">\
                                        <button id=\"rmvFile_"+fileNum+"\" class=\"btn btn-secondary\" type=\"button\" onclick=\"removeFile(this);\">\
                                            <svg xmlns=\"http://www.w3.org/2000/svg\" width=\"16\" height=\"16\" fill=\"currentColor\" class=\"bi bi-trash\" viewBox=\"0 0 16 16\">\
                                                <path d=\"M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z\"/>\
                                                <path fill-rule=\"evenodd\" d=\"M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z\"/>\
                                            </svg>\
                                        </button>\
                                    </div>\
                                </div>\
                            </li>";
                    }
                }

                out += "<div id=\"fileSizeTotal\" class=\"list-group-item list-group-item-light\">";
                out += parseInt(paths.length + numExistingFiles) + " File";
                if (paths.length + numExistingFiles > 1)
                {
                    out += "s";
                }
                out += " - " + formatBytes(totalFileSize);
                out += "</div>";
                outList.innerHTML = out;
            }
            else
            {
                document.getElementById("reqAttachment").value = '';
                let readable = (formatBytes(totalFileSize));
                alert("Sorry, but your attachment is too large.\nOur server accepts a total attachment size of 25 MB.\nYour total attachment size: " + readable + ".");
                input.files = fileStore.files;
                totalFileSize -= newFileSize;
            }
        }
        else if (paths.length == 0 && filesAlreadyExist)
        {
            input.files = fileStore.files;
        }
        console.log(input.files);
    }
    function removeFile(fileRow)
    {
        const fileID = (fileRow.id.split('_'))[1] - 1;
        var outList = document.getElementById("reqAttachList");

        fileStore = new DataTransfer();
        const input = document.getElementById("reqAttachment");
        let paths = input.files;
        totalFileSize = 0;
        for (let i = 0; i < paths.length; i++)
        {
            const file = input.files[i];
            if (fileID !== i)
            {
                totalFileSize += file.size;
                fileStore.items.add(file);
            }
        }
        input.files = fileStore.files;
        paths = fileStore.files;

        let newFileCount = paths.length;
        let out = "";
        outList.innerHTML = "";
        for (let i = 0; i < newFileCount; i++)
        {
            let fileNum = i + 1;
            if (i == newFileCount - 1)
            {
                out += "<li id=\"lastFileRow\" class=\"list-group-item pe-0 py-0\">";
			}
            else
            {
                out += "<li class=\"list-group-item pe-0 py-0\">";
			}
            out += "<div class=\"row\">\
                        <div class=\"col-1 d-flex justify-content-center align-items-center border-end\">"+fileNum+"</div>\
                        <div class=\"col d-flex align-items-center\">"+paths[i].name+" - "+formatBytes(paths[i].size)+"</div>\
                        <div class=\"col d-flex justify-content-end\">\
                            <button id=\"rmvFile_"+fileNum+"\" class=\"btn btn-secondary\" type=\"button\" onclick=\"removeFile(this);\">\
                                <svg xmlns=\"http://www.w3.org/2000/svg\" width=\"16\" height=\"16\" fill=\"currentColor\" class=\"bi bi-trash\" viewBox=\"0 0 16 16\">\
                                    <path d=\"M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z\"/>\
                                    <path fill-rule=\"evenodd\" d=\"M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z\"/>\
                                </svg>\
                            </button>\
                        </div>\
                    </div>\
                </li>";
        }
        if (paths.length >= 1)
        {
            out += "<div id=\"fileSizeTotal\" class=\"list-group-item list-group-item-light\">";
            out += parseInt(paths.length) + " File";
            if (paths.length > 1)
            {
                out += "s";
            }
            out += " - " + formatBytes(totalFileSize);
            out += "</div>";
            outList.innerHTML = out;
        }
    }

    function hideAll()
    {
<?php
if (!$authenticated)
{
?>
        hideField("auth_LoginSet");
<?php
}
    if ($reqTypeList[$reqType][2] == "Embed" || $reqTypeList[$reqType][2] == "Custom")
    {
        echo "\t\t\t\t" . $reqModuleList[$reqType]->getClassHeader() . "Hide();\n";
    }
?>
        hideField("reqInfoRow");
        hideField("reqBehalfOfRow");
        hideRequiredFieldGroup("reqBehalfInfoRow");
        hideField("reqLdapChangeRow");
        hideRequiredField("reqAttachmentRow");
<?php
        if (!$authenticated)
        {
?>
        hideField("reqCaptchaRow");
<?php
        }
?>
        hideRequiredField("reqMessageRow");
        hideField("reqCCRow");
        hideField("reqSubmitRow");
    }

    function updateFormControls()
    {
        hideAll();

        var cb = document.getElementById("reqBehalfOf").checked;
        if (cb)
        {
            showRequiredFieldGroup("reqBehalfInfoRow");
        }

        var reqType = document.getElementById("reqType");

        switch (reqType.value)
        {
<?php
        $reqToOut = $reqTypeList[$reqType];
        if ($reqToOut[2] == "Embed" || $reqToOut[2] == "Custom")
        {
            if ($authenticated)
            {
?>
            case "<?php echo $reqType;?>":
                <?php echo $reqModuleList[$reqType]->getClassHeader();?>UpdateFormControls();
                showField("reqInfoRow");
                showField("reqBehalfOfRow");
                showField("reqAttachmentRow");
                showField("reqMessageRow");
                showField("reqCCRow");
                showField("reqSubmitRow");
            break;
<?php
            }
            else
            {
?>
            case "<?php echo $reqType;?>":
<?php
                if ($reqAuthList[$reqType] == "N")
                {
?>
                <?php echo $reqModuleList[$reqType]->getClassHeader();?>UpdateFormControls();
                showField("reqBehalfOfRow");
                showField("reqAttachmentRow");
                showField("reqMessageRow");
                showField("reqCCRow");
                showField("reqCaptchaRow");
                showField("reqSubmitRow");
<?php
                }
                else
                {
?>
                showField("auth_LoginSet");
<?php
                }
?>
            break;
<?php
            }
        }
?>
            default:
            break;
        }
    }

    // Loop through all modules and insert updateformcontrols() code

    window.onbeforeunload = prepareSubmit;
    </script>
<?php
    echo $reqModuleList[$reqType]->js_Hide();
    echo $reqModuleList[$reqType]->js_UpdateFormControls();
}
?>
</head>

<body>
<?php
if ($devMode == "BETA" || $devMode == "PRD")
{
?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-PZ7WJ674"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<?php
}
?>
<nav <?php echo ($embedMode ? "" : "id=\"umbcSupportFormHeader\""); ?> class="navbar navbar-dark navbar-expand py-1" data-bs-theme="dark">
    <div class="container-fluid umbcHelpFormContainer">
        <h1>
            <a class="navbar-brand pb-2" href="../index.php">
                <img id="umbcLogo" class="align-middle pb-2" src="../../Utils/images/UMBC_Shield.png" alt="UMBC"/>
                <span class="align-text-top text-white">&nbsp;Help</span>
            </a>
        </h1>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarUMBCList" aria-controls="navbarUMBCList" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="justify-content-end" id="navbarUMBCList">
            <ul class="navbar-nav nav-pills align-text-top">
                <li class="nav-item dropdown">
                    <button class="btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        Service Areas
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="nav-item px-3">
                            <a href="../studentServices/studentServices-support.php" class="nav-link text-white">
                                <div class="row">
                                    <div class="col-2">
                                        <svg class="ms-1" xmlns="http://www.w3.org/2000/svg" height="40" width="40" viewBox="0 0 576 512">
                                            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2023 Fonticons, Inc.-->
                                            <path fill="#feffff" d="M64 32C28.7 32 0 60.7 0 96V416c0 35.3 28.7 64 64 64H512c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64H64zm80 256h64c44.2 0 80 35.8 80 80c0 8.8-7.2 16-16 16H80c-8.8 0-16-7.2-16-16c0-44.2 35.8-80 80-80zm-32-96a64 64 0 1 1 128 0 64 64 0 1 1 -128 0zm256-32H496c8.8 0 16 7.2 16 16s-7.2 16-16 16H368c-8.8 0-16-7.2-16-16s7.2-16 16-16zm0 64H496c8.8 0 16 7.2 16 16s-7.2 16-16 16H368c-8.8 0-16-7.2-16-16s7.2-16 16-16zm0 64H496c8.8 0 16 7.2 16 16s-7.2 16-16 16H368c-8.8 0-16-7.2-16-16s7.2-16 16-16z"></path>
                                        </svg>
                                    </div>
                                    <div class="col-10 align-self-center ps-4">
                                        Academic &amp; Student Services
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li class="nav-item px-3">
                            <a href="../administrative/administrative-support.php" class="nav-link text-white">
                                <div class="row">
                                    <div class="col-2">
                                        <svg class="ms-1" xmlns="http://www.w3.org/2000/svg" height="40" width="40" viewBox="0 0 384 512">
                                            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2023 Fonticons, Inc.-->
                                            <path fill="#feffff" d="M48 0C21.5 0 0 21.5 0 48V464c0 26.5 21.5 48 48 48h96V432c0-26.5 21.5-48 48-48s48 21.5 48 48v80h96c26.5 0 48-21.5 48-48V48c0-26.5-21.5-48-48-48H48zM64 240c0-8.8 7.2-16 16-16h32c8.8 0 16 7.2 16 16v32c0 8.8-7.2 16-16 16H80c-8.8 0-16-7.2-16-16V240zm112-16h32c8.8 0 16 7.2 16 16v32c0 8.8-7.2 16-16 16H176c-8.8 0-16-7.2-16-16V240c0-8.8 7.2-16 16-16zm80 16c0-8.8 7.2-16 16-16h32c8.8 0 16 7.2 16 16v32c0 8.8-7.2 16-16 16H272c-8.8 0-16-7.2-16-16V240zM80 96h32c8.8 0 16 7.2 16 16v32c0 8.8-7.2 16-16 16H80c-8.8 0-16-7.2-16-16V112c0-8.8 7.2-16 16-16zm80 16c0-8.8 7.2-16 16-16h32c8.8 0 16 7.2 16 16v32c0 8.8-7.2 16-16 16H176c-8.8 0-16-7.2-16-16V112zM272 96h32c8.8 0 16 7.2 16 16v32c0 8.8-7.2 16-16 16H272c-8.8 0-16-7.2-16-16V112c0-8.8 7.2-16 16-16z"/>
                                        </svg>
                                    </div>
                                    <div class="col-10 align-self-center ps-4">
                                        Administrative Services
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li class="nav-item px-3">
                            <a href="../computingTechnology/computingTechnology-support.php" class="nav-link text-white">
                                <div class="row">
                                    <div class="col-2">
                                        <svg class="ms-1" xmlns="http://www.w3.org/2000/svg" height="40" width="40" viewBox="0 0 640 512">
                                            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2023 Fonticons, Inc.-->
                                            <path fill="#feffff" d="M128 32C92.7 32 64 60.7 64 96V352h64V96H512V352h64V96c0-35.3-28.7-64-64-64H128zM19.2 384C8.6 384 0 392.6 0 403.2C0 445.6 34.4 480 76.8 480H563.2c42.4 0 76.8-34.4 76.8-76.8c0-10.6-8.6-19.2-19.2-19.2H19.2z"/>
                                        </svg>
                                    </div>
                                    <div class="col-10 align-self-center ps-4">
                                        Computing &amp; Technology
                                    </div>
                                </div>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
<?php
if ($devMode == "DEV" || $devMode == "BETA")
{
?>
        <span style="position:absolute;float:top;left:50vw;top:0;color:lime;font-weight:bold;border-color:white"><?php echo $devMode; ?></span>
<?php
}
?>
</nav>
<div <?php echo ($embedMode ? "" : "id=\"umbcNavAccent\""); ?>></div>
<div class="container umbcHelpFormContainer" <?php echo ($embedMode ? "style=\"border-bottom: 0; width:100%; height:100%; position:fixed; overflow-y: hidden\"" : ""); ?>>
<div class="py-0 my-0" style="margin-left: 20px;" <?php echo ($embedMode ? "style=\"overflow-y: auto\"" : "");?>>
<?php
if ($process_mode == MODE_FINISH)
{
    $bodyTop  = "First Name:                " . $req['FirstName'] . "\n";
    $bodyTop .= "Last Name:                 " . $req['LastName'] . "\n";
    $bodyTop .= "Email:                     " . $req['Email'] . "\n";
    $bodyTop .= "Campus ID:                 " . $req['CampusID'] . "\n\n";

    $behalfOf = $_POST['behalfOf'];
    $behalfArray = array();
    if ($behalfOf == "sb")
    {
        $behalfArray = $_POST['reqBehalfInfo'];
        $behalfFirstName = filter_var($behalfArray[0], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $behalfLastName = filter_var($behalfArray[1], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $behalfEmail = filter_var($behalfArray[2], FILTER_SANITIZE_EMAIL);
        $behalfCampusID = filter_var($behalfArray[3], FILTER_SANITIZE_STRING);

        $bodyTop .= "On Behalf Of:             " . $behalfFirstName . " " . $behalfLastName . " (" . $behalfEmail . ", " . $behalfCampusID . ")\n\n";
    }

    $ccList = explode(",", $_POST['altEmail']);
    for ($i = 1; $i < count($ccList); $i++)
    {
        $ccList[$i] = filter_var($ccList[$i], FILTER_SANITIZE_EMAIL);
    }
    $ccList = implode(", ", $ccList);
    if (strlen($ccList) > 0)
    {
        $bodyTop .= "Cc:                        " . $ccList . "\n\n";
    }

    $reqType = $_POST['reqType'];
    if ($reqType == "")
    {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    $retArray = $reqModuleList[$reqType]->finish();


    if (!empty($ccList))
    {
        $retArray['CustomFields']['2281'] = $ccList;
        $retArray['CustomFields']['2584'] = $behalfName;
    }

    $message = filter_var($_POST['reqMessage'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
    $bodyTail .= $message . "\n\n";

    $countFiles = count($_FILES['reqAttachment']['name']);

    $allEmpty = true;

    $box = "";
    $folderID = "";
    $folderTempName = "";

    foreach($_FILES['reqAttachment']['name'] as $f)
    {
        if (!empty($f))
        {
            $allEmpty = false;
        }
    }

    if (!$allEmpty) { do
    {
        $redirect_uri = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
        $box = UmbcRTBoxApi::LoadApp($retArray['BoxProject'], "load", $redirect_uri);
        if (!$box)
        {
            $rtErr = array(
                'Code' => 4,
                'Message' => 'ERR_BOX_CONNECT_FAIL',
            );
            break;
        }

        $rootFolder = $box->getFolderID();
        $folderToMake = $box->fixUpFolderName($retArray['BoxFolderName']);
        $ret = $box->createFolderInParentFolder($folderToMake);
        $box->setFolderID($ret['FolderId']);
        $folderID = $ret['FolderId'];
        $folderTempName = $folderToMake;

        $retArray['CustomFields']['2075'] = "";     // BOX-Attachlink

        for ($i = 0; $i < $countFiles; $i++)
        {
            if (!empty($_FILES['reqAttachment']['name'][$i]))
            {
                $filename = $_FILES['reqAttachment']['name'][$i];
                $fileLoc = $_FILES['reqAttachment']['tmp_name'][$i];

                $upload = $box->uploadFileToParentFolder($fileLoc, $filename);

                if ($upload['sharedUrl'])
                {
                    $bodyTail .= "Attachment " . ($i+1) . ": " . "<a href=\"" . $upload['sharedUrl'] . "\" target=\"_blank\">" . $upload['FileName'] . "</a>\n";
                    $retArray['CustomFields']['2075'] .= "<a href=\"" . $upload['sharedUrl'] . "\" target=\"_blank\">" . $upload['FileName'] . "</a>";
                    $hasUpload = true;
                }
                else
                {
                    $rtErr = array(
                        'Code' => 3,
                        'Message' => 'ERR_BOX_UPLOAD_FAIL',
                    );
                    break;
                }
            }
        }
    } while (false); }

    $requestorList = "";

    if (!empty($behalfArray))
    {
        if (!empty($behalfArray[3]))
        {
            // Use "On Behalf" Campus ID
            $requestorList = $behalfArray[3];
        }
        else
        {
            // Use "On Behalf" Email
            $requestorList = $behalfArray[2];
        }
        $ccList = explode(", ", $ccList);
        array_push($ccList, $req['Email']);
        $ccList = implode(", ", array_filter($ccList));
    }
    else
    {
        $requestorList = $req['CampusID'];
    }

    if ($hasUpload)
    {
        $fullBody = $bodyTop . $retArray['BodyMid'] . $bodyTail;
        $fullBody = str_replace("\n", "<br />\n", $fullBody);
        $postData = array("Cc" => $ccList, "Content" => $fullBody, "ContentType" => "text/html");
    }
    else
    {
        $postData = array("Cc" => $ccList, "Content" => ($bodyTop . $retArray['BodyMid'] . $bodyTail), "ContentType" => "text/plain");
    }

    if (isset($retArray['AdminCc']) && !empty($retArray["AdminCc"]))
    {
        $postData['AdminCc'] = $retArray['AdminCc'];
    }

    if (isset($retArray['QCC']) && !empty($retArray["QCC"]))
    {
        $postData['RT::CustomRole-22'] = $retArray['QCC'];
    }

    if (isset($retArray['DueDate']) && !empty($retArray['DueDate']))
    {
        $postData['Due'] = $retArray['DueDate'];
    }

    if ($devMode == "DEV" || $devMode == "BETA")
    //if ($devMode == "DEV")
    {
        echo "<pre>" . print_r($retArray, true) . "</pre>";
        echo "<pre>" . print_r($postData, true) . "</pre>";
        exit();
    }

    $response = $RT->createTicket($retArray['Queue'], $retArray['Subject'], $requestorList, $retArray['CustomFields'], $postData);

    if ($response && $RT->getErrorCode() == 0)
    {
        if ($hasUpload)
        {
            $renameFolder = $folderTempName . "_" . $response;
            $box->updateFolder($folderID, array('name' => $renameFolder));
        }

        // Printing Confirmation
?>
    <div class="container">
        <div class="row mb-3">
            <div class="col-md-10 col-lg-8 alert alert-success" role="alert">
                <div class="row">
                    <div class="col-1 mt-4 ms-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-journal-check" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M10.854 6.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 8.793l2.646-2.647a.5.5 0 0 1 .708 0z"/>
                            <path d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2z"/>
                            <path d="M1 5v-.5a.5.5 0 0 1 1 0V5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0V8h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0v.5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1z"/>
                        </svg>
                    </div>
                    <div class="col">
                        <p style="color: black; font-size: 20px">
                            Thank you!  Your RT request has been received.<br />
                            You will receive an email confirmation immediately.<br />
                            Please allow up to 2 business days for a response.
                        </p>

                        <p style="color: black; font-size: 20px">
                            Your ticket number is <a href="https://rt.umbc.edu/Ticket/Display.html?id=<?php echo $response; ?>" target="_blank">RT#<?php echo $response; ?></a>.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <p><a href="<?php echo $_SERVER['PHP_SELF'];?>" class="btn btn-primary" role="button">Submit another Request</a></p>
        </div>
    </div>
<?php
    }
    else
    {
        $rtErr = array(
            'Code' => 1,
            'Message' => 'ERR_TICKET_CREATE_FAIL',
        );
    }

    if ($rtErr['Code'] < 0)
    {
?>
    <div id="spot-container">
        <p style="color: #f00; font-size: 20px">
            We're sorry, but something has gone wrong.<br />
            Error Code: <?php echo $rtErr['Code'];?> (<?php echo $rtErr['Message'];?>)<br />
            You may try to submit again, or contact UMBC DoIT for support.
        </p>
    </div>
<?php
        $process_mode = MODE_START;
    }
}

if ($process_mode & MODE_START)
{
    if (!empty($autoReqType) && !$embedMode)
    {
?>
<div style="position: absolute; right: 0; margin-right: 10px; width: 30%; z-index: 30" class="form-text text-muted">
    Not looking for <?php echo $autoReqType; ?> help?<br><a href="<?php echo $_SERVER['PHP_SELF']; ?>?auto=0">Click here</a>.
</div>
<?php
    }
?>

<form id="RTSendRequest" name="RTSendRequest" action="<?php echo $_SERVER['PHP_SELF'];?>" enctype="multipart/form-data" method="post" onsubmit="return validateInput();">
<div class="container mt-3">
    <article class="row mb-3" id="reqSelectChosenRow" <?php echo ($autoReqType ? "style=\"display: none;\"" : "");?>>
        <div class="col-md-10 col-lg-8">
            <div class="input-group px-0">
                <input id="reqType" name="reqType" class="form-control" type="hidden" readonly required value="<?php echo $reqType;?>" />
                <h2>Request Help: <?php echo $reqType;?></h2>
            </div>
        </div>
    </article>

    <div class="row mb-3" id="reqInfoRow">
        <div class="col-3 col-sm-2" style="color:#00f;font-size:16px;">Requestor: </div>
        <div class="col-9 col-sm-10" style="color:#00f;font-size:16px;"><?php echo $req['FirstName'] . " " . $req['LastName'];?></div>

        <div class="col-3 col-sm-2" style="color:#00f;font-size:16px;">&nbsp;</div>
        <div class="col-9 col-sm-10" style="color:#00f;font-size:16px;"><?php echo $req['CampusID'] . " - " . $req['Email'];?></div>
    </div>

    <div class="row mb-3" id="reqBehalfOfRow">
        <div class="col-md-7 col-lg-6 offset-md-3 offset-lg-2">
            <div class="form-check">
                <input id="reqBehalfOf" name="behalfOf" type="checkbox" class="form-check-input" value="sb" onchange="updateFormControls();" />
                <div class="form-check-label"><label for="reqBehalfOf"><strong>I'm submitting this on behalf of someone else.</strong></label></div>
            </div>
        </div>
    </div>

    <div class="row mb-3" id="reqBehalfInfoRow">
        <div class="col-md-3 col-lg-2 col-form-label">
            <span id="spn-reqBehalfInfo" class="req-span">*</span>
            <label class="form-label" for="reqBehalfInfo">Submitted for:</label>
        </div>
        <div class="col-md-7 col-lg-6">
            <div class="input-group px-0">
                <input id="reqBehalfInfo_FirstName" name="reqBehalfInfo[]" type="text" class="form-control" placeholder="First Name"/>
                <input id="reqBehalfInfo_LastName" name="reqBehalfInfo[]" type="text" class="form-control" placeholder="Last Name"/>
            </div>
            <input id="reqBehalfInfo_Email" name="reqBehalfInfo[]" type="email" class="form-control" placeholder="Email (example@umbc.edu)" onchange="reqGetLdapInfo('Email');"/>
            <input id="reqBehalfInfo_CampusID" name="reqBehalfInfo[]" type="text" class="form-control" maxlength="7" placeholder="Campus ID (ex. AB12345)" onkeyup="reqCheckCampusID();" />
        </div>
    </div>

    <div class="row mb-3" id="reqLdapChangeRow">
        <div class="col-md-7 col-lg-6 offset-md-3 offset-lg-2">
            <div id="reqLdapChangeHeader">
                []
            </div>
            <a type="button" class="btn btn-secondary" href="javaScript:reqResetLdap();">Change</a>
        </div>
    </div>
</div>

<br />

<!-- module form fields go here -->
<?php
    echo $reqModuleList[$reqType]->html_outputFields();
?>

<?php
    if (!$authenticated)
    {
?>
    <div class="container" id="auth_LoginSet" name="auth_LoginSet">
        <div class="row mb-3">
            <div class="col-md-10 col-lg-8 px-0">
                <div class="card shadow rounded">
                    <div class="card-body">
                        <p>Please login to myUMBC to continue.</p>
                        <input type="hidden" id="auth_selected" name="auth_selected" value="" />
                        <input type="hidden" id="auth_login" name="auth_login" value="1" />
                        <input type="Submit" id="auth_submit" name="auth_submit" value="Login" class="btn btn-primary required" />
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
    }
?>

<br />

<div class="container">
    <div class="row mb-3" id="reqMessageRow">
        <span id="spn-reqMessage" class="req-span">*</span>
        <label class="form-label" for="reqMessage">Notes/Comments:</label>
        <div class="col-md-10 col-lg-8">
            <textarea class="form-control" name="reqMessage" id="reqMessage" rows="6" cols="74" maxlength="2100"></textarea>
        </div>
    </div>

    <div class="row mb-3" id="reqCCRow">
        <div class="col-md-2 col-lg-1 col-form-label">
            <label class="form-label" for="reqAltEmail">CC: </label>
        </div>
        <div class="col-md-8 col-lg-7">
            <input id="reqAltEmail" name="altEmail" type="email" class="form-control" multiple />
            <small class="form-text text-muted" style="color:#f00;">If more than one email address, separate them with commas.</small>
        </div>
    </div>

    <br />

    <div class="row mb-3" id="reqAttachmentRow">
        <div class="col-md-2 col-lg-1 col-form-label">
            <span id="spn-reqAttachment" class="req-span">*</span>
            <label class="form-label" for="reqAttachment">Attachment:</label>
        </div>
        <div class="col-md-8 col-lg-7">
            <input type="file" name='reqAttachment[]' id='reqAttachment' class="form-control" enctype="multipart/form-data" multiple="multiple" size='30' onchange="UpdateFile()" />
            <div id="reqAttachList" class="list-group"></div>
            <small class="form-text text-muted" style='color:#f00;'>Maximum total upload size is 25 MB.</small>
            <small class="form-text text-muted" style='color:#f00;'>Warning: Sending documents with highly sensitive information, ie. social security numbers, is not recommended.</small>
        </div>
    </div>

    <br />
<?php
if (!$authenticated)
{
?>
    <div class="row mb-3" id="reqCaptchaRow">
        <label class="control-label">&nbsp;</label>
        <div class="controls">
            <div class="g-recaptcha" data-sitekey="6LfmrqsZAAAAAMbqZ8dYvvXpM9gC7UX-m9xwfyK3"></div>
        </div>
    </div>
<?php
}
?>
    <br />
    <div class="row mb-3" id="reqSubmitRow">
        <input class="col-sm-3 col-lg-2 btn btn-primary required" type="submit" id="reqSubmit" name="reqSubmit" value="Submit" />
        <span class="col-1">&nbsp;</span>
        <input class="col-sm-3 col-lg-2 btn btn-secondary" type="reset" value="Clear" />
    </div>

    <div class="row mb-3">
        <strong><span style="color:#f00;">* = Required field.</span></strong>
    </div>
</div>

</form>
<?php
}
?>
</div>
</div>

<?php
if ($devMode == "DEV")
{
?>
    <div id="sizer">
        <div class="d-block d-sm-none d-md-none d-lg-none d-xl-none" data-size="xs">XS</div>
        <div class="d-none d-sm-block d-md-none d-lg-none d-xl-none" data-size="sm">SM</div>
        <div class="d-none d-sm-none d-md-block d-lg-none d-xl-none" data-size="md">MD</div>
        <div class="d-none d-sm-none d-md-none d-lg-block d-xl-none" data-size="lg">LG</div>
        <div class="d-none d-sm-none d-md-none d-lg-none d-xl-block" data-size="xl">XL</div>
    </div>
<?php
}
?>

<div class="modal fade" id="waitOnSubmit" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labeledby="pw" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pw">Please Wait</h5>
            </div>
            <div class="modal-body">
                Submitting your request...
            </div>
        </div>
    </div>
</div>

</body>
<footer>
    <nav class="navbar navbar-expand pb-4">
        <div class="container-fluid umbcHelpFormContainer d-flex align-items-end flex-wrap">
            <a class="umbcLogo" style="margin-right: 16px; margin-bottom: 2px" href="https://umbc.edu">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 661 158" class="logo" role="img" style="width: 101px; height: 24px;">
                    <title>UMBC</title>
                    <g fill-rule="evenodd" class="umbc" clip-rule="evenodd"><path d="M227.243 148.774c-8.63 0-24.84-1.636-36.457-12.599-7.181-6.888-15.42-18.384-15.42-46.407v-25.39c0-27.09 0-31.97-.346-37.506-.314-5.349-1.362-7.927-6.749-9.069-1.32-.33-4.167-.503-5.722-.503-1.121 0-2.313-.689-2.313-1.964 0-2.313 2.596-2.313 3.704-2.313 5.398 0 12.209.251 16.279.401 1.91.071 3.246.121 3.72.121.531 0 2.133-.064 4.176-.144l.159-.007c4.071-.157 9.52-.371 12.883-.371 1.108 0 3.704 0 3.704 2.313 0 1.275-1.191 1.964-2.313 1.964-1.124 0-2.123.166-4.11.495-3.96.589-5.398 2.876-5.753 9.073-.346 5.54-.346 10.42-.346 37.51v24.347c0 26.543 6.45 36.271 12.758 42.288 8.256 7.752 15.926 9.31 25.624 9.31 10.111 0 20.301-4.67 26.596-12.189 6.918-8.575 9.872-21.144 9.872-42.017V64.378c0-27.09 0-31.97-.346-37.506-.314-5.349-1.363-7.927-6.749-9.069-1.321-.33-4.167-.503-5.722-.503-1.122 0-2.312-.689-2.312-1.964 0-2.313 2.595-2.313 3.703-2.313 5.736 0 12.396.28 15.973.43 1.336.056 2.191.092 2.462.092.365 0 1.43-.052 2.795-.118l.221-.011c3.229-.156 8.063-.393 11.592-.393 1.108 0 3.704 0 3.704 2.313 0 1.275-1.191 1.964-2.313 1.964-1.152 0-2.147 0-4.125.495-4.002.743-5.387 2.946-5.738 9.073-.346 5.54-.346 10.42-.346 37.51v18.607c0 33.871-8.715 45.95-16.308 52.686-13.211 11.744-27.123 13.103-36.437 13.103Z"></path><path d="M485.993 19.735c-.794 0-4.919.04-8.125.843-1.39.309-1.39.605-1.39 1.019v46.605c0 .802.085.836.639 1.058.958.139 4.035.631 10.266.631 9.72 0 11.722-.311 15.133-4.209 3.834-4.381 6.033-10.562 6.033-16.957 0-13.374-5.906-28.99-22.556-28.99Zm-9.506 56.699c.008.027-.009.118-.009.29v9.914c0 13.063 0 34.924.172 37.503l.043.691c.48 7.923.595 9.803 5.351 12.105 4.974 2.396 13.288 2.517 14.903 2.517 10.889 0 23.602-6.225 23.602-23.774 0-6.82-1.606-23.79-16.47-34.023-5.693-3.848-9.892-4.372-13.95-4.876-1.557-.238-6.074-.353-13.527-.353-.056 0-.091.003-.115.006Zm15.592 70.427c-2.485 0-10.316-.405-16.036-.7l-.707-.037c-3.302-.171-5.91-.306-6.387-.306-.241 0-1.344.045-2.867.105-2.467.099-45.283.176-48.653.176-1.109 0-3.704 0-3.704-2.313 0-.978.714-1.963 2.311-1.963 1.249 0 3.125-.253 5.017-.675.432-.085 2.49-.759 1.762-5.87l-8.14-87.308-42.803 90.829c-2.817 5.953-3.403 7.192-5.36 7.192-1.728 0-2.517-1.496-5.839-7.795l-.367-.693c-1.893-3.569-6.862-13.274-19.79-39.234l-.566-1.135c-1.647-3.29-17.881-38.115-22.107-47.604l-6.759 78.403c-.17 2.732-.17 5.811-.17 8.789 0 2.162 1.59 4.05 3.781 4.487 2.861.675 5.384.853 6.427.853 1.137 0 2.313.671 2.313 1.791 0 2.488-2.839 2.488-4.052 2.488-4.435 0-9.633-.242-13.072-.399l-.245-.013c-1.423-.065-2.449-.111-2.856-.111-.532 0-1.881.063-3.606.145l-.284.014c-3.288.155-7.733.364-10.718.364-2.573 0-3.878-.838-3.878-2.488 0-1.176 1.339-1.791 2.661-1.791 1.35 0 2.517 0 5.035-.504 4.892-.889 5.506-6.768 6.156-12.99l.066-.636L311.164 14.54c.218-1.212.715-3.952 2.829-3.952 1.86 0 2.754 1.626 3.446 3.149l52.362 107.632 50.308-107.468c.552-1.232 1.479-3.313 3.445-3.313 2.235 0 2.696 2.612 3.005 5.52l11.649 106.776c.643 5.777 1.712 15.394 8.756 17.656 3.452 1.112 7.521 1.061 8.648.722 2.953-.886 3.649-4.297 4.251-8.664.861-7.234.861-20.641.861-37.613V64.378c0-27.09 0-31.97-.346-37.506-.316-5.349-1.365-7.928-6.75-9.069-1.32-.33-4.167-.503-5.721-.503-1.319 0-2.312-.845-2.312-1.964 0-2.313 2.593-2.313 3.702-2.313 5.751 0 13.405.291 17.083.43l.899.035c.9.033 1.493.057 1.67.057h.001c1.753 0 4.052-.096 6.486-.198 3.496-.145 7.816-.324 12.47-.324 28.181 0 35.701 16.977 35.701 27.008 0 14.211-8.146 23.108-15.59 30.885 6.989 2.433 13.816 6.771 18.853 11.994 6.855 7.104 10.477 15.79 10.477 25.119 0 9.931-4.119 19.624-11.302 26.595-8.25 8.005-19.994 12.237-33.966 12.237Z"></path><path d="M619.559 148.774c-25.14 0-43.573-6.043-58.006-19.018-14.167-12.705-21.349-30.255-21.349-52.162 0-13.719 5.189-33.568 19.748-48.129 12.703-12.702 30.266-18.877 53.694-18.877 4.188 0 15.045.204 25.38 2.101l.491.093c6.337 1.167 11.78 2.169 17.141 2.504 1.35.123 3.124.546 3.124 2.832 0 .604-.08 1.262-.204 2.261-.184 1.506-.458 3.755-.667 7.529-.198 3.278-.284 7.883-.353 11.583-.051 2.854-.095 5.211-.17 6.34-.118 1.795-.296 4.512-2.484 4.512-2.312 0-2.312-2.536-2.312-4.574 0-6.658-2.805-13.503-6.978-17.035-5.632-4.826-18.385-9.694-34.187-9.694-26.557 0-36.19 9.358-39.355 12.434-13.548 12.99-15.374 29.901-15.374 44.73 0 16.109 6.265 32.02 17.185 43.651 11.552 12.303 27.42 19.077 44.676 19.077 18.794 0 25.333-4.046 29.598-8.417 3.513-3.678 5.684-11.1 6.186-14.117.26-1.43.613-3.377 2.648-3.377.643 0 2.138.344 2.138 3.529 0 1.328-2.098 16.521-3.863 22.519-1.119 3.54-1.635 4.117-5.032 5.627-7.522 3.008-21.291 4.078-31.675 4.078Z"></path></g>
                    <g fill-rule="evenodd" class="shield" clip-rule="evenodd"><path d="M126.133 84.637s-3.443 46.125-59.985 60.97C9.606 130.762 6.164 84.637 6.164 84.637V18.602C29.085 6.758 59.314 6.163 65.149 6.163c.651 0 .999.008.999.008s.348-.008.999-.008c5.839 0 36.066.595 58.986 12.439v66.035Zm2.83-71.51C104.785.633 73.228 0 67.147 0c-.489 0-.827.004-.998.006C65.977.004 65.639 0 65.15 0 59.069 0 27.512.633 3.334 13.127L0 14.849v70.018l.017.228c.04.53 1.067 13.138 9.632 27.601 7.823 13.208 23.703 30.673 54.934 38.873l1.565.41 1.566-.41c31.231-8.2 47.111-25.665 54.933-38.873 8.566-14.463 9.594-27.071 9.633-27.601l.017-.228V14.849l-3.334-1.722Z" class="shield__outer-border"></path><path fill="#C7C8CA" d="M119.613 75.263v7.975s-3.069 41.111-53.465 54.342c-7.087-1.861-13.21-4.287-18.548-7.059-7.77-4.034-13.844-8.818-18.549-13.784-15.047-15.881-16.367-33.499-16.367-33.499V24.382c5.19-2.682 10.8-4.711 16.367-6.254 6.487-1.799 12.911-2.933 18.549-3.643 10.695-1.347 18.548-1.183 18.548-1.183s30.756-.655 53.465 11.08v50.881ZM66.148 6.17102s-.348-.008-.999-.008c-5.835 0-36.064.595-58.985 12.43898v66.035s3.442 46.125 59.984 60.97c56.542-14.845 59.985-60.97 59.985-60.97V18.602C103.213 6.75802 72.986 6.16302 67.147 6.16302c-.651 0-.999.008-.999.008Z" class="shield__inner-border"></path>
                    <g class="shield__flag"><path fill="#1A1919" d="M47.6 57.61V14.485c-5.638.71-12.062 1.844-18.549 3.643v21.829L47.6 57.61Z"></path><path fill="#FDB515" d="M29.051 18.128c-5.567 1.543-11.177 3.572-16.367 6.254l16.367 15.575V18.128Z"></path><path fill="#FDB515" d="M29.051 39.957v35.928L47.6 93.537V57.61L29.051 39.957Z"></path><path fill="#1A1919" d="M29.051 39.957 12.684 24.382v35.927l16.367 15.576V39.957Z"></path><path fill="#FDB515" d="M66.148 75.263V13.302s-7.853-.164-18.548 1.183V57.61l18.548 17.653Z"></path><path fill="#1A1919" d="m47.6 93.537 18.548 17.654V75.263L47.6 57.61v35.927Z"></path><path fill="#FFFFFE" d="M111.86 83.016h-5.694v4.222c0 4.049-2.961 7.665-6.992 8.049-4.623.442-8.514-3.183-8.514-7.715v-4.556H73.901v20.5h4.222c4.05 0 7.666 2.961 8.05 6.991.441 4.624-3.183 8.515-7.716 8.515h-4.556v5.694c0 4.281-3.471 7.752-7.753 7.752v5.112c50.396-13.231 53.465-54.342 53.465-54.342v-7.975c0 4.281-3.471 7.753-7.753 7.753Z"></path><path fill="#FDB515" d="M66.148 111.191 47.6 93.537v36.984c5.338 2.772 11.461 5.198 18.548 7.059v-26.389Z"></path><path fill="#DA2128" d="M66.148 13.302v4.755c4.282 0 7.753 3.471 7.753 7.753v5.694h4.556c4.533 0 8.157 3.891 7.716 8.515-.384 4.03-4 6.991-8.05 6.991h-4.222v20.501H90.66v-4.556c0-4.533 3.891-8.157 8.514-7.716 4.031.384 6.992 4 6.992 8.049v4.223h5.694c4.282 0 7.753 3.47 7.753 7.752V24.382c-22.709-11.735-53.465-11.08-53.465-11.08Z"></path><path fill="#1A1919" d="M29.051 75.885v40.852c4.705 4.966 10.779 9.75 18.549 13.784V93.537L29.051 75.885Z"></path><path fill="#FDB515" d="M12.684 60.309v22.929s1.32 17.618 16.367 33.499V75.885L12.684 60.309Z"></path><path fill="#FFFFFE" d="M111.86 67.511h-5.694v-4.223c0-4.049-2.961-7.665-6.992-8.049-4.623-.441-8.514 3.183-8.514 7.716v4.556H73.901V47.01h4.222c4.05 0 7.666-2.961 8.05-6.991.441-4.624-3.183-8.515-7.716-8.515h-4.556V25.81c0-4.282-3.471-7.753-7.753-7.753v57.206h53.465c0-4.282-3.471-7.752-7.753-7.752Z"></path><path fill="#DA2128" d="M66.148 111.191v21.277c4.282 0 7.753-3.471 7.753-7.752v-5.694h4.556c4.533 0 8.157-3.891 7.716-8.515-.384-4.03-4-6.991-8.05-6.991h-4.222v-20.5H90.66v4.556c0 4.532 3.891 8.157 8.514 7.715 4.031-.384 6.992-4 6.992-8.049v-4.222h5.694c4.282 0 7.753-3.472 7.753-7.753H66.148v35.928Z"></path></g></g>
                </svg>
            </a>
            <div class="me-auto" style="font-size: 12px; margin-top: 4px;">© 2024 University of Maryland, Baltimore County.</div>
            <ul class="navbar-nav ms-md-auto" style="font-size: 12px; margin-top: 4px;">
                <li class="nav-item">
                    <a class="nav-link py-0" href="https://my3.my.umbc.edu/about/studentdata">Use of Student Data</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-0" href="https://umbc.edu/go/equal-opportunity">Equal Opportunity</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-0" href="https://umbc.edu/go/safety">Safety Resources</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-0" href="https://forms.gle/PcYUQZDVsyzf4xFx5">Feedback</a>
                </li>
            </ul>
        </div>
    </nav>
</footer>
</html>