## Changelog

# 5.0.2
- Added plugin category for Marketplace

# 5.0.1
- Fix issue where max action limit was blocking IP addresses from the allow list

# 5.0.0
- Compatibility with Matomo 5.0

# 4.1.7
- Fixed Azure IP ranges download URL parsing code better accounting for character encoding

# 4.1.6
- Started including userAgent in the banned IP email

# 4.1.5
- Azure IP ranges download code updated and added tests to alert if download fails

# 4.1.4
- Add new command to block new organisations `./console trackingspamprevention:block-geo-ip-organisation --organisation-name="Example"`

# 4.1.3
- Translation changes
- Added code to not throw exception if digitalOcean file is empty

# 4.1.2
- Fix location data in email to show IP instead of IP range
- Started blocking digital ocean through providers.
- Stopped checking GeoIp DB if UserCountry plugin is disabled
- Azure IP ranges download code updated

# 4.1.1
- Fixed IP ban notification email leading to internal sever error

# 4.1.0
- Exclude user agents from load testing services
- Exclude user agents from server side tracking SDK by enabling an option

# 4.0.0
* Initial version
