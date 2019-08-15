<?php
    // make sure browsers see this page as utf-8 encoded HTML
include 'simple_html_dom.php';
include 'SpellCorrector.php';
header('Content-Type: text/html; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:11080', false);
$limit = 10;
$start = 0;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$results = false;
$file = fopen("URLtoHTML_reuters_news.csv", "r");
$map = array();
$correctWord = "";
if(isset($_REQUEST["query"])){
    $correctWord = SpellCorrector::correct($_REQUEST["query"]);
    exit ($correctWord);
}
if($file){
    while($line = fgetcsv($file,0,",")){
        $key = "/Users/sophiahat/Documents/School/Web Search Engine/HW/HW4/solr-8.0.0/reutersnews/" . $line['0'];
	    $value = $line['1'];
	    $map[$key] = $value;
    }
}
if ($query)
{
    // The Apache Solr Client library should be on the include path
    // which is usually most easily accomplished by placing in the
    // same directory as this script ( . or current directory is a default
    // php include path entry in the php.ini)
    require_once('../solr-php-client/Apache/Solr/Service.php');
    // create a new solr service instance - host, port, and corename
    // path (all defaults in this example)
    $solr = new Apache_Solr_Service('localhost', 8983, '/solr/myexample/');
    // if magic quotes is enabled then stripslashes will be needed
    if (get_magic_quotes_gpc() == 1)
    {
        $query = stripslashes($query);
    }
    // in production code you'll always want to use a try /catch for any
    // possible exceptions emitted by searching (i.e. connection
    // problems or a query parsing error)
    try
    {
        //$results = $solr->search($query, $start, $rows, $additionalParameters);
        if($_REQUEST['order']=='solr')
            $additionalParameters = array('sort' => '');
            //$results = $solr->search($query, $start, $limit);
		else{
            $additionalParameters = array('sort' => 'pageRankFile desc');
        }
        $results = $solr->search($query, 0, $limit, $additionalParameters);
    }
    catch (Exception $e)
    {
        // in production you'd probably log or email this error to an admin
        // and then show a special message to the user but for this example
        // we're going to show the full exception
        die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
    }
}
?>
<html>
<head>
    <title>PHP Solr Client Example</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="http://code.jquery.com/jquery-1.9.1.js"></script>
    <script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
    <link rel="stylesheet" href="http://code.jquery.com/ui/1.9.0/themes/smoothness/jquery-ui.css" />
    <style>
        body{
            position: absolute;
        }
        #searchForm{
            width: 600px;
            font-size: 15pt;
            margin-left: 330px;
            line-height: 25pt;
        }
        #results{
            line-height: 25pt;
            font-size: 15pt;
            width: 1200px;
            margin-left: 330px;
        }
        #result a{
            text-decoration: none;
            color: green;
        }
        #submit{
            width: 100px;
            height: 30px;
            border-radius: 3px;
            border: none;
            font-size: 10pt;
            margin-left: 10px;
        }
        a:-webkit-any-link{
            text-decoration: none;
        }
        #spellCheck{
            margin-left: 330px;
            font-size: 15pt;
        }
    </style>
    <script>
        var key = document.getElementById("q");
        $(function(){
        $("#q").autocomplete({
            source: function(request, response){
               // console.log(query);
                var query = $("#q").val();
                var space =  query.lastIndexOf(' ');
                var newQ = "", previous = "";
                var results = [];
                if(query.length - 1 > space || space != -1){
                    newQ = query.substr(space + 1).toLowerCase();
                    previous = query.substr(0, space);
                }
                else{
                    newQ = query.substr(0).toLowerCase();
                }
                var xmlhttp = new XMLHttpRequest();
                try{
                    xmlhttp.open("GET", "main.php?query=" + newQ, false);
                    xmlhttp.send();
                }catch(e){
                    alert(e);
                    return;
                }
                console.log("get correct q: ")
                console.log(xmlhttp.responseText)
                var correctQ = xmlhttp.responseText;
                $.ajax({
                    'url': 'http://localhost:8983/solr/myexample/suggest',
                    'data': {'wt': 'json', 'q': newQ},
                    'success': function(data) { 
                        var list = "";
                        var tmp = [];
                        var words = data.suggest.suggest[newQ].suggestions
                        console.log(words)
                        for(var i = 0; i < words.length; i++){
                            if(words[i].term == newQ || words[i].term == correctQ){
                                continue;
                            }
                            for(var k = 0; k < results.length; k++){
                                if(results[k] == words[i].term){
                                    continue;
                                }
                            }
                            if(words[i].term.indexOf(".") >= 0 || words[i].term.indexOf("_") >= 0){
                                continue;
                            }
                            if(tmp.length == 5)
                                break;
                            var s = words[i].term;
                            if(tmp.indexOf(s) == -1){
                                tmp.push(s);
                                if(previous == "")
                                    results.push(s)
                                else{
                                    s = previous + " " + s;
                                    results.push(s);
                                }
                            } 
                        }
                        if(newQ != correctQ){
                            if(previous != "")
                                correctQ = previous + " " + correctQ;
                            results = [correctQ, ...results];
                        }
                        console.log(results);
                        response(results);
                    },
                    'dataType': 'jsonp',
                    'jsonp': 'json.wrf'
                })
            }
        });
    });
    </script>
