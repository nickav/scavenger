<?php

namespace nickaversano\Scavenger;

require_once 'functions.php';

class Scavenger
{
	/**
	 * Fetches information from the URL and returns an array of information about the page
	 * @param  string $url the to parse
	 * @return array [title, description, keywords, images, type, url, website]
	 */
	public static function get($url)
	{
		$html = self::curl($url);
		$data = self::parse($html, $url);

		//array_intersect_key($data, array('title', 'description', 'keywords', 'images', 'type', 'url', 'website'))

		return $data;
	}

	/**
	 * Parse html data, extracting important information from the header or the body
	 * @param string  $htmldata
	 * @param string  $url      the url of the htmldata
	 * @return array [title, description, keywords, images, type, url, website]
	 */
	public static function parse($htmldata, $url = null)
	{
		if (empty($htmldata)) {
			throw new ScavengerException('Cannot parse empty html');
		}

		$oldSetting = libxml_use_internal_errors(true); 
		libxml_clear_errors();

		//just look at the site header first, then fallback to parsing the rest of the page
		$webpage = explode('</head>', $htmldata, 2);
		$head = $webpage[0] . '</head>';

		if (count($webpage) > 1)
			$body = $webpage[1];

		//parse the page's metadata
		$data = self::parseMetadata($head);
		$data['images'] = array();

		$image = array_first_defined($data, array('og:image', 'twitter:image'));
		if (isset($image)) $data['images'][] = $image;

		$data['url'] = array_first_defined($data, array('canonical', 'og:url'), $url);

		//couldn't find all the information, fallback to parsing the body
		if (empty($data['title']) || empty($data['description']) || empty($data['images'])) {
			$doc = new \DOMDocument();
			@$doc->loadHTML($body);

			if (empty($data['title'])) {
				$nodes = $doc->getElementsByTagName('h1')->item(0);
				if (isset($nodes)) $data['title'] = $nodes->nodeValue;
			}

			if (empty($data['description'])) {
				$nodes = $doc->getElementsByTagName('p')->item(0);
				if (isset($nodes)) $data['description'] = trim($nodes->nodeValue);
			}

			if (empty($data['images'])) {
				$data['images'] = self::parseImages($doc, $url);
			}
		}

		unset($doc);
		libxml_clear_errors();
		libxml_use_internal_errors($oldSetting);

		return $data;
	}

	protected static function parseMetadata($html_head) {
		$doc = new \DOMDocument();
		@$doc->loadHTML($html_head);
		$data = array();

		//page title
		$title = $doc->getElementsByTagName('title')->item(0);
		if (isset($title)) {
			$data['title'] = $title->nodeValue;
		}

		// link rel = canonical
		$nodes = $doc->getElementsByTagName('link');
		$len = $nodes->length;
		for ($i = 0; $i < $len; $i++) {
			$node = $nodes->item($i);

			if ($node->getAttribute('rel') === 'canonical') {
				$data['canonical'] = $node->getAttribute('href');
			}
		}

		//parse meta tags
		$metas = $doc->getElementsByTagName('meta');
		$len = $metas->length;
		for ($i = 0; $i < $len; $i++) {
			$meta = $metas->item($i);
			$name = $meta->getAttribute('name');
			$key = empty($name) ? $meta->getAttribute('property') : $name;

			if (empty($key)) continue;

			$content = $meta->getAttribute('content');

			//if multiple values, create an array
			if (isset($data[$key])) {
				if (!is_array($data[$key])) {
					$data[$key] = array($data[$key]);
				}
				$data[$key][] = $content;
			}
			else {
				$data[$key] = $content;
			}
		}

		return $data;
	}

	protected static function parseImages($doc, $url, $threshold = 128)
	{
		$images = $doc->getElementsByTagName('img');
		$urlParts = parse_url($url);
		$absUrl = $urlParts['scheme'] . '://' . $urlParts['host'];

		//look for images
		$bucket = array();

		$len = $images->length;
		for ($i = 0; $i < $len; $i++) {
			$image = $images->item($i);

			$src = $image->getAttribute('src');
			$width = $image->getAttribute('width');

			$lsrc = strtolower($src);

			//ignore gifs & favicon
			if (substr($lsrc, -3) == 'gif' || $lsrc == '/favicon.ico') continue;

			if ($src[0] == '/') {
				if ($src[1] == '/') {
					$src = $urlParts['scheme'] . ':' . $src;
				} else {
					$src = $absUrl . $src;
				}
			}

			if (filter_var($src, FILTER_VALIDATE_URL) === false) continue;

			//@todo: do these requests in parallel with curl
			if (empty($width)) {
				list($width, $height, $type, $attr) = getimagesize($src);
			} else {
				$height = $image->getAttribute('height');
			}
			
			if ($width >= $threshold && $height >= $threshold)
				$bucket[] = array('src' => $src, 'size' => $width * $height);
		}

		if (count($bucket) == 1) {
			return array($bucket[0]['src']);
		}

		// sort by size 
		usort($bucket, function($a, $b){
			return $b['size'] - $a['size'];
		});

		//@todo: use a clustering algorithm to get more than 1 image

		return array($bucket[0]['src']);
	}

	/**
	 * Sends the cURL request to the given URL
	 * @param  string $url the webpage to load
	 * @return string      the html data returned
	 */
	protected static function curl($url)
	{
		$ch = curl_init($url);

		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_BINARYTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HEADER         => false,
			CURLOPT_USERAGENT      => 'spider',
			CURLOPT_TIMEOUT        => 30
		);

		curl_setopt_array($ch, $options);
		$response = curl_exec($ch);
		curl_close($ch);

		if ($response === false) {
			throw new ScavengerException('Error connecting to ' . $url);
		}

		return $response;
	}
}
