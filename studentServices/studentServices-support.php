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
Global $req;
Global $authenticated;
Global $autoReqType;

$authenticated  = "";

$reqCategoryList        = array();
$reqCategoryInfoList    = array();
$reqTypeList            = array();
$reqAuthList            = array();
$reqTagList             = array();
$reqModuleList          = array();
$reqOSAData             = array();  // Other service areas, for searching results that aren't on this page

$found      = "";
$embedMode  = "";
$tagCF      = "";

$returnTo = "https://rtforms.umbc.edu/rt_myumbcHelpPage/" . $SERVICE_HEADER . "/" . $SERVICE_HEADER . "-support.php";

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
    $reqTagsFile = iconv("UTF-8", "ASCII//TRANSLIT", array_shift($reqLine));

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
    
        if ($reqName != "I'm not sure / Other")
        {
            $tagFile = file_get_contents("./" . $SERVICE_HEADER . "Config/" . $SERVICE_HEADER . "Tags/$reqTagsFile.csv");
            $tagFile = explode("\r\n", $tagFile);
            $reqTagList[$reqName] = implode("#", $tagFile);
    
            $reqAuthList[$reqName] = $reqAuth;
        }
    }
    else
    {
        $OSATagFile = "";
        $OSAFolderName = "";
        if ($reqServiceArea == "Academic & Student Services")
        {
            $OSAFolderName = "studentServices";
        }
        else if ($reqServiceArea == "Administrative Services")
        {
            $OSAFolderName = "administrative";
        }
        else if ($reqServiceArea == "Computing & Technology")
        {
            $OSAFolderName = "computingTechnology";
        }
        $OSATagFile = file_get_contents("../" . $OSAFolderName . "/" . $OSAFolderName . "Config/" . $OSAFolderName . "Tags/" . $reqTagsFile . ".csv");
        $OSATagFile = explode("\r\n", $OSATagFile);

        if (!isset($reqOSAData[$reqServiceArea]))
        {
            $reqOSAData[$reqServiceArea] = array();
        }
        $OSALink = "../" . $OSAFolderName . "/" . $OSAFolderName . "-support.php?display=" . urlencode($reqName);
        $reqOSAData[$reqServiceArea][$reqName] = array($reqName, implode("#", $OSATagFile), $OSALink);
    }
}
fclose($config);


// Load all defined modules
$reqModuleList = array();
$reqNames = array_keys($reqTypeList);
for($i = 0; $i < count($reqNames); $i++)
{
    $name = $reqNames[$i];
    if ($reqTypeList[$name][2] == "Embed" || $reqTypeList[$name][2] == "Custom")
    {
        if (!empty($reqTypeList[$name][4]) && $reqTypeList[$name][4] != "N/A")
        {
            $className = $reqTypeList[$name][4];
            require_once("./" . $SERVICE_HEADER . "Modules/$className.class.php");
            $reqModuleList[$name] = new $className($name, $reqTypeList[$name][0], $reqTagsList[$name]);
        }
    }
}

