<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class crawlerController extends Controller
{
    /**
     * Start the page crawl.
     *
     * @param  str  $url
     */
    public function crawlCommander($url, $depth) {
        $pages = [];
        $nextUrl = $url;
        $failedCount = 0;
        $depthTracker = $depth;

        while ($depthTracker > 0) {
            if($failedCount > 10) break;
            $crawledPageResults = $this->crawlPage($nextUrl);
            if($crawledPageResults !== false && $crawledPageResults['internalLinks'] != null) {
                $pages[$nextUrl] = $crawledPageResults;
                $x = array_rand($crawledPageResults['internalLinks']);
                while (in_array($crawledPageResults['internalLinks'][$x], array_keys($pages)) && $crawledPageResults['internalLinks'][$x] != "" ) {
                    $x = array_rand($crawledPageResults['internalLinks']);
                }
                $nextUrl = "https://agencyanalytics.com" . $crawledPageResults['internalLinks'][$x];
                $depthTracker--;
            } else {
                // something failed, go back and try a url from a previously scanned page
                $failedCount++;
                $pageIdx = array_rand($pages);
                $nextUrl = "https://agencyanalytics.com" . $pages[$pageIdx]['internalLinks'][array_rand($pages[$pageIdx]['internalLinks'])];
            }
        }

        //dd($pages);

        // Formatting data to return to the view
        $returnData = [];
        // Number of pages crawled
        $returnData['totalPagesCrawled'] = count($pages);

        $returnData['pages'] = [];
        $uniqueImages = [];
        $uniqueInternalLinks = [];
        $uniqueExternalLinks = [];
        $totalPageLoadTime = 0;
        $totalWordCount = 0;
        $totalTitleLength = 0;


        foreach ($pages as $pageName => $pageData) {
            
            // Number of a unique images
            foreach ($pageData['images'] as $img) {
                if(!in_array($img, $uniqueImages)) $uniqueImages[] = $img;
            }

            // Number of unique internal links
            foreach ($pageData['internalLinks'] as $link) {
                if(!in_array($link, $uniqueInternalLinks)) $uniqueInternalLinks[] = $link;
            }

            // Number of unique external links
            foreach ($pageData['externalLinks'] as $link) {
                if(!in_array($link, $uniqueExternalLinks)) $uniqueExternalLinks[] = $link;
            }

            $totalPageLoadTime += $pageData['pageLoadTime'];
            $totalWordCount += $pageData['pageWordCount'];
            $totalTitleLength += $pageData['titleLength'];

            $returnData['pages'][$pageName] = $pageData['httpCode'];

        }

        $returnData['totalUniqueImages'] = count($uniqueImages);
        $returnData['uniqueImages'] = $uniqueImages;

        $returnData['totalUniqueInternalLinks'] = count($uniqueInternalLinks);
        $returnData['uniqueInternalLinks'] = $uniqueInternalLinks;

        $returnData['totalUniqueExternalLinks'] = count($uniqueExternalLinks);
        $returnData['uniqueExternalLinks'] = $uniqueExternalLinks;

        // Average page load in seconds
        $returnData['avgPageLoad'] = $totalPageLoadTime / $depth;

        // Average word count
        $returnData['avgWordCount'] = $totalWordCount / $depth;

        // Average title length
        $returnData['avgTitleLength'] = $totalTitleLength / $depth;

        // Url
        // StatusCode

        //dd($returnData);
        return $returnData;
    }

    private function crawlPage($url) {
        // returnData will hold information being returned to the view
        $returnData = [];

        // Grab the page using CURL and load it into a DOMdocument (and grab page load time)
        $dom = new \DOMDocument;
        $pageLoadStart = microtime(true);
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36');
        curl_setopt ($ch, CURLOPT_HEADER, 0);
        curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        $returnval = curl_exec ($ch);
        @$dom->loadHTML($returnval);
        $returnData['pageLoadTime'] = number_format(microtime(true) - $pageLoadStart, 3);
        $returnData['httpCode'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($returnData['httpCode'] == 0) {
            //dd(curl_error($ch));
            dd($url);
            return false;
        }

        // Get page contents with tags stripped
        //if(null == $dom->getElementsByTagName("body")->item(0)) dd($url . " - ".count($dom->getElementsByTagName("body")) . " - ".$returnData['httpCode']);
        $strippedHtml = $dom->getElementsByTagName("body")->item(0);
        if(null != $strippedHtml->getElementsByTagName("script")) {
            while (($scr = $strippedHtml->getElementsByTagName("script")) && $scr->length) {
                $scr->item(0)->parentNode->removeChild($scr->item(0));
            }
        }
        $cleanedHtml = $strippedHtml->ownerDocument->saveHTML($strippedHtml);
        $cleanedHtml = str_replace( '<', ' <', $cleanedHtml); // Adding a space before html tags to prevent words from running together after we strip tags
        $cleanedHtml = strip_tags($cleanedHtml);
        $cleanedHtml = str_replace('&amp;', ' ', $cleanedHtml);
        $cleanedHtml = trim(preg_replace('/\\n/', ' ', $cleanedHtml));
        $cleanedHtml = trim(preg_replace('/\s+/', ' ', $cleanedHtml));
        $returnData['pageTextOnly'] = $cleanedHtml;
        $returnData['pageWordCount'] = str_word_count($cleanedHtml);
        
    
        // Title tag length
        $titleTag = $dom->getElementsByTagName("title");
        $returnData['titleTag'] = $titleTag->item(0)->nodeValue;
        $returnData['titleLength'] = strlen(trim($returnData['titleTag']));

        // Count the img tags
        $imgs = $dom->getElementsByTagName("img");
        $returnData['images'] = [];
        foreach ($imgs as $img) {
            $returnData['images'][] = ($img->getAttribute('src') != "" ? $img->getAttribute('src') : $img->getAttribute('data-src') );
        }
        $returnData['totalImages'] = count($imgs);

        // Grab and analyze the links
        $links = $dom->getElementsByTagName("a");
        $returnData['totalInternalLinks'] = 0;
        $returnData['totalExternalLinks'] = 0;
        $returnData['externalLinks'] = [];
        $returnData['internalLinks'] = [];

        foreach ($links as $link) {
            $href = strtolower($link->getAttribute('href'));
            if(strpos($href, "://") !== false){
                $returnData['totalExternalLinks']++;
                $returnData['externalLinks'][] = $href;
            } else {
                $returnData['totalInternalLinks']++;
                $returnData['internalLinks'][] = $href;
            }
        }

        // Only return unique external links
        $foundLinksExt = [];
        foreach ($returnData['externalLinks'] as $k => $v) {
            if(!in_array($v, $foundLinksExt)){
                $foundLinksExt[] = $v;
            } else {
                unset($returnData['externalLinks'][$k]);
            }
        }
        // Re-index the array
        $returnData['externalLinks'] = array_values($returnData['externalLinks']);

        // Only return unique internal links
        $foundLinksInt = [];
        foreach ($returnData['internalLinks'] as $k => $v) {
            if(!in_array($v, $foundLinksInt)){
                $foundLinksInt[] = $v;
            } else {
                unset($returnData['internalLinks'][$k]);
            }
        }
        // Re-index the array
        $returnData['internalLinks'] = array_values($returnData['internalLinks']);

        return $returnData;
    }
}
