# CORS

[Cross Origin Resource Sharing](https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS) for TYPO3 CMS.

## Configuration

All configuration options can be set via TypoScript setup in `config` or per page object in `page.config`. The following options are available:

| Option | Type | Description |
|-|-|-|
| `allowCredentials` | int/boolean | Processing of the `credentials` flag |
| `allowOrigin` | string/array|  List of allowed origins |

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

* Array syntax, useful for conditionally adding values:

        config {
          allowOrigin {
            org = http://example.org
            com = http://example.com
          }
        }

* Allow `credential` flag processing:

        config {
          // Set to 1/true to enable
          allowCredentials = 1
        }
