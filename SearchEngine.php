<!DOCTYPE html>
<?php
	include 'SpellCorrector.php';
	$limit = 10; 
	$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false; 
	$ranktype = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : false;
	$results = false; 
	if ($query) {
		require_once('./solr-php-client-master/Apache/Solr/Service.php');
		$solr = new Apache_Solr_Service('localhost', 8983, '/solr/myexample/');
		if (get_magic_quotes_gpc() == 1) { 
			$query = stripslashes($query); 
		}
		try { 
			if($_GET['algorithm'] == 'Default'){
		      $results = $solr->search($query, 0, $limit, array('qt' => '/suggest', 'sort' => 'score desc',));
		    } else {
		      $results = $solr->search($query, 0, $limit, array('qt' => '/suggest', 'sort' => 'pageRankFile desc',));
		    } 
		} catch (Exception $e) {
			die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>"); 
		} 
	}
?>
<html> 
<head> 
	<title>PHP Solr Client Example</title> 
</head> 
<body> 
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="http://connect.facebook.net/en_US/all.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.7.0/moment.min.js" type="text/javascript"></script>
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css">
    <script src="http://code.jquery.com/jquery-1.10.2.js"></script>
    <script src="http://code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
    <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
    <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
	<script>
		$(document).ready(function() {  
		    $("#q").autocomplete({
		        source: function( request, response ) {
		            $.ajax({  
		                url: "helper.php",
		                dataType: "json",
		                type: "GET",
		                data: {
		                	//http://localhost:8983/solr/myexample/select?indent=on&q=a&wt=json
		                    url:"http://localhost:8983/solr/myexample/suggest?indent=on&q="+request.term+"&wt=json"
		                },
		                success: function(data) {
		                    var query = request.term;
		                    var querylength = query.length;
		                    var res = data.suggest.suggest[request.term].suggestions;
		                    response($.map(res,function(item) {
		                    return {
		                        label: item.term,
		                        value: item.term
		                        };
		                    }));
		                }
		            });
		        },
		        minLength: 1,
		        select: function( event, ui ) {
		        }
		    });
			$.ajax({
		        url: "helper.php",
		        dataType: "json",
		        type: "GET",
		        data: {
		        	//http://localhost:8983/solr/myexample/select?indent=on&q=californa&wt=json
		            url:"http://localhost:8983/solr/myexample/select?indent=on&q="+document.getElementById("q").value+"&wt=json"
		        },
		        success: function(data) {
		        	//console.log(data);
		        	if (data.hasOwnProperty('spellcheck')) {
		        		var algorithm = document.getElementById("algorithm").value;
		        		var res = data.spellcheck.suggestions[1].suggestion[0];
		        		var url = "http://localhost/~Jus/572/hw5.php?q=" + res + "&algorithm=" + algorithm;
		        		//$('#my-link').html('<a href="http://www.google.com">Google</a>');
		        		$('#spellcheck').html("Search instead for  " + "<a id='click' href ='" + url + "'>"  + res + "</a>");
		        		//$("#spellcheck").text(algorithm);
		        	}
		        }
	    	});
	    });
  	</script>
	<form accept-charset="utf-8" method="get"> 
		<label for="q">Search:</label> 
		<input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
		<select name="algorithm" id = "algorithm">
			<option <?php if ($_GET['algorithm'] == 'Default') { ?>selected="true" <?php }; ?>value="Default">Default</option>
   			<option <?php if ($_GET['algorithm'] == 'PageRank') { ?>selected="true" <?php }; ?>value="PageRank">PageRank</option>
	  	</select>
		<input type="submit"/> 
	</form>
	<p id="spellcheck"></p>
	<?php 
		if ($results) { 
			$total = (int) $results->response->numFound; 
			$start = min(1, $total); 
			$end = min($limit, $total); 
	?> 
	<div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div> 
	<ol> <?php 
			foreach ($results->response->docs as $doc) { 
		?> 
		<li> <table style="border: 1px solid black; text-align: left"> 
			<?php 
				foreach ($doc as $field => $value) { 
					$title = $doc->title;
				    $url = $doc->og_url ;
				    $id = $doc->id;  
				    $description  = $doc->description;
				    $id1 = substr($id,0,30);
				    $id2 = substr($id,32);
				    $id3 = $id1.$id2;
				    $content = strip_tags(file_get_contents($id3, true));
				    $word = $results->responseHeader->params->q;
				    $pos = stripos($content, $word);
				    $size = 20;
				    if ($pos-$size<0){
				        $snippet = substr($content,0,$size*2);
				    }
				    else{
				        $snippet = substr($content,$pos-$size,$size*2);
				    }
			?>
			<?php 
				} 
			?> 
			<tr>
				<tr>
	            	<th>title - </th><th><a href="<?php echo $url; ?>"><?php echo $title; ?></a></th>
	          	</tr>
	            <tr>
	            	<th>url - </th><th><a href="<?php echo $url; ?>"><?php echo $url; ?></a></th>
	            </tr>
	            <tr>  
	            	<th>id - </th><th><?php echo $id3; ?></th>  
	            </tr>
	            <tr>
	            	<th>description - </th><th><?php echo $description; ?></th>
	            </tr>
	            <tr>
	            	<th>snippet - </th><th><?php echo $snippet; ?></th>
	            </tr>
			</tr> 
			</table> 
		</li> 
		<?php } ?> 
	</ol> <?php } ?> 
</body> 
</html>