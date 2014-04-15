<?php

namespace nickaversano\Scavenger;

class Scavenger
{
	/**
	 * Fetches information from the URL and returns an array of information about the page
	 * @param $url the string to parse
	 * @return array [title, description, keywords, images, type, url, website]
	 */
	public static function get($url, $verbose = false)
	{
		$html = self::curl($url);
		$data = self::parse($html);

		//$ids = array_intersect_key($data, array('title', 'description', 'keywords', 'images', 'type', 'url', 'website'))

		return $data;
	}

	/**
	 * Parse html data, extracting important information from the header or the body
	 * @param $htmldata
	 * @return array [title, description, keywords, images, type, url, website]
	 */
	public static function parse($htmldata)
	{
		if (empty($htmldata)) {
			throw new ScavengerException('Cannot parse empty html');
		}

		$oldSetting = libxml_use_internal_errors(true); 
		libxml_clear_errors();

		//meta property="og:title", og:type, og:image, og:url, og:description, og:site_name
		//meta name="twitter:site", twitter:title, twitter:description, twitter:image, twitter:url, 
		// twitter:card[photo, player, summary]

		//just look at the site header first, then fallback to parsing the rest of the page
		$webpage = explode('</head>', $htmldata, 2);
		$head = $webpage[0] . '</head>';

		if (count($webpage) > 1)
			$body = $webpage[1];

		$doc = new \DOMDocument();
		@$doc->loadHTML($head);

		//page title
		$title = $doc->getElementsByTagName('title')->item(0);
		if (isset($title)) {
			$data['title'] = $title->nodeValue;
		}

		// link rel = canonical
		$nodes = $doc->getElementsByTagName('link');
		$len = $nodes->length;
		for ($i = 0; $i < $len; $i ++) {
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
			$content = $meta->getAttribute('content');

			if (isset($data[$name])) continue; //don't override values

			if (!empty($name)) {
				$data[$name] = $content;
			} else {
				$property = $meta->getAttribute('property');
				$data[$property] = $content;
			}
		}

		if (isset($data['og:image'])) {
			$data['images'] = array($data['og:image']);
		}
		else if (isset($data['twitter:image'])) {
			$data['images'] = array($data['twitter:image']);
		}

		//couldn't find all the information, fallback to stripos
		// first h1 tag = title, first p tag = description, first image = images
		//images
		/*$image = $doc->getElementsByTagName('img')->item(0);
		if (isset($image)) {
			$image = $image->getAttribute('src');
		}
		$images = array($image);*/

		unset($doc);
		libxml_clear_errors();
		libxml_use_internal_errors($oldSetting);

		return $data;/*array(
			'title' => $title,
			'description' => $description,
			'images' => $images,
			'url' => null,
			'type' => null,
			'website' => null
		);*/
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