// Load Category Info
$config = fopen("./" . $SERVICE_HEADER . "Config/" . $SERVICE_HEADER . "_CategoryInfo.csv", 'r');
$headers = fgetcsv($config);
while ($reqLine = fgetcsv($config))
{
    $reqCategory = trim(iconv("UTF-8", "ASCII//TRANSLIT", array_shift($reqLine)));
    $reqCorrespondingType = trim(iconv("UTF-8", "ASCII//TRANSLIT", array_shift($reqLine)));
    $reqMenu = trim(iconv("UTF-8", "ASCII//TRANSLIT", array_shift($reqLine)));
    $reqClickAction = trim(iconv("UTF-8", "ASCII//TRANSLIT", array_shift($reqLine)));
    $reqClickArgs = trim(iconv("UTF-8", "ASCII//TRANSLIT", array_shift($reqLine)));

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

$embedMode = filter_var($_GET['embed'], FILTER_SANITIZE_NUMBER_INT);
$autoDisplayInfo = urldecode(filter_var($_GET['display'], FILTER_SANITIZE_STRING));
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
echo reqModule::js_outputFlowControl();
?>
    <script type="text/javascript">
    // Main form functions
    let reqTags = <?php echo json_encode($reqTagList);?>;
    let reqOSAData = <?php echo json_encode($reqOSAData);?>;

    let searchWhenTyped;
    let searchBorderChanges = {};
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

    $(document).ready(function()
    {
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

    function prepareSubmit()
    {
        $('#waitOnSubmit').modal({backdrop:"static"});
        $('#waitOnSubmit').modal("show");
    }

    function reqDisplayInfo(selection)
    {
        var current = document.getElementById("reqSelectCurrent");
        hideField("reqSelectDesc-"+current.value);
        showField("reqSelectDesc-"+selection);
        current.value = selection;
    }
    function reqFilterSelect(src)
    {
        clearTimeout(searchWhenTyped);

        reqHideConfluence();

        $("#reqFAQLoading,#reqFAQLoadingMobile").removeClass("d-none");
        $("#reqFAQLoading,#reqFAQLoadingMobile").addClass("d-flex");

        let term = "";
        if (src == 1)
        {
            term = document.getElementById("reqSelectFilterMobile").value.trim().split(" ");
            document.getElementById("reqSelectFilter").value = document.getElementById("reqSelectFilterMobile").value;
        }
        else
        {
            term = document.getElementById("reqSelectFilter").value.trim().split(" ");
            document.getElementById("reqSelectFilterMobile").value = document.getElementById("reqSelectFilter").value;
        }
        let typeList = $("#reqSelectMenu").find("button").not("button[id^='reqTypeCategory']");
        let OSATypeList = $("#reqOSASelectMenu").find("a");
        let OSATypeListMobile = $("#reqOSASelectMenuMobile").find("a");
        let typeListMobile = $("#reqSelectMenuMobile button").not("button[id^='reqTypeCategory']").not("button[id^='reqTypeMobileHeader']");
        let categoryList = $("#reqSelectMenu button[id^='reqTypeCategory'],a[id^='reqTypeCategory']");
        let categoryListMobile = $("#reqSelectMenuMobile h2[id^='reqTypeCategory']");

        let charLength = 0;

        for (let t = 0; t < term.length; t++)
        {
            charLength += term[t].length;
        }

        if (charLength >= 3)
        {
            $(".collapse[id^='reqType']").addClass('no-transition');
            $(".collapse[id^='reqType']").collapse('show');
    
            $("div[id^='reqTypeList']").removeClass("mb-2");

            document.getElementById('reqOSASelectMenu').style.display = "";
            document.getElementById('reqOSASelectMenuMobile').style.display = "";
            $("div[id^='reqOSAGroup']").hide();
            $("div[id^='reqOSAGroupMobile']").hide();
    
            for (let i = 0; i < typeList.length; i++)
            {
                typeList[i].style.display = "none";
                typeListMobile[i].style.display = "none";
            }
            for (let i = 0; i < OSATypeList.length; i++)
            {
                OSATypeList[i].style.display = "none";
                OSATypeListMobile[i].style.display = "none";
            }
            for (let i = 0; i < categoryList.length; i++)
            {
                categoryList[i].style.display = "";
                categoryListMobile[i].style.display = "";
            }

            for (let i in searchBorderChanges)
            {
                $("#"+i).removeClass(searchBorderChanges[i]);
                delete searchBorderChanges.i;
            }
            $("div[id^='reqOSAGroupMobile']").find("a:visible:first").removeClass("border-top rounded-top");
            $("div[id^='reqOSAGroupMobile']").find("a:visible:last").removeClass("border-bottom rounded-bottom");

    
            if (term.length >= 1 && !(term.length == 1 && term[0] === ""))
            {
                // Hide all category headers
                for (let i = 0; i < categoryList.length; i++)
                {
                    categoryList[i].style.display = "none";
                    categoryListMobile[i].style.display = "none";
                }

                let termVal = "";
                let firstChange = false;
                let firstChangeOSA = false;
    
                // Show only request types that match our search
                for (let t = 0; t < term.length; t++)
                {
                    let thisTerm = term[t].toLowerCase();
                    if (thisTerm.length > 2 )
                    {
                        for (let i = 0; i < typeList.length; i++)
                        {
                            if (typeList[i].id.split('-')[0] != "reqTypeCategory")
                            {
                                termVal = reqTags[typeList[i].value].toLowerCase();
                                if (termVal.indexOf(thisTerm) >= 0)
                                {
                                    typeList[i].style.display = "";
                                    typeListMobile[i].style.display = "";

                                    if (firstChange == false)
                                    {
                                        $(typeListMobile[i]).addClass("border-top rounded-top border-bottom rounded-bottom-0");
                                        searchBorderChanges[typeListMobile[i].id] = "border-top rounded-top";
                                        firstChange = true;
                                    }
                                    else
                                    {
                                        $(typeListMobile[i]).addClass("border-top-0 rounded-top-0 border-bottom rounded-bottom-0");
                                        searchBorderChanges[typeListMobile[i].id] = "border-top-0 rounded-top-0 border-bottom rounded-bottom-0";
                                    }
                                }
                            }
                        }

                        // Show OSA filter results
                        for (let o = 0; o < OSATypeList.length; o++)
                        {
                            let serviceArea = OSATypeList[o].dataset.servicearea;
                            let OSAType = OSATypeList[o].dataset.reqtype;
                            termVal = reqOSAData[serviceArea][OSAType][1].toLowerCase();
                            if (termVal.indexOf(thisTerm) >= 0)
                            {
                                OSATypeList[o].style.display = "";
                                OSATypeList[o].parentNode.style.display = "";
                                OSATypeListMobile[o].style.display = "";
                                OSATypeListMobile[o].parentNode.style.display = "";
                            }
                        }
                    }
                }

                if (firstChange == true)
                {
                    let insID = "reqTypeOptionMobile-" + typeList.length;
                    $("#"+insID).addClass("border-top-0 rounded-top-0 border-bottom rounded-bottom");
                    searchBorderChanges[insID] = "border-top-0 rounded-top-0 border-bottom rounded-bottom";
                }

                $("div[id^='reqOSAGroupMobile']").find("a:visible:first").addClass("border-top rounded-top");
                $("div[id^='reqOSAGroupMobile']").find("a:visible:last").addClass("border-bottom rounded-bottom");

                // Show FAQ results
                searchWhenTyped = setTimeout(reqSearchConfluence, 500, term.join(" "));
            }
            else
            {
                $(".collapse[id^='reqType']").collapse('hide');
                $("div[id^='reqTypeList']").addClass("mb-2");
                $(".collapse[id^='reqType']").removeClass('no-transition');
            }
        }
        else
        {
            for (let i = 0; i < typeList.length-1; i++)
            {
                typeList[i].style.display = "";
                typeListMobile[i].style.display = "";
            }
            for (let i = 0; i < OSATypeList.length-1; i++)
            {
                OSATypeList[i].style.display = "";
                OSATypeListMobile[i].style.display = "";
            }
            for (let i = 0; i < categoryList.length; i++)
            {
                categoryList[i].style.display = "";
                categoryListMobile[i].style.display = "";
            }
            document.getElementById('reqOSASelectMenu').style.display = "none";
            document.getElementById('reqOSASelectMenuMobile').style.display = "none";
            $("div[id^='reqOSAGroup']").hide();
            $("div[id^='reqOSAGroupMobile']").hide();
            $(".collapse[id^='reqType']").collapse('hide');
            $("div[id^='reqTypeList']").addClass("mb-2");
            $(".collapse[id^='reqType']").removeClass('no-transition');
            reqHideConfluence();
            $("#reqFAQLoading,#reqFAQLoadingMobile").removeClass("d-flex");
            $("#reqFAQLoading,#reqFAQLoadingMobile").addClass("d-none");
        }
    }
    async function reqSearchConfluence(term)
    {
<?php
if ($devMode == "BETA" || $devMode == "PRD")
{
?>
        dataLayer.push({
            event: "search",
            search_term: term,
        });
<?php
}
?>
        //Call to Confluence REST API
        let url = "../helpUtils/doConfluenceSearch.php?term=" + term + "&order=ID asc";
        const faqOptions = {
            method: "GET",
            headers: {
                "Accept": "application/json",
                "Connection": "keep-alive",
                "Access-Control-Allow-Origin": "*",
            },
        };

        const response = await fetch(url, faqOptions);
        const searchResults = await response.json();

        let resultsOut = "";
        let resultsOutMobile = "";
        
        // If any results returned, show the FAQ group
        if ("status" in searchResults && searchResults.status == 200)
        {
            if ('size' in searchResults && searchResults.size > 0)
            {
                // Format first 4 results
                for (i = 0; i < searchResults.size; i++)
                {
                    resultsOut += "<a href=\"" + searchResults.results[i].url + "\" id=\"reqFAQOption-" + i + "\" target=\"_blank\" class=\"list-group-item list-group-item-action";
                    
                    resultsOut += "\" style=\"border:0;\" data-servicearea=\"FAQs\" data-reqtype=\"FAQs_" + i + "\">\n"
                        + "<div class=\"px-2\">" + searchResults.results[i].title + "</div>\n"
                        + "</a>\n";
                    resultsOutMobile += "<a href=\"" + searchResults.results[i].url + "\" id=\"reqFAQOptionMobile-" + i + "\" target=\"_blank\" class=\"list-group-item list-group-item-action py-4 ";
                    if (i == 0)
                    {
                        resultsOutMobile += "border-top rounded-top";
                    }
                    resultsOutMobile += "\" data-servicearea=\"FAQs\" data-reqtype=\"FAQs_" + i + "\">\n"
                        + "<div class=\"px-2\" style=\"text-align:center;\">" + searchResults.results[i].title + "</div>\n"
                        + "</a>\n";
                }

                // Add "More Results" button
                resultsOut += "<a href=\"" + searchResults.moreFAQs + "\" id=\"reqFAQOption-More\" target=\"_blank\" class=\"list-group-item list-group-item-action\" style=\"border:0;\" data-servicearea=\"FAQs\" data-reqtype=\"FAQs_More\">\n"
                    + "<div class=\"px-2\">More FAQs...</div>\n"
                    + "</a>\n";
                resultsOutMobile += "<a href=\"" + searchResults.moreFAQs + "\" id=\"reqFAQOptionMobile-More\" target=\"_blank\" class=\"list-group-item list-group-item-action py-4 border-bottom rounded-bottom\" data-servicearea=\"FAQs\" data-reqtype=\"FAQs_More\">\n"
                    + "<div class=\"px-2\" style=\"text-align:center;\">More FAQs...</div>\n"
                    + "</a>\n";

                document.getElementById("reqFAQSearchGroup").innerHTML = resultsOut;
                document.getElementById("reqFAQSelectMenu").style.display = "";
                
                document.getElementById("reqFAQSearchGroupMobile").innerHTML = resultsOutMobile;
                $("#reqFAQSelectMenuMobile").removeClass("d-none");
                $("#reqFAQSelectMenuMobile").addClass("d-flex");
            }
            else if ('size' in searchResults && searchResults.size == 0)
            {
                reqHideConfluence();
            }
        }
        else
        {
            console.log("Error: Confluence returned status " + searchResults.status + "\nMessage: " + searchResults.errMsg);
            reqHideConfluence();
        }
        $("#reqFAQLoading,#reqFAQLoadingMobile").removeClass("d-flex");
        $("#reqFAQLoading,#reqFAQLoadingMobile").addClass("d-none");
    }
    function reqHideConfluence()
    {
        document.getElementById("reqFAQSelectMenu").style.display = "none";
        document.getElementById("reqFAQSearchGroup").innerHTML = "";

        $("#reqFAQSelectMenuMobile").removeClass("d-flex");
        $("#reqFAQSelectMenuMobile").addClass("d-none");
        document.getElementById("reqFAQSearchGroupMobile").innerHTML = "";
    }

    window.onbeforeunload = prepareSubmit;
    </script>
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
                            <a href="../studentServices/studentServices-support.php" class="nav-link text-white active">
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
        <span style="position:absolute;float:top;left:50vw;top:0;color:lime;font-weight:bold;border-color:white"><?php echo $devMode ?></span>
<?php
}
?>
</nav>
<div <?php echo ($embedMode ? "" : "id=\"umbcNavAccent\""); ?>></div>
<div class="container-fluid py-2 border-end border-start border-bottom rounded-bottom umbcHelpFormContainer" <?php echo ($embedMode ? "style=\"border-bottom: 0; width:100%; height:100%; position:fixed; overflow-y: hidden\"" : ""); ?>>
    <div class="py-0 my-0" <?php echo ($embedMode ? "style=\"overflow-y: auto\"" : "");?>>
        <form id="RTSendRequest" name="RTSendRequest" action="<?php echo $_SERVER['PHP_SELF'];?>" enctype="multipart/form-data" method="post" onsubmit="return validateInput();">
            <div class="container-fluid" id="ReqTypeSet">
                <input type="hidden" id="reqAuto" value="<?php echo $autoReqType;?>" />

                <div class="row mb-3" id="reqTypeRow" <?php echo ($autoReqType ? "style=\"display:none;\"" : "");?>>
                    <div class="col-form-label" style="display:none;">
                        <span id="spn-reqType" class="req-span">*</span>
                        <label class="form-label" for="reqType">Request Type:</label>
                    </div>
                    <div class="px-0 pe-md-0 px-lg-0 mx-0 w-100" id="reqSelectInit">
                        <div class="row">
                            <div class="col-4 d-none d-md-block border-end px-4">
                                <div class="input-group py-3">
                                    <div class="input-group-text">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 512 512" fill="currentColor">
                                            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                            <path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/>
                                        </svg>
                                    </div>
                                    <input id="reqSelectFilter" name="reqSelectFilter" type="text" class="form-control" oninput="reqFilterSelect()" placeholder="Search topics..." />
                                </div>
                                <div class="list-group list-group-flush" id="reqSelectMenu" style="">
<?php
$temp1 = array_keys($reqCategoryList);
$temp2 = array_keys($reqTypeList);

$temp3 = array_pop($temp1);
sort($temp1);
array_push($temp1, $temp3);

for ($i = 0; $i < count($temp1)-1; $i++)
{
?>
                                    <div id="reqTypeGroup-<?php echo $i;?>" class="accordion">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button id="reqTypeCategory-<?php echo $i;?>" type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#reqTypeList-<?php echo $i;?>" aria-expanded="false" aria-controls="reqTypeList-<?php echo $i;?>" value="<?php echo $temp1[$i];?>">
                                                    <div><?php echo $temp1[$i];?></div>
                                                </button>
                                            </h2>
                                            <div class="accordion-collapse collapse border-none mb-2" id="reqTypeList-<?php echo $i;?>" aria-labeledby="reqTypeCategory-<?php echo $i;?>" data-parent="#reqTypeGroup-<?php echo $i;?>">
<?php
    for ($j = 0; $j < count($temp2); $j++)
    {
        if (array_search($temp2[$j], $reqCategoryList[$temp1[$i]]) !== false)
        {
?>
                                                <button type="button" id="reqTypeOption-<?php echo $j;?>" class="list-group-item list-group-item-action" style="border:0;" onclick="reqDisplayInfo(<?php echo $j;?>);" value="<?php echo $temp2[$j];?>">
                                                    <div class="px-2"><?php echo $temp2[$j];?></div>
                                                </button>
<?php
        }
    }
?>
                                            </div>
                                        </div>
                                    </div>
<?php
}
?>
                                    <div id="reqTypeGroup-<?php echo (count($temp1) - 1);?>" class="accordion">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <a id="reqTypeCategory-<?php echo (count($temp1) - 1);?>" class="accordion-button collapsed genSupport" href="<?php echo "./" . $SERVICE_HEADER . "-requestHelp.php?r=" . base64_encode("I'm not sure / Other");?>">I'm not sure / Other</a>
                                            </h2>
                                            <div class="accordion-collapse collapse border-none mb-2" id="reqTypeList-<?php echo (count($temp1) - 1);?>">
                                                <a id="reqTypeOption-<?php echo (count($temp2) - 1);?>" class="list-group-item list-group-item-action" style="border:0;" href="<?php echo "./" . $SERVICE_HEADER . "-requestHelp.php?r=" . base64_encode("I'm not sure / Other");?>">
                                                    <div class="px-2">I'm not sure / Other</div>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="list-group list-group-flush mt-3" id="reqOSASelectMenu" style="display:none;">
<?php
    $serviceAreas = array_keys($reqOSAData);
    for ($sa = 0; $sa < count($serviceAreas); $sa++)
    {
?>
                                    <div id="reqOSAGroup-<?php echo ($sa+1);?>" class="mb-3">
                                        <h6 class="ms-4"><?php echo $serviceAreas[$sa];?></h6>
<?php
        for ($t = 0; $t < count($reqOSAData[$serviceAreas[$sa]]); $t++)
        {
            $typesForSA = array_keys($reqOSAData[$serviceAreas[$sa]]);
?>
                                        <a href="<?php echo $reqOSAData[$serviceAreas[$sa]][$typesForSA[$t]][2];?>" id="reqOSAOption-<?php echo $t;?>" class="list-group-item list-group-item-action" style="border:0;" data-servicearea="<?php echo $serviceAreas[$sa];?>" data-reqtype="<?php echo $reqOSAData[$serviceAreas[$sa]][$typesForSA[$t]][0];?>">
                                            <div class="px-2"><?php echo $reqOSAData[$serviceAreas[$sa]][$typesForSA[$t]][0];?></div>
                                        </a>
<?php
        }
?>
                                    </div>
<?php
    }
?>
                                </div>
                                <div id="reqFAQLoading" class="d-none justify-content-center">
                                    <div class="spinner-border text-secondary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                                <div class="list-group list-group-flush" id="reqFAQSelectMenu" style="display:none;">
                                    <h6 class="ms-4">FAQs</h6>
                                    <div id="reqFAQSearchGroup" class="px-0 py-0">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-12 col-md-8 py-2 px-4">
                                <div class="sticky-top">
                                    <input id="reqSelectCurrent" type="hidden" value="init" required />
                                    <article id="reqSelectDesc-init" class="px-4 py-4" <?php echo (!empty($autoDisplayInfo) ? "style=\"display:none;\"" : ""); ?>>
                                        <h2 class="mb-4"><?php echo $SERVICE_AREA; ?></h2>
                                        <div class="d-block d-md-none">
                                            <div class="input-group py-3">
                                                <div class="input-group-text">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 512 512" fill="currentColor">
                                                        <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                                        <path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/>
                                                    </svg>
                                                </div>
                                                <input id="reqSelectFilterMobile" name="reqSelectFilterMobile" type="text" class="form-control" oninput="reqFilterSelect(1)" placeholder="Search topics..." />
                                            </div>
                                        </div>
                                        
                                        <p>Select a category <span class="d-none d-md-inline">to the left </span>for more information.</p>
                                        <div id="reqSelectMenuMobile" class="row row-cols-1 g-0 d-flex d-md-none">
<?php
// Mobile layout
for ($i = 0; $i < count($temp1)-1; $i++)
{
?>
                                            <div id="reqTypeGroupMobile-<?php echo $i;?>" class="col">
                                                <h2 class="card mb-0 pb-0" id="reqTypeCategoryMobile-<?php echo $i;?>">
                                                    <button id="reqTypeMobileHeader-<?php echo $i;?>" type="button" class="btn btn-primary serviceCategoryButtonMobile collapsed py-4" data-bs-toggle="collapse" data-bs-target="#reqTypeListMobile-<?php echo $i;?>" aria-expanded="false" aria-controls="reqTypeListMobile-<?php echo $i;?>" value="<?php echo $temp1[$i];?>">
                                                        <div><?php echo $temp1[$i];?></div>
                                                    </button>
                                                </h2>
                                                <div class="list-group collapse" id="reqTypeListMobile-<?php echo $i;?>" aria-labeledby="reqTypeCategoryMobile-<?php echo $i;?>" data-parent="#reqTypeGroupMobile-<?php echo $i;?>" style="padding: 0 !important;">
<?php
    for ($j = 0; $j < count($temp2); $j++)
    {
        if (array_search($temp2[$j], $reqCategoryList[$temp1[$i]]) !== false)
        {
?>
                                                    <button type="button" id="reqTypeOptionMobile-<?php echo $j;?>" class="list-group-item list-group-item-action py-4" onclick="reqDisplayInfo(<?php echo $j;?>);" value="<?php echo $temp2[$j];?>">
                                                        <div class="px-2" style="text-align:center;"><?php echo $temp2[$j];?></div>
                                                    </button>
<?php
        }
    }
?>
                                                </div>
                                            </div>
<?php
}
?>
                                            <div id="reqTypeGroupMobile-<?php echo (count($temp1) - 1);?>" class="col">
                                                <h2 class="card mb-0 pb-0" id="reqTypeCategoryMobile-<?php echo (count($temp1) - 1);?>">
                                                    <a id="reqTypeMobileHeader-<?php echo (count($temp1) - 1);?>" type="button" class="btn btn-primary serviceCategoryButtonMobile collapsed py-4" href="<?php echo "./" . $SERVICE_HEADER . "-requestHelp.php?r=" . base64_encode("I'm not sure / Other");?>">
                                                        <div>I'm not sure / Other</div>
                                                    </a>
                                                </h2>
                                                <div class="list-group collapse" id="reqTypeListMobile-<?php echo (count($temp1) - 1);?>" aria-labeledby="reqTypeCategoryMobile-<?php echo (count($temp1) - 1);?>" data-parent="#reqTypeGroupMobile-<?php echo (count($temp1) - 1);?>" style="padding: 0 !important;">
                                                    <a id="reqTypeOptionMobile-<?php echo (count($temp2) - 1);?>" class="list-group-item list-group-item-action py-4" href="<?php echo "./" . $SERVICE_HEADER . "-requestHelp.php?r=" . base64_encode("I'm not sure / Other");?>">
                                                        <div class="px-2" style="text-align:center;">I'm not sure / Other</div>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row row-cols-1 list-group list-group-flush g-0 d-flex d-md-none mt-5" id="reqOSASelectMenuMobile" style="display:none;">
<?php
    $serviceAreas = array_keys($reqOSAData);
    for ($sa = 0; $sa < count($serviceAreas); $sa++)
    {
?>
                                            <div id="reqOSAGroupMobile-<?php echo ($sa+1);?>" class="mb-5" style="display: none;">
                                                <h6 class="ms-1"><?php echo $serviceAreas[$sa];?></h6>
<?php
        for ($t = 0; $t < count($reqOSAData[$serviceAreas[$sa]]); $t++)
        {
            $typesForSA = array_keys($reqOSAData[$serviceAreas[$sa]]);
?>
                                                <a href="<?php echo $reqOSAData[$serviceAreas[$sa]][$typesForSA[$t]][2];?>" id="reqOSAOptionMobile-<?php echo $t;?>" class="list-group-item list-group-item-action py-4" data-servicearea="<?php echo $serviceAreas[$sa];?>" data-reqtype="<?php echo $reqOSAData[$serviceAreas[$sa]][$typesForSA[$t]][0];?>">
                                                    <div class="px-2" style="text-align:center;"><?php echo $reqOSAData[$serviceAreas[$sa]][$typesForSA[$t]][0];?></div>
                                                </a>
<?php
        }
?>
                                            </div>
<?php
    }
?>
                                        </div>
                                        <div id="reqFAQLoadingMobile" class="d-none d-md-none justify-content-center">
                                            <div class="spinner-border text-secondary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </div>
                                        <div class="row row-cols-1 list-group list-group-flush g-0 d-none d-md-none" id="reqFAQSelectMenuMobile" style="display:none;">
                                            <h6 class="ms-1">FAQs</h6>
                                            <div id="reqFAQSearchGroupMobile" class="px-0 py-0">
                                            </div>
                                        </div>
                                    </article>
<?php
for ($i = 0; $i < count($temp2); $i++)
{
    if (!empty($autoDisplayInfo) && $autoDisplayInfo == $temp2[$i])
    {
        echo "<script type=\"text/javascript\">document.getElementById('reqSelectCurrent').value = '$i';</script>";
    }
?>
                                    <article class="px-4 py-4" id="reqSelectDesc-<?php echo $i;?>" <?php echo (!empty($autoDisplayInfo) && $autoDisplayInfo == $temp2[$i] ? "" : "style=\"display:none;\"");?>>
                                        <button type="button" class="d-block d-md-none btn btn-secondary mb-4" onclick="reqDisplayInfo('init');">
                                            &#8592;&nbsp;Go back
                                        </button>
                                        <h2 class="mb-4"><?php echo $temp2[$i];?></h2>
                                        <p class="mb-4"><?php echo $reqTypeList[$temp2[$i]][0];?></p>
<?php
    $tempCategory = $reqTypeList[$temp2[$i]][5];
    $tempCategoryInfo = $reqCategoryInfoList[$tempCategory][$temp2[$i]];
    $faqCategoryInfo = array();
    $quickLinkCategoryInfo = array();
    if (count($tempCategoryInfo) > 0)
    {
        foreach ($tempCategoryInfo as $listItem)
        {
            if ($listItem[1] == "FAQ")
            {
                array_push($faqCategoryInfo, $listItem);
            }
            else if ($listItem[1] == "Link")
            {
                array_push($quickLinkCategoryInfo, $listItem);
            }
        }
        if (count($faqCategoryInfo) > 0)
        {

?>
                                        <h3 class="pt-3">Top FAQs</h3>
                                        <ul>
<?php
            foreach ($faqCategoryInfo as $listItem)
            {
?>
                                            <li>
                                                <a href="<?php echo $listItem[2];?>" target="_blank"><?php echo $listItem[0];?></a>
                                            </li>
<?php
            }
        }
?>
                                        </ul>
                                        <hr style="border:0;" />
<?php
        if (count($quickLinkCategoryInfo) > 0)
        {
?>
                                        <h3>Quick Links</h3>
                                        <ul>
<?php
            foreach ($quickLinkCategoryInfo as $listItem)
            {
?>
                                            <li>
                                                <a href="<?php echo $listItem[2];?>" target="_blank"><?php echo $listItem[0];?></a>
                                            </li>
<?php
            }
        }
?>
                                        </ul>
                                        <hr style="border:0;" />
<?php
    }
?>
                                        <a type="button" id="reqTypeOption-<?php echo $i;?>" class="btn btn-primary" style="color:#007bff;" href="
<?php 
    $clickAction = $reqTypeList[$temp2[$i]][2];
    $redirect = $reqTypeList[$temp2[$i]][3];
    if ($clickAction == "Link" || $clickAction == "Embed")
    {
        echo $redirect;
    }
    else
    {
        echo "./" . $SERVICE_HEADER . "-requestHelp.php?r=" . base64_encode($temp2[$i]);
    }
?>">
                                            Request Help
                                        </a>
                                    </article>
<?php
}
?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
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