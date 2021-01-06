Feature: Browser Preferred Language
  As a visitor
  I want my preferred browsing language to be detected
  In order to browse the websites pages in my preferred language

  Scenario:
	Polylang serves the best matching region
	Given a webpage exists in en-US, en-GB languages
	And I chose en-GB as my preferred browsing languages
	When I visit the webpage for the first time
	Then I should be served this page in en-GB language

  Scenario:
	Polylang deduces language from unmatched language-region code
	Given a webpage exists in en-US, zh-CN languages
	And I chose zh-HK as my preferred browsing language
	When I visit the webpage for the first time
	Then I should be served this page in zh-CN language

  Scenario:
	The browser languages configuration should prevail over Polylang language deduction
	Given a webpage exists in en-US, zh-CN languages
	And I chose zh-HK, en (in this order) as my preferred browsing languages
	When I visit the webpage for the first time
	Then I should be served this page in en-US language

  Scenario:
	Polylang deduces region from unmatched language-script-region code. @see https://github.com/polylang/polylang/issues/591
	Given a webpage exists in zh-HK, zh-CN, en-US languages
	And I chose zh-Hant-HK as my preferred browsing language
	When I visit the webpage for the first time
	Then I should be served this page in zh-HK language

  Scenario:
	Given a webpage exists in zh-HK, zh-CN, en-US languages
	And I chose zh-Hant-HK, zh-CN, en as my preferred browsing language
	When I visit the webpage for the first time
	Then I should be served this page in zh-HK language

  Scenario:
	Polylang deduces language from unmatched language-script-region code.
	Given a webpage exists in zh-CN, en-US languages
	And I chose zh-Hant-Hk, en (in this order) as my preferred browsing languages
	When I visit the webpage for the first time
	Then I should be served this page in zh-CN language
