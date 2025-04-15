![Logo](https://repository-images.githubusercontent.com/198229221/9de925f4-668f-4f65-a9d0-e989db6d4fe3)

# Bolt

PHP library for communication with graph database over TCP socket with Bolt protocol specification. Bolt protocol was created by [Neo4j](https://neo4j.com/) and documentation is available at [https://www.neo4j.com/](https://www.neo4j.com/docs/bolt/current/). This library is aimed to be low level, support
all available versions and keep up with protocol messages architecture and specifications.

[![](https://img.shields.io/github/stars/stefanak-michal/Bolt)](https://github.com/neo4j-php/Bolt/stargazers)
[![](https://img.shields.io/packagist/dt/stefanak-michal/bolt)](https://packagist.org/packages/stefanak-michal/bolt/stats)
[![](https://img.shields.io/github/v/release/stefanak-michal/bolt)](https://github.com/neo4j-php/Bolt/releases)
[![](https://img.shields.io/github/commits-since/stefanak-michal/bolt/latest)](https://github.com/neo4j-php/Bolt/releases/latest)

[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/Z8Z5ABMLW)

[![image](https://github.com/neo4j-php/Bolt/assets/5502917/1aeb4e22-c9b1-4fe5-8956-1c2e784c4292)](https://awsmfoss.com/neo4j-bolt-php/)

## :office: Professional Support and Consulting

Need help with integrating communication with graph databases in your PHP project?  I offer professional support and consulting services related to graph databases.

My services include:

* Integration assistance
* Performance tuning
* Custom development
* Training

Website: https://stefanak.serv00.net/

## :label: Version support

We are trying to keep up and this library supports **Bolt <= 5.8**.

## :books: Supported ecosystems

- Neo4j [bolt compatibility](https://www.neo4j.com/docs/bolt/current/bolt-compatibility/)
- Memgraph [bolt compatibility](https://memgraph.com/docs/client-libraries)
- Amazon Neptune [bolt compatiblity](https://docs.aws.amazon.com/neptune/latest/userguide/access-graph-opencypher-bolt.html#access-graph-opencypher-bolt-connections)
- DozerDB
- ONgDB

## :white_check_mark: Requirements

This library keep up with [PHP supported versions](https://www.php.net/supported-versions.php) what means it is at **PHP^8.1**.

### PHP Extensions

- [mbstring](https://www.php.net/manual/en/book.mbstring.php)
- [sockets](https://www.php.net/manual/en/book.sockets.php) (optional) - Required when you use Socket connection class
- [openssl](https://www.php.net/manual/en/book.openssl.php) (optional) - Required when you use StreamSocket connection
  class with enabled SSL

## :floppy_disk: Installation

You can use composer or download this repository from github and manually implement it.

### Composer

Run the following command in your project to install the latest applicable version of the package:

`composer require stefanak-michal/bolt`

[Packagist](https://packagist.org/packages/stefanak-michal/bolt)

### Manual

1. Download source code from [github](https://github.com/stefanak-michal/php-bolt-driver/)
2. Unpack
3. Copy content of `src` directory into your project

## :desktop_computer: Usage

Concept of usage is based on Bolt messages. Bolt messages are mapped 1:1 as protocol methods. Available protocol methods depends on Bolt version. Communication works in [pipeline](https://www.neo4j.com/docs/bolt/current/bolt/message/#pipelining) and you can chain multiple Bolt messages before consuming response from the server.

Main `Bolt` class serves as Factory design pattern and it returns instance of protocol class by requested Bolt version. Basic usage consist of query execution and fetching response which is split in two methods. First message `run` is for sending queries. Second message `pull` is for fetching response from executed query on database. Response from database for Bolt message `pull` always contains n+1 rows because last entry is `success` message with
meta informations.

:information_source: More info about available Bolt messages: https://www.neo4j.com/docs/bolt/current/bolt/message/

### Available methods

**Bolt class**

| Method / Property    | Description                                                                                       | Type          | Parameters              | Return    |
|----------------------|---------------------------------------------------------------------------------------------------|---------------|-------------------------|-----------|
| __construct          | Bolt constructor                                                                                  | public        | IConnection $connection | Bolt      |
| setProtocolVersions  | Set allowed protocol versions for connection                                                      | public        | int/float/string ...$v  | Bolt      |
| setPackStreamVersion | Set PackStream version                                                                            | public        | int $version = 1        | Bolt      |
| build                | Create protocol instance. Method creates connection, executes handshake and do a version request. | public        |                         | AProtocol |
| $debug               | Print binary communication (as hex)                                                               | public static | bool                    |           |

**Protocol class**

| Method / Property | Description                                                  | Parameters                                                         |
|-------------------|--------------------------------------------------------------|--------------------------------------------------------------------|
| hello             | Connect to database                                          | array $extra                                                       |
| logon             | Perform authentification                                     | array $auth                                                        |
| logoff            | Log out authentificated user                                 |                                                                    |
| run               | Execute query. Response from database are meta informations. | string $statement<br/>array $parameters = []<br/>array $extra = [] |
| pull              | Pull result from executed query                              | array $extra = []                                                  |
| discard           | Discard result waiting for pull                              | array $extra = []                                                  |
| begin             | Start transaction                                            | array $extra = []                                                  |
| commit            | Commit transaction                                           |                                                                    |
| rollback          | Rollback transaction                                         |                                                                    |
| reset             | Send message to reset connection                             |                                                                    |
| telemetry         |                                                              | int $api                                                           |
| getVersion        | Get used protocol version                                    |                                                                    |
| getResponse       | Get waiting response from server                             |                                                                    |
| getResponses      | Get waiting responses from server                            |                                                                    |
|                   |                                                              |                                                                    |
| init              | @see hello                                                   |                                                                    |
| pullAll           | @see pull                                                    |                                                                    |
| discardAll        | @see discard                                                 |                                                                    |

Multiple methods accept argument called `$extra`. This argument can contain any of key-value by Bolt specification. This
argument was extended during Neo4j development which means the content of it changed. You should keep in mind what
version you are working with when using this argument. You can read more about extra parameter in Bolt documentation
where you can look into your version and bolt message.

:information_source: Annotation of methods in protocol classes contains direct link to specific version and message from mentioned
documentation website.

### Authentication

Method logon expects `$auth` array. This array has to contain up to these three keys: scheme, principal and credentials. It depends on chosen scheme, as content for the other keys. Look at following table to choose the right structure.

| scheme   | principal | credentials |
|----------|-----------|-------------|
| none     |           |             |
| basic    | username  | password    |
| bearer   |           | token       |
| kerberos |           | token       |

### Transactions

Bolt from version 3 supports transactions and protocol contains these methods:

- begin
- commit
- rollback

_`run` executes query in auto-commit transaction if explicit transaction was not open._

### Cypher query parameters

| Neo4j      | PHP                                                                                                                                                             |
|------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Null       | null                                                                                                                                                            |
| Boolean    | boolean                                                                                                                                                         |
| Integer    | integer                                                                                                                                                         |
| Float      | float                                                                                                                                                           |
| Bytes      | [Bytes class](https://github.com/stefanak-michal/php-bolt-driver/blob/master/src/packstream/Bytes.php)                                                          |
| String     | string                                                                                                                                                          |
| List       | array with consecutive numeric keys from 0                                                                                                                      |
| Dictionary | object or array which is not considered as list                                                                                                                 |
| Structure  | Classes implementing `IStructure` by protocol version ([docs](https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/))                               |

List or dictionary can be also provided as instance of class implementing `Bolt\packstream\IPackListGenerator`
or `Bolt\PackStream\IPackDictionaryGenerator`. This approach helps with memory management while working with big amount
of data. To learn more you can
check [performance test](https://github.com/neo4j-php/Bolt/blob/master/tests/PerformanceTest.php)
or [packer test](https://github.com/neo4j-php/Bolt/blob/master/tests/PackStream/v1/PackerTest.php).

:warning: Structures `Node`, `Relationship`, `UnboundRelationship` and `Path` cannot be used as parameter. They are available only
as received data from database.

### Example

```php
// Choose and create connection class and specify target host and port.
$conn = new \Bolt\connection\Socket('127.0.0.1', 7687);
// Create new Bolt instance and provide connection object.
$bolt = new \Bolt\Bolt($conn);
// Set requested protocol versions ..you can add up to 4 versions
$bolt->setProtocolVersions(5.4);
// Build and get protocol version instance which creates connection and executes handshake.
$protocol = $bolt->build();

// Initialize communication with database
$response = $protocol->hello()->getResponse();
// verify $response for successful initialization

// Login into database
$response = $protocol->logon(['scheme' => 'basic', 'principal' => 'neo4j', 'credentials' => 'neo4j'])->getResponse();
// verify $response for successful login

// Pipeline two messages. One to execute query with parameters and second to pull records.
$protocol
    ->run('RETURN $a AS num, $b AS str', ['a' => 123, 'b' => 'text'])
    ->pull();
    
// Fetch waiting server responses for pipelined messages.
foreach ($protocol->getResponses() as $response) {
    // $response is instance of \Bolt\protocol\Response.
    // First response is SUCCESS message for RUN message.
    // Second response is RECORD message for PULL message.
    // Third response is SUCCESS message for PULL message.
}
```

:information_source: Default settings for bolt protocol version is 4.3, 4.4 and 5.0 to 5.8. If you are within this list you can ommit calling `$bolt->setProtocolVersions(5.4);`.

### Autoload

Directory `src` contains autoload file which accepts only Bolt library namespaces. Main Bolt namespace points to this
directory. If you have installed this project with composer, you have to load `vendor/autoload.php`.

## :chains: Connection

Bolt class constructor accepts connection argument. This argument has to be instance of class which implements IConnection interface. Library offers few options.

**\Bolt\connection\Socket**

This class use php extension sockets and has better memory usage. More informations
here: [https://www.php.net/manual/en/book.sockets.php](https://www.php.net/manual/en/book.sockets.php)

**\Bolt\connection\StreamSocket**

This class uses php stream functions. Which is a part of php and there is no extensions needed. More informations
here: [https://www.php.net/manual/en/ref.stream.php](https://www.php.net/manual/en/ref.stream.php)

StreamSocket besides of implemented methods from interface has method to configure SSL. SSL option requires php extension openssl. When you want to activate SSL
you have to call method `setSslContextOptions`. This method accept array by php specification available
here: [https://www.php.net/manual/en/context.ssl.php](https://www.php.net/manual/en/context.ssl.php).

**\Bolt\connection\PStreamSocket**

This class extends StreamSocket and adds support for persistent connections. Upon reuse of connection remaining buffer is consumed and message RESET is automatically sent. PHP is stateless therefore using this connection class requires storing meta information about active TCP connection. These informations are stored in [PSR-16 cache](#minidisc-psr-16-cache).

:warning: If your system reuse persistent connection and meta information about it was lost for some reason, your attempt to connect will end with ConnectionTimeoutException. Repeated attempt to connect will succeed.

## :lock: SSL

### Neo4j Aura

Connecting to Aura requires encryption which is provided with SSL. To connect to Aura you have to use `StreamSocket` connection class and enable SSL.

```php
$conn = new \Bolt\connection\StreamSocket('helloworld.databases.neo4j.io');
// enable SSL
$conn->setSslContextOptions([
    'verify_peer' => true
]);
$bolt = new \Bolt\Bolt($conn);
```

https://www.php.net/manual/en/context.ssl.php

### Example on localhost database with self-signed certificate:

```php
$conn = new \Bolt\connection\StreamSocket();
$conn->setSslContextOptions([
    'local_cert'=> 'd:\www\bolt\cert\public_cert.pem',
    'local_pk' => 'd:\www\bolt\cert\private_key.pem',
    'passphrase' => 'password',
    'allow_self_signed' => true,
    'verify_peer' => false,
    'verify_peer_name' => false
]);
$bolt = new \Bolt\Bolt($conn);
```

:bookmark: You can also take a look at my article on how to implement SSL for Neo4j running on localhost
at [Neo4j and self signed certificate](https://ko-fi.com/post/Neo4j-and-self-signed-certificate-on-Windows-S6S2I0KQT).

## :stopwatch: Timeout

Connection class constructor contains `$timeout` argument. This timeout is for established socket connection. To set up
timeout for establishing socket connection itself you have to set ini directive `default_socket_timeout`.

_Setting up ini directive isn't part of connection class because function `ini_set` can be disabled on production
environments for security reasons._

## :vertical_traffic_light: Server state

Server state is not reported by server but it is evaluated by received response. You can access current state through property `$protocol->serverState`. This property is updated with every call `getResponse(s)`.

## :minidisc: PSR-16 cache

This library contains own PSR-16 cache implementation `\Bolt\helpers\FileCache`. It stores files in temporary directory. Obtaining cache instance throughout the project is secured with `\Bolt\helpers\CacheProvider::get()`. You can set own implementation by calling `\Bolt\helpers\CacheProvider::set($cache)`.

[PSR-16 specification](https://www.php-fig.org/psr/psr-16/)

## :bar_chart: Analytics

Bolt does collect anonymous analytics data. These data are just aggregated counts of executed queries and sessions. They are stored in [cache](#minidisc-psr-16-cache) and submitted once a day after midnight. You can opt out with environment variable `BOLT_ANALYTICS_OPTOUT`.

Analytics data are public and available at [Mixpanel](https://eu.mixpanel.com/p/7ttVKqvjdqJtGCjLCFgdeC).
