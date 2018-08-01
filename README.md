# SiteChecker
This is a simple class for working with the page structure. The class will help you quickly get the data from the html code.
You can use this class to create an automatic checklist.
**You can check:**
- length of the page title
- get all the links on your page
- check the page description and keywords
- make sure that there are no duplicate identifiers on the page
etc
## Requirements
 - php 5.5+
 -- curl
 -- DOMDocument
## Basic Usage
We start with ``` new SiteCheck``` and call ```scan();```

```php
$url = 'https://vk.com';
$sc = new SiteCheck($url);
$sc->scan();
```
after that we can make requests!

## Methods

- **getParseUrl** - Get the URL array (full or partial)
- **getDomTree** - Return HTML tree (DOM)
- **getFaviconLink** - Get favicon link
- **howManyTags** - How many tags $tag in html tree
- **hasDescription** - Has html tree tag meta[name='description']
- **getDescriptionText** - Get meta[name='description'] content value
- **getDescriptionLength** - meta[name='description'] content length
- **hasKeywords** - Has html tree tag meta[name='keywords']
- **getKeywordsText** - Get meta[name='keywords'] content value
- **getKeywordsLength** - meta[name='keywords'] content length
- **explodeKeywords** - To split a meta[name='keywords'] into words
- **getAllId** - Get all ID attributes on the page
- **getDublicateId** - Get all duplicate ID attributes on the page
- **getPageTitle** - Get page title
- **getPageTitleLength** - Get page title length
- **getAllLinks** - Get all links with attributes
