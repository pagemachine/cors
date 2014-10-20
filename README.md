# CORS

[Cross Origin Resource Sharing](https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS) for TYPO3 CMS.

## Configuration

All configuration options can be set via TypoScript setup in `config` or per page object in `page.config`. The following options are available:

| Option | Type | Description |
|-|-|-|
| `allowCredentials` | int/boolean | Processing of the `credentials` flag |
| `allowHeaders` | string |  List of allowed headers |
| `allowMethods` | string |  List of allowed methods (GET, POST, ...) |
| `allowOrigin` | string |  List of allowed origins |
| `exposeHeaders` | string |  List of headers exposed to clients |
| `maxAge` | int |  Cache lifetime of preflight requests, watch out for browser limits |

### Examples

* Origin wildcarding:

        config {
          allowOrigin = *
        }

* Simple list of origins:

        config {
          allowOrigin = http://example.org, http://example.com
        }

* Same as above but more readable with many origins:

        config {
          allowOrigin (
            http://example.org,
            http://example.com
          )
        }

* Allow specific methods:

        config {
          allowMethods = GET, POST, PUT, DELETE
        }

* Allow headers:

        config {
          allowHeaders = (
            Content-Type,
            ...
          )
        }

* Allow `credential` flag processing:

        config {
          // Set to 1/true to enable
          allowCredentials = 1
        }

*  Expose headers:

        config {
          exposeHeaders (
            X-My-Custom-Header,
            X-Another-Custom-Header
          )
        }

* Set maximum age of preflight request result:

        config {
          // 10 minutes
          maxAge = 600
        }
