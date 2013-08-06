RESTful API PHP Framework
=========================

Overview
--------
This framework transform PHP classes in RESTful webservices.

Features
--------
Easily transform a generic PHP class in a RESTful API.
A HTTP request will be converted in object creation and then one corresponding
method execution. Having only one method execution is a costraint from the
stateless nature of RESTful webservices.

One installation can manage many classes.
The basic PHP spl_autoload() mechanism is implemented.
You can spl_autoload_register() you own autoload mechanism in the stack.

The framework provide a routing mechanism, providing consistent and multiformed
URLs.
http://mywebsite.domanin/basepath/resourcename/methodname?param1=value1&param2=value2
where resources, methods and params names are the class names (converted to
lowercase).

Parameters' names, order and defaults are managed internally and fit the
classes structure.

The framework provide a discovery service, to make the consumer life easier.
The discovery service extracts informations from the class and from its
docComment. Having very well commented code will result in self-documented
services.
Accessing http://mywebsite.domanin/basepath/ with your browser will guide your
consumers to the service discovery and usage.

GET, POST (untested), PUT (untested) and DELETE (untested) HTTP methods are
supported. However, each class is bound to a single method. This limitation is
to avoid a extra configuration for each class function.

The framework is able to output 4 'Content-Type:'s:
- JSON (application/json);
- JSONp (text/javascript)
- XML (application/xml, in the WDDX format, see http://en.wikipedia.org/wiki/WDDX);
- HTML (text/html, oriented to human understanding and debugging);
- TXT (text/plain, simply a print_r dump of the output).

The 'Content-Type' selection is managed via the 'Accept:' header of the request
or via a virtual "extension" applied to the method name. The latter will
override the former.
http://mywebsite.domanin/resourcename/methodname.xml?param1=value1&param2=value2
will output 'application/xml'.

The configuration is very simple, and potentially unnecessary if you have a
default installation and no constructors' params.
If you want to drive your own installation paths and/or if you want prepare
constructors' params, you can configure them via an easy ini file.

Caches are managed via appropriate headers, including appropriate responses
based on If-Match and If-Modified-Since request headers and response status
codes.

Internal exceptions and errors are managed via 5xx HTTP status codes, while
request errors are managed via 4xx status codes.
However, resurce classes can rise an HTTP status as exception code and it will
returned as response code.
