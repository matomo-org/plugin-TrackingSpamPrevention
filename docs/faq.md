## FAQ

__How do I allow specific IPs to not be blocked?__

Say you are using AWS to replay your traffic using log analytics. When you have the block clouds feature enabled, all the requests from your AWS would be blocked. However, you can specifically allow your own IPs to be allowed and not blocked by editing your `config/config.ini.php` file and configuring a list of allowed IP ranges like this:

```
[TrackingSpamPrevention]
block_cloud_iprange_allowlist[] = "127.0.0.1/32"
block_cloud_iprange_allowlist[] = "192.168.0.0/21"
```

Make sure to enter a valid IP range. 

__What happens when it fails to synchronise public IPs from cloud providers?__

Any error is currently ignored and if it does not synchronise successfully, then the IP for the provider that failed are not synced.

To be aware when such an error happens you can enable the following setting:

```
[TrackingSpamPrevention]
iprange_sync_throw_exception_on_error = 1
```

It is disabled by default as it could stop other scheduled tasks from being executed.