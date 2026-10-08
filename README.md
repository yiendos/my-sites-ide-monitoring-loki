# Loki

[Loki](https://grafana.com/oss/loki/) for [my-sites-ide](https://github.com/yiendos/my-sites-ide):
log storage, reached as `loki:3100` inside the IDE. It stores logs; the
[alloy plugin](https://github.com/yiendos/my-sites-ide-monitoring-alloy) collects them - every IDE
container's output, plus your apps' OpenTelemetry logs - and the
[grafana plugin](https://github.com/yiendos/my-sites-ide-monitoring-grafana) is where you read them.

Written for: developers running sites in my-sites-ide who want to search all the IDE's logs in one
place.

## Contents

- [Installation](#installation)
- [How logs get here](#how-logs-get-here)
- [Command reference](#command-reference)
- [Configuration](#configuration)
- [What it uses from the IDE](#what-it-uses-from-the-ide)
- [Troubleshooting](#troubleshooting)
- [Known gaps](#known-gaps)

## Installation

A [my-sites-ide](https://github.com/yiendos/my-sites-ide) plugin. Add it to the `require` section
of the IDE's `composer.local.json` - or add
[`yiendos/my-sites-ide-preset-monitoring`](https://github.com/yiendos/my-sites-ide-preset-monitoring)
instead, for the whole monitoring stack:

```json
"yiendos/my-sites-ide-monitoring-loki": "@dev"
```

Then, from the IDE root:

```
composer update
php my-sites-ide monitoring:loki-start
```

Loki is opt-in: it doesn't autostart. Start it with `monitoring:loki-start`. Then run
`monitoring:alloy-start` and `monitoring:grafana-start` (when those plugins are installed) so Alloy
sends to Loki and Grafana gets a Loki data source.

## How logs get here

| Source | Sent by | Labels |
|---|---|---|
| every container on the IDE's network (stdout/stderr) | Alloy, from the Docker socket | `service_name` (compose service), `container`, `compose_project` |
| your apps' OpenTelemetry logs | Alloy, from `alloy:4318`, to Loki's OTLP endpoint | `service_name` from the app; `trace_id` and `span_id` kept as structured metadata |

The `trace_id` is what Grafana uses to link a log line to its trace in Tempo.

Loki runs as a single binary, with everything on the filesystem in `storage/plugins/loki/`. Logs
older than `LOKI_RETENTION` are deleted by its compactor.

## Command reference

| Command | What it does |
|---|---|
| `monitoring:loki-start` | `docker compose up -d --build loki`. Recreates a running Loki whose compose config changed (e.g. a new `LOKI_RETENTION`) |
| `monitoring:loki-stop` | `docker compose stop loki`, leaving the rest of the IDE running |

## Configuration

| Variable | Default | What it does |
|---|---|---|
| `LOKI_RETENTION` | `2d` (this plugin's `.env`) | How long logs are kept |

Set it in the IDE's root `.env`, which wins over the plugin's default, then run
`monitoring:loki-start`. `php my-sites-ide ide:plugin-env yiendos/my-sites-ide-monitoring-loki`
copies it in, commented out.

The rest of Loki's config is in the package's `conf/loki.yaml`.

## What it uses from the IDE

| From the IDE | Used for |
|---|---|
| `NAMESPACE` (root `.env`) | the image name, `${NAMESPACE}_loki` |
| `IDE_ROOT` (set by the CLI and `_dev/cache/ide.env`) | finding the plugin list and storage |
| `_dev/cache/plugins.php` | whether the alloy plugin is installed, to say so when it isn't |
| `storage/plugins/loki/` (`"storage": true`) | chunks, index, rules and the compactor's working files |
| the `my-sites-ide` network | being reached by Alloy and Grafana |

The container carries `prometheus.io/scrape` labels, so the prometheus plugin scrapes Loki's own
metrics.

## Troubleshooting

**No logs in Grafana.** Check Alloy is installed and running, and was started after Loki was
installed (`monitoring:alloy-start` lists its pipelines - look for `container logs -> loki`). Its UI
at http://localhost:12345 shows whether `loki.write.default` is healthy.

**`empty ring` in the logs right after starting.** Loki logs this once while its in-memory ring
comes up; it's harmless.

## Known gaps

- No host port, so tools on the host (e.g. `logcli`) can't reach it - query through Grafana, or
  `docker compose exec grafana wget -qO- http://loki:3100/...`.
- On Linux hosts, `storage/plugins/loki/` is created by your user while Loki runs as uid 10001, so
  it may not be able to write there. Docker Desktop on macOS maps ownership, so it isn't affected.
