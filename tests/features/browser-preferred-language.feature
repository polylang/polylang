Feature: Browser Preferred Language
  As a visitor
  I want my preferred browsing language to be detected
  In order to browse the websites pages in my preferred language

  Scenario:
	Polylang serves the best matching region
	Given my website has content in English(en-US)
  	And my website has content in English(en-GB) with the slug en-gb
	And I chose en-GB as my preferred browsing languages
	When I visit my website's homepage for the first time
	Then Polylang will remember English(en-GB) as my preferred browsing language

  Scenario:
	Polylang deduces language from unmatched language-region code.
	Given my website has content in English(en-US)
  	And my website has content in Chinese(zh-CN)
	And I chose zh-HK, en (in this order) as my preferred browsing languages
	When I visit my website's homepage for the first time
	Then Polylang will remember Chinese(zh-CN) as my preferred browsing language

  Scenario:
  	Polylang deduces language from unmatched language-script-region code.
	Given my website has content in Chinese(zh-CN)
  	And my website has content in English(en-US)
	And I chose zh-Hant-Hk, en (in this order) as my preferred browsing languages
	When I visit my website's homepage for the first time
	Then Polylang will remember Chinese(zh-CN) as my preferred browsing language

  Scenario:
	Polylang deduces region from unmatched language-script-region code. @see https://github.com/polylang/polylang/issues/591
	Given my website has content in Chinese(zh-HK) with the slug zh-hk
  	And my website has content in Chinese(zh-CN)
  	And my website has content in English(en-US)
	And I chose zh-Hant-HK, zh-CN, en (in this order) as my preferred browsing language
	When I visit my website's homepage for the first time
	Then Polylang will remember Chinese(zh-HK) as my preferred browsing language
