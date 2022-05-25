<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-F3w7mX95PdgyTmZZMECAngseQB83DfGTowi0iMjiWaeVhAn4FJkqJByhZMI3AhiU" crossorigin="anonymous">
<title>Crawler</title>
<div style='padding: 20px;'>
	<form  action='index.php' spellcheck="false">
		<p><b><a href='<?php echo $_SERVER['PHP_SELF']; ?>' style=' text-decoration: none; color: black;'>Enter url:</a></b></p>
		<p><textarea rows="5" cols="45" name="url"><?php echo @$_GET['url']; ?></textarea></p>
		<p><input type="submit" value="Start"></p>
	</form>
 <?php
 
if ( !isset($_GET['url']) ) die;

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

set_time_limit(0);
include_once('simple_html_dom.php');
ini_set("memory_limit","1024M");


$url= trim($_GET['url']);

$csv_file_path = 'products.txt';

$csv_content = [["name","rating","regular_price","sale_price","availability","imgs_src","description","categories","sku","amount"]];	


if( !file_exists('cache/'.urlencode($url)) ){
	$html = curl($url);
	file_put_contents('cache/'.urlencode($url), $html);
}
$pure_html = file_get_contents('cache/'.urlencode($url));

//$pure_html = file_get_contents('test.html');
//$pure_html = curl($url);

$html = str_get_html( $pure_html );


foreach ( $html->find('li[class="product-col"]') as $product ) {
	$url = $product->find('a[class="product-loop-title"]', 0)->href;
	process_url($url);
}

	echo '<pre>';print_r($csv_content);echo '</pre>';
//	die;

$fp = fopen($csv_file_path, 'w');

foreach ($csv_content as $fields) {
    fputcsv($fp, $fields);
}

fclose($fp);

echo '<br><br>Data stored in <a href="'.$csv_file_path.'">'.$csv_file_path.'</a>';

$i = 0;

function process_url($url) {
	global $i, $csv_content;
	$name = $rating = $regular_price = $sale_price = $availability = $imgs_src = $description = $categories = $sku = $amount = '';
	$i = $i +1;
	if( !file_exists('cache/'.urlencode($url)) ){
		$html = curl($url);
		file_put_contents('cache/'.urlencode($url), $html);
	}
	$pure_html = file_get_contents('cache/'.urlencode($url));

	$html = str_get_html( $pure_html );


	$name = $html->find('h2[class="product_title"]', 0)->innertext;

	echo '<br>'.$i.'. <a href="'.$url.'">'.$name.'</a>';

	$price = $html->find('p[class="price"]', 0);

	if ( $price->find('del', 0) != null ) {
		echo ' - <span style="color: green;">Good</span>';
		$sale_price = $price->find('del', 0)->innertext;
		$regular_price = $price->find('ins', 0)->innertext;
	}
	else {
		echo ' - <span style="color: red;">Not on sale, skipping</span>';
		return;
	}


	if ( $html->find('span[class="product-stock"]', 0) != null ) {
		$availability = $html->find('span[class="product-stock"]', 0)->plaintext;
		$availability = str_replace("Availability: ", "", $availability);
	}
	$categories = $html->find('span[class="posted_in"]', 0)->plaintext;
	$categories = str_replace("Categories: ", "", $categories);
	
	$rating = $html->find('strong[class="rating"]', 0)->innertext;
	$description = $html->find('div[class="description"]', 0)->innertext;
	$sku = $html->find('span[class="sku_wrapper"]', 0)->plaintext;
	$sku = str_replace("SKU: ", "", $sku);

	if ( $html->find('ul[class="filter-item-list"]', 0) != null ) {
		$amount = $html->find('ul[class="filter-item-list"]', 0)->plaintext;
	}

	foreach ( $html->find('div[class="img-thumbnail"]')as $img ) { 
		
		if ( $img->find('img', 0)->width > 200 ) { //hardcoded
			$img_src = $img->find('img', 0)->getAttribute('data-lazy-src');
			$imgs_src = $imgs_src.','.$img_src;
		}
	}

//	echo '<h6>'.$i.'. '.$name.'</h6><br>';

//	echo $price.'<br>';
//	echo $imgs_src.'<br>';
//	echo $stock.'<br>';
//	echo $categories.'<br>';	
//	echo $description.'<br>';	
//	echo $sku.'<br>';
//	echo $variations.'<br>';
	
//	$csv_content = ["name","rating","regular_price","sale_price","availability","imgs_src","description","categories","sku","amount"];
	$row = [$name, $rating, $regular_price, $sale_price, $availability, $imgs_src, $description, $categories, $sku, $amount];
	$row = array_map('trim', $row);//trim array
	$row = array_map('html_entity_decode', $row);
	$csv_content[] = $row;
	
//	echo '<pre>';print_r($row);echo '</pre>';
//	die;
	
//	echo $url;die;
}

function curl( $url ){
	sleep(5);
	$user_agent = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.54 Safari/537.36';
	$ch = curl_init($url);
	curl_setopt ($ch, CURLOPT_USERAGENT, $user_agent);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
//	curl_setopt($ch, CURLOPT_POST, 1);
//	curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}

?>