</head>
<body>
    <form accept-charset="utf-8" method="get" id="searchForm">
        <label for="q">Search:</label>
        <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
        <datalist id="keywordSuggest">
        </datalist>
        <input type="submit" id="submit" value="submit"/><br>
        <input type="radio" name="order" value="solr" <?php if(!isset($_REQUEST['order']) || $_REQUEST['order']=='solr' ) echo "checked default"; ?>>Solr default&nbsp
        <input type="radio" name="order" value="pageRank" <?php if($_REQUEST['order']=='pageRank') echo "checked pageRank"; ?>>Page Rank
    </form>
    <?php
        if ($results)
        {
    ?>
    <?php
            $right_terms = "";
            $incorrect = false;
            $original_terms = explode(" ", trim($query));
            foreach($original_terms as $original_term){
                $new_term = SpellCorrector::correct($original_term);
                //$new_query = $new_query . " " . trim($new_term);
                $new_query = $new_query.SpellCorrector::correct($original_term)." ";
            }
           // $new_query = $query;
              $new_query = trim($new_query);
            if(strtolower($new_query) != strtolower(trim($query))){
                $ref = "main.php?q=$new_query" . "&order=solr";
                $suggest = "<div id='spellCheck'>Show results for: <a href='$ref'>$new_query</a></div>";
                $new_query = $right_terms;
                echo $suggest;
            }
    ?>
            <div id="results">
            <div>Top 10 Results: </div>
                <table style="border: none; text-align: left">
                <?php
                // iterate result documents
                foreach ($results->response->docs as $doc)
                {
                ?>
                    <?php
                        // iterate document fields / values
                        $search_keyword = trim($_GET["q"]);
                        $id = "N/A";
                        $url = "N/A";
                        $link = "N/A";
                        $title = "N/A";
                        $descript = "N/A";
                        $snippet = "N/A";
                        $finalSnippet = "N/A";
                        foreach ($doc as $field => $value)
                        {
                            if($field == "id")
                                $id = $value;
                            if($field == "title"){
                                if(is_array($value))
                                    $title = $value[0];
                                else
                                    $title = $value;
                            }
                            if($filed == "og_url")
                                $link = $value;
                        }
                        if($link == "N/A")
                            $link = $map[$id];
                        $id_length = strlen($id) - 82;
                        $query_terms = explode(" ", $search_keyword);
                        $lower_query = strtolower($search_keyword);
                        $query_length = sizeof($query_terms);
                        $find_all = false;
                        $max_cnt = 0;
                        $cnt = 0;
                        $findWord = false;
                        $file_content = file_get_contents($id);
	                    $html = str_get_html($file_content);
                        $content =  strtolower($html->plaintext);
                        foreach(preg_split("/((\r?\n)|(\r\n?))/", $content) as $line){
                            $cnt = 0;
                            $lower_line = strtolower($line);
                            if(strpos($lower_line, $lower_query) != false){
                                $find_all =  true;
                                $snippet = $line;
                                $findWord = true;
                                break;
                            }
                            foreach($query_terms as $query_term){
                                $query_term = strtolower($query_term);
                                if(strpos($lower_line, $query_term) != false){
                                    $cnt = $cnt + 1;
                                    $findWord = true;
                                }
                            }
                            if($max_cnt < $cnt){
                                $snippet = $line;
                                $max_cnt = $cnt;
                                if($max_cnt >= $query_length)
                                    break;
                            }
                        }
                        if($findWord == true){
                        $first_pos = 100000;
                        $last_pos = 0;
                        $cur_pos = 0;
                        $totalLen = strlen($line);
                        if($totalLen <= 160){
                            $finalSnippet = $line;
                        }
                        else{
                            foreach($query_terms as $query_term){
                                $query_term = strtolower($query_term);
                                if(strpos(strtolower($snippet), $query_term) != false){
                                    $cur_pos = strpos(strtolower($snippet), $query_term);
                                    if($cur_pos < $first_pos)
                                        $first_pos = $cur_pos;
                                    if($cur_pos > $last_pos)
                                        $last_pos = $cur_pos;
                                }
                            }
                            $start = 0;
                            $end = 0;
                            $pre = "";
                            $for = "";
                            if($last_pos - $first_pos <= 160){
                                if($last_pos <= 160){
                                    $start = 0;
                                    $end = 160;
                                }
                                else{
                                    //last pos to the last is in 160
                                    if($first_pos + 160 <= $totalLen){
                                        $end = $first_pos + 160;
                                        $start = $first_pos;
                                    }
                                    else{
                                        $addTail = $totalLen - $last_pos;
                                        $addHead = 160 - $addTail - ($last_pos - $first_pos);
                                        $end = $totalLen;
                                        if($first_pos - $addHead >= 0)
                                            $start = $first_pos - $addHead;
                                        else
                                            $start = 0;
                                    }
                                }
                            }
                            else{
                                $start = $first_pos;
                                $end = $first_pos + 160;
                            }
                            if($start != 0)
                                $pre = "...";
                            if(strlen($snippet) > $end)
                                $for = "...";
                            else
                                $end = strlen($snippet);
                            if($start == $end){
                                if($end - 160 >= 0)
                                    $start = $end - 160;
                                else
                                    $start = 0;
                            }
                            $finalSnippet = $pre . substr($snippet, $start, $end-$start+1) . $for;
                            //$descript = $start . " / " . $line . "/" . $end ;
                        }
                        }
                        if($finalSnippet == "© 2019 reuters. all rights reserved.")
                            $descript  = "N/A" ;
                        else
                            $descript = $finalSnippet;
                        //$descript = $line;
                        echo "<tr>";
                        echo "<a href='" . $link . "' target='_blank'><span style='color: #1616B6;'>" . $title . "</span></a><br>";
                        echo "<a href='" . $link . "' target='_blank'><span style='color: green; text-decoration:none'>" . $link . "</span></a><br>";
                        echo "<span style='color: black;'>" . $descript . "</span><br>";
                        echo "<span style='color: green;'>" . substr($id, 82, $id_length) . "</span><br><br>";
                        echo "</tr><tr></tr>"
                    ?>
            <?php
                }
            ?>
    <?php
        }
    ?>      </table>
            </div>
</body>
</html>
