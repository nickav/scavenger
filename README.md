# Scavenger - HTML Webpage Parser

A webpage parser that gathers important information about a webpage given a URL.   
Gets all meta tags and title, description, main images, keywords, url, website, type.   
Lazily looks at the header first and falls back to parsing the data from the webpage.  

Useful for URL sharing where information about that link is needed.

## Installation

Add the repository to your composer.json:

```"require": {
	"nickaversano/Scavenger": "dev-master"
}```

Add the PSR-4 autoload:

```"autoload": {
	"nickaversano\\Scavenger\\": "src"
}```

## Usage

```
use nickaversano\Scavenger\Scavenger;
$data = Scavenger::get($url);

```

## License

MIT, Open Source.   
Free to redistribute and modify.