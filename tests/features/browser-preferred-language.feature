Feature: Browser Preferred Language
  As a visitor
  I want my preferred browsing language to be detected
  In order to browse the websites pages in my preferred language

  Scenario:
	Polylang infers language from unmatched language-region code
	Given I chose zh-HK as my preferred browsing language
	And a webpage exists in en-US, zh-CN languages
	When I visit the webpage for the first time
	Then I should be served this page in zh-CN language

  Scenario:
	The browser languages configuration should supersede Polylang language inferring
	Given I chose zh-HK, en, zh-CN (in this order) as my preferred browsing languages
	And a webpage exists in en-US, zh-CN languages
	When I visit the webpage for the first time
	Then I should be served this page in en-US language

  Scenario:
	Polylang infers region from unmatched language-script-region code. @see https://github.com/polylang/polylang/issues/591
	Given I chose zh-Hant-HK as my preferred browsing language
	And a webpage exists in zh-HK, zh-CN, en-US languages
	When I visit the webpage for the first time
	Then I should be served this page in zh-HK language

  Scenario:
	The browser languages configuration should supersede Polylang region inferring
	Given I chose zh-Hant-HK, zh-CN as my preferred browsing language
	And a webpage exists in zh-HK, zh-CN, en-US languages
	When  I visit the webpage for the first time
	Then I should be served this page in zh-CN language

	Scenario:
	  Polylang infers language from unmatched language-script-region code.
	  Given I chose zh-Hant-HK, zh-HK (in this order) as my preferred browsing languages
	  And a webpage exists in zh-CN, en-US
	  When I visit the webpage for the first time
	  Then I should be served this page in zh-CN language
