# WP Health Check for UptimeRobot

A [637 Digital Solutions](https://637digital.com) WordPress plugin that exposes a per-site keyword URL for [UptimeRobot](https://uptimerobot.com/) Keyword monitors.

- **Help:** [637digital.com/wiki](https://637digital.com/wiki/)
- **Source:** [github.com/637-Digital/uptime-robot-for-wp](https://github.com/637-Digital/uptime-robot-for-wp)

Install it on a single site, or network-activate it on multisite. The keyword and URL are always stored **per site**, so each site can be monitored independently.

## What it does

After activation, each site gets:

- A public URL, default `https://example.com/healthcheck/`
- A generated keyword shown under **Settings → UptimeRobot Health Check**
- A plaintext response like:

```
OK
whcu_a1b2c3d4e5f6...
```

UptimeRobot polls that URL and alerts if the keyword is missing. That fires when PHP/WordPress cannot boot, the database is down, the site is serving the wrong content, or someone rotated the keyword.

## UptimeRobot setup

1. In WordPress: **Settings → UptimeRobot Health Check**
2. Copy the **Monitor URL** and **Keyword**
3. In UptimeRobot, add a monitor:
   - Type: **Keyword**
   - URL: the Monitor URL (HTTPS)
   - Keyword type: **exists**
   - Alert when: keyword **does not exist**
   - Keyword: the value from the settings screen
   - Interval: 5 minutes is typical

If pretty permalinks are disabled, use the fallback query-string URL shown on the settings screen.

## Multisite

- Network-activate from **Network Admin → Plugins** so every site gets the endpoint.
- Open **Settings → UptimeRobot Health Check** on each site to copy that site’s URL and keyword.
- New sites created after network activation are seeded automatically.
- Do not put a network-wide keyword in UptimeRobot; each site should have its own monitor.

## Requirements

- WordPress 6.0+
- PHP 7.4+
- Pretty permalinks recommended so `/healthcheck/` works

## Install

1. Copy this folder to `wp-content/plugins/wp-health-check-uptimerobot/`
2. Activate (or network-activate) the plugin
3. Visit **Settings → UptimeRobot Health Check** on the site you want to monitor

## License

MIT. See [LICENSE](LICENSE).

---

Built by [637 Digital Solutions](https://637digital.com). Help: [wiki](https://637digital.com/wiki/). Source: [GitHub](https://github.com/637-Digital/uptime-robot-for-wp).
