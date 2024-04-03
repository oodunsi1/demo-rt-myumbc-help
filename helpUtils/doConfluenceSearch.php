<?php
require_once("../../rtdev_Utils/rtConfluenceAPI.php");

session_start();

if (!isset($_SESSION['activeUser']) || empty($_SESSION['activeUser']) || $_SESSION['activeUser'] == false)
{
    http_response_code(403);
    exit();
}

$term = filter_input(INPUT_GET, "term", FILTER_SANITIZE_STRING);
$order = filter_input(INPUT_GET, "order", FILTER_SANITIZE_STRING);

if (empty($term) && empty($order))
{
    header("Location: ../index.php");
    exit();
}

$cached = apcu_fetch("searchCache#".$term);
if ($cached !== false)
{
    echo json_encode($cached);
    exit();
}

$confluence = new UmbcRTConfluenceAPI();

$cql = "(title~\"" . urlencode($term) . "\")";
if (!empty($order))
{
    $cql .= " ORDER BY " . $order;
}

$response = $confluence->search($cql, "page", "faq", false);

// if status 200, status will not be set in return for some reason
if (!isset($response['status']))
{
    $response['status'] = 200;
}

$toReturn = array();

// if status 200, parse up to 5 results to send back
if ($response['status'] == 200)
{
    $returnSize = ($response['size'] > 5 ? 5 : $response['size']);
    
    $toReturn['status'] = $response['status'];
    $toReturn['size'] = $returnSize;
    $toReturn['moreFAQs'] = "https://umbc.atlassian.net/wiki/search?spaces=faq&text=" . urlencode($term);
    $toReturn['results'] = array();
    
    for ($i = 0; $i < $returnSize; $i++)
    {
        $toReturn['results'][$i] = array();

        $title = $response['results'][$i]['title'];
        $title = preg_replace('/@@@hl@@@/', "<b>", $title);
        $title = preg_replace('/@@@endhl@@@/', "</b>", $title);
        //$title = preg_replace('/@@@hl@@@/', "", $title);
        //$title = preg_replace('/@@@endhl@@@/', "", $title);
        $toReturn['results'][$i]['title'] = $title;

        $url = $confluence->getBaseDomain() . $response['results'][$i]['url'];
        $toReturn['results'][$i]['url'] = $url;
    }

    if ($returnSize > 0)
    {
        apcu_store("searchCache#".$term, $toReturn, 86400);
    }
}

//if not 200, send back response as-is

echo json_encode($toReturn);

exit();