# Matomo Tracking Spam Prevention Plugin

## Description

Ever noticed tracking requests that look unnatural or originated from locations you wouldn't expect to get visits from?

These tracking requests may be caused by spammers or bots and make your data less accurate. In some cases they can even
cause pretty much any metric to become inaccurate. This plugin offers various options to fight these kind of tracking requests
so you can rely on your data again. 

### 1. Block requests from cloud providers

When enabled, this plugin will automatically detect IP addresses used by popular cloud providers like AWS, Azure, Digital Ocean, Google Cloud and Oracle Cloud.

When a tracking request matches such an IP address, then the tracking request will be blocked. Additionally, some Cloud providers like Alibaba Cloud may be detected using the geolocation database (requires eg DB-IP City DB).

If you are only tracking using the JavaScript tracker then this should be a safe feature to enable as tracking requests from humans would not originate from these clouds.

If you are sending tracking requests from a cloud server, then you can also configure IP addresses that are always allowed so you can still use this feature.

### 2. Restrict number of actions per visit

When enabled, you can configure how many actions a visit should max have. 

Most sites have under normal circumstances never say more than say 100 or 200 or 300 actions within one visit. It many cases it might be therefore safe to assume that if someone has caused more actions than the configured amount of actions, then it might be actually tracking spam, or a bot, or something else unnatural causing these actions. 

Matomo will in this case stop recording further actions for that visit to have less inaccurate data and to reduce server load. The IP address of this visit will then be blocked for up to 24 hours.

### Further ways Matomo prevents spam

* We maintain a list of spam referrers and Matomo will block tracking requests if such a referrer is detected
* The [Exclude Countries](https://plugins.matomo.org/ExcludeCountries) plugin lets you configure to only accepted tracking requests for visitors from specific countries. For example if you have a German website, then it might be unexpected to have any legit visitors from a country outside of Europe meaning it is likely a spammer or bot. By only tracking visitors from certain countries you can easily avoid a lot of potential spam and bots plus you might also avoid needing to be compliant with certain privacy laws.