# CORS

[Cross Origin Resource Sharing](https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS) for TYPO3 CMS.

## Configuration

All configuration options can be set via TypoScript setup in `config` or per page object in `page.config`. The following options are available:

| Option | Type | Description |
|-|-|-|
| `allowCredentials` | int/boolean | Processing of the `credentials` flag |
| `allowHeaders` | string |  List of allowed headers (X-Foo, ...), [simple headers](http://www.w3.org/TR/2014/REC-cors-20140116/#simple-header) are always allowed |
| `allowMethods` | string |  List of allowed methods (PUT, DELETE, ...), [simple methods](http://www.w3.org/TR/2014/REC-cors-20140116/#simple-method) are always allowed |
| `allowOrigin` | string |  List of allowed origins |
| `allowOrigin.pattern` | string |  Regular expression for matching origins, make sure to escape as necessary |
| `exposeHeaders` | string |  List of headers exposed to clients |
| `maxAge` | int |  Cache lifetime of preflight requests, watch out for browser limits |

Note that all options support [stdWrap](http://docs.typo3.org/typo3cms/TyposcriptReference/Functions/Stdwrap/Index.html) processing through their `.stdWrap` property.

### Examples

* Origin wildcarding:

        config {
          allowOrigin = *
        }

* Simple list of origins:

        config {
          allowOrigin = http://example.org, http://example.com
          // More readable version
          allowOrigin (
            http://example.org,
            http://example.com
          )
        }

* Matching origins via regular expressions:

        config {
          allowOrigin.pattern = https?://example\.(org|com)
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

* Set maximum age via some `stdWrap` processing:

        config {
          maxAge.stdWrap.cObject = TEXT
          maxAge.stdWrap.cObject {
            value = 600
          }
        }
