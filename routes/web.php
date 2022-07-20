<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/', function () {
    return view('pagecrawl');
});

Route::post('/', function () {
    // returnData will hold information being returned to the view
    $returnData = [];

    // Error messages is an emptry string to begin with
    $returnData['postError'] = "";

    // Check if the url has been passed, if not, set variable to null
    $url = ( isset($_POST['myUrl']) ? strtolower($_POST['myUrl']) : null );

    // Check if the crawlDepth has been passed and is valid, if not, default to 1
    $crawlDepth = ((isset($_POST['myCrawlDepth']) && $_POST['myCrawlDepth'] >= 1 && $_POST['myCrawlDepth'] <= 10) ? $_POST['myCrawlDepth'] : 1 );

    // Send the crawled url and crawl depth back to the view
    $returnData['pageUrl'] = $url;
    $returnData['crawlDepth'] = $crawlDepth;

    // If the url given isn't an AgencyAnalytics url, add that error to the list of errors to pass back to the view
    if ( $url !== null && strpos($url, "agencyanalytics.com") === false  ) {
        $returnData['postError'] .= "This is not an AgencyAnalytics Url.\n";
    }

    // If no errors have been encountered, we can begin crawling
    if($returnData['postError'] == "" && $url !== null) {
        $crawler = new App\Http\Controllers\crawlerController;
        $returnData['crawl'] = $crawler->crawlCommander($url, $crawlDepth);
    }

    return view('pagecrawl', $returnData);
});