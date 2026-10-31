# WP Health Check for UptimeRobot

A [637 Digital Solutions](https://637digital.com) WordPress plugin that exposes a per-site keyword URL for [UptimeRobot](https://uptimerobot.com/) Keyword monitors.

**Source:** [github.com/637-Digital/uptime-robot-for-wp](https://github.com/637-Digital/uptime-robot-for-wp)

## What it does

After activation, each site gets:

- A public URL, default `https://example.com/healthcheck/`
- A generated keyword under **Settings → UptimeRobot Health Check**
- A plaintext response like:

```
OK
whcu_a1b2c3d4e5f6...
```

UptimeRobot polls that URL and alerts if the keyword is missing. That fires when PHP/WordPress cannot boot, the database is down, the site is serving the wrong content, or someone rotated the keyword.

The plugin can be network-activated. The keyword, slug, and monitor URL are always stored **per site**, so each site is monitored independently.

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- Pretty permalinks recommended so `/healthcheck/` works
- An UptimeRobot account

## Installation

These plugins are not on WordPress.org.

1. Download the ZIP from [GitHub](https://github.com/637-Digital/uptime-robot-for-wp)
2. Upload it under **Plugins → Add New → Upload Plugin**
3. Activate it (or **Network Activate** on multisite)

## Configuration

1. Open **Settings → UptimeRobot Health Check** on the site you want to monitor
2. Copy the **Monitor URL** and **Keyword**
3. Optionally change the endpoint slug (default is `healthcheck`) or generate a new keyword
4. Save changes

On multisite, do this on each site. There is no network-wide keyword.

If pretty permalinks are off, use the fallback query-string URL shown on the settings screen.

## UptimeRobot setup

Create one Keyword monitor per WordPress site:

1. Monitor type: **Keyword**
2. URL: the Monitor URL from settings (HTTPS)
3. Keyword type: **exists**
4. Alert when: the keyword **does not exist**
5. Keyword: the exact value from that site’s settings
6. Interval: 5 minutes is typical

After the first successful check, **Last probe** on the settings screen should update.

## Multisite

- Network-activate from **Network Admin → Plugins** so every site gets the endpoint
- Open **Settings → UptimeRobot Health Check** on each site for that site’s URL and keyword
- New sites created after network activation are seeded automatically
- Point UptimeRobot at each site separately — do not reuse one keyword across the network

## Troubleshooting

### `/healthcheck/` returns 404

- Enable a permalink structure under **Settings → Permalinks**, or use the fallback URL from the plugin settings
- Visit **Settings → Permalinks** and click Save to flush rewrite rules

### UptimeRobot says the keyword is missing

- Confirm the keyword in UptimeRobot matches the settings screen exactly
- Open the Monitor URL in a browser and check that the keyword appears in the plaintext response
- If you generated a new keyword, update the UptimeRobot monitor to match

### Last probe never updates

- UptimeRobot may not be reaching the site (firewall, HTTP auth, or DNS)
- Open the Monitor URL yourself — a successful hit should stamp **Last probe**

### Keyword disappeared after a site copy or migration

- The keyword is stored in that site’s options table. Re-save settings or generate a new keyword, then update UptimeRobot

## License

MIT License. See LICENSE in the repository.

## Credits

Built by [637 Digital Solutions](https://637digital.com).
