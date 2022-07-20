<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Agency Analytics Page Crawler</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">

        <!-- Styles -->
        <style>
            html, body {
                background-color: #fff;
                font-family: 'Nunito', sans-serif;
                font-weight: 200;
                height: 100vh;
                margin: 0;
                color: #636b6f;
                font-size:2vw;
            }
            #urlInput {
                font-size: clamp(16px, 5vw, 50px);
                padding: 1vw;
                margin: 20px;
                border-radius: 8px;
                width: 80vw;
            }
            #urlDepth{
                font-size: clamp(16px, 5vw, 50px);
                padding: 1vw;
                margin: 20px;
                border-radius: 8px;
                width: 4vw;
            }
            #formWrapper {
                text-align: center;
            }
            #errorMessages {
                color: red;
                font-weight: bold;
                font-size: clamp(16px, 3vw, 35px);
            }
            #crawledDataOutput {
                padding: 2vw;
                display: inline-block;
                text-align: center;
            }
            #loadingGif {
                display: none;
            }
            #crawledDataOutput div {
                text-align: left;
            }
            #pagesTable td {
                border: 2px solid black;
                padding: 0.5vw;
            }
        </style>
    </head>
    <body>
        <div id="formWrapper">
            <p>Enter a full url from https://agencyanalytics.com/, choose the number of pages to crawl and then hit ENTER.</p>
            <form id="urlCrawlForm" method="post" action="/">
                @csrf
                <input id="urlInput" name="myUrl" type="text" placeholder="Url to crawl" value="@if(isset($pageUrl)){{$pageUrl}}@endif" />
                <input id="urlDepth" name="myCrawlDepth" placeholder=1 type="number" min="1" max="10" value="@if(isset($crawlDepth)){{$crawlDepth}}@endif" />
            </form>
            <center><img id="loadingGif" src="/img/loading.gif" /></center>
            @if(isset($postError))
                <div id="errorMessages">
                    {{$postError}}
                </div>
            @endif

            @if(isset($pageUrl) and $postError == "")
                <div id="crawledDataOutput">
                    <div>Number of pages crawled: <b>{{$crawl['totalPagesCrawled']}}</b></div>
                    <div>Number of a unique images: <b>{{$crawl['totalUniqueImages']}}</b></div>
                    <div>Number of unique internal links: <b>{{$crawl['totalUniqueInternalLinks']}}</b></div>
                    <div>Number of unique external links: <b>{{$crawl['totalUniqueExternalLinks']}}</b></div>
                    <div>Average page load in seconds: <b>{{$crawl['avgPageLoad']}}</b></div>
                    <div>Average word count: <b>{{$crawl['avgWordCount']}}</b></div>
                    <div>Average title length: <b>{{$crawl['avgTitleLength']}}</b></div><br />
                    <table id="pagesTable" class="table-bordered">
                        <tr>
                            <td style="text-align:left; background-color:#0072EE; color:white;"><b>Url</b></td>
                            <td style=" background-color:#0072EE; color:white;"><b>HTTP Status</b></td>
                        </tr>
                        @foreach ($crawl['pages'] as $page => $code)
                        <tr>
                            <td style="text-align:left;">{{$page}}</td>
                            <td>{{$code}}</td>
                        </tr>
                        @endforeach
                    </table>
                </div>
            @endif
        </div>
        <script type="text/javascript">
        // Submit form when ENTER is pressed
        crawlForm = document.getElementById("urlCrawlForm");
        crawlForm.addEventListener("keypress", (event)=> {
            if (event.keyCode === 13) {
                document.getElementById("loadingGif").style.display = 'block';
                document.getElementById("urlCrawlForm").submit(); 
            }
        });
        </script>
    </body>
</html>

<!-- 
array:11 [▼
  "totalPagesCrawled" => 6
  "pages" => array:6 [▼
    "https://agencyanalytics.com/" => 200
    "https://agencyanalytics.com/report-templates" => 200
    "https://agencyanalytics.com/feature/white-label" => 200
    "https://agencyanalytics.com/competitor/seranking-alternative" => 200
    "https://agencyanalytics.com/competitor/ninjacat-alternative" => 200
    "https://agencyanalytics.com/company/customers" => 200
  ]
  "totalUniqueImages" => 109
  "uniqueImages" => array:109 [▶]
  "totalUniqueInternalLinks" => 160
  "uniqueInternalLinks" => array:160 [▶]
  "totalUniqueExternalLinks" => 37
  "uniqueExternalLinks" => array:37 [▶]
  "avgPageLoad" => 0.29
  "avgWordCount" => 1386.8
  "avgTitleLength" => 75.8
]
 -